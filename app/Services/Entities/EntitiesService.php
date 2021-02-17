<?php

namespace App\Services\Entities;

use App\Chat;
use App\ChatUser;
use App\Models\BuyingRoomProgress;
use App\Models\ProcessingBids;
use App\Models\PropertyAssociations;
use App\Models\BidAssociations;
use App\Models\Entity;
use App\Models\Invitation;
use App\Repositories\Comms\CommsInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Factory as Validator;
use App\Repositories\Entities\EntitiesInterface;
use App\Repositories\User\UserInterface;
use App\Services\Aggregate\AggregateService;
use App\Models\UserPreApprovals;
use App\Services\Comms\CommsService;
use App\User;
use Illuminate\Contracts\Routing\ResponseFactory as Response;
use DB;
use Illuminate\Support\Facades\Validator as Validate;
use Illuminate\Support\Facades\Log;

class EntitiesService
{

    private $validator;
    private $response;
    private $entities;
    private $user;
    private $aggService;
    private $comms;

    public static $progress = array(
        array(
            'module' => 'Initial Contract', 'who' => 'b_realtor', 'pre-requsite' => false, 'file_upload' => true, 'date' => true, 'status' => false,
            'description' => 'Negotiating the terms of the contract including the final price, the estimated closing-date, and any other specifications and contingencies such as appraisals, inspections, repairs etc. '
        ),
        array(
            'module' => 'Signed Initial Contract', 'who' => 's_realtor', 'pre-requsite' => 'Initial Contract', 'file_upload' => true, 'date' => false, 'status' => false,
            'description' => 'The seller signs the pre-contract, which is to agree to the offer in principle.'
        ),
        array(
            'module' => 'Attorney Review Buyer', 'who' => 'b_realtor', 'pre-requsite' => 'Signed Initial Contract', 'file_upload' => false, 'date' => true, 'status' => false,
            'description' => 'At this stage your attorney will review the small fine print to ensure that you are protected during this transaction.'
        ),
        array(
            'module' => 'Attorney Review Seller', 'who' => 's_realtor', 'pre-requsite' => 'Signed Initial Contract', 'file_upload' => false, 'date' => true, 'status' => false,
            'description' => 'At this stage your attorney will review the small fine print to ensure that you are protected during this transaction.'
        ),
        array(
            'module' => 'Attorney Approval Buyer', 'who' => 'buy', 'pre-requsite' => 'Attorney Review Seller', 'file_upload' => false, 'date' => true, 'status' => false,
            'description' => 'The point where your attorney gives their final blessing to the transaction.'
        ),
        array(
            'module' => 'Attorney Approval Seller', 'who' => 'seller', 'pre-requsite' => 'Attorney Review Seller', 'file_upload' => false, 'date' => true, 'status' => false,
            'description' => 'The point where your attorney gives their final blessing to the transaction.'
        ),
        array(
            'module' => 'Inspection Date', 'skip' => false, 'who' => 'buy', 'pre-requsite' => 'Initial Contract', 'file_upload' => false, 'date' => true, 'status' => false,
            'description' => 'This is your opportunity to hire a licensed home inspector to give a full quality evaluation of the homes structure, fixings, and fittings. This provides an assessment of the risks and potential costs.'
        ),
        array(
            'module' => 'Confirm inspection Date or suggest alternative', 'who' => 'seller', 'pre-requsite' => 'Inspection Date', 'file_upload' => false, 'date' => true, 'status' => false
        ),
        array(
            'module' => 'Inspection Results', 'who' => 'buy', 'pre-requsite' => 'Confirm inspection Date or suggest alternative', 'file_upload' => true, 'date' => true, 'status' => false
        ),
        array(
            'module' => 'Suggest Appraisal Date', 'who' => 'buy', 'pre-requsite' => false, 'file_upload' => false, 'date' => true, 'status' => false,
            'description' => 'This is the point at which your lender will hire a federally licensed appraiser to give the final value of the property.'
        ),
        array(
            'module' => 'Confirm appraisal date or suggest alternative', 'who' => 'seller', 'pre-requsite' => 'Suggest Appraisal Date', 'file_upload' => false, 'date' => true, 'status' => false
        ),
        array(
            'module' => 'Upload appraisal report', 'who' => 'buy', 'pre-requsite' => 'Confirm appraisal date or suggest alternative', 'file_upload' => true, 'date' => true, 'status' => false
        ),
        array(
            'module' => 'Title insurance', 'who' => 'buy', 'pre-requsite' => 'Confirm appraisal date or suggest alternative', 'file_upload' => true, 'date' => false, 'status' => false
        ),
        array(
            'module' => 'Mortgage committment', 'who' => 'buy_lender', 'pre-requsite' => 'Confirm appraisal date or suggest alternative', 'file_upload' => true, 'date' => false, 'status' => false
        ),
        array(
            'module' => 'Buyer Cleared to Close', 'who' => 'buy', 'pre-requsite' => false, 'file_upload' => false, 'date' => true, 'status' => false,
            'description' => 'This is where the lender and attorney confirm your mortgage is ready to close and your title insurance is approved.'
        ),
        array(
            'module' => 'Seller Cleared to close', 'who' => 'seller', 'pre-requsite' => false, 'file_upload' => false, 'date' => true, 'status' => false,
            'description' => 'The is the point at which the title for your property is able to be delivered free and clear. This is where your attorney will sign-off on your ability to close.'
        ),
        array(
            'module' => 'Set close Date', 'who' => 'seller', 'pre-requsite' => 'Seller Cleared to close', 'file_upload' => false, 'date' => true, 'status' => false
        ),
        array(
            'module' => 'Buyer Walkthrough', 'who' => 'buy', 'pre-requsite' => 'Set close Date', 'file_upload' => false, 'date' => true, 'status' => false,
            'description' => 'This is where you physically view the property for the final time before you get the keys'
        ),
        array(
            'module' => 'Confirm close date', 'who' => 'lender', 'pre-requsite' => 'Set close Date', 'file_upload' => false, 'date' => true, 'status' => false
        ),
    );

