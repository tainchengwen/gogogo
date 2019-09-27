<?php

namespace App\Http\Controllers;

use App\AreaName;
use App\Order;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApiController extends Controller
{


    public function test(){
//        echo phpinfo();exit;
        $url = "https://fenithcdn.oss-cn-shanghai.aliyuncs.com/longpicture/top.jpg";
//        dd(  imagecreatefromstring($url));
//        dd(getimagesize($url));
        dd(imagecreatefrompng($url));
        var_dump(auth('api')->user());exit;
        return User::all();
        $user = $this->auth->user();
        var_dump($user);exit;
        //一个订单的数据
        $arr = [
            [
                'name' => '名字',
                'tel' => '手机号',
                'city' => '城市',
                'province' => '省',
                'address' => '地址',
                'weight' => '重量'
            ],
            [
                'name' => '名字',
                'tel' => '手机号',
                'city' => '城市',
                'province' => '省',
                'address' => '地址',
                'weight' => '重量'
            ],
        ];

        echo json_encode($arr);

    }


    //生成订单
    public function makeOrderData(Request $request){
        $sign = $request -> sign;
        $sign = base64_decode($sign);
        $user_pass = explode('&&',$sign);
        $username = $user_pass[0];
        if($request->method() == 'POST'){

            $json_data = $request -> json_data;
            json_decode($json_data);
            if(json_last_error()){
                return [
                    'code' => '500',
                    'msg' => 'json 格式错误'
                ];
            }


            //订单备注
            if(isset($request -> remark)){
                $remark = $request -> remark;
            }



            //校验json
            //先互相校验
            $json_data = json_decode($json_data,true);

            //收件人姓名
            $names = [];
            //收件人电话
            $tels = [];
            //收件人省份
            $provinces = [];
            //收件人市
            $citys = [];
            //收件人地址
            $address = [];
            //重量
            $weights = [];


            $count_values_name_arr = [];
            $count_values_tel_arr = [];
            $count_values_addressstr_arr = [];
            $package_nums = [];
            $is_super_package_num = false;

            foreach($json_data as $k => $vo){
                //首先看是否存在
                if(isset($vo['name']) && isset($vo['tel']) && isset($vo['city']) && isset($vo['province']) && isset($vo['address']) && isset($vo['weight'])){
                    $names[] = trim($vo['name']);
                    $tels[] = trim($vo['tel']);
                    $address[] = trim($vo['address']);
                    $provinces[] = trim($vo['province']);
                    $citys[] = trim($vo['city']);
                    $weights[] = trim($vo['weight']);
                    $address_str[] = trim($vo['province']).trim($vo['city']).trim($vo['address']);

                    if($vo['package_num']){
                        $is_super_package_num = true;
                        $package_nums[] = trim($vo['package_num']);
                        //看下给的订单编号是否重复
                        if(in_array(trim($vo['6']),$package_nums)){
                            $count_package_nums = array_count_values($package_nums);
                            if($count_package_nums[trim($vo['package_num'])] > 1){
                                //重复
                                return [
                                    'code' => '500',
                                    'msg' => (intval($k) + 1).'个包裹 上传表中的订单编号互相重复',
                                ];
                            }

                        }
                    }



                    //验证必填
                    if(!$names[$k] || mb_strlen($names[$k],'utf-8') > 5){
                        return [
                            'code' => '500',
                            'msg' => (intval($k) + 1).'个包裹 名字格式有误',
                        ];
                    }


                    if(!$tels[$k] || strlen($tels[$k]) <>  11){
                        return [
                            'code' => '500',
                            'msg' => (intval($k) + 1).'个包裹 手机格式有误',
                        ];
                    }

                    if(!$address[$k] || mb_strlen($address[$k],'utf-8') >  200){
                        return [
                            'code' => '500',
                            'msg' => (intval($k) + 1).'个包裹 地址格式有误',
                        ];
                    }

                    if(!$provinces[$k] || mb_strlen($provinces[$k],'utf-8') >  100){
                        return [
                            'code' => '500',
                            'msg' => (intval($k) + 1).'个包裹 省名格式有误',
                        ];
                    }

                    if(!$citys[$k] || mb_strlen($citys[$k],'utf-8') >  100){
                        return [
                            'code' => '500',
                            'msg' => (intval($k) + 1).'个包裹 市名格式有误',
                        ];
                    }

                    if(!$weights[$k] || !is_numeric($weights[$k])){
                        return [
                            'code' => '500',
                            'msg' => (intval($k) + 1).'个包裹 重量格式有误',
                        ];
                    }





                    //每次赋值的时候 都判断下 用过几次了
                    $count_values_name_arr = array_count_values($names);
                    $count_values_name = $count_values_name_arr[trim($vo['name'])];
                    if($count_values_name == 4){
                        //名字重复
                        return [
                            'code' => '500',
                            'msg' => (intval($k) + 1).'个包裹 名字重复',
                        ];
                    }


                    $count_values_tel_arr = array_count_values($tels);
                    $count_values_tels = $count_values_tel_arr[trim($vo['tel'])];
                    if($count_values_tels == 4){
                        //电话重复
                        return [
                            'code' => '500',
                            'msg' => (intval($k) + 1).'个包裹 电话号码重复',
                        ];
                    }

                    //省市地址 加起来重复
                    $count_values_addressstr_arr = array_count_values($address_str);
                    $count_values_addressstr = $count_values_addressstr_arr[trim($vo['province']).trim($vo['city']).trim($vo['address'])];
                    if($count_values_addressstr == 4){
                        //地址重复
                        return [
                            'code' => '500',
                            'msg' => (intval($k) + 1).'个包裹 地址重复',
                        ];
                    }


                    //查看地址中 是否包含省 市
                    if(!strstr(trim($vo['address']),trim($vo['province']))){
                        //省
                        return [
                            'code' => '500',
                            'msg' => (intval($k) + 1).'个包裹 地址中没有包含省份',
                        ];
                    }

                    if(!strstr(trim($vo['address']),trim($vo['city']))){
                        //市
                        return [
                            'code' => '500',
                            'msg' => (intval($k) + 1).'个包裹 地址中没有包含市名',
                        ];
                    }

                    //数据处理完毕 --> 开始判断 有没有在别的订单中出现过




                }else{
                    return [
                        'code' => '500',
                        'msg' => '第'.(intval($k) + 1).'个包裹数据错误'
                    ];
                }
            }


            if($is_super_package_num){
                //如果有订单编号
                //查看订单编号是否个数一样
                if(count($weights) != count($package_nums)){
                    return [
                        'code' => '500',
                        'msg' => '订单号短缺'
                    ];
                }
                //检查 订单编号 是否存在
                foreach($package_nums as $vo){
                    $package_info = DB::table('packages')
                        -> where('package_num','=',$vo)
                        -> where('flag','=',0)
                        -> first();
                    if($package_info){
                        return [
                            'code' => '500',
                            'msg' => '订单编号'.$vo.'已存在',
                        ];
                    }

                }
            }




            //数据处理完毕 --> 开始判断 有没有在别的订单中出现过


            //验证下 每个名字 每个电话 每个地址 在以前+这次 一共出现了几次
            //一个名字 一个uuid
            $uuid_names_arr = [];
            foreach($count_values_name_arr as $key => $vo){
                $uuid_names_arr[$key] = $this -> create_uuid();
                $count_pre = DB::table('packages') -> where([
                    'wuliu_num' => '',
                    'name' => trim($key),
                    'flag' => 0
                ])  -> count();
                if(intval($count_pre) + intval($vo)  >= 4 ){
                    //判断下 是第几行
                    $temp = array_search(trim($key),$names);
                    return [
                        'code' => '500',
                        'msg' => (intval($temp) + 1).'个包裹 名字在未发货订单中已存在',
                    ];
                }
            }

            foreach($count_values_tel_arr as $key => $vo){
                $count_pre = DB::table('packages') -> where([
                    'wuliu_num' => '',
                    'tel' => trim($key),
                    'flag' => 0
                ])  -> count();
                if(intval($count_pre) + intval($vo)  >= 4 ){
                    //判断下 是第几行
                    $temp = array_search(trim($key),$tels);
                    return [
                        'code' => '500',
                        'msg' => (intval($temp) + 1).'个包裹 电话号码在未发货订单中已存在',
                    ];

                }
            }

            foreach($count_values_addressstr_arr as $key => $vo){
                $count_pre = DB::table('packages') -> where([
                    'wuliu_num' => '',
                    'address' => trim($key),
                    'flag' => 0
                ])  -> count();
                if(intval($count_pre) + intval($vo)  >= 4 ){
                    //判断下 是第几行
                    $temp = array_search(trim($key),$address);
                    return [
                        'code' => '500',
                        'msg' => (intval($temp) + 1).'个包裹 地址在未发货订单中已存在',
                    ];
                }
            }




            //验证完毕=======>

            $user_info = DB::table('admin_users') -> where([
                'username' => $username
            ]) -> first();

            //找到这个代理绑定的用户
            $area_info = DB::table('area_name') -> where([
                'id' => $user_info -> from_area
            ]) -> first();
            if(!$area_info -> wx_user_id){
                return [
                    'code' => '500',
                    'msg' => '无绑定用户'
                ];
            }

            $temp_user = explode(',',trim($area_info -> wx_user_id));



            $this_user_admin = $temp_user[0];



            //开始下单
            $model = new Order();
            $order_res = $model -> underOrder([
                'weights' => $weights,
                'from_area' => $user_info -> from_area,
                'user_id' => $this_user_admin,
                'names' => $names,
                'address' => $address,
                'provinces' => $provinces,
                'citys' => $citys,
                'tels' => $tels,
                'uuid_names_arr' => $uuid_names_arr,
                'remark' => isset($remark)?$remark:'',
                'package_nums' => $package_nums
            ]);

            if($order_res){
                return [
                    'code' => '200',
                    'msg' => $order_res
                ];
            }else{
                return [
                    'code' => '500',
                    'msg' => '下单失败'
                ];
            }




        }else{
            return [
                'code' => '500',
                'msg' => '请求方法错误'
            ];
        }
    }


    //查询订单详情
    public function getOrderInfo(Request $request){
        $order_id = $request -> order_id;
        if(!$order_id){
            return [
                'code' => '500',
                'msg' => '订单号必填'
            ];
        }

        $sign = $request -> sign;
        $sign = base64_decode($sign);
        $user_pass = explode('&&',$sign);
        //代理的username
        $username = $user_pass[0];
        $userinfo = DB::table('admin_users') -> where([
            'username' => $username
        ]) -> first();

        $order_info = DB::table('order') -> where([
            'order_num' => trim($order_id),
            'flag' => 0,
            'from_area' => $userinfo -> from_area
        ]) -> first();

        if(!$order_info){
            return [
                'code' => '500',
                'msg' => '无此订单号'
            ];
        }


        $package_info = DB::table('packages') -> where([
            'order_id' => $order_info -> id,
            'flag' => 0
        ]) -> get();



        if(!$package_info){
            return [
                'code' => '500',
                'msg' => '无此订单号'
            ];
        }


        $package_arr = [];
        foreach($package_info as $k => $vo){
            //编号地址姓名电话重量、邮局单号
            $package_arr[$k][] = [
                'package_num' => $vo -> package_num,
                'address' => $vo -> address,
                'name' => $vo -> name,
                'tel' => $vo -> tel,
                'post_num' => $vo -> wuliu_num,
            ];
        }

        $order_arr = [
            'order_num' => $order_info -> order_num,
            'pay_status' => $order_info -> pay_status
        ];

        return [
            'order_info' => $order_arr,
            'package_info' => $package_arr
        ];


    }


    //订单支付
    public function payOrder(Request $request){
        $order_id = trim($request -> order_id);
        $time = trim($request -> time);
        $number = trim($request -> pay_number);
        $type = trim($request -> type);

        if(!$order_id){
            return [
                'code' => '500',
                'msg' => '订单号必填'
            ];
        }

        if(!$time || !is_numeric($time)){
            return [
                'code' => '500',
                'msg' => '支付时间戳必填'
            ];
        }

        if(!$number){
            return [
                'code' => '500',
                'msg' => '订单流水号必填'
            ];
        }

        if(!$type || !in_array($type,[1,2])){
            return [
                'code' => '500',
                'msg' => '支付种类必填'  //1微信支付2支付宝
            ];
        }

        //看是不是他的订单

        $sign = $request -> sign;
        $sign = base64_decode($sign);
        $user_pass = explode('&&',$sign);
        //代理的username
        $username = $user_pass[0];
        $userinfo = DB::table('admin_users') -> where([
            'username' => $username
        ]) -> first();

        $order_info = DB::table('order') -> where([
            'flag' => 0,
            'from_area' => $userinfo -> from_area,
            'order_num' => $order_id
        ]) -> first();

        if(!$order_info){
            return [
                'code' => '500',
                'msg' => '订单不存在'
            ];
        }


        //如果是已填地址 则改为待发货
        if($order_info -> status == 5){
            $status = 1;
        }else{
            $status = $order_info -> status;
        }

        DB::table('order') -> where([
            'id' => $order_info -> id
        ]) -> update([
            'pay_type' => $type,
            'pay_number' => $number,
            'pay_status' => 1,
            'status' => $status,
            'pay_time' => $time
        ]);


        return [
            'code' => '200',
            'msg' => 'success'
        ];












    }

    //生成单号
    public function makePostNumber(Request $request){
        $order_id = trim($request -> order_id);
        $route = 4; //路线 默认为NN100

        if(!$order_id){
            return [
                'code' => '500',
                'msg' => '订单号必填'
            ];
        }

        $sign = $request -> sign;
        $sign = base64_decode($sign);
        $user_pass = explode('&&',$sign);
        //代理的username
        $username = $user_pass[0];
        $userinfo = DB::table('admin_users') -> where([
            'username' => $username
        ]) -> first();

        $order_info = DB::table('order') -> where([
            'order_num' => $order_id,
            'flag' => 0,
            'from_area' => $userinfo -> from_area
        ]) -> whereIn('status',[1,2]) -> first();

        if(!$order_info){
            return [
                'code' => '500',
                'msg' => '没有此订单'
            ];
        }

        $packages = DB::table('packages') -> where([
            'order_id' => $order_info -> id,
            'flag' => 0
        ]) ->join('goods_paratemer', 'packages.goods_id', '=', 'goods_paratemer.id')
            ->select('packages.*', 'goods_paratemer.*','packages.id as id')
            -> get();


        $apiRes = [];
        foreach($packages as $vol){

            if($vol -> wuliu_num){
                continue;
            }

            $model_order = new Order();
            $apiRes[] = $model_order -> sendPackage($vol,$order_info,$route);
        }

        return [
            'code' => '200',
            'msg' => $apiRes
        ];
    }

    //取消单号
    public function cancelPostNumber(Request $request){
        $package_num = trim($request -> package_num);
        $sign = $request -> sign;
        $sign = base64_decode($sign);
        $user_pass = explode('&&',$sign);
        //代理的username
        $username = $user_pass[0];
        $userinfo = DB::table('admin_users') -> where([
            'username' => $username
        ]) -> first();

        //先看下这个包裹存不存在
        $package_info = DB::table('packages') -> where([
            'flag' => 0,
            'package_num' => $package_num
        ]) -> first();
        if(!$package_info || !$package_info -> wuliu_num){
            return [
                'code' => '500',
                'result' => '没有此订单'
            ];
        }
        $order_info = DB::table('order') -> where([
            'flag' => 0,
            'id' => $package_info -> order_id,
            'from_area' => $userinfo -> from_area
        ]) -> first();

        if(!$order_info){
            return [
                'code' => '500',
                'result' => '没有此订单'
            ];
        }

        //单个取消面单
        $model_area_price = new AreaName();
        $model_order = new Order();
        $api_data = $model_order -> deleteApiData($package_info -> wuliu_num);
        $json_data = json_decode($api_data,true);
        if($json_data['Code'] == '200'){
            //取消成功的话 给他返余额

            $model_area_price -> cancelPackage($package_info -> id);
            //并且 把包裹的单号 删除掉
            DB::table('packages') -> where([
                'id' => $package_info -> id
            ]) -> update([
                'wuliu_num' => ''
            ]);
            //判断订单里有几个没发货的 如果全没发货 则订单状态改为 待发货1  否则 为部分发货2
            $count_send = DB::table('packages') -> where([
                'order_id' => $package_info -> order_id,
                'flag' => 0,
            ]) -> where('wuliu_num','!=','') -> count();
            if($count_send){
                //部分发货
                DB::table('order') -> where([
                    'id' => $package_info -> order_id,
                ]) -> update([
                    'status' => 2  //部分发货
                ]);
            }else{
                DB::table('order') -> where([
                    'id' => $package_info -> order_id,
                ]) -> update([
                    'status' => 1  //待发货
                ]);
            }



            $apiRes['data'] = $json_data['Data'];
            $apiRes['result'] = 'success';
            $apiRes['package_num'] = $package_info -> package_num;
        }else{
            $apiRes['data'] = $json_data['ErrorMsg'];
            $apiRes['result'] = 'error';
            $apiRes['package_num'] = $package_info -> package_num;
        }

        return [
            'code' => '200',
            'msg' => $apiRes
        ];


    }


    //生成pdf
    public function makePdfFile(Request $request){
        //传递order_id
        //用逗号分割的order_id
        $order_ids = trim($request -> order_ids);
        if(!$order_ids){
            return [
                'code' => '500',
                'msg' => '订单号必填'
            ];
        }

        $sign = $request -> sign;
        $sign = base64_decode($sign);
        $user_pass = explode('&&',$sign);
        //代理的username
        $username = $user_pass[0];
        $userinfo = DB::table('admin_users') -> where([
            'username' => $username
        ]) -> first();


        $order_arr = explode(',',$order_ids);
        $temp_str = '';

        foreach($order_arr as $vo){
            $order_info = DB::table('order') -> where([
                'order_num' => trim($vo),
                'from_area' => $userinfo -> from_area,
                'flag' => 0
            ]) -> first();
            if(!$order_info){
                return [
                    'code' => '500',
                    'msg' => '没有此订单号'
                ];
            }

            $packages = DB::table('packages') -> where([
                'order_id' => $order_info -> id,
                'flag' => 0
            ])-> where('wuliu_num','<>','') -> get();


            if(!$packages){
                return [
                    'code' => '500',
                    'msg' => '没有此订单号'
                ];
            }

            foreach($packages as $vol){
                $expressNo = $vol -> wuliu_num;
                if(!$expressNo){
                    continue;
                }
                $temp_str .= $expressNo.',';

            }

        }


        if($temp_str){
            $model_order = new Order();
            $post_data = $model_order -> makePdf($temp_str);
            if($post_data && $post_data != 'error'){
                return [
                    'code' => '200',
                    'msg' => 'http://wl.ledutec.com/pdf/'.$post_data
                ];
            }else{
                return [
                    'code' => '500',
                    'msg' => '生成pdf出错'
                ];
            }
        }else{
            return [
                'code' => '500',
                'msg' => '数据错误'
            ];
        }








    }

    //查询余额
    public function getLastPrice(Request $request){
        $sign = $request -> sign;
        $sign = base64_decode($sign);
        $user_pass = explode('&&',$sign);
        //代理的username
        $username = $user_pass[0];
        $userinfo = DB::table('admin_users') -> where([
            'username' => $username
        ]) -> first();
        $area_info = DB::table('area_name') -> where([
            'id' => $userinfo -> from_area
        ]) -> first();

        return [
            'code' => '200',
            'msg' => $area_info -> price
        ];
    }



    //包裹删除
    public function deletePackage(Request $request){
        //包裹单号
        $package_num = trim($request -> package_num);
        if(!$package_num){
            return [
                'code' => '500',
                'msg' => '包裹单号'
            ];
        }

        $sign = $request -> sign;
        $sign = base64_decode($sign);
        $user_pass = explode('&&',$sign);
        //代理的username
        $username = $user_pass[0];
        $userinfo = DB::table('admin_users') -> where([
            'username' => $username
        ]) -> first();

        //看下这个包裹 是不是这个区域的
        //先看下这个包裹存不存在
        $package_info = DB::table('packages') -> where([
            'flag' => 0,
            'package_num' => $package_num
        ]) -> first();
        if(!$package_info){
            return [
                'code' => '500',
                'result' => '没有此包裹'
            ];
        }



        $order_info = DB::table('order') -> where([
            'flag' => 0,
            'id' => $package_info -> order_id,
            'from_area' => $userinfo -> from_area
        ]) -> first();

        if(!$order_info){
            return [
                'code' => '500',
                'result' => '没有此包裹'
            ];
        }

        //假如已发货 则不允许删除
        if($package_info -> wuliu_num){
            return [
                'code' => '500',
                'result' => '已发货不允许删除,请执行取消单号操作'
            ];
        }


        //如果删除的是最后一个包裹 则让他执行删除订单操作
        $count_package = DB::table('packages') -> where([
            'order_id' => $package_info -> order_id,
            'flag' => 0
        ]) -> count();
        if($count_package == 1){
            return [
                'code' => '500',
                'result' => '请执行删除订单操作'
            ];
        }


        //执行删除操作
        $model_order = new Order();

        $model_order -> deletePackage($package_info,$order_info);

        return [
            'code' => '200',
            'result' => 'success'
        ];



    }


    //订单删除
    public function deleteOrder(Request $request){
        $order_id = trim($request -> order_id);

        if(!$order_id){
            return [
                'code' => '500',
                'msg' => '订单号必填'
            ];
        }

        $sign = $request -> sign;
        $sign = base64_decode($sign);
        $user_pass = explode('&&',$sign);
        //代理的username
        $username = $user_pass[0];
        $userinfo = DB::table('admin_users') -> where([
            'username' => $username
        ]) -> first();

        $order_info = DB::table('order') -> where([
            'order_num' => trim($order_id),
            'flag' => 0,
            'from_area' => $userinfo -> from_area
        ]) -> first();

        if(!$order_info){
            return [
                'code' => '500',
                'msg' => '无此订单号 '.$order_id
            ];
        }


        //
        //如果 订单下 有物流单号 则不允许删除
        $package_info = DB::table('packages') -> where('order_id','=',$order_info -> id) -> where('wuliu_num','<>','') -> where('flag','=',0) -> first();

        if($package_info && ($order_info -> status == 2 || $order_info -> status == 3) ){
            //不允许删除
            return [
                'code' => '500',
                'msg' => '不允许删除此订单，因为有已发货的订单'
            ];
        }



        //开始执行删除订单操作
        $model_order = new Order();
        $model_order -> deleteOrder($order_info);
        return [
            'code' => '200',
            'msg' => 'success'
        ];
    }

    //生成pdf（包裹）
    public function makePackagePdfFile(Request $request){
        //传递order_id
        //用逗号分割的order_id
        $package_ids = trim($request -> package_ids);
        if(!$package_ids){
            return [
                'code' => '500',
                'msg' => '包裹号必填'
            ];
        }

        $sign = $request -> sign;
        $sign = base64_decode($sign);
        $user_pass = explode('&&',$sign);
        //代理的username
        $username = $user_pass[0];
        $userinfo = DB::table('admin_users') -> where([
            'username' => $username
        ]) -> first();


        $packages_arr = explode(',',$package_ids);
        $temp_str = '';
        foreach($packages_arr as $vo){

            $packages = DB::table('packages') -> where([
                'package_num' => trim($vo),
                'flag' => 0
            ])-> where('wuliu_num','<>','') -> first();


            if(!$packages){
                return [
                    'code' => '500',
                    'msg' => '没有此包裹号 '.$vo
                ];
            }
            //看下此包裹是不是他的
            $order_info = DB::table('order') -> where([
                'id' => $packages -> order_id,
                'flag' => 0
            ]) -> first();
            if($order_info -> from_area != $userinfo -> from_area){
                return [
                    'code' => '500',
                    'msg' => '没有此包裹号 '.$vo
                ];
            }
            $expressNo = $packages -> wuliu_num;
            $temp_str .= $expressNo.',';

        }




        if($temp_str){
            $model_order = new Order();
            $post_data = $model_order -> makePdf($temp_str);
            if($post_data && $post_data != 'error'){
                return [
                    'code' => '200',
                    'msg' => 'http://wl.ledutec.com/pdf/'.$post_data
                ];
            }else{
                return [
                    'code' => '500',
                    'msg' => '生成pdf出错'
                ];
            }
        }else{
            return [
                'code' => '500',
                'msg' => '数据错误'
            ];
        }








    }

    //修改包裹重量
    public function updatePackageWeight(Request $request){
        $package_num = trim($request -> package_num);
        $weight = round(floatval($request ->weight),2);
        if(!$package_num || !$weight){
            return [
                'code' => '500',
                'msg' => '包裹单号、重量必填'
            ];
        }

        $sign = $request -> sign;
        $sign = base64_decode($sign);
        $user_pass = explode('&&',$sign);
        //代理的username
        $username = $user_pass[0];
        $userinfo = DB::table('admin_users') -> where([
            'username' => $username
        ]) -> first();



        //先看下 这个包裹 是不是他的

        $package_info = DB::table('packages') -> where([
            'flag' => 0,
            'package_num' => $package_num
        ]) -> first();
        if(!$package_info || !$package_info -> wuliu_num){
            return [
                'code' => '500',
                'msg' => '没有此包裹编号'
            ];
        }


        $order_info = DB::table('order') -> where([
            'id' => $package_info -> order_id,
            'flag' => 0,
            'from_area' => $userinfo -> from_area
        ]) -> first();
        if(!$order_info){
            return [
                'code' => '500',
                'msg' => '没有此包裹编号'
            ];
        }

        if($package_info -> weight == $weight){
            return [
                'code' => '500',
                'msg' => '没有修改'
            ];
        }

        $model = new Order();
        //修改包裹重量
        $update_res = $model -> updateWeight($package_info,$order_info,$weight);
        if($update_res['res'] == 'success'){
            return [
                'code' => '200',
                'msg' => 'success'
            ];
        }else{
            return [
                'code' => '500',
                'msg' => $update_res['msg']
            ];
        }




    }























    //生成面单
    public function makeOrder(Request $request){
        if($request->method() == 'POST'){
            $json_data = $request -> json_data;
            json_decode($json_data);
            if(json_last_error()){
                return [
                    'code' => '500',
                    'result' => 'json error'
                ];
            }
            $post_send_data = json_decode($json_data,true);
            $post_data = $this -> post('http://webapi.rongtong-group.com/api/order/PostOrder',$post_send_data,[
                'Content-Type:application/json',
                "Authorization: Basic ".base64_encode("NN100:N123456:672890e7bc70a3a4b8e067caa3c1305c")
            ]);
            $str = str_replace(array("/r/n", "/r", "/n",'<br/>'), '', $post_data);
            echo $str;
        }else{
            return [
                'code' => '500',
                'result' => 'method error'
            ];
        }
    }


    //删除包裹
    public function deleteApiData(){
        $code = $_GET['code'];
        $get_data = $this -> getData('http://webapi.rongtong-group.com/api/order/DeleteOrderCode?Code='.$code,[
            'Content-Type:application/json',
            "Authorization: Basic ".base64_encode("NN100:N123456:672890e7bc70a3a4b8e067caa3c1305c")
        ]);
        echo $get_data;
    }




    public  function post($url, $post_data = '',$headers = []){
        header("Content-type:text/html;charset=utf-8");
        $ch = curl_init();
        curl_setopt ($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS,json_encode($post_data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            return  curl_error($ch);
        }
        curl_close($ch);
        return $result;
    }


    public function getData($url,$headers = []){
        header("Content-type:text/html;charset=utf-8");
        $ch = curl_init();
        curl_setopt ($ch, CURLOPT_URL, $url);
        //curl_setopt ($ch, CURLOPT_POST, 1);

        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $file_contents = curl_exec($ch);
        if (curl_errno($ch)) {
            return  curl_error($ch);
        }
        curl_close($ch);
        return $file_contents;
    }

    function create_uuid($prefix = ""){    //可以指定前缀
        $str = md5(uniqid(mt_rand(), true));
        $uuid  = substr($str,0,8) . '-';
        $uuid .= substr($str,8,4) . '-';
        $uuid .= substr($str,12,4) . '-';
        $uuid .= substr($str,16,4) . '-';
        $uuid .= substr($str,20,12);
        return $prefix . $uuid;
    }



    //打印任务请求
    public function getPrintFiles(){
        //Log::info(print_r($_REQUEST,true));
        if(!isset($_POST['printer_num'])){
            echo json_encode([
                'code' => '500',
                'msg' => 'parameter_error',
                'data' => ''
            ]);
            exit;
        }
        $info = DB::table('print_sequeue') -> where([
            'result' => 0,
            'printer_num' => $_POST['printer_num']
        ]) -> limit(1) -> get();

        if(!count($info)){
            echo json_encode([
                'code' => '500',
                'msg' => 'nofile',
                'data' => ''
            ]);
        }else{
            $data = [];
            foreach($info as $vo){
                $data[$vo -> id] = $vo -> file_name;
            }


            echo json_encode([
                'code' => 200,
                'msg' => 'success',
                'data' => $data,
                //'long' => 220,
                'long' => $info[0] -> long,
                //'width' => 110,
                'width' => $info[0] -> width,
                'dir' => $info[0] -> dir, // 1 横 0 竖

                'marginLeft' => $info[0] -> marginLeft,
                'marginRight' => $info[0] -> marginRight,
                'marginTop' => $info[0] -> marginTop,
                'marginBottom' => $info[0] -> marginBottom,
                'print_name' => $info[0] -> print_name

            ]);
        }
    }

    //打印结束请求
    public function printEnd(){
        if(!isset($_POST['printer_num']) || !isset($_POST['id'])){
            echo json_encode([
                'code' => '500',
                'msg' => 'parameter_error',
            ]);
            exit;
        }

        $res = DB::table('print_sequeue') -> where([
            'id' => $_POST['id'],
            'printer_num' => $_POST['printer_num']
        ]) -> update([
            'updated_at' => time(),
            'result' => 1
        ]);
        if($res){
            echo json_encode([
                'code' => '200',
                'msg' => 'success'
            ]);
        }else{
            echo json_encode([
                'code' => '500',
                'msg' => 'delete_error'
            ]);
        }

    }


    //trackMore 推送地址
    public function trackingMoreHook(){
        $inputJSON = file_get_contents("php://input");
        $json_decode_data = json_decode($inputJSON,true);
        $verify_code = $this -> verify($json_decode_data['verifyInfo']['timeStr'],'leo.li@fenith.com',$json_decode_data['verifyInfo']['signature']);
        if($verify_code == 1){
            //Log::info(1118);
            //Log::info($inputJSON);
            //通过这里做更新
            if($json_decode_data['meta']['code'] == 200){
                $saveJson = json_encode($json_decode_data['data']);
                //更新
                DB::table('packages') -> where([
                    'flag' => 0 ,
                    'wuliu_num' => $json_decode_data['data']['tracking_number']
                ]) -> update([
                    'updated_at' => time(),
                    'trackingMoreJson' => $saveJson
                ]);

                Log::info($json_decode_data['data']['tracking_number'].' trackingMore');
            }


        }
        //Log::info($inputJSON);
    }

    function verify($timeStr,$useremail,$signature){
        $hash="sha256";
        $result=hash_hmac($hash,$timeStr,$useremail);
        return strcmp($result,$signature)==0?1:0;
    }


    function loadpic(){

        $pic = DB::table('erp_product_list')->where('image','like','%ali_oss:sku_img%')->pluck('image')->toArray();
        $new = [];
        foreach ($pic as $v){
            $new[] = str_replace('https://fenithcdn.oss-cn-shanghai.aliyuncs','http://cdn.fenith',getImageUrl($v));
        }
        view('welcome');
        view('home/pic')->with(
            ['pic' => $new]
        );

    }
}
