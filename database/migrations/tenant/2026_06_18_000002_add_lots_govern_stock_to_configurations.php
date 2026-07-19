<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLotsGovernStockToConfigurations extends Migration
{
    public function up()
    {
        Schema::table('configurations', function (Blueprint $table) {
            $table->boolean('lots_govern_stock')->default(false)
                ->after('product_only_location')
                ->comment('Los lotes mandan sobre el stock (ADR-0015): al guardar lotes, stock = Σlotes + ajuste en kardex');
        });
    }

    public function down()
    {
        Schema::table('configurations', function (Blueprint $table) {
            $table->dropColumn('lots_govern_stock');
        });
    }
}
