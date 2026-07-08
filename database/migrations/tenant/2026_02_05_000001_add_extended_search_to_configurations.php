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
        Schema::table('configurations', function(Blueprint $table) {
            // Búsqueda Avanzada: maestro apagado por defecto → sin cambio de comportamiento.
            $table->boolean('enable_extended_search')->default(false);
            $table->boolean('search_by_second_name')->default(false);
            $table->boolean('search_by_model')->default(false);
            $table->boolean('search_by_extra_name')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('configurations', function(Blueprint $table) {
            $table->dropColumn([
                'enable_extended_search',
                'search_by_second_name',
                'search_by_model',
                'search_by_extra_name',
            ]);
        });
    }
}
