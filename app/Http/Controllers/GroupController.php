<?php

namespace App\Http\Controllers;

use App\Group;
use App\User;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\ResultResource;
use Illuminate\Support\Facades\Validator;

class GroupController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(User $user)
    {
        $groups = DB::table('groups')->orderBy('member_count')->get();
        foreach ($groups as $row) {
            $row->created_at = CommentController::diffHumans($row);

            $query = DB::table('group_users')->where('user_id', $user->id)
                    ->where('group_id', $row->id)->get();

            if($query->isEmpty()) {
                $row->is_member = '0';
            } else {
                $row->is_member = '1';
            }
        }
        return response(['data' => new ResultResource($groups),
        'message' => 'Group created successfully'], 200);
    }

    public function userGroups(User $user)
    {
        $query = DB::table('group_users')->where('user_id', $user->id)->get();

        if($query->isEmpty()) {
            return response(['data' => [],
            'message' => 'Retrieved successfully'], 200);
        }

        $groups = collect();
        foreach ($query as $row) {
            $groups = $groups->merge(DB::table('groups')->where('id', $row->group_id)->get());
        }

        foreach ($groups as $row) {
            $row->created_at = CommentController::diffHumans($row);
            $row->is_member = '1';
        }
        return response(['data' => new ResultResource($groups),
        'message' => 'Retrieved successfully'], 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function addUsers(Group $group, User $user)
    {
        $query = DB::table('group_users')->where('group_id', $group->id)
        ->where('user_id', $user->id)->get();

        if(!$query->isEmpty()) {
            return response([
                'message' => 'User already exists'], 200);
        }

        DB::table('group_users')->insert([
            'group_id' => $group->id,
            'user_id' => $user->id
        ]);

        $group->increment('member_count');
        $group->save();


        return response([
         'message' => 'User added successfully'], 200);
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Group  $group
     * @return \Illuminate\Http\Response
     */
    public function removeUsers(Group $group, User $user)
    {
        DB::table('group_users')->where([
            'group_id' => $group->id,
            'user_id' => $user->id
        ])->delete();


        if($group->member_count > 1) {
            $group->decrement('member_count');
            $group->save();
        }

        return response([
         'message' => 'User removed successfully'], 200);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function store(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            // 'profile_pic' => 'image|mimes:jpeg,png,jpg',
            // 'cover_pic' => 'image|mimes:jpeg,png,jpg',
        ]);

        if($validator->fails()){
            return response(['error' => $validator->errors(), 'Validation Error']);
        }

        $profile_pic_path = UserController::uploadOneImage($request, 'profile_pic');
        $cover_pic_path = UserController::uploadOneImage($request, 'cover_pic');

        // CREATE USER
        $group = Group::create([
            'id' => Uuid::uuid4(),
            'name' => $request->input('name'),
            'profile_pic' => $profile_pic_path,
            'cover_pic' => $cover_pic_path,
            'admin_id' => $user->id,
            'member_count' => 1,
        ]);

        DB::table("group_users")->insert([
            'group_id' => $group->id,
            'user_id' => $user->id
        ]);

        return response(['data' => new ResultResource($group),
         'message' => 'Group created successfully'], 200);
    }


    /**
     * Display the specified resource.
     *
     * @param  \App\Group  $group
     * @return \Illuminate\Http\Response
     */
    public function show(Group $group, User $user)
    {
        $groups_query = DB::table('groups')->where('id', $group->id)->get();
        foreach ($groups_query as $row) {
            $row->created_at = CommentController::diffHumans($row);
        }

        $admin = DB::table('users')->where('id', $group->admin_id)->get();

        $me_query = DB::table('group_users')->where('group_id', $group->id)
                            ->where('user_id', $user->id)->get();

        if($me_query->isEmpty()) {
            $is_member = '0';
        } else {
            $is_member = '1';
        }

        $results = [
            'group' => $groups_query,
            'admin' => $admin,
            'is_member' => $is_member
        ];
        return response(['data' => new ResultResource($results),
            'message' => 'Retrieved successfully'], 200);

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Group  $group
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Group $group)
    {

        $profile_pic_path = UserController::uploadOneImage($request, 'profile_pic');
        $cover_pic_path = UserController::uploadOneImage($request, 'cover_pic');

        $group->update([
            'name' => $request->input('name'),
            'profile_pic' => $profile_pic_path,
            'cover_pic' => $cover_pic_path
        ]);

        $group->save();

        return response(['data' => new ResultResource($group),
            'message' => 'Updated successfully'], 200);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Group  $group
     * @return \Illuminate\Http\Response
     */
    public function destroy(Group $group)
    {
        $group->delete();
        return response([
            'message' => 'Deleted successfully'], 200);

    }


    // group posts
    public function storePost(Request $request, User $user)
    {
        // add group_id in yo request just
        $p = new PostController();
        return $p->store($request, $user, false, false, true);
    }

    public function showGroupPosts(Group $group, User $user)
    {
        $query = DB::table('group_posts')
                ->where('group_id', $group->id)
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


    public function searchGroupPosts($term, Group $group, User $user)
    {
        $posts_query = DB::table('posts')->where('text', 'LIKE', '%'.$term.'%')->get();

        if($posts_query->isEmpty()) {
            return response(['posts' => [],
            'message' => 'Retrieved successfully'], 200);
        }

        $res = collect();
        foreach ($posts_query as $row) {
            $query = DB::table('group_posts')
                    ->where('group_id', $group->id)
                    ->where('post_id', $row->id)
                    ->get();

            if(!$query->isEmpty()) {
                $res = $res->merge($query);
            }
        }

        if(!$res->isEmpty()) {
            $result = collect();
            foreach ($res as $row) {
                $single_q = DB::table('posts')->where('id', $row->post_id)->get();
                $result = $result->merge($single_q);
            }

            $posts = PostController::postResults($result, $user);
        } else {
            $posts = collect();
        }
        // return json
        return response(['posts' => new ResultResource($posts),
            'message' => 'Retrieved successfully'], 200);
    }


}
