<?php

namespace App\Http\Controllers\Home;

use App\Repertory;
use EasyWeChat\Factory;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

//预约送货
class OrderRepertoryController extends Controller
{
    //
    //送货单预约
    public function home(){
        //微信授权
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
            //$userid = 41;
            session([
                'home_user_id' => 41
            ]) ;
        }

        return view('home.repertory.home');
    }

    //国际快递选择界面 自约物流-代约物流
    public function home_express(){
        return view('home.repertory.home_express');
    }


    public function express_info(){
        if($_GET['type'] == 1){
            //自约物流
            return view('home.repertory.express_my');
        }else{
            //代约物流

        }
    }


    //
    public function subRepertoryData(){
        $userid = session('home_user_id');
        if($_POST['sub_type'] == 1){
            //处理上传国际物流的照片
            Repertory::insert([
                'user_id' => $userid,
                'photo' => $_POST['file_name'],
                'created_at' => time(),
                'updated_at' => time(),
                'admin_user_name' => 'admin',
                'is_check' => 1,
                'sub_type' => 1,
                'status' => 5
            ]);


            return [
                'code' => 'success',
                'msg' => '提交成功'
            ];



        }


    }





    public function orderRepertory(){
        $repertory_company = config('admin.repertory_company');
        $package_status = config('admin.package_status');
        $currency = config('admin.currency');


        if(isset($_GET['type']) && $_GET['type'] == 1){
            return view('home.repertory.repertory_guoji') -> with([
                'company' => $repertory_company,
                'package_status' => $package_status,
                'currency' => $currency,
            ]);
        }else{
            return view('home.repertory.repertory_xianggang') -> with([
                'company' => $repertory_company,
                'package_status' => $package_status,
                'currency' => $currency,
            ]);
        }



    }

    public function orderRepertoryRes(){

        $userid = session('home_user_id');
        $id = Repertory::insertGetId([
            'user_id' => $userid,
            'numbers' => isset($_POST['numbers'])?preg_replace('# #','',strtoupper($_POST['numbers'])):NULL,
            'company' => isset($_POST['company'])?$_POST['company']:NULL,
            'num' => isset($_POST['num'])?$_POST['num']:NULL,
            'weight' => isset($_POST['weight'])?$_POST['weight']:NULL,
            'category' => isset($_POST['category'])?$_POST['category']:NULL,
            'goods_value' => isset($_POST['goods_value'])?$_POST['goods_value']:NULL,
            'currency' => isset($_POST['currency'])?$_POST['currency']:NULL,
            'card' => isset($_POST['card'])?$_POST['card']:NULL,
            'tel' => isset($_POST['tel'])?$_POST['tel']:NULL,
            'mail' => isset($_POST['mail'])?$_POST['mail']:NULL,
            'name' => isset($_POST['name'])?$_POST['name']:NULL,
            'ti_date' => isset($_POST['ti_date'])?$_POST['ti_date']:NULL,
            'address' => isset($_POST['address'])?$_POST['address']:NULL,
            'service_type' => isset($_POST['service_type'])?$_POST['service_type']:NULL,
            'remark' => isset($_POST['remark'])?$_POST['remark']:NULL,
            'package_status' => isset($_POST['package_status'])?$_POST['package_status']:NULL,
            'song_date' => isset($_POST['song_date'])?$_POST['song_date']:NULL,

            'created_at' => time(),
            'updated_at' => time(),
            'is_check' => 1,
            'sub_type' => $_POST['sub_type'],
            'admin_user_name' => 'admin',
            'status' => 6
        ]);


        //更新入仓号
        Repertory::where('id',$id) -> update([
            'canghao' => 'HQ'.$userid.'-'.date('ymd').'-'.sprintf('%06s',$id)
        ]);




        return [
            'code' => 'success',
            'msg' => '提交成功'
        ];
    }


    public function info(){
        $currency = config('admin.currency');
        return view('home.repertory.repertory_info') -> with([
            'currency' => $currency,
            'type' => $_GET['type']
        ]);


    }

}
