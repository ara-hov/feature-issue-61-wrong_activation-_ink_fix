<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BidNegotiations extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    public function bid()
    {
        return $this->hasMany("App\Models\ProcessingBids", "id", "bid_id");
    }

    public function bidAssociation()
    {
        return $this->hasMany("App\Models\BidAssociations", "bid_id", "bid_id");
    }
}
