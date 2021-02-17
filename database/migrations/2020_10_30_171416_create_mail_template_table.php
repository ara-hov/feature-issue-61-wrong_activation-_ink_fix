<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMailTemplateTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mail_template', function (Blueprint $table) {
            $table->id();
            $table->string('identifier');
            $table->string('subject');
            $table->string('hint');
            $table->longText('body');
            $table->string('wildcards');
            $table->softDeletes('deleted_at', 0);
            $table->string('email_from')->default('noreply@supportroom.com');
            $table->timestamps();
        });

        DB::table('mail_template')->insert(
            array(
                'id'=> 1,
                'identifier'=>'email_verification', 
                'subject'=>'Email Verification',
                 'hint'=>'user email verification', 
                 'body'=>'<p>Hey [USER_NAME], </p><br><p> you’re almost ready to start enjoying [APP_NAME].\r\nSimply click the below link to verify your email address. </p> <br><a href=\"[CONFIRMATION_LINK]\">[CONFIRMATION_LINK]</a><br><p> If that doesn’t work, copy and paste the above link in your browser.</p><br>Thank You!<br><br><p>The [APP_NAME] Team.</p>',
                  'wildcards'=>'[USER_NAME],[APP_NAME],[CONFIRMATION_LINK]'
                  
            )
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mail_template');
    }
}
