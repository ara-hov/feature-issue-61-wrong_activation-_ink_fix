<?php

namespace App\Repositories\Comms;

use Illuminate\Support\Facades\Auth;
use Twilio\Exceptions\ConfigurationException;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;
use Illuminate\Support\Facades\Mail;

use App\Models\Opt;
use App\Models\Invitation;
use App\Models\UsersReferrals;
use App\Models\SystemNotificatios;
use App\User;
use Exception;

class CommsRepository implements CommsInterface
{
    private $twilioAccountSid, $twilioAuthToken, $masterNumber, $userAuth;

    public function __construct(
        Auth $userAuth
    )
    {
        $this->userAuth = $userAuth;
        $this->twilioAccountSid = 'AC3cd85c3463e4732278f437ed182d19f4';
        $this->twilioAuthToken = 'f72288245e924ba51f9d7e3512bc9cf0';
        $this->masterNumber = '+12182261187';
    }

    public function askCommsToSendSms($mobileNumber)
    {
        $userId = Auth::id();
        $opt = new Opt();
        $numberCode = rand(10, 199999);
        try {
            $client = new Client($this->twilioAccountSid, $this->twilioAuthToken);
        } catch (ConfigurationException $e) {
            return $e->getMessage();
        }

        try {
            $message = $client->messages->create(
                $mobileNumber, // Text this number
                [
                    'from' => $this->masterNumber, // From a valid Twilio number
                    'body' => 'Your Reverifi verification code is: ' . $numberCode
                ]
            );
            if ($message->sid) {
                $opt->user_id = $userId;
                $opt->auth_code = $numberCode;
                $opt->save();
                return $opt;
            }

        } catch (TwilioException $e) {
            return $e->getMessage();
        }
    }

    public function askCommsToVerifyOtp($code)
    {
        $userId = Auth::id();
        $codes = Opt::where('user_id', $userId)->pluck('auth_code')->last();
        if ($codes == $code) {
            User::where('id', $userId)->update(['mobile_verified' => 1]);
            return true;
        }
        return false;
    }

    public function askCommsToSaveInvitations($data)
    {
        return Invitation::insert($data);
    }

    public function askCommsToSaveUserReferrals($data)
    {
        return UsersReferrals::insert($data);
    }

    public function askCommsToCheckDuplicateInvitations($data){
        return Invitation::where($data)->get()->toArray();
    }

    public function askCommsToSendEmail($to)
    {
        try {
            Mail::raw('Testing Email From TrisecIo', function ($message) use ($to) {
                $message->to($to);
            });
        } catch (Exception $ex) {
            throw new Exception($ex);
        }
    }

    public function askCommsToSendNotifications($userId, $notificationText, $notificationType, $property_id = null, $receiver = null)
    {
        $data = ['user_id'=>$userId, 'notification_text'=>$notificationText, 'notification_type'=>$notificationType];

        if($property_id != null && !is_null($property_id)) {
            $data['property_id'] = $property_id;
        }

        if($receiver != null && !is_null($receiver)) {
            $data['receiver'] = $receiver;
        }

        return SystemNotificatios::insert($data);
    }

    public function askCommsToGetUserNotifications($userId)
    {
        return SystemNotificatios::where('user_id', $userId)->orderBy('id', 'desc')->skip(0)->take(20)->get();
    }

    public function askCommsToUpdateUserNotifications($userId)
    {
        return SystemNotificatios::where('user_id', $userId)->update(['status' => 1]);
    }

    public function getUser($id)
    {
        return User::select('id')->where('id', $id)->first();
    }
}
