<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BidAssociations extends Model
{
    protected $guarded = [];

    public function bids()
    {
        return $this->hasMany("App\Models\ProcessingBids", "id", "bid_id");
    }

    public function bidsNegotiations()
    {
        return $this->hasMany("App\Models\BidNegotiations", "bid_id", "bid_id");
    }


    public function propertyBids()
    {
        return $this->hasMany("App\Models\ProcessingBids", "prop_id", "id");
    }

    public function userProperty()
    {
        return $this->hasMany("App\Models\Entity", "id", "prop_id");
    }

    public function user()
    {
        return $this->hasOne("App\User", "id", "user_id");
    }
}