    private $allowed_mimes = [
        'image/png',
        'image/jpeg',
        'image/bmp',
        'image/webp',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/pdf',
        'text/csv',
        'text/plain'
    ];

    public function __construct(
        Validator $validator,
        EntitiesInterface $entities,
        UserInterface $user,
        AggregateService $aggService,
        CommsInterface $comms,
        Response $response
    )
    {
        $this->entities = $entities;
        $this->response = $response;
        $this->user = $user;
        $this->validator = $validator;
        $this->aggService = $aggService;
        $this->comms = $comms;
    }

    public function getPreApprovalsResponse($id)
    {
        return UserPreApprovals::where(['lender_id' => $id])->get();
    }

    public function submitPreApprovalsResponse($propertyData)
    {
        $bP = new UserPreApprovals();
        $bP->lender_id = Auth::user()->id;
        $bP->user_id = $propertyData['prop_id'];
        $bP->offer_price = $propertyData['offer_price'];
        $bP->loan_type = $propertyData['loan_type'];
        $bP->down_payment = $propertyData['down_payment'];
        $bP->credit_score = $propertyData['credit_score'];
        $bP->bank_balance = $propertyData['bank_balance'];
        $bP->total_assets = $propertyData['total_assets'];
        $bP->loan_amount = $propertyData['loan_amount'];

        if ($bP->save()) {
            $text = 'A pre-mortgage approval has been uploaded for you';
            $this->comms->askCommsToSendNotifications($propertyData['prop_id'], $text, 1);
            return $bP;
        } else {
            return array('success' => false, 'data' => null);
        }
    }

    public function propertyResponse($propertyData, $realtorId)
    {
        //Validate
        $__validator = $this->validator->make($propertyData, [
            'title' => 'required'
        ]);
        
        if ($__validator->passes()) {
            $imageName = $this->imageUploader($propertyData);
            $propertyData['media'] = $imageName;
            $data = $this->doSubmitProperty($propertyData, $realtorId);
            return response()->json($data, 201);
        } else {
            return response()->json($__validator->errors(), 422);
        }
    }

