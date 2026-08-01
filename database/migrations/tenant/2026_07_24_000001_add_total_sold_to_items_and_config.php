<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTotalSoldToItemsAndConfig extends Migration
{
    /**
     * Port de pro8 `2026_04_16_120825_add_total_sold_to_items_and_config`.
     *
     * Agrega:
     *  - `items.total_sold` (decimal 12,4, default 0) + índice → contador de unidades
     *    vendidas por producto. Decimal para soportar venta por peso (ej. 1.5kg).
     *  - `configurations.pos_search_order` (string, default 'default') → preferencia del
     *    selector "Orden de Búsqueda en POS" (default | alphabetical | sales | price_desc).
     *
     * Idempotente: las 5 BD de Pro9 YA tienen ambas columnas (llegaron dentro de los dumps
     * de producción de pro8, no de una migración). Sin guard, revienta con
     * `errno 1060 Duplicate column` / `errno 1061 Duplicate key`. No-op en tenants actuales,
     * efectiva en tenants nuevos. Mismo patrón que
     * 2026_07_23_000001_add_fulltext_indexes_to_items.
     *
     * Nota: en las BD actuales `total_sold` viene congelado a la fecha del dump (Pro9 nunca lo
     * incrementó). Se repuebla con el comando `items:sync-sales` tras migrar.
     */
    public function up()
    {
        if (Schema::hasTable('items')) {
            $indexExists = $this->indexChecker('items');

            Schema::table('items', function (Blueprint $blueprint) use ($indexExists) {
                if (!Schema::hasColumn('items', 'total_sold')) {
                    $blueprint->decimal('total_sold', 12, 4)->default(0)->after('stock');
                }
            });

            if (!$indexExists('items_total_sold_index') && Schema::hasColumn('items', 'total_sold')) {
                Schema::table('items', function (Blueprint $blueprint) {
                    $blueprint->index('total_sold');
                });
            }
        }

        if (Schema::hasTable('configurations') && !Schema::hasColumn('configurations', 'pos_search_order')) {
            Schema::table('configurations', function (Blueprint $blueprint) {
                $blueprint->string('pos_search_order')->default('default')->after('enable_extended_search');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('items')) {
            $indexExists = $this->indexChecker('items');

            if ($indexExists('items_total_sold_index')) {
                Schema::table('items', function (Blueprint $blueprint) {
                    $blueprint->dropIndex(['total_sold']);
                });
            }
            if (Schema::hasColumn('items', 'total_sold')) {
                Schema::table('items', function (Blueprint $blueprint) {
                    $blueprint->dropColumn('total_sold');
                });
            }
        }

        if (Schema::hasTable('configurations') && Schema::hasColumn('configurations', 'pos_search_order')) {
            Schema::table('configurations', function (Blueprint $blueprint) {
                $blueprint->dropColumn('pos_search_order');
            });
        }
    }

    /**
     * Closure que responde si un índice ya existe en la tabla dada. Se consulta
     * information_schema en vez de doctrine/dbal, que puede no estar instalado.
     */
    private function indexChecker(string $table): \Closure
    {
        $connection = Schema::getConnection();
        $database   = $connection->getDatabaseName();

        return function ($indexName) use ($connection, $database, $table) {
            return count($connection->select(
                "SELECT 1 FROM information_schema.STATISTICS
                 WHERE table_schema = ? AND table_name = ?
                   AND index_name = ?",
                [$database, $table, $indexName]
            )) > 0;
        };
    }
}
