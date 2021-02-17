<?php

namespace App\Repositories\User;

use App\Models\ProcessingBids;
use App\Models\BidAssociations;
use App\User;
use App\Repositories\Core\CoreRepository;

class UserRepository extends CoreRepository implements UserInterface
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function getInvitedProperties($userId, $roleId)
    {

        $results = $this->model()->find($userId)->invitions;

        $data = [];
        foreach ($results as $result) {
            if ($result->prop_id !== 0) {
                $result['property']['invitation_status'] = $result->status;
                $data[] = $result->property;
            }
        }
        foreach ($data as $key => $property) {
            $data[$key]['totalBids'] = 0;
            $data[$key]['bidRejected'] = false;
            $data[$key]['buyingRoom'] = false;

            $bids = BidAssociations::with(['bids', 'bidsNegotiations'])->where(['user_id' => $userId, 'prop_id' => $property->id])->get();

            if (!$bids->isEmpty()) {
                $data[$key]['bids'] = $bids;

                //Total bids has been submitted, and there status.
                foreach ($bids as $bkey => $bval) {

                    $data[$key]->bidRejected = false;
                    $data[$key]->buyingRoom = false;

                    if ($bval->bids[0]->status < 0) {
                        $data[$key]->bidRejected = true;
                    } elseif ($bval->bids[0]->status === 1) {
                        $data[$key]->buyingRoom = true;
                    }

                    $data[$key]->totalBids += (count($bval->bids) + count($bval->bidsNegotiations));
                }
            } else {
                $data[$key]['bids'] = false;
            }
        }

        return $data;
        /*
        $results = $this->model()->find($userId)->invitions;
        $data = [];
        foreach ($results as $result) {
            $result['property']['invitation_status'] = $result->status;
            $data[] = $result->property;
        }
        $properties = [];
        foreach ($data as $key => $value) {
            $properties[] = Entity::where('id', $value->id)->with(["propertyBids","propertyBids.user"])->first();
        }

        foreach ($properties as $key => $property) {
            if($property->propertyBids && !empty($property->propertyBids)) {
                $filtered = $property->propertyBids->reject(function ($bid) {
                    $userId = Auth::user()->id;
                    return $bid->user_id != $userId;
                });
                $properties[$key]->bids = $filtered->all();
            }
        }

        return $properties;
        */
    }

    public function getClientList($userId, $roleId)
    {
        $data = [];

        $results = $this->model::with(['invitions' => function ($q) use ($roleId, $userId) {
            $q->where(['role_id' => $roleId]);
        }])->where(['id' => $userId])->get();

        foreach ($results[0]['invitions'] as $result) {
            $temp['client'] = $result->client ? $result->client : [];
            $temp['property'] = $result->property ? $result->property : [];
            if ($roleId === 3) {
                $repChck = $result->userPreApprovalsSubmited;
                if ($repChck) {
                    $temp['invitionsLendersRealters'] = $result->userPreApprovalsSubmited->where(['user_id' => $result->client->id])->get();
                }
            }
            $data[] = $temp;
        }

        return $data;
    }

    public function getUserRoles($userId, $role)
    {
        return $this->model()->find($userId)->userRoles->toArray();

    }

    public function getUserLendsRealters($role, $userId)
    {
        $data = [];
        $userRoles = $this->model::with(
            ["invitionsLendersRealters" => function ($q) use ($role, $userId) {
                $q->where(['role_id' => $role]);
            }])->where(['id' => $userId])->get();

        foreach ($userRoles[0]['invitionsLendersRealters'] as $result) {
            if ($result->userBusinessProfiles) {
                $temp = $result->getUserLendersRealters;
                $temp['business_profiles'] = $result->userBusinessProfiles;
                $data[] = $temp;
            }

        }

        return $data;
    }


}
