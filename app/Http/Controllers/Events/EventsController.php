<?php

namespace App\Http\Controllers\Events;

use App\Http\Controllers\Controller;
use App\Models\Entity;
use App\Models\ProcessingBids;
use App\Services\Events\EventsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class EventsController extends Controller
{
    private $eventsService;

    public function __construct(
        EventsService $eventsService
    )
    {
        $this->eventsService = $eventsService;
    }

    public function bidProcessing(Request $request)
    {
        $bidInfo = $request->only(['offer_price', 'loan_type', 'down_payment', 'loan_amount', 'credit_score',
            'bank_balance', 'is_pre_approved', 'file']);

        $bidAssociation = $request->only(['lender', 'bank_name', 'realtor',
            'lender_email', 'lender_phone', 'prop_id']);
        if ($bidInfo['bank_balance'] == 'null') {
            $bidInfo['bank_balance'] = 0;
        }

        return $this->eventsService->bidResponse($bidInfo, $bidAssociation);
    }

    public function updatebidStatus(Request $request)
    {
        $bidInfo = $request->only(['status', 'id', 'prop_id']);

        return $this->eventsService->updatebidStatusResponse($bidInfo);
    }

    public function updatebid(Request $request)
    {
        $bidInfo = $request->only(['id', 'prop_id', 'offer_price']);

        return $this->eventsService->updatebidResponse($bidInfo);
    }

    public function bidNegotiate(Request $request)
    {
        $data = $request->only(['bid_id', 'bid_price', 'negotiating_prices', 'prop_id', 'buyer_id']);
        $this->eventsService->updateBidNegotiations($data);
        return $this->eventsService->bidNegotiateResponse($data);
    }

    public function getSingleBid(Request $request, $bidId)
    {
        return $this->eventsService->getSingleBidResponse($bidId);
    }

    public function cancelDeal(Request $request)
    {
        $data['user_id'] = Auth::user()->id;
        $data['prop_id'] = $request->prop_id;
        $data['reason'] = $request->reason;
        $isSeller = Entity::select('id')->where(['user_id' => $data['user_id'], 'id' => $data['prop_id']])->first();
        $isBuyer = ProcessingBids::select('id')->where(['user_id' => $data['user_id'], 'prop_id' => $data['prop_id']])->first();
        $isLender = ProcessingBids::select('id')->where(['lender' => $data['user_id'], 'prop_id' => $data['prop_id']])->first();

        if (!$isSeller && !$isBuyer && !$isLender) return response()->json(['success' => false, 'error' => 'Forbidden']);

        $data['role_id'] = ($isSeller) ? 1 : 2;

        return $this->eventsService->cancelBid($data);
    }

    public function buyingroomDocumentDownload($id)
    {
        # code...
    }
}
