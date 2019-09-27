<?php

namespace App\Http\Controllers\Api;

use App\Configure;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ExpressNumberController extends Controller
{
    //快递账号配置
    public function theList(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }


        $list = DB::table('erp_express_numbers')
            -> leftJoin('erp_business','erp_express_numbers.business_id','erp_business.id')
            -> select([
                'erp_express_numbers.*',
                'erp_business.name as business_unit'
            ])
            -> where([
                'erp_express_numbers.business_id' => $request -> business_id
            ])
            -> paginate(isset($request -> per_page)?$request -> per_page:20);



        $config = Configure::getExpressNumbers();
        foreach($list as $k => $vo){
            $list[$k] -> express_type_str = $config[$vo -> express_type];
        }

        return $list;
    }

    //快递账号info
    public function info(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
            'id' => 'required|numeric'
        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }

        $info = DB::table('erp_express_numbers')
            -> where([
                'id' => $request -> id
            ]) -> first();

        $config = Configure::getExpressNumbers();
        $info -> express_type_str = $config[$info -> express_type];

        return [
            'code' =>200,
            'data' => $info
        ];
    }

    //快递账号新增
    public function add(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
            'user_name' => 'required',
            'app_key' => 'required',
            'customer_name' => 'required',
            'express_type' => 'required',
        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }

        $number_info = DB::table('erp_express_numbers')
            -> where([
                'business_id' => $request -> business_id,
                'express_type' => $request -> express_type
            ]) -> first();
        if($number_info){
            return [
                'code' => 500,
                'msg' => '此事业部，已经存在该类型快递账号'
            ];
        }


        DB::table('erp_express_numbers')
            -> insertGetId([
                'user_name' => $request -> user_name,
                'express_type' => $request -> express_type,
                'app_key' => $request -> app_key,
                'customer_name' => $request -> customer_name,
                'business_id' => $request -> business_id,
                'created_at' => time(),
                'updated_at' => time(),
            ]);

        return [
            'code' => 200,
            'msg' => '新增成功'
        ];



    }

    //快递账号修改
    public function edit(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
            'id' => 'required|numeric',
            'user_name' => 'required',
            'app_key' => 'required',
            'customer_name' => 'required',
            'express_type' => 'required',
        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }

        DB::table('erp_express_numbers')
            -> where([
                'id' => $request -> id
            ]) -> update([
                'user_name' => $request -> user_name,
                'app_key' => $request -> app_key,
                'customer_name' => $request -> customer_name,
                'express_type' => $request -> express_type,
                'updated_at' => time()
            ]);

        return [
            'code' => 200,
            'msg' => '修改成功'
        ];
    }

}
