<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Entity extends Model
{
    protected $table = 'properties';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    public function userdetail()
    {
        return $this->belongsTo("App\User", "id");
    }

    public function propertyBids()
    {
        return $this->hasMany("App\Models\ProcessingBids", "prop_id", "id");
    }

    public function propertyBidsAssociation()
    {
        return $this->hasMany("App\Models\BidAssociations", "prop_id", "id");
    }

    public function propertyAssociation()
    {
        return $this->hasMany("App\Models\PropertyAssociations", "prop_id", "id");
    }

    public function buyingRoomProgress(){
    	return $this->hasMany("App\Models\BuyingRoomProgress","prop_id", "id");
    }

    public function CancelledBuyingRoomProgres(){
    	return $this->hasMany("App\Models\CancelledBuyingRoomProgres","prop_id", "id");
    }
}
