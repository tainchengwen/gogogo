<?php

namespace App\Http\Controllers\ApiMpV1;

use App\Configure;
use App\Jobs\Reptile;
use App\Log;
use App\MpPackageNumber;
use App\MpScanInfo;
use App\Order;
use Egulias\EmailValidator\Validation\DNSCheckValidation;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PackageV2Controller extends Controller
{
    //扫描包裹结果
    public function scanCode(Request $request){
        \Illuminate\Support\Facades\Log::info(print_r($_REQUEST,true));
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:mp_users,id',
            'code' => 'required'
        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }

        //\Illuminate\Support\Facades\Log::info(json_encode($request->all()));

        //先看有没有这个包裹
        $temp_info = DB::table('mp_temp_package_number')
            -> where([
                'package_num' => $request -> code,
                'flag' => 0
            ]) -> first();
        if(!$temp_info){
            return [
                'code' => 500,
                'msg' => '没有此箱子编码'
            ];
        }

        if($temp_info -> user_id){
            if($temp_info -> user_id != $request -> user_id){
                \Illuminate\Support\Facades\Log::info('request_user_id:'.$request -> user_id);
                return [
                    'code' => 500,
                    'msg' => '不属于你的箱子'
                ];
            }else{
                //查下此箱子 属于什么状态， 然后跳转到相应的状态
                //标记此箱子当前的状态。0待完善、 1填写包裹详情、 2货物清单、 3存档照片
                return [
                    'code' => 200,
                    'msg' => '扫描成功',
                    'status' => $temp_info -> status,
                    'package_id' => $temp_info -> id,
                    'order_status' => $temp_info -> order_status
                ];
            }
        }




        //第一扫描成功 绑定数据
        DB::table('mp_temp_package_number')
            -> where([
                'id' => $temp_info -> id
            ]) -> update([
                'is_scan' => 1,
                'user_id' => $request -> user_id,
                'created_at' => time(),
                'updated_at' => time(),
                'status' => 0 //此包裹已经到了添加包裹信息界面
            ]);



        return [
            'code' => 200,
            'msg' => '扫描成功',
            'status' => 0,
            'package_id' => $temp_info -> id,
            'order_status' => 0
        ];
        //临时加
        DB::table('mp_temp_package_number')
            -> where([
                'id' => $temp_info -> id
            ]) -> update([
                'mode_id' => 1,
                'status' => 1
            ]);

        return [
            'code' => 200,
            'msg' => '扫描成功',
            'status' => 1,
            'package_id' => $temp_info -> id,
            'order_status' => 0
        ];


        /*


        */
    }


    //选择发货方式页面
    public function selectMode(){
        $arr = config('admin.mp_mode');

        $temp = [];
        foreach($arr as $k => $vo){
            $temp[] = [
                'id' => $k,
                'value' => $vo
            ];
        }
        \Illuminate\Support\Facades\Log::info(print_r($temp,true));

        return $temp;
    }

    //提交选择发货方式
    public function updateMode(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:mp_users,id',
            'mode_id' => 'required',
        ]);
        if ($validator->fails()) {
            return [
                'code' => 500,
                'msg' => '缺少必要参数'
            ];
        }

        //自动生成包裹
        $package_id = DB::table('mp_temp_package_number') -> insertGetId([
            'package_num' => '临时单号'.date('Ymd').str_random(10),
            'user_id' => $request -> user_id,
            'mode_id' => $request -> mode_id, //绑定发货方式
            'is_scan' => 1,
            'created_at' => time(),
            'updated_at' => time(),
            'flag' => 0,
            'is_temp' => 1,
            'status' => 1,  //此包裹已经到了添加包裹信息界面
        ]);

        return [
            'code' => 200,
            'msg' => '选择发货方式成功',
            'package_id' => $package_id
        ];


    }


    //添加包裹信息页面 区分从哪里进来的。 如果是正常进来的 允许编辑,从详情进来 没有编辑
    public function addPackageInfo(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:mp_users,id',
            'package_id' => 'required'
        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }


        $temp_info = DB::table('mp_temp_package_number')
            -> where([
                'id' => $request -> package_id,
                'flag' => 0,
            ]) -> first();

        /*
        $temp_info = DB::table('mp_temp_package_number')
            -> where([
                'id' => $request -> package_id,
                'flag' => 0,
                'user_id' => $request -> user_id
            ]) -> first();
        if(!$temp_info){
            return [
                'code' => 500,
                'msg' => '没有此箱子编码'
            ];
        }
        */



        $list = DB::table('mp_scan_goods_info')
            -> where([
                'package_id' => $request -> package_id,
                'flag' => 0
            ])
            -> select([
                'id',
                'product_no',
                'product_name',
                'product_img',
                'goods_number',
                'declare_price'
            ])
            ->  get();
        //计算目前的商品总价格
        $price_sum = 0;
        $shuijin = 0;
        foreach($list as $k => $vo){
            $price_sum = intval($vo -> goods_number) * floatval($vo -> declare_price);
        }



        //判断temp_info的状态
        //标记此箱子当前的状态。0初始状态、1填写包裹详情、2货物清单、3存档照片
        $order_status = Configure::getMpPackageOrderStatus();
        if($temp_info -> mode_id == 1){
            $kuaidi = '香港邮政';
        }else{
            $kuaidi = '顺丰快递';
        }

        $package = '';
        if($temp_info -> order_id){
            $package = DB::table('packages')
                -> leftJoin('ems_package','packages.id','ems_package.package_id')

                -> where([
                'packages.order_id' => $temp_info -> order_id,
                'packages.flag' => 0
            ])
                -> select([
                    'packages.*',
                    'ems_package.ems_json'
                ])
                -> first();
        }elseif($temp_info -> package_id){
            $package = DB::table('packages')
                -> leftJoin('ems_package','packages.id','ems_package.package_id')

                -> where([
                    'packages.id' => $temp_info -> package_id,
                ])
                -> select([
                    'packages.*',
                    'ems_package.ems_json'
                ])
                -> first();

            if($package){
                $kuaidi = '香港邮政';
                $price_sum = 0;
                //计算包裹的申报税金
                $goods_par = DB::table('packages_goods_paratemer') -> where([
                    'id' => $package -> goods_id
                ]) -> first();
                //计算税金
                $goods_tax = config('admin.goods_tax');
                //取税率1 税率2 税率3
                $tax_rate1 = 0;
                $tax_rate2 = 0;
                $tax_rate3 = 0;

                foreach($goods_tax as $vo){
                    if($goods_par -> Tax_code1 && $vo['code'] ==  $goods_par -> Tax_code1){
                        $tax_rate1 = floatval($vo['tax']);
                    }
                    if($goods_par -> Tax_code2 && $vo['code'] ==  $goods_par -> Tax_code2){
                        $tax_rate2 = floatval($vo['tax']);
                    }
                    if($goods_par -> Tax_code3 && $vo['code'] ==  $goods_par -> Tax_code3){
                        $tax_rate3 = floatval($vo['tax']);
                    }
                }

                $shuijin =
                    floatval($goods_par -> s_price1) * intval($goods_par -> s_pieces1) * $tax_rate1+
                    floatval($goods_par -> s_price2) * intval($goods_par -> s_pieces2) * $tax_rate2+
                    floatval($goods_par -> s_price3) * intval($goods_par -> s_pieces3) * $tax_rate3;

                $price_sum =
                    floatval($goods_par -> s_price1) * intval($goods_par -> s_pieces1)+
                    floatval($goods_par -> s_price2) * intval($goods_par -> s_pieces2)+
                    floatval($goods_par -> s_price3) * intval($goods_par -> s_pieces3);


            }


        }

        $trackingList = [];
        $ems_json = [];
        if($package && $package -> trackingList){
            $temp = json_decode($package -> trackingList,true);
            foreach($temp as $k => $vo){
                $timestamp = strtotime($vo['Time']);
                $temp[$k]['date_str'] = date('Y.m.d',$timestamp);
                $temp[$k]['time_str'] = date('H:i',$timestamp);
            }
            $trackingList = $temp;
        }

        if($package && $package -> ems_json){
            $ems_temp = json_decode($package -> ems_json);

            foreach($ems_temp as $k => $vo){

                $timestamp = strtotime($vo[0]);
                $ems_json[$k]['date_str'] = date('Y.m.d',$timestamp);
                $ems_json[$k]['time_str'] = date('H:i',$timestamp);
                $ems_json[$k]['content'] = $vo[1];
                $ems_json[$k]['extra'] = $vo[2];
            }

        }





        $model = new Order();
        $package_info = [
            'package_num' => $temp_info -> package_num,
            'status' => $order_status[$temp_info -> order_status],
            'express' => $kuaidi,
            'price' => $price_sum?round(floatval($price_sum),2):0, //申报价格
            'tax' => round(floatval($shuijin),2), //税金
            'points' => 0,//使用积分
            'baoxian_type' => '不使用',
            'wuliu_num' => $package?$package -> wuliu_num:'',
            'trackingStatus' => $package?$model -> transTrackingStatus($package->trackingStatus):'',
            'clear_status' => $package?$package -> clear_status:'',
            'trackingList' => $trackingList,
            'ems_json' => $ems_json,
            'order_type' => $temp_info -> order_type
        ];


        $address_info = DB::table('mp_address')
            -> where([
                'id' => $temp_info -> address_id
            ]) -> first();

        if($address_info){
            $address_info = [
                'id' => $address_info -> id ,
                'name' => $temp_info -> name,
                'tel' => $temp_info -> tel,
                'address' => $temp_info -> address
            ];
        }else{
            $address_info = [
                'id' => null ,
                'name' => $package?$package -> name:'',
                'tel' => $package?$package -> tel:'',
                'address' => $package?$package -> address:''
            ];
        }




        return [
            'is_edit' => $temp_info -> order_status > 0?0:1, //是否允许编辑 1 允许 0 不允许
            'package_info' => $package_info,
            'address_info' => $address_info
        ];


    }


    //绑定地址id 跟 箱子id
    public function bindAddressId(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:mp_users,id',
            'package_id' => 'required|numeric',
            'address_id' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }

        $address = DB::table('mp_address')
            -> where([
                'user_id' => $request -> user_id,
                'id' => $request -> address_id,
                'flag' => 0
            ]) -> first();
        if(!$address){
            return [
                'code' => 500,
                'msg' => '绑定失败，401'
            ];
        }

        //判断是不是她的箱子
        $package_info = DB::table('mp_temp_package_number')
            -> where([
                'id' => $request -> package_id,
                'flag' => 0,
                'user_id' => $request -> user_id
            ]) -> first();
        if(!$package_info){
            return [
                'code' => 500,
                'msg' => '绑定失败，402'
            ];
        }


        //更新包裹的绑定地址id
        DB::table('mp_temp_package_number')
            -> where([
                'id' => $request -> package_id,
            ]) -> update([
                'address_id' => $address -> id,
                'province' => $address -> province,
                'city' => $address -> city,
                'country' => $address -> country,
                'address' => $address -> address,
                'name' => $address -> name,
                'tel' => $address -> tel,
                'card' => $address -> card,
            ]);

        //更新箱子状态为 货物清单扫描
        DB::table('mp_temp_package_number')
            -> where([
                'id' => $package_info -> id
            ]) -> update([
                'status' => 2
            ]);

        return [
            'code' => 200,
            'msg' => '绑定成功',
            'status' => 2,
            'status_str' => '货物清单扫描'
        ];

    }


    //货物清单扫描
    public function scanGoods(Request $request){

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:mp_users,id',
            'package_id' => 'required|numeric',
            'code' => 'required|max:100',
        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }



        $code = trim($request -> code);

        $goods_info = DB::table('mp_scan_goods_info')
            -> where([
                'package_id' => $request -> package_id,
                'product_no' => $code,
                'flag' => 0
            ]) -> first();

        $check_res = $this -> checkOutMode($request -> package_id);
        \Illuminate\Support\Facades\Log::info($check_res);
        if($check_res){
            return [
                'code' => 500,
                'msg'=>$check_res
            ];
        }

        if(!$goods_info){
            //查找他是否在erp_product_list 中
            $product_info = DB::table('goods_list')
                -> where([
                    'flag' => 0,
                    'product_id' => $code
                ]) -> first();
            $info_id = DB::table('mp_scan_goods_info') -> insertGetId([
                'product_no' => $code,
                'product_id' => $product_info?$product_info -> id:0,
                'product_name' => $product_info?$product_info -> product_name:'',
                'declare_price' => $product_info?$product_info -> price:0,
                'taobao_url' => $product_info?$product_info -> taobao_url:'',
                'product_method' => $product_info?$product_info -> product_method:'',
                'product_format' => $product_info?$product_info -> product_format:'',
                'product_unit' => $product_info?$product_info -> product_unit:'',
                'english_name' => $product_info?$product_info -> english_name:'',
                'brand_name' => $product_info?$product_info -> brand_name:'',



                'goods_number' => 1,
                'package_id' => $request -> package_id,
                'user_id' => $request -> user_id,
                'created_at' => time(),
                'updated_at' => time(),
            ]);

            dispatch(new Reptile(MpScanInfo::find($info_id)));

        }else{
            //数量 + 1
            DB::table('mp_scan_goods_info')
                -> where([
                    'id' => $goods_info -> id
                ]) -> update([
                    'goods_number' => intval($goods_info -> goods_number) + 1,
                    'updated_at' => time()
                ]);
            $info_id = $goods_info -> id;
        }


        //查此箱子的情况
        $list = DB::table('mp_scan_goods_info')
            -> where([
                'package_id' => $request -> package_id,
                'flag' => 0
            ])
            -> select([
                'id',
                'product_no',
                'product_name',
                'product_img',
                'goods_number',
                'declare_price'
            ])
            ->  get();

        //计算商品总件数
        $number = 0;

        //计算目前的商品总价格
        $price_sum = 0;


        foreach($list as $k => $vo){
            $number += intval($vo -> goods_number);
            $price_sum = intval($vo -> goods_number) * floatval($vo -> declare_price);
        }





        $info = DB::table('mp_scan_goods_info') -> where([
            'id' => $info_id
        ]) -> first();



        return [
            'code' => 200,
            //商品种类
            'type_num' => count($list),
            //商品件数
            'goods_num' => $number,
            //商品详情
            'goods_info' => $info
        ];


    }


    //扫描页面 返回list
    public function scanGoodsList(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:mp_users,id',
            'package_id' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }

        //查此箱子的情况
        $list = DB::table('mp_scan_goods_info')
            -> where([
                'package_id' => $request -> package_id,
                'flag' => 0
            ])
            -> select([
                'id',
                'product_no',
                'product_name',
                'product_img',
                'goods_number'
            ])
            ->  get();

        //计算商品总件数
        $number = 0;
        foreach($list as $k => $vo){
            $number += intval($vo -> goods_number);
        }




        return [
            'code' => 200,
            //商品种类
            'type_num' => count($list),
            //商品件数
            'goods_num' => $number,
            //商品详情
            'goods_list' => $list
        ];



    }

    //通过清单id 获取商品详情
    public function getGoodsInfo(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:mp_users,id',
            'package_id' => 'required|numeric', //箱子id
            'goods_info_id' => 'required|numeric', //内件id
        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }

        //先检验
        $scan_goods_info = DB::table('mp_scan_goods_info')
            -> where([
                'id' => $request -> goods_info_id,
                'package_id' => $request -> package_id,
                'user_id' => $request -> user_id,
                'flag' => 0
            ])
            -> first();
        if(!$scan_goods_info){
            return [
                'code' => 500,
                'msg' => '没有此数据'
            ];
        }

        return [
            'code' => 200,
            'msg' => $scan_goods_info
        ];

    }


    //货物清单编辑
    public function editGoodsInfo(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:mp_users,id',
            'package_id' => 'required|numeric', //箱子id
            'goods_info_id' => 'required|numeric', //内件id
            //'product_name' => 'required|max:200', //商品名称

        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }


        //先检验
        $scan_goods_info = DB::table('mp_scan_goods_info')
            -> where([
                'id' => $request -> goods_info_id,
                'package_id' => $request -> package_id,
                'user_id' => $request -> user_id,
                'flag' => 0
            ])
            -> first();
        if(!$scan_goods_info){
            return [
                'code' => 500,
                'msg' => '没有此数据'
            ];
        }



        //更新货物清单
        DB::table('mp_scan_goods_info')
             -> where([
                 'id' => $scan_goods_info -> id
             ])
            -> update([
                'product_name' => $request -> product_name,
                'product_method' => $request -> product_method, //包装方式
                'product_format' => $request -> product_format, //规格
                'english_name' => $request -> english_name, //英文名称
                'brand_name' => $request -> brand_name, //品牌
                'product_unit' => $request -> product_unit, //单位
                'declare_price' => $request -> declare_price,  //申报价格
                'taobao_url' => $request -> taobao_url, //淘宝链接
                'updated_at' => time()
            ]);

        return [
            'code' => 200,
            'msg' => '更新成功'
        ];

    }


    //货物清单删除
    public function deleteGoods(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:mp_users,id',
            'package_id' => 'required|numeric', //箱子id
            'goods_info_id' => 'required|numeric', //内件id
        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }

        //先检验
        $scan_goods_info = DB::table('mp_scan_goods_info')
            -> where([
                'id' => $request -> goods_info_id,
                'package_id' => $request -> package_id,
                'user_id' => $request -> user_id,
                'flag' => 0
            ])
            -> first();
        if(!$scan_goods_info){
            return [
                'code' => 500,
                'msg' => '没有此数据'
            ];
        }

        //删除此包裹
        DB::table('mp_scan_goods_info')
            -> where([
                'id' => $scan_goods_info -> id
            ]) -> update([
                'flag' => 1
            ]);


        //查此箱子的情况
        $list = DB::table('mp_scan_goods_info')
            -> where([
                'package_id' => $request -> package_id,
                'flag' => 0
            ])
            -> select([
                'id',
                'product_no',
                'product_name',
                'product_img',
                'goods_number'
            ])
            ->  get();

        //计算商品总件数
        $number = 0;
        foreach($list as $k => $vo){
            $number += intval($vo -> goods_number);
        }



        return [
            'code' => 200,
            'msg' => '删除成功',
            'type_num' => count($list),
            'goods_num' => $number
        ];

    }

    //货物清单 编辑数量
    public function editGoodsNumber(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:mp_users,id',
            'package_id' => 'required|numeric', //箱子id
            'goods_info_id' => 'required|numeric', //内件id,
            'number' => 'required|numeric|min:1'
        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }

        $goods_info = DB::table('mp_scan_goods_info')
            -> where([
                'id' => $request -> goods_info_id
            ]) -> first();

        //增加
        if(intval($goods_info -> goods_number) < intval($request -> number)){
            $check_res = $this -> checkOutMode($request -> package_id);
            \Illuminate\Support\Facades\Log::info($check_res);
            if($check_res){
                return [
                    'code' => 500,
                    'msg'=>$check_res
                ];
            }
        }






        DB::table('mp_scan_goods_info')
            -> where([
                'id' => $request -> goods_info_id
            ]) -> update([
                'goods_number' => $request -> number
            ]);



        return [
            'code' => 200,
            'msg' => '编辑成功'
        ];

    }


    //货物清单下一步 -> 存档照片
    public function endGoodsList(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:mp_users,id',
            'package_id' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }


        $check_res = $this -> checkOutMode($request -> package_id);
        \Illuminate\Support\Facades\Log::info($check_res);
        if($check_res){
            return [
                'code' => 500,
                'msg'=>$check_res
            ];
        }


        //先看有没有这个包裹
        $temp_info = DB::table('mp_temp_package_number')
            -> where([
                'id' => $request -> package_id,
                'flag' => 0,
                'user_id' => $request -> user_id
            ]) -> first();
        if(!$temp_info){
            return [
                'code' => 500,
                'msg' => '没有此箱子编码'
            ];
        }


        //更新箱子状态为 存档照片
        DB::table('mp_temp_package_number')
            -> where([
                'id' => $temp_info -> id
            ]) -> update([
                'status' => 3
            ]);

        return [
            'code' => 200,
            'msg' => '保存成功',
            'status' => 3,
            'status_str' => '存档照片'
        ];


    }



    //判断
    /*
    邮政小包：个数15个
    bc 跨境电商  不能超过5000块
    */
    //通过 包裹的 发货方式  判断
    public function checkOutMode($package_id){
        return false;
        $package_info = DB::table('mp_temp_package_number')
            -> where([
                'id' => $package_id
            ]) -> first();
        if($package_info -> mode_id == 1 || $package_info -> mode_id == 3){
            //邮政小包
            //查此箱子的情况
            $list = DB::table('mp_scan_goods_info')
                -> where([
                    'package_id' => $package_id,
                    'flag' => 0
                ])
                -> select([
                    'id',
                    'product_no',
                    'product_name',
                    'product_img',
                    'goods_number',
                    'declare_price'
                ])
                ->  get();

            //计算商品总件数
            $number = 0;
            //计算目前的商品总价格
            $price_sum = 0;

            foreach($list as $k => $vo){
                $number += intval($vo -> goods_number);
                $price_sum = intval($vo -> goods_number) * floatval($vo -> declare_price);
            }


            //邮政小包 个数不能大于15
            if($package_info -> mode_id == 1){
                if($number > 15){
                    return '邮政小包,商品个数不能大于15个';
                }
            }

            if($package_info -> mode_id == 2){
                if($price_sum > 5000){
                    return '跨境电商,总价不能大于5000元';
                }
            }

        }

    }



    //存档照片
    public function savePhotos(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:mp_users,id',
            'package_id' => 'required|numeric',
            //'photo' => 'required|image'
            'photo' => 'required'
        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }

        $image = $request -> photo;
        if (strstr($image,",")){
            $image = explode(',',$image);
            $image = $image[1];
        }



        $destinationPath = public_path('uploads/mp_photos');
        $extension = 'png';
        $fileName = str_random(10).time().'.'.$extension;

        /*
        $file = $request -> photo;

        $extension = $file->getClientOriginalExtension();
        $fileName = str_random(10).'.'.$extension;
        $res = $file->move($destinationPath, $fileName);
        */
        //return $destinationPath.'/'.$fileName;

        $res = file_put_contents($destinationPath.'/'.$fileName,base64_decode($image));
        if($res){
            //添加图片
            $photo_id = DB::table('mp_package_photos')
                -> insertGetId([
                    'user_id' => $request -> user_id,
                    'package_id' => $request -> package_id,
                    'image_url' => $fileName,
                    'created_at' => time(),
                    'updated_at' => time(),
                    'photo_type' => $request -> photo_type
                ]);
            return [
                'code' => 200,
                'file_name' => url('uploads/mp_photos').'/'.$fileName,
                'id' => $photo_id,
                'msg' => '上传成功'
            ];
        }else{
            return [
                'code' => 500,
                'msg' => '上传失败'
            ];
        }






    }


    //照片列表
    public function photoLists(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:mp_users,id',
            'package_id' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }


        $temp_info = DB::table('mp_temp_package_number')
            -> where([
                'id' => $request -> package_id
            ]) -> first();


        $photos = DB::table('mp_package_photos')
             -> where([
                 'user_id' => $request -> user_id,
                 'package_id' => $request -> package_id,
                 'flag' => 0
             ]) -> get();
        foreach($photos as $k => $vo){
            $fileName = $vo -> image_url ;
            $photos[$k] -> file_name = url('uploads/mp_photos').'/'.$fileName;
        }


        return [
            'remark' => $temp_info -> remark,
            'photos' => $photos
        ];




    }

    //删除存档照片
    public function deletePhoto(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:mp_users,id',
            'package_id' => 'required|numeric',
            'photo_id' => 'required'
        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }

        $photo_info = DB::table('mp_package_photos')
            -> where([
                'flag' => 0,
                'user_id' => $request -> user_id,
                'package_id' => $request -> package_id,
                'id' => $request -> photo_id
            ]) -> first();
        if(!$photo_info){
            return [
                'code' => 500,
                'msg' => '没有此照片'
            ];
        }

        DB::table('mp_package_photos')
            -> where([
                'id' => $photo_info -> id
            ]) -> update([
                'flag' => 1
            ]);
        return [
            'code' => 200,
            'msg' => '删除成功'
        ];


    }


    //设置未装箱照片
    public function setPhoto(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:mp_users,id',
            'package_id' => 'required|numeric',
            'photo_id' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }

        $photo_info = DB::table('mp_package_photos')
            -> where([
                'user_id' => $request -> user_id,
                'package_id' => $request -> package_id,
                'id' => $request -> photo_id,
                'flag' => 0
            ]) -> first();

        if(!$photo_info){
            return [
                'code' => 500,
                'msg' => '没有此照片'
            ];
        }

        DB::beginTransaction();
        try{
            DB::table('mp_package_photos')
                -> where([
                    'flag' => 0,
                    'user_id' => $request -> user_id,
                    'package_id' => $request -> package_id,
                ]) -> update([
                    'is_set' => 0
                ]);
            DB::table('mp_package_photos')
                -> where([
                    'id' => $photo_info -> id
                ]) -> update([
                    'is_set' => 1
                ]);

            DB::commit();
            return [
                'code' => 200,
                'msg' => '设置成功'
            ];
        }catch (\Exception $exception){
            DB::rollBack();
            return [
                'code' => 500,
                'msg' => '设置失败'
            ];
        }



    }



    //提交存档照片
    public function submitPhotos(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:mp_users,id',
            'package_id' => 'required|numeric',
            'remark' => 'max:200',
            //'photo_ids' => 'required|json'
        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }

        //先看有没有这个包裹
        $temp_info = DB::table('mp_temp_package_number')
            -> where([
                'id' => $request -> package_id,
                'flag' => 0,
                'user_id' => $request -> user_id
            ]) -> first();
        if(!$temp_info){
            return [
                'code' => 500,
                'msg' => '没有此箱子编码'
            ];
        }

        //判断是否有1、地址 2、货物清单 3、存档照片
        if(!$temp_info -> province || !$temp_info -> city || !$temp_info -> country){
            return [
                'code' => 500,
                'msg' => '没有填写地址'
            ];
        }


        $goods_info = DB::table('mp_scan_goods_info')
            -> where([
                'package_id' => $temp_info -> id,
                'flag' => 0
            ]) -> get();

        if(!count($goods_info)){
            return [
                'code' => 500,
                'msg' => '没有填写货物清单'
            ];
        }



        //检查货物清单里 是否都填写了商品名称
        /*
        foreach($goods_info as $vo){
            if(!$vo -> product_name){
                return [
                    'code' => 500,
                    'msg' => '编号'.$vo -> product_no.',没有编辑商品名称'
                ];
            }
        }

        */

        //更新箱子状态为 存档照片
        DB::table('mp_temp_package_number')
            -> where([
                'id' => $temp_info -> id
            ]) -> update([
                'status' => 4,
                'remark' => $request -> remark,
            ]);



        return [
            'code' => 200,
            'msg' => '设置装箱照片成功',
            'status' => 4,
            'status_str' => '物品申报'
        ];

        $photos = DB::table('mp_package_photos')
            -> where([
                'flag' => 0,
                'package_id' => $temp_info -> id,
                'is_set' => 1
            ]) -> first();
        if(!$photos){
            return [
                'code' => 500,
                'msg' => '没有设置未装箱照片'
            ];
        }


        //更新箱子状态为 存档照片
        DB::table('mp_temp_package_number')
            -> where([
                'id' => $temp_info -> id
            ]) -> update([
                'status' => 3,
                'remark' => $request -> remark
            ]);

        return [
            'code' => 200,
            'msg' => '恭喜，打包装箱成功'
        ];


    }


    //提交存档照片 下单
    public function underOrder(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:mp_users,id',
            'package_id' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }

        //先看有没有这个包裹
        $temp_info = DB::table('mp_temp_package_number')
            -> where([
                'id' => $request -> package_id,
                'flag' => 0,
                'user_id' => $request -> user_id
            ]) -> first();
        if(!$temp_info){
            return [
                'code' => 500,
                'msg' => '没有此箱子编码'
            ];
        }

        if($temp_info -> is_order){
            return [
                'code' => 500,
                'msg' => '已下单'
            ];
        }








        $model = new Order();
        $model -> underhandOrder([
            'from_area' => $temp_info -> area_id,
            'userid' => $temp_info -> user_id,
            'weights' => 1,
            'remark' => $_POST['remark'],
        ]);




    }



    //用户列表的 分类 个数
    public function getOrderListNum(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:mp_users,id',
            //'order_status' => 'numeric', //筛选状态
        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }

        $list = DB::table('mp_temp_package_number')
            -> leftJoin('ems_package','mp_temp_package_number.package_id','ems_package.package_id')
            -> leftJoin('packages','mp_temp_package_number.package_id','packages.id')
            -> leftJoin('order','packages.order_id','order.id')
            -> where([
                'mp_temp_package_number.flag' => 0,

            ])
            -> where(function($query)use($request){
                //先查下 他有没有wxuser_id
                $mp_userinfo = DB::table('mp_users') -> where([
                    'id' => $request -> user_id
                ]) -> first();
                if($mp_userinfo -> unionid){
                    $wx_userinfo = DB::table('wxuser') -> where([
                        'unionid' => $mp_userinfo -> unionid
                    ]) -> first();
                    if($wx_userinfo){
                        $query -> where('mp_temp_package_number.user_id',$request -> user_id) -> orWhere('wx_user_id',$wx_userinfo -> id);
                    }else{
                        $query -> where('mp_temp_package_number.user_id',$request -> user_id);
                    }
                }else{
                    $query -> where('mp_temp_package_number.user_id',$request -> user_id);
                }


            })
            -> where(function($query)use($request){
                $query -> where('mp_temp_package_number.order_status',0);
            })
            -> select([
                //'mp_temp_package_number.*',
                'ems_package.ems_status',
                'ems_package.id as ems_package_id',
                'order.pay_status'
            ])
            -> orderBy('mp_temp_package_number.updated_at','desc')
            -> where(function($query)use($request){
                if($request -> keywords){
                    //关键词
                    $query -> where('mp_temp_package_number.package_num','like','%'.trim($request -> keywords).'%')
                        -> orWhere('mp_temp_package_number.remark','like','%'.trim($request -> keywords).'%')
                        -> orWhere('packages.wuliu_num','like','%'.trim($request -> keywords).'%');
                }
            })
            -> get();


        $data = [
            [
                'status_name' => '异常件',
                'num' => 0
            ],
            [
                'status_name' => '待缴税',
                'num' => 0
            ],
            [
                'status_name' => '已经发货',
                'num' => 0
            ],
            [
                'status_name' => '待付款',
                'num' => 0
            ]
        ];


        foreach($list as $k => $vo){
            //判断新的订单状态
            if($vo -> ems_status == '8'){
                //需办理退运 按照异常处理
                //$list[$k] -> order_status_str = '异常件';
                $data[0]['num'] ++;
            }elseif($vo -> ems_status == '5'){
                //$list[$k] -> order_status_str = '待缴税';
                $data[1]['num'] ++;
            }elseif($vo -> ems_package_id){
                //$list[$k] -> order_status_str = '已经发货';
                $data[2]['num'] ++;
            }elseif(!$vo -> pay_status){
                //$list[$k] -> order_status_str = '待付款';
                $data[3]['num'] ++;
            }
        }

        return $data;




    }

    //返回用户的订单列表v1
    public function orderListV0516(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:mp_users,id',
            //'order_status' => 'numeric', //筛选状态
        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }

        $list = DB::table('mp_temp_package_number')
            -> leftJoin('ems_package','mp_temp_package_number.package_id','ems_package.package_id')
            -> leftJoin('packages','mp_temp_package_number.package_id','packages.id')
            -> leftJoin('order','packages.order_id','order.id')
            -> where([
                'mp_temp_package_number.flag' => 0
            ])
            -> where(function($query)use($request){
                //先查下 他有没有wxuser_id
                $mp_userinfo = DB::table('mp_users') -> where([
                    'id' => $request -> user_id
                ]) -> first();
                if($mp_userinfo -> unionid){
                    $wx_userinfo = DB::table('wxuser') -> where([
                        'unionid' => $mp_userinfo -> unionid
                    ]) -> first();
                    if($wx_userinfo){
                        $query -> where('mp_temp_package_number.user_id',$request -> user_id) -> orWhere('wx_user_id',$wx_userinfo -> id);
                    }else{
                        $query -> where('mp_temp_package_number.user_id',$request -> user_id);
                    }
                }else{
                    $query -> where('mp_temp_package_number.user_id',$request -> user_id);
                }


            })
            -> where(function($query)use($request){
                if($request -> order_status){
                    $query -> where('mp_temp_package_number.order_status',$request -> order_status);
                }else{
                    $query -> where('mp_temp_package_number.order_status',0);
                }
            })
            -> where(function($query)use($request){
                if($request -> keywords){
                    //关键词
                    $query -> where('mp_temp_package_number.package_num','like','%'.trim($request -> keywords).'%')
                        -> orWhere('mp_temp_package_number.remark','like','%'.trim($request -> keywords).'%')
                        -> orWhere('packages.wuliu_num','like','%'.trim($request -> keywords).'%');

                }
            })
            -> select([
                'mp_temp_package_number.*',
                'ems_package.ems_status',
                'ems_package.id as ems_package_id',
                'order.pay_status'
            ])
            -> orderBy('mp_temp_package_number.updated_at','desc')
            -> paginate(20);


        foreach($list as $k => $vo){

            if($vo -> name && $vo -> remark){
                $list[$k] -> title = $vo -> name.','.str_limit(trim($vo -> remark),20);
            }elseif($vo -> name){
                $list[$k] -> title = $vo -> name;
            }elseif($vo -> order_type){
                $list[$k] -> title = '后台下单';
            }else{
                $list[$k] -> title = $vo -> package_num;
            }


            //商品件数
            $goods_number = DB::table('mp_scan_goods_info')
                -> where([
                    'package_id' => $vo -> id,
                    'flag' => 0
                ]) -> sum('goods_number');
            $list[$k] -> goods_number = $goods_number;
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
            if($vo -> ems_status == '8'){
                //需办理退运 按照异常处理
                $list[$k] -> order_status_str = '异常件';
                $list[$k] -> button_num = 4;
            }elseif($vo -> ems_status == '5'){
                $list[$k] -> order_status_str = '待缴税';
                $list[$k] -> button_num = 3;
            }elseif($vo -> ems_package_id){
                $list[$k] -> order_status_str = '已发货';
                $list[$k] -> button_num = 2;
            }elseif(!$vo -> pay_status){
                $list[$k] -> order_status_str = '待付款';
                $list[$k] -> button_num = 1;
            }else{
                $list[$k] -> order_status_str = '其他';
                $list[$k] -> button_num = 0;
            }

            if($vo -> status == 5){
                //已完善
                $list[$k] -> is_end = 1;
            }else{
                $list[$k] -> is_end = 0;
            }













        }



        return $list;

    }


    //返回用户的订单列表
    public function orderList(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:mp_users,id',
            //'order_status' => 'numeric', //筛选状态
        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }

        $list = DB::table('mp_temp_package_number')
            -> where([
                'flag' => 0
            ])
            -> where(function($query)use($request){
                //先查下 他有没有wxuser_id
                $mp_userinfo = DB::table('mp_users') -> where([
                    'id' => $request -> user_id
                ]) -> first();
                if($mp_userinfo -> unionid){
                    $wx_userinfo = DB::table('wxuser') -> where([
                        'unionid' => $mp_userinfo -> unionid
                    ]) -> first();
                    if($wx_userinfo){
                        $query -> where('user_id',$request -> user_id) -> orWhere('wx_user_id',$wx_userinfo -> id);
                    }else{
                        $query -> where('user_id',$request -> user_id);
                    }
                }else{
                    $query -> where('user_id',$request -> user_id);
                }


            })
            -> where(function($query)use($request){
                if($request -> order_status){
                    $query -> where('order_status',$request -> order_status);
                }else{
                    $query -> where('order_status',0);
                }
            })
            -> orderBy('updated_at','desc')
            -> paginate(20);

        $order_status = Configure::getMpPackageOrderStatus();

        foreach($list as $k => $vo){
            //status
            //标记此箱子当前的状态。0初始状态、1填写包裹详情、2货物清单、3存档照片


            /*
            if($vo -> province && $vo -> city && $vo -> country){
                $list[$k] -> title = 'To：'.$vo -> province.'-'.$vo -> city.'-'.$vo -> country;
            }else{
                $list[$k] -> title = 'To：暂无收货地区';
            }
            */
            if($vo -> name && $vo -> remark){
                $list[$k] -> title = $vo -> name.','.str_limit(trim($vo -> remark),20);
            }elseif($vo -> name){
                $list[$k] -> title = $vo -> name;
            }elseif($vo -> order_type){
                $list[$k] -> title = '后台下单';
            }else{
                $list[$k] -> title = $vo -> package_num;
            }


            //商品件数
            $goods_number = DB::table('mp_scan_goods_info')
                -> where([
                    'package_id' => $vo -> id,
                    'flag' => 0
                ]) -> sum('goods_number');
            $list[$k] -> goods_number = $goods_number;
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

            $list[$k] -> order_status_str = $order_status[$vo -> order_status];

        }



        return $list;

    }


    //付款
    public function payOrder(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:mp_users,id',
            'package_id' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }

        //先看有没有这个包裹
        $temp_info = DB::table('mp_temp_package_number')
            -> where([
                'id' => $request -> package_id,
                'flag' => 0,
                'user_id' => $request -> user_id
            ]) -> first();
        if(!$temp_info){
            return [
                'code' => 500,
                'msg' => '没有此箱子编码'
            ];
        }

        $mp_user = DB::table('mp_users') -> where([
            'id' => $request -> user_id
        ]) -> first();
        $wx_user = DB::table('wxuser') -> where([
            'unionid' => $mp_user -> unionid
        ]) -> first();

        //根据包裹 拿订单
        $order_info = DB::table('order') -> where([
            'mp_package_id' => $request -> package_id
        ]) -> first();
        if(!$order_info){
            return [
                'code' => 500,
                'msg' => '没有订单'
            ];
        }

        if(!$order_info -> weight){
            return [
                'code' => 500,
                'msg' => '等待网点工作人员确定包裹重量，请稍后付款'
            ];
        }



        if(!$wx_user || !$order_info || $wx_user -> price < $order_info -> pay_price){
            return [
                'code' => 500,
                'msg' => '余额不足'
            ];
        }


        //订单改为一支付
        if($order_info -> status == 5){
            $order_status = 1 ;
        }else{
            $order_status = $order_info -> status;
        }

        DB::table('order') -> where([
            'id' => $order_info -> id
        ]) -> update([
            'pay_time' => time(),
            'pay_type' =>  1,
            'pay_number' => '小程序支付',
            'pay_status' => 1,
            'status' => $order_status
        ]);

        //扣除余额
        DB::table('wxuser') -> where([
            'id' => $wx_user -> id
        ]) -> decrement('price',floatval($order_info -> pay_price));
        //扣除余额添加日志记录
        $userinfo = DB::table('wxuser') -> where([
            'id' => $wx_user -> id
        ]) -> first();

        //添加日志
        DB::table('price_log') -> insert([
            'userid' => $wx_user -> id,
            'price' => floatval($order_info -> pay_price),
            'type' => 7,
            'created_at' => time(),
            'updated_at' => time(),
            'from_user_id' => 0,
            'in_out' => 1 ,// 0收入1支出
            'end_price' => $userinfo -> price
        ]);



        //改成已支付
        DB::table('mp_temp_package_number')
            -> where([
                'id' => $request -> package_id
            ]) -> update([
                'order_status' => 2,
                'updated_at' => time()
            ]);

        return [
            'code' => 200,
            'msg' => '付款成功'
        ];

    }


    //付款
    public function payOrderNew(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:mp_users,id',
            'package_id' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }

        //先看有没有这个包裹
        $temp_info = DB::table('mp_temp_package_number')
            -> where([
                'id' => $request -> package_id,
                'flag' => 0,
                'user_id' => $request -> user_id
            ]) -> first();
        if(!$temp_info){
            return [
                'code' => 500,
                'msg' => '没有此箱子编码'
            ];
        }



        //根据包裹 拿订单
        $order_info = DB::table('order') -> where([
            'mp_package_id' => $request -> package_id
        ]) -> first();
        if(!$order_info){
            return [
                'code' => 500,
                'msg' => '该包裹还未称重，暂时无法付款'
            ];
        }



        //返回单号 和 价格
        return [
            'code' => 200,
            'order_num' => $order_info -> order_num,
            //'order_id' => $order_info -> id,
            'price' => $order_info -> pay_price,
        ];



    }


    //选择付款方式
    public function selectPayMethod(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:mp_users,id',
            'package_id' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }


        //先看有没有这个包裹
        $temp_info = DB::table('mp_temp_package_number')
            -> where([
                'id' => $request -> package_id,
                'flag' => 0,
                'user_id' => $request -> user_id
            ]) -> first();
        if(!$temp_info){
            return [
                'code' => 500,
                'msg' => '没有此箱子编码'
            ];
        }


        //根据包裹 拿订单
        $order_info = DB::table('order') -> where([
            'mp_package_id' => $request -> package_id
        ]) -> first();
        if(!$order_info){
            return [
                'code' => 500,
                'msg' => '该包裹还未称重，暂时无法付款'
            ];
        }

        //根据区域 取收款二维码
        $pay_imgs_zhifubao = DB::table('payimage')
            -> where([
                'area_id' => $temp_info -> area_id,
                'type' => 0
            ]) -> first();
        if(!$pay_imgs_zhifubao){
            $pay_imgs_zhifubao = DB::table('payimage')
                -> where([
                    'area_id' => 0,
                    'type' => 0
                ]) -> first();
        }

        $pay_imgs_weixin = DB::table('payimage')
            -> where([
                'area_id' => $temp_info -> area_id,
                'type' => 1
            ]) -> first();
        if(!$pay_imgs_weixin){
            $pay_imgs_weixin = DB::table('payimage')
                -> where([
                    'area_id' => 0,
                    'type' => 1
                ]) -> first();
        }

        return [
            'code' => 200,
            'zhifubao_img' => asset('uploads/'.$pay_imgs_zhifubao -> image),
            'weixin_img' => asset('uploads/'.$pay_imgs_weixin -> image),
        ];


    }


    //余额付款
    public function payBalance(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:mp_users,id',
            'package_id' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }

        //先看有没有这个包裹
        $temp_info = DB::table('mp_temp_package_number')
            -> where([
                'id' => $request -> package_id,
                'flag' => 0,
                'user_id' => $request -> user_id
            ]) -> first();
        if(!$temp_info){
            return [
                'code' => 500,
                'msg' => '没有此箱子编码'
            ];
        }

        $mp_user = DB::table('mp_users') -> where([
            'id' => $request -> user_id
        ]) -> first();
        $wx_user = DB::table('wxuser') -> where([
            'unionid' => $mp_user -> unionid
        ]) -> first();

        //根据包裹 拿订单
        $order_info = DB::table('order') -> where([
            'mp_package_id' => $request -> package_id
        ]) -> first();
        if(!$order_info){
            return [
                'code' => 500,
                'msg' => '该包裹还未称重，暂时无法付款'
            ];
        }







        if(!$wx_user || !$order_info || $wx_user -> price < $order_info -> pay_price){
            return [
                'code' => 500,
                'msg' => '余额不足'
            ];
        }





        //订单改为一支付
        if($order_info -> status == 5){
            $order_status = 1 ;
        }else{
            $order_status = $order_info -> status;
        }

        DB::table('order') -> where([
            'id' => $order_info -> id
        ]) -> update([
            'pay_time' => time(),
            'pay_type' =>  1,
            'pay_number' => '小程序支付',
            'pay_status' => 1,
            'status' => $order_status
        ]);

        //扣除余额
        DB::table('wxuser') -> where([
            'id' => $wx_user -> id
        ]) -> decrement('price',floatval($order_info -> pay_price));
        //扣除余额添加日志记录
        $userinfo = DB::table('wxuser') -> where([
            'id' => $wx_user -> id
        ]) -> first();

        //添加日志
        DB::table('price_log') -> insert([
            'userid' => $wx_user -> id,
            'price' => floatval($order_info -> pay_price),
            'type' => 7,
            'created_at' => time(),
            'updated_at' => time(),
            'from_user_id' => 0,
            'in_out' => 1 ,// 0收入1支出
            'end_price' => $userinfo -> price
        ]);



        //改成已支付
        DB::table('mp_temp_package_number')
            -> where([
                'id' => $request -> package_id
            ]) -> update([
                'order_status' => 2,
                'updated_at' => time()
            ]);

        return [
            'code' => 200,
            'msg' => '付款成功'
        ];
    }


    //物品申报
    public function declareGoods(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:mp_users,id',
            'package_id' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            return [
                'code' => 500,
                'msg' => '缺少必填参数'
            ];
        }

        //先看有没有这个包裹
        $temp_info = DB::table('mp_temp_package_number')
            -> where([
                'id' => $request -> package_id,
                'flag' => 0,
                'user_id' => $request -> user_id
            ]) -> first();
        if(!$temp_info){
            return [
                'code' => 500,
                'msg' => '没有此箱子编码'
            ];
        }



        //查找申报
        $declare_arr = [];
        if($temp_info -> declare_id){
            $goods_paratemer = DB::table('packages_goods_paratemer')
                -> where([
                    'id' => $temp_info -> declare_id
                ]) -> first();
            if($goods_paratemer){
                if($goods_paratemer->s_content1){
                    $declare_arr[] = [
                        'content' => $goods_paratemer->s_content1,
                        'price' => $goods_paratemer->s_price1,
                        'tax' => $goods_paratemer->Tax_code1,
                        'pieces' => $goods_paratemer-> s_pieces1,
                    ];
                }
                if($goods_paratemer->s_content2){
                    $declare_arr[] = [
                        'content' => $goods_paratemer->s_content2,
                        'price' => $goods_paratemer->s_price2,
                        'tax' => $goods_paratemer->Tax_code2,
                        'pieces' => $goods_paratemer->s_pieces2,
                    ];
                }

                if($goods_paratemer->s_content3){
                    $declare_arr[] = [
                        'content' => $goods_paratemer->s_content3,
                        'price' => $goods_paratemer->s_price3,
                        'tax' => $goods_paratemer->Tax_code3,
                        'pieces' => $goods_paratemer->s_pieces3,
                    ];
                }



            }

        }


        //返回币种
        return [
            'code' => 200,
            'currency_type' => Configure::dealArray(Configure::getCurrency()),
            'weight' => $temp_info -> weight?$temp_info -> weight:'',
            'currency' => 1,
            'declare_json' => $declare_arr,
            'need_bind' => $temp_info -> is_temp?1:0 //是否需要跳转绑定单号
        ];

    }


    //物品申报 查看示例
    public function getDeclareTemp (){
        //随机取一条
        $info = DB::table('goods_paratemer')
            -> orderBy('id','desc')
            -> first();

        $goods_paratemer = DB::table('goods_paratemer')
            -> where([
                'id' => rand(1,$info -> id)
            ]) -> first();
        return [
            'code' => 200,
            'data' => $goods_paratemer
        ];
    }

    //通过税号 获取税率
    public function getTaxRate(){

    }




    //保存物品申报
    public function saveDeclare(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:mp_users,id',
            'package_id' => 'required|numeric',
            'weight' => 'required|numeric|min:0.01',
            //'currency' => 'required|numeric',
            'declare_json' => 'required|json' //[{"":"",}]

        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }


        $declare_json = json_decode($request -> declare_json);
        if(!count($declare_json)){
            return [
                'code' => 500,
                'msg' => '提交参数有误'
            ];
        }

        foreach($declare_json as $vo){
            if(!isset($vo -> content) || !isset($vo -> tax) || !isset($vo -> price) || !isset($vo -> pieces) ){
                return [
                    'code' => 500,
                    'msg' => '提交参数有误'
                ];
            }
        }

        $temp = [];


        foreach($declare_json as $k => $vo){
            if(!$vo -> content || !$vo -> tax || !$vo -> price || !$vo -> pieces){
                return [
                    'code' => 500,
                    'msg' => '缺少必填项'
                ];
            }
            $temp['s_content'.($k + 1)] = trim($vo -> content);
            $temp['Tax_code'.($k + 1)] = trim($vo -> tax);
            $temp['s_price'.($k + 1)] = trim($vo -> price);
            $temp['s_pieces'.($k + 1)] = trim($vo -> pieces);

        }

        //添加 goods_paratemer
        $declare_id = DB::table('packages_goods_paratemer')
            -> insertGetId([
                's_content1' => isset($temp['s_content1'])?$temp['s_content1']:'',
                's_content2' => isset($temp['s_content2'])?$temp['s_content2']:'',
                's_content3' => isset($temp['s_content3'])?$temp['s_content3']:'',

                'Tax_code1' => isset($temp['Tax_code1'])?$temp['Tax_code1']:'',
                'Tax_code2' => isset($temp['Tax_code2'])?$temp['Tax_code2']:'',
                'Tax_code3' => isset($temp['Tax_code3'])?$temp['Tax_code3']:'',

                's_price1' => isset($temp['s_price1'])?$temp['s_price1']:'',
                's_price2' => isset($temp['s_price2'])?$temp['s_price2']:'',
                's_price3' => isset($temp['s_price3'])?$temp['s_price3']:'',

                's_pieces1' => isset($temp['s_pieces1'])?$temp['s_pieces1']:'',
                's_pieces2' => isset($temp['s_pieces2'])?$temp['s_pieces2']:'',
                's_pieces3' => isset($temp['s_pieces3'])?$temp['s_pieces3']:'',

                'declare_currency' => 'RMB',
                'declare_value' => 1,
                'is_super' => 0,
                'created_at' => time(),
                'updated_at' => time(),
            ]);

        //更新小程序订单的绑定 declare_id
        DB::table('mp_temp_package_number') -> where([
            'id' => $request -> package_id
        ]) -> update([
            'declare_id' => $declare_id,
            'weight' => $request -> weight
        ]);

        $info = DB::table('mp_temp_package_number') -> where([
            'id' => $request -> package_id
        ]) -> first();
        if(!$info){
            return [
                'code' => 500,
                'msg' => '数据有误'
            ];
        }


        return [
            'code' => 200,
            'msg' => $info -> is_temp?'申报成功，跳转到绑定编号页面':'申报成功，进入订单列表'
        ];

    }


    //绑定 临时单号
    public function bindTempPackage(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:mp_users,id',
            'code' => 'required',
            'package_id' => 'required'
        ]);
        if ($validator->fails()) {
            return [
                'code' => 500,
                'msg' => '缺少必要参数'
            ];
        }


        $package_info = DB::table('mp_temp_package_number') -> where([
            'id' => $request -> package_id
        ]) -> first();
        if(!$package_info -> is_temp){
            return [
                'code' => 500,
                'msg' => '此单不允许绑定'
            ];
        }





        //先检查下此单号 有没有被别人绑定
        //先看有没有这个包裹
        $temp_info = DB::table('mp_temp_package_number')
            -> where([
                'package_num' => $request -> code,
                'flag' => 0
            ]) -> first();
        if(!$temp_info){
            return [
                'code' => 500,
                'msg' => '没有此箱子编码'
            ];
        }

        if($temp_info -> user_id){
            if($temp_info -> user_id != $request -> user_id){
                return [
                    'code' => 500,
                    'msg' => '不属于你的箱子'
                ];
            }
        }

        //扫描成功 把这个箱子的编号、区域 给了临时箱子
        DB::table('mp_temp_package_number') -> where([
            'id' => $request -> package_id
        ]) -> update([
            'package_num' => $temp_info -> package_num,
            'area_id' => $temp_info -> area_id,
            'is_temp' => 0,
            'temp_repertory_id' => $temp_info -> temp_repertory_id,
            'status' => 5
        ]);

        //


        //把旧的单号删除掉
        DB::table('mp_temp_package_number')
            -> where([
                'id' => $temp_info -> id
            ]) -> update([
                'flag' => 1,
                'package_num' => 'temp_'.$temp_info -> package_num
            ]);





        return [
            'code' => 200,
            'msg' => '绑定成功',
        ];

    }















}
