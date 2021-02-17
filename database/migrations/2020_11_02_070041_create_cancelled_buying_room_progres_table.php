<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCancelledBuyingRoomProgresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cancelled_buying_room_progress', function (Blueprint $table) {
            $table->id();
            $table->integer('prop_id');
            $table->longtext('value');
            $table->string('reason')->nullable();
            $table->integer('cancelled_by');
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
        Schema::dropIfExists('cancelled_buying_room_progres');
    }
}
