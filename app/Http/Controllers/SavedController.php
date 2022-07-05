<?php

namespace App\Http\Controllers;

use App\Post;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\ResultResource;

class SavedController extends Controller
{

    public function index(User $user)
    {
        $query = DB::table('saved_posts')
                ->where('user_id', $user->id)
                ->get();

        if($query->isEmpty()) {
            return response([
                'data' => [],
                'message' => 'Retrieved successfully'], 200);
        }

        $res = collect();
        foreach ($query as $row) {
            $post_query = DB::table('posts')->where('id', $row->post_id)->get();
            $res = $res->merge($post_query);
        }

        $posts = PostController::postResults($res, $user);
         // return json
        return response(['data' => new ResultResource($posts),
            'message' => 'Retrieved successfully'], 200);
    }

    public function savePost(User $user, Post $post)
    {
        $query = DB::table('saved_posts')
                ->where('user_id', $user->id)
                ->where('post_id', $post->id)
                ->get();

        if(!$query->isEmpty()) {
            return response([
                'message' => 'Saved successfully'], 200);
        }

        DB::table('saved_posts')->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'created_at' => Carbon::now()
        ]);

        return response([
            'message' => 'Saved successfully'], 200);
    }


    public function unsavePost(User $user, Post $post)
    {
        $query = DB::table('saved_posts')
                ->where('user_id', $user->id)
                ->where('post_id', $post->id)
                ->get();

        if($query->isEmpty()) {
            return response([
                'message' => 'Unsaved successfully'], 200);
        }

        DB::table('saved_posts')
        ->where('post_id', $post->id)
        ->where('user_id', $user->id)
        ->delete();

        return response([
            'message' => 'Unsaved successfully'], 200);
    }


}
