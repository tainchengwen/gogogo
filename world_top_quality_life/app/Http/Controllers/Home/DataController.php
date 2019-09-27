<?php

namespace App\Http\Controllers\Home;

use EasyWeChat\Factory;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class DataController extends Controller
{
    //
    public function index(){
        $userid = session('home_user_id');
        $config = env('APP_ENV');
        if($config != 'local'){
            if(!$userid){
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
        }else{
            $userid = 41;
        }


        $userinfo = DB::table('wxuser') -> where([
            'id' => $userid
        ]) -> first();
        $class_config = config('admin.user_class');
        $userinfo -> class_name = $class_config[$userinfo ->class];


        //查看is_look是0的数量
        $count_look = DB::table('order') -> where([
            'userid' => $userid,
            'is_look' => 0
        ]) -> count();

        return view('home.mydata') -> with([
            'userinfo' => $userinfo,
            'count_look' => $count_look
        ]);
    }

    //余额明细
    public function table(){

        //根据get筛选条件
        if(isset($_GET['type'])){
            //1 是收入 2 支出
            if($_GET['type'] == 1){
                $types = [1,2];
            }
            if($_GET['type'] == 2){
                $types = [0,3];

            }

        }else{
            $types = [0,1,2,3];
        }


        $userid = session('home_user_id');
        $config = env('APP_ENV');
        if($config != 'local'){
            if(!$userid){
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
                    'target_url' => url('priceTable')
                ]);
                return $oauth->redirect(url('redirect'));
            }
        }else{
            $userid = 41;
        }


        $price_log = DB::table('price_log') -> where([
            'userid' => $userid
        ]) -> whereIn('type',$types)-> orderBy('created_at','desc') ->  get();
        //配置
        $configs = config('admin.price_log_type');
        foreach($price_log as $k => $vo){
            $price_log[$k] -> type_name = $configs[$vo -> type];
            if($vo -> in_out == 0){
                //收入
                $price_log[$k] -> price = '+'.$vo -> price;
            }else{
                $price_log[$k] -> price = '-'.$vo -> price;
            }
        }


        return view('home.account_table') -> with([
            'log' => $price_log
        ]);
    }
}
