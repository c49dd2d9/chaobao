<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\Sockpuppet;
use App\Models\User;
use App\Jobs\UpdatePostTag;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Exceptions\ApiErrorException;
use App\Http\Controllers\Controller;

class PostController extends Controller
{
    public function create(Request $request)
    {
        $rules = [
            'content' => 'required|max:1000',
            'sockpuppet_id' => 'required|numeric'
        ];  
        validateParams($request->only('content', 'sockpuppet_id'), $rules); 
        $sockpuppetInfo = Sockpuppet::find($request->input('sockpuppet_id'));
        if (!$sockpuppetInfo) {
            Log::error('[动态错误]无法找到马甲信息'.$request->input('sockpuppet_id'));
            throw new ApiErrorException('SOCKPUPPET_NOT_FOUND');
        }
        $userInfo = $request->get('userInfo');
        $post = new Post;
        $post->user_id = $userInfo['id'];
        $post->sockpuppet_id = $sockpuppetInfo->id;
        $post->platform = $request->input('platform') ?? $sockpuppetInfo->platform;
        $post->content = e($request->input('content'));
        $post->image_url = nullSecurity($request->input('image_url'));
        $post->likes = '';
        $post->is_top = 0;
        $post->publishtime = $request->input('publishtime') ?? now();
        $post->is_delete = 0;
        $post->tag_json = '';
        $post->save();
        $inputTag = $request->input('tag');
        $tagData = [];
        if ($inputTag || $inputTag != '' || $inputTag != null) {
            // 如果传入的tag不为空            
            $tagList = array_unique(explode(',' , $inputTag));
            foreach ($tagList as $tagItem) {
               $currentTag = DB::table('tags')->where('name', $tagItem)->first();
               if (!$currentTag) {
                $currentTag = DB::table('tags')->insertGetId([
                    'name' => $tagItem,
                ]);
               }
               $newTagData = [
                'post_id' => $post->id,
                'tag_id' => isset($currentTag->id) ? $currentTag->id : $currentTag,
               ];
               array_push($tagData, $newTagData);
            }
            DB::table('tag_data')->insert($tagData);
            UpdatePostTag::dispatch([
                'id' => $post->id,
            ]);
        }
        return successJson();
    }
    public function getSockpuppetPost(Request $request, $id)
    {
        $sockpuppetInfo = Cache::remember('majia:'.$id, 300, function()use($id) {
            return Sockpuppet::find($id);
        });
        if (!$sockpuppetInfo) {
            throw new ApiErrorException('SOCKPUPPET_NOT_FOUND');
        }
        $userInfo = $request->get('userInfo');
        $page = isset($_GET['page']) ? $_GET['page'] : 1;
        $post = Cache::remember('post:list:'.$id.'_'.$page, 300, function() use($id) {
            return Post::where('sockpuppet_id', $id)->where('is_delete', 0)->orderBy('is_top', 'desc')->select('id', 'content', 'sockpuppet_id', 'platform', 'image_url', 'is_top', 'publishtime', 'tag_json', 'is_delete')->paginate(50);
        });
        $follow = Cache::remember('follow_count_'.$sockpuppetInfo->id, 60 * 15, function() use($sockpuppetInfo) {
            return DB::table('follow')->where('sockpuppet_id', $sockpuppetInfo->id)->count();
        });
        $fans = Cache::remember('fans_count_'.$sockpuppetInfo->id, 60 * 15, function() use($sockpuppetInfo) {
            return DB::table('follow')->where('follow_id', $sockpuppetInfo->id)->count();
        });
        $currentPagePostId = [];
        foreach ($post as $item) {
            $item->image_url = $item->image_url != '' ? explode(',', $item->image_url) : null; 
            $item->tag_json = json_decode($item->tag_json);
            array_push($currentPagePostId, $item->id);
            unset($item->is_delete);
        }
        $likeList = Cache::remember('post:like:list:'.$id.'_'.$page, 300, function() use($currentPagePostId) {
            return DB::table('post_like')->whereIn('post_like.post_id', $currentPagePostId)->leftJoin('sockpuppet', 'post_like.sockpuppet_id', '=', 'sockpuppet.id')->select('sockpuppet.id as sockpuppet_id', 'sockpuppet.name', 'sockpuppet.avatar_url', 'post_like.post_id')->get();
        });
        $collectList = DB::table('keeps')->where('user_id', $userInfo['id'])->whereIn('post_id', $currentPagePostId)->pluck('post_id');
        $comment = DB::table('comments')->whereIn('comments.post_id', $currentPagePostId)->leftJoin('sockpuppet', 'comments.sockpuppet_id', '=', 'sockpuppet.id')->select('comments.id', 'comments.post_id', 'comments.content', 'comments.image_url', 'comments.publishtime', 'comments.author_like', 'comments.platform', 'sockpuppet.name', 'sockpuppet.id as sockpuppet_id', 'sockpuppet.avatar_url')->get();
        foreach ($comment as $commentItem) {
            $commentItem->image_url = $commentItem->image_url != '' ? explode(',', $commentItem->image_url) : null;
        }
        $data = [
            'sockpuppet_info' => $sockpuppetInfo,
            'post' => $post,
            'likeList' => $likeList,
            'collect' => $collectList,
            'comment' => $comment,
            'follow_count' => $follow,
            'fans_count' => $fans
        ];
        return successJson($data);
    }
    public function getPostInfo(Request $request, $id)
    {
        $post = Post::where('posts.id', $id)->leftJoin('sockpuppet', 'posts.sockpuppet_id', '=', 'sockpuppet.id')->select('posts.id', 'posts.sockpuppet_id', 'posts.tag_json', 'posts.content', 'posts.image_url', 'posts.publishtime', 'posts.is_delete', 'posts.platform', 'sockpuppet.name', 'sockpuppet.avatar_url', 'sockpuppet.sign')->first();
        if (!$post || $post->is_delete == 1) {
            throw new ApiErrorException('POST_NOT_FOUND');
        }
        $post->image_url = $post->image_url != '' ? explode(',' , $post->image_url) : null;
        $post->tag_json = json_decode($post->tag_json);
        $userInfo = $request->get('userInfo');
        $likeList = DB::table('post_like')->where('post_id', $post->id)->leftJoin('sockpuppet', 'post_like.sockpuppet_id', '=', 'sockpuppet.id')->select('sockpuppet.id as sockpuppet_id', 'sockpuppet.name', 'sockpuppet.avatar_url', 'post_like.post_id')->get();
        $collect = DB::table('keeps')->where('user_id', $userInfo['id'])->where('post_id', $post->id)->first();
        $comment = DB::table('comments')->where('comments.post_id', $post->id)->leftJoin('sockpuppet', 'comments.sockpuppet_id', '=', 'sockpuppet.id')->select('comments.id', 'comments.content', 'comments.image_url', 'comments.publishtime', 'comments.author_like', 'comments.platform', 'sockpuppet.name', 'sockpuppet.id as sockpuppet_id', 'sockpuppet.avatar_url')->get();
        foreach ($comment as $commentItem) {
            $commentItem->image_url = $commentItem->image_url != '' ? explode(',', $commentItem->image_url) : null;
        }
        $data = [
            'post' => $post,
            'likeList' => $likeList,
            'is_collect' => $collect ? true : false,
            'comment' => $comment
        ];
        return successJson($data);
    }
    public function update(Request $request)
    {
        $rules = [
            'post_id' => 'required',
        ];  
        validateParams($request->only('post_id'), $rules); 
        $post = Post::find($request->input('post_id'));
        if (!$post || $post->is_delete == 1) {
            throw new ApiErrorException('POST_NOT_FOUND');
        }
        $userInfo = $request->get('userInfo');
        if ($userInfo['id'] != $post->user_id) {
            throw new ApiErrorException('NO_PERMISSION_EDIT_OR_DELETE');
        }
        $post->content = e($request->input('content'));
        $post->image_url = nullSecurity($request->input('image_url'));
        $post->publishtime = nullSecurity($request->input('publishtime'));
        $post->platform = nullSecurity($request->input('platform'));
        $post->save(); 
        $inputTag = array_unique(explode(',',$request->input('tag')));
        $deleteTag = [];
        $tagList = DB::table('tags')->whereIn('id', DB::table('tag_data')->where('post_id', $post->id)->pluck('tag_id'))->get();
        $tagData = [];
        foreach ($tagList as $tag) {
            if (($index = array_search($tag->name, $inputTag)) !== false) {
                unset($inputTag[$index]);
            } else {
                array_push($deleteTag, $tag->id);
            }
        }
        foreach ($inputTag as $tagItem) {
            if (strlen($tagItem) > 0) {
                $tagInfo = DB::table('tags')->where('name', $tagItem)->first();
                if (!$tagInfo) {
                    $tagId = DB::table('tags')->insertGetId([
                        'name' => $tagItem
                    ]);
                } else {
                    $tagId = $tagInfo->id;
                }
                $newTagData = [
                'post_id' => $post->id,
                'tag_id' => $tagId
                ];
                array_push($tagData, $newTagData);
            }
        }
        DB::table('tag_data')->insert($tagData);  
        if ($deleteTag) {
            DB::table('tag_data')->whereIn('tag_id', $deleteTag)->where('post_id', $post->id)->delete();
        }
        UpdatePostTag::dispatch([
            'id' => $post->id,
        ]);
        return successJson();
    }
    public function delete(Request $request,$id)
    {
        $post = Post::find($id);
        if (!$post || $post->is_delete == 1) {
            throw new ApiErrorException('POST_NOT_FOUND');
        }
        $userInfo = $request->get('userInfo');
        if ($userInfo['id'] != $post->user_id) {
            throw new ApiErrorException('NO_PERMISSION_EDIT_OR_DELETE');
        }
       $post->is_delete = 1;
       $post->save();
       return successJson();
    }
    public function postTop($id)
    {
        $post = Post::find($id);
        if (!$post) {
            throw new ApiErrorException('POST_NOT_FOUND');
        }
        if (Redis::get('top:'.$post->sockpuppet_id)) {
            throw new ApiErrorException('TOP_FAIL');
        }
        $topPost = Post::where('id', $post->sockpuppet_id)->where('is_top', 1)->first();
        if ($topPost) {
            Post::where('id', $topPost->id)->update([
                'is_top' => 0,
            ]);
        }
        $post->is_top = 1;
        $post->save();
        Redis::setex('top:'.$post->sockpuppet_id, 300, 1);
        // 清空第一页的缓存
        Cache::forget('post:list:'.$post->sockpuppet_id.'_1');
        return successJson();
    }
    public function likePost(Request $request)
    {
        $userInfo = $request->get('userInfo');
        $rules = [
            'post_id' => 'required|numeric',
            'sockpuppet_id' => 'required|numeric',
            'current_page' => 'required|numeric'
        ];  
        validateParams($request->only('post_id', 'sockpuppet_id', 'current_page'), $rules); 
        $post = Post::find(e($request->input('post_id')));
        if (!$post || $post->is_delete == 1) {
            throw new ApiErrorException('POST_NOT_FOUND');
        }
        $sockpuppetInfo = SockPuppet::find($request->input('sockpuppet_id'));
        if (!$sockpuppetInfo) {
            throw new ApiErrorException('SOCKPUPPET_NOT_FOUND');
        }
        $like = DB::table('post_like')->where('post_id', $post->id)->where('sockpuppet_id', $sockpuppetInfo->id)->first();
        if ($like) {
            DB::table('post_like')->where('id', $like->id)->delete();
        } else {
            DB::table('post_like')->insert([
                'post_id' => $post->id,
                'sockpuppet_id' => $sockpuppetInfo->id
            ]);
        }
        Cache::forget('post:like:list:'.$post->sockpuppet_id.'_'.$request->input('current_page'));
        return successJson();
    }
    public function collectPost(Request $request, $id)
    {
        $post = Post::find($id);
        if (!$post) {
            throw new ApiErrorException('POST_NOT_FOUND');
        }
        $userInfo = $request->get('userInfo');
        DB::table('keeps')->insert([
            'user_id' => $userInfo['id'],
            'post_id' => $post->id
        ]);
        return successJson();
    }
    public function collectList(Request $request)
    {
        $userInfo = $request->get('userInfo');
        $collect = DB::table('keeps')
                        ->where('keeps.user_id', $userInfo['id'])
                        ->leftJoin('posts', 'keeps.post_id', '=', 'posts.id')
                        ->leftJoin('sockpuppet', 'posts.sockpuppet_id', '=', 'sockpuppet.id')
                        ->orderBy('keeps.id', 'desc')
                        ->select('keeps.id', 'posts.id as post_id', 'sockpuppet.name', 'sockpuppet.avatar_url', 'posts.content', 'posts.publishtime', 'posts.platform', 'posts.image_url', 'posts.is_delete', 'posts.tag_json')
                        ->paginate(50);
        foreach ($collect as $item) {
            $item->tag_json = json_decode($item->tag_json);
            $item->image_url = $item->image_url != '' ? explode(',', $item->image_url) : null;
            if ($item->is_delete == 1) {
                $item->content = '***这条动态被删除了***';
            }
        }
        return successJson($collect);
    }
    public function deleteCollect(Request $request,$id)
    {
        $collect = DB::table('keeps')->where('id', $id)->first();
        $userInfo = $request->get('userInfo');
        if (!$collect) {
            throw new ApiErrorException('COLLECT_NOT_FOUND');
        }
        if ($collect->user_id != $userInfo['id']) {
            throw new ApiErrorException('NO_PERMISSION_EDIT_OR_DELETE');
        }
        DB::table('keeps')->where('id', $collect->id)->delete();
        return successJson();
    }
    public function getGroupPost(Request $request, $id)
    {
        $userInfo = $request->get('userInfo');
        $group = DB::table('post_group')->where('id', $id)->first();
        if (!$group) {
            throw new ApiErrorException('GROUP_NOT_FOUND');
        }
        if ($group->user_id != $userInfo['id']) {
            throw new ApiErrorException('DENY_ALLOW_VIEW_THIS_GROUP');
        }
        $sockpuppetList = DB::table('post_group_data')->where('group_id', $group->id)->pluck('sockpuppet_id');
        $post = DB::table('posts')->whereIn('posts.sockpuppet_id', $sockpuppetList)->where('is_delete', 0)->orderBy('id', 'desc')->leftJoin('sockpuppet', 'posts.sockpuppet_id', '=', 'sockpuppet.id')->select('posts.id', 'posts.content', 'posts.sockpuppet_id', 'posts.platform', 'posts.image_url', 'posts.is_top', 'posts.publishtime', 'posts.tag_json', 'posts.is_delete', 'sockpuppet.name', 'sockpuppet.avatar_url')->paginate(50);
        $currentPagePostId = [];
        foreach ($post as $item) {
            $item->image_url = $item->image_url != '' ? explode(',', $item->image_url) : null; 
            $item->tag_json = json_decode($item->tag_json);
            array_push($currentPagePostId, $item->id);
            unset($item->is_delete);
        }
        $likeList = DB::table('post_like')->whereIn('post_like.post_id', $currentPagePostId)->leftJoin('sockpuppet', 'post_like.sockpuppet_id', '=', 'sockpuppet.id')->select('sockpuppet.id as sockpuppet_id', 'sockpuppet.name', 'sockpuppet.avatar_url', 'post_like.post_id')->get();
        $collectList = DB::table('keeps')->where('user_id', $userInfo['id'])->whereIn('post_id', $currentPagePostId)->pluck('post_id');
        $comment = DB::table('comments')->whereIn('comments.post_id', $currentPagePostId)->leftJoin('sockpuppet', 'comments.sockpuppet_id', '=', 'sockpuppet.id')->select('comments.id', 'comments.post_id', 'comments.content', 'comments.image_url', 'comments.publishtime', 'comments.author_like', 'comments.platform', 'sockpuppet.name', 'sockpuppet.id as sockpuppet_id', 'sockpuppet.avatar_url')->get();
        foreach ($comment as $commentItem) {
            $commentItem->image_url = $commentItem->image_url != '' ? explode(',', $commentItem->image_url) : null;
        }
        $data = [
            'post' => $post,
            'likeList' => $likeList,
            'collect' => $collectList,
            'comment' => $comment
        ];
        return successJson($data);
    }
    public function getAllPost(Request $request)
    {
        $userInfo = $request->get('userInfo');
        $post = DB::table('posts')->where('is_delete', 0)->orderBy('id', 'desc')->leftJoin('sockpuppet', 'posts.sockpuppet_id', '=', 'sockpuppet.id')->select('posts.id', 'posts.content', 'posts.sockpuppet_id', 'posts.platform', 'posts.image_url', 'posts.is_top', 'posts.publishtime', 'posts.tag_json', 'posts.is_delete', 'sockpuppet.name', 'sockpuppet.avatar_url')->paginate(50); 
        $currentPagePostId = [];
        foreach ($post as $item) {
            $item->image_url = $item->image_url != '' ? explode(',', $item->image_url) : null; 
            $item->tag_json = json_decode($item->tag_json);
            array_push($currentPagePostId, $item->id);
            unset($item->is_delete);
        }
        $likeList = DB::table('post_like')->whereIn('post_like.post_id', $currentPagePostId)->leftJoin('sockpuppet', 'post_like.sockpuppet_id', '=', 'sockpuppet.id')->select('sockpuppet.id as sockpuppet_id', 'sockpuppet.name', 'sockpuppet.avatar_url', 'post_like.post_id')->get();
        $collectList = DB::table('keeps')->where('user_id', $userInfo['id'])->whereIn('post_id', $currentPagePostId)->pluck('post_id');
        $comment = DB::table('comments')->whereIn('comments.post_id', $currentPagePostId)->leftJoin('sockpuppet', 'comments.sockpuppet_id', '=', 'sockpuppet.id')->select('comments.id', 'comments.post_id', 'comments.content', 'comments.image_url', 'comments.publishtime', 'comments.author_like', 'comments.platform', 'sockpuppet.name', 'sockpuppet.id as sockpuppet_id', 'sockpuppet.avatar_url')->get();
        foreach ($comment as $commentItem) {
            $commentItem->image_url = $commentItem->image_url != '' ? explode(',', $commentItem->image_url) : null;
        }
        $data = [
            'post' => $post,
            'likeList' => $likeList,
            'collect' => $collectList,
            'comment' => $comment
        ];
        return successJson($data);
    }
    public function focusOn($type, $id)
    {
        // type: 1为关注，2为粉丝
        // id: 为查看的id
        $field = $type == 1 ? 'follow.sockpuppet_id' : 'follow.follow_id';
        $field_2 = $type == 1 ? 'follow.follow_id' : 'follow.sockpuppet_id';
        $data = DB::table('follow')->where($field, $id)->leftJoin('sockpuppet', $field_2, '=', 'sockpuppet.id')->select('sockpuppet.id', 'sockpuppet.name', 'sockpuppet.avatar_url', 'sockpuppet.sign')->get();
        return successJson($data);
    }
    public function followSockpuppet(Request $request)
    {
       $rules = [
        'follow_id' => 'required|numeric',
        'sockpuppet_id' => 'required|numeric',
       ]; 
       validateParams($request->only('follow_id', 'sockpuppet_id'), $rules);
       $sockpuppetInfo = Sockpuppet::find($request->input('sockpuppet_id'));
       if (!$sockpuppetInfo) {
        throw new ApiErrorException('SOCKPUPPET_NOT_FOUND');
       }
       $followInfo = Sockpuppet::find($request->input('follow_id'));
       if (!$followInfo) {
        throw new ApiErrorException('SOCKPUPPET_NOT_FOUND');
       }
       $followData = DB::table('follow')->where('sockpuppet_id', $request->input('sockpuppet_id'))->pluck('follow_id');
       if (in_array($request->input('follow_id'), $followData->toArray())) {
        throw new ApiErrorException('CANNOT_RE_FOLLOW');
       }
       
       DB::table('follow')->insert([
        'sockpuppet_id' => $request->input('sockpuppet_id'),
        'follow_id' => $request->input('follow_id'),
        'created_at' => now(),
        'updated_at' => now()
       ]);
       return successJson();
    }
}