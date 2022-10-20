<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Exceptions\ApiErrorException;
use App\Http\Controllers\Controller;

class GroupController extends Controller
{
    public function create(Request $request)
    {
        $rules = [
           'name' => 'required|max:30'
        ];  
        validateParams($request->only('name'), $rules); 
        $userInfo = $request->get('userInfo');
        DB::table('post_group')->insert([
            'name' => e($request->input('name')),
            'user_id' => $userInfo['id'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return successJson();
    }
    public function list(Request $request)
    {
        $userInfo = $request->get('userInfo');
        $list = DB::table('post_group')->where('user_id', $userInfo['id'])->get();
        return successJson($list); 
    }
    public function update(Request $request)
    {
        $userInfo = $request->get('userInfo');
        $rules = [
            'name' => 'required|max:30',
            'group_id' => 'required|numeric',
         ];  
         validateParams($request->only('name', 'group_id'), $rules);
         $groupInfo = DB::table('post_group')->where('id', $request->input('group_id'))->first();
         if (!$groupInfo) {
            throw new ApiErrorException('GROUP_NOT_FOUND');
         }
         if ($groupInfo->user_id != $userInfo['id']) {
            throw new ApiErrorException('NO_PERMISSION_EDIT_OR_DELETE');
         }
         DB::table('post_group')->where('id', $groupInfo->id)->update([
            'name' => e($request->input('name')),
         ]);
         return successJson();
    }
    public function delete(Request $request,$id)
    {
        $userInfo = $request->get('userInfo'); 
        $groupInfo = DB::table('post_group')->where('id', $id)->first(); 
        if (!$groupInfo || $groupInfo->user_id != $userInfo['id']) {
            throw new ApiErrorException('NO_PERMISSION_EDIT_OR_DELETE');
        }
        DB::table('post_group')->where('id', $groupInfo->id)->delete();
        DB::table('post_group_data')->where('group_id', $groupInfo->id)->delete();
        return successJson();
    }
    public function addSockpuppetToGroup(Request $request)
    {
        $rules = [
            'sockpuppet_list' => 'required',
            'group_id' => 'required|numeric',
        ];
        validateParams($request->only('sockpuppet_list', 'group_id'), $rules);
        $sockpuppetList = $request->input('sockpuppet_list');
        $userInfo = $request->get('userInfo');
        if (!is_array($sockpuppetList)) {
            // 因为提示效果都是一样的，所以就用这个键了(……)
            throw new ApiErrorException('EMOJI_LIST_NOT_A_LIST');
        }
        $groupInfo = DB::table('post_group')->where('id', $request->input('group_id'))->first();
        if (!$groupInfo || $groupInfo->user_id != $userInfo['id']) {
            throw new ApiErrorException('GROUP_NOT_FOUND');
        }
        $sockpuppetList = array_unique($sockpuppetList); // 去重
        $sockpuppet = DB::table('sockpuppet')->whereIn('id', $sockpuppetList)->pluck('id');
        $data = [];
        foreach ($sockpuppet as $sockpuppetItem) {
            $itemData = [
                'group_id' => $groupInfo->id,
                'user_id' => $userInfo['id'],
                'sockpuppet_id' => $sockpuppetItem,
            ];
            array_push($data, $itemData);
        }
        DB::table('post_group_data')->insert($data);
        return successJson();
    }
    public function deleteSockpuppetToGroup(Request $request)
    {
        $rules = [
            'id_list' => 'required',
        ];
        $idList = $request->input('id_list');
        validateParams($request->only('id_list'), $rules);
        if (!is_array($idList)) {
            // 因为提示效果都是一样的，所以就用这个键了(……)
            throw new ApiErrorException('EMOJI_LIST_NOT_A_LIST');
        }
        $userInfo = $request->get('userInfo');
        $data = DB::table('post_group_data')->whereIn('id' , $idList)->get();
        $deleteData = [];
        foreach ($data as $item) {
           if ($item->user_id == $userInfo['id']) {
            array_push($deleteData, $item->id);
           }
        }
        DB::table('post_group_data')->whereIn('id', $deleteData)->delete();
        return successJson();
    }
    public function groupDetail(Request $request, $id)
    {
        $userInfo = $request->get('userInfo');
        $data = DB::table('post_group_data')->where('post_group_data.group_id', $id)
                    ->leftJoin('sockpuppet', 'post_group_data.sockpuppet_id', '=', 'sockpuppet.id')
                    ->select('post_group_data.id', 'sockpuppet.id as sockpuppet_id', 'sockpuppet.name', 'sockpuppet.avatar_url', 'sockpuppet.sign')
                    ->get();
        return successJson($data);
    }
}