    public function doSubmitProperty($propertyData, $realtorId)
    {
        $propertyData = $this->entities->store($propertyData);

        $id = Auth::user()->id;
        $role_id = Auth::user()->role_id;
        $rId = ($role_id == 2) ? $id : $realtorId['realtor'];
        $userId = ($role_id == 2) ? $realtorId['realtor'] : $id;
        $propId = $propertyData->id;
        
        $invitation_exists = Invitation::orderBy('id', 'DESC')->where(['to' => $rId])->first();
        if ($role_id == 2) {
            if ( $invitation_exists !== null) {
        $email_of_invitee = $invitation_exists->to_email;
        Log::info($email_of_invitee);
        $invitee_exists = User::orderBy('id', 'DESC')->where(['email' => $email_of_invitee])->first();
        if ( $invitee_exists !== null) {
            $userId = $invitee_exists->id;
            Log::info($userId);
        }
        }
        }
        
        $propertyAssociations = new PropertyAssociations();
        try {
            $propertyAssociations->prop_id = $propId;
            $propertyAssociations->user_id = $userId;
            $propertyAssociations->realtor_id = $rId;
            $propertyAssociations->save();
        } catch (ValidationException $e) {
            return response()->json(array('success' => false, 'error' => $e->errors()));
        }

        return $propertyData;
    }

    public function base64ImageUploader($images = array())
    {
        $imagesNames = array();
        foreach ($images as $key => $val) {
            $img = $val[0];
            $imageInfo = explode(";base64,", $img);
            $imgExt = str_replace('data:image/', '', $imageInfo[0]);
            $images = str_replace(' ', '+', $imageInfo[1]);
            $imageName = "post-" . time() . "." . $imgExt;

            Storage::put('public/' . $imageName, base64_decode($img));
            array_push($imagesNames, $imageName);
        }
        return $imagesNames[0];

    }

    public function imageUploader($propertyData)
    {
        if ($propertyData['media']->isValid()) {
            $validated = $this->validator->make($propertyData, [
                'media' => 'mimes:jpeg,png,jpg|max:5000',
            ]);
            if ($validated->passes()) {
                $extension = $propertyData['media']->extension();
                $imageName = "prop-" . time() . "." . $extension;
                $propertyData['media']->storeAs('public/', $imageName);
                return $imageName;
            } else {
                return response()->json($validated->errors(), 422);
            }
        }
    }

    public function getInvitationResponse()
    {
        $id = Auth::user()->id;
        $roleId = Auth::user()->role_id;

        $data = $this->user->getInvitedProperties($id, $roleId);
        return response()->json($data, 200);

    }

    public function getClientListResponse()
    {
        $id = Auth::user()->id;
        $roleId = Auth::user()->role_id;

        $data = $this->user->getClientList($id, $roleId);
        return response()->json($data, 200);

    }

    public function aprepareDataForPropertyAssociations($data, $status = false)
    {
        $offerData = [];

        foreach ($data as $key => $val) {
            if ($val['user_property'][0]['property_bids_association'] || $status) {
                $propData['id'] = $val['user_property'][0]['id'];
                $propData['title'] = $val['user_property'][0]['title'];
                $propData['price'] = $val['user_property'][0]['price'];
                $propData['address'] = $val['user_property'][0]['address'];
                $propData['state'] = $val['user_property'][0]['state'];
                $propData['city'] = $val['user_property'][0]['city'];
                $propData['country'] = $val['user_property'][0]['country'];
                $propData['media'] = $val['user_property'][0]['media'];
                $propData['status'] = $val['user_property'][0]['status'];
                $propData['property_bids'] = [];

                foreach ($val['user_property'][0]['property_bids_association'] as $keyb => $valb) {
                    foreach ($valb['bids'] as $keybb => $valbb) {
                        $valbb['user'] = $valb['user'];
                        $valbb['prop_id'] = $valb['prop_id'];
                        $valbb['buyer_id'] = $valb['user_id'];
                        $valbb['buyer_realtor_id'] = $valb['realtor_id'];
                        $valbb['lender_id'] = $valb['lender_id'];
                        $valbb['seller_id'] = $val['user_id'];
                        $valbb['seller_realtor_id'] = $val['realtor_id'];
                        $valbb['negotiation_count'] = count($valb['bids_negotiations']);
                        array_push($propData['property_bids'], $valbb);
                    }
                }
                array_push($offerData, $propData);
            }
        }
        return $offerData;
    }

