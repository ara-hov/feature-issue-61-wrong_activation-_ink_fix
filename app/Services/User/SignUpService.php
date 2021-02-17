<?php

namespace App\Services\User;

use App\Models\UserRole;
use App\Models\Invitation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Validation\Factory as Validator;
use Illuminate\Contracts\Routing\ResponseFactory as Response;
use App\Repositories\User\UserInterface;
use App\Repositories\UserRoles\UserRolesInterface;
use App\Models\Entity;
use App\Models\UserPreApprovals;
use App\Models\BidNegotiations;
use DB;
use App\User;
use Illuminate\Support\Facades\Storage;
use  App\Services\Comms\CommsService;

class SignUpService
{
    private $validator;
    private $response;
    private $user;
    private $userRole;
    private $invitation;

    public function __construct(
        Validator $validator,
        UserInterface $user,
        UserRolesInterface $userRole,
        Invitation $invitation,
        Response $response
    )
    {
        $this->user = $user;
        $this->response = $response;
        $this->validator = $validator;
        $this->userRole = $userRole;
        $this->invitation = $invitation;
    }

    public function validateUserData($data)
    {
        return $this->validator->make($data, [
            'name' => 'required',
            'last_name' => 'required',
            'mobile_number' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required'
        ]);
    }

    public function signUpResponse($userInfo)
    {
        return $this->signUp($userInfo);
    }

    public function signUp($userInfo)
    {
        $validator = $this->validateUserData($userInfo);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $userInfo['password'] = bcrypt($userInfo['password']);
        $verify_token = $userInfo['verify_token'];
        $userData = $this->user->store($userInfo);

        try {
            $verify_url = env('APP_LOGIN_URL');
            $email_data = array(
                'name' => $userInfo['name'].' '.$userInfo['last_name'],
                'email' => $userInfo['email'],
                // 'link' => 'https://reverifi.trisec.io/'
                'link' => $verify_url.'?verify_token='.$verify_token,
            );
            CommsService::sendMail('Registration', $email_data);
        } catch (\Exception $th) {

        }

        return $userData;
    }

    public function getUserProfile()
    {
        $userId = Auth::id();
        return $this->user->show($userId);
    }

    public function updateUserProfile($request)
    {
        $user = $this->user;
        $id = Auth::id();
        $user->name = $request['name'];
        $user->last_name = $request['last_name'];
        $user->email = $request['email'];
        $user->avatar = $request['avatar'];
        $user->mobile_number = $request['mobile_number'];
        $user->address = $request['address'];
        $user->address2 = $request['address_line_2'];
        $user->country = $request['country'];
        $user->state = $request['state'];
        $user->city = $request['city'];
        $user->zip = $request['zip'];

        $avatar = $request['avatar'];

        if (!empty($avatar)) {
            $avatarFolder = '/uploads/profile_image';
            if (!Storage::exists($avatarFolder)) {
                Storage::disk('public')->makeDirectory($avatarFolder, 0777);
            }
            $fileName = Storage::disk('public')->put($avatarFolder, $avatar);
            $avatar_image = $avatar->hashName();
        }

        $userdata = [
            'name' => $user->name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'avatar' => $fileName,
            'mobile_number' => $user->mobile_number,
            'address' => $user->address,
            'address2' => $user->address2,
            'country' => $user->country,
            'state' => $user->state,
            'city' => $user->city,
            'zip' => $user->zip,
        ];
        if (!empty($request['confirmPassword'])) {
            $userdata['password'] = bcrypt($request['confirmPassword']);
        }
        $userUpdated = DB::table('users')
            ->where('id', $id)
            ->update($userdata);

        return response()->json($userUpdated, 201);
    }

    public function updateUserRole($request)
    {
        $id = Auth::id();
        $role = $request['role_id'];

        //Manage UserRoles table
        $userRoles = $this->user->getUserRoles($id, $role);
        $data = array_filter($userRoles, function ($item) use ($role) {
            return $item["role_id"] === $role;
        });

        if (empty($data)) {
            $data = array('user_id' => $id, 'role_id' => $role);
            $this->userRole->store($data);
        }

        $userdata = ['role_id' => $role];
        $userUpdated = DB::table('users')
            ->where('id', $id)
            ->update($userdata);

        return $userUpdated;
    }

    public function getUserByRoleResponse($roleId)
    {
        $users = [];
        $userId = Auth::id();
        $users = $this->user->getUserLendsRealters($roleId, $userId);
        foreach ($users as $key => $val) {
            $val['pre_approvals'] = UserPreApprovals::where(['lender_id' => $val->id, 'user_id' => $userId])->get();
        }
        return $users;
    }

    public function userProfile($id)
    {
        return $this->user->show($id);
    }

    public function getSinglePropertyResponse($propId)
    {
        $data = Entity::where('id', $propId)->get();
        if (!$data->isEmpty()) {
            $user = User::select('name', 'last_name')->find($data[0]->propertyAssociation[0]->realtor_id);
            if ($user) {
                $data[0]['user'] = $user;
            }
        }
        return $data;
    }

    public function getBidNegotiationsResponse($propId)
    {
        $userId = Auth::user()->id;
        $bidSubmission = [
            'propDetails' => [],
            'lender' => [],
            'realtor' => [],
            'bidNegotiation' => []
        ];

        $bidSubmission['propDetails'] = $this->getSinglePropertyResponse($propId);
        $bidSubmission['realtor'] = $this->getUserByRoleResponse(2);
        $bidSubmission['lender'] = $this->getUserByRoleResponse(3);

        $resp = BidNegotiations::with(['bid', 'bidAssociation'])->where([
            'prop_id' => $propId,
            'user_id' => $userId,
            'status' => 0
        ])->get()->toArray();
        $bidSubmission['bidNegotiation'] = $resp;

        return $bidSubmission;
    }
}
