<?php

namespace App\Http\Controllers;

use App\Event;
use App\Http\Resources\ResultResource;
use App\Post;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Str;
use Google\Cloud\Storage\StorageClient;


// $table->enum('type', ['image', 'video', 'text']);

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(User $user)
    {
        $posts = DB::table('posts')->where('creator_id', $user->id)->get();
        $results = $this->postResults($posts, $user);

        // return json
        return response(['data' => new ResultResource($results),
            'message' => 'Retrieved successfully'], 200);

    }


    public static function postResults($query, User $user)
    {
        foreach ($query as $key) {
            $key->created_at = CommentController::diffHumans($key);
            $key->creator = DB::table('users')->where('id', $key->creator_id)->get();
            $key->liked = PostController::checkPostLike(Post::find($key->id), $user);
            $key->saved = PostController::checkPostSaved(Post::find($key->id), $user);
        }

        return $query;
    }

     /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function likedPosts(User $user)
    {
        $query = DB::table('user_posts_likes')->where('user_id', $user->id)->get();
        $posts = collect();
        foreach ($query as $key) {
            $posts = $posts->merge(DB::table('posts')->where('id', $key->post_id)->get());
        }
        $posts = $this->postResults($posts, $user);

        // return json
        return response(['data' => new ResultResource($posts),
            'message' => 'Retrieved successfully'], 200);
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function like(Post $post, User $user)
    {
        $query = DB::table('user_posts_likes')->where('post_id', $post->id)
        ->where('user_id', $user->id)->get();

        if(!$query->isEmpty()) {
            return response([
                'message' => 'Liked successfully'], 200);
        }

        $query = DB::table('user_posts_likes')->insert([
            'user_id' => $user->id,
            'post_id' => $post->id
        ]);

        $post->increment('likes');

        NotificationController::create(' liked your post', $post->creator_id, $user->id);

        return response([
            'message' => 'Liked successfully'], 200);
    }

     /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function dislike(Post $post, User $user)
    {
        $query = DB::table('user_posts_likes')->where('post_id', $post->id)
        ->where('user_id', $user->id)->get();

        if($query->isEmpty()) {
            return response([
                'message' => 'Disliked successfully'], 200);
        }


        DB::table('user_posts_likes')->where('post_id', $post->id)
        ->where('user_id', $user->id)->delete();

        $post->decrement('likes');

        return response([
            'message' => 'Disliked successfully'], 200);
    }

    /**
     * =========================================
     * ============== READ ME PLEASE ===========
     * =========================================
     *
     * if 'event', the request should include 'event_type' parameter
     *
     * if 'extra', the request should include 'extra_id' parameter
     *
     * if 'group', the request should include 'group_id' parameter
     *
     * Posts are of type:
     *  'text', 'video', 'audio', 'image'
     */
    public function store(Request $request, User $user, $event = false, $extra = false, $group = false)
    {
        // validate

        // move file
        $post = Post::create([
            'id' => Uuid::uuid4(),
            'creator_id' => $user->id,
            'comments' => '0',
            'likes' => '0',
            'text' => $request->input('text'),
            'type' => $request->input('type'),
            'attached' => $this->uploadFile($request, 'attached'),
            'is_deleted' => '0',
        ]);

        if($event) {
            $event = DB::table('events')->insert([
                // 'id' => Uuid::uuid4(),
                'event_type' => $request->input('event_type') ?? 'Clubs',
                'post_id' => $post->id
            ]);
        }

        if($extra) {
             $extra = DB::table('extra_posts')->insert([
                'extra_id' =>$request->input('extra_id'),
                'post_id' => $post->id
            ]);
        }

        if($group) {
            DB::table('group_posts')->insert([
                'group_id' =>$request->input('group_id'),
                'post_id' => $post->id
            ]);
        }

        // return json
        return response(['data' => new ResultResource($post),
            'message' => 'Created successfully'], 200);
    }


    /**
     * Display the specified resource.
     *
     * @param  \App\Post  $post
     * @return \Illuminate\Http\Response
     */
    public function show(Post $post, User $user)
    {
        $query = DB::table('user_posts_likes')->where('post_id', $post->id)
                ->where('user_id', $user->id)->get();
        $liked = '0';
        if(!$query->isEmpty()) {
            $liked = '1';
        }
        $post->liked = $liked;

        // return post json
        return response(['data' => new ResultResource($post),
        'message' => 'Retrieved successfully'], 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Post  $post
     * @return \Illuminate\Http\Response
     */
    public function edit(Post $post)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Post  $post
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Post $post)
    {
        $post->update([
            'text' => $request->input('text')
        ]);

        $post->save();

        // return json
        return response([
            'message' => 'Updated successfully'], 200);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Post  $post
     * @return \Illuminate\Http\Response
     */
    public function destroy(Post $post, $event = false)
    {
        $post->update([
            'is_deleted' => '1'
        ]);

        $post->save();

        if($event) {
            $event = DB::table('events')->where([
                'post_id' => $post->id
            ])->get();

            $this->deleteQuery($event);
        }

        // return json
        return response([
            'message' => 'Deleted successfully'], 200);
    }

    // public static function deleteQuery($query)
    // {
    //     foreach ($query as $row) {
    //         $row->delete();
    //     }
    // }

    // public static function uploadFile(Request $request, $name)
    // {

    //     // type == audio, image, video
    //     $type = $request->input('type');
    //     $video_path = public_path().'/uploads/video/';
    //     $image_path = public_path().'/uploads/image/';
    //     $audio_path = public_path().'/uploads/audio/';
    //     // $filepath = "gs://$bucket.appspot.com/uploadedfiles/$filename";

    //     if($request->hasFile($name)) {
    //         $file = $request->file($name);
    //         $img_name = time().Str::random(32).$file->getClientOriginalName();
    //         $extension = $file->extension();

    //         $result = $type == 'image' ? $image_path.$img_name :
    //                 ( $type == 'video'? $video_path.$img_name : $audio_path.$img_name );

    //         move_uploaded_file($_FILES[$name]['tmp_name'], $result);


    //         return $result;
    //     }
    // }


/**
 * Upload a file.
 *
 * composer require google/cloud
 *
 * @param string $bucketName The name of your Cloud Storage bucket.
 * @param string $objectName The name of your Cloud Storage object.
 * @param string $source The path to the file to upload.
 */
public static function uploadFile(Request $request, $name)
{
    $bucketName = 'insomnis_assets';
    // $source = '/path/to/your/file';

    if($request->hasFile($name)) {
        $fileNameGen = $request->file($name);
        $objectName = time().Str::random(32).$fileNameGen->getClientOriginalName();
        $storage = new StorageClient();
        $file = fopen($_FILES[$name]['tmp_name'], 'r');
        $bucket = $storage->bucket($bucketName);
        $object = $bucket->upload($file, [
            'name' => $objectName
        ]);
        // printf('Uploaded %s to gs://%s/%s' . PHP_EOL, basename($$_FILES[$name]['tmp_name']), $bucketName, $objectName);

        return 'https://storage.googleapis.com/' . $bucketName . '/' . $objectName;
    }
}


    public static function checkPostLike(Post $post, User $user) {
        $query = DB::table('user_posts_likes')->where('post_id', $post->id)
        ->where('user_id', $user->id)->get();
        $liked = '0';
        if(!$query->isEmpty()) {
            $liked = '1';
        }
        return $liked;
    }

    public static function checkPostSaved(Post $post, User $user) {
        $query = DB::table('saved_posts')->where('post_id', $post->id)
        ->where('user_id', $user->id)->get();
        $saved = '0';
        if(!$query->isEmpty()) {
            $saved = '1';
        }
        return $saved;
    }


}
