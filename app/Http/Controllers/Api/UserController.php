<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Exceptions\ApiErrorException;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redis;

class UserController extends Controller
{
    public function register(Request $request)
    {
        $user = User::all();
        if (count($user) >= 2) {
            throw new ApiErrorException('THE_NUMBER_OF_USERS_EXCEEDS_THE_LIMIT');
        }
        $newCookie = e($request->input('cookie'));
        $newUser = new User;
        $newUser->cookie = $newCookie;
        $newUser->save();
        return successJson($newCookie);
    }
    public function login(Request $request)
    {
        $cookie = e($request->input('cookie'));
        if (strlen($cookie) < 2) {
            throw new ApiErrorException('COOKIE_LENGTH_ERROR');
        }
        $user = User::where('cookie', $cookie)->first();
        if (!$user) {
            throw new ApiErrorException('USER_NOT_FOUND');
        }
        $token = Str::random(64);
        Redis::setex('user:'.$token, 24 * 3600 * 180, serialize($user));
        Log::info($cookie.'在'.now().'登录完成，登录Token是:'.$token);
        return successJson($token);
    }
    
}
