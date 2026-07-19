<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Agrega warehouse_id a item_lots_group.
 *
 * Razón: el diseño original (2020) trató los lotes como globales por item.
 * En multi-almacén el mismo lote puede tener stock distinto en cada sucursal,
 * por lo que necesitamos un registro separado por (item, lote, almacén).
 *
 * Estrategia para datos existentes:
 * - Se asigna cada registro al primer almacén donde el item tiene stock.
 * - Si no hay ninguno, se usa warehouse_id = 1 como fallback.
 * - El constraint único anterior (code, item_id, date_of_due) se reemplaza
 *   por (code, item_id, date_of_due, warehouse_id).
 */
class AddWarehouseToItemLotsGroup extends Migration
{
    public function up()
    {
        // 1. Agregar columna nullable para no romper registros existentes.
        Schema::table('item_lots_group', function (Blueprint $table) {
            $table->unsignedInteger('warehouse_id')->nullable()->after('item_id');
        });

        // 2. Poblar warehouse_id en registros existentes.
        //    Intentar inferirlo desde item_warehouse (primer almacén con stock > 0).
        $lots = DB::table('item_lots_group')->whereNull('warehouse_id')->get();
        foreach ($lots as $lot) {
            $warehouse = DB::table('item_warehouse')
                ->where('item_id', $lot->item_id)
                ->where('stock', '>', 0)
                ->orderBy('warehouse_id')
                ->first();

            $warehouse_id = $warehouse ? $warehouse->warehouse_id : 1;

            DB::table('item_lots_group')
                ->where('id', $lot->id)
                ->update(['warehouse_id' => $warehouse_id]);
        }

        // 3. Eliminar el constraint único anterior (code, item_id, date_of_due)
        //    creado en la migración 2026_05_19.
        try {
            Schema::table('item_lots_group', function (Blueprint $table) {
                $table->dropUnique('item_lots_group_code_item_date_unique');
            });
        } catch (\Exception $e) {
            // Si no existía la constraint, continuar sin error.
        }

        // 4. Resolver posibles duplicados (mismo code+item_id+date_of_due en el mismo
        //    warehouse ahora que agrupamos por warehouse) antes de crear el constraint.
        $duplicates = DB::table('item_lots_group')
            ->select(
                'code', 'item_id', 'date_of_due', 'warehouse_id',
                DB::raw('MIN(id) as keep_id'),
                DB::raw('SUM(quantity) as total_qty')
            )
            ->groupBy('code', 'item_id', 'date_of_due', 'warehouse_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $dup) {
            DB::table('item_lots_group')
                ->where('id', $dup->keep_id)
                ->update(['quantity' => $dup->total_qty]);

            DB::table('item_lots_group')
                ->where('code', $dup->code)
                ->where('item_id', $dup->item_id)
                ->where('date_of_due', $dup->date_of_due)
                ->where('warehouse_id', $dup->warehouse_id)
                ->where('id', '!=', $dup->keep_id)
                ->delete();
        }

        // 5. Agregar FK y nuevo constraint único (code, item_id, date_of_due, warehouse_id).
        Schema::table('item_lots_group', function (Blueprint $table) {
            $table->foreign('warehouse_id')
                  ->references('id')->on('warehouses')
                  ->onDelete('restrict');

            $table->unique(
                ['code', 'item_id', 'date_of_due', 'warehouse_id'],
                'item_lots_group_code_item_date_warehouse_unique'
            );
        });
    }

    public function down()
    {
        Schema::table('item_lots_group', function (Blueprint $table) {
            $table->dropUnique('item_lots_group_code_item_date_warehouse_unique');
            $table->dropForeign(['warehouse_id']);
            $table->dropColumn('warehouse_id');
        });

        // Restaurar constraint anterior
        Schema::table('item_lots_group', function (Blueprint $table) {
            $table->unique(['code', 'item_id', 'date_of_due'], 'item_lots_group_code_item_date_unique');
        });
    }
}
