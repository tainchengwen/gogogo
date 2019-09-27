<?php

namespace App\Http\Controllers\ApiMpV1;

use App\ReturnShop;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ReturnPointController extends Controller
{
    //返点配置
    public function returnPointSetting(){
        $shops = DB::table('return_shops') -> where([
            'flag' => 0
        ]) -> get();
        $money_type_config = config('admin.money_type');
        foreach($shops as $k => $vo){
            $shops[$k] -> money_type_str = $money_type_config[$vo -> money_type];
        }

        $arr = [];
        foreach($shops as $k => $vo){
            $arr[$vo -> city_id][$k] = $vo;
        }


        $arr_temp = [];
        foreach($arr as $k => $vo){
            $temp = [];
            //return $vo;
            $temp['city_id'] = $k;
            if($k == 1){
                $temp['city_name'] = '中国';
                $temp['city_img'] = url('return/img/zhongguo.png');
            }elseif($k == 3){
                $temp['city_name'] = '日本';
                $temp['city_img'] = url('return/img/riben.png');
            }
            $temp['shop_ids'] = $vo;

            $arr_temp[] = $temp;
        }

        return $arr_temp;
    }

    //返点提交
    public function subReturnPoint(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:mp_users,id',
            'shop_id' => 'required|numeric',
            'price' => 'required|numeric',
            'numbers' => 'required',  //凭证编号
            'photo' => 'required',

        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }


        $shop_info = ReturnShop::find($request -> shop_id);
        if(!$shop_info){
            return [
                'code' => 500,
                'msg' => '没有此商铺'
            ];
        }

        $image = $request -> photo;
        if (strstr($image,",")){
            $image = explode(',',$image);
            $image = $image[1];
        }

        $destinationPath = public_path('uploads/return');
        $extension = 'png';
        $fileName = str_random(10).time().'.'.$extension;

        $res = file_put_contents($destinationPath.'/'.$fileName,base64_decode($image));

        //新增
        DB::table('return_point') -> insertGetId([
            'shop_id' => $request -> shop_id,
            //实付款
            'price' => round(floatval($request -> price),2),
            //应返款
            'fan_price' => round(floatval($shop_info -> bili) * floatval($request -> price) * 0.01,2) ,
            'numbers' => $request -> numbers,
            'image' => url('uploads/return').'/'.$fileName,
            'created_at' => time(),
            'updated_at' => time(),
            'user_id' => $request -> user_id,
        ]);

        return [
            'code' => 200,
            'msg' => '提交成功'
        ];


    }


    public function myReturnPoint(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:mp_users,id',

        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }

        $datas = DB::table('return_point')
            -> leftJoin('return_shops','return_point.shop_id','return_shops.id')
            -> where([
                'return_point.user_id' => $request -> user_id,
                'return_point.flag' => 0
            ])
            -> where(function($query)use($request){
                if($request -> type == 1){
                    $query -> where('status',1);
                }elseif($request -> type == 2){
                    $query -> where('status',0);
                }
            })
            -> select([
                'return_point.*',
                'return_shops.shop_name'
            ])
            -> paginate(20);

        foreach($datas as $k => $vo){
            if($vo -> status == 1){
                $datas[$k]->status_str = '已返';
            }elseif($vo -> status == 2){
                $datas[$k]->status_str = '驳回';
            }else{
                $datas[$k]->status_str = '待返';
            }
            $datas[$k] -> created_at = date('m-d H:i',$vo -> created_at);

        }



        return $datas;


    }

    //返点详情
    public function returnPointDetail(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:mp_users,id',
            'id' => 'required|numeric',

        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }

        $data = DB::table('return_point')
            -> leftJoin('return_shops','return_point.shop_id','return_shops.id')
            -> where([
                'return_point.id' => $request -> id
            ])
            -> select([
                'return_point.*',
                'return_shops.shop_name',
                'return_shops.money_type',
            ])
            -> first();

        if($data -> status == 1){
            $data->status_str = '已返';
        }elseif($data -> status == 2){
            $data->status_str = '驳回';
        }else{
            $data->status_str = '待返';
        }

        $money_type_config = config('admin.money_type');
        $data -> money_type_str = $money_type_config[$data -> money_type];

        return [
            'code' => 200,
            'data' => $data
        ];




    }
}
