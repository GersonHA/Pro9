<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSellerViewSaleNotesToConfigurations extends Migration
{
    public function up()
    {
        Schema::table('configurations', function (Blueprint $table) {
            if (!Schema::hasColumn('configurations', 'seller_can_view_sale_notes_by_establishment')) {
                $table->boolean('seller_can_view_sale_notes_by_establishment')->default(false);
            }
        });
    }

    public function down()
    {
        Schema::table('configurations', function (Blueprint $table) {
            if (Schema::hasColumn('configurations', 'seller_can_view_sale_notes_by_establishment')) {
                $table->dropColumn('seller_can_view_sale_notes_by_establishment');
            }
        });
    }
}
