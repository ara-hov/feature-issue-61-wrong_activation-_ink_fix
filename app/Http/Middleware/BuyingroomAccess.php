<?php

namespace App\Http\Middleware;

use App\Services\Entities\EntitiesService;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Routing\ResponseFactory as Response;
class BuyingroomAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $prop_id = $request->id;
        $user_id = Auth::user()->id;
        $bids = EntitiesService::AccessBuyingRoom($prop_id);
        
        if(empty($bids)) return response()->json(['success' => false, 'error' => 'Unauthorized access']);
        
        $bids = collect($bids);
        $access = $bids->filter(function($bid) use ($user_id) {
            return $bid['status'] == 1 && 
            ($bid['buyer_id'] == $user_id || $bid['buyer_realtor_id'] == $user_id || $bid['lender_id'] == $user_id || $bid['seller_id'] == $user_id || $bid['seller_realtor_id'] == $user_id);
        });
        $access = $access->first();
        
        if(!$access) return response()->json(['status' => false, 'error' => 'Unauthorized access']);
        
        return $next($request);
    }
}
