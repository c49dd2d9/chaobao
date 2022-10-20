<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use  Illuminate\Support\Facades\DB;
use App\Exceptions\ApiErrorException;
use App\Http\Controllers\Controller;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $keyword = $request->input('keyword');
        if (strlen($keyword) < 1) {
            throw new ApiErrorException('SERVER_ERROR');
        }
        $post = DB::table('posts')->where('content', 'like', '%'.$keyword.'%')->where('is_delete', 0)->select('id', 'content')->get();
        $comment = DB::table('comments')->where('content', 'like', '%'.$keyword.'%')->select('post_id as id', 'content')->get();
        $data = [
            'post' => $post,
            'comment' => $comment,
        ];
        return successJson($data);
    }
}
