<?php

namespace App\Http\Controllers\Chat;

use App\Chat;
use App\ChatUser;
use App\Conversation;
use App\Http\Controllers\Controller;
use App\Services\Chat\ChatService;
use App\Services\Comms\CommsService;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    private $commsService;
    private $chatService;
    public function __construct(CommsService $commsService, ChatService $chatService)
    {
        $this->commsService = $commsService;
        $this->chatService = $chatService;
    }

    public function index()
    {
        $user_id = Auth::user()->id;
        $user = Auth::user();
        if ($user->chatrooms->isEmpty()) {
            return response()->json(array('success' => false, 'message' => 'No Chat Found !'));
        } else {
            $chat_ids = array_column(array_values($user->chatrooms->toArray()), 'chat_id');
            $chatRes = array();

            $convo = Conversation::select('chat_id', 'created_at')->whereIn('chat_id', $chat_ids)->distinct('chat_id')
                ->orderBy('created_at', 'desc')
                ->get()
                ->groupBy('chat_id');
                // return response()->json(array('success' => true, 'data' => $convo));
            foreach ($convo as $key => $chatroom) {
                $chat = Chat::find($key);
                if ($chat) {
                    if($chat->property_id != 0) {
                        $chat['property'] = $chat->property;
                    }
                    $chat['users'] = ChatUser::select('user_id', 'is_read')
                        ->where('chat_id', $chat->id)
                        ->with('users')
                        ->get();
                        
                    $chat['conversations'] = $chat->conversations;
                }
                $chatRes[] = $chat;
            }

            return response()->json(array('success' => true, 'data' => $chatRes));
        }
    }

    public function create(Request $request)
    {
        if (!is_null($request->property_id) || $request->property_id != 0) {
            return $this->chatService->createChatForBuyingRoom($request);
        } else {
            return $this->chatService->createChatForInbox($request);
        }

    }

    public function show(Request $request, $id)
    {
        $limit = 50;
        $offset = 0;
        if ($request->offset) {
            $offset = $request->offset;
        }

        $chat = Chat::where(['id' => $id, 'status' => 1])->first();

        if ($chat) {
            $conversations = $chat->conversations()
                ->orderBy('created_at', 'asc')
            // ->offset($offset)
            // ->limit(50)
                ->get();

            if (!$conversations->isEmpty()) {
                $chat->conversations = $conversations;
                foreach ($chat->conversations as $key => $message) {
                    $chat->conversations[$key]->user = $message->user;
                }
            } else {
                $chat->conversations = [];
            }
            return response()->json($chat);
        } else {
            return response()->json(array('success' => false, 'message' => 'No Chat Found !'));
        }
    }

    public function messageNotification()
    {
        $data = $this->chatService->userUnreadMessages();
        return $data;
    }

    public function updateChatNotification($id)
    {
        $chat_id = $id;
        $user_id = Auth::user()->id;
        $response = $this->chatService->updateChatNotification($chat_id, $user_id);
        return [];
    }
}
