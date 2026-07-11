<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration ported from pro8 commit ad091bb6 (2026-03-06) for Caja Compartida
 * ("La Puerta Proxy") + start_route post-login per-user feature.
 *
 * - start_route: ruta de redirección post-login (string nullable).
 * - default_cash_id: FK a cash.id que apunta a la caja del "jefe" cuando el
 *   vendedor factura contra la caja de otro usuario.
 *
 * Idempotente: si las columnas ya existen (típico en HENAVI por dump de datos
 * pro8), no hace nada. Aplicada en BD por dump pero el archivo se incluye para
 * trazabilidad del repo y para tenants futuros.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'start_route')) {
                $table->string('start_route')->nullable()->after('multiple_default_document_types');
            }
            if (!Schema::hasColumn('users', 'default_cash_id')) {
                $table->unsignedInteger('default_cash_id')->nullable()->after('start_route');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['start_route', 'default_cash_id']);
        });
    }
};
