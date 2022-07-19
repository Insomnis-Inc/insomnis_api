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
    public function index()
    {
        //
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
        DB::table('group_users')->delete([
            'group_id' => $group->id,
            'user_id' => $user->id
        ]);


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
            'profile_pic' => 'image|mimes:jpeg,png,jpg',
            'cover_pic' => 'image|mimes:jpeg,png,jpg',
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
            'admin_id' => $user->id
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
        $is_admin = $group->admin_id == $user->id;
        $admin = DB::table('users')->where('id', $group->admin_id)->get();
        $group_users_query = DB::table('group_users')->where('group_id', $group->id)->get();
        $users = collect();
        foreach ($group_users_query as $row) {
            $users = $users->merge(DB::table('users')->where('id', $row->user_id)->get());
        }

        $results = [
            'group' => $groups_query,
            'admin' => $admin,
            'users' => $users
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
            'id' => Uuid::uuid4(),
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
}
