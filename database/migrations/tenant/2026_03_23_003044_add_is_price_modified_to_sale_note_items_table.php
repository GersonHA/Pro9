<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsPriceModifiedToSaleNoteItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Idempotente: si la columna ya existe (vino del dump de pro8 o ya se aplicó),
        // no la recreamos. Esto permite correr la migración sin romper tenants existentes.
        Schema::table('sale_note_items', function (Blueprint $table) {
            if (!Schema::hasColumn('sale_note_items', 'is_price_modified')) {
                $table->boolean('is_price_modified')->default(false)->after('unit_price');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sale_note_items', function (Blueprint $table) {
            if (Schema::hasColumn('sale_note_items', 'is_price_modified')) {
                $table->dropColumn('is_price_modified');
            }
        });
    }
}
