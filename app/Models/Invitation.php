<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invitation extends Model
{
    protected $table = 'invitations';

    protected $filliable = ["from", "to", "to_email", "prop_id", "status"];

    public function property()
    {
        return $this->hasOne('\App\Models\Entity', 'id', "prop_id");
    }

    public function client()
    {
        return $this->hasOne('\App\User', 'id', "from");
    }

    public function userPreApprovalsSubmited()
    {
        return $this->hasOne('\App\Models\UserPreApprovals', 'lender_id', 'to');
    }

    public function getUserLendersRealters()
    {
        return $this->hasOne('\App\User', 'id', "to");
    }

    public function userBusinessProfiles()
    {
        return $this->hasOne("App\Models\BusinessProfile", "user_id", "to");
    }
}
