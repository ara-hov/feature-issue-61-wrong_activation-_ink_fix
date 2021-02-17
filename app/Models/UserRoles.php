<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserRoles extends Model
{
    protected $table = 'user_roles';

    protected $filliable = ["user_id", "role_id"];

    protected $guarded = [];

    public function UserByRole()
    {
        return $this->hasMany("App\User", "id", "user_id");
    }

}
