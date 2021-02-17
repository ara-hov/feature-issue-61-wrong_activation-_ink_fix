<?php

namespace App\Http\Middleware;

use App\Services\Entities\EntitiesService;
use Closure;
use Illuminate\Contracts\Routing\ResponseFactory as Response;
use Illuminate\Support\Facades\Auth;
use App\User;
class VerifyEmail
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
        if (Auth::attempt(['email' => $request->username, 'password' =>  $request->password])) {
            $user = Auth::user();
            if ($user->email_verified_at) {
                return $next($request);
            }else{
                $data['message'] = "Your email does not verified!";
                return response()->json(['status' => 'failure', 'message' => 'Your email does not verified!', 'data' => []], 200);
            }
        }else{
            return response()->json(['status' => 'failure', 'message' => 'User does not exist'], 200);
        }
    }
}
