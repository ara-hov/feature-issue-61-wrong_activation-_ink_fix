<?php

namespace App\Http\Controllers\Chat;

use App\Chat;
use App\ChatUser;
use App\Conversation;
use App\Http\Controllers\Controller;
use App\Models\Property;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Services\Comms\CommsService;
use App\Services\Chat\ChatService;

class ConversationController extends Controller
{
    private $commsService;
    private $chatService;
    public function __construct( CommsService $commsService, ChatService $ChatService )
    {
        $this->commsService = $commsService;
        $this->chatService = $ChatService;
    }

    public function index(Request $request, $chat_id) {

        if($request->message_id && $request->message_id > 0) {
            $message_id = $request->message_id;
            $conversation = Conversation::where('chat_id', $chat_id)
            ->where('id', '>', $message_id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
         } else {
            $conversation = Conversation::where('chat_id', $chat_id)->latest('id')->get();
            if(!$conversation) {
                return response()->json([]);
            }
         }

        return response()->json($conversation);
    }

    public function show(Request $request, $chat_id) {
        $offset = 0;
        if($request->offset) {
            $offset = $request->offset;
        }
        $conversation = Conversation::where('chat_id', $chat_id)
        ->orderBy('created_at', 'desc')
        ->offset($offset)
        ->limit(50)
        ->get();

        return response()->json($conversation);
    }

    public function create(Request $request)
    {
        $user = Auth::user();
        $message = $request->message;
        $chat_id = $request->id;
        $chat = Chat::find($chat_id);
        
        if(!$request->file('attachment')) {
            $validatedData = $request->validate([
                'message' => 'required',
            ],
            [ 'message.required' => 'The :attribute is required.']);
        }
        
        $conversation = new Conversation ();
        
        $conversation->chat_id = $chat_id;
        $conversation->user_id = Auth::user()->id;
        $conversation->user->name = Auth::user()->name;

        if($request->file('attachment')) {
            $f = $request->file('attachment');
            $allowed_mimes = [
                'image/png',
                'image/jpeg',
                'image/bmp',
                'image/webp',
                'application/msword', // Microsoft Word (.doc)
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // Microsoft Word (.docx)
                'application/vnd.ms-excel', // Microsoft Excel (.xls)
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // Microsoft Excel (.xlsx)
                'application/pdf', // PDF (.pdf)
                'text/csv', // Comma-separated values (.csv)
                'text/plain', // Text (.txt)
            ];
            $allowed_mimes = implode(',', $allowed_mimes);
            
            $validatedData = $request->validate([
                'attachment' => 'max:6000|mimetypes:' . $allowed_mimes,
            ]);
            
            $file = $request->file('attachment');
            $size = $file->getSize();
            $file_name = $file->getClientOriginalName();
            
            $attachment_type = $file->getMimeType();
            $attachment_type = explode("/", $attachment_type);
                        
            // $path = $request->file('attachment')->store('documents', 's3');
            $path = Storage::disk('s3')->putFileAs('/documents',$request->file('attachment'),$file_name ,'private');
            
            $conversation->attachment = $file_name;
            $conversation->attachment_type = $attachment_type[0];
            $conversation->size = $size / 1000;
        }

        if($request->has('message')) {
            $conversation->message = $message;
        }

        $saved = $conversation->save();
        if($saved) {
            $group = ChatUser::where('chat_id', $chat_id)->get();
            foreach($group as $receiver) {
                if($receiver->user_id == $user->id) {
                    ChatUser::where('id',$receiver->id)->update(['is_read' => 1]);
                } else {
                    ChatUser::where('id',$receiver->id)->update(['is_read' => 0]);
                }
            }
        }
        
        return response()->json($conversation);
    }
}
