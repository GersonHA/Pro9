<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddShowQuotationPosToConfigurations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        // Idempotente: si la columna ya existe (tenant donde corrió de facto), se salta.
        // Así es segura tanto en local como en el cutover a prod (donde no existirá).
        // Nace ENCENDIDA (default true): la cotización se ve en el POS como en pro8;
        // quien no la quiera la desactiva desde config/avanzado/pos.
        Schema::table('configurations', function(Blueprint $table) {
            if (!Schema::hasColumn('configurations', 'show_quotation_pos')) {
                $table->boolean('show_quotation_pos')->default(true);
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('configurations', function(Blueprint $table) {
            if (Schema::hasColumn('configurations', 'show_quotation_pos')) {
                $table->dropColumn('show_quotation_pos');
            }
        });
    }
}
