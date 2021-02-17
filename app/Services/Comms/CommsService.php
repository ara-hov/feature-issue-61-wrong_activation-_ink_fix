<?php

namespace App\Services\Comms;

use App\Conversation;
use App\EmailTemplate;
use App\Models\BuyingRoomProgress;
use App\Models\Entity;
use App\Models\ProcessingBids;
use App\Models\PropertyAssociations;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Factory as Validator;
use Illuminate\Support\Facades\Auth;
use App\Services\Entities\EntitiesService;
use App\Repositories\Comms\CommsInterface;
use App\Repositories\Comms\CommsRepository;
use App\Models\BusinessProfile;
use App\Repositories\User\UserInterface;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Models\UserPreApprovals;
use App\Models\BidAssociations;
use Ramsey\Collection\Collection;

class CommsService
{
    private $comms;
    private $user;
    private $validator;
    private $EntitiesService;

    public function __construct(
        CommsInterface $comms,
        UserInterface $user,
        Validator $validator,
        EntitiesService $EntitiesService
    )
    {
        $this->comms = $comms;
        $this->user = $user;
        $this->validator = $validator;
        $this->EntitiesService = $EntitiesService;
    }

    public function validateEmail($data)
    {
        $validator = $this->validator->make($data, [
            'email' => 'required|email'
        ]);
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    public function mobilNumberVerificationResponse($mobileNumberInfo)
    {
        try {
            return $this->verifyMobileNumber($mobileNumberInfo);
        } catch (ValidationException $e) {
            return $this->response->validateError($e->errors());
        }
    }

    public function mobilNumberOtpVerificationResponse($code)
    {
        try {
            return $this->verifyMobileNumberOtp($code);
        } catch (ValidationException $e) {
            return $this->response->validateError($e->errors());
        }
    }

    public function userNotificationsResponse()
    {
        $userId = Auth::user()->id;
        $data = $this->comms->askCommsToGetUserNotifications($userId);

        $resp = ['data' => [], 'notifCount' => 0];
        foreach ($data as $key => $val) {
            if ($val->status == 0) {
                $resp['notifCount'] += 1;
            }
            array_push($resp['data'], $val);
        }

        return response()->json($resp, 201);
    }

    public function updateUserNotificationsResponse()
    {
        $userId = Auth::user()->id;
        $data = $this->comms->askCommsToUpdateUserNotifications($userId);
        return response()->json($data, 201);
    }

    public function checkDuplicateInvitations($params)
    {
        return $this->comms->askCommsToCheckDuplicateInvitations($params);
    }

    public function sendReferralsResponse($params)
    {
        $userRoleId = Auth::user()->role_id;
        $userId = Auth::user()->id;
        $userName = Auth::user()->name . ' ' . Auth::user()->last_name;
        $dataTemp = [];

        $email_data = array(
            'subject' => 'referral',
            'name' => $userName,
            'link' => 'https://reverifi.trisec.io/sign-up',
        );

        foreach ($params['invitation'] as $key => $val) {
            try {
                $this->validateEmail(array('email' => $val));
                try {
                    $email_data['email'] = $val;
                    $this->sendMail($email_data['subject'], $email_data);
                    $data = array('user_id' => $userId, 'referral_email' => $val);
                    array_push($dataTemp, $data);

                } catch (ValidationException $e) {
                    return $this->response->validateError($e->errors());
                }

            } catch (ValidationException $e) {
                $data = array('error' => $e->getMessage() . " " . $val);
                return response()->json($data, 422);
            }
        }

        if (!empty($dataTemp)) {
            $invited['data'] = $this->comms->askCommsToSaveUserReferrals($dataTemp);
        }

        if ($invited) {
            return response()->json($invited);
        } else {
            return response()->json(array('success' => false, 'error' => 'Something went wrong'), 422);
        }
    }

    public function sendInvitaionResponse($params)
    {
        $userRoleId = Auth::user()->role_id;
        $userId = Auth::user()->id;
        $userName = Auth::user()->name . ' ' . Auth::user()->last_name;

        $propId = isset($params['propId']) ? $params['propId'] : 0;
        $roleId = isset($params['roleId']) ? $params['roleId'] : 0;

        if ($userRoleId == 2) {
            if ($propId) {
                $prop = PropertyAssociations::where(['prop_id' => $propId])->get()->toArray();
                $userId = $prop[0]['user_id'];
            }
        }

        $dataTemp = [];
        $onceInvited = [];

        /**
         * Setting to send email
         */
        $prop_name = ''; // Variable which will hold value for property name in case it needs to be send in email
        if ($roleId == 1) {
            $subject = 'Lender Invite';
            $prop_name = '';
            if ($propId) {
                $subject = 'Buyer Invite';
                $prop_name = $this->EntitiesService->getPropertyDetails('id', $propId);
                $prop_name = $prop_name[0]->title;
            }

            $email_data = array(
                'subject' => $subject,
                'name' => Auth::user()->name . ' ' . Auth::user()->last_name,
                'property_name' => $prop_name,
                'link' => 'https://reverifi.trisec.io/sign-up',
            );

        } else if ($roleId == 2) {
            $email_data = array(
                'subject' => 'Realtor Invite',
                'link' => 'https://reverifi.trisec.io/sign-up',
            );
        } else if ($roleId == 3) {
            $email_data = array(
                'subject' => 'Lender Invite',
                'name' => Auth::user()->name . ' ' . Auth::user()->last_name,
                'link' => 'https://reverifi.trisec.io/sign-up',
            );
        }

        foreach ($params['invitation'] as $key => $val) {
            try {
                $to = 0;
                $this->validateEmail(array('email' => $val));

                try {
                    $email_data['email'] = $val;
                    $this->sendMail($email_data['subject'], $email_data);

                    $data = $this->user->where('email', $val)->toArray();
                    if (!empty($data)) {
                        $to = $data[0]['id'];
                    }
                    $data = [];

                    if ($userRoleId === 1) {
                        $data = array('from' => $userId, 'to' => $to, 'to_email' => $val, 'prop_id' => $propId, 'role_id' => $roleId);
                    }

                    if ($userRoleId === 2 || $userRoleId === 3) {
                        if ($roleId === 1) {
                            $data = array('from' => $to, 'to' => $userId, 'to_email' => $val, 'prop_id' => $propId, 'role_id' => $userRoleId);
                        }
                    }

                    $invitationChecked = $this->checkDuplicateInvitations($data);
                    if (!empty($invitationChecked)) {
                        array_push($onceInvited, $invitationChecked[0]);
                        continue;
                    }

                    array_push($dataTemp, $data);

                } catch (ValidationException $e) {
                    return $this->response->validateError($e->errors());
                }

            } catch (ValidationException $e) {
                $data = array('error' => $e->getMessage() . " " . $val);
                return response()->json($data, 422);
            }
        }

        $invited['onceInvited'] = $onceInvited;
        $invited['data'] = 0;
        if (!empty($dataTemp)) {
            $invited['data'] = $this->comms->askCommsToSaveInvitations($dataTemp);

            foreach ($dataTemp as $key => $val) {

                if ($val['to'] !== 0 && $val['from'] !== 0) {
                    $to = $val['to'];
                    $text = 'You have been invited to make a bid on a property';
                    $type = 1;

                    if ($val['role_id'] == 3 || $val['role_id'] == 2) {
                        if ($roleId === 1) {
                            $userType = 'client';
                            $to = $val['from'];
                            $type = $roleId;
                            $text = 'You have been invited by ' . $userName . ' to be their ' . $userType;
                        } else {
                            $userType = $val['role_id'] == 3 ? 'lender' : 'realtor';
                            $to = $val['to'];
                            $type = $val['role_id'];
                            $text = 'You have been invited by ' . $userName . ' to be their ' . $userType;
                        }
                    }
                    $this->comms->askCommsToSendNotifications($to, $text, $type);
                }
            }
        }

        if ($invited) {
            return response()->json($invited);
        } else {
            return response()->json(array('success' => false, 'error' => 'Something went wrong'), 422);
        }
    }
    
    public function verifyMobileNumber($mobileNumberInfo)
    {
        return $this->comms->askCommsToSendSms($mobileNumberInfo);
    }

    public function verifyMobileNumberOtp($code)
    {
        return $this->comms->askCommsToVerifyOtp($code);
    }

    public function filterUsersForChat($users_id)
    {
        foreach ($users_id as $key => $user_id) {
            $users[] = $this->comms->getUser($user_id);
        }
        if (!empty($users)) {
            $users = array_filter($users);
            if (count($users) != count($users_id)) {
                echo 123;
                die;
                return false;
            }
            return true;
        } else {
            return false;
        }
    }

    public function autocompleteAddress($address)
    {
        return $this->comms->askCommsToVerifyOtp($address);
    }

    public function dashboardStats()
    {
        $id = Auth::user()->id;
        $roleId = Auth::user()->role_id;

        $data['listed_properties'] = 0;
        $data['offers_received'] = 0;
        $data['accepted_offers'] = 0;
        $data['rejected_offers'] = 0;
        $data['clients'] = 0;
        $data['businessProfile'] = true;
        $data['pre_approved_mortgages'] = 0;
        $data['transactions_in_process'] = [];
        $data['selling'] = [];
        $data['buying'] = [];

        if ($roleId === 1 || $roleId === 2) {
            $resp = $this->EntitiesService->getOffersResponse(true);
            $props = collect($resp);
            if (!$props->isEmpty()) {
                $data['listed_properties'] = count($props);
            }
            foreach ($props as $key => $property) {
                $bids = collect($property['property_bids']);
                if (!$bids->isEmpty()) {
                    $data['offers_received'] += $bids->count();

                    $accepted = $bids->filter(function ($bid) {
                        return $bid['status'] == 1;
                    });

                    $accepted = $accepted->all();
                    $accepted = collect($accepted);
                    $data['accepted_offers'] += $accepted->count();

                    $rejected = $bids->filter(function ($bid) {
                        return $bid['status'] == -1;
                    });

                    $rejected = $rejected->all();
                    $rejected = collect($rejected);
                    $data['rejected_offers'] += $rejected->count();
                }
            }
            $data['buying'] = $this->EntitiesService->getInvitationResponse()->original;
            $data['selling'] = PropertyAssociations::with(['userProperty'])->where(['user_id' => $id])->get();
        }

        if ($roleId === 2) {
            $resp = $this->user->getClientList($id, $roleId);
            $rprops = collect($resp);
            $data['listed_properties'] = count($rprops);
            $data['buying'] = $resp = BidAssociations::with(["userProperty"])->where(['realtor_id' => $id])->get()->toArray();
            $data['selling'] = PropertyAssociations::with([
                "userProperty",
                "userProperty.propertyBidsAssociation",
                "userProperty.propertyBidsAssociation.bids",
                "userProperty.propertyBidsAssociation.bidsNegotiations",
                "userProperty.propertyBidsAssociation.user"
            ])->where(['realtor_id' => $id])->get()->toArray();
            $checker = BusinessProfile::where(['user_id' => $id, 'role_id' => $roleId])->get()->toArray();
            if (empty($checker)) {
                $data['businessProfile'] = false;
            }
            $offerData = $this->EntitiesService->aprepareDataForPropertyAssociations($data['selling'], true);
            $propsd = collect($offerData);
            foreach ($propsd as $key => $property) {
                $bids = collect($property['property_bids']);
                if (!$bids->isEmpty()) {
                    $data['offers_received'] += $bids->count();

                    $accepted = $bids->filter(function ($bid) {
                        return $bid['status'] == 1;
                    });

                    $accepted = $accepted->all();
                    $accepted = collect($accepted);
                    $data['accepted_offers'] += $accepted->count();

                    $rejected = $bids->filter(function ($bid) {
                        return $bid['status'] == -1;
                    });

                    $rejected = $rejected->all();
                    $rejected = collect($rejected);
                    $data['rejected_offers'] += $rejected->count();
                }
            }
        }

        if ($roleId === 3) {
            //$resp = $this->EntitiesService->getClientListResponse();
            $resp = $this->user->getClientList($id, $roleId);
            foreach ($resp as $key => $val) {
                $data['clients']++;
            }

            $data['pre_approved_mortgages'] = UserPreApprovals::where(['lender_id' => $id])->count();
            $buyingRoom = BidAssociations::with(['userProperty'])->where(['lender_id' => $id])->get()->toArray();
            $checker = BusinessProfile::where(['user_id' => $id, 'role_id' => $roleId])->get()->toArray();
            if (empty($checker)) {
                $data['businessProfile'] = false;
            }
            foreach ($buyingRoom as $key => $val) {
                if ($val['user_property'][0]['status'] === 1) {
                    array_push($data['transactions_in_process'], $val['user_property'][0]);
                }
            }
            $tempArray = [];
            $collectArray = collect($data['transactions_in_process'])->unique();
            foreach ($collectArray as $key => $val) {
                array_push($tempArray, $val);
            }
            $data['transactions_in_process'] = $tempArray;
        }

        return response()->json($data);
    }

    public function getAttachment($id)
    {
        $attachment = Conversation::find($id);
        if ($attachment) {
            $adapter = Storage::disk('s3')->getDriver()->getAdapter();
            $command = $adapter->getClient()->getCommand('GetObject', [
                'Bucket' => $adapter->getBucket(),
                'Key' => 'documents/' . $attachment->attachment
            ]);
            $request = $adapter->getClient()->createPresignedRequest($command, '+20 minute');

            return response()->json((string)$request->getUri());
        } else {
            return response()->json(array('error' => 'File doesn\'t exists '));
        }

    }

    public static function sendMail($slug, $data)
    {
        try {
            $template = EmailTemplate::where('title', $slug)->first();

            Mail::send([], [], function ($message) use ($template, $data) {
                $html_template = html_entity_decode($template->template);
                $name = isset($data['name']) ? $data['name'] : '';
                $message->to($data['email'], $name)
                    ->subject($template->subject)
                    ->setBody($template->parse($data, $html_template), 'text/html');
            });
        } catch (\Exception $th) {
            return true;
        }
    }

    public static function isSeller($user_id, $prop_id)
    {
        return Entity::where(['user_id' => $user_id, 'id' => $prop_id])->first();
    }

    public function getData($data = array(), $model, $where = array(), $first = false)
    {
        if ($first) {
            return $model::select($data)->where($where)->first();
        } else {
            return $model::select($data)->where($where)->get();
        }
    }

    public function buyingRoomDocument($id, $module)
    {
        $progress = BuyingRoomProgress::where('prop_id', $id)->first();
        if ($progress) {
            $progress = collect(json_decode($progress->value));

            $progress = $progress->filter(function ($step) use ($module) {
                return $step->module == $module;
            });
            if (!$progress->isEmpty()) {
                $file_name = (isset($progress->first()->file_value)) ? $progress->first()->file_value : $progress->first()->value;

                $adapter = Storage::disk('s3')->getDriver()->getAdapter();
                $command = $adapter->getClient()->getCommand('GetObject', [
                    'Bucket' => $adapter->getBucket(),
                    'Key' => 'buyingroom/' . $id . '/' . $file_name
                ]);
                $request = $adapter->getClient()->createPresignedRequest($command, '+20 minute');
                return response()->json((string)$request->getUri());
            }
        }
    }



    
    public function sendPropertyInvitaionResponse($params)
    {
        $userRoleId = Auth::user()->role_id;
        $userId = Auth::user()->id;
        $userName = Auth::user()->name . ' ' . Auth::user()->last_name;

        $propId = isset($params['propId']) ? $params['propId'] : 0;
        $roleId = isset($params['roleId']) ? $params['roleId'] : 0;

        if ($userRoleId == 2) {
            if ($propId) {
                $prop = PropertyAssociations::where(['prop_id' => $propId])->get()->toArray();
                $userId = $prop[0]['user_id'];
            }
        }

        $dataTemp = [];
        $propertyInvitation = [];
        $onceInvited = [];

        /**
         * Setting to send email
         */
        $prop_name = ''; // Variable which will hold value for property name in case it needs to be send in email
        if ($roleId == 1) {
            $subject = 'Lender Invite';
            $prop_name = '';
            if ($propId) {
                $subject = 'Buyer Invite';
                $prop_name = $this->EntitiesService->getPropertyDetails('id', $propId);
                $prop_name = $prop_name[0]->title;
            }

            $email_data = array(
                'subject' => $subject,
                'name' => Auth::user()->name . ' ' . Auth::user()->last_name,
                'property_name' => $prop_name,
                'link' => 'https://reverifi.trisec.io/sign-up',
            );

        } else if ($roleId == 2) {
            $email_data = array(
                'subject' => 'Realtor Invite',
                'link' => 'https://reverifi.trisec.io/sign-up',
            );
        } else if ($roleId == 3) {
            $email_data = array(
                'subject' => 'Lender Invite',
                'name' => Auth::user()->name . ' ' . Auth::user()->last_name,
                'link' => 'https://reverifi.trisec.io/sign-up',
            );
        }

        foreach ($params['invitation'] as $key => $val) {
            try {
                $to = 0;
                $this->validateEmail(array('email' => $val));

                try {
                    $email_data['email'] = $val;
                    $this->sendMail($email_data['subject'], $email_data);

                    $data = $this->user->where('email', $val)->toArray();
                    if (!empty($data)) {
                        $to = $data[0]['id'];
                    }
                    $data = [];
                    $invitation_data = [];

                    if ($userRoleId === 1) {
                        $data = array('from' => $userId, 'to' => $to, 'to_email' => $val, 'prop_id' => $propId, 'role_id' => $roleId);
                        $invitation_data = array('from' => $to, 'to' => $userId, 'to_email' => $val, 'prop_id' => $propId, 'role_id' => $roleId);
                    }

                    if ($userRoleId === 2 || $userRoleId === 3) {
                        if ($roleId === 1) {
                            $data = array('from' => $to, 'to' => $userId, 'to_email' => $val, 'prop_id' => $propId, 'role_id' => $userRoleId);
                            $invitation_data = array('from' => $userId, 'to' => $to, 'to_email' => $val, 'prop_id' => $propId, 'role_id' => $userRoleId);
                        }
                    }

                    $invitationChecked = $this->checkDuplicateInvitations($invitation_data);
                    if (!empty($invitationChecked)) {
                        array_push($onceInvited, $invitationChecked[0]);
                        continue;
                    }

                    array_push($dataTemp, $data);
                    array_push($propertyInvitation, $invitation_data);

                } catch (ValidationException $e) {
                    return $this->response->validateError($e->errors());
                }

            } catch (ValidationException $e) {
                $data = array('error' => $e->getMessage() . " " . $val);
                return response()->json($data, 422);
            }
        }

        $invited['onceInvited'] = $onceInvited;
        $invited['data'] = 0;
        if (!empty($dataTemp)) {

            $invited['data'] = $this->comms->askCommsToSaveInvitations($propertyInvitation);

            foreach ($dataTemp as $key => $val) {

                if ($val['to'] !== 0 && $val['from'] !== 0) {
                    $to = $val['to'];
                    $text = 'You have been invited to make a bid on a property';
                    $type = 1;

                    if ($val['role_id'] == 3 || $val['role_id'] == 2) {
                        if ($roleId === 1) {
                            $userType = 'client';
                            $to = $val['from'];
                            $type = $roleId;
                            $text = 'You have been invited by ' . $userName . ' to be their ' . $userType;
                        } else {
                            $userType = $val['role_id'] == 3 ? 'lender' : 'realtor';
                            $to = $val['to'];
                            $type = $val['role_id'];
                            $text = 'You have been invited by ' . $userName . ' to be their ' . $userType;
                        }
                    }
                    $this->comms->askCommsToSendNotifications($to, $text, $type);
                }
            }
        }

        if ($invited) {
            return response()->json($invited);
        } else {
            return response()->json(array('success' => false, 'error' => 'Something went wrong'), 422);
        }
    }
}
