<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Opt extends Model
{
    protected $table = 'opts';
    
    protected $filliable  = ["id", "user_id","auth_code"];
}