    public function getOffersResponse($status = false)
    {
        $id = Auth::user()->id;

        $data = PropertyAssociations::with([
            "userProperty",
            "userProperty.propertyBidsAssociation",
            "userProperty.propertyBidsAssociation.bids",
            "userProperty.propertyBidsAssociation.bidsNegotiations",
            "userProperty.propertyBidsAssociation.user"])->where(['user_id' => $id])->get()->toArray();

        $offerData = $this->aprepareDataForPropertyAssociations($data, $status);
        if ($status) {
            return $offerData;
        }
        return response()->json($offerData, 200);

    }

    public function getBidForBuyingRoom($id)
    {
        $data = PropertyAssociations::with([
            "userProperty",
            "userProperty.propertyBidsAssociation",
            "userProperty.propertyBidsAssociation.bids",
            "userProperty.propertyBidsAssociation.bidsNegotiations",
            "userProperty.propertyBidsAssociation.user"])->where(['id' => $id])->get()->toArray();
        $offerData = $this->aprepareDataForPropertyAssociations($data);

        return $offerData;
    }

    public function getBuyingRoom($id, $JSON = true)
    {
        $property = $this->getBidForBuyingRoom($id);

        if ($property) {
            $data = $property[0];
            $data['progress'] = BuyingRoomProgress::where('prop_id', $id)->get();
            $data['progress'] = json_decode($data['progress'][0]->value);
            $chat_room = Chat::where(['property_id' => $id, 'status' => 1])->first();
            $data['chat'] = json_decode($chat_room);
        }
        if ($JSON) {
            if (isset($data['property_bids'][0]['buyer_realtor_id'])) {
                $data['property_bids'][0]['buyer_realtor'] = User::select('name', 'last_name')->where('id', $data['property_bids'][0]['buyer_realtor_id'])->first();
            }

            if (isset($data['property_bids'][0]['seller_id'])) {
                $data['property_bids'][0]['seller'] = User::select('name', 'last_name')->where('id', $data['property_bids'][0]['seller_id'])->first();
            }

            if (isset($data['property_bids'][0]['seller_realtor_id'])) {
                $data['property_bids'][0]['seller_realtor'] = User::select('name', 'last_name')->where('id', $data['property_bids'][0]['seller_realtor_id'])->first();
            }

            if (isset($data['property_bids'][0]['lender_id'])) {
                $data['property_bids'][0]['lender'] = User::select('name', 'last_name')->where('id', $data['property_bids'][0]['lender_id'])->first();
            }
            return $data;
        } else {
            return response()->json($data, 200);
        }

    }

    public function createBuyingRoom($id)
    {
        $buyingRoom = BuyingRoomProgress::where('prop_id', $id)->get();

        if (!$buyingRoom->isEmpty()) {
            return response()->json(array('success' => false, 'error' => 'Room already created for this property'));
        } else {
            $BuyingRoomProgress = new BuyingRoomProgress();
            try {
                $BuyingRoomProgress->prop_id = $id;
                $BuyingRoomProgress->value = json_encode($this::$progress);
                $BuyingRoomProgress->save();
            } catch (ValidationException $e) {
                return response()->json(array('success' => false, 'error' => $e->errors()));
            }
            return response()->json($BuyingRoomProgress, 200);
        }

    }

