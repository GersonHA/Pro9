<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPrintItemsPdfToItemsTable extends Migration
{
    public function up()
    {
        Schema::table('items', function (Blueprint $table) {
            if (!Schema::hasColumn('items', 'print_items_pdf')) {
                $table->boolean('print_items_pdf')->default(false)->after('is_set');
            }
        });
    }

    public function down()
    {
        Schema::table('items', function (Blueprint $table) {
            if (Schema::hasColumn('items', 'print_items_pdf')) {
                $table->dropColumn('print_items_pdf');
            }
        });
    }
}