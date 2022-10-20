<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Comment;
use App\Models\Sockpuppet;
use Illuminate\Support\Facades\DB;
use App\Exceptions\ApiErrorException;
use App\Models\Post;
use App\Http\Controllers\Controller;

class CommentController extends Controller
{
    /**
     * 添加
     * 删除
     * 编辑
     * 获取list
     * 获取单条评论
     */
    public function create(Request $request)
    {
        $rules = [
            'content' => 'required|max:1000',
            'sockpuppet_id' => 'required|numeric',
            'post_id' => 'required|numeric',
        ];  
        validateParams($request->only('content', 'sockpuppet_id', 'post_id'), $rules); 
        $sockPuppet = SockPuppet::find($request->input('sockpuppet_id'));
        if (!$sockPuppet) {
            throw new ApiErrorException('SOCKPUPPET_NOT_FOUND');
        }
        $post = Post::find($request->input('post_id'));
        if (!$post || $post->is_delete == 1) {
            throw new ApiErrorException('POST_NOT_FOUND');
        }
        $comment = new Comment;
        $comment->content = $request->input('content');
        $comment->sockpuppet_id = $sockPuppet->id;
        $comment->post_id = $post->id;
        $comment->user_id = $post->user_id;
        $comment->image_url = nullSecurity($request->input('image_url'));
        $comment->publishtime = $request->input('publishtime') ?? now();
        $comment->author_like = 0;
        $comment->platform = $request->input('platform') ? $request->input('platform') : $sockPuppet->platform;
        $comment->save();
        return successJson();
    }
    public function delete(Request $request,$id)
    {
        $comment = Comment::find($id);
        if (!$comment) {
            throw new ApiErrorException('COMMENT_DELETE_FAIL');
        }
        $userInfo = $request->get('userInfo');
        if ($comment->user_id != $userInfo['id']) {
            throw new ApiErrorException('COMMENT_DELETE_FAIL');
        }
        Comment::where('id', $comment->id)->delete();
        return successJson();
    }
    public function getCommentInfo($id)
    {
        $comment = Comment::find($id);
        if (!$comment) {
            throw new ApiErrorException('COMMENT_NOT_FOUND');
        }
        unset($comment->user_id);
        $comment->image_url = $comment->image_url != '' ? explode(',', $comment->image_url) : null; 
        return successJson($comment);
    }
    public function updateCommentInfo(Request $request)
    {
        $rules = [
            'content' => 'required|max:1000',
            'comment_id' => 'required|numeric',
            'publishtime' => 'required',
            'platform' => 'required'
        ];  
        validateParams($request->only('content', 'comment_id', 'publishtime', 'platform'), $rules); 
        $comment = Comment::find($request->input('comment_id'));
        if (!$comment) {
            throw new ApiErrorException('COMMENT_NOT_FOUND');
        }
        $userInfo = $request->get('userInfo');
        if ($comment->user_id !=  $userInfo['id']) {
            throw new ApiErrorException('NO_PERMISSION_EDIT_OR_DELETE');
        }
        $comment->content = $request->input('content');
        $comment->publishtime = $request->input('publishtime');
        $comment->platform = $request->input('platform');
        $comment->save();
        return successJson();
    }
    public function newComment($id = 0)
    {
        if ($id != 0) {
            $comment = DB::table('comments')->where('comments.sockpuppet_id', $id)->leftJoin('sockpuppet', 'comments.sockpuppet_id', '=', 'sockpuppet.id')
                            ->select('comments.id', 'comments.post_id' ,'comments.content', 'comments.image_url', 'comments.publishtime', 'comments.author_like', 'comments.platform', 'sockpuppet.name', 'sockpuppet.id as sockpuppet_id', 'sockpuppet.avatar_url')
                            ->orderBy('id', 'desc')
                            ->limit(30)
                            ->get();
        } else {
            $comment = DB::table('comments')->leftJoin('sockpuppet', 'comments.sockpuppet_id', '=', 'sockpuppet.id')
                            ->select('comments.id', 'comments.post_id' , 'comments.content', 'comments.image_url', 'comments.publishtime', 'comments.author_like', 'comments.platform', 'sockpuppet.name', 'sockpuppet.id as sockpuppet_id', 'sockpuppet.avatar_url')
                            ->orderBy('id', 'desc')
                            ->limit(30)
                            ->get();
        }
        foreach ($comment as $item) {
            $item->image_url = $item->image_url != '' ? explode(',' ,$item->image_url) : null;
        }
        return successJson($comment);
    }
}
