<?php

namespace App\Services\Events;

use App\Chat;
use App\Models\BuyingRoomProgress;
use App\Models\CancelledBuyingRoomProgres;
use App\Models\Entity;
use App\Repositories\BidNegotiations\BidNegotiationsInterface;
use  App\Repositories\Events\EventsInterface;
use  App\Repositories\Entities\EntitiesInterface;
use App\Repositories\Comms\CommsInterface;
use App\Repositories\User\UserInterface;
use App\Services\Comms\CommsService;
use App\Services\User\SignUpService;
use App\Models\BidAssociations;
use App\Models\BidNegotiations;

use Illuminate\Support\Facades\Auth;
use App\Models\Invitation;
use App\Models\ProcessingBids;
use App\Models\PropertyAssociations;
use Exception;
use Illuminate\Contracts\Validation\Factory as Validator;
use Illuminate\Contracts\Routing\ResponseFactory as Response;
use Illuminate\Support\Facades\DB;

class EventsService
{
    private $events;
    private $entity;
    private $bidNegotiations;
    private $comms;
    private $validator;
    private $response;
    private $commsService;
    private $userService;
    private $userInterface;

    public function __construct(
        CommsService $commsService,
        SignUpService $userService,
        CommsInterface $comms,
        UserInterface $userInterface,
        EventsInterface $events,
        EntitiesInterface $entity,
        BidNegotiationsInterface $bidNegotiations,
        Validator $validator,
        Response $response
    )
    {
        $this->commsService = $commsService;
        $this->userService = $userService;
        $this->events = $events;
        $this->entity = $entity;
        $this->bidNegotiations = $bidNegotiations;
        $this->comms = $comms;
        $this->validator = $validator;
        $this->response = $response;
        $this->userInterface = $userInterface;
    }

    public function bidResponse($bidInfo, $bidAssociation)
    {
        return $this->processBid($bidInfo, $bidAssociation);
    }

