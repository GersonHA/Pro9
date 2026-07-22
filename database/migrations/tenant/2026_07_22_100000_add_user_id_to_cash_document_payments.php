<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Plan #B (Responsable per-row): agregar user_id al pivote cash_document_payments
 * para registrar QUÉN creó cada movimiento de caja. Permite que el reporte de
 * caja Pos (migración 6526b745) muestre "Responsable" por fila en lugar del
 * dueño de la caja (relevante para Caja Compartida #46).
 *
 * - Idempotente: hasColumn check evita doble-add
 * - Nullable: filas históricas quedan con NULL → fallback a $cash->user->name
 *   en el render del Excel. Backfill retroactivo NO es posible (no hay forma
 *   de saber quién hizo cada pivote antiguo).
 */
class AddUserIdToCashDocumentPayments extends Migration
{
    public function up()
    {
        Schema::table('cash_document_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('cash_document_payments', 'user_id')) {
                $table->unsignedInteger('user_id')->nullable()->after('cash_document_credit_id');
                $table->foreign('user_id')->references('id')->on('users');
            }
        });
    }

    public function down()
    {
        Schema::table('cash_document_payments', function (Blueprint $table) {
            if (Schema::hasColumn('cash_document_payments', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
        });
    }
}