    public function buyingRoomProgress($request)
    {
        $input = '';
        $id = $request->prop_id;
        $property = $this->getBuyingRoom($id);

        $notificationData['buyer'] = $property['property_bids'][0]['buyer_id'];
        $notificationData['realtor'] = $property['property_bids'][0]['buyer_realtor_id'];
        $notificationData['lender'] = $property['property_bids'][0]['lender_id'];
        $notificationData['seller'] = $property['property_bids'][0]['seller_realtor_id'];
        $notificationData['s_realtor'] = $property['property_bids'][0]['seller_id'];


        if (!$property || empty($property)) {
            return response()->json(array('success' => false, 'error' => 'Property doesn\'t exists ', 200));
        }

        $property = collect($property);
        $property['progress'] = collect($property['progress']);

        $filtered = $property['progress']->filter(function ($name) {
            return $name->status == false;
        });

        if ($filtered->isEmpty()) {
            return response()->json(array('success' => false, 'error' => 'Progress already completed', 200));
        }

        $progress = $filtered->first();
        if ($progress->module == "Inspection Date" && is_null($request->date)) {
            return $this->waiveInspection($progress, $property, $id, $notificationData);
        } else {

            $completed_progress = $progress;

            $allowed_mimes = implode(',', $this->allowed_mimes);
            
            if ($progress->file_upload && $progress->date) {
                $input = 'date_file';
                $validator = Validate::make($request->all(), [
                    'date' => 'required|date_format:Y-m-d|after:tomorrow',
                ]);
                if ($request->file()) {
                    $validator = Validate::make($request->file(), [
                        'file' => 'required|max:6000|mimetypes:' . $allowed_mimes,
                    ], [
                        'file.required' => 'You have to choose the file!',
                        'file.max' => 'Upload limit exceed',
                        'file.mimetypes' => 'Invalid file format',
                    ]);
                }
            } else if ($progress->date && !$progress->file_upload) {
                $input = 'date';
                $validator = Validate::make($request->all(), [
                    'date' => 'required|date_format:Y-m-d',
                ]);
            } else if (!$progress->date && $progress->file_upload) {
                $input = 'file';
                $validator = Validate::make($request->file(), [
                    'file' => 'required|max:6000|mimetypes:' . $allowed_mimes,
                ], [
                    'file.required' => 'You have to choose the file!',
                    'file.max' => 'Upload limit exceed',
                    'file.mimetypes' => 'Invalid file format',
                ]);
            }

            if ($validator->fails()) {
                return response()->json(array('success' => false, 'error' => $validator->errors()), 200);
            }
            if ($input == 'file') {
                $file = $request->file('file');
                $attachment_type = $file->getMimeType();
                $attachment_type = explode("/", $attachment_type);

                // $file_name = time() . "." . $file->getClientOriginalExtension();
                $file_name = $file->getClientOriginalName();

                // $path = $file->storeAs('public/buying_room', $file_name);
                $upload_data = array(
                    'directory' => '/buyingroom\/' . $id,
                    'file' => $file,
                    'file_name' => $file_name,
                    'privacy' => 'private',
                );
                $path = $this->aggService->uploadOnAws($upload_data);

                // $progress->value = asset('storage/buying_room/' . $file_name);
                $progress->value = $file_name;
                $progress->completion_date = date("F j, Y, g:i a");
                $progress->status = true;
            } else if ($input == 'date') {
                $matched = true;

                if ($progress->module == 'Confirm inspection Date or suggest alternative') {
                    $inspection_date = $property['progress']->filter(function ($name) {
                        return $name->module == 'Inspection Date';
                    });
                    $progress = $inspection_date->first();
                    if ($progress->value != $request->date) {
                        $progress->suggest = $request->date;
                        $progress->status = false;
                        $matched = false;
                    } else {
                        $progress = $filtered->first();
                    }
                }
                if ($progress->module == 'Confirm appraisal date or suggest alternative') {
                    $appraisal_date = $property['progress']->filter(function ($name) {
                        return $name->module == 'Suggest Appraisal Date';
                    });
                    $progress = $appraisal_date->first();
                    if ($progress->value != $request->date) {
                        $progress->suggest = $request->date;
                        $progress->status = false;
                        $matched = false;
                    } else {
                        $progress = $filtered->first();
                    }
                }
                if ($progress->module == 'Confirm close date') {
                    $close_date = $property['progress']->filter(function ($name) {
                        return $name->module == 'Set close Date';
                    });
                    $progress = $close_date->first();
                    if ($progress->value != $request->date) {
                        $progress->suggest = $request->date;
                        $progress->status = false;
                        $matched = false;
                    } else {
                        $progress = $filtered->first();
                    }
                }
                if ($matched) {
                    $progress->value = $request->date;
                    $progress->completion_date = date("F j, Y, g:i a");
                    $progress->status = true;
                }
            } else {
                if ($request->file()) {
                    $file = $request->file('file');
                    $attachment_type = $file->getMimeType();
                    $attachment_type = explode("/", $attachment_type);

                    // $file_name = time() . "." . $file->getClientOriginalExtension();
                    $file_name = $file->getClientOriginalName();

                    // $path = $file->storeAs('public/buying_room', $file_name);
                    $upload_data = array(
                        'directory' => '/buyingroom\/' . $id,
                        'file' => $file,
                        'file_name' => $file_name,
                        'privacy' => 'private',
                    );
                    $path = $this->aggService->uploadOnAws($upload_data);
                    $progress->file_value = $file_name;
                }
                if ($progress->module != 'Initial Contract') {
                    if ($request->file()) {
                        $progress->value = $file_name;
                    } else {
                        $progress->value = $request->date;
                    }
                } else {
                    $progress->value = $request->date;
                }

                $progress->completion_date = date("F j, Y, g:i a");
                $progress->status = true;
            }
            
            foreach ($property['progress'] as $key => $value) {
                if ($value->module == $progress->module) {
                    $property['progress'][$key] = $progress;
                }
                if ($progress->module == 'Initial Contract' && $value->module == 'Set close Date') {
                    $property['progress'][$key]->value = $request->date;
                }
            }

            $buyingRoomProgress = BuyingRoomProgress::where('prop_id', $id)->first();
            try {
                $buyingRoomProgress->value = json_encode($property['progress']);
                $buyingRoomProgress->save();
            } catch (ValidationException $e) {
                return response()->json(array('success' => false, 'error' => $e->errors()));
            }

            foreach ($notificationData as $key => $value) {
                $text = Auth::user()->name . ' ' . Auth::user()->last_name . ' has completed progress "' . $completed_progress->module . '"';
                if ($key = 's_realtor') {
                    $key = 'realtor';
                }
                $this->comms->askCommsToSendNotifications($value, $text, 7, $id, $key);
            }

            return response()->json($property, 200);
        }
    }

