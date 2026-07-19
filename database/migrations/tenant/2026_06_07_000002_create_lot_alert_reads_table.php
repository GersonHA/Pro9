<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLotAlertReadsTable extends Migration
{
    public function up()
    {
        Schema::create('lot_alert_reads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('lot_alert_id');
            $table->timestamp('seen_at');
            $table->unique(['user_id', 'lot_alert_id']);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('lot_alert_reads');
    }
}
