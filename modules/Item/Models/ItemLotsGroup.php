<?php

namespace Modules\Item\Models;

use App\Models\Tenant\Item;
use App\Models\Tenant\ModelTenant;
use App\Models\Tenant\Warehouse;
use Illuminate\Support\Facades\Cache;
use Modules\Inventory\Models\InventoryTransferItem;
use Modules\Inventory\Models\ItemWarehouse;


/**
 * Modules\Item\Models\ItemLotsGroup
 *
 * @property Item $item
 * @method static \Illuminate\Database\Eloquent\Builder|ItemLotsGroup newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ItemLotsGroup newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ItemLotsGroup query()
 * @mixin \Eloquent
 */
class ItemLotsGroup extends ModelTenant
{
    protected $table = 'item_lots_group';

    /**
     * Código del lote fantasma que absorbe el stock sin clasificar (ADR-0010/0012).
     * Única fila por (item, almacén) — vence el LIBRE_DUE_DATE para ser FEFO-último.
     */
    public const LIBRE_CODE = 'LIBRE';

    /**
     * Fecha de vencimiento del LIBRE. 9999-12-31 lo deja siempre al final del orden
     * FEFO: los lotes con fecha real salen primero.
     */
    public const LIBRE_DUE_DATE = '9999-12-31';

    /**
     * Los lotes se mutan directo (traslado FEFO en InventoryChangeServiceProvider, descuento de
     * NV vía listener, etc.) sin pasar por ItemController, que es el único que hacía forget del
     * caché. Sin esto el form mostraba lotes viejos hasta que expiraba item_detail_{id} (TTL 1h):
     * el stock se veía actualizado pero el lote no. Invalidamos por-item (no flush del tag) para
     * no vaciar el caché de todos los productos en cada venta.
     */
    protected static function booted()
    {
        static::saved(function ($lot) {
            Cache::tags(['item_detail'])->forget("item_detail_{$lot->item_id}");
            Cache::tags(['items_list'])->flush();
        });

        static::deleted(function ($lot) {
            Cache::tags(['item_detail'])->forget("item_detail_{$lot->item_id}");
            Cache::tags(['items_list'])->flush();
        });
    }


