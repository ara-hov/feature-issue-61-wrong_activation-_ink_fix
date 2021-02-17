<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessProfile extends Model
{
   protected $table = 'business_profiles';
	  public function businessProfile(){
    	return $this->belongsTo("App\User","id");
    }

}
