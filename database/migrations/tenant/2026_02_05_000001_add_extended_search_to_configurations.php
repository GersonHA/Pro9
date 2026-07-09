<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddExtendedSearchToConfigurations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        // Idempotente: si la columna ya existe (tenant donde corrió de facto), se salta.
        // Así es segura tanto en local como en el cutover a prod (donde no existirán).
        $columns = [
            'enable_extended_search', // maestro apagado por defecto → sin cambio de comportamiento
            'search_by_second_name',
            'search_by_model',
            'search_by_extra_name',
        ];
        Schema::table('configurations', function(Blueprint $table) use ($columns) {
            foreach ($columns as $column) {
                if (!Schema::hasColumn('configurations', $column)) {
                    $table->boolean($column)->default(false);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        $columns = [
            'enable_extended_search',
            'search_by_second_name',
            'search_by_model',
            'search_by_extra_name',
        ];
        Schema::table('configurations', function(Blueprint $table) use ($columns) {
            foreach ($columns as $column) {
                if (Schema::hasColumn('configurations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
