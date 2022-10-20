<?php

namespace App\Http\Middleware;

use Closure;
use App\Exceptions\ApiErrorException;
use Illuminate\Support\Facades\Redis;

class UserAuth
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
        // return $next($request);
        $token = $request->header('token');
        if (!$token || $token == '') {
            // 如果 token 不存在或者为''
            throw new ApiErrorException('USER_LOGIN_STATE_FAIL');
        }
        $user = Redis::get('user:'.$token);
        if (!$user) {
            // 如果 redis 里不存在这个token
            throw new ApiErrorException('USER_LOGIN_STATE_FAIL'); 
        }
        $userInfo = unserialize($user); // 反序列化用户信息
        $request->attributes->add(['userInfo' => $userInfo]);
        Redis::setex('user:'.$token, 3600 * 24 * 180, serialize($userInfo));
        return $next($request);
    }
}
