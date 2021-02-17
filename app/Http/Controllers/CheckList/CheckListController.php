<?php

namespace App\Http\Controllers\CheckList;

use App\Http\Controllers\Controller;
use App\Models\CheckList;
use App\Models\Entity;
use App\Models\ProcessingBids;
use App\Models\PropertyCheckList;
use App\Services\Comms\CommsService;
use App\Services\Entities\EntitiesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use stdClass;

class CheckListController extends Controller
{


    private $EntitiesService;

    public function __construct(
        EntitiesService $EntitiesService
    )
    {
        $this->EntitiesService = $EntitiesService;
    }


    public function index($id)
    {
        $user_id = Auth::user()->id;

        $resp = $this->EntitiesService->getBidForBuyingRoom($id)[0]['property_bids'];
        $data = collect($resp)->filter(function ($vl) {
            if ($vl['status'] == 1) {
                return $vl;
            }
        });
        $bid = $data->first();

        $associators = [
            '1' => $bid['seller_id'],
            '2' => $bid['buyer_id'],
            '3' => $bid['seller_realtor_id'],
            '4' => $bid['buyer_realtor_id'],
            '5' => $bid['lender_id'],
        ];
        $associatorsRole = array_search($user_id, $associators);


        if (!$associatorsRole) return response()->json(['success' => false, 'error' => 'Forbidden']);

        $data = DB::table('property_check_list')->where(['prop_id' => $id, 'role_id' => $associatorsRole])->first();
        if ($data) {
            $data->check_list = json_decode($data->check_list);
            return response()->json(['success' => true, 'data' => $data]);
        }

        $data = DB::table('check_list')->where(['status' => 1, 'role_id' => $associatorsRole])->first();
        if ($data) {
            $data->check_list = json_decode($data->check_list);
            return response()->json(['success' => true, 'data' => $data]);
        } else {
            return response()->json(['success' => false, 'error' => 'No checklist']);
        }
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $user_id = Auth::user()->id;
        $resp = $this->EntitiesService->getBidForBuyingRoom($id)[0]['property_bids'];
        $data = collect($resp)->filter(function ($vl) {
            if ($vl['status'] == 1) {
                return $vl;
            }
        });

        $bid = $data->first();

        $associators = [
            '1' => $bid['seller_id'],
            '2' => $bid['buyer_id'],
            '3' => $bid['seller_realtor_id'],
            '4' => $bid['buyer_realtor_id'],
            '5' => $bid['lender_id'],
        ];
        $associatorsRole = array_search($user_id, $associators);


        if (!$associatorsRole) return response()->json(['success' => false, 'error' => 'Forbidden']);

        $model = new PropertyCheckList;
        $__model = false;
        $saved = false;
        $data = $model::where(['prop_id' => $id, 'role_id' => $associatorsRole])->first();
        if (!$data) {
            $model = new CheckList;
            $__model = true;
            $data = $model::where(['status' => 1, 'role_id' => $associatorsRole])->first();
        }

        $data->check_list = json_decode($data->check_list);

        if (array_search($request->name, array_column($data->check_list, 'module')) !== false) {
            $key = array_search($request->name, array_column($data->check_list, 'module'));
            $tmp_arr = $data->check_list;
            $tmp_arr[$key]->status = $request->status;
            $tmp_arr[$key]->user_id = Auth::user()->id;

            $data->check_list = json_encode($tmp_arr);
            if ($__model) {
                $insert = new PropertyCheckList;
                $insert->check_list = $data->check_list;
                $insert->prop_id = $id;
                $insert->role_id = $associatorsRole;

                $saved = $insert->save();
            } else {
                $saved = $data->save();
            }
        }

        if ($saved) {
            return response()->json(['success' => true, 'data' => json_decode($data->check_list)]);
        } else {
            return response()->json(['success' => false, 'error' => 'Something went wrong']);
        }

    }
}
