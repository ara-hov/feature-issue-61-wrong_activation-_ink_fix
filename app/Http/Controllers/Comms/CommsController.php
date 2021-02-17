<?php

namespace App\Http\Controllers\Comms;

use App\Chat;
use App\Http\Controllers\Controller;
use App\Models\BuyingRoomProgress;
use App\Services\Comms\CommsService;
use App\Services\Aggregate\AggregateService;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Log;
use App\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class CommsController extends Controller
{
    private $commsService;
    private $aggService;

    public function __construct(
        CommsService $commsService,
        AggregateService $aggService
    )
    {
        $this->commsService = $commsService;
        $this->aggService = $aggService;
    }

    public function verifyUserMobileNumber(Request $request)
    {
        Log::info('Requesting to service to verify user Mobile Number');

        $params = $request->all();
        return $this->commsService->mobilNumberVerificationResponse($params['mobileNumber']);
    }

    public function verifyUserMobileOtp(Request $request)
    {
        Log::info('Requesting to service to verify user Mobile Number');

        $params = $request->all();
        return $this->commsService->mobilNumberOtpVerificationResponse($params['otpCode']);
    }

    public function sendInvitaion(Request $request)
    {
        Log::info('Requesting to service to verify user Mobile Number');

        $params = $request->all();
        return $this->commsService->sendInvitaionResponse($params);
    }

    public function sendPropertyInvitaion(Request $request)
    {
        Log::info('Requesting to service to verify user Mobile Number');

        $params = $request->all();
        return $this->commsService->sendPropertyInvitaionResponse($params);
    }

    public function sendReferrals(Request $request)
    {
        Log::info('Requesting to service to send the referrals');

        $params = $request->all();
        return $this->commsService->sendReferralsResponse($params);
    }

    public function getuserNotifications()
    {
        return $this->commsService->userNotificationsResponse();
    }

    public function updateUserNotifications()
    {
        return $this->commsService->updateUserNotificationsResponse();
    }

    public function adddressAutocomplete(Request $request)
    {
        $validatedData = $request->validate([
            'address' => ['required', 'max:20'],
        ]);

        return $this->aggService->autocompleteAddress($request->address);
    }

    public function dashboardStats()
    {
        return $this->commsService->dashboardStats();
    }

    public function getAttachment($id)
    {
        return $this->commsService->getAttachment($id);
    }

    public function buyingroomDocumentDownload(Request $request, $id)
    {
        $validatedData = $request->validate([
            'module' => ['required'],
        ]);
        return $this->commsService->buyingRoomDocument($id, $request->module);
    }

    public function buyingroomDocument(Request $request, $id)
    {
        $documents = [];

        $progress = BuyingRoomProgress::where('prop_id', $id)->first();
        if ($progress) {
            $progress = json_decode($progress->value);
            $progress = collect($progress);
            $progress = $progress->filter(function ($module) {
                return $module->file_upload && $module->status == true;
            });
            $progress = $progress->all();
            $progress = collect($progress);
            // $progress = $progress->pluck('value', 'module');
            // $progress = $progress->all();
            // dd($progress);
            foreach ($progress as $key => $module) {
                if (isset($module->file_value)) {
                    $documents[] = array(
                        'module' => $module->module,
                        'value' => $module->file_value,
                    );
                } else {
                    $documents[] = array(
                        'module' => $module->module,
                        'value' => $module->value,
                    );
                }
            }
        }

        return response()->json(['success' => true, 'data' => $documents]);
    }
}
