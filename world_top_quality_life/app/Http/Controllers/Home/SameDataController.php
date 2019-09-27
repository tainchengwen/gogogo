<?php

namespace App\Http\Controllers\Home;

use App\MallApi;
use EasyWeChat\Factory;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SameDataController extends Controller
{
    //同步某url_type 的数据
    public function sameGoodsData($type){


        $config = config('admin.url_type');
        $configs = $config[$type];

        $model = new MallApi();
        $zong = 0;

        //把 每次跑api的商品 ID记录下来
        $api_arr = [];

        for($i=1;$i<=1000;$i++){
            $data = $model -> getGoodsList($i,15,[
                'Organization' => $configs['Organization'],
                'WarehouseName' => $configs['WarehouseName'],
            ]);

            //var_dump($data);
            if(!count($data)){
                //Log::info('market_api_res');
                //Log::info(json_encode($api_arr));
                //把 不在这几条 $api_arr  范围之内的 商品 下架掉
                $temp = [];
                //先找到所有商品的id
                $goods_ids = DB::table('goods_list') -> whereIn('product_id',$api_arr) -> pluck('id');
                foreach ($goods_ids as $vo) {
                    $temp[] = $vo;
                }

                DB::table('goods_price_temp') -> where([
                    'url_type' => $type,
                    'flag' => 0
                ]) -> whereNotIn('goods_id',$temp) -> update([
                    'updated_at' => time(),
                    'status' => 2, //下架,
                    'kucun' => 0
                ]);


                echo $zong.' ';
                echo '同步完毕';
                break;
            }
            $zong += count($data);
            foreach($data as $vo){

                $isset = DB::table('goods_list') -> where([
                    'product_id' => $vo['ProductNo']
                ]) -> first();
                if(!$isset){
                    //如果都不在商品库里 则跳过
                    continue;
                }
                $api_arr[] = $vo['ProductNo'];

                //在商品库
                //查下此Product_no在不在此url_type 中
                $is_price = DB::table('goods_price_temp') -> where([
                    'url_type' => $type,
                    'goods_id' => $isset -> id,
                    'flag' => 0
                ]) -> first();
                if(!$is_price){
                    //如果不存在  则加到待上架中
                    DB::table('goods_price_temp') -> insert([
                        'created_at' => time(),
                        'updated_at' => time(),
                        'url_type' => $type,
                        'goods_id' => $isset -> id,
                        'status' => 0,  //待上架状态
                    ]);
                }else{

                    //看下此商品 在不在套餐商品里 如果在 判断下 是否可以单卖 如果不可以 则直接下架
                    $merge_goods_detail = DB::table('merge_goods_detail') -> where([
                        'product_no' => $vo['ProductNo'],
                        'url_type' => $type,
                        'flag' => 0
                    ]) -> get();
                    if(count($merge_goods_detail)){
                        $can_on_sale = 0;
                        foreach($merge_goods_detail as $value){
                            $merge_goods_id = $value -> merge_goods_id;
                            //假如不可以单卖 就直接下架此商品
                            $merge_goods_info = DB::table('merge_goods') -> where([
                                'id' => $merge_goods_id,
                                'flag' => 0,
                                'status' => 1
                            ]) -> first();
                            if($merge_goods_info && $merge_goods_info -> can_on_sale){
                                //不允许单卖 直接将此商品下架
                                DB::table('goods_price_temp') -> where([
                                    'url_type' => $type,
                                    'goods_id' => $isset -> id,
                                    'flag' => 0
                                ]) -> update([
                                    'status' => 2,  //下架
                                    'updated_at' => time(),
                                    //虽然下架 但是有库存的
                                    'kucun' => 1
                                ]);
                                $can_on_sale = 1;
                                break;
                            }
                        }

                        if($can_on_sale){
                            continue;
                        }

                    }



                    //如果存在 检查下参数是否完整
                    if($is_price -> price_s && $is_price -> price_a && $is_price -> price_b && $is_price -> price_c && $is_price -> price_d && $isset -> weight && $isset -> image ){
                        //Log::info('super:'.$isset -> id.'  '.$isset -> weight);
                        //完整 更新为已上架


                        //update by 1030 取消自动上架功能 只是国内现货取消
                        if(in_array($type,[2,3])){
                            DB::table('goods_price_temp') -> where([
                                'url_type' => $type,
                                'goods_id' => $isset -> id,
                                'flag' => 0
                            ]) -> update([
                                'status' => 1,  //上架状态
                                'kucun' => 1,
                                'updated_at' => time(),
                            ]);
                        }





                    }else{
                        DB::table('goods_price_temp') -> where([
                            'url_type' => $type,
                            'goods_id' => $isset -> id,
                            'flag' => 0
                        ]) -> update([
                            'status' => 0,  //待上架状态
                            'updated_at' => time(),
                        ]);
                    }
                }


//先找到所有商品的id


            }




            sleep(1);



        }




        //判断套餐商品商品 是否上架
        for($i=0;$i<=99999999;$i+=10){
            $merge_goods = DB::table('merge_goods') -> where([
                'flag' => 0
            ]) -> offset($i) -> limit(10) -> get();
            if(!count($merge_goods)){
                exit;
            }
            //Log::info(print_r($merge_goods,true));
            //看下他所有的商品 是否上架
            foreach($merge_goods as $vo){
                //如果 不允许单卖 则不要判断下边了 --- 错误的 如不允许单卖 也要判断是否有库存
                /*
                if($vo -> can_on_sale){
                    continue;
                    DB::table('merge_goods') -> where([
                        'id' => $vo -> id
                    ]) -> update([
                        'status' => 1,
                        'updated_at' => time(),
                    ]);

                    continue;
                }
                */



                if(!$vo -> price_s || !$vo -> price_a || !$vo -> price_b || !$vo -> price_c || !$vo -> price_d || !$vo -> weight || !$vo -> image || !$vo -> product_no || !$vo -> product_name ){
                    //直接将他下架
                    DB::table('merge_goods') -> where([
                        'id' => $vo -> id
                    ]) -> update([
                        'updated_at' => time(),
                        'status' => 0
                    ]);
                    continue;
                }


                //查下此套餐的所有商品
                $merge_goods_detail = DB::table('merge_goods_detail') -> where([
                    'merge_goods_id' => $vo -> id,
                    'flag' => 0
                ]) -> get();
                if(!count($merge_goods_detail)){
                    //如果此套餐内 没有商品 则把他删除
                    DB::table('merge_goods') -> where([
                        'id' => $vo -> id
                    ]) -> update([
                        'updated_at' => time(),
                        'flag' => 1
                    ]);

                    continue;
                }


                //先把它改成上架
                DB::table('merge_goods') -> where([
                    'id' => $vo -> id
                ]) -> update([
                    'updated_at' => time(),
                    'status' => 1
                ]);

                //查下此套餐的商品 是否上架
                foreach($merge_goods_detail as $value){
                    //先 查看product_no 的goods_id
                    $goods_info = DB::table('goods_list') -> where([
                        'product_id' => $value -> product_no
                    ]) -> first();
                    if(!$goods_info){
                        //如果都不在商品库里 则跳过
                        DB::table('merge_goods') -> where([
                            'id' => $vo -> id
                        ]) -> update([
                            'updated_at' => time(),
                            'flag' => 1
                        ]);
                        continue;
                    }


                    //1是不允许 0是允许
                    if($vo -> can_on_sale){
                        //如果不允许单卖 则看下库存有没有就行
                        $goods_info_temp = DB::table('goods_price_temp') -> where([
                            'url_type' => $vo -> url_type,
                            'goods_id' => $goods_info -> id,
                            'flag' => 0,
                        ]) -> first();
                        //如果压根没有商品 或者 没有库存 则直接下架
                        if(!$goods_info_temp){
                            //下架
                            DB::table('merge_goods') -> where([
                                'id' => $vo -> id
                            ]) -> update([
                                'updated_at' => time(),
                                'status' => 0
                            ]);
                        }
                        continue;
                    }

                    //允许单卖的话 判断下是否上架



                    //查看下 在此url_type 中 是否上架
                    /*
                    $goods_info_temp = DB::table('goods_price_temp') -> where([
                        'url_type' => $vo -> url_type,
                        'goods_id' => $goods_info -> id,
                        'flag' => 0,
                        'status' => 1
                    ]) -> first();

                    if(!$goods_info_temp){
                        //下架
                        DB::table('merge_goods') -> where([
                            'id' => $vo -> id
                        ]) -> update([
                            'updated_at' => time(),
                            'status' => 0
                        ]);
                    }else{
                        //有库存 上架
                        DB::table('merge_goods') -> where([
                            'id' => $vo -> id
                        ]) -> update([
                            'updated_at' => time(),
                            'status' => 1
                        ]);
                    }
                    */





                }







            }



        }




    }
}
