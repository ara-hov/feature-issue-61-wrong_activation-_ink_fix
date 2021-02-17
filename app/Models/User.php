<?php

namespace App\Model;

//use Illuminate\Notifications\Notifiable;
//use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Libraries\Helper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;

class User extends Model
{
    protected $table = "users";
    protected $fillable = ['name','role','email','password','verify_token','status','email_verified_at'];

}