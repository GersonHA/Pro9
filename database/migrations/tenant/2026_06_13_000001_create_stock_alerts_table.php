<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStockAlertsTable extends Migration
{
    public function up()
    {
        Schema::create('stock_alerts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('establishment_id');
            $table->string('severity', 10)->default('low');
            $table->decimal('stock_min', 12, 2);
            $table->decimal('stock', 12, 2);
            $table->text('message');
            $table->string('type', 30)->default('low_stock');
            $table->boolean('seen')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->json('stock_data')->nullable();
            $table->timestamps();

            $table->index(['item_id', 'establishment_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('stock_alerts');
    }
}
