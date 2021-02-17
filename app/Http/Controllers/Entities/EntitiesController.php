<?php

namespace App\Http\Controllers\Entities;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Services\Entities\EntitiesService;
use Illuminate\Http\Request;
use DB;

class EntitiesController extends Controller
{
    private $EntitiesService;

    public function __construct(
        EntitiesService $EntitiesService
    )
    {
        $this->EntitiesService = $EntitiesService;
    }

    public function submitProperty(Request $request)
    {
        $propertyData = $request->only(['title', 'mls_link', 'price', 'address', 'state', 'city', 'zipcode', 'country', 'media']);
        $realtorId = $request->only(['realtor']);
        $propertyData['title'] = $propertyData['address'];

        return $this->EntitiesService->propertyResponse($propertyData, $realtorId);
    }

    public function submitPreApprovals(Request $request)
    {

        $propertyData = $request->only(['offer_price', 'loan_type', 'loan_amount', 'down_payment', 'loan_amount', 'credit_score',
            'bank_balance', 'total_assets', 'is_pre_approved', 'lender', 'bank_name',
            'lender_email', 'lender_phone', 'file', 'prop_id']);
        $data = $this->EntitiesService->submitPreApprovalsResponse($propertyData);

        return response()->json($data);
    }

    public function getPreApprovals(Request $request, $id)
    {
        $data = $this->EntitiesService->getPreApprovalsResponse($id);
        return response()->json($data);
    }

    public function getInvitation()
    {
        return $this->EntitiesService->getInvitationResponse();
    }

    public function getClientList()
    {
        return $this->EntitiesService->getClientListResponse();
    }

    public function getOfferes()
    {
        return $this->EntitiesService->getOffersResponse();
    }

    public function createBuyingRoom($id)
    {
        return $this->EntitiesService->createBuyingRoom($id);
    }

    public function buyingRoom($id)
    {
        return $this->EntitiesService->getBuyingRoom($id);
    }

    public function buyingRoomProgress(Request $request)
    {
        return $this->EntitiesService->buyingRoomProgress($request);
    }

    public function getBuyingRoomProgress($id)
    {
        return $this->EntitiesService->getBuyingRoomProgress($id);
    }

    public function showBuyingRooms()
    {
        return $this->EntitiesService->showBuyingRooms();
    }

    public function closedDeals()
    {
        return $this->EntitiesService->closedDeals();
    }

}
