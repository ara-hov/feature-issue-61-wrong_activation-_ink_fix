<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropertyAssociations extends Model
{
    protected $guarded = [];

    public function userProperty()
    {
        return $this->hasMany("App\Models\Entity", "id", "prop_id");
    }
}
