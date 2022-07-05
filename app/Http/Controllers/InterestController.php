<?php

namespace App\Http\Controllers;

use App\Interest;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Str;
use App\Http\Resources\ResultResource;

class InterestController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $interests = Interest::all();


        return response(['data' => new ResultResource($interests),
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
    public function store(Request $request)
    {
        Interest::create([
            'id' => Uuid::uuid4(),
            'name' => $request->input('name'),
            'image' => $this->uploadImage($request, 'image')
        ]);

         return response([
         'message' => 'Created successfully'], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Interest  $interest
     * @return \Illuminate\Http\Response
     */
    public function show(Interest $interest)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Interest  $interest
     * @return \Illuminate\Http\Response
     */
    public function edit(Interest $interest)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Interest  $interest
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Interest $interest)
    {
        $interest->update([
            'name' => $request->input('name'),
        ]);
        $interest->save();

        return response([
        'message' => 'Interest updated successfully'], 200);
    }


    public function updatePhoto(Request $request, Interest $interest)
    {
        $interest->update([
            'image' => $this->uploadImage($request, 'image')
        ]);
        $interest->save();

        return response([
        'message' => 'Interest updated successfully'], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Interest  $interest
     * @return \Illuminate\Http\Response
     */
    public function destroy(Interest $interest)
    {
        $interest->delete();

        return response([
            'message' => 'Interest deleted successfully'], 200);

    }

    public static function uploadImage(Request $request, $name )
    {
        $path = 'uploads/image/';
        $file = $request->file($name);
        $img_name = time().Str::random(32).$file->getClientOriginalName();
        $extension = $file->extension();

        move_uploaded_file($_FILES[$name]['tmp_name'], $path.$img_name);


        return $path.$img_name;
    }
}
