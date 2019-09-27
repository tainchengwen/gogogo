<?php
/**
 * Created by PhpStorm.
 * User: richer
 * Date: 2018/11/21
 * Time: 13:15
 */

namespace App\Http\Controllers\Home;


use App\ReturnPoint;
use App\ReturnShop;
use EasyWeChat\Factory;
use Illuminate\Support\Facades\DB;

class ReturnPointController
{
    //返点首页
    public function index(){
        $userid = session('home_user_id');
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


        $return_shops = ReturnShop::all();

        $return_shop_res = [];
        foreach($return_shops as $k => $vo){
            $return_shop_res[$vo -> city_id][$k] = $vo;
        }


        //dd($return_shop_res);
        return view('home.return.index') -> with([
            'data' => $return_shop_res
        ]);


    }


    //返点填写页面
    public function writePage(){
        $userid = session('home_user_id');
        $check_value = $_GET['check_value'];
        $shop_info = ReturnShop::find($check_value);
        $money_config = config('admin.money_type');

        $return_info = '';
        if(isset($_GET['return_id'])){
            $return_info = ReturnPoint::find($_GET['return_id']);
        }


        return view('home.return.writePage') -> with([
            'userid' => sprintf("%06d", $userid),
            'shop_info' => $shop_info,
            'money_config' => $money_config,
            'return_info' => $return_info
        ]);
    }

    //提交返点数据
    public function subData(){
        $userid = session('home_user_id');
        $config = env('APP_ENV');
        if($config == 'local'){
            $userid = 41;
        }
        if($_POST['file_name'] && $_POST['shop_id'] && $_POST['price'] && $_POST['numbers']){
            $shop_info = ReturnShop::find($_POST['shop_id']);
            if($_POST['return_id']){
                //更新
                DB::table('return_point') -> where([
                    'id' => $_POST['return_id'],

                ]) -> update([
                    'shop_id' => $_POST['shop_id'],
                    //实付款
                    'price' => round(floatval($_POST['price']),2),
                    //应返款
                    'fan_price' => round(floatval($shop_info -> bili) * floatval($_POST['price']) * 0.01,2) ,
                    'numbers' => $_POST['numbers'],
                    'image' => $_POST['file_name'],
                    'updated_at' => time(),
                    'user_id' => $userid,
                    'status' => 0,
                ]);


            }else{
                //新增
                DB::table('return_point') -> insertGetId([
                    'shop_id' => $_POST['shop_id'],
                    //实付款
                    'price' => round(floatval($_POST['price']),2),
                    //应返款
                    'fan_price' => round(floatval($shop_info -> bili) * floatval($_POST['price']) * 0.01,2) ,
                    'numbers' => $_POST['numbers'],
                    'image' => $_POST['file_name'],
                    'created_at' => time(),
                    'updated_at' => time(),
                    'user_id' => $userid
                ]);
            }





            return [
                'code' => 'success',
                'msg' => ''
            ];
        }else{
            return [
                'code' => 'error',
                'msg' => ''
            ];
        }

    }


    //返点列表
    public function returnList(){
        $userid = session('home_user_id');
        $config = env('APP_ENV');
        if($config == 'local'){
            $userid = 41;
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
                'target_url' => url()->full()
            ]);
            return $oauth->redirect(url('redirect'));
        }
        $where['user_id'] = $userid;
        if(isset($_GET['type']) && $_GET['type'] == 2){
            $where['status'] = 0;
        }

        if(isset($_GET['type']) && $_GET['type'] == 3){
            $where['status'] = 1;
        }
        //查找此人的返点列表
        $return_list = DB::table('return_point')
            -> join('return_shops','return_point.shop_id','return_shops.id')
            -> where($where)
            -> select('return_point.*','return_shops.shop_name')
            -> get();

        //dd($return_list);
        return view('home.return.returnList') -> with([
            'data' => $return_list,
        ]);


    }


    public function look(){
        $id = $_GET['id'];
        $return_point = ReturnPoint::find($id);
        $return_shop = ReturnShop::find($return_point -> shop_id);
        return view('home.return.look') -> with([
            'info' => $return_point,
            'return_shop' => $return_shop
        ]);
    }

}