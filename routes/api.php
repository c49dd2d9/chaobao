<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
/**
 * 目前已经测试通过的Api：
 * user/register [√]
 * user/login [√]
 * sockpuppet/create [√]
 * sockpuppet/list[√]
 * sockpuppet/info/{id}[√]
 * sockpuppet/update[√]
 * post/create[√]
 * post/update[√]
 * post/delete/{id}[√]
 * post/top/{id}[√]
 * post/{id}[√]
 * search/tag[√]
 * create/emoji[√]
 * emoji/list[√]
 * delete/emoji[√]
 * collect/post/{id}[√]
 * collect/list[√]
 * collect/delete/{id}[√]
 * comment/create[√]
 * new/comment/{id?}[√]
 * comment/delete/{id}[√]
 * comment/{id}[√]
 * comment/update[√]
 * get/sockpuppet/post/list/{id}[√]
 * group/create[√]
 * group/list[√]
 * group/delete/{id}[√]
 * add/sockpuppet/to/group[√]
 * group/detail/{id}[√]
 * remove/sockpuppet/to/group[√]
 * like/post[√]
 */
Route::post('user/register', 'Api\UserController@register');
Route::post('user/login', 'Api\UserController@login');
Route::post('sockpuppet/create', 'Api\SockpuppetController@create')->middleware('user.auth');
Route::get('sockpuppet/delete/{id}', 'Api\SockpuppetController@delete')->middleware('user.auth');
Route::get('sockpuppet/list', 'Api\SockpuppetController@list')->middleware('user.auth');
Route::get('sockpuppet/info/{id}', 'Api\SockpuppetController@getSockpuppetInfo')->middleware('user.auth');
Route::post('sockpuppet/update', 'Api\SockpuppetController@update')->middleware('user.auth');
Route::post('post/create', 'Api\PostController@create')->middleware('user.auth');
Route::post('post/update', 'Api\PostController@update')->middleware('user.auth');
Route::get('post/delete/{id}', 'Api\PostController@delete')->middleware('user.auth');
Route::get('post/top/{id}', 'Api\PostController@postTop')->middleware('user.auth');
Route::get('post/{id}', 'Api\PostController@getPostInfo')->middleware('user.auth');
Route::post('like/post', 'Api\PostController@likePost')->middleware('user.auth');
Route::get('get/sockpuppet/post/list/{id}', 'Api\PostController@getSockpuppetPost')->middleware('user.auth');
Route::post('/search/tag', 'Api\TagController@list')->middleware('user.auth');
Route::get('emoji/list', 'Api\EmojiController@list')->middleware('user.auth');
Route::post('create/emoji', 'Api\EmojiController@create')->middleware('user.auth');
Route::post('delete/emoji', 'Api\EmojiController@delete')->middleware('user.auth');
Route::get('collect/post/{id}', 'Api\PostController@collectPost')->middleware('user.auth');
Route::get('collect/list', 'Api\PostController@collectList')->middleware('user.auth');
Route::get('collect/delete/{id}', 'Api\PostController@deleteCollect')->middleware('user.auth');
Route::post('comment/create', 'Api\CommentController@create')->middleware('user.auth');
Route::get('comment/delete/{id}', 'Api\CommentController@delete')->middleware('user.auth');
Route::get('comment/{id}', 'Api\CommentController@getCommentInfo')->middleware('user.auth');
Route::post('comment/update', 'Api\CommentController@updateCommentInfo')->middleware('user.auth');
Route::get('new/comment/{id?}', 'Api\CommentController@newComment')->middleware('user.auth');
Route::post('group/create', 'Api\GroupController@create')->middleware('user.auth');
Route::get('group/list', 'Api\GroupController@list')->middleware('user.auth');
Route::post('group/update', 'Api\GroupController@update')->middleware('user.auth');
Route::get('group/delete/{id}', 'Api\GroupController@delete')->middleware('user.auth');
Route::post('add/sockpuppet/to/group', 'Api\GroupController@addSockpuppetToGroup')->middleware('user.auth');
Route::get('group/detail/{id}', 'Api\GroupController@groupDetail')->middleware('user.auth');
Route::post('remove/sockpuppet/to/group', 'Api\GroupController@deleteSockpuppetToGroup')->middleware('user.auth');
Route::get('group/post/{id}', 'Api\PostController@getGroupPost')->middleware('user.auth');
Route::get('all/post', 'Api\PostController@getAllPost')->middleware('user.auth');
Route::get('focuson/list/{type}/{id}', 'Api\PostController@focusOn')->middleware('user.auth');
Route::post('follow/sockpuppet', 'Api\PostController@followSockpuppet')->middleware('user.auth');
Route::get('tag/post/list/{id}', 'Api\TagController@tagPost')->middleware('user.auth');
Route::post('search', 'Api\SearchController@search')->middleware('user.auth');
