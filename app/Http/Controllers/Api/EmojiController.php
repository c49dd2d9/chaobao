<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Exceptions\ApiErrorException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use App\Http\Controllers\Controller;

class EmojiController extends Controller
{
    public function create(Request $request)
    {
        if (!is_array($request->input('emoji_list'))) {
            throw new ApiErrorException('EMOJI_LIST_NOT_A_LIST');
        }
        if (!$request->input('group_id') || $request->input('group_id') < 0) {
            throw new ApiErrorException('EMOJI_GROUP_IS_REQUIRED');
        } 
        $list = array_unique($request->input('emoji_list'));
        $data = [];
        foreach ($list as $item) {
            $code = Str::random(rand(4, 10));
            $newItem = [
                'code' => $code,
                'image_url' => $item,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            array_push($data, $newItem);
        }
        DB::table('emoji')->insert($data);
        Redis::del('emoji:list');
        return successJson();
    }
    public function list()
    {
        $cacheList = Redis::get('emoji:list');
        if (!$cacheList) {
            $data = DB::table('emoji')->select('id', 'code', 'image_url', 'group_id')->get();
            Redis::set('emoji:list', serialize($data));
            return successJson($data);
        } else {
            return successJson(unserialize($cacheList));
        }
    }
    public function delete(Request $request)
    {
        $id_list = $request->input('id_list');
        if (!is_array($id_list)) {
            throw new ApiErrorException('EMOJI_LIST_NOT_A_LIST');
        }
        DB::table('emoji')->whereIn('id', $id_list)->delete();
        Redis::del('emoji:list');
        return successJson();
    }
}
