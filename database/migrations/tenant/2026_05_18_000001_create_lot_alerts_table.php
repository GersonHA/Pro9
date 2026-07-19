<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLotAlertsTable extends Migration
{
    public function up()
    {
        Schema::create('lot_alerts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_id');
            $table->string('lot_code');
            $table->date('date_of_due');
            $table->unsignedBigInteger('establishment_id');
            $table->text('message');
            $table->boolean('seen')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('lot_alerts');
    }
}
