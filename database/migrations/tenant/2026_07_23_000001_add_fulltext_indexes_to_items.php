<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFulltextIndexesToItems extends Migration
{
    /**
     * Port de pro8 `2026_04_07_234132_add_fulltext_indexes_to_items`. Alinea el
     * esquema de índices de `items` entre ambos repos de cara al cutover.
     *
     * Por qué hace falta si las BD ya los tienen: los 5 tenants actuales de Pro9
     * se restauraron desde dumps de producción de pro8, así que estos índices
     * llegaron dentro del dump y no de una migración. Un tenant NUEVO creado por
     * Pro9 nacería sin ellos. La migración es no-op en los tenants existentes y
     * efectiva solo en los nuevos.
     *
     * Idempotente por guard contra information_schema.STATISTICS — no try/catch
     * como pro8, que también se tragaba los errores reales. Sin el guard la
     * corrida revienta con `errno 1061 Duplicate key name` en los 5 tenants.
     * Mismo patrón que 2026_07_19_000004_dedup_and_unique_cash_document_payments.
     *
     * Los FULLTEXT de `model`, `second_name` y `name` se portan por fidelidad de
     * esquema, pero hoy ninguna consulta los lee: en pro8 tampoco se usaron nunca
     * (el único MATCH era sobre description+internal_id) y ese Modo Turbo se
     * eliminó por medirse más lento que el LIKE simple. Quedan como candidatos a
     * DROP en un pase de limpieza posterior.
     */
    public function up()
    {
        if (!Schema::hasTable('items')) {
            return;
        }

        $indexExists = $this->indexChecker();

        // Índices de texto. Cada columna opcional se comprueba antes: en una
        // instalación fresca puede no existir todavía.
        $fullTextTargets = [
            'items_description_internal_id_fulltext' => ['description', 'internal_id'],
            'items_model_fulltext'                   => ['model'],
            'items_second_name_fulltext'             => ['second_name'],
            'items_name_fulltext'                    => ['name'],
        ];

        foreach ($fullTextTargets as $indexName => $columns) {
            if ($indexExists($indexName)) {
                continue;
            }
            foreach ($columns as $column) {
                if (!Schema::hasColumn('items', $column)) {
                    continue 2;
                }
            }
            Schema::table('items', function (Blueprint $table) use ($columns) {
                $table->fullText($columns);
            });
        }

        // Índices B-Tree para los filtros del listado y del POS.
        $btreeTargets = [
            'items_active_index'       => 'active',
            'items_category_id_index'  => 'category_id',
            'items_unit_type_id_index' => 'unit_type_id',
            'items_brand_id_index'     => 'brand_id',
            'items_barcode_index'      => 'barcode',
        ];

        foreach ($btreeTargets as $indexName => $column) {
            if ($indexExists($indexName) || !Schema::hasColumn('items', $column)) {
                continue;
            }
            Schema::table('items', function (Blueprint $table) use ($column) {
                $table->index($column);
            });
        }
    }

    public function down()
    {
        if (!Schema::hasTable('items')) {
            return;
        }

        $indexExists = $this->indexChecker();

        $fullTextTargets = [
            'items_description_internal_id_fulltext' => ['description', 'internal_id'],
            'items_model_fulltext'                   => ['model'],
            'items_second_name_fulltext'             => ['second_name'],
            'items_name_fulltext'                    => ['name'],
        ];

        foreach ($fullTextTargets as $indexName => $columns) {
            if (!$indexExists($indexName)) {
                continue;
            }
            Schema::table('items', function (Blueprint $table) use ($columns) {
                $table->dropFullText($columns);
            });
        }

        foreach (['active', 'category_id', 'unit_type_id', 'brand_id', 'barcode'] as $column) {
            if (!$indexExists("items_{$column}_index")) {
                continue;
            }
            Schema::table('items', function (Blueprint $table) use ($column) {
                $table->dropIndex([$column]);
            });
        }
    }

    /**
     * Closure que responde si un índice ya existe en `items`. Se consulta
     * information_schema en vez de doctrine/dbal, que puede no estar instalado.
     */
    private function indexChecker(): \Closure
    {
        $connection = Schema::getConnection();
        $database   = $connection->getDatabaseName();

        return function ($indexName) use ($connection, $database) {
            return count($connection->select(
                "SELECT 1 FROM information_schema.STATISTICS
                 WHERE table_schema = ? AND table_name = 'items'
                   AND index_name = ?",
                [$database, $indexName]
            )) > 0;
        };
    }
}
