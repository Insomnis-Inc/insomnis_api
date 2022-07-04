<?php

namespace App\Http\Controllers;

use App\Event;
use App\Http\Resources\ResultResource;
use App\Post;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class EventController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(User $user)
    {
        $query = DB::table('events')->get();
        $results = collect();
        foreach ($query as $key) {
            $row = DB::table('posts')->where('id', $key->post_id)->get();
            $results = $results->merge(PostController::postResults($row, $user));
        }

        // return json
        return response(['data' => new ResultResource($results),
            'message' => 'Retrieved successfully'], 200);
    }

    public function eventType(User $user, $type)
    {
        $query = DB::table('events')->where('event_type', $type)->get();
        $results = collect();
        foreach ($query as $key) {
            $row = DB::table('posts')->where('id', $key->post_id)->get();
            $results = $results->merge(PostController::postResults($row, $user));
        }

        // return json
        return response(['data' => new ResultResource($results),
            'message' => 'Retrieved successfully'], 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, User $user)
    {
        $p = new PostController();
        return $p->store($request, $user, true);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Event  $event
     * @return \Illuminate\Http\Response
     */
    // public function show(Post $post, User $user)
    // {
    //     $p = new PostController();
    //     return $p->show($post, $user);
    // }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Event  $event
     * @return \Illuminate\Http\Response
     */
    public function edit(Event $event)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Event  $event
     * @return \Illuminate\Http\Response
     */
    // public function update(Request $request, Post $post)
    // {

    //     $p = new PostController();
    //     return $p->update($request, $post);
    // }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Event  $event
     * @return \Illuminate\Http\Response
     */
    public function destroy(Post $post)
    {

        $p = new PostController();
        return $p->destroy($post, true);
    }
}
