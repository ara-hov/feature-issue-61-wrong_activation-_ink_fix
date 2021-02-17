<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EditPreRequsiteInBuyingRoomProgress extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('buying_room_progress', function (Blueprint $table) {
            $table->string('pre_requsite', 255)->nullable()->change();
            $table->string('file', 255)->nullable()->change();
            $table->dateTime('date', 0)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('buying_room_progress', function (Blueprint $table) {
            $table->string('pre_requsite', 255)->change();
            $table->string('file', 255)->change();
            $table->dateTime('date', 0)->change();
        });
    }
}
