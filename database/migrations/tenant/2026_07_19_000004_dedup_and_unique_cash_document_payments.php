<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class DedupAndUniqueCashDocumentPayments extends Migration
{
    /**
     * Limpia el doble registro de pagos en caja (bug heredado de pro8 pre-fix
     * 6910789d) y lo bloquea con un índice único. Cuatro escritores con llaves
     * distintas amarraban un mismo pago dos veces (una fila con
     * cash_document_id y otra sin él), inflando los reportes que suman por
     * JOIN. El cierre de caja no se afectaba porque filtra pago por pago, pero
     * los reportes de efectivo sí.
     *
     * Idempotente: el guard contra `information_schema.STATISTICS` evita el
     * `errno 1061 Duplicate key name` si la migración se corre 2x. El dedup
     * con `whereIn id IN (...)` ya es benigno en re-corrida.
     */
    public function up()
    {
        if (!Schema::hasTable('cash_document_payments')) {
            // Tabla aún no creada (instalación fresca) — skip
            return;
        }

        // 1. Deduplicar: por cada pago repetido, conservar la fila más completa
        //    (la que tiene cash_document_id) y, a igualdad, la más antigua.
        foreach (['document_payment_id', 'sale_note_payment_id'] as $field) {
            $duplicated = DB::table('cash_document_payments')
                ->select($field)
                ->whereNotNull($field)
                ->groupBy($field)
                ->havingRaw('COUNT(*) > 1')
                ->pluck($field);

            foreach ($duplicated as $paymentId) {
                $rows = DB::table('cash_document_payments')
                    ->where($field, $paymentId)
                    ->orderByRaw('cash_document_id IS NULL') // 0 (con valor) antes que 1 (null)
                    ->orderBy('id')
                    ->get();

                $rows->shift(); // conservamos la primera (la más completa/antigua)
                $idsToDelete = $rows->pluck('id')->all();

                if (!empty($idsToDelete)) {
                    DB::table('cash_document_payments')->whereIn('id', $idsToDelete)->delete();
                }
            }
        }

        // 2. Candado en la base: un pago solo puede amarrarse a UNA caja.
        //    Idempotente: solo creamos el índice si aún no existe. Se consulta
        //    information_schema en vez de doctrine/dbal (que puede no estar
        //    instalado en Pro9).
        $connection = Schema::getConnection();
        $database   = $connection->getDatabaseName();
        $indexExists = function ($indexName) use ($connection, $database) {
            return count($connection->select(
                "SELECT 1 FROM information_schema.STATISTICS
                 WHERE table_schema = ? AND table_name = 'cash_document_payments'
                   AND index_name = ?",
                [$database, $indexName]
            )) > 0;
        };

        Schema::table('cash_document_payments', function (Blueprint $table) use ($indexExists) {
            if (!$indexExists('cdp_document_payment_id_unique')) {
                $table->unique('document_payment_id', 'cdp_document_payment_id_unique');
            }
            if (!$indexExists('cdp_sale_note_payment_id_unique')) {
                $table->unique('sale_note_payment_id', 'cdp_sale_note_payment_id_unique');
            }
        });
    }

    public function down()
    {
        if (!Schema::hasTable('cash_document_payments')) return;

        $connection = Schema::getConnection();
        $database   = $connection->getDatabaseName();
        $indexExists = function ($indexName) use ($connection, $database) {
            return count($connection->select(
                "SELECT 1 FROM information_schema.STATISTICS
                 WHERE table_schema = ? AND table_name = 'cash_document_payments'
                   AND index_name = ?",
                [$database, $indexName]
            )) > 0;
        };

        Schema::table('cash_document_payments', function (Blueprint $table) use ($indexExists) {
            if ($indexExists('cdp_document_payment_id_unique')) {
                $table->dropUnique('cdp_document_payment_id_unique');
            }
            if ($indexExists('cdp_sale_note_payment_id_unique')) {
                $table->dropUnique('cdp_sale_note_payment_id_unique');
            }
        });
    }
}