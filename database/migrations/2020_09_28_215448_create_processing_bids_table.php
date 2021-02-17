<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProcessingBidsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('processing_bids', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('offer_price');
            $table->string('loan_type');
            $table->bigInteger('down_payment');
            $table->string('credit_score')->nullable(true);
            $table->bigInteger('bank_balance')->nullable(true);
            $table->bigInteger('loan_amount');
            $table->tinyInteger('is_pre_approved')->default('0');
            $table->tinyInteger('status')->default('0');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('processing_bids');
    }
}
