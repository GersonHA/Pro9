<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFreeProductToConfigurations extends Migration
{
    /**
     * Port de pro8 `2026_01_25_121254_add_free_product_to_configurations`.
     *
     * Agrega a `configurations`:
     *  - `allow_free_product` (bool, default false) → switch "Habilitar Producto Libre".
     *  - `product_free_id` (unsignedBigInteger nullable) → id del ítem comodín "LIBRE-SYS"
     *    con el que se facturan los productos temporales del POS.
     *
     * Idempotente: las 5 BD de Pro9 YA tienen ambas columnas (vinieron en los dumps de pro8).
     * Sin guard revienta con `errno 1060 Duplicate column`. No-op en tenants actuales, efectiva
     * en tenants nuevos. (pro8 dejó `down()` vacío; aquí sí se revierte.)
     */
    public function up()
    {
        if (!Schema::hasTable('configurations')) {
            return;
        }

        Schema::table('configurations', function (Blueprint $table) {
            if (!Schema::hasColumn('configurations', 'allow_free_product')) {
                $table->boolean('allow_free_product')->default(false)->after('amount_plastic_bag_taxes');
            }
        });

        Schema::table('configurations', function (Blueprint $table) {
            if (!Schema::hasColumn('configurations', 'product_free_id')) {
                $table->unsignedBigInteger('product_free_id')->nullable()->after('allow_free_product');
            }
        });
    }

    public function down()
    {
        if (!Schema::hasTable('configurations')) {
            return;
        }

        Schema::table('configurations', function (Blueprint $table) {
            if (Schema::hasColumn('configurations', 'product_free_id')) {
                $table->dropColumn('product_free_id');
            }
            if (Schema::hasColumn('configurations', 'allow_free_product')) {
                $table->dropColumn('allow_free_product');
            }
        });
    }
}
