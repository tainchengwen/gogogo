<?php

namespace App\Http\Controllers\Home;

use App\MallApi;
use EasyWeChat\Factory;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MallController extends Controller
{

    public function stockPrice($encryptionLevel)
    {
        // var_dump(base64_encode(base64_encode('d'))); // WkE9PQ
        // var_dump(base64_encode(base64_encode('c'))); // WXc9PQ
        // var_dump(base64_encode(base64_encode('b'))); // WWc9PQ
        // var_dump(base64_encode(base64_encode('a'))); // WVE9PQ
        // exit;
        $level = base64_decode(base64_decode($encryptionLevel));

        $trueLevel = in_array($level, ['a','b','c','d'], true) ? $level : 'd' ;

        //取所有分类
        $class_name = DB::table('mall_class') -> get();

        return view('home.mall.stockVIP')->with([
            'class_name' => $class_name,
            'level' => $level
            //'request_config' => $request_config
        ]);
    }

    //ajax动态获取商品列表
    public function ajaxGetStockVIPInfo($level)
    {
        $business_id_arr = [49];

        if(isset($_POST['keyword']) && $_POST['keyword']){
            $keyword = $_POST['keyword'];
        }else{
            $keyword = '';
        }
        $data = DB::table('erp_stock')
            -> leftJoin('erp_warehouse','erp_stock.warehouse_id','erp_warehouse.id')
            -> leftJoin('erp_product_list','erp_stock.product_id','erp_product_list.id')
            -> leftJoin('erp_product_price','erp_product_list.id','erp_product_price.product_id')
            -> leftJoin('erp_mp_name','erp_product_price.mp_name_id','erp_mp_name.id')
            //商品去找类别、品牌、系列
            //类别
            -> leftJoin('erp_product_class as product_class','erp_product_list.class_id','product_class.id')
            //品牌
            -> leftJoin('erp_product_class as product_brand','erp_product_list.brand_id','product_brand.id')
            //系列
            -> leftJoin('erp_product_class as product_series','erp_product_list.series_id','product_series.id')
            -> select([
                'erp_product_list.product_no as ProductNo',
                'erp_product_list.product_name as PartNumber',
                'erp_product_list.image',
                'erp_warehouse.name as erp_warehouse_name',
                'erp_stock.can_buy_num',
                'erp_product_price.price_'.$level.' as price',
                'erp_mp_name.mp_name'
            ])
            -> where('erp_stock.can_buy_num','>',0)

            -> where('erp_product_price.flag', 0)
            -> where('erp_mp_name.is_show', 1)
            -> whereNotNull('erp_mp_name.mp_name')

            -> whereIn('erp_stock.business_id',$business_id_arr)
            -> where(function($query)use($keyword){
                $query -> where('erp_product_list.product_name','like','%'.trim($keyword).'%') -> orWhere('erp_product_list.product_no',trim($keyword));
            })
            -> orderBy('product_class.id','asc')
            -> orderBy('product_brand.id','asc')
            -> orderBy('product_series.id','asc')
            -> orderBy('erp_stock.warehouse_id','desc')
            -> orderBy('erp_stock.store_house_id','desc')
            // -> orderBy('product_class.name','asc')
            // -> orderBy('product_brand.name','asc')
            // -> orderBy('product_series.name','asc')
            -> orderBy('erp_stock.updated_at','desc')
            -> offset($_POST['page']?$_POST['page']:0)
            -> paginate(15);

        foreach($data as $k => $vo){
            $data[$k] -> product_img = getImageUrl($vo -> image);
        }

        echo json_encode($data);
    }


    //商城首页
    public function index(){

        $userid = session('home_user_id');
        if(isset($_GET['mmmmmm'])){
          $userid = 41;
          session([
             'home_user_id' => 41
          ]);
        }
        $config = env('APP_ENV');
        if(!$userid &&  $config != 'local'){
            //回去回调用
            $config = [
                'app_id' => env('WECHAT_OFFICIAL_ACCOUNT_APPID'),
                'secret' => env('WECHAT_OFFICIAL_ACCOUNT_SECRET'),
                // ...
                'oauth' => [
                    'scopes'   => ['snsapi_userinfo'],
                    'callback' => '/redirect',
                ],
                // ..
            ];

            $app = Factory::officialAccount($config);
            $oauth = $app->oauth;
            session([
                'target_url' => url()->full()
            ]);
            return $oauth->redirect(url('redirect'));
        }


        if(!isset($_GET['from'])){
            $request_config = [
                'url_type' => isset($_GET['url_type'])?$_GET['url_type']:1,
            ];

            session([
                'request_config' => $request_config
            ]);
        }


        //查下此人有没有绑定
        $business_info = DB::table('erp_business')
            -> where([
                'flag' => 0,
            ]) -> get();
        //获取有事业部的数组
        $user_str = [];
        foreach($business_info as $vo){
            if(isset($vo -> user_id) && $vo -> user_id){
                $temp = explode(',',$vo -> user_id);
                foreach($temp as $value){
                    $user_str[] = $value;
                }
            }
        }
        Log::info(json_encode($user_str));


        $show = false;
        foreach($user_str as $vo){
            if($vo == $userid){
                $show = true;
                break;
            }
        }






        if(!$show){
            echo "<div style='width:100%;height:100%;text-align: center'><h1>施工中</h1></div>";exit;
        }








        //取所有分类
        $class_name = DB::table('mall_class') -> get();
        return view('home.mall.index')->with([
            'class_name' => $class_name,
            //'request_config' => $request_config
        ]);
    }



    //ajax动态获取商品列表
    public function ajaxGetInfo(){
        $userid = session('home_user_id');
        //看这个人是那个事业部的
        if(!$userid && env('APP_ENV') != 'local'){
            exit;
        }

        if(env('APP_ENV') == 'local'){
            $userid = 41;
        }


        //查下此人有没有绑定
        $business_info = DB::table('erp_business')
            -> where([
                'flag' => 0,
            ]) -> get();
        //获取有事业部的数组
        $business_id_arr = [];
        foreach($business_info as $vo){
            if($vo -> user_id){
                $temp = explode(',',$vo -> user_id);
                foreach($temp as $value){
                    if($value == $userid){
                        $business_id_arr[] =   $vo -> id;
                    }
                }
            }
        }



        if(isset($_POST['keyword']) && $_POST['keyword']){
            $keyword = $_POST['keyword'];
        }else{
            $keyword = '';
        }
        $data = DB::table('erp_stock')
            -> leftJoin('erp_warehouse','erp_stock.warehouse_id','erp_warehouse.id')
            -> leftJoin('erp_product_list','erp_stock.product_id','erp_product_list.id')
            //商品去找类别、品牌、系列
            //类别
            -> leftJoin('erp_product_class as product_class','erp_product_list.class_id','product_class.id')
            //品牌
            -> leftJoin('erp_product_class as product_brand','erp_product_list.brand_id','product_brand.id')
            //系列
            -> leftJoin('erp_product_class as product_series','erp_product_list.series_id','product_series.id')
            -> select([
                'erp_product_list.product_no as ProductNo',
                'erp_product_list.product_name as PartNumber',
                'erp_product_list.image',
                'erp_warehouse.name as erp_warehouse_name',
                'erp_stock.can_buy_num'
            ])
            -> where('erp_stock.can_buy_num','>',0)
            -> whereIn('erp_stock.business_id',$business_id_arr)
            -> where(function($query)use($keyword){
                $query -> where('erp_product_list.product_name','like','%'.trim($keyword).'%') -> orWhere('erp_product_list.product_no',trim($keyword));
            })

            -> orderBy('erp_stock.warehouse_id','desc')
            -> orderBy('erp_stock.store_house_id','desc')

            -> orderBy('product_class.name','asc')
            -> orderBy('product_brand.name','asc')
            -> orderBy('product_series.name','asc')

            -> orderBy('erp_stock.updated_at','desc')
            -> offset($_POST['page']?$_POST['page']:0)
            -> paginate(15);



        foreach($data as $k => $vo){
            $data[$k] -> product_img = getImageUrl($vo -> image);
        }



        echo json_encode($data);
    }

    //购物车
    public function car(){
        //return view('home.mall.car2');
        $config = env('APP_ENV');
        $userid = session('home_user_id');
        if($config == 'local'){
            $userid = 44;
        }

        if(!$userid &&  $config != 'local'){
            //回去回调用
            $config = [
                'app_id' => env('WECHAT_OFFICIAL_ACCOUNT_APPID'),
                'secret' => env('WECHAT_OFFICIAL_ACCOUNT_SECRET'),
                // ...
                'oauth' => [
                    'scopes'   => ['snsapi_userinfo'],
                    'callback' => '/redirect',
                ],
                // ..
            ];

            $app = Factory::officialAccount($config);
            $oauth = $app->oauth;
            session([
                'target_url' => url('mall/car')
            ]);
            return $oauth->redirect(url('redirect'));
        }

        $product_no_res = [];
        $product_no_res2 = [];
        $number_arr = [];
        $number_arr2 = [];
        if(isset($_GET['product_no_str'])){
            //商城配置
            $request_config = session('request_config');
            $url_type_config = config('admin.url_type')[$request_config['url_type']];


            $product_no_arr = explode(',',$_GET['product_no_str']);
            $number_arr = explode(',',$_GET['number_str']);
            $model = new MallApi();


            foreach($product_no_arr as $key => $vo){
                /*
                $data = $model -> getGoodsList(1,1,[
                    'ProductNo' => $vo
                ]);
                if(!isset($data[0])){
                    continue;
                }

                $product_no_res[] = $data[0];
                */


                //把他要的东西 加入购物车
                //首先看下有没有这个东西
                $isset = DB::table('mall_car') -> where([
                    'user_id' => $userid,
                    'product_no' => $vo,
                    'url_type' => $request_config['url_type']
                ]) -> first();
                //如果有 就增加
                //dd($number_arr);
                if($isset){
                    DB::table('mall_car') -> where([
                        'user_id' => $userid,
                        'product_no' => $vo,
                        'url_type' => $request_config['url_type']
                    ]) -> increment('number',$number_arr[$key]);
                }else{
                    DB::table('mall_car') -> insert([
                        'url_type' => $request_config['url_type'],
                        'user_id' => $userid,
                        'product_no' => $vo,
                        'number' => $number_arr[$key],
                        'created_at' => time(),
                        'updated_at' => time(),
                    ]);
                }
                //dd($data);
            }
            //$product_no_res = $this -> getPublicData($product_no_res);
            return redirect('mall/car');
        }

        //dd($product_no_res);





        if(!$userid &&  $config != 'local'){
            //回去回调用
            $config = [
                'app_id' => env('WECHAT_OFFICIAL_ACCOUNT_APPID'),
                'secret' => env('WECHAT_OFFICIAL_ACCOUNT_SECRET'),
                // ...
                'oauth' => [
                    'scopes'   => ['snsapi_userinfo'],
                    'callback' => '/redirect',
                ],
                // ..
            ];

            $app = Factory::officialAccount($config);
            $oauth = $app->oauth;
            session([
                'target_url' => url('mall/car')
            ]);
            return $oauth->redirect(url('redirect'));
        }

        //找到user_id 之后。把购物车里的东西拿出来
        if($userid){
            $car = DB::table('mall_car') -> where([
                'user_id' => $userid
            ]) -> get();
            if($car){
                //查此人的商城等级
                $user_info = DB::table('wxuser') -> where([
                    'id' => $userid
                ]) -> first();
                $market_class = $user_info -> market_class;

                $url_type = [];
                //dd($car);
                $model = new MallApi();
                $car_id = [];
                foreach($car as $vo){
                    if(strpos($vo -> product_no,'M') === 0){
                        //如果是从M 开头的 就是套餐商品
                        $temp_merge_goods = DB::table('merge_goods') -> where([
                            'product_no' => $vo -> product_no,
                            'flag' => 0,
                            'status' => 1
                        ]) -> first();
                        if(!$temp_merge_goods){
                            continue;
                        }

                        //构造数组
                        $temp_arr['id'] = '9999'.$temp_merge_goods -> id;
                        $temp_arr['product_img'] = asset('uploads/'.$temp_merge_goods -> image);;
                        $temp_arr['weight'] = $temp_merge_goods -> weight;
                        $temp_arr['PartNumber'] = $temp_merge_goods -> product_name;
                        $temp_arr['ProductNo'] = $temp_merge_goods -> product_no;
                        //根据他的等级 显示价格
                        if($market_class == 4){
                            $temp_arr['Price'] = $temp_merge_goods -> price_s;
                        }elseif($market_class == 3){
                            $temp_arr['Price'] = $temp_merge_goods -> price_a;
                        }elseif($market_class == 2){
                            $temp_arr['Price'] = $temp_merge_goods -> price_b;
                        }elseif($market_class == 1){
                            $temp_arr['Price'] = $temp_merge_goods -> price_c;
                        }else{
                            $temp_arr['Price'] = $temp_merge_goods -> price_d;
                        }

                        $product_no_res2[] = $temp_arr;

                        //拼接car的id
                        $car_id[] = $vo -> id;
                        //拼接 url_type
                        $url_type[] = $vo -> url_type;
                        //$product_no_res2[] = $data[0];
                        $number_arr2[] = $vo -> number;
                        continue;
                    }


                    $data = $model -> getGoodsList(1,1,[
                        'ProductNo' => $vo->product_no
                    ]);
                    if(!isset($data[0])){
                        continue;
                    }






                    //$data[0] 一维数组变2维数组
                    $temp[] = $data[0];
                    $temp_res = $this -> getPublicData($temp,$vo -> url_type);
                    unset($temp);
                    if(count($temp_res)){
                        //Log::info('super111222:'.print_r($temp_res,true));
                        $product_no_res2[] = $temp_res[0];
                    }else{
                        continue;
                    }

                    //拼接car的id
                    $car_id[] = $vo -> id;
                    //拼接 url_type
                    $url_type[] = $vo -> url_type;


                    //$product_no_res2[] = $data[0];
                    $number_arr2[] = $vo -> number;
                }



                //$product_no_res2 = $this -> getPublicData($product_no_res2);
                foreach($product_no_res2 as $k => $vo){
                    $product_no_res2[$k]['id'] = $car_id[$k];
                    $product_no_res2[$k]['url_type'] = $url_type[$k];
                    $product_no_res2[$k]['buy_number'] = $number_arr2[$k];
                }

                //将数组 转换成 一个店铺 一列数据
                $product_no_res2_temp = [];

                foreach($product_no_res2 as $k => $vo){
                    //$vo_temp_url_type = $config_url_type[$vo['url_type']]['name'];
                    $product_no_res2_temp[$vo['url_type']][] = $vo;
                }

                $product_no_res2 = $product_no_res2_temp;


            }

        }

        //店铺名称为索引
        $config_url_type = config('admin.url_type');
        return view('home.mall.car')->with([
            'res' => $product_no_res2,
            'number' => $number_arr2,
            'config_url_type' => $config_url_type
        ]);




    }

    //删除购物车
    public function delCar(){
        $config = env('APP_ENV');
        $userid = session('home_user_id');
        if($config == 'local'){
            $userid = 44;
        }
        if(!isset($_GET['spIds']) || !$_GET['spIds']){
            exit;
        }

        //解析spIds
        $spIds = explode(',',$_GET['spIds']);
        $spIds_arr = array_chunk($spIds,2);
        //检查数组 哪个里边不是2个元素 就exit
        foreach($spIds_arr as $vo){
            if(count($vo) != 2){
                exit;
            }
        }

        foreach($spIds_arr as $vo){
            $car_id = trim($vo[0]);
            DB::table('mall_car') -> where([
                'user_id' => $userid,
                'id' => $car_id
            ]) -> delete();
            /*
            $temp_product_id = trim($vo[0]);
            //删除此人的商品编号
            DB::table('mall_car') -> where([
                'user_id' => $userid,
                'product_no' => $temp_product_id
            ]) -> delete();
            */
        }

        return redirect('mall/car');


    }


    //第二次处理数据
    //Organization 事业部

    function getPublicData($data,$url_type = 1,$from=''){
        //取图片地址
        $userid = session('home_user_id');
        $env_config = env('APP_ENV');
        if($env_config == 'local'){
            $userid = 41;
        }

        if(!$userid){
            return false;
        }

        //查此人的商城等级
        $user_info = DB::table('wxuser') -> where([
            'id' => $userid
        ]) -> first();
        $market_class = $user_info -> market_class;

        //Log::info(66666666);
        //Log::info(print_r($data,true));


        foreach($data as $k => $vo){
            $data[$k]['product_img'] = '';
            $data[$k]['weight'] = 0;
            if(isset($vo['ProductNo']) && $vo['ProductNo']){
                //看 此商品的goods_id
                $goods_info = DB::table('goods_list') -> where([
                    'product_id' => $vo['ProductNo']
                ]) -> first();
                if(!$goods_info){
                    unset($data[$k]);
                    continue;
                }
                $price_info = '';
                //如果来自我的订单 不需要判断url_type
                if($from !='myorder'){
                    //如果有此商品 看下在此url_type中 是否上架
                    $price_info = DB::table('goods_price_temp') -> where([
                        'url_type' => $url_type,
                        'goods_id' => $goods_info -> id,
                        'flag' => 0
                    ]) -> first();
                    if(!$price_info || $price_info -> status != 1){
                        unset($data[$k]);
                        continue;
                    }
                }

                if($from == 'myorder'){
                    $price_info = DB::table('goods_price_temp') -> where([
                        'goods_id' => $goods_info -> id
                    ]) -> first();

                    Log::info(print_r($price_info,true));

                }





                //上架的 都是可以用的。
                $data[$k]['FPrice'] = 0;
                $data[$k]['product_img'] = '';
                if(isset($goods_info -> image) && $goods_info -> image){
                    $data[$k]['product_img'] = asset('uploads/'.$goods_info -> image);
                }

                if(isset($goods_info -> weight) && $goods_info -> weight){
                    $data[$k]['weight'] = $goods_info -> weight;
                }

                if(!$price_info){
                    continue;
                }

                //看 用特价 还是普通价格
                if($price_info -> s_starttime && $price_info -> s_endtime && $price_info -> s_price_s && $price_info -> s_price_a && $price_info -> s_price_b && $price_info -> s_price_c && $price_info -> s_price_d  ){
                    if(time() >= $price_info -> s_starttime && time() <= $price_info -> s_endtime){
                        $price_info -> price_s = $price_info -> s_price_s;
                        $price_info -> price_a = $price_info -> s_price_a;
                        $price_info -> price_b = $price_info -> s_price_b;
                        $price_info -> price_c = $price_info -> s_price_c;
                        $price_info -> price_d = $price_info -> s_price_d;
                    }
                }

                //s等级
                if($market_class == 4 && isset($price_info -> price_s) && $price_info -> price_s){
                    $data[$k]['Price'] = $price_info -> price_s;
                    //$data[$k]['FPrice'] = isset($temp_data -> price_c)?$temp_data -> price_c:0;
                }
                //a等级
                if($market_class == 3 && isset($price_info -> price_a) && $price_info -> price_a){
                    $data[$k]['Price'] = $price_info -> price_a;
                    //$data[$k]['FPrice'] = isset($temp_data -> price_c)?$temp_data -> price_c:0;
                }
                //b等级
                if($market_class == 2  && isset($price_info -> price_b) && $price_info -> price_b){
                    $data[$k]['Price'] = $price_info -> price_b;
                    $data[$k]['FPrice'] = isset( $price_info -> price_a)? $price_info -> price_a:0;
                    if(isset(session('request_config')['url_type']) && session('request_config')['url_type'] == 3){
                        $data[$k]['Fimage'] = isset( $price_info -> price_a)? asset('mall/img/vip3_blue.png'):'';
                    }elseif(isset(session('request_config')['url_type']) && session('request_config')['url_type'] == 2){
                        $data[$k]['Fimage'] = isset( $price_info -> price_a)? asset('mall/img/vip3_ouzhou.png'):'';
                    }elseif(isset(session('request_config')['url_type']) && session('request_config')['url_type'] == 5){
                        $data[$k]['Fimage'] = isset( $vo -> price_a)? asset('mall/img/vip3_aozhou.png'):'';
                    }else{
                        $data[$k]['Fimage'] = isset( $price_info -> price_a)? asset('mall/img/vip3.png'):'';
                    }
                }

                //c等级
                if($market_class == 1 && isset($price_info -> price_c) && $price_info -> price_c){
                    $data[$k]['Price'] = $price_info -> price_c;
                    $data[$k]['FPrice'] = isset( $price_info -> price_b)? $price_info -> price_b:0;
                    if(isset(session('request_config')['url_type']) && session('request_config')['url_type'] == 3){
                        $data[$k]['Fimage'] = isset( $price_info -> price_b)? asset('mall/img/vip2_blue.png'):'';
                    }elseif(isset(session('request_config')['url_type']) && session('request_config')['url_type'] == 2){
                        $data[$k]['Fimage'] = isset( $price_info -> price_b)? asset('mall/img/vip2_ouzhou.png'):'';
                    }elseif(isset(session('request_config')['url_type']) && session('request_config')['url_type'] == 5){
                        $data[$k]['Fimage'] = isset( $vo -> price_b)? asset('mall/img/vip2_aozhou.png'):'';
                    }else{
                        $data[$k]['Fimage'] = isset( $price_info -> price_b)? asset('mall/img/vip2.png'):'';
                    }

                }
                // d等级
                if($market_class == 0 && isset($price_info -> price_d) && $price_info -> price_d){
                    $data[$k]['Price'] = $price_info -> price_d;
                    $data[$k]['FPrice'] = isset( $price_info -> price_c)? $price_info -> price_c:0;

                    if(isset(session('request_config')['url_type']) && session('request_config')['url_type'] == 3){
                        $data[$k]['Fimage'] = isset( $price_info -> price_c)? asset('mall/img/vip1_blue.png'):'';
                    }elseif(isset(session('request_config')['url_type']) && session('request_config')['url_type'] == 2){
                        $data[$k]['Fimage'] = isset( $price_info -> price_b)? asset('mall/img/vip1_ouzhou.png'):'';
                    }elseif(isset(session('request_config')['url_type']) && session('request_config')['url_type'] == 5){
                        $data[$k]['Fimage'] = isset( $vo -> price_c)? asset('mall/img/vip1_aozhou.png'):'';
                    }elseif(isset(session('request_config')['url_type']) && session('request_config')['url_type'] == 6){
                        $data[$k]['Fimage'] = isset( $vo -> price_c)? asset('mall/img/vip1_riben.png'):'';
                    }else{
                        $data[$k]['Fimage'] = isset( $price_info -> price_c)? asset('mall/img/vip1.png'):'';
                    }


                }
                if(!isset($data[$k]['Price'])){
                    unset($data[$k]);
                    continue;
                }


                $data[$k]['Price'] = sprintf('%.1f', floatval($data[$k]['Price']));
                //Log::info('fffffff:'.$data[$k]['Price']);
                $data[$k]['FPrice'] = sprintf('%.1f', floatval($data[$k]['FPrice']));

            }else{
                unset($data[$k]);
                continue;
            }
        }

        $data = array_values($data);
        return $data;
    }




    public function center(){
        $userid = session('home_user_id');

        $config_env = env('APP_ENV');
        if($config_env == 'local'){
            $userid = 44;
        }
        $config = [
            'app_id' => env('WECHAT_OFFICIAL_ACCOUNT_APPID'),
            'secret' => env('WECHAT_OFFICIAL_ACCOUNT_SECRET'),
            'mch_id'             => '1501525601',
            // ...
            'oauth' => [
                'scopes'   => ['snsapi_userinfo'],
                'callback' => '/redirect',
            ],
            // ..
        ];
        $app = Factory::officialAccount($config);
        if(!$userid &&  $config_env != 'local'){
            //回去回调用

            $oauth = $app->oauth;
            session([
                'target_url' => url('mall/center')
            ]);
            return $oauth->redirect(url('redirect'));
        }

        $userinfo = DB::table('wxuser') -> where([
            'id' => $userid
        ]) -> first();
        //dd($userinfo);
        return view('home.mall.center') -> with([
            'userinfo' => $userinfo
        ]);

    }

    //我的订单
    public function myorder(){
        $userid = session('home_user_id');
        if(isset($_GET['ttttttt']) && $_GET['ttttttt']){
            $userid = $_GET['ttttttt'];
            session([
                'home_user_id' => $_GET['ttttttt']
            ]);
        }
        $config_env = env('APP_ENV');
        $config = [
            'app_id' => env('WECHAT_OFFICIAL_ACCOUNT_APPID'),
            'secret' => env('WECHAT_OFFICIAL_ACCOUNT_SECRET'),
            'mch_id'             => '1501525601',
            // ...
            'oauth' => [
                'scopes'   => ['snsapi_userinfo'],
                'callback' => '/redirect',
            ],
            // ..
        ];
        $app = Factory::officialAccount($config);
        if(!$userid &&  $config_env != 'local'){
            //回去回调用

            $oauth = $app->oauth;
            session([
                'target_url' => url('mall/myorder')
            ]);
            return $oauth->redirect(url('redirect'));
        }

        $model= new MallApi();
        $data = $model -> getOrderList($userid);

        if(isset($_GET['ttttttt']) && $userid == $_GET['ttttttt']){
            Log::info(3453456534);
            Log::info(print_r($data,true));
            //Log::info(999993);
        }

        //查找每个订单的商品
        foreach($data as $k => $vo){
            $temp_detail = $model -> getOrderDetail($vo['SaleID']);

            $temp_detail = $this -> getPublicData($temp_detail,null,'myorder');
            if(isset($_GET['ttttttt']) && $userid == $_GET['ttttttt']){
                Log::info(print_r($temp_detail,true));
            }


            //dd($temp_detail);
            $data[$k]['order_detail'] = $temp_detail;
        }

        if(isset($_GET['ttttttt']) && $userid == $_GET['ttttttt']){
            Log::info(99999);
        }

        //dd($data);
        return view('home.mall.myorder') -> with([
            'data' => $data
        ]);
    }

    //如果成为会员
    public function howtouser(){
        return view('home.mall.howtouser');
    }

    //支付
    public function submitbuy(){
        $userid = session('home_user_id');
        $config_env = env('APP_ENV');
        $config = [
            'app_id' => env('WECHAT_OFFICIAL_ACCOUNT_APPID'),
            'secret' => env('WECHAT_OFFICIAL_ACCOUNT_SECRET'),
            'mch_id'             => '1501525601',
            // ...
            'oauth' => [
                'scopes'   => ['snsapi_userinfo'],
                'callback' => '/redirect',
            ],
            // ..
        ];
        $app = Factory::officialAccount($config);
        if(!isset($_GET['spIds']) || !$_GET['spIds']){
            exit;
        }
        if(!$userid &&  $config_env != 'local'){
            //回去回调用

            $oauth = $app->oauth;
            session([
                'target_url' => url('mall/submitbuy').'?spIds='.$_GET['spIds']
            ]);
            return $oauth->redirect(url('redirect'));
        }

        session([
            'mall_spIds' => $_GET['spIds']
        ]);


        if($config_env != 'local'){
            $payment = Factory::payment($config);

            $jssdk = $payment->jssdk;
        }else{
            $jssdk = [];
        }


        //解析spIds
        $spIds = explode(',',$_GET['spIds']);
        $spIds_arr = array_chunk($spIds,2);
        //检查数组 哪个里边不是2个元素 就exit
        foreach($spIds_arr as $vo){
            if(count($vo) != 2){
                exit;
            }
        }

        //从智能地址点进来
        $user_address = '';
        if(isset($_GET['address_id']) && $_GET['address_id']){
            //查下此人地址
            $user_address = DB::table('user_address') -> where([
                'id' => $_GET['address_id'],
                'user_id' => $userid
            ]) -> first();
        }



        //检查一下 库存够不够


        //通过product_id 取商品
        $model_api = new MallApi();
        $mall_res = [];
        $car_number = [];
        //数量
        $mall_numbers = [];
        //每个包裹的重量
        $goods_weight = [];
        $price_all = 0;
        //购物车里每个商品 所属的商城
        $url_type = [];
//查此人的商城等级
        $user_info = DB::table('wxuser') -> where([
            'id' => $userid
        ]) -> first();
        $market_class = $user_info -> market_class;

        foreach($spIds_arr as $vo){
            $car_id = trim($vo[0]);
            $car_info = DB::table('mall_car') -> where([
                'id' => $car_id
            ]) -> first();
            Log::info('888888888');
            Log::info(print_r($car_info,true));
            $temp_product_id = $car_info -> product_no;
            //如果 product_no里包含 M 则 是套餐商品
            if(strpos($temp_product_id,'M') === 0){
                //判断套餐内的每个库存 够不够
                //如果是从M 开头的 就是套餐商品
                $temp_merge_goods = DB::table('merge_goods') -> where([
                    'product_no' => $temp_product_id,
                    'flag' => 0,
                    'status' => 1
                ]) -> first();
                if(!$temp_merge_goods){
                    continue;
                }


                //拆开套餐商品 看套餐中的 每个商品 是否有库存
                $merge_goods_detail = DB::table('merge_goods_detail') -> where([
                    'merge_goods_id' => $temp_merge_goods -> id,
                    'flag' => 0
                ]) -> get();
                if(!count($merge_goods_detail)){
                    continue;
                }

                //查看每个套餐商品 是否有库存
                foreach($merge_goods_detail as $value){
                    $product_info = $model_api -> getGoodsList(1,1,[
                        'ProductNo' => $value -> product_no
                    ]);
                    //判断库存够不够
                    //注意 这里的库存比较，的是 商品数量*套餐内商品数量
                    if(!isset($product_info[0]) || intval($product_info[0]['CanSaleQty']) < intval($vo[1]) * intval($value -> number) ){
                        return redirect('mall/car') -> with('back_res',$temp_product_id.'下单数量不能超过库存数量!');
                    }
                }






                $temp_arr['id'] = '9999'.$temp_merge_goods -> id;
                $temp_arr['product_img'] = asset('uploads/'.$temp_merge_goods -> image);;
                $temp_arr['weight'] = $temp_merge_goods -> weight;
                $temp_arr['PartNumber'] = $temp_merge_goods -> product_name;
                $temp_arr['ProductNo'] = $temp_merge_goods -> product_no;
                //根据他的等级 显示价格

                if($market_class == 4){
                    $temp_arr['Price'] = $temp_merge_goods -> price_s;
                }elseif($market_class == 3){
                    $temp_arr['Price'] = $temp_merge_goods -> price_a;
                }elseif($market_class == 2){
                    $temp_arr['Price'] = $temp_merge_goods -> price_b;
                }elseif($market_class == 1){
                    $temp_arr['Price'] = $temp_merge_goods -> price_c;
                }else{
                    $temp_arr['Price'] = $temp_merge_goods -> price_d;
                }

                $mall_res[] = $temp_arr;

                //购物车的数量
                $car_number[] = $vo[1];
                //每件商品的重量
                $goods_weight[] = $temp_merge_goods -> weight;
                //每个商城type
                $url_type[] = $car_info -> url_type;
                //数量
                $mall_numbers[] = intval($vo[1]);
                continue;


            }



            //$temp_product_id = $vo[0];
            //通过商品编号 取 商品详情
            $product_info = $model_api -> getGoodsList(1,1,[
                'ProductNo' => $temp_product_id
            ]);
            Log::info(print_r($product_info,true));
            //判断库存够不够
            if(intval($product_info[0]['CanSaleQty']) < intval($vo[1])){
                return redirect('mall/car') -> with('back_res',$temp_product_id.'下单数量不能超过库存数量!');
            }




            //先构造二维数组
            $temp[] = $product_info[0];
            $temp_res = $this -> getPublicData($temp,$car_info -> url_type);
            Log::info('sss11111');
            Log::info(print_r($temp_res,true));
            unset($temp);
            if(count($temp_res)){
                $mall_res[] = $temp_res[0];
            }else{
                continue;
            }


            //$goods_weight
            //$mall_res[] = $product_info[0];

            //购物车的数量
            $car_number[] = $vo[1];
            //每件商品的重量
            $goods_weight[] = $temp_res[0]['weight'];
            //每个商城type
            $url_type[] = $car_info -> url_type;

            //数量
            $mall_numbers[] = intval($vo[1]);
            //$price_all += floatval($product_info[0]['Price']) * intval($vo[1]);
            //dd($product_info)
        }
        //dump($mall_res);
        //$mall_res = $this -> getPublicData($mall_res);
        //计算总价
        //数组中拼接url_type car_number
        foreach($mall_res as $k => $vo){
            //购物车中的数量
            $mall_res[$k]['car_number'] = $car_number[$k];
            //这里需要添加 此商品 所属的商城
            $mall_res[$k]['url_type'] = $url_type[$k];
            //每类商品的总重量
            $mall_res[$k]['weight_all'] =$goods_weight[$k];


            $price_all += $mall_res[$k]['Price'] * $mall_numbers[$k];
        }






        Log::info(print_r($mall_res,true));
        //总价格

        //dd($mall_res);


        //dd($spIds_arr);
        //dd(json_encode($mall_res));




        //$configForPickAddress = $jssdk->shareAddressConfig($token);


        return view('home.mall.submitbuy')->with([
            'jssdk' => $jssdk,
            'app' => $app,
            'config_env' => $config_env,
            'mall_res' => $mall_res,
            'price_all' => $price_all,
            'mall_res_json' => json_encode($mall_res),
            'user_address' => $user_address
        ]);
    }


    //地址列表
    public function checkAddress(){
        $userid = session('home_user_id');
        $config_env = env('APP_ENV');
        if($config_env == 'local'){
            $userid = 41;
        }



        //展开此人地址
        $address = DB::table('user_address') ->  where([
            'user_id' =>$userid,
            'type' => 1
        ]) -> orderBy('id','desc') -> get();
        return view('home.mall.checkAddress') -> with([
            'address' => $address,
            'return' => isset($_GET['return'])?$_GET['return']:''
        ]);


    }

    public function addAddress(){
        return view('home.mall.addAddress');
    }

    public function addAddressRes(){
        $userid = session('home_user_id');
        $config_env = env('APP_ENV');
        if($config_env == 'local'){
            $userid = 41;
        }

        $triggerIpt = explode(',',$_POST['triggerIpt']);
        //区分省市区
        $province = isset($triggerIpt[0])?$triggerIpt[0]:'';
        $city = isset($triggerIpt[1])?$triggerIpt[1]:'';
        $country = isset($triggerIpt[2])?$triggerIpt[2]:'';

        //查看 地址中 是否包含 省、市、区
        if($province && !strstr($_POST['address'],$province)){
            //地址中必须含有省
        }
        if($city && !strstr($_POST['address'],$city)){
            //地址中必须含有市
        }
        if($country && !strstr($_POST['address'],$country)){
            //地址中必须含有地区
        }

        //保存地址
        $id = DB::table('user_address') -> insertGetId([
            'user_id' => $userid,
            'name' => $_POST['name'],
            'tel' => $_POST['tel'],
            'address' => $_POST['address'],
            'address_code' => $_POST['valueToIpt'],

            'province' => $province,
            'city' => $city,
            'country' => $country,

            'type' => 1,
            'created_at' => time(),
            'updated_at' => time(),

        ]);
        if($id){
            echo 'success';
        }else{
            echo 'error';
        }
    }

    //编辑地址
    public function editAddress(){
        $userid = session('home_user_id');
        $config_env = env('APP_ENV');
        if($config_env == 'local'){
            $userid = 41;
        }

        $id = $_GET['id'];
        $address = DB::table('user_address') -> where([
            'id' => $id,
            'user_id' => $userid
        ]) -> first();
        if(!$address){
            exit;
        }

        if($address -> province && $address -> city && $address -> country){
            $address_value = $address -> province.','.$address -> city.','.$address -> country;
        }else{
            $address_value = $address -> province.','.$address -> city;
        }

        return view('home.mall.editAddress') -> with([
            'address' => $address,
            //拼写地址
            'address_value' => $address_value
        ]);

    }

    public function editAddressRes(){
        $userid = session('home_user_id');
        $config_env = env('APP_ENV');
        if($config_env == 'local'){
            $userid = 41;
        }

        $id = $_POST['id'];

        $triggerIpt = explode(',',$_POST['triggerIpt']);
        //区分省市区
        $province = isset($triggerIpt[0])?$triggerIpt[0]:'';
        $city = isset($triggerIpt[1])?$triggerIpt[1]:'';
        $country = isset($triggerIpt[2])?$triggerIpt[2]:'';


        DB::table('user_address') -> where([
            'id' => $id,
            'user_id' => $userid,
            'type' => 1
        ]) -> update([
            'name' => $_POST['name'],
            'tel' => $_POST['tel'],
            'address' => $_POST['address'],
            'address_code' => $_POST['valueToIpt'],

            'province' => $province,
            'city' => $city,
            'country' => $country,
            'updated_at' => time(),
        ]);

        echo 'success';

    }

    //删除
    public function delAddress(){
        $userid = session('home_user_id');
        $config_env = env('APP_ENV');
        if($config_env == 'local'){
            $userid = 41;
        }
        DB::table('user_address') -> where([
            'id' => $_GET['id'],
            'user_id' => $userid,
            'type' => 1
        ]) -> delete();
        return redirect('mall/checkAddress');
    }


    //智能地址分割
    public function getExtAddress(){
        $address = trim($_POST['address']);
        $address = preg_replace('# #','',$address);
        if($address){
            $model = new MallApi();
            $res = $model -> getAddressBySF($address);

            if(!$res){
                return [
                    'code' => '500',
                    'res' => 'error'
                ];
            }
            echo $res;
        }else{
            return [
                'code' => '500',
                'res' => 'error'
            ];
        }
    }

    //根据总价格、 发货人、收货人 获取快递费
    public function getKuaiDiPrice(){
        //echo json_encode($_POST);exit;
        $price_all = $_POST['price_all'];
        $province_input = mb_substr(trim($_POST['province']),0,2,'utf-8');
        $goods_list = json_decode(htmlspecialchars_decode($_POST['json_data']),true);
        $userid = session('home_user_id');
        Log::info('market:getKuaidi:'.$userid);
        //计算总重量

        $zong_weight = 0;
        foreach($goods_list as $vo){
            $temp = intval($vo['car_number'])*floatval($vo['weight_all']);
            $zong_weight += $temp;
        }


        //纸箱子的重量
        if($zong_weight <= 0.7){
            $zong_weight += 0.3;
        }elseif($zong_weight > 0.7 && $zong_weight <= 1.5){
            $zong_weight += 0.5;
        }else{
            $zong_weight += 0.7;
        }
        Log::info('zong_weight:'.$zong_weight);

        /*
        $yunfei_info = DB::table('freight_temp_name') -> where([
            'id' => 1
        ]) -> first();
        */
        $yunfei_detail = DB::table('freight_temp') -> where([
            'temp_name_id' => 1,
        ]) -> where('address','like','%'.$province_input.'%') -> first();

        //看是否超过首重
        if($zong_weight > $yunfei_detail -> first_weight){
            //看超了多少重量
            $ext_weight = ceil($zong_weight - $yunfei_detail -> first_weight);
            //看超了的重量里边 有几个续重
            $num_ext_weight = ceil($ext_weight/$yunfei_detail->secend_weight);


            //续重费 = 续重费数量 * 续重费
            $ext_price = $num_ext_weight * $yunfei_detail -> secend_price;


            $price_res = $ext_price + $yunfei_detail -> first_price;
        }else{
            //没有超过首重 快递费就是首重费
            $price_res = $yunfei_detail -> first_price;
        }
        Log::info('price_res:'.$price_res);
        echo $price_res;

    }




    //提交订单处理
    public function  sendOrderRes(){
        header("Content-type: text/html; charset=utf-8");
        $model = new MallApi();

        $userid = session('home_user_id');
        $config_env = env('APP_ENV');
        $config = [
            'app_id' => env('WECHAT_OFFICIAL_ACCOUNT_APPID'),
            'secret' => env('WECHAT_OFFICIAL_ACCOUNT_SECRET'),
            'mch_id'             => '1501525601',
            // ...
            'oauth' => [
                'scopes'   => ['snsapi_userinfo'],
                'callback' => '/redirect',
            ],
            // ..
        ];
        $app = Factory::officialAccount($config);
        if(!$userid &&  $config_env != 'local'){
            //回去回调用

            $oauth = $app->oauth;
            session([
                'target_url' => url('mall/center')
            ]);
            return $oauth->redirect(url('redirect'));
        }

        $user_info = DB::table('wxuser') -> where([
            'id' => $userid
        ]) -> first();
        $market_class = $user_info -> market_class;





        //计算api所用 商品列表
        $goods_list = json_decode(htmlspecialchars_decode($_POST['order_json']),true);
        $SaleDetailList = [];
        //总价
        $DeliveryFee = 0;
        $DeliveryFee_arr = [];
        //运费
        $yunfei = $_POST['kuaidi']?$_POST['kuaidi']:0 ;

        if(!$yunfei){
            echo json_encode([
                'code' => 'error',
                'result' => '数据有误，请联系客服处理',
            ]);
        }



        //根据有几个商城 看下几单 $goods_list 里边 包含所有的数据
        //处理之后 通过url_type 分组的数据
        $url_type_arr = [];
        Log::info('999999999begin');
        Log::info(print_r($goods_list,true));

        //一个商城一单
        foreach($goods_list as $k => $vo){
            //通过url_type 区分数组
            $url_type_arr[$vo['url_type']][] = $vo;
            //此商品的总价
            $DeliveryFee_temp = intval($vo['car_number']) * floatval($vo['Price']);

            //通过url_type 计算总价
            if(isset($DeliveryFee_arr[$vo['url_type']])){
                $DeliveryFee_arr[$vo['url_type']] = $DeliveryFee_arr[$vo['url_type']] + $DeliveryFee_temp;
            }else{
                $DeliveryFee_arr[$vo['url_type']] = $DeliveryFee_temp;
            }


        }

        //处理结束 有几个url_type 就下几次单

        Log::info(print_r($url_type_arr,true));

        foreach($url_type_arr as $key => $vo){


            if(in_array($key,[4,5,6])){
                //澳洲 德国 免邮
                $yunfei = 0;
            }

            $SaleDetailList = [];
            $merge_goods_arr = [];
            $merge_number_arr = [];
            //vo 是其中一个url_typ 的 数组
            foreach($vo as $k => $v){
                //整理下单数据
                //$v 是 一种商品。
                //在这里判断 $v 是商品 还是套餐
                if(strpos($v['ProductNo'],'M') === 0){
                    //是套餐
                    //寻找套餐内的商品
                    $merge_goods_info = DB::table('merge_goods') -> where([
                        'product_no' => $v['ProductNo'],
                        'flag' => 0,
                        'status' => 1
                    ]) -> first();
                    if(!$merge_goods_info){
                        continue;
                    }


                    $merge_goods_detail = DB::table('merge_goods_detail') -> where([
                        'merge_goods_id' => $merge_goods_info -> id,
                        'flag' => 0
                    ]) -> get();
                    if(!count($merge_goods_detail)){
                        continue;
                    }


                    //如果有套餐商品 则拆分
                    foreach($merge_goods_detail as $value){
                        //套餐全部商品 数组
                        $merge_goods_arr[] = $value;
                        //套餐商品数量
                        $merge_number_arr[] = $v['car_number'];
                    }




                }else{
                    $SaleDetailList[$k]['ProductNo'] = $v['ProductNo'];
                    $SaleDetailList[$k]['Qty'] = $v['car_number'];
                    $SaleDetailList[$k]['Price'] = $v['Price'];
                }



            }

            //如果有merge_goods_arr 则 把 merge_goods_arr 拼入 SaleDetailList
            $SaleDetailList = array_values($SaleDetailList);
            if(count($merge_goods_arr)){
                foreach($merge_goods_arr as $k_temp => $value_temp){
                    //取出此人的价格
                    if($market_class == 4){
                        $price = $value_temp -> price_s;
                    }elseif($market_class == 3){
                        $price = $value_temp -> price_a;
                    }elseif($market_class == 2){
                        $price = $value_temp -> price_b;
                    }elseif($market_class == 1){
                        $price = $value_temp -> price_c;
                    }else{
                        $price = $value_temp -> price_d;
                    }


                    $sale_temp = [
                        'ProductNo' => $value_temp -> product_no,
                        'Qty' => $merge_number_arr[$k_temp] * $value_temp -> number,
                        'Price' => $price
                    ];
                    $SaleDetailList[] = $sale_temp;
                }
            }

            Log::info(print_r($SaleDetailList,true));
            //这单的总价是 $DeliveryFee_arr[$key]



            //根据url_type 取出事业部 仓库
            $url_type_config = config('admin.url_type')[$key];
            Log::info(print_r($url_type_config,true));
            $res = $model -> makeOrder([
                //事业部
                'OrganizationName' => $url_type_config['Organization'],
                //仓库
                'WarehouseName' => $url_type_config['WarehouseName'],
                'DeliveryFee' => $yunfei,

                'SaleDetailList' => $SaleDetailList,
                //'DeliveryFee' => $DeliveryFee_arr[$key],
                //客户名称
                'CustomerName' => $userid,
                //收货人
                'ReceiveConsignee' => $_POST['name_input'],
                'ReceiveProvince' => $_POST['province_input'],
                'ReceiveCity' => $_POST['city_input'],
                'ReceiveDistrict' => $_POST['country_input'],
                'ReceiveAddress' => $_POST['address_input'],
                'ReceiveMobile' => $_POST['tel_input'],
                'Remark' => $_POST['remark'],
            ]);
            Log::info('999999999end');
            if($res['code'] == 'success'){
                //提交订单结束后 把商品删除购物车
                foreach($SaleDetailList as $value){
                    DB::table('mall_car') -> where([
                        'user_id' => $userid,
                        'url_type' => $key,
                        'product_no' => $value['ProductNo']
                    ])->delete();
                }

                echo json_encode([
                    'code' => 'success',
                    'result' => $res['result'],
                ]);

            }else{

                echo json_encode([
                    'code' => 'error',
                    'result' => trim($res['result'],'"'),
                ]);
            }

            /*
            if($res){
                //提交订单结束后 把商品删除购物车
                foreach($SaleDetailList as $value){
                    DB::table('mall_car') -> where([
                        'user_id' => $userid,
                        'url_type' => $key,
                        'product_no' => $value['ProductNo']
                    ])->delete();
                }

                echo json_encode([
                    'code' => 'success',
                    'result' => $res,
                ]);

                //echo $res;
            }else{

                echo json_encode([
                    'code' => 'error',
                    'result' =>
                ]);

                echo 'error';
            }
            */


        }


        /*
        foreach($goods_list as $k => $vo){
            $SaleDetailList[$k]['ProductNo'] = $vo['ProductNo'];
            $SaleDetailList[$k]['Qty'] = $vo['car_number'];
            $SaleDetailList[$k]['Price'] = $vo['Price'];
            $DeliveryFee += intval($vo['car_number']) * floatval($vo['Price']);
        }
        */







        /*
        $res = $model -> makeOrder([
            'SaleDetailList' => $SaleDetailList,
            'DeliveryFee' => $DeliveryFee,
            //客户名称
            'CustomerName' => $userid,
            //收货人
            'ReceiveConsignee' => $_POST['name_input'],
            'ReceiveProvince' => $_POST['province_input'],
            'ReceiveCity' => $_POST['city_input'],
            'ReceiveDistrict' => $_POST['country_input'],
            'ReceiveAddress' => $_POST['address_input'],
            'ReceiveMobile' => $_POST['tel_input'],
            'Remark' => $_POST['remark'],
        ]);
        */


        /*
        if($res){
            //提交订单结束后 把商品删除购物车
            foreach($goods_list as $k => $vo){
                DB::table('mall_car') -> where([
                    'user_id' => $userid,
                    'product_no' => $vo['ProductNo']
                ])->delete();
            }
            echo $res;
        }else{
            echo 'error';
        }
        */

        //Log::info(print_r($_POST,true));
    }


    //提交订单 选择支付方式
    public function payOrder(){
        //通过编号获取商品
        $order_id = trim($_GET['order_id'],'\"');
        $model = new MallApi();
        $order_info = $model -> getOrderInfo($order_id);
        $order_detail = $model -> getOrderDetail($order_id);
        //dd($order_info);
        return view('home.mall.payOrder')->with([
            'order_info' => $order_info,
            'order_detail' => $order_detail
        ]);
    }

    //支付宝-微信 支付
    public function payMethod(){
        $order_info = [
            'SaleID' => $_GET['SaleID'],
            'Payable' => $_GET['Payable'],
            'number' => $_GET['number'],
        ];

        if($_GET['value'] == '2'){
            //微信
            $type = 1;
        }else{
            $type = 0;
        }

        $imgs = DB::table('payimage') -> where([
            'type' => $type
        ]) -> first();
        if($type == 1){
            $typename = '微信';
        }else{
            $typename = '支付宝';
        }


        return view('home.mall.payMethod')->with([
            'order_info' => $order_info,
            'typename' => $typename,
            'image' => asset('uploads/'.$imgs -> image)
        ]);
    }

    //余额支付接口
    public function payApi(){
        header("Content-type:text/html;charset=utf-8");
        $config = env('APP_ENV');
        $userid = session('home_user_id');
        if($config == 'local'){
            $userid = 41;
        }

        //判断余额够不够
        $userinfo = DB::table('wxuser') -> where([
            'id' => $userid
        ]) -> first();
        $model = new MallApi();
        $order_info = $model -> getOrderInfo($_POST['SaleID']);
        if(!$order_info){
            echo 'error';exit;
        }

        if(floatval($order_info['Payable']) > floatval($userinfo -> price)){
            //余额不足
            echo 'noprice';exit;
        }

        //默认够 直接请求接口
        if(isset($_POST['SaleID']) && $_POST['SaleID']){
            //调用接口
            $model = new MallApi();

            $res = $model -> payOrder($_POST['SaleID'],$order_info['Payable']);

            if(strpos($res,'保存成功')){
                //扣除余额
                DB::table('wxuser') -> where([
                    'id' => $userid
                ]) -> update([
                    'price' => floatval($userinfo -> price) - floatval($order_info['Payable'])
                ]);

                $userinfo = DB::table('wxuser') -> where([
                    'id' => $userid
                ]) -> first();

                //扣除余额添加日志记录
                //添加日志
                DB::table('price_log') -> insert([
                    'userid' => $userid,
                    'price' => floatval($order_info['Payable']),
                    'type' => 10,
                    'created_at' => time(),
                    'updated_at' => time(),
                    'from_user_id' => 0,
                    'in_out' => 1 ,// 0收入1支出
                    'end_price' => $userinfo -> price
                ]);


                //计算返点
                //看下他的推荐人
                if($userinfo -> from_userid){
                    //推荐人的信息
                    $userinfo_from_user = DB::table('wxuser') -> where([
                        'id' => $userinfo -> from_userid
                    ]) -> first();

                    //根据等级 取他的返点比例
                    $config_bili = config('admin.market_bili');
                    $bili_num = $config_bili[$userinfo_from_user -> market_class];
                    if($bili_num){
                        $fan_price = floatval($bili_num) * floatval($order_info['Payable']);
                        //返点
                        DB::table('price_log') -> insert([
                            'userid' => $userinfo_from_user -> id,
                            'price' => $fan_price,
                            'type' => 6,
                            'created_at' => time(),
                            'updated_at' => time(),
                            'from_user_id' => $userid,
                            'in_out' => 0 , // 0收入1支出
                            'end_price' => floatval($userinfo_from_user -> fandian) + $fan_price
                        ]);

                        //给他加返的钱
                        DB::table('wxuser') -> where([
                            'id' => $userinfo_from_user -> id
                        ]) -> update([
                            'fandian' => floatval($userinfo_from_user -> fandian) + $fan_price
                        ]);

                        //返点 通知他

                        $config = [
                            'app_id' => env('WECHAT_OFFICIAL_ACCOUNT_APPID'),
                            'secret' => env('WECHAT_OFFICIAL_ACCOUNT_SECRET'),
                        ];

                        $app = Factory::officialAccount($config);

                        $res = $app->template_message->send([
                            'touser' => $userinfo_from_user -> openid,
                            'template_id' => 'RmayJqYux-RGgY5n_4T7mKuzIJyTkXGBvDLmcQdD4dE',
                            'url' => url('market'),
                            'data' => [
                                'first' => '到账提醒',
                                'keyword1' => date('Y-m-d H:i'),
                                'keyword2' => '¥' . round($fan_price,2),
                                'keyword3' => $_POST['SaleID'],
                                'keyword4' => '商城返点',
                                'remark' => '感谢您的使用'
                            ]
                        ]);



                    }



                }




                echo 'success';
            }else{
                echo 'error';
            }
        }else{
            echo 'error';
        }



    }


    //订单详情
    public function orderdetail(){
        $config = env('APP_ENV');
        $userid = session('home_user_id');
        if($config == 'local'){
            $userid = 44;
        }

        if(!$userid &&  $config != 'local'){
            //回去回调用
            $config = [
                'app_id' => env('WECHAT_OFFICIAL_ACCOUNT_APPID'),
                'secret' => env('WECHAT_OFFICIAL_ACCOUNT_SECRET'),
                // ...
                'oauth' => [
                    'scopes'   => ['snsapi_userinfo'],
                    'callback' => '/redirect',
                ],
                // ..
            ];

            $app = Factory::officialAccount($config);
            $oauth = $app->oauth;
            session([
                'target_url' => url('mall/orderdetail').'?order_id='.$_GET['order_id']
            ]);
            return $oauth->redirect(url('redirect'));
        }

        $model = new MallApi();
        $order_info = $model -> getOrderInfo($_GET['order_id']);
        $temp_detail = $model -> getOrderDetail($_GET['order_id']);
        //找到图片


        foreach($temp_detail as $k => $vo){
            Log::info(print_r($vo,true));
            $temp_detail[$k]['product_img'] = '';
            $temp_detail[$k]['weight'] = 0;
            if(isset($vo['ProductNo']) && $vo['ProductNo']){
                //看 此商品的goods_id
                $goods_info = DB::table('goods_list') -> where([
                    'product_id' => $vo['ProductNo']
                ]) -> first();
                if(!$goods_info){
                    unset($temp_detail[$k]);
                    continue;
                }

                if(isset($goods_info -> image) && $goods_info -> image){
                    $temp_detail[$k]['product_img'] = asset('uploads/'.$goods_info -> image);
                }



            }
        }


        //查看物流
        $wuliu_info = $model -> getWuliu($_GET['order_id']);
        if(count($wuliu_info)){
            $wuliu_info = $wuliu_info[0];
            //物流的照片
            $wuliu_image = 'http://yx.fenith.com/File/KuaiDi/'.date('Y').'/'.$wuliu_info['LogisticsNo'].'.jpg';
            $wuliu_no = $wuliu_info['LogisticsNo'];
            $wuliu_company = $wuliu_info['LogisticsCompany'];
        }else{
            $wuliu_image = '';
            $wuliu_no = '';
            $wuliu_company = '';
        }

        //$order_detail = $this -> getPublicData($temp_detail);



        //dd($order_info);
        return view('home.mall.orderdetail')->with([
            'order_info' => $order_info,
            'order_detail' => $temp_detail,


            'wuliu_info' => $wuliu_info,
            'wuliu_image' => $wuliu_image,
            'wuliu_no' => $wuliu_no,
            'wuliu_company' => $wuliu_company,

        ]);


    }


    //下单
    public function makeOrder(){
        $model = new MallApi();
        $res = $model -> makeOrder([]);
        dd($res);
    }








}
