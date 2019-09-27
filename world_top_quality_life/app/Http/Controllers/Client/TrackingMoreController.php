<?php

namespace App\Http\Controllers\Client;

use App\BatchPackagesRelation;
use App\Order;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Anchu\Ftp\Facades\Ftp;
use Illuminate\Support\Facades\Log;


//异步调取trackingMore
class TrackingMoreController extends Controller
{

    public function index(){
        if($_POST['sign'] == 's1d1f2g3h4k5'){
            $send_order = DB::table('send_order_list') -> where([
                'id' => $_POST['id']
            ]) -> first();
            $send_order_info = DB::table('batch_packages') -> where([
                'send_order_id' => $_POST['id']
            ]) -> get();
            //交货单内 包裹的数量
            $count_numbers = 0;
            if(count($send_order_info)){
                $packages_ids_big_temp = [];

                //发货单下所有批次
                foreach($send_order_info as $vo){
                    //通过批次 查找所有包裹

                    //找所有包裹 需要到batch_packages_relation
                    $packages = BatchPackagesRelation::where([
                        'batch_id' => $vo -> id
                    ]) -> get();
                    foreach($packages as $value){
                        $packages_ids_big_temp[]  = $value -> package_id;
                        $count_numbers ++ ;
                    }
                }




                /*
                $packages_ids_temp = [];
                foreach($send_order_info as $vo){
                    //把每个package_ids 取出来
                    $packages_ids_temp[] = explode(',',$vo -> package_ids);
                }

                //$packages_ids_temp 组合成一个大数组
                foreach($packages_ids_temp as $value){
                    foreach($value as $value_temp){
                        $count_numbers ++;
                        $packages_ids_big_temp[] = $value_temp;
                    }

                }
                */




                $array_chuck = array_chunk($packages_ids_big_temp,30);
                //制造数组
                $post_arr = [];
                //路线数组
                $route_config = config('admin.route_setting');





                foreach($array_chuck as $key1 => $vo){
                    foreach($vo as $key2 => $value){
                        //dd($value);
                        $package_info = DB::table('packages') -> where([
                            'id' => $value
                        ]) -> first();


                        if(!$package_info){
                            continue;
                        }
                        //如果是xs001 的 过滤掉
                        if($package_info -> route_id == 5 || $package_info -> route_id == 7){
                            continue;
                        }

                        $order_info = DB::table('order') -> where([
                            'id' => $package_info -> order_id
                        ]) -> first();
                        if(!$order_info){
                            continue;
                        }
                        //找线路、找客户代码
                        $from_area_info = DB::table('area_name') -> where([
                            'id' => $order_info -> from_area
                        ]) -> first();




                        $post_arr[$key1][$key2] = [
                            'tracking_number' =>  $package_info -> wuliu_num,
                            'carrier_code' => 'hong-kong-post',
                            //'logistics_channel' => '',
                            'customer_phone' => $package_info -> tel,
                            'customer_name' => $package_info -> name,
                            //'destination_code' => 'cn',
                            'title' => date('m/d',$send_order -> created_at).'('.$count_numbers.')'.$from_area_info -> area_name.'('.$route_config[$package_info->route_id].')',
                            'order_id' => $package_info -> package_num,
                            'comment' => $package_info -> remark,
                            'order_create_time' => date('Y/m/d H:i:s',$package_info -> created_at),
                            "lang" => "en",
                        ];
                    }
                }

                //请求接口
                //40个一组
                $model = new Order();
                foreach($post_arr as $vo){
                    $post_res = $model -> makeMoreNumber($vo);
                    Log::info('trackingMore');
                    Log::info($post_res);
                }






            }

        }
    }




}
