<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCreditAlertsTable extends Migration
{
    public function up()
    {
        Schema::create('credit_alerts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sale_note_id')->nullable();
            $table->unsignedBigInteger('document_id')->nullable();
            $table->unsignedBigInteger('establishment_id');
            $table->unsignedBigInteger('seller_id')->nullable();
            $table->string('customer_name');
            $table->string('doc_type_label', 5);
            $table->string('doc_series', 20);
            $table->string('doc_number', 20);
            $table->decimal('amount_total', 12, 2);
            $table->decimal('amount_pending', 12, 2);
            $table->date('due_date');
            $table->tinyInteger('threshold_days');
            $table->string('type', 30)->default('overdue_credit');
            $table->boolean('seen')->default(false);
            $table->json('fees_data')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('credit_alerts');
    }
}
