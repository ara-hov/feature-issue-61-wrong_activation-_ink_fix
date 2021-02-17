<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ChatUser extends Model
{
    protected $table = 'chat_user';

    public function chat(){
        return $this->hasOne("App\Chat", 'id', 'chat_id');
    }

    public function users(){
        return $this->hasMany("App\User", 'id', 'user_id');
    }
}
