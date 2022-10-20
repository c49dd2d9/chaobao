<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use App\Exceptions\ApiErrorException;
use App\Http\Controllers\Controller;

class TagController extends Controller
{
    public function list(Request $request)
    {
        $name = e($request->input('keyword'));
        $data = DB::table('tags')->where('name', 'like', '%'.$name.'%')->select('id', 'name')->get();
        return successJson($data);
    }
    public function tagPost($id)
    {
        $data = DB::table('tag_data')->where('tag_id', $id)->pluck('post_id');
        $post = DB::table('posts')->whereIn('posts.id', $data)->leftJoin('sockpuppet', 'posts.sockpuppet_id', '=', 'sockpuppet.id')->select('posts.id', 'posts.content', 'posts.sockpuppet_id', 'posts.platform', 'posts.image_url', 'posts.is_top', 'posts.publishtime', 'posts.tag_json', 'posts.is_delete', 'sockpuppet.name', 'sockpuppet.avatar_url')->get();
        foreach ($post as $item) {
           if ($item->is_delete == 1) {
            $item->content = '***这条动态被删除了***';
            $item->image_url = $item->image_url != '' ? explode(',', $item->image_url) : null; 
            $item->tag_json = json_decode($item->tag_json);
            unset($item->is_delete);
           }
        }
        return successJson($post);
    }
}
