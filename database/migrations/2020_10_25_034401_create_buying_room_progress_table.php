<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBuyingRoomProgressTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('buying_room_progress', function (Blueprint $table) {
            $table->id();
            $table->string('module', 255);
            $table->string('role', 20);
            $table->string('pre_requsite', 255);
            $table->string('file', 255);
            $table->dateTime('date', 0);
            $table->integer('prop_id');
            $table->tinyInteger('status')->default(0);
            $table->timestamps();
            $table->index(['module', 'prop_id' ,'pre_requsite', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('buying_room_progress');
    }
}
