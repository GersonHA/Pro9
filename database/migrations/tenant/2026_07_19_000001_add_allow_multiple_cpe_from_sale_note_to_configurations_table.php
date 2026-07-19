<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Feature port: allow_multiple_cpe_from_sale_note
 * Origen: pro8/database/migrations/tenant/2026_05_23_000001_add_allow_multiple_cpe_from_sale_note_to_configurations_table.php
 * ADR/tarea: Wiki/pendientes/pendientes.md (Fix #43 Acciones múltiples NV)
 *
 * IDEMPOTENTE: usa hasColumn() para no romper si ya fue aplicada.
 */
class AddAllowMultipleCpeFromSaleNoteToConfigurationsTable extends Migration
{
    public function up()
    {
        Schema::table('configurations', function (Blueprint $table) {
            if (!Schema::hasColumn('configurations', 'allow_multiple_cpe_from_sale_note')) {
                $table->boolean('allow_multiple_cpe_from_sale_note')
                      ->default(false)
                      ->after('quotation_allow_seller_generate_sale');
            }
        });
    }

    public function down()
    {
        Schema::table('configurations', function (Blueprint $table) {
            if (Schema::hasColumn('configurations', 'allow_multiple_cpe_from_sale_note')) {
                $table->dropColumn('allow_multiple_cpe_from_sale_note');
            }
        });
    }
}
