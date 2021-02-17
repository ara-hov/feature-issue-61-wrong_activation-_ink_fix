<?php

namespace App\Repositories\DoAuth;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class DoAuthRepository extends Model
{
    public function login(\App\User $user)
    {
        try {
            $user::where('email', '=', $user->getUsername())->get();
        } catch (\Exception $e) {
            Log::error('Unable to call for ' . $e->getMessage());
            return null;
        }
        return true;
    }
}
