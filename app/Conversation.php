<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = ['message'];

    protected $with = ['user'];
    protected $appends = ['name','last_name'];

    public function chat(){
        return $this->belongsTo("App\Chat");
    }

    public function user(){
        return $this->belongsTo("App\User");
    }

    public function getNameAttribute() {
        return $this->user->name;
    }

    public function getLastNameAttribute() {
        return $this->user->last_name;
    }

    public function getEmailAttribute() {
        return $this->user->email;
    }
}
