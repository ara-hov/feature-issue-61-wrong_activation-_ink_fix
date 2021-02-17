<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    protected $fillable = ['property_id'];
    public function users(){
        return $this->belongsToMany("App\User","chat_user");
    }
    
    public function chatroom(){
        return $this->belongsTo("App\ChatUser","chat_user");
    }

    public function conversations(){
        return $this->hasMany("App\Conversation");
    }

    public function property(){
        return $this->belongsTo("App\Models\Entity", 'property_id');
    }
}