    public function getPropertyDetails($col, $value)
    {
        return $this->entities->where($col, $value);
    }

    public function getBuyingRoomProgress($id, $JSON = true)
    {
        $property = $this->getBuyingRoom($id);

        if (!$property || empty($property)) {
            return response()->json(array('success' => false, 'error' => 'Property doesn\'t exists ', 200));
        }

        $property = collect($property);
        $property['progress'] = collect($property['progress']);

        $filtered = $property['progress']->filter(function ($name) {
            return $name->status == false;
        });

        $progress = $filtered->first();

        if (!$JSON) {
            return $progress;
        } else {
            return response()->json(array('success' => true, 'data' => $progress));
        }
    }

    public static function AccessBuyingRoom($id)
    {
        $data = PropertyAssociations::with([
            "userProperty",
            "userProperty.propertyBidsAssociation",
            "userProperty.propertyBidsAssociation.bids",
            "userProperty.propertyBidsAssociation.user"])->where(['id' => $id])->get()->toArray();

        $offerData = [];
        foreach ($data as $key => $val) {
            if ($val['user_property'][0]['property_bids_association']) {
                $propData['id'] = $val['user_property'][0]['id'];
                $propData['title'] = $val['user_property'][0]['title'];
                $propData['price'] = $val['user_property'][0]['price'];
                $propData['address'] = $val['user_property'][0]['address'];
                $propData['state'] = $val['user_property'][0]['state'];
                $propData['city'] = $val['user_property'][0]['city'];
                $propData['country'] = $val['user_property'][0]['country'];
                $propData['media'] = $val['user_property'][0]['media'];
                $propData['status'] = $val['user_property'][0]['status'];
                $propData['property_bids'] = [];

                foreach ($val['user_property'][0]['property_bids_association'] as $keyb => $valb) {
                    foreach ($valb['bids'] as $keybb => $valbb) {
                        $valbb['user'] = $valb['user'];
                        $valbb['prop_id'] = $valb['prop_id'];
                        $valbb['buyer_id'] = $valb['user_id'];
                        $valbb['buyer_realtor_id'] = $valb['realtor_id'];
                        $valbb['lender_id'] = $valb['lender_id'];
                        $valbb['seller_id'] = $val['user_id'];
                        $valbb['seller_realtor_id'] = $val['realtor_id'];
                        array_push($propData['property_bids'], $valbb);
                    }
                }
                array_push($offerData, $propData);
            }
        }

        return $offerData[0]['property_bids'];
    }

