<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\FreightTempName;
use App\FreightTemp;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class FreighttplsController extends Controller
{
    /*
    *   模板管理
    */
    public function get(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|numeric|exists:freight_temp_name,id'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        return FreightTempName::find($request -> id);
    }

    // 运费模板列表
    public function theList(Request $request)
    {
        $tpls = DB::table('freight_temp_name')
            -> where('temp_name','like','%'.trim($request -> name).'%')
            -> orderBy('id','desc')
            -> select([
                '*'
            ]) -> get();
        
        foreach($tpls as $key => $value){
            $tpls[$key]->weight_info = (float)$value->weight_info;
            $tpls[$key]->package_limit = (float)$value->package_limit;
            if ($tpls[$key]->is_weight === 0) {
                $tpls[$key]->is_weight = false;
            }
            if ($tpls[$key]->is_weight === 1) {
                $tpls[$key]->is_weight = true;
            }
        }
        return $tpls;
    }

    public function add(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'temp_name'     => 'required',
            'send_address'  => 'required',
            'company'       => 'required',
            'country'  => 'required|numeric'

        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        DB::table('freight_temp_name')
        -> insertGetId([
            'temp_name'     => $request -> temp_name,
            'send_address'  => $request -> send_address,
            'company'       => $request -> company,
            'country'       => $request -> country,
            'is_weight'     => $request -> is_weight,
            'weight_info'   => $request -> weight_info,
            'package_limit' => $request -> package_limit,
            'created_at'    => time(),
            'updated_at'    => time()
        ]);

        return [
            'code' => 200,
            'msg' => '添加成功'
        ];
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|numeric|exists:freight_temp_name,id',
            'temp_name'     => 'required',
            'send_address'  => 'required',
            'company'       => 'required',
            'country'  => 'required|numeric'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $info = FreightTempName::find($request -> id);

        if(!$info){
            return [
                'code' => 500,
                'msg' => '没有此条数据'
            ];
        }

        FreightTempName::where('id',$request -> id) -> update([
            'temp_name'     => isset($request -> temp_name)?$request -> temp_name:$info -> temp_name,
            'send_address'  => isset($request -> send_address)?$request -> send_address:$info -> send_address,
            'company'       => isset($request -> company)?$request -> company:$info -> company,
            'country'       => isset($request -> country)?$request -> country:$info -> country,
            'is_weight'     => isset($request -> is_weight)?$request -> is_weight:$info -> is_weight,
            'weight_info'   => isset($request -> weight_info)?$request -> weight_info:$info -> weight_info,
            'package_limit' => isset($request -> package_limit)?$request -> package_limit:$info -> package_limit,
            'updated_at'    => time()
        ]);

        return [
            'code' => 200,
            'msg' => '编辑成功'
        ];
    }

    /*
    *   省份详情管理
    */
    public function theManageList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|numeric|exists:freight_temp_name,id',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $manageTpls = FreightTemp::where([
            'temp_name_id' => $request -> id,
            'flag'  =>  0
        ])->orderBy('id','desc')->get();

        foreach($manageTpls as $key => $value){
            $manageTpls[$key]->firstWeight_cost = (float)$value->firstWeight_cost;
            $manageTpls[$key]->secondWeight_cost = (float)$value->secondWeight_cost;
        }
        return $manageTpls;
    }

    public function delTpl(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array|min:1'
        ]);

        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        foreach ($request -> ids as $id) {
            $info = FreightTemp::find($id);
            if($info -> flag){
                continue;
            }

            FreightTemp::where([
                'id' => $id
            ]) -> update([
                'updated_at' => time(),
                'flag' => 1
            ]);
        }

        return [
            'code' => 200,
            'msg' => '删除成功'
        ];
    }

    public function addTpl(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_price'       => 'required|numeric',
            'first_weight'      => 'required|numeric',
            'firstWeight_cost'  => 'required|numeric',
            'secend_price'      => 'required|numeric',
            'secend_weight'     => 'required|numeric',
            'secondWeight_cost' => 'required|numeric',
            'temp_name_id'      => 'required|numeric',
            'address'           => 'required|array|min:1'
        ]);

        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        // 再做一次省份校验
        if ($this->isTplAddressAlready($request->temp_name_id, $request->address)) {
            return new JsonResponse([
                'code'  => 500,
                'msg'   => '你提交的省份已存在！'
            ]);
        }

        //验证首重续重是否为0
        if ($request->first_weight <= 0) {
            return [
                'code' => 500,
                'msg' => '首重小于或等于0'
            ];
        };
        if ($request->secend_weight <= 0) {
            return [
                'code' => 500,
                'msg' => '续重小于或等于0'
            ];
        };

        // 开始插入
        DB::table('freight_temp') -> insertGetId([
            'first_price'       => $request->first_price,
            'first_weight'      => $request->first_weight,
            'firstWeight_cost'  => $request->firstWeight_cost,
            'secend_price'      => $request->secend_price,
            'secend_weight'     => $request->secend_weight,
            'secondWeight_cost' => $request->secondWeight_cost,
            'temp_name_id'      => $request->temp_name_id,
            'address'           => implode(",", $request->address),
            'created_at'        => time(),
            'updated_at'        => time()
        ]);

        return [
            'code' => 200,
            'msg' => '添加成功'
        ];
    }

    public function editTpl(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'                => 'required|numeric|exists:freight_temp,id',
            'temp_name_id'      => 'required|numeric|exists:freight_temp_name,id',
            'first_price'       => 'required|numeric',
            'first_weight'      => 'required|numeric',
            'firstWeight_cost'  => 'required|numeric',
            'secend_price'      => 'required|numeric',
            'secend_weight'     => 'required|numeric',
            'secondWeight_cost' => 'required|numeric'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $info = FreightTemp::find($request -> id);

        if(!$info){
            return [
                'code' => 500,
                'msg' => '没有此条数据'
            ];
        }
        //验证首重续重是否为0
        if ($request->first_weight <= 0) {
            return [
                'code' => 500,
                'msg' => '首重小于或等于0'
            ];
        };
        if ($request->secend_weight <= 0) {
            return [
                'code' => 500,
                'msg' => '续重小于或等于0'
            ];
        };

        FreightTemp::where('id',$request -> id) -> update([
            'first_price'       => isset($request -> first_price)?$request -> first_price:$info -> first_price,
            'first_weight'      => isset($request -> first_weight)?$request -> first_weight:$info -> first_weight,
            'firstWeight_cost'  => isset($request -> firstWeight_cost) ? $request -> firstWeight_cost : $info -> firstWeight_cost,
            'secend_price'      => isset($request -> secend_price)?$request -> secend_price:$info -> secend_price,
            'secend_weight'     => isset($request -> secend_weight)?$request -> secend_weight:$info -> secend_weight,
            'secondWeight_cost' => isset($request -> secondWeight_cost) ? $request -> secondWeight_cost : $info -> secondWeight_cost,
        ]);

        return [
            'code' => 200,
            'msg' => '编辑成功'
        ];
    }

    private function isTplAddressAlready($temp_name_id, $addAddressArr)
    {
        $tpls = DB::table('freight_temp')
            -> where('temp_name_id', $temp_name_id)
            -> where('flag', 0) // 未删除的
            -> orderBy('id','desc')
            -> select([
                '*'
            ]) -> get();
        foreach ($tpls as $key => $tpl) {
            // 该省份已存在
            if (!empty(array_intersect(explode(',',$tpl->address),$addAddressArr))) {
                return true;
            }
        }
        return false;
    }

}