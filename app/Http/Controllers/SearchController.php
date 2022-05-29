<?php

namespace App\Http\Controllers;

use App\Http\Resources\ResultResource;
use App\Post;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    public function index($term, User $user)
    {
        $posts = DB::table('posts')->where('text', 'LIKE', '%'.$term.'%')->get();
        $users = DB::table('users')->where('username', 'LIKE', '%'.$term.'%')->get();

        $posts = PostController::postResults($posts, $user);

        // return json
        return response(['posts' => new ResultResource($posts),
        'users' => new ResultResource($users),
            'message' => 'Retrieved successfully'], 200);
    }

    public function trending(User $user)
    {
        $posts = DB::table('posts')->orderByDesc('comments')->take(20)->get();

        $posts = PostController::postResults($posts, $user);

        // return json
        return ['posts' => $posts,
            'message' => 'Retrieved successfully'];
    }

    public function timeline(User $user)
    {
        // get followers then show followers posts
        $posts = Post::orderByDesc('likes')->take(40)->get();

        $posts = PostController::postResults($posts, $user);

        // return json
        $headers = ['Content-Type' => 'application/json; charset=UTF-8'];
        return ['posts' => $posts,
        'message' => 'Retrieved successfully'];
    }

    public function bars(User $user)
    {
        // get followers then show followers posts
        $posts = DB::table('posts')
                ->join('users', 'posts.creator_id', '=', 'users.id')
                ->where('users.type', 'Bar')
                ->select('posts.*')
                ->latest()->get();

        $posts = PostController::postResults($posts, $user);

        // return json
        return response(['posts' => new ResultResource($posts),
            'message' => 'Retrieved successfully'], 200);
    }

    public function restaurants(User $user)
    {
        // get followers then show followers posts
        $posts = DB::table('posts')
        ->join('users', 'posts.creator_id', '=', 'users.id')
        ->where('users.type', 'Restaurant')
        ->select('posts.*')
        ->latest()->get();

        $posts = PostController::postResults($posts, $user);

        // return json
        return response(['posts' => new ResultResource($posts),
        'message' => 'Retrieved successfully'], 200);
    }
}