    public function showBuyingRooms($JSON = true)
    {
        $id = Auth::user()->id;
        $rooms = ChatUser::where('user_id', $id)->get();
        if ($rooms->isEmpty()) {
            return response()->json(array('success' => false, 'data' => [], 'message' => 'No Buying Found !'));
        } else {
            $res = [];
            foreach ($rooms as $key => $room) {
                if ($room->chat->property != null) {
                    array_push($res, $room->chat->property);
                }
            }
            if ($JSON) {
                return response()->json(array('success' => true, 'data' => $res));
            } else {
                return $res;
            }
        }
    }

    public function closedDeals()
    {
        $rooms = $this->showBuyingRooms(false);
        $rooms = collect($rooms);
        $closed_deals = [];
        $a = [];
        foreach ($rooms as $room) {
            if (isset($room->id)) {
                $property = Entity::find($room->id);
                if ($property) {
                    $progress = $property->buyingRoomProgress;
                    if (!$progress->isEmpty()) {
                        if ($progress[0]->status == 1) {
                            $closed_deals[] = $this->getBuyingRoom($room->id);
                        }
                    }
                }
            }
        }
        if (!empty($closed_deals)) {
            return response()->json(array('success' => true, 'data' => $closed_deals));
        } else {
            return response()->json(array('success' => false, 'data' => [], 'message' => 'No closed deals found !'));
        }
    }

    public function waiveInspection($progress, $property, $id, $notificationData)
    {
        foreach ($property['progress'] as $key => $__progress) {
            if ($__progress->module == 'Inspection Date') {
                if (isset($__progress->skip)) {
                    $__progress->skip = true;
                    $__progress->status = true;
                }
            }
            if ($__progress->module == 'Confirm inspection Date or suggest alternative') {
                $__progress->status = true;
            }
            if ($__progress->module == 'Inspection Results') {
                $__progress->status = true;
            }
        }
        
        $buyingRoomProgress = BuyingRoomProgress::where('prop_id', $id)->first();
        try {
            $buyingRoomProgress->value = json_encode($property['progress']);
            $buyingRoomProgress->save();
        } catch (ValidationException $e) {
            return response()->json(array('success' => false, 'error' => $e->errors()));
        }

        foreach ($notificationData as $key => $value) {
            $text = Auth::user()->name . ' ' . Auth::user()->last_name . ' has completed progress "' . $progress->module . '"';
            if ($key = 's_realtor') {
                $key = 'realtor';
            }
            $this->comms->askCommsToSendNotifications($value, $text, 7, $id, $key);
        }

        return response()->json($property, 200);
        // $completed_progress = $progress;
        // $progress->skip = true;
        // $progress->status = true;
    }

}
