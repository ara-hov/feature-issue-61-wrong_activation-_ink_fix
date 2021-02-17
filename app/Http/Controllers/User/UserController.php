<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\BidAssociations;
use App\Services\User\SignUpService;
use App\Services\Entities\EntitiesService;
use Illuminate\Http\Request;
use DB;
use App\Models\Property;
use App\Models\BusinessProfile;
use App\Models\Invitation;
use App\Models\PropertyAssociations;
use App\Models\BidNegotiations;
use App\Models\Entity;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Models\PasswordReset;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

class UserController extends Controller
{
    private $signUpService;
    private $entitiesService;

    public function __construct(
        SignUpService $signUpService,
        EntitiesService $entitiesService
    )
    {
        $this->signUpService = $signUpService;
        $this->entitiesService = $entitiesService;
    }

    public function signUp(Request $request)
    {

        $userInfo = $request->only(['name', 'last_name', 'email', 'mobile_number', 'password', 'role_id']);
        $checker = Invitation::select('id', 'from', 'role_id')->where('to_email', $userInfo['email'])->get()->toArray();
        $token = Str::random('32');
        // dd($checker);
        $userInfo['verify_token'] = $token;
        $data = $this->signUpService->signUpResponse($userInfo);

        if (!empty($checker)) {
            $fromId = $checker[0]['from'];
            $invitationArray = ['to' => $data->id];

            if ($fromId === 0) {
                $invitationArray = ['from' => $data->id];
            }

            Invitation::where('to_email', $userInfo['email'])
                ->update($invitationArray);
        }
        return $data;
    }

    public function userProfile()
    {
        $profData = $this->signUpService->getUserProfile();
        $id = Auth::user()->id;
        $role_id = Auth::user()->role_id;

        $checker = BusinessProfile::where(['user_id' => $id, 'role_id' => $role_id])->get();
        $data = array('userProfile' => $profData, 'businessProfile' => $checker);
        return response()->json($data);
    }

    public function updateUser(Request $request)
    {
        $params = $request->all();
        $data = $this->signUpService->updateUserProfile($params);
        return response()->json($data);
    }

    public function updateUserRoleType(Request $request)
    {
        $params = $request->all();
        $data = $this->signUpService->updateUserRole($params);
        return response()->json($data);
    }

    public function submitBusinessProfile(Request $request)
    {
        $id = Auth::user()->id;
        $roleId = Auth::user()->role_id;
        $checker = BusinessProfile::select('id')->where(['user_id' => $id, 'role_id' => $roleId])->exists();


        if (!$checker) {
            $bP = new BusinessProfile();
            $bP->user_id = Auth::user()->id;
            $bP->role_id = $request->role_id;
            $bP->office_name = $request->office_name;
            $bP->email = $request->email;
            $bP->address = $request->address;
            $bP->country = $request->country;
            $bP->state = $request->state;
            $bP->city = $request->city;
            $bP->zip = $request->zip;
            $bP->nmls = $request->nmls;
            $bP->license = $request->license;
            $data = $bP->save();
        } else {
            $id = Auth::id();
            $userdata = [
                'office_name' => $request->office_name,
                'email' => $request->email,
                'address' => $request->address,
                'country' => $request->country,
                'state' => $request->state,
                'city' => $request->city,
                'nmls' => $request->nmls,
                'license' => $request->license,
                'zip' => $request->zip
            ];
            $data = BusinessProfile::where('user_id', $id)->update($userdata);
        }
        return response()->json($data);
    }

    public function properties()
    {
        $id = Auth::user()->id;
        $roleId = Auth::user()->role_id;

        if($roleId == 1)
        {
        $invitee_email = Auth::user()->email;
        $invitation_exists = Invitation::orderBy('id', 'DESC')->where(['to_email' => $invitee_email])->first();

        if ( $invitation_exists !== null) {
        $inviter_id = $invitation_exists->to;

        $property_association_exists = PropertyAssociations::orderBy('id', 'DESC')->where(['realtor_id' => $inviter_id,'user_id' => -1])->first();
        if ( $property_association_exists !== null) {
            $property_association_exists->user_id = Auth::user()->id;
            $property_association_exists->save();
        }
        }
        }

        if ($roleId == 2) {
            $resp = PropertyAssociations::with(["userProperty",
                "userProperty.propertyBidsAssociation",
                "userProperty.propertyBidsAssociation.bids",
                "userProperty.propertyBidsAssociation.bidsNegotiations",
                "userProperty.propertyBidsAssociation.user"])->where(['realtor_id' => $id])->get()->toArray();

            $resps = BidAssociations::with(["userProperty",
                "userProperty.propertyBidsAssociation",
                "userProperty.propertyBidsAssociation.bids",
                "userProperty.propertyBidsAssociation.bidsNegotiations",
                "userProperty.propertyBidsAssociation.user"])->where(['realtor_id' => $id])->get()->toArray();
            $mergedData = array_merge($resp, $resps);
            $data = $this->makeDataForPropertyOffers($mergedData);
        } else if ($roleId == 3) {
            $resp = BidAssociations::with(["userProperty",
                "userProperty.propertyBidsAssociation",
                "userProperty.propertyBidsAssociation.bids",
                "userProperty.propertyBidsAssociation.bidsNegotiations",
                "userProperty.propertyBidsAssociation.user"])->where(['lender_id' => $id])->get()->toArray();
            $data = $this->makeDataForPropertyOffers($resp);
        } else {
            $resp = PropertyAssociations::with(["userProperty",
                "userProperty.propertyBidsAssociation",
                "userProperty.propertyBidsAssociation.bids",
                "userProperty.propertyBidsAssociation.bidsNegotiations",
                "userProperty.propertyBidsAssociation.user"])->where(['user_id' => $id])->get()->toArray();

            $data = $this->makeDataForPropertyOffers($resp);
        }

        // For filter and removing duplicat data need to do this circus... ;)
//        if ($roleId == 2 || $roleId == 3) {
//            $tempArray = [];
//            $collectArray = collect($data)->unique('prop_id');
//            foreach ($collectArray as $key => $val) {
//                if ($val['user_property'][0]['status'] === 1 || $roleId != 3) {
//                    array_push($tempArray, $val);
//                }
//            }
//            $data = $tempArray;
//        }

        return response()->json($data);
    }

