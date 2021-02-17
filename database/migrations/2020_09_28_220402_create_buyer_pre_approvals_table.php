<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBuyerPreApprovalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('buyer_pre_approvals', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->integer('bid_id');
            $table->string('bank_name');
            $table->string('banker_email');
            $table->string('banker_phone_number');
            $table->string('status');
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
        Schema::dropIfExists('buyer_pre_approvals');
    }
}
