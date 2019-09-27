<?php
/**
 * Created by PhpStorm.
 * User: richer
 * Date: 2018/11/10
 * Time: 15:29
 */

namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use App\Order;
use App\PrintSequeue;
use EasyWeChat\Factory;
use Illuminate\Support\Facades\DB;

class EtkController extends Controller
{
    private $sign_str = '$#U*%#VN@J!';

    //ETK发货
    public function index(){
        return view('home.etk.index');
    }

    //生成发货二维码
    public function sendOrderQrCode(){
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


        if($config == 'local'){
            $userid = 41;
        }

        return view('home.etk.sendOrderQrCode') -> with([
            'userid' => $userid,
            'sign' => base64_encode($this -> sign_str.$userid)
        ]);
    }





    //店员扫二维码
    public function qrCodeManage(){
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


        if($config == 'local'){
            $userid = 41;

            //判断userid 在不在打印机列表里
            $printer_info = DB::table('printer_manage') -> where([
                'user_id' => $userid
            ]) -> first();

            $from_userid = 44;
        }else{
            //判断userid 在不在打印机列表里
            $printer_info = DB::table('printer_manage') -> where([
                'user_id' => $userid
            ]) -> first();

            if(!$printer_info){
                echo '无权限';
                exit;
            }

            if(isset($_GET['sign'])){
                //验证sign
                //echo base64_decode($_GET['sign']);
                $checkRes = strstr(base64_decode($_GET['sign']),$this -> sign_str);
                if(!$checkRes){
                    echo '越权1';
                    exit;
                }
                $from_userid = str_replace($this -> sign_str,'',base64_decode($_GET['sign']));
            }else{
                echo '越权2';
                exit;
            }
        }



        //找一下这个店员 属于哪个区域



        return view('home.etk.qrCodeManage') -> with([
            'userid' => $from_userid,
            'from_area' => $printer_info -> from_area?$printer_info -> from_area:1,
            'printer_userid' => $printer_info -> user_id
        ]);
    }


    //店员提交订单
    public function subOrderRes(){

        $model = new Order();
        $model -> underhandOrder([
            'from_area' => $_POST['from_area'],
            'userid' => $_POST['userid'],
            'remark' => '',
            'weights' => $_POST['weight_arr'],
            //有打印权限的店员
            'printer_userid' => $_POST['printer_userid'],
        ]);

        echo 'success';
    }

    //店员管理页面
    public function managePrintPage(){
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


        if($config == 'local'){
            $userid = 41;
        }else{
            //判断userid 在不在打印机列表里
            $printer_info = DB::table('printer_manage') -> where([
                'user_id' => $userid
            ]) -> first();

            if(!$printer_info){
                echo '无权限';
                exit;
            }
        }


        $print_request = DB::table('print_request') -> where([
            'printer_id' => $userid,
            'flag' => 0
        ]) -> orderBy('id','desc') ->  get();
        if($print_request){
            foreach($print_request as $k => $vo){
                $temp = DB::table('order') -> where([
                    'id' => $vo -> order_id
                ]) -> first();
                $print_request[$k] -> order_info = $temp;
            }
        }
        return view('home.etk.managePage') -> with([
            'info' => $print_request,
            'printer_id' => $userid
        ]);





    }


    //开始打印
    public function startPrint(){
        $order_info = DB::table('order') -> where([
            'id' => $_POST['order_id'],
            'flag' => 0
        ]) -> first();
        //通过 printer_id 拿到路线
        $printer_info = DB::table('printer_manage') -> where([
            'user_id' => $_POST['printer_id']
        ]) -> first();

        //打印机信息
        $printer = DB::table('printer') -> where([
            'id' => $printer_info -> printer_id
        ]) -> first();


        if($order_info){

            $packages = DB::table('packages') -> where([
                'order_id' => $order_info -> id,
                'flag' => 0
            ]) ->  get();
            //看下每个订单是否有pdf
            $pdf_arr = [];
            foreach($packages as $vo){
                if(!$vo -> pdf){
                    echo 'no_pdf';exit;
                }
                $pdf_arr[$vo -> wuliu_num] = url('temp_pdf').'/'.$vo -> pdf;
            }

            //加入队列
            foreach($pdf_arr as $k => $vo){
                PrintSequeue::addQueue(4,$k,$vo,$printer -> number);
            }

            //删除此request
            DB::table('print_request') -> where([
                'printer_id' => $_POST['printer_id'],
                'order_id' => $_POST['order_id']
            ]) -> update([
                'flag' => 1
            ]);

            echo 'success';



        }else{
            echo 'error';
        }
    }


}