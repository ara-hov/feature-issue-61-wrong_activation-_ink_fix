<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcessingBids extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    protected $table = 'processing_bids';

    public function user()
    {
        return $this->hasOne("App\User", "id", "user_id");
    }
}
