<?php

namespace App\Http\Controllers;

use App\ChatRoom;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\ResultResource;
use Illuminate\Support\Facades\Validator;
use App\Message;
use App\User;

class MessageController extends Controller
{
    public function store(Request $request, User $user)
    {
        $media = PostController::uploadFile($request, 'media');
        // message has a type please - text, audio, image, video
        // message bool newChatRoom
        // if true => provide member_count
        // if false => provide chatroom_id

        $newChatRoom = $request->input('new_chat_room');

        if($newChatRoom) {
            $chatRoom = ChatRoom::create([
                'id' => Uuid::uuid4(),
                'member_count' => $request->input('member_count'),
            ]);
            DB::table('user_chatrooms')->insert([
                'user_id' => $user->id,
                'chatroom_id' => $chatRoom->id
            ]);
            // add the receiver or group
            $receivers = $request->input('receiverIds');
            foreach ($receivers as $key) {
                DB::table('user_chatrooms')->insert([
                    'user_id' => $key,
                    'chatroom_id' => $chatRoom->id
                ]);
            }
        } else {
            $chatRoom = ChatRoom::find($request->input('chatroom_id'));
        }

        // MESSAGE
        $message = Message::create([
            'id' => Uuid::uuid4(),
            'text' => $request->input('text'),
            'media' => $media,
            'sender_id' => $user->id,
            'chatroom_id' => $chatRoom->id,
        ]);

        // Send Notification and Pusher

        return response(['data' => new ResultResource($message),
         'message' => 'Group created successfully'], 200);
    }
}
