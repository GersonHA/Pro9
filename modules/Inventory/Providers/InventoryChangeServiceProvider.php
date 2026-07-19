<?php

namespace Modules\Inventory\Providers;

use App\Models\Tenant\Item;
use Illuminate\Support\ServiceProvider;
use Modules\Inventory\Models\Inventory;
use Modules\Inventory\Traits\InventoryTrait;
use Modules\Inventory\Models\ItemWarehouse;
use Modules\Item\Models\ItemLotsGroup;

class InventoryChangeServiceProvider extends ServiceProvider
{
    use InventoryTrait;

    public function register()
    {
    }

    public function boot()
    {
        $this->createdItem();
        $this->inventory();
    }

    private function createdItem()
    {

        Item::created(function ($item) {


            if($item->unit_type_id == 'ZZ')
            {
                return;
            }
            $warehouse = ($item->warehouse_id) ? $this->findWarehouse($this->findWarehouseById($item->warehouse_id)->establishment_id) : $this->findWarehouse();
            if(!$item->is_set){
                $this->createInitialInventory($item->id, $item->stock, $warehouse->id);
            }else{
                $item_warehouse = ItemWarehouse::firstOrNew(['item_id' => $item->id, 'warehouse_id' => $warehouse->id]);
                $item_warehouse->stock = 0;
                $item_warehouse->save();
            }

        });
    }

    private function inventory()
    {
        Inventory::created(function ($inventory) {
            switch ($inventory->type) {
                case 1:
                    $this->createInventoryKardex($inventory, $inventory->item_id, $inventory->quantity, $inventory->warehouse_id);
                    $this->updateStock($inventory->item_id, $inventory->quantity, $inventory->warehouse_id);
                    break;
                case 2:
                    //Origin
                    $this->createInventoryKardex($inventory, $inventory->item_id, -1 * $inventory->quantity, $inventory->warehouse_id);
                    $this->updateStock($inventory->item_id, -1 * $inventory->quantity, $inventory->warehouse_id);
                    //Arrival — garantizar que ItemWarehouse exista en destino antes de que
                    // getRealWarehouseId() lo busque; si no existe lo crea con stock 0
                    ItemWarehouse::firstOrCreate(
                        ['item_id' => $inventory->item_id, 'warehouse_id' => $inventory->warehouse_destination_id],
                        ['stock' => 0]
                    );
                    $this->createInventoryKardex($inventory, $inventory->item_id, $inventory->quantity, $inventory->warehouse_destination_id);
                    $this->updateStock($inventory->item_id, $inventory->quantity, $inventory->warehouse_destination_id);

                    // Trasladar lotes entre almacenes si el item maneja lotes por grupo.
                    // Usa FEFO: descuenta del lote más próximo a vencer en origen y acredita
                    // el equivalente (mismo code + date_of_due) en destino.
                    $this->transferLotsGroupBetweenWarehouses(
                        $inventory->item_id,
                        $inventory->warehouse_id,
                        $inventory->warehouse_destination_id,
                        (float) $inventory->quantity
                    );

                    // Reafirmar el invariante en ambos extremos. Auto-sana stock huérfano
                    // que nunca se sincronizó: si el origen no tenía lotes, el traslado no
                    // movió ninguno pero sí el stock; aquí el LIBRE absorbe el residual.
                    $this->syncLibreLotForOrphanStock($inventory->item_id, $inventory->warehouse_id);
                    $this->syncLibreLotForOrphanStock($inventory->item_id, $inventory->warehouse_destination_id);
                    break;
                case 3:
                    $this->createInventoryKardex($inventory, $inventory->item_id, -1 * $inventory->quantity, $inventory->warehouse_id);
                    $this->updateStock($inventory->item_id, -1 * $inventory->quantity, $inventory->warehouse_id);
                    break;
                default:
                    //aqui en el defualt tendria que acceder a la inventory_transactions y determinar el tipo de transaccion
                    //si es ingreso sumo, caso contrario descuento
                    $inventory_transaction = $this->findInventoryTransaction($inventory->inventory_transaction_id);

                    if($inventory_transaction->type === 'input'){

                        $this->createInventoryKardex($inventory, $inventory->item_id, $inventory->quantity, $inventory->warehouse_id);
                        $this->updateStock($inventory->item_id, $inventory->quantity, $inventory->warehouse_id);

                    }else{

                        $this->createInventoryKardex($inventory, $inventory->item_id, -1 * $inventory->quantity, $inventory->warehouse_id);
                        $this->updateStock($inventory->item_id, -1 * $inventory->quantity, $inventory->warehouse_id);

                    }
                    break;
            }
        });
    }

    /**
     * Mueve cantidades de lotes entre almacenes usando orden FEFO.
     *
     * Para cada unidad trasladada, descuenta del lote más próximo a vencer en el
     * almacén origen y acredita el mismo lote (por code + date_of_due) en destino,
     * creándolo si no existe.
     *
     * @param  int   $item_id
     * @param  int   $warehouse_origin_id
     * @param  int   $warehouse_destination_id
     * @param  float $quantity_to_transfer
     */
    private function transferLotsGroupBetweenWarehouses(
        int $item_id,
        int $warehouse_origin_id,
        int $warehouse_destination_id,
        float $quantity_to_transfer
    ): void {
        $item = Item::find($item_id);
        if (!$item || !$item->lots_enabled) return;

        $origin_lots = ItemLotsGroup::where('item_id', $item_id)
            ->where('warehouse_id', $warehouse_origin_id)
            ->where('quantity', '>', 0)
            ->orderBy('date_of_due', 'asc')
            ->get();

        $remaining = $quantity_to_transfer;

        foreach ($origin_lots as $origin_lot) {
            if ($remaining <= 0) break;

            $move = min((float) $origin_lot->quantity, $remaining);

            // Descontar en origen
            $origin_lot->quantity = (float) $origin_lot->quantity - $move;
            $origin_lot->has_movements = true;
            $origin_lot->save();

            // Acreditar en destino (mismo lote, distinto almacén)
            $dest_lot = ItemLotsGroup::firstOrCreate(
                [
                    'code'         => $origin_lot->code,
                    'item_id'      => $item_id,
                    'date_of_due'  => $origin_lot->date_of_due,
                    'warehouse_id' => $warehouse_destination_id,
                ],
                ['quantity' => 0]
            );
            $dest_lot->increment('quantity', $move);

            $remaining -= $move;
        }
    }

}
