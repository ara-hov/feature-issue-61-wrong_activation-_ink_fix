<?php

namespace App\Services\Chat;

use App\Chat;
use App\ChatUser;
use App\Models\PropertyAssociations;
use Illuminate\Validation\ValidationException;
use App\Repositories\Comms\CommsInterface;
use App\Services\Entities\EntitiesService;
use Exception;
use Illuminate\Support\Facades\Auth;

class ChatService
{
    private $comms;

    public function __construct(
        CommsInterface $comms
    )
    {
        $this->comms = $comms;
    }

    public function filterUsersForChat($users_id)
    {
        foreach($users_id as $key => $user_id) {
            $users[] = $this->comms->getUser($user_id);
        }

        if(!empty($users)) {
            $users = array_filter($users);
            if(count($users) != count($users_id)) {
                return false;
            }
            return true;
        } else {
            return false;
        }
    }

    public function createChatForBuyingRoom($request)
    {
        $property_id = $request->property_id;
            $bids = EntitiesService::AccessBuyingRoom($property_id);
            $bids = collect($bids);
            
            $access = $bids->filter(function($bid) {
                return $bid['status'] == 1;
            });
            $access = $access->first();
            $users_id = array(
                $access['buyer_id'],
                $access['buyer_realtor_id'],
                $access['lender_id'],
                $access['seller_id'],
                $access['seller_realtor_id'],
            );
            
            $property = PropertyAssociations::find($property_id);
            
            if(!$property) {
                return response()->json(array('success' => false, 'message' => 'Property doesn\'t exists'));
            }
    
            if(!in_array($property->user_id, $users_id)) {
                return response()->json(array('success' => false, 'message' => 'One or more users not allowed to join Chatroom'));
            }
            
            try {
                $chat = Chat::create([
                    'property_id' => $property_id
                ]);
                $chat->users()->attach($users_id);
                return response()->json($chat);
            } catch (Exception $e) {
                return response()->json($e);
            }
    }

    public function createChatForInbox($request)
    {
        $users_id = array(
            Auth::user()->id,
            $request->user_id
        );
        
        try {
            $chat = Chat::create([
                'property_id' => 0
            ]);
            $chat->users()->attach($users_id);
            return response()->json($chat);
        } catch (Exception $e) {
            return response()->json($e);
        }
    }

    public function userUnreadMessages()
    {
        $user_id = Auth::user()->id;
        $data = [];
        try {
            $group = ChatUser::where(['user_id' => $user_id, 'is_read' => 0])->get();
            if(!$group->isEmpty()) {
                foreach($group as $key => $value) {
                    $chat = [];
                    $chat = $value->chat;
                    $chat = $chat->conversations->last();
                    if($chat) {
                        $data[] = $chat;
                    }
                }
                return response()->json($data);
            } else {
                return response()->json($data);
            }

        } catch (ValidationException $e) {
            return $this->response->validateError($e->errors());
        }
    }

    public function updateChatNotification($chat_id, $user_id)
    {
        try {
            $group = ChatUser::where(['user_id' => $user_id, 'is_read' => 0, 'chat_id' => $chat_id])->first();
            if($group) {
                $group->is_read = 1;
                $saved = $group->save();
                if($saved) {
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }

        } catch (ValidationException $e) {
            return $this->response->validateError($e->errors());
        }
    }
    // public function mobilNumberOtpVerificationResponse($code)
    // {
    //     try {
    //         return $this->verifyMobileNumberOtp($code);
    //     } catch (ValidationException $e) {
    //         return $this->response->validateError($e->errors());
    //     }
    // }

    // public function verifyMobileNumber($mobileNumberInfo)
    // {
    //     return $this->comms->askCommsToSendSms($mobileNumberInfo);
    // }

    // public function verifyMobileNumberOtp($code)
    // {
    //     return $this->comms->askCommsToVerifyOtp($code);
    // }
}