    protected $fillable = [
        'code',
        'quantity',
        'date_of_due',
        'item_id',
        'warehouse_id',
        'old_quantity',
        'has_movements',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function inventory_transfer_item()
    {
        return $this->hasMany(InventoryTransferItem::class);
    }

    /**
     * Sincroniza el lote LIBRE de cada almacén con el stock SIN CLASIFICAR del item.
     *
     * Stock sin clasificar = unidades reales en item_warehouse.stock que ningún lote
     * con fecha representa. Aparece al activar lotes en un producto que ya tenía stock
     * en varios almacenes: solo el almacén donde se registran lotes a mano obtiene
     * item_lots_group; los demás quedan con stock sin lote, imposible de trasladar o
     * vender por canales que exigen lote.
     *
     * El LIBRE absorbe ese sobrante manteniendo el invariante, por almacén:
     *     LIBRE.quantity = max(0, item_warehouse.stock − Σ(lotes reales del almacén))
     *
     * El LIBRE representa SOLO stock real positivo (≥0). El déficit de sobreventa
     * (stock negativo por vender bajo cero con stock_control OFF) NO vive en un lote:
     * queda en item_warehouse.stock negativo, gobernado por stock_control. Por eso se
     * clampa a 0 — un LIBRE negativo histórico se auto-sana en el próximo sync.
     *
     * Como el LIBRE vence 9999-12-31 queda último en FEFO (los lotes con fecha real
     * salen primero) y al registrar un lote real el LIBRE baja solo, porque Σ de reales
     * sube. Solo aplica a productos con lots_enabled.
     *
     * @param  int      $item_id
     * @param  int|null $warehouse_id  Si se indica, solo ese almacén; si no, todos.
     */
    public static function syncLibreForOrphanStock(int $item_id, ?int $warehouse_id = null): void
    {
        $item = Item::find($item_id);
        if (!$item || !$item->lots_enabled) return;

        $warehouses = ItemWarehouse::where('item_id', $item_id)
            ->when($warehouse_id, fn ($q) => $q->where('warehouse_id', $warehouse_id))
            ->get();

        foreach ($warehouses as $item_warehouse) {
            $real_lots_qty = (float) self::where('item_id', $item_id)
                ->where('warehouse_id', $item_warehouse->warehouse_id)
                ->where('code', '!=', self::LIBRE_CODE)
                ->sum('quantity');

            // El déficit (stock − reales < 0, sobreventa) no entra al lote: se clampa a 0.
            $unclassified = max(0, (float) $item_warehouse->stock - $real_lots_qty);

            $libre = self::firstOrCreate(
                [
                    'item_id' => $item_id,
                    'warehouse_id' => $item_warehouse->warehouse_id,
                    'code' => self::LIBRE_CODE,
                ],
                [
                    'quantity' => 0,
                    'old_quantity' => 0,
                    'date_of_due' => self::LIBRE_DUE_DATE,
                    'has_movements' => false,
                ]
            );
            $libre->quantity = $unclassified;
            $libre->save();
        }
    }

    /**
     * Detecta sobre-asignación de lotes: almacenes cuya suma de cantidades de
     * lotes reales propuesta excede el stock real disponible.
     *
     * La suma de lotes reales NUNCA puede superar el stock del almacén (sí puede
     * ser menor: el sobrante lo absorbe el lote LIBRE, ver syncLibreForOrphanStock).
     * Este método es el límite superior complementario de ese invariante: impide
     * que la captura de lotes deje al LIBRE en negativo (stock fantasma).
     *
     * @param  array $sum_by_warehouse   [warehouse_id => Σ cantidades de lotes reales]
     * @param  array $stock_by_warehouse [warehouse_id => stock real del almacén]
     * @return array Violaciones: [['warehouse_id','description','sum','stock'], ...]
     */
    public static function findStockOverAllocations(array $sum_by_warehouse, array $stock_by_warehouse): array
    {
        // Tolerancia para evitar falsos positivos por aritmética de punto flotante.
        $epsilon = 1e-6;
        $violations = [];

        foreach ($sum_by_warehouse as $warehouse_id => $sum) {
            $sum   = (float) $sum;
            $stock = (float) ($stock_by_warehouse[$warehouse_id] ?? 0);

            if ($sum - $stock > $epsilon) {
                $warehouse = Warehouse::find($warehouse_id);
                $violations[] = [
                    'warehouse_id' => $warehouse_id,
                    'description'  => $warehouse ? $warehouse->description : "Almacén #{$warehouse_id}",
                    'sum'          => $sum,
                    'stock'        => $stock,
                ];
            }
        }

        return $violations;
    }

    /**
     * @return mixed
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param mixed $code
     *
     * @return ItemLotsGroup
     */
    public function setCode($code)
    {
        $this->code = $code;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @param mixed $quantity
     *
     * @return ItemLotsGroup
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDateOfDue()
    {
        return $this->date_of_due;
    }

    /**
     * @param mixed $date_of_due
     *
     * @return ItemLotsGroup
     */
    public function setDateOfDue($date_of_due)
    {
        $this->date_of_due = $date_of_due;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getItemId()
    {
        return $this->item_id;
    }

    /**
     * @param mixed $item_id
     *
     * @return ItemLotsGroup
     */
    public function setItemId($item_id)
    {
        $this->item_id = $item_id;
        return $this;
    }


    /**
     *
     * Obtener datos para formulario de venta de lotes
     *
     * @return array
     */
    public function getRowResourceSale()
    {
        return [
            'id'          => $this->id,
            'code'        => $this->code,
            'quantity'    => $this->quantity,
            'date_of_due' => $this->date_of_due,
            'checked'     => false,
            'compromise_quantity' => 0
        ];
    }


}