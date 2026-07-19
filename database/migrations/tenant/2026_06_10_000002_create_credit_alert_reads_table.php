<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCreditAlertReadsTable extends Migration
{
    public function up()
    {
        Schema::create('credit_alert_reads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('credit_alert_id');
            $table->timestamp('seen_at');
            $table->unique(['user_id', 'credit_alert_id']);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('credit_alert_reads');
    }
}
