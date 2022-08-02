<?php

namespace App\Http\Controllers;

use App\ChatRoom;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\ResultResource;
use App\User;

class ChatRoomController extends Controller
{
    public function allMessages(ChatRoom $chatRoom)
    {
        $messages_query = DB::table('messages')
                        ->where('chatroom_id', $chatRoom->id)
                        ->get();

        $results = $this->messageResults($messages_query);


        return response(['data' => new ResultResource($results),
        'message' => 'Retrieved successfully'], 200);
    }

    public function userChatRooms(User $user)
    {
        $query = DB::table('user_chatrooms')
                            ->where('user_id', $user->id)
                            ->get();

        $results = collect();
        foreach ($query as $row) {
            $chat = ChatRoom::find($row->chatroom_id);
            foreach ($chat as $key) {
                $key->users = $this->chatRoomUsers($chat, $user);
            }

            $results = $results->merge($chat);
        }

        return response(['data' => new ResultResource($results),
        'message' => 'Retrieved successfully'], 200);
    }

    public static function chatRoomUsers(ChatRoom $chat, User $user)
    {
        $users = collect();
        foreach ($chat as $key) {

            $chat_query = DB::table('user_chatrooms')
                                ->where('chatroom_id', $key->id)
                                ->get();
            foreach ($chat_query as $value) {
                if($value->user_id != $user->id) {
                    $users = $users->merge(DB::table('users')->where('id', $value->user_id)->get());
                }
            }
        }

        return $users;
    }

    public static function messageResults($query)
    {
        foreach ($query as $key) {
            $key->created_at = CommentController::diffHumans($key);
            $key->creator = DB::table('users')->where('id', $key->sender_id)->get();
        }

        return $query;
    }



    public function destroy(ChatRoom $chatRoom)
    {
        $chatRoom->delete();
        return response([
            'message' => 'Deleted successfully'], 200);

    }
}
