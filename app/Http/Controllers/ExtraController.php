<?php

namespace App\Http\Controllers;

use App\Extra;
use App\User;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\ResultResource;
use App\Post;

class ExtraController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $extras = Extra::all();

        return response([
            'data' => $extras,
            'message' => 'Created successfully'], 200);

    }

    public function searchExtraPosts($term, Extra $extra, User $user)
    {
        $posts = DB::table('posts')->where('text', 'LIKE', '%'.$term.'%')->get();

        if($posts->isEmpty()) {
            // return
        }

        $res = collect();
        foreach ($posts as $row) {
            $query = DB::table('extra_posts')
                    ->where('extra_id', $extra->id)
                    ->where('post_id', $row->id)
                    ->get();

            if(!$query->isEmpty()) {
                $res = $res->merge($query);
            }
        }

        $posts = PostController::postResults($res, $user);

        // return json
        return response(['posts' => new ResultResource($posts),
            'message' => 'Retrieved successfully'], 200);
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function createExtra(Request $request)
    {
        Extra::create([
            'id' => Uuid::uuid4(),
            'name' => $request->input('name')
        ]);


        return response([
            'message' => 'Created successfully'], 200);
    }

     /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storePost(Request $request, User $user)
    {
        $p = new PostController();
        return $p->store($request, $user, false, true);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showExtraPosts(Extra $extra, User $user)
    {
        $query = DB::table('extra_posts')
                ->where('extra_id', $extra->id)
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



    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Extra $extra)
    {
        $extra->delete();
        return response([
            'message' => 'Deleted successfully'], 200);

    }

    public function destroyExtraPost(Post $post)
    {

        $p = new PostController();
        return $p->destroy($post, true);
    }
}
