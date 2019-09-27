<?php

namespace App\Http\Controllers\ApiMpV1;

use App\MallApi;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class InvoiceAddressController extends Controller
{
    //
    public function theList(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:mp_users,id',
        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }

        $address = DB::table('mp_address_invoice')
            -> where([
                'user_id' => $request -> user_id,
                'flag' => 0
            ])  -> get();
        return $address;
    }


    public function addAddress(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:mp_users,id',
            //'province' => 'required',
            //'province_id' => 'required',
            //'city' => 'required',
            //'city_id' => 'required',
            //'country' => 'required',
            //'country_id' => 'required',
            'address' => 'required',
            'name' => 'required',
            'tel' => 'required',
            // card
        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }

        DB::table('mp_address_invoice')
            -> insertGetId([
                'user_id' => $request -> user_id,
                'province' => $request -> province,
                'province_id' => $request -> province_id,
                'city' => $request -> city,
                'city_id' => $request -> city_id,
                'country' => $request -> country,
                'country_id' => $request -> country_id,
                'address' => $request -> address,
                'name' => $request -> name,
                'tel' => $request -> tel,
                'card' => $request -> card,
                'company' => $request -> company,
                'zip_code' => $request -> zip_code,
                'created_at' => time(),
                'updated_at' => time(),
            ]);
        return [
            'code' => 200,
            'msg' => '添加成功'
        ];

    }



    //编辑地址 -> 地址详情
    public function  addressInfo(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:mp_users,id',
            'address_id' => 'required'
        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }

        $address = DB::table('mp_address_invoice')
            -> where([
                'user_id' => $request -> user_id,
                'id' => $request -> address_id,
                'flag' => 0
            ]) -> first();
        if(!$address){
            return [
                'code' => 500,
                'msg' => '没有此数据'
            ];
        }

        return [
            'code' => 200,
            'msg' => $address
        ];

    }

    public function editAddress(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:mp_users,id',
            'address_id' => 'required',
            //'province' => 'required',
            //'province_id' => 'required',
            //'city' => 'required',
            //'city_id' => 'required',
            //'country' => 'required',
            //'country_id' => 'required',
            'address' => 'required',
            'name' => 'required',
            'tel' => 'required',
            //card
        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }

        $address = DB::table('mp_address_invoice')
            -> where([
                'user_id' => $request -> user_id,
                'id' => $request -> address_id,
                'flag' => 0
            ]) -> first();
        if(!$address){
            return [
                'code' => 500,
                'msg' => '没有此数据'
            ];
        }


        DB::table('mp_address_invoice')
            -> where([
                'id' => $request -> address_id
            ])
            -> update([
                'province' => $request -> province,
                'province_id' => $request -> province_id,
                'city' => $request -> city,
                'city_id' => $request -> city_id,
                'country' => $request -> country,
                'country_id' => $request -> country_id,
                'address' => $request -> address,
                'name' => $request -> name,
                'tel' => $request -> tel,
                'card' => $request -> card,
                'company' => $request -> company,
                'zip_code' => $request -> zip_code,
                'updated_at' => time(),
            ]);

        return [
            'code' => 200,
            'msg' => '编辑成功'
        ];



    }


    //地址删除
    public function deleteAddress(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:mp_users,id',
            'address_id' => 'required'
        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }

        $address = DB::table('mp_address_invoice')
            -> where([
                'user_id' => $request -> user_id,
                'id' => $request -> address_id,
                'flag' => 0
            ]) -> first();
        if(!$address){
            return [
                'code' => 500,
                'msg' => '没有此数据'
            ];
        }

        DB::table('mp_address_invoice')
            -> where([
                'id' => $request -> address_id
            ])
            -> update([
                'flag' => 1,
                'updated_at' => time(),
            ]);
        return [
            'code' => 200,
            'msg' => '删除成功'
        ];

    }
}
