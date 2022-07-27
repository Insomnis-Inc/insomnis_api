<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use Illuminate\Support\Facades\Validator;
use Ramsey\Uuid\Uuid;
use App\Http\Resources\ResultResource;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;


// $table->enum('type', ['Bar', 'User', 'Restaurant', 'Hotel', 'Apartment']);


class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        DB::table('user_follower')->truncate();
        $query = DB::table('users')->get();
        foreach ($query as $row) {
            $user = User::find($row->id);
            $user->update([
                'following' => 0,
                'followers' => 0
            ]);

            $user->save();
        }
    }

    public function follow(User $user, User $toFollow)
    {
        $this->followUser($user, $toFollow);
        $user->increment('following');
        $toFollow->increment('followers');
        return response([
         'message' => 'Followed Successfully'], 200);
    }

    public function unfollow(User $user, User $toUnfollow)
    {
        $this->unfollowUser($user, $toUnfollow);
        $user->decrement('following');
        $toUnfollow->decrement('followers');
        return response([
         'message' => 'Unfollowed Successfully'], 200);
    }

    public static function followUser(User $user, User $toFollow)
    {
        $query = DB::table('user_follower')->where('follower_id', $user->id)
        ->where('user_id', $toFollow->id)->get();

        if(!$query->isEmpty()) {
            return 1;
        }

        $query = DB::table('user_follower')->insert([
            'follower_id' => $user->id,
            'user_id' => $toFollow->id
        ]);


        return 1;
    }

    public static function unfollowUser(User $user, User $toUnfollow)
    {
        $query = DB::table('user_follower')->where('follower_id', $user->id)
        ->where('user_id', $toUnfollow->id)->get();

        if($query->isEmpty()) {
            return 1;
        }


        DB::table('user_follower')->where('follower_id', $user->id)
        ->where('user_id', $toUnfollow->id)->delete();


        return 1;
    }

    public function followings(User $user)
    {
        $query = DB::table('user_follower')->where('follower_id', $user->id)->get();

        if($query->isEmpty()) {
            return response(['data' => [],
            'message' => 'Retrieved Followings'], 200);
        }

        $results = collect();
        foreach ($query as $row) {
           $results = $results->merge(DB::table('users')->where('id', $row->user_id)->get());
        }


        return response(['data' => new ResultResource($results),
         'message' => 'Retrieved followings'], 200);
    }

    public function followers(User $user)
    {
        $query = DB::table('user_follower')->where('user_id', $user->id)->get();

        if($query->isEmpty()) {
            return response(['data' => [],
            'message' => 'Retrieved Followings'], 200);
        }

        $results = collect();
        foreach ($query as $row) {
           $results = $results->merge(DB::table('users')->where('id', $row->follower_id)->get());
        }


        return response(['data' => new ResultResource($results),
         'message' => 'Retrieved followers'], 200);
    }


    // User types are here

    // 'Bar',
    // 'User',
    // 'Restaurant',
    // 'Hotel',
    // 'Apartment'

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'type' => 'required',
            'username' => 'required',
            'profile_pic' => 'image|mimes:jpeg,png,jpg',

        ]);

        if($validator->fails()){
            return response(['error' => $validator->errors(), 'Validation Error']);
        }

        $profile_pic_path = $this->uploadOneImage($request, 'profile_pic');
        $cover_pic_path = $this->uploadOneImage($request, 'cover_pic');

        $testUser = User::where('email', $request->input('email'))->get();

        if(!$testUser->isEmpty()) {
        //     Token::where('user_id', $testUser->id)
        // ->update(['revoked' => true]);


        // $token = $testUser->createToken('TundaToken')->accessToken;

        return response(['data' => new ResultResource($testUser),
        // 'token' => $token,
         'message' => 'Account exists already'], 200);
        }

        // CREATE USER
        $user = User::create([
            'id' => Uuid::uuid4(),
            'display_name' => $request->input('display_name')?? $request->input('username'),
            'username' => $request->input('username'),
            'email' => $request->input('email'),
            'profile_pic' => $profile_pic_path,
            'password' => '@3djhrf%&&*',
            'cover_pic' => $cover_pic_path,
            'bio' => $request->input('bio'),
            'phone' => $request->input('phone'),
            'type' => $request->input('type'),
            'address' => $request->input('address'),
            'following' => 0,
            'followers' => 0
        ]);

        // Token::where('user_id', $user->id)
        // ->update(['revoked' => true]);

        // StripeController::trial($user);

        // $token = $user->createToken('TundaToken')->accessToken;

        return response(['data' => new ResultResource($user),
        // 'token' => $token,
         'message' => 'User created successfully'], 200);
    }


    public function login (Request $request) {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
        ]);
        if ($validator->fails())
        {
            return response(['errors'=>$validator->errors()->all()], 422);
        }
        $user = User::where('email', $request->email)->first();
        if ($user) {
                // $user->token()->revoke();
                // $user->token()->delete();

                // Token::where('user_id', $user->id)
                // ->update(['revoked' => true]);

                // $token = $user->createToken('Tunda Password Grant Client')->accessToken;
                $response = [
                    // 'token' => $token,
                 'data' => new ResultResource($user),
                 "message" => "User logged in Successfully"];
                return response($response, 200);

        } else {
            $response = ["message" =>'User does not exist'];
            return response($response, 404);
        }
    }


    public function logout (Request $request) {
        // $token = $request->user()->token();
        // $token->revoke();
        // $response = ['message' => 'You have been successfully logged out!'];
        // return response($response, 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function show(User $user, User $me)
    {
        $query = DB::table('user_follower')->where('user_id', $user->id)
                ->where('follower_id', $me->id)->get();

        $followingUser = 0;
        if($query->isEmpty()) {
            $followingUser = 0;
        } else {
            $followingUser = 1;
        }

        $userquery = DB::table('users')->where('id', $user->id)->get();

        foreach ($userquery as $key) {
            $key->you_follow = $followingUser;
        }

        return response(['data' => new ResultResource($userquery),
        'message' => 'Retrieved successfully'], 200);
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    // public function destroy(User $user)
    // {
    //     $user->delete();
    //     return response(['message' => 'Deleted successfully'], 200);
    // }


    public static function uploadOneImage(Request $request, $name, $cover = false )
    {
        //COVER IMAGE
        $path = 'uploads/users/';
        if($request->hasFile($name)) {
        $file = $request->file($name);
        $img_name = time().Str::random(32).$file->getClientOriginalName();
        $extension = $file->extension();

        move_uploaded_file($_FILES[$name]['tmp_name'], $path.$img_name);
        } else {
            $img_name = 'profile.png';
            if($cover) {
                $img_name = 'cover.png';
            }
        }

        return $path.$img_name;
    }


        /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function changePhone(Request $request, User $user)
    {
        // ADD SELLER DETAILS
        $user->update([
            'phone' => $request->input('phone'),
        ]);
        $user->save();

        return response(['user' => new ResultResource($user),
        'message' => 'User updated successfully'], 200);
    }


       /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function changeCoverPhoto(Request $request, User $user)
    {

        $cover_pic_path = $this->uploadOneImage($request, 'cover_pic', true);


        // ADD SELLER DETAILS
        $user->update([
            'cover_pic' => $cover_pic_path,
        ]);
        $user->save();

        return response(['user' => new ResultResource($user),
        'message' => 'User updated successfully'], 200);
    }


       /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function changeProfilePhoto(Request $request, User $user)
    {


        $profile_pic_path = $this->uploadOneImage($request, 'profile_pic');

        // ADD SELLER DETAILS
        $user->update([
            'profile_pic' => $profile_pic_path,
        ]);
        $user->save();

        return response(['user' => new ResultResource($user),
        'message' => 'User updated successfully'], 200);
    }




        /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function changeNamesBioAddress(Request $request, User $user)
    {
        // ADD SELLER DETAILS
        $user->update([
            'display_name' => $request->input('display_name'),
            'username' => $request->input('username'),
            'bio' => $request->input('bio'),
            'address' => $request->input('address'),
        ]);
        $user->save();

        return response(['user' => new ResultResource($user),
        'message' => 'User updated successfully'], 200);
    }


}