    public function processBid($bidInfo, $bidAssociation)
    {
        $data = $this->userService->getUserByRoleResponse(2);

        if (!$data) {
            $userName = Auth::user()->name . ' ' . Auth::user()->last_name;
            $sellerRealtorData = $this->userService->userProfile($bidAssociation['realtor']);
            $dataTemp = [array('from' => Auth::user()->id, 'to' => $sellerRealtorData->id, 'to_email' => $sellerRealtorData->email, 'prop_id' => 0, 'role_id' => 2)];
            $this->comms->askCommsToSaveInvitations($dataTemp);
            foreach ($dataTemp as $key => $val) {
                if ($val['to'] !== 0) {
                    $to = $val['to'];
                    $text = 'You have been invited to make a bid on a property';
                    $type = 1;

                    if ($val['role_id'] == 3 || $val['role_id'] == 2) {
                        $userType = $val['role_id'] == 3 ? 'lender' : 'realtor';
                        $to = $val['to'];
                        $text = 'You have been invited by ' . $userName . ' to be their ' . $userType;
                        $type = $val['role_id'];
                    }

                    $this->comms->askCommsToSendNotifications($to, $text, $type);
                }
            }
        }

        if ($bidInfo['is_pre_approved'] != 2 && $bidInfo['file'] != 'undefined' && $bidInfo['file']->isValid()) {
            $this->fileUpload($bidInfo);
        }
        if ($bidInfo['is_pre_approved'] == 2) {
            $bidAssociation["lender"] = 0;
        }

        unset($bidInfo["file"]);
        if ($bidAssociation["lender_phone"] == 'undefined') {
            $bidAssociation["lender_phone"] = 0;
        }

        if ($bidInfo['is_pre_approved'] == 1) {
            $data = ['propId' => $bidAssociation['prop_id'], 'roleId' => 3, 'invitation' => []];
            if ($bidAssociation['lender'] > 0) {
                $lender = $this->userInterface->show($bidAssociation['lender'])->toArray();
                $data['invitation'][] = $lender['email'];
            }
        }

        $respNego = BidNegotiations::select('bid_id', 'id')->where([
            'prop_id' => $bidAssociation['prop_id'],
            'user_id' => Auth::user()->id,
            'status' => 0
        ])->get()->toArray();


        // checking for bid rejection
        $bidAssoc = BidAssociations::select('bid_id', 'id')->where([
            'prop_id' => $bidAssociation['prop_id'],
            'user_id' => Auth::user()->id,
            'status' => 0
        ])->orderBy('created_at', 'desc')->first();

        $rejected = false;
        if (!empty($bidAssoc)) {
            $bidId = $bidAssoc->bid_id;
            $bid = $this->events->where('id', $bidId);
            if ($bid[0]->status < 0) {
                $rejected = true;
            }
        }
        /*---rejection check done---*/


        if (!empty($respNego) && !$rejected) {
            $bidInfo['status'] = 0;
            $resp = $this->events->update($respNego[0]['bid_id'], $bidInfo);
            $resp['prop_id'] = $bidAssociation['prop_id'];

            BidNegotiations::where(['id' => $respNego[0]['id']])->update(['status' => 2]);
            Invitation::where(['to' => Auth::user()->id, 'prop_id' => $bidAssociation['prop_id']])->update(['status' => 2]);
        } else {
            $bidInfoResp = $this->events->store($bidInfo);
            $bidAssociationData = [
                'bid_id' => $bidInfoResp->id,
                'prop_id' => $bidAssociation['prop_id'],
                'user_id' => Auth::user()->id,
                'realtor_id' => $bidAssociation['realtor'],
                'lender_id' => $bidAssociation['lender']
            ];
            $resp = BidAssociations::create($bidAssociationData);
            Invitation::where(['to' => $bidAssociationData['user_id'], 'prop_id' => $bidAssociationData['prop_id']])->update(['status' => 2]);
        }


        /**
         * Preparing data to send detail to seller
         */

        try {
            $property = $this->entity->where('id', $bidAssociation['prop_id']);
            $seller_id = $property[0]->propertyAssociation[0]->user_id;
            $seller_email = $this->userInterface->show($seller_id)->email;
            $email_data = array(
                'subject' => 'You have a new offer',
                'link' => 'https://reverifi.trisec.io/offers',
                'email' => $seller_email,
            );
            $this->comms->askCommsToSendNotifications($seller_id, 'A bid has been made on your property', 4);
            $this->commsService->sendMail($email_data['subject'], $email_data);
        } catch (\Throwable $th) {
            return response()->json($resp, 201);
        }

        return response()->json($resp, 201);
    }

    public function fileUpload($data)
    {
        if ($data['file']->isValid()) {
            $extension = $data['file']->extension();
            $imageName = "prop-" . time() . "." . $extension;
            $data['file']->storeAs('public/', $imageName);
            return $imageName;
        }
    }

    public function updatebidStatusResponse($data)
    {
        return $this->updateBid($data);
    }

    public function updatebidResponse($data)
    {

        return $this->updateBidData($data);
    }

    public function getSingleBidResponse($bidId)
    {
        $return = $this->events->where('id', $bidId);
        return response()->json($return, 201);
    }

    public function updateBid($data)
    {
        $prop_id = $data['prop_id'];

        $param = ['status' => $data['status']];
        $return = $this->events->update($data['id'], $param);
        BidNegotiations::where(['bid_id' => $data['id'],'prop_id' => $data['prop_id']])->update(['status' => 1]);
        if ($return) {
            if ($data['status'] == 1) {
                $proId = $data['prop_id'];

                $param = ['status' => 1];
                $this->entity->update($proId, $param);

                $users = BidAssociations::where(['prop_id' => $proId, 'bid_id' => $data['id']])->get();
                $seller = PropertyAssociations::where(['prop_id' => $proId])->get();
                if (!$users->isEmpty() && !$seller->isEmpty()) {
                    $notificationData['buyer'] = $users[0]->user_id;
                    $notificationData['realtor'] = $users[0]->realtor_id;
                    $notificationData['lender'] = $users[0]->lender_id;
                    $notificationData['seller'] = $seller[0]->user_id;
                    $notificationData['s_realtor'] = $seller[0]->realtor_id;

                    foreach ($notificationData as $key => $value) {
                        if ($key == 'buyer') {
                            $text = "Seller has accepted your bid. Click to view Transaction Room";
                        } else if ($key == 'realtor') {
                            $text = "Seller has accepted your bid. Click to view Transaction Room";
                        } else if ($key == 'lender') {
                            $text = "Seller has accepted your bid. Click to view Transaction Room";
                        } else if ($key == 'seller') {
                            $text = "You have successfully accepted bid. Click to view Transaction Room";
                        } else if($key == 's_realtor') {
                            $text = "Seller has successfully accepted bid. Click to view Transaction Room";
                            $key = 'realtor';
                        }
                        $this->comms->askCommsToSendNotifications($value, $text, 6, $proId, $key);
                    }
                }
            }
            if ($data['status'] == 2) {
                $id = $data['id'];
                $proId = $data['prop_id'];
                $param = ['status' => 2];
                $this->entity->update($proId, $param);
            }
        }

        return response()->json($return, 201);
    }

