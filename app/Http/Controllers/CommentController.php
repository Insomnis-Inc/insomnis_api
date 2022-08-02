<?php

namespace App\Http\Controllers;

use App\Comment;
use App\Http\Resources\ResultResource;
use App\Post;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class CommentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function postComments(Post $post, User $user)
    {
        $comments_query = DB::table('comments')->where('post_id', $post->id)->get();

        $res = $this->commentResults($comments_query, $user);

        // return json
        return response(['data' => new ResultResource($res),
            'message' => 'Retrieved successfully'], 200);
    }

    public static function checkCommentLike(Comment $comment, User $user) {
        $query = DB::table('user_comments_likes')->where('comment_id', $comment->id)
        ->where('user_id', $user->id)->get();
        $liked = '0';
        if(!$query->isEmpty()) {
            $liked = '1';
        }
        return $liked;
    }

     /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function commentComments(Comment $comment, User $user)
    {
        $comments_query = DB::table('comments')->where('comment_id', $comment->id)->get();

       $res = $this->commentResults($comments_query, $user);

        // return json
        return response(['data' => new ResultResource($res),
            'message' => 'Retrieved successfully'], 200);

    }

    public static function commentResults($query, User $user)
    {
        foreach ($query as $key) {
            $key->creator = DB::table('users')->where('id', $key->creator_id)->get();
            $key->created_at = CommentController::diffHumans($key);
            $key->liked = CommentController::checkCommentLike(Comment::find($key->id), $user);
        }

        return $query;
    }

   /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function like(Comment $comment, User $user)
    {
        $query = DB::table('user_comments_likes')->where('comment_id', $comment->id)
        ->where('user_id', $user->id)->get();

        if(!$query->isEmpty()) {
            return response([
                'message' => 'Liked successfully'], 200);
        }

        $query = DB::table('user_comments_likes')->insert([
            'user_id' => $user->id,
            'comment_id' => $comment->id
        ]);

        $comment->increment('likes');

        NotificationController::create(' liked your comment', $comment->creator_id, $user->id);

        return response([
            'message' => 'Liked successfully'], 200);
    }

     /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function dislike(Comment $comment, User $user)
    {
        $query = DB::table('user_comments_likes')->where('comment_id', $comment->id)
        ->where('user_id', $user->id)->get();

        if($query->isEmpty()) {
            return response([
                'message' => 'Disliked successfully'], 200);
        }

        DB::table('user_comments_likes')->where('comment_id', $comment->id)
        ->where('user_id', $user->id)->delete();

        $comment->decrement('likes');

        return response([
            'message' => 'Disliked successfully'], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, User $user)
    {
        Comment::create([
            'id' => Uuid::uuid4(),
            'creator_id' => $user->id,
            'post_id' => $request->input('post_id'),
            'comment_id' => $request->input('comment_id'),
            'text' => $request->input('text'),
            'likes' => '0',
            'comments' => '0'
        ]);

        if($request->input('post_id') != null) {
            $post = Post::find($request->input('post_id'));
            $post->increment('comments');
            NotificationController::create(' commented on your post', $post->creator_id, $user->id);
        } else {
            if($request->input('comment_id') != null) {
                $comment = Comment::find($request->input('comment_id'));
                $comment->increment('comments');
                NotificationController::create(' replied to your comment', $comment->creator_id, $user->id);
            }
        }


        // return json
        return response(['data' => new ResultResource($comment),
            'message' => 'Created successfully'], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Comment  $comment
     * @return \Illuminate\Http\Response
     */
    public function show(Comment $comment, User $user)
    {
        $query = DB::table('user_comments_likes')->where('comment_id', $comment->id)
        ->where('user_id', $user->id)->get();
        $liked = '0';
        if(!$query->isEmpty()) {
            $liked = '1';
        }
        $comment->liked = $liked;

        // return post json
        return response(['data' => new ResultResource($comment),
        'message' => 'Retrieved successfully'], 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Comment  $comment
     * @return \Illuminate\Http\Response
     */
    public function edit(Comment $comment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Comment  $comment
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Comment $comment)
    {
        $comment->update([
            'text' => $request->input('text')
        ]);

        $comment->save();

        // return json
        return response([
            'message' => 'Updated successfully'], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Comment  $comment
     * @return \Illuminate\Http\Response
     */
    // public function destroy(Comment $comment)
    // {
    //     $comment->update([
    //         'is_deleted' => '1'
    //     ]);

    //     $comment->save();

    //     // return json
    //     return response([
    //         'message' => 'Deleted successfully'], 200);
    // }

    public static function diffHumans($key) {
        return Carbon::createFromTimeStamp(strtotime($key->created_at))->diffForHumans();
    }
}