    public function makeDataForPropertyOffers($data)
    {
        $offerData = [];
        foreach ($data as $key => $val) {
            $val['user_property'][0]['property_bids'] = [];
            $val['user_property'][0]['buyer_bid_count'] = [];
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
                    if (!isset($val['user_property'][0]['buyer_bid_count'][$valb['user_id']])) {
                        $val['user_property'][0]['buyer_bid_count'][$valb['user_id']] = [];
                    }
                    if (!isset($val['user_property'][0]['buyer_bid_count'][$valb['user_id']]['count'])) {
                        $val['user_property'][0]['buyer_bid_count'][$valb['user_id']]['count'] = 0;
                    }

                    $val['user_property'][0]['buyer_bid_count'][$valb['user_id']]['count'] += count($valb['bids']) + count($valb['bids_negotiations']);
                    array_push($val['user_property'][0]['property_bids'], $valbb);
                }
            }
            array_push($offerData, $val);
        }
        return $offerData;
    }

    public function getSingleProperty(Request $request, $propId)
    {
        $data = $this->signUpService->getSinglePropertyResponse($propId);
        return response()->json($data);
    }

    public function getBidNegotiations(Request $request, $propId)
    {
        $resp = $this->signUpService->getBidNegotiationsResponse($propId);

        return response()->json($resp);
    }

    public function getBidHistory(Request $request, $propId)
    {
        $userId = Auth::user()->id;
        $resp = BidAssociations::with(['bids', 'bidsNegotiations'])->where([
            'prop_id' => $propId,
            'user_id' => $userId
        ])->get()->toArray();
        return response()->json($resp);
    }

    public function getUserByRoles(Request $request, $roleId)
    {
        $rep = $this->signUpService->getUserByRoleResponse($roleId);
        return response()->json($rep, 200);
    }

    public function forgotPassword(Request $request)
    {
        $email = $request->only(['email'])['email'];
        $request->validate([
            'email' => "required|email",
        ]);
        $rand = '1234567890';
        $rand = substr(str_shuffle($rand), 0, 6);

        //try {
        Passwordreset::where('email', $request->email)->delete();
        $checkUser = User::where('email', $request->email)->count();

        if ($checkUser) {
            Passwordreset::insert(['email' => $request->email, 'token' => $rand, 'created_at' => Carbon::now()]);
            //try{
            $mail_params['USER_NAME'] = '';
            $mail_params['RESET_CODE'] = $rand;
            $mail_params['RESET_LINK'] = env('APP_URL') . "/reset/$rand";
            $mail_params['APP_NAME'] = env('APP_NAME');
            $this->__sendMail('reset_password', $request->email, $mail_params);
            //}catch(\Exception $e){}
        } else {
            return response()->json(['status' => 'error', 'message' => 'Email not found', 'data' => []], 400);
        }
        //} catch (\Exception $e) {
        //   return response()->json(['status'=>'error','message' => 'Something went wrong','data'=>[]], 400);
        // }
        return response()->json(['status' => 'success', 'message' => 'We have sent you an e-mail containing your password reset code', 'data' => []], 200);
    }

    public function verify(Request $request)
    {
        // dd($request);
        $request->validate([
            'verify_token' => "required|string"
        ]);

        $user = User::where('verify_token', $request->verify_token)->first();
        if (!$user) {
            return response()->json(['status' => 'failed', 'message' => 'Verification link  invalid', 'data' => []], 200);
        }
        if ($user->email_verified_at) {
            $data['message'] = "Email is already verified!";
            return response()->json(['status' => 'failed', 'message' => 'Email is already verified', 'data' => []], 200);
        }

        $user->email_verified_at = Carbon::now()->toDateTimeString();
        $user->save();
        return response()->json(['status' => 'success', 'message' => 'Email is successfully verified!', 'data' => []], 200);
    }


    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => "required|email",
            'reset_code' => 'required|int',
            'new_password' => 'required|min:6',
            'confirm_password' => 'required|same:new_password',
        ]);

        try {
            $checkToken = Passwordreset::where('email', $request->email)->where('token', $request->reset_code)->first();
            $checkUser = User::where('email', $request->email)->count();
            if ($checkToken && $checkUser) {
                $checkUser = User::where('email', $request->email)->update(['password' => bcrypt($request->new_password)]);
                return response()->json(['status' => 'success', 'message' => 'Your password has been updated successfully', 'data' => []], 200);
            } else {
                return response()->json(['status' => 'error', 'message' => 'Email or reset code is invalid', 'data' => []], 400);
            }
        } catch (\Exception $ex) {
            return response()->json(['status' => 'error', 'message' => 'Something went wrong', 'data' => []], 400);
        }
    }

    public function show($id)
    {
        $rep = $this->signUpService->userProfile($id);
        return response()->json($rep, 200);
    }
}
