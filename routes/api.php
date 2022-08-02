<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });


// public routes
Route::post('login', 'UserController@login')->name('login');

// user sign up
Route::post('register','UserController@store')->name('register');

// user log out
Route::post('logout', 'UserController@logout')->name('logout');

Route::get('login', function () {
    return response(["message" => "Access denied"], 401);
});


// add change phone
Route::post('users/{user}/phone', 'UserController@changePhone');
// add update photos
Route::post('users/{user}/cover_photo', 'UserController@changeCoverPhoto');
// add update photos
Route::post('users/{user}/profile_photo', 'UserController@changeProfilePhoto');
 // update names, bio, address
 Route::post('users/{user}/update', 'UserController@changeNamesBioAddress');

Route::get('users/{user}/profile/{me}', 'UserController@show');

Route::get('usersindex', 'UserController@index');

// follow
Route::get('users/{user}/follow/{toFollow}', 'UserController@follow');
Route::get('users/{user}/unfollow/{toUnfollow}', 'UserController@unfollow');
Route::get('users/{user}/followings', 'UserController@followings');
Route::get('users/{user}/followers', 'UserController@followers');

// SEARCH
Route::get('search/{term}/users/{user}', 'SearchController@index');
// trending
Route::get('trending/{user}', 'SearchController@trending');
// for you
Route::get('timeline/{user}', 'SearchController@timeline');
// bars
Route::get('bars/{user}', 'SearchController@bars');
// restaurants
Route::get('restaurants/{user}', 'SearchController@restaurants');


// POSTS
Route::get('posts/{user}', 'PostController@index');
Route::get('posts/{user}/liked', 'PostController@likedPosts');
Route::post('posts/{user}/create', 'PostController@store');
Route::get('posts/{post}/show/{user}', 'PostController@show');
Route::post('posts/{post}/update', 'PostController@update');
Route::get('posts/{post}/delete', 'PostController@destroy');
// post likes / dislikes
Route::get('posts/{post}/like/{user}', 'PostController@like');
Route::get('posts/{post}/dislike/{user}', 'PostController@dislike');

// COMMENTS
Route::post('comments/{user}', 'CommentController@store');
Route::get('comments/{comment}/show/{user}', 'CommentController@show');
Route::post('comments/{comment}/update', 'CommentController@update');
// comment likes / dislikes
Route::get('comments/{comment}/like/{user}', 'CommentController@like');
Route::get('comments/{comment}/dislike/{user}', 'CommentController@dislike');
// post comments
Route::get('posts/{post}/comments/{user}', 'CommentController@postComments');
Route::get('comments/{comment}/check/{user}', 'CommentController@commentComments');

// Events
Route::post('events/{user}', 'EventController@store');
Route::get('events/{user}', 'EventController@index');
Route::get('events/{user}/type/{type}', 'EventController@eventType');
// to show use Posts show
// to update use Posts update
Route::get('events/{post}/delete', 'EventController@destroy');


// Interests
Route::get('interests', 'InterestController@index');

// Saved Posts
Route::get('users/{user}/posts/{post}/save', 'SavedController@savePost');
Route::get('users/{user}/posts/{post}/unsave', 'SavedController@unsavePost');
Route::get('users/{user}/savedposts', 'SavedController@index');
Route::get('search/{term}/saved/{user}', 'SavedController@searchSavedPosts');

// ===========================================================================
// ==================================== EXTRAS ===============================
// ===========================================================================
Route::post('extras/{user}/posts', 'ExtraController@storePost');
Route::post('extras', 'ExtraController@createExtra');
Route::get('extras', 'ExtraController@index');
Route::get('search/{term}/extras/{extra}/users/{user}', 'ExtraController@searchExtraPosts');
Route::get('extras/{extra}/users/{user}', 'ExtraController@showExtraPosts');
Route::get('extras/{extra}/delete', 'ExtraController@destroy');
Route::get('extras/{post}/postdelete', 'ExtraController@destroyExtraPost');



// ===========================================================================
// ==================================== GROUPS ===============================
// ===========================================================================
Route::post('groups/{user}', 'GroupController@store'); //
Route::get('groups/{user}', 'GroupController@index'); //
Route::get('groups/{user}/member', 'GroupController@userGroups'); //
Route::post('groups/{group}/update', 'GroupController@update'); //
Route::get('groups/{group}/users/{user}', 'GroupController@show'); //
Route::get('groups/{group}/delete', 'GroupController@destroy'); //
Route::get('groups/{group}/users/{user}/add', 'GroupController@addUsers'); //
Route::get('groups/{group}/users/{user}/remove', 'GroupController@removeUsers'); //
Route::get('search/{term}/groups/{group}/users/{user}', 'GroupController@searchGroupPosts'); //
Route::get('groups/{group}/users/{user}/posts', 'GroupController@showGroupPosts'); //
Route::post('groups/{user}/posts', 'GroupController@storePost'); //

// NOTIFICATIONS
Route::get('notifications/{user}', 'NotificationController@index');
