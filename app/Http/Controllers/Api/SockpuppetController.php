<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Sockpuppet;
use App\Exceptions\ApiErrorException;
use App\Http\Controllers\Controller;

class SockpuppetController extends Controller
{
    public function create(Request $request)
    {
        $rules = [
            'name' => 'required|max:20|unique:sockpuppet',
            'type' => 'required'
        ];  
        validateParams($request->only('name','type'), $rules); 
        $sockpuppet = new Sockpuppet;
        $sockpuppet->name = e($request->input('name'));
        $sockpuppet->avatar_url = nullSecurity($request->input('avatar_url'));
        $sockpuppet->platform = nullSecurity($request->input('platform'));
        $sockpuppet->background_url = nullSecurity($request->input('background_url'));
        $sockpuppet->sign = nullSecurity($request->input('sign'));
        $sockpuppet->save();
        return successJson();
    }
    public function delete($id)
    {
        $sockpuppet = Sockpuppet::find($id);
        if (!$sockpuppet) {
            throw new ApiErrorException('SOCKPUPPET_NOT_FOUND');
        }
        DB::table('posts')->where('sockpuppet_id', $sockpuppet->id)->delete();
        DB::table('comments')->where('sockpuppet_id', $sockpuppet->id)->delete();
        DB::table('post_group_data')->where('sockpuppet_id', $sockpuppet->id)->delete();
        Sockpuppet::destroy($sockpuppet->id);
        return successJson();
    }
    public function list()
    {
        $sockpuppet = Sockpuppet::all();
        return successJson($sockpuppet);
    }
    public function getSockpuppetInfo($id)
    {
        $sockpuppet = SockPuppet::find($id);
        if (!$sockpuppet) {
            throw new ApiErrorException('SOCKPUPPET_NOT_FOUND');
        }
        return successJson($sockpuppet);
    }
    public function update(Request $request)
    {
        $rules = [
            'id' => 'required',
        ];  
        validateParams($request->only('id'), $rules); 
        $sockpuppet = Sockpuppet::find($request->input('id'));
        if (!$sockpuppet) {
            throw new ApiErrorException('SOCKPUPPET_NOT_FOUND');
        }
        $sockpuppet->name = nullSecurity($request->input('name'));
        $sockpuppet->avatar_url = nullSecurity($request->input('avatar_url'));
        $sockpuppet->platform = nullSecurity($request->input('platform'));
        $sockpuppet->background_url = nullSecurity($request->input('background_url'));
        $sockpuppet->sign = nullSecurity($request->input('sign'));
        $sockpuppet->save();
        return successJson();
    }
}
