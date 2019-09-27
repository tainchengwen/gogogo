<?php

namespace App\Http\Controllers\ApiMpV1;

use App\Configure;
use App\Repertory;
use App\WxUser;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class InputController extends Controller
{
    //国际物流 上传物流单号图片
    public function uploadRepertoryPhoto(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:mp_users,id',
            'photo' => 'required',
        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
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
        if($res){

            $model = new WxUser();
            $wxuser_id = $model -> getWxUserId($request -> user_id);

            Repertory::insert([
                'user_id' => $wxuser_id,
                'photo' => url('uploads/return').'/'.$fileName,
                'created_at' => time(),
                'updated_at' => time(),
                'admin_user_name' => 'admin',
                'goods_value' => $request -> goods_value,
                'currency' => $request -> currency,
                'is_check' => 1,
                'sub_type' => 1,
                'status' => 6
            ]);

            return [
                'code' => 200,
                'msg' => '上传成功'
            ];

        }else{
            return [
                'code' => 500,
                'msg' => '上传失败'
            ];
        }









    }


    //提交送货上门
    public function submitDoor(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:mp_users,id',
            'send_date' => 'required|date',
            'tel' => 'required|numeric',
            'card' => 'required',
            'mail' => 'required',
            'num' => 'required', //件数/板数
            'weight' => 'required|numeric',
            'package_status' => 'required|numeric',

        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }
        $model = new WxUser();
        $userid = $model -> getWxUserId($request -> user_id);

        $id = Repertory::insertGetId([
            'user_id' => $userid,
            'weight' => $request -> weight,
            'card' => $request -> card,
            'tel' => $request -> tel,
            'mail' => $request -> mail,
            'song_date' => $request -> send_date,
            'package_status' => $request -> package_status,
            'num' => $request -> num,
            'remark' => $request -> remark,
            'created_at' => time(),
            'updated_at' => time(),
            'is_check' => 1,
            'sub_type' => 2,
            'admin_user_name' => 'admin',
            'status' => 6
        ]);


        //更新入仓号
        Repertory::where('id',$id) -> update([
            'canghao' => 'HQ'.$userid.'-'.date('ymd').'-'.sprintf('%06s',$id)
        ]);

        return [
            'code' => 200,
            'msg' => '提交成功'
        ];



    }


    //预约上门打包
    public function subscribeDoor(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:mp_users,id',
            'come_date' => 'required',
            'come_time' => 'required',
            'come_number' => 'required',
            'weight' => 'required',
        ]);
        if ($validator->fails()) {
            return [
                'code' => 500,
                'msg' => '缺少参数'
            ];
        }


        $model = new WxUser();
        $userid = $model -> getWxUserId($request -> user_id);

        $id = Repertory::insertGetId([
            'user_id' => $userid,
            'weight' => $request -> weight,
            'come_date' => $request -> come_date,
            'come_time' => $request -> come_time,
            'come_number' => $request -> come_number,
            'created_at' => time(),
            'updated_at' => time(),
            'is_check' => 1,
            'sub_type' => 5,
            'admin_user_name' => 'admin',
            'status' => 6
        ]);


        //更新入仓号
        Repertory::where('id',$id) -> update([
            'canghao' => 'HQ'.$userid.'-'.date('ymd').'-'.sprintf('%06s',$id)
        ]);

        return [
            'code' => 200,
            'msg' => '提交成功'
        ];



    }


    //安排提货
    public function takeGoods(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:mp_users,id',
            'ti_date' => 'required|date',
            'name' => 'required',
            'tel' => 'required|numeric',
            'address' => 'required',
            'num' => 'required',
            'weight' => 'required|numeric',
            'service_type' => 'required|numeric', //1 2 999

        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }
        $model = new WxUser();
        $userid = $model -> getWxUserId($request -> user_id);

        $id = Repertory::insertGetId([
            'user_id' => $userid,
            'ti_date' => $request -> ti_date,
            'name' => $request -> name,
            'tel' => $request -> tel,
            'address' => $request -> address,
            'num' => $request -> num,
            'weight' => $request -> weight,
            'service_type' => $request -> service_type,
            'remark' => $request -> remark,
            'created_at' => time(),
            'updated_at' => time(),
            'is_check' => 1,
            'sub_type' => 3,
            'admin_user_name' => 'admin',
            'status' => 6
        ]);


        //更新入仓号
        Repertory::where('id',$id) -> update([
            'canghao' => 'HQ'.$userid.'-'.date('ymd').'-'.sprintf('%06s',$id)
        ]);

        return [
            'code' => 200,
            'msg' => '提交成功'
        ];

    }


    public function getCurrencySetting(){
        return Configure::dealArray(Configure::getCurrency());
    }

    //预约国际物流
    public function intLogistics(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:mp_users,id',

            //发货地址
            'send_company' => 'required',
            'send_address' => 'required',
            'send_zipCode' => 'required',
            'send_name' => 'required',
            'send_tel' => 'required',

            //提货地址


            //收货地址
            'receive_company' => 'required',
            'receive_address' => 'required',
            'receive_zipCode' => 'required',
            'receive_name' => 'required',
            'receive_tel' => 'required',

            'currency_type' => 'required', //币种
            'weight' => 'required', //实际重量
            'volume_weight' => 'required', //体积重量
            //'area' => 'required', //面积
            'number' => 'required', //件数/个数






            //'product_json' => 'json', //格式

            /*

            [
                {
                    "product_name":"男士洗面奶",
                    "product_number":"04945845",
                    "product_count":3,
                    "product_price":12.44
                },
                {
                    "product_name":"男士洗面奶",
                    "product_number":"04945845",
                    "product_count":3,
                    "product_price":12.44
                }
            ]

            */
        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }

        $product_json = [];
        if($request -> product_json){
            $product_json = json_decode($request -> product_json);
            if(!count($product_json)){
                return [
                    'code' => 500,
                    'msg' => '提交参数有误'
                ];
            }

            foreach($product_json as $vo){
                if(!isset($vo -> product_name) || !isset($vo -> product_number) || !isset($vo -> product_count) || !isset($vo -> product_price) ){
                    return [
                        'code' => 500,
                        'msg' => '提交参数有误'
                    ];
                }
            }

            foreach($product_json as $vo){
                if(!$vo -> product_name || !$vo -> product_number || !$vo -> product_count || !$vo -> product_price ){
                    return [
                        'code' => 500,
                        'msg' => '提交参数有误'
                    ];
                }
            }
        }



        //invoce存表
        $invoice_id = DB::table('invoice')
            -> insertGetId([
                'mp_user_id' => $request -> user_id,

                'send_company' => $request -> send_company,
                'send_address' => $request -> send_address,
                'send_zipCode' => $request -> send_zipCode,
                'send_name' => $request -> send_name,
                'send_tel' => $request -> send_tel,

                'take_company' => $request -> take_company,
                'take_address' => $request -> take_address,
                'take_zipCode' => $request -> take_zipCode,
                'take_name' => $request -> take_name,
                'take_tel' => $request -> take_tel,


                'receive_company' => $request -> receive_company,
                'receive_address' => $request -> receive_address,
                'receive_zipCode' => $request -> receive_zipCode,
                'receive_name' => $request -> receive_name,
                'receive_tel' => $request -> receive_tel,



                'currency_type' => $request -> currency_type,
                'weight' => $request -> weight,
                'volume_weight' => $request -> volume_weight,
                'area' => $request -> area,
                'number' => $request -> number,
                'created_at' => time(),
                'updated_at' => time(),
            ]);

            if(count($product_json)){
                foreach($product_json as $vo){
                    DB::table('invoice_info')
                        -> insertGetId([
                            'invoice_id' => $invoice_id,
                            'product_name' => $vo->product_name,
                            'product_number' => $vo->product_number,
                            'product_count' => $vo->product_count,
                            'product_price' => $vo->product_price,
                            'created_at' => time(),
                            'updated_at' => time(),
                        ]);
                }
            }


            return [
                'code' => 200,
                'msg' => '提交成功'
            ];









    }


    //我的预报
    public function myInput(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }

        //通过mp_user 查找 wxuser
        $mp_user = DB::table('mp_users')
            -> where([
                'id' => $request -> user_id
            ]) -> first();

        if(!$mp_user){
            return [
                'code' => 500,
                'msg' => '没有此用户'
            ];
        }

        $wxuser = DB::table('wxuser')
            -> where([
                'unionid' => $mp_user -> unionid
            ]) -> first();
        if(!$wxuser){
            return [
                'code' => 500,
                'msg' => '数据有误'
            ];
        }



        //查找他自己的预报
        $data = DB::table('repertory')
            -> where([
                'user_id' => $wxuser -> id,
                'flag' => 0
            ])
            -> select([
                'id',
                'numbers',
                'created_at',
                'company',
                'status',
                'sub_type',
                'is_check',
            ])
            -> where(function($query)use($request){
                if($request -> keywords){
                    $str = str_replace('zp','',trim($request -> keywords));
                    //Log::info($str);
                    $str = str_replace('ZP','',$str);
                    //Log::info($str);
                    $str = intval($str);
                    //Log::info($str);
                    $query -> where('id','like','%'.$str.'%');
                }
            })
            -> orderBy('id','desc')
            -> paginate(20);

        $company = config('admin.repertory_company');
        $status = config('admin.repertory_status');
        $subtype = config('admin.repertory_sub_type');
        foreach($data as $k => $vo){
            $data[$k] -> zp_number = config('admin.repertory_id_prefix').sprintf('%06s',$vo -> id);
            $data[$k] -> company_str = isset($company[$vo -> company])?$company[$vo -> company]:'其他';
            if(!$vo -> is_check){
                //已经审核通过
                $data[$k] -> status_str = isset($status[$vo -> status])?$status[$vo -> status]:'未知';
            }else{
                $data[$k] -> status_str = '待确认';
            }
            if(!$vo -> numbers){
                $data[$k] -> numbers = '暂无';
            }

            $data[$k] -> created_at_str = date('Y-m-d H:i',$vo -> created_at);
            $data[$k] -> sub_type_str = $subtype[$vo -> sub_type];
        }

        return $data;

    }


    //我的预约列表
    public function mySubscribe(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
        ]);
        if ($validator->fails()) {
            return [
                'code' => 500,
                'msg' => '缺少参数'
            ];
        }

        //通过mp_user 查找 wxuser
        $mp_user = DB::table('mp_users')
            -> where([
                'id' => $request -> user_id
            ]) -> first();

        if(!$mp_user){
            return [
                'code' => 500,
                'msg' => '没有此用户'
            ];
        }

        $wxuser = DB::table('wxuser')
            -> where([
                'unionid' => $mp_user -> unionid
            ]) -> first();
        if(!$wxuser){
            return [
                'code' => 500,
                'msg' => '数据有误'
            ];
        }



        //查找他自己的预约
        $data = DB::table('repertory')
            -> where([
                'user_id' => $wxuser -> id,
                'flag' => 0,
                'sub_type' => 5
            ])
            -> where(function($query)use($request){
                if($request -> keywords){
                    $str = str_replace('zp','',trim($request -> keywords));
                    //Log::info($str);
                    $str = str_replace('ZP','',$str);
                    //Log::info($str);
                    $str = intval($str);
                    //Log::info($str);
                    $query -> where('id','like','%'.$str.'%');
                }

                if($request -> is_door == '1'){
                    //上门
                    $query -> where('is_door',1);
                }elseif($request -> is_door == '2'){
                    //未上门
                    $query -> where('is_door',0);
                }

            })
            -> select([
                'id',
                'numbers',
                'created_at',
                'company',
                'status',
                'sub_type',
                'is_check',
                'sub_type',
                'weight'
            ])
            -> orderBy('id','desc')
            -> paginate(20);

        $company = config('admin.repertory_company');
        $status = config('admin.repertory_status');
        $subtype = config('admin.repertory_sub_type');
        foreach($data as $k => $vo){
            $data[$k] -> zp_number = config('admin.repertory_id_prefix').sprintf('%06s',$vo -> id);
            $data[$k] -> company_str = isset($company[$vo -> company])?$company[$vo -> company]:'其他';
            if(!$vo -> is_check){
                //已经审核通过
                $data[$k] -> status_str = isset($status[$vo -> status])?$status[$vo -> status]:'未知';
            }else{
                $data[$k] -> status_str = '待确认';
            }
            if(!$vo -> numbers){
                $data[$k] -> numbers = '暂无';
            }

            $data[$k] -> created_at_str = date('Y-m-d H:i',$vo -> created_at);
            $data[$k] -> sub_type_str = $subtype[$vo -> sub_type];

            //是否上门
            if($vo -> status <> 6 ){
                $data[$k] -> is_door = 1;
            }else{
                $data[$k] -> is_door = 0;
            }
        }

        return $data;
    }



    //我的入仓预报详情
    public function myInputDetail(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'id' => 'required'
        ]);
        if ($validator->fails()) {
            return [
                'code' => 500,
                'msg' => '缺少参数'
            ];
        }


        $model = new WxUser();
        $wxuser_id = $model -> getWxUserId($request -> user_id);
        if(!$wxuser_id){
            return [
                'code' => 500,
                'msg' => '数据有误'
            ];
        }


        $data = DB::table('repertory')
            -> where([
                'repertory.user_id' => $wxuser_id,
                'repertory.flag' => 0,
                'repertory.id' => $request -> id
            ])
            -> select([
                'repertory.numbers',
                'repertory.id',
                'repertory.created_at',
            ])
            -> first();

        if(!$data){
            return [
                'code' => 500,
                'msg' => '没有此数据'
            ];
        }

        $data -> num = config('admin.repertory_id_prefix').sprintf('%06s',$data -> id);;







        $data -> name = "";
        $data -> tel = "";
        $data -> address = "";
        $data -> created_at_str = date('Y-m-d H:i:s',$data -> created_at);

        //按照 mp_temp_package_number de  temp_repertory_id 来连接
        $list = DB::table('mp_temp_package_number')
            -> leftJoin('repertory','mp_temp_package_number.temp_repertory_id','repertory.id')
            -> leftJoin('packages','packages.id','mp_temp_package_number.package_id')
            -> leftJoin('ems_package','mp_temp_package_number.package_id','ems_package.package_id')
            -> leftJoin('order','packages.order_id','order.id')
            -> where([
                'repertory.id' => $request -> id,
                'mp_temp_package_number.flag' => 0
            ])
            -> select([
                'packages.wuliu_num as etk_num',
                'mp_temp_package_number.package_num as mp_package_num',
                'mp_temp_package_number.package_num',
                'mp_temp_package_number.created_at',
                'mp_temp_package_number.remark',
                'mp_temp_package_number.province',
                'mp_temp_package_number.city',
                'mp_temp_package_number.country',
                'mp_temp_package_number.address',
                'mp_temp_package_number.name',
                'mp_temp_package_number.tel',
                'mp_temp_package_number.order_type',
                'mp_temp_package_number.id as temp_package_id',
                'mp_temp_package_number.id',
                'mp_temp_package_number.updated_at',
                'ems_package.ems_status',
                'mp_temp_package_number.order_status',
                'ems_package.taxes',
                'ems_package.id as ems_package_id',
                'order.pay_status'
            ])
            -> get();

        $zong = count($list);
        //已发货
        $yifa = 0;
        //待发货
        $daifa = 0;
        //异常件
        $yichang = 0;
        //放行
        $fangxing = 0;

        if($zong){


            foreach($list as $k => $vo){
                if($vo -> ems_status == 8){
                    $yichang ++;
                }elseif($vo -> ems_status == 2){
                    $fangxing ++;
                }elseif($vo -> ems_package_id){
                    $yifa ++;
                }else{
                    $daifa++;
                }


                $list[$k] -> created_at_str = date('Y-m-d H:i:s',$vo -> created_at);

                if(!$vo -> taxes){
                    $list[$k] -> taxes = 0;
                }

                if($vo -> name && $vo -> remark){
                    $list[$k] -> title = $vo -> name.','.str_limit(trim($vo -> remark),20);
                }elseif($vo -> name){
                    $list[$k] -> title = $vo -> name;
                }elseif($vo -> order_type){
                    $list[$k] -> title = '后台下单';
                }else{
                    $list[$k] -> title = $vo -> package_num;
                }



                //图片
                $photos = DB::table('mp_package_photos')
                    -> where([
                        'package_id' => $vo -> id,
                        'flag' => 0
                    ]) -> first();
                if($photos){
                    $list[$k] -> first_img = url('uploads/mp_photos').'/'.$photos -> image_url;
                }else{
                    $list[$k] -> first_img = '';
                }

                $list[$k] -> time_str = date('Y-m-d H:i',$vo -> updated_at);

                //$list[$k] -> order_status_str = $order_status[$vo -> order_status];


                //判断新的订单状态
                //判断新的订单状态

                if(!$vo -> order_status){
                    //待完善
                    $list[$k] -> button_num = 0;
                }

                if($vo -> ems_status == '8'){
                    //需办理退运 按照异常处理
                    $list[$k] -> order_status_str = '异常件';
                    $list[$k] -> button_num = 4;

                }elseif($vo -> ems_status == '5'){
                    $list[$k] -> order_status_str = '待缴税';
                    $list[$k] -> button_num = 3;
                }elseif($vo -> ems_package_id){
                    $list[$k] -> order_status_str = '已经发货';
                    $list[$k] -> button_num = 2;
                }elseif(!$vo -> pay_status){
                    $list[$k] -> order_status_str = '待付款';
                    $list[$k] -> button_num = 1;
                }else{
                    $list[$k] -> order_status_str = '其他';
                    //$list[$k] -> order_status_str = '其他';
                    $list[$k] -> button_num = 9;
                }
            }
        }

        $data -> repertory_rows = '本次打包共'.$zong.'个包裹、已发货'.$yifa.'个、待发货'.$daifa.'个、异常件'.$yichang.'个（需要修改地址）、放行'.$fangxing.'个包裹';



        return [
            'code' => 200,
            'msg' => 'success',
            'data' => $data,
            'package_info' => $list
        ];







        /*
        $data = DB::table('repertory')
            -> leftJoin('area_scan_order','repertory.id','area_scan_order.repertory_id')
            -> where([
                'repertory.user_id' => $wxuser_id,
                'repertory.flag' => 0,
                'repertory.id' => $request -> id
            ])
            -> select([
                'repertory.numbers',
                'area_scan_order.order_num',
                'area_scan_order.id as order_id',
                'area_scan_order.count_package',
            ])
            -> first();

        if(!$data){
            return [
                'code' => 500,
                'msg' => '没有此数据'
            ];
        }



        $package_info = [];
        if($data -> order_id){
            $package_info = DB::table('area_scan_order_info')
                -> leftJoin('mp_temp_package_number','area_scan_order_info.mp_number_id','mp_temp_package_number.id')
                -> leftJoin('packages','mp_temp_package_number.package_id','packages.id')
                -> select([
                    'mp_temp_package_number.package_num',
                    'mp_temp_package_number.package_id'
                ])
                -> where([
                    'area_scan_order_info.order_id' => $data -> order_id,
                    'area_scan_order_info.flag' => 0
                ]) -> get();

        }else{
            //没有order_id 就直接按照 mp_temp_package_number de  temp_repertory_id 来连接
            $package_info = DB::table('packages')
                -> leftJoin('repertory','packages.temp_repertory_id','repertory.id')
                -> where([
                    'repertory.id' => $request -> id
                ])
                -> select();
        }

        */



        return [
            'code' => 200,
            'msg' => 'success',
            'data' => $data,
            'package_info' => $package_info
        ];



    }




}
