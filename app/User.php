<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // Things that define the user
    private $apiauth_token, $password;


    public function setApiAuthToken($token)
    {
        $this->apiauth_token = $token;
    }

    public function setUsername($username)
    {
        $this->username = $username;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function invitions(){
        return $this->hasMany('\App\Models\Invitation', 'to', 'id');
    }

    public function invitionsLendersRealters()
    {
        return $this->hasMany('\App\Models\Invitation', 'from', 'id');
    }

    public  function userRoles(){
        return $this->hasMany('\App\Models\UserRoles', 'user_id', 'id');
    }

    public  function getUserByRoles(){
        return $this->belongsToMany('\App\Models\UserRoles');
    }

    public function chats(){
        return $this->belongsToMany("App\Chat","chat_user");
    }

    public function messages()
    {
        return $this->hasMany('\App\Conversation', 'user_id', 'id');
    }

    public function chatrooms()
    {
        return $this->hasMany('App\ChatUser', 'user_id', 'id');
    }
}
