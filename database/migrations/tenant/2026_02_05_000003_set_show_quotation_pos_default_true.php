<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

class SetShowQuotationPosDefaultTrue extends Migration
{
    /**
     * Correctiva: `show_quotation_pos` se creó con default false en los tenants del
     * ensayo local. Se decide que la cotización nazca VISIBLE en el POS (como pro8),
     * y quien no la quiera la apaga desde config/avanzado/pos.
     *
     * Fija el default de columna a 1 y enciende el flag en los registros existentes.
     * Idempotente y no-op en prod (donde la columna ya nace con default true).
     */
    public function up() {
        if (!Schema::hasColumn('configurations', 'show_quotation_pos')) {
            return;
        }

        // Default a nivel de columna (para nuevos registros de configuración).
        DB::connection('tenant')->statement(
            'ALTER TABLE configurations MODIFY show_quotation_pos TINYINT(1) NOT NULL DEFAULT 1'
        );

        // Enciende la cotización en los tenants existentes (nace encendida).
        DB::connection('tenant')->table('configurations')->update(['show_quotation_pos' => 1]);
    }

    /**
     * Reversa: vuelve el default de columna a 0. No re-apaga los registros
     * (podrían haber sido activados intencionalmente).
     */
    public function down() {
        if (!Schema::hasColumn('configurations', 'show_quotation_pos')) {
            return;
        }

        DB::connection('tenant')->statement(
            'ALTER TABLE configurations MODIFY show_quotation_pos TINYINT(1) NOT NULL DEFAULT 0'
        );
    }
}
