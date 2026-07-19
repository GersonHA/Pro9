<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToLotAlerts extends Migration
{
    public function up()
    {
        Schema::table('lot_alerts', function (Blueprint $table) {
            $table->string('type', 30)->default('fefo_violation')->after('seen');
            $table->tinyInteger('threshold_days')->nullable()->after('type');
            $table->json('lots_data')->nullable()->after('threshold_days');
        });
    }

    public function down()
    {
        Schema::table('lot_alerts', function (Blueprint $table) {
            $table->dropColumn(['type', 'threshold_days', 'lots_data']);
        });
    }
}
