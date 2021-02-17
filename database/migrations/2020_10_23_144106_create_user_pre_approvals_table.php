<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserPreApprovalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_pre_approvals', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->integer('lender_id');
            $table->bigInteger('offer_price');
            $table->string('loan_type');
            $table->bigInteger('down_payment');
            $table->string('credit_score')->nullable(true);
            $table->bigInteger('bank_balance')->nullable(true);
            $table->bigInteger('total_assets')->nullable(true);
            $table->bigInteger('loan_amount');
            $table->tinyInteger('is_pre_approved')->default('0');
            $table->string('lender')->nullable(true);
            $table->string('bank_name')->nullable(true);
            $table->string('lender_email', 250)->nullable(true);
            $table->string('lender_phone', 20)->nullable(true);
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
        Schema::dropIfExists('user_pre_approvals');
    }
}
