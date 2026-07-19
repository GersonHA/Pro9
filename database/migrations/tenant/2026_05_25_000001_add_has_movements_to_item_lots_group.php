<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddHasMovementsToItemLotsGroup extends Migration
{
    public function up()
    {
        Schema::table('item_lots_group', function (Blueprint $table) {
            // Indica si el lote fue usado en algún movimiento de inventario
            // (venta, compra, traslado, ajuste). Cuando es true, código y
            // fecha de vencimiento se vuelven inmutables para preservar la
            // integridad del historial.
            $table->boolean('has_movements')->default(false)->after('warehouse_id');
        });
    }

    public function down()
    {
        Schema::table('item_lots_group', function (Blueprint $table) {
            $table->dropColumn('has_movements');
        });
    }
}