    public function updateBidData($data)
    {
        $previousBid = $this->events->show($data['id'])->toArray();
        if ($previousBid['status'] == 2) {
            $id = $data['id'];
            $offer_price = $data['offer_price'];
            $counter = $previousBid['counter'];
            if ($counter < 3) {
                $counter++;
                if ($offer_price > $previousBid['offer_price']) {
                    $param = ['offer_price' => $offer_price, 'counter' => $counter];
                    $previousBid = $this->events->update($id, $param);
                }
            }
        }
        return response()->json($previousBid, 201);
    }

    public function cancelBid($data = array())
    {
        if (empty($data)) return response()->json(['success' => false, 'error' => 'Forbidden']);

        $property = Entity::find($data['prop_id']);
        if ($property) {
            try {
                DB::beginTransaction();

                $bids = $property->propertyBids->filter(function ($bid) {
                    return $bid->status == 1;
                });

                $bid = $bids->all();
                if (!empty($bid)) {
                    $bid = array_values($bid);
                    $bidDetail = ProcessingBids::find($bid[0]->id);
                    if ($bidDetail) {
                        $bidDetail->status = -1;
                        $bidDetail->save();

                        $buyingroom = BuyingRoomProgress::where('prop_id', $data['prop_id'])->first();
                        if ($buyingroom) {
                            try {
                                $cancelled_buyingroom = new CancelledBuyingRoomProgres();
                                $cancelled_buyingroom->prop_id = $data['prop_id'];
                                $cancelled_buyingroom->value = $buyingroom->value;
                                $cancelled_buyingroom->reason = $data['reason'];
                                $cancelled_buyingroom->cancelled_by = $data['role_id'];

                                $saved = $cancelled_buyingroom->save();

                                if ($saved) {
                                    $buyingroom->delete();

                                    $chat = Chat::where('property_id', $data['prop_id'])->first();
                                    if ($chat) {
                                        $chat->status = 0;
                                        $chat->save();
                                    }
                                }
                            } catch (Exception $e) {
                                DB::rollback();
                                return response()->json(['success' => false, 'error' => 'Something went wrong.']);
                            }
                        }
                    }
                }

                DB::commit();
            } catch (\Throwable $th) {
                DB::rollback();
                return response()->json(['success' => false, 'error' => 'Something went wrong.']);
            }
        }
        return response()->json(['success' => true, 'message' => 'Deal closed.']);
    }

    public function bidNegotiateResponse($data)
    {
        $saveData = [
            'user_id' => $data['buyer_id'],
            'bid_id' => $data['bid_id'],
            'bid_price' => $data['bid_price'],
            'prop_id' => $data['prop_id'],
            'negotiating_prices' => $data['negotiating_prices'],
        ];

        $statusData = ['status' => 2, 'id' => $data['bid_id'], 'prop_id' => $data['prop_id']];
        Invitation::where(['to' => $data['buyer_id'], 'prop_id' => $data['prop_id']])->update(['status' => 1]);
        $this->updateBid($statusData);
        $this->comms->askCommsToSendNotifications($data['buyer_id'], 'A negotiation has been made on your offer', 5);
        return $this->bidNegotiations->store($saveData);
    }

    public function updateBidNegotiations($data)
    {
        BidNegotiations::where([
            'user_id' => $data['buyer_id'],
            'bid_id' => $data['bid_id'],
            'prop_id' => $data['prop_id']
        ])->update(['status' => 1]);
    }
}
