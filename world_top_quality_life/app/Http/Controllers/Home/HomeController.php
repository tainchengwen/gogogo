<?php

namespace App\Http\Controllers\Home;

use Anchu\Ftp\Facades\Ftp;
use App\BatchList;
use App\BatchPackagesRelation;
use App\BatchRepertoryRelation;
use App\CommodityCode;
use App\CommodityCodeTemp;
use App\FindGoods;
use App\Jobs\GetMailStatus;
use App\Jobs\ProcessPodcast;
use App\Jobs\Reptile;
use App\MallApi;
use App\MpScanInfo;
use App\NoSplitPackage;
use App\NumberQueue;
use App\Order;
use App\Package;
use App\PrintSequeue;
use App\PurchaseOrder;
use App\Repertory;
use App\RepertoryLog;
use App\Smtp;
use App\SplitPackage;
use App\TrackingMore;
use App\WxUser;
use Faker\Provider\Image;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use EasyWeChat\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use PHPMailer\PHPMailer\PHPMailer;

class HomeController extends Controller
{
    //


    protected $cellLetter = [
        'A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q',
        'R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD',
        'AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN',
        'AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ'
    ];


    public function redirect(Request $request){
        $config = [
            'app_id' => env('WECHAT_OFFICIAL_ACCOUNT_APPID'),
            'secret' => env('WECHAT_OFFICIAL_ACCOUNT_SECRET'),
        ];

        $app = Factory::officialAccount($config);
        $oauth = $app->oauth;

// 获取 OAuth 授权结果用户信息
        $user = $oauth->user();

        // $user 可以用的方法:
        // $user->getId();  // 对应微信的 OPENID
        // $user->getNickname(); // 对应微信的 nickname
        // $user->getName(); // 对应微信的 nickname
        // $user->getAvatar(); // 头像网址
        // $user->getOriginal(); // 原始API返回的结果
        // $user->getToken(); // access_token， 比如用于地址共享时使用

        session(['wechat_user' => [
            'openid' => $user -> getId(),
            'nickname' => $user->getName(),
            'headimg' => $user->getAvatar(),
            'apires' => $user->getOriginal(),
            'access_token' => $user->getToken()

        ]]);


        $user = session('wechat_user');
        //Log::info(json_encode($user));
        $user['unionid'] = $user['apires']['unionid'];
        $user_model = new WxUser();
        //查看他有没有注册过
        $is_reg  = $user_model -> checkIsReg($user);
        if($is_reg){
            //如果注册 则检测下他是否签约
            $is_sign = true;
            //$is_sign  = $user_model -> checkIsSign($user);
            if(!$is_sign){
                //没有签约 跳转签约页面
                return view('home.sign');
            }
            //dump($is_sign);
        }


        if($request->session()->has('target_url')){
            $targetUrl = session('target_url');
        }else{
            $targetUrl = '/home';
        }

        return redirect($targetUrl);

        //header('location:'. $targetUrl);

    }

    public function home(Request $request){
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

// 未登录
        if (!$request->session()->has('wechat_user')) {
            session([
                'target_url' => '/home'
            ]);
            return $oauth->redirect(url('redirect'));
        }

        // 已经登录过
        $user = session('wechat_user');
        //查看他有没有注册过
        $user_model = new WxUser();
        $is_reg  = $user_model -> checkIsReg($user);
        if($is_reg){
            //如果注册 则检测下他是否签约
            $is_sign = true;
            //$is_sign  = $user_model -> checkIsSign($user);
            if(!$is_sign){
                //没有签约 跳转签约页面
                return view('home.sign');
            }
            //dump($is_sign);
        }
        //注册好 并签约 显示订单页面
        return redirect('order');
        //dump($user);



    }


    //同意签约
    public function agreeSign(){
        //订单页面
        //变成签约
        //当前userid
        $userid = session('home_user_id');
        DB::table('wxuser') -> where([
            'id' => $userid
        ]) -> update([
            'is_sign' => 1
        ]);
        return redirect('order');
    }



    public function signPage(){
        return view('home.sign');
    }
    public function clearCache(Request $request){
        //模拟数据
        DB::table('wxuser') -> delete();
        DB::table('order') -> delete();
        DB::table('packages') -> delete();
        DB::table('price_log') -> delete();


        //$request->session()->flush();
    }


    public function testWeight($weight){
        //计重重量
        $weight_count = floatval($weight) - 1 ;
        //向上取0。5 的倍数
        $times = ceil($weight_count*2);
        dd($times);
    }

    public function exportPdf($id){
        header("Content-type:text/html;charset=utf-8");
        ///linli/public/index.php
        $selfurl =  $_SERVER['PHP_SELF'];

        //去掉index.php
        $url_arr = explode('/',$selfurl);
        //dump($url_arr);exit;
        unset($url_arr[count($url_arr) - 1]);
        $public_url = $url_arr;
        //dd('http://'.$_SERVER['HTTP_HOST'].implode('/',$public_url).'/pdf/');
        $url_arr[] = 'exportPdfPage';
        $url_arr[] = $id;
        $url_pdf = implode('/',$url_arr);


        $pdfurl = 'http://'.$_SERVER['HTTP_HOST'].$url_pdf;
        //echo $pdfurl;exit;
        //echo $pdfurl;exit;
        //pdf 生成的卢靖
        $path = "  /var/www/html/world_top_quality_life/public/pdf/";
        $time = date('Y-m-d-H:i:s');
        //dd($pdfurl.$path.$time);
        //echo "~/wkhtmltox/bin/wkhtmltopdf ".$pdfurl.$path.$time.".pdf 2>&1";exit;
        exec("wkhtmltopdf ".$pdfurl.$path.$time.".pdf 2>&1",$output);

        ///data/wwwroot/www.szyeweihui.com/public/pdf/pdf
        //echo "wkhtmltopdf ".$pdfurl." /webdata/laravel/public/pdf/pdf.pdf 2>&1" ;
        //dump($output);exit;
        if(count($output)){
            $save_file = 'http://'.$_SERVER['HTTP_HOST'].implode('/',$public_url).'/pdf/'.$time.'.pdf';
            echo "<a href='".$save_file."'>下载</a>";
            //dump($save_file);exit;
        }
    }

    public function exportPdfPage($id){

        $packages = DB::table('packages')  -> where([
            'order_id' => $id
        ]) -> get() -> toArray();


        $order_info = Order::find($id);
        $user_info = WxUser::find($order_info -> userid);

        /*

        $pdf = \PDF::loadView('home.getCodeImg', [
            'url' => env('PDF_URL'),
            'packages' => $packages
        ]);
        return $pdf->download('pdf.pdf');
        */
        return view('home.getCodeImg') -> with([
            'url' => env('PDF_URL'),
            'packages' => $packages,
            'nickname' => $user_info -> nickname
        ]);
    }


    public function exportSplitPdfPage(){
        //条形码内容
        $sp_number = $_GET['sp_number'];

        $temp = explode('----',$sp_number);

        $show_number = $temp[1];
        $sp_number = $temp[0];
        return view('home.exportSplitPdfPage') -> with([
            'url' => env('PDF_URL'),
            'sp_number' => $sp_number,
            'show_number' => $show_number
        ]);
    }


    //测试标签  大标签纸
    public function testPdfPage(){
        /*
        wkhtmltopdf     --page-width 100 --page-height 100  --disable-smart-shrinking -L 0mm -R 0mm -T 0mm -B 0mm  http://hqyp.fenith.com/testPdfPage?sp_number=EK439815435HK-01    /var/www/html/world_top_quality_life/public/pdf/aaaaa1.pdf
        wkhtmltopdf     --page-width 90 --page-height 90  --disable-smart-shrinking -L 0mm -R 0mm -T 0mm -B 0mm  http://hqyp.fenith.com/testPdfPage?sp_number=EK439815435HK-01999EK439815435HK-01 /var/www/html/world_top_quality_life/public/pdf/aaaaa1.pdf
        wkhtmltopdf     --page-width 90 --page-height 90  --disable-smart-shrinking -L 0mm -R 0mm -T 0mm -B 0mm  http://hqyp.fenith.com/testPdfPage?sp_number=EK439815435HK-01999EK439815435HK\t-01 /var/www/html/world_top_quality_life/public/pdf/aaaaa1.pdf
        */
        $sp_number = $_GET['sp_number'];
        $temp = explode('----',$sp_number);

        $show_number = $temp[1];
        $sp_number = $temp[0];

        $ext = '';
        $ext2 = '';
        if(strstr($show_number,'---')){
            $temp = explode('---',$show_number);
            $ext =  isset($temp[1])?$temp[1]:'';
            $ext2 =  isset($temp[2])?$temp[2]:'';
            $show_number = $temp[0];
        }


        return view('home.testPdfPage') -> with([
            'url' => env('PDF_URL'),
            'sp_number' => $sp_number,
            'show_number' => $show_number,
            'ext' => $ext,
            'ext2' => $ext2
        ]);
    }

    //测试 打印标识卡
    public function markPdfPage(){
        $sp_number = $_GET['sp_number'];
        return view('home.markPdfPage') -> with([
            'url' => env('PDF_URL'),
            'sp_number' => $sp_number
        ]);
    }


    //扫码枪用
    public function barCode(){
        exit;
        $data = [];
        if(isset($_GET['ids']) && $_GET['ids']){
            $wuliu_nums = explode(',',$_GET['ids']);
            foreach($wuliu_nums as $vo){
                $temp = DB::table('packages') -> where([
                    'flag' => 0,
                    'wuliu_num' => $vo
                ]) -> first();
                if($temp){
                    $data[] = $temp;
                }

            }
        }


        $ids = [];
        foreach($data as $k => $vo){
            //拼接id
            $ids[] = $vo -> id;
        }

        return view('home.barcode') -> with([
            'data' => $data,
            'ids' => implode(',',$ids)
        ]);
    }
    public function barCodeAjax(){

        $wuliu_num = $_POST['wuliu_num'];
        $query = $_POST['query']?explode(',',$_POST['query']):[];
        //查下这个物流单号 有没有对应的包裹
        $data = DB::table('packages') -> where([
            'flag' => 0,
            'wuliu_num' => $wuliu_num
        ]) -> first();

        if(!$data){
            echo 'error';
        }else{
            //判断下此包裹 有没有出现过别的托盘里
            $isset = DB::table('batch_packages')
                -> where('package_ids','like',$data -> id .',%')
                -> orWhere('package_ids','like','%,'.$data -> id .',%')
                -> orWhere('package_ids','like','%,'.$data -> id) -> first();
            Log::info(999999900);
            Log::info(print_r($isset,true));
            if($isset){
                echo 'repeat';exit;
            }


            if(in_array($wuliu_num,$query)){
                echo 'repeat';
            }else{
                echo 'success';
            }


        }
    }


    //保存扫码结果
    public function saveBarCode(){
        $pici_num = count(explode(',',$_POST['query'])).'-'.date('Ymd');


        $batch_id = DB::table('batch_packages') -> insertGetId([
            'package_ids' => '',
            'batch_num' => $pici_num,
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        //添加批次-包裹关系
        foreach($_POST['query'] as $vo){
            DB::table('batch_packages_relation') -> insert([
                'package_id' => $vo,
                'batch_id' => $batch_id,
                'created_at' => time(),
                'updated_at' => time(),
            ]);
        }



    }


    //新扫码 到货扫描
    public function scanGoods(){
        //批次-物流单号 为主表
        /*
        $info = DB::table('batch_repertory_relation')
            -> join('batch_packages','batch_id','batch_packages.id')
            -> join('repertory','repertory_id','repertory.id')
            -> where('batch_packages.send_order_id',0)
            ->select('batch_packages.*', 'repertory.numbers','batch_repertory_relation.id as id','batch_packages.id as pici')
            ->get();
        */

        $info = DB::table('batch_packages')
            -> where('send_order_id',0)
            -> orderBy('id','desc')
            ->get();
        foreach($info as $k => $vo){
            //寻找物流编号 - 批次关系

            $temp = DB::table('batch_repertory_relation') -> where([
                'batch_id' => $vo -> id
            ]) -> get();

            if(count($temp)){
                foreach($temp as $key => $value){
                    //通过 物流编号 拿编号信息
                    $temp_repertory = DB::table('repertory') -> where([
                        'id' => $value -> repertory_id
                    ])-> first();
                    $temp[$key] -> temp_repertory = $temp_repertory;
                }
                $info[$k] -> relation = $temp;
            }


        }
        //dd($info);



        return view('home.scanGoods') -> with([
            'data' => $info
        ]);
    }


    //到货清单
    public function repertoryList(){
        //未打包-已打包-部分贴单-已贴单
        $repertory = DB::table('repertory')
            -> where([
                'status' => 5,
                'is_check' => 0,
                'flag' => 0,
                'is_fid' => 0
            ])
            -> where('user_id','>',0)
            -> orderByRaw('FIELD(package_status,0,1,3,2);') ->  get();
        $package_config = config('admin.package_status');
        foreach($repertory as $k => $vo){
            $repertory[$k] -> f_name = '';
            $repertory[$k] -> package_status = isset($package_config[$vo -> package_status])?$package_config[$vo -> package_status]:0;
            if($vo -> weight_json){
                $temp = json_decode($vo -> weight_json,true);
                $show_str = '';
                foreach($temp as $key => $value){
                    if($value > 0 ){
                        $show_str .= $key.':'.$value.",";
                        //$show_json[$key] = $value;
                    }
                }
                $repertory[$k] -> weight_json = trim($show_str,",");
            }

            if($vo -> fid){
                $repertory_f = Repertory::find($vo -> fid);
                if($repertory_f){
                    $repertory[$k] -> f_name = $repertory_f -> numbers;
                }
            }


        }


        //运输中的物流单号
        $repertory_under = Repertory::where('status','6')  -> orderByRaw('FIELD(package_status,0,1,3,2);') ->  get();
        foreach($repertory_under as $k => $vo){
            $repertory_under[$k] -> f_name = '';
            $repertory_under[$k] -> package_status = isset($package_config[$vo -> package_status])?$package_config[$vo -> package_status]:0;
            if($vo -> weight_json){
                $temp = json_decode($vo -> weight_json,true);
                $show_str = '';
                foreach($temp as $key => $value){
                    if($value > 0 ){
                        $show_str .= $key.':'.$value.",";
                        //$show_json[$key] = $value;
                    }
                }
                $repertory_under[$k] -> weight_json = trim($show_str,",");
            }

            if($vo -> fid){
                $repertory_f = Repertory::find($vo -> fid);
                if($repertory_f){
                    $repertory_under[$k] -> f_name = $repertory_f -> numbers;
                }
            }
        }
        //dd($repertory_under);

        $deal_methods = config('admin.deal_method');


        return view('home.repertoryList') -> with([
            'data' => $repertory,
            'repertory_under' => $repertory_under,
            'deal_methods' => $deal_methods
        ]);

    }

    //打印全部的物流小标签
    public function printRepertoryList(){
        $repertory_info = Repertory::find($_POST['id']);
        $repertory_weights = DB::table('repertory_weight')
            -> where([
                'repertory_id' => $_POST['id'],
                'flag' => 0
            ]) -> get();

        if($repertory_info && count($repertory_weights)){

            if(env('APP_ENV') != 'local'){
                $area_label = config('admin.repertory_id_prefix').sprintf('%06s',$repertory_info -> id);
                foreach($repertory_weights as $vo){

                    //维护完 自动加入打印
                    $pdf = PrintSequeue::makeSPdf($vo -> label,$vo -> label.'---'.$vo -> weight.'---'.$area_label);
                    //加入自动打印队列
                    PrintSequeue::addQueue(6,$vo -> label,$pdf,'009');
                }

            }
            return [
                'code' => 'success',
                'msg' => '已加入打印队列'
            ];
        }else{
            return [
                'code' => 'error',
                'msg' => '无打包的包裹'
            ];
        }

    }






    //到货清单维护重量
    public function updateRepertoryData(){
        //根据get 查询json
        $repertory_info = DB::table('repertory') -> where([
            'id' => $_GET['repertory_id']
        ]) -> first();
        $arr = [];
        $zong = 0;
        if($repertory_info -> weight_json){
            $temp = json_decode($repertory_info -> weight_json,true);
            foreach($temp as $key => $value){
                if($value > 0 ){
                    $arr[$key] = $value;
                    $zong += $value;
                }
            }
        }
        return view('home.updateRepertoryData') -> with([
            'weight_json' => $arr,
            'zong' => $zong
        ]);
    }

    //到货清单维护重量保存结果
    public function saveUpdateRepertoryData(){
        //var_dump(json_encode($_REQUEST));
        $temp_arr = $_POST['temp_arr'];
        $data_arr = $_POST['data_arr'];

        //组合数组
        $res_arr = [];
        //更新打包数量 -状态
        $count_dabao = 0;
        foreach($temp_arr as $k => $vo){
            $res_arr[$data_arr[$k]] = $vo;
            if(intval($vo) > 0){
                $count_dabao += intval($vo);
            }
        }


        $repertory_info = DB::table('repertory') -> where([
            'id' => $_POST['repertory_id']
        ]) -> first();

        DB::beginTransaction();
        try{
            //如果维护过weight_json 则 比较下 $res_arr 和 weight_json
            if($repertory_info -> weight_json){
                //并且下过单，才报警
                $order = DB::table('order') -> where([
                    'repertory_id' => $repertory_info -> id,
                    'flag' => 0
                ]) -> first();
                if($order){
                    $weight_json = json_decode($repertory_info -> weight_json,true);
                    foreach($weight_json as $k => $vo){
                        //如果发现 减少了 则报警
                        if(intval($vo) > intval($res_arr[$k])){
                            echo 'no_decrease';exit;
                        }
                    }
                }
            }








            //查看 此物流单号 有没有打印过
            $repertory_weights = DB::table('repertory_weight')
                -> where([
                    'flag' => 0,
                    'repertory_id' => $_POST['repertory_id']
                ]) -> get();
            if(!count($repertory_weights)){
                //$temp_ids 更新重量
                //{ ["3.4"]=> string(1) "1" ["3.9"]=> string(1) "5" ["4.4"]=> string(1) "0" ["4.9"]=> string(1) "0" ["1.0"]=> string(1) "0" ["1.4"]=> string(1) "0" ["1.9"]=> string(1) "6" ["2.4"]=> string(1) "0" ["2.9"]=> string(1) "0" }
                $label_num = 1;
                foreach($res_arr as $k => $vo){
                    if($vo > 0){

                        for($i=intval($vo);$i>=1;$i--){
                            //直接生成包裹订单编号
                            //这里生成的 都是我们自己区域的
                            $package_num_res = Order::getPackageNum0310(5,$repertory_info -> user_id);
                            $label = $package_num_res['order_number'];
                            $repertory_label = config('admin.repertory_id_prefix').sprintf('%06s',$repertory_info -> id).'-'.sprintf('%03s',$label_num);
                            $id_this = DB::table('repertory_weight')
                                -> insertGetId([
                                    'repertory_id' => $_POST['repertory_id'],
                                    'label' => $label,
                                    'weight' =>$k,
                                    'created_at' => time(),
                                    'updated_at' => time(),
                                ]);

                            $label_num ++;
                            if(env('APP_ENV') != 'local'){
                                //维护完 自动加入打印
                                $pdf = PrintSequeue::makeSPdf($label,$label.'---'.$k.'---'.$repertory_label);
                                //加入自动打印队列
                                PrintSequeue::addQueue(6,$label,$pdf,'009');
                            }
                        }
                    }
                }
            }else{
                $temp_number = count($repertory_weights) + 1;
                //如果有了 根据现在的情况判断 看缺少哪个重量
                foreach($res_arr as $k => $vo){
                    //["3.4"]=> string(1) "1"
                    //判断 这个重量 是不是这个个数
                    $number_temp = DB::table('repertory_weight')
                        -> where([
                            'repertory_id' => $_POST['repertory_id'],
                            'flag' => 0,
                            'weight' => $k
                        ]) -> count();
                    if(intval($vo) > intval($number_temp)){
                        for( $i = 1; $i<=intval($vo)-intval($number_temp); $i++ ){
                            $label = config('admin.repertory_id_prefix').sprintf('%06s',$repertory_info -> id).'-'.$temp_number;

                            $id_this = DB::table('repertory_weight')
                                -> insertGetId([
                                    'repertory_id' => $_POST['repertory_id'],
                                    'label' => $label,
                                    'weight' =>$k,
                                    'created_at' => time(),
                                    'updated_at' => time(),
                                ]);
                            $temp_number ++ ;
                        }
                    }elseif(intval($vo) < intval($number_temp)){
                        echo 'no_decrease';
                        Log::info('repertory_id'.$_POST['repertory_id'].' vo < number');
                        DB::rollBack();
                        exit;

                    }

                }
            }



            DB::table('repertory') -> where([
                'id' => $_POST['repertory_id']
            ]) -> update([
                //打包数量
                'dabao_num' => $count_dabao,
                //剩余数量 = 打包数量 - 发出数量
                'shengyu_num' => abs(intval($count_dabao) - intval($repertory_info -> fachu_num)),
                'package_status' => 1
            ]);



            //zong {"3.4":"0","4.4":"0","4.9":"4","1.0":"0","1.4":"5","1.9":"0","2.4":"0","2.9":"5"}

            //先看 有没有剩余json
            if(!$repertory_info -> over_json){
                DB::table('repertory') -> where([
                    'id' => $_POST['repertory_id']
                ]) -> update([
                    'weight_json' => json_encode($res_arr),
                    'over_json' => json_encode($res_arr),
                    'updated_at' => time()
                ]);
            }else{
                //如果有over json 则 over_json = 总的 - order_json 已出的
                //copy 基数是总  赋值给over_json
                $res_arr_copy = $res_arr;
                $order_json_arr = json_decode($repertory_info -> order_json,true);
                if(count($order_json_arr)){
                    foreach($order_json_arr as $k => $vo){
                        $res_arr_copy[$k] = intval($res_arr_copy[$k]) - intval($vo);
                    }
                }

                //copy 赋值给 over__json
                DB::table('repertory') -> where([
                    'id' => $_POST['repertory_id']
                ]) -> update([
                    'weight_json' => json_encode($res_arr),
                    'over_json' => json_encode($res_arr_copy),
                    'updated_at' => time()
                ]);
            }
            DB::commit();
            echo 'success';
        }catch (\Exception $exception){
            DB::rollBack();
            Log::info($exception->getTraceAsString());
            echo 'error';
        }







    }


    //打包维护标签
    public function makePackageLabel(){
        return view('home.makePackageLabel');
    }

    //打包维护标签结果
    public function makePackageLabelRes(){
        $scan = $_POST['scan'];
        $op_user_id = $_POST['op_user_id'];
        $op_user_id_config = config('admin.op_user_id');
        if(!in_array($op_user_id,$op_user_id_config)){
            return [
                'code' => 500,
                'msg' => '没有此员工编号'
            ];
        }

        $repertory_id = intval(str_replace(config('admin.repertory_id_prefix'),'',$scan));


        $repertory_info = DB::table('repertory')
            -> where([
                'id' => $repertory_id
            ]) -> first();
        if(!$repertory_info || $repertory_info -> flag){
            return [
                'code' => 500,
                'msg' => '没有此物流编号'
            ];
        }

        if(!$repertory_info -> user_id){
            return [
                'code' => 500,
                'msg' => '没有分配客户'
            ];
        }

        if($_POST['weight_input']){
            $weight = round(floatval($_POST['weight_input']),2);
        }else{
            //生成随机重量
            $weight = 2.9;
        }




        $package_num_res = Order::getPackageNum0310(5,$repertory_info -> user_id);
        //查找最大的
        $label = $package_num_res['order_number'];
        //$repertory_label = config('admin.repertory_id_prefix').sprintf('%06s',$repertory_info -> id).'-'.sprintf('%03s',$label_num);
        DB::table('repertory_weight') -> insertGetId([
            'repertory_id' => $repertory_id,
            'label' => $label,
            'weight' =>$weight,
            'created_at' => time(),
            'updated_at' => time(),
            'op_user_id' => $op_user_id,
        ]);

        //更新打包数量
        DB::table('repertory')
            -> where([
                'id' => $repertory_id,
            ]) -> increment('dabao_num',1);

        DB::table('repertory')
            -> where([
                'id' => $repertory_id,
            ]) -> update([
                'status' => 1
            ]);

        if(env('APP_ENV') != 'local'){
            $temp_number = DB::table('repertory_weight')
                -> where([
                    'repertory_id' => $repertory_id,
                     'flag' => 0
                ]) -> count();
            $area_label = config('admin.repertory_id_prefix').sprintf('%06s',$repertory_info -> id).'-'.$temp_number;
            //维护完 自动加入打印
            $pdf = PrintSequeue::makeSPdf($label,$label.'---'.$weight.'---'.$area_label);
            //加入自动打印队列
            PrintSequeue::addQueue(6,$label,$pdf,'009');
        }


        return [
            'code' => 200,
            'msg' => '打印成功'
        ];


    }

    //删除没有包裹的批次
    public function deleteScanGoods(){
        $info = DB::table('batch_packages') -> where([
            'id' => $_POST['id']
        ]) -> first();
        if(!$info -> count_packages){
            //可以删除
            DB::table('batch_packages') -> where([
                'id' => $_POST['id']
            ]) -> delete();
            DB::table('batch_repertory_relation') -> where([
                'batch_id' => $_POST['id']
            ]) -> delete();
            echo 'success';
        }else{
            echo 'error';
        }

    }

    //到货扫描处理
    public function subScanGoodsRes(){
        $scan = preg_replace('# #','',strtoupper($_POST['scan']));;
        //echo $scan;exit;

        //通过账号查找 是否存在此出入库管理
        $info = DB::table('repertory') -> where('numbers','like',$scan) -> first();
        if($info){
            if($info -> update_type == 'scan'){
                //重复扫描
                echo 're_scan';exit;
            }else{
                //更新为  已入库
                DB::table('repertory') -> where([
                    'id' => $info -> id
                ]) -> update([
                    'status' => 5,
                    'updated_at' => time(),
                    'shiji_date' => date('Y-m-d'),
                    'update_type' => 'scan'
                ]);


                //把她的子单号 也更新为已入库
                DB::table('repertory') -> where([
                    'fid' => $info -> id
                ]) -> update([
                    'status' => 5,
                    'updated_at' => time(),
                    'shiji_date' => date('Y-m-d'),
                    'update_type' => 'scan'
                ]);

                //添加到货扫描时间
                RepertoryLog::addLog($info,2);


                echo 'update';exit;
            }


        }else{
            DB::table('repertory') -> insertGetId([
                'numbers' => $scan,
                'status' => 5,
                'updated_at' => time(),
                'created_at' => time(),
                'insert_type' => 'scan',
                'update_type' => 'scan',
                'shiji_date' => date('Y-m-d'),
                'admin_user_name' => 'admin'
            ]);
            echo 'success';exit;
        }


    }

    //到货扫描
    public function scanStart(){
        $start_time = strtotime(date("Y-m-d"),time());
        $end_time = $start_time + 60*60*24;

        $info = DB::table('repertory')
            -> where('update_type','=','scan')
            -> where('created_at','>=',$start_time)
            -> where('created_at','<=',$end_time)
            -> orderBy('id','desc')-> get();

        //到货状态的配置
        $config = config('admin.repertory_status');

        return view('home.scanStart') -> with([
            'data' => $info,
            'config_status' => $config
        ]);
    }

    //异常件扫描
    public function scanWarningStart(){
        return view('home.scanWarningStart');
    }

    //异常件扫描处理
    public function scanWarningStartRes(){
        $number = trim($_POST['scan']);
        $info = DB::table('warning_package')
            -> where('number',$number)
            -> first();
        if($info){
            return [
                'code' => 500,
                'msg' => '已存在于异常件列表中'
            ];
        }else{

            //如果没有 先查下有没有此包裹
            $info = DB::table('packages')
                -> where([
                    'flag' => 0,
                    'wuliu_num' => $number
                ]) -> first();
            if(!$info){
                return [
                    'code' => 500,
                    'msg' => '没有此单包裹，何谈退件？'
                ];
            }

            DB::table('warning_package') -> insertGetId([
                'create_date' => date('Y-m-d'),
                'number' => $number,
                'remark' => '仓库扫描提交异常件'
            ]);


            return [
                'code' => 200
            ];
        }

    }


    //生成新托盘
    public function createNewBatch(){
        $batch_info = '';
        $package_info = [];
        $repertory_nums = [];
        $repertory_info = [];
        if(isset($_GET['batch'])){
            //这个batch_id 是 批次 物流关系绑定关系ID
            $relation_info = BatchRepertoryRelation::find($_GET['batch']);
            //批次id = $relation_info = batch_id
            $batch_info = BatchList::find($relation_info -> batch_id);


            //通过这个关系 找到批次。及包裹
            $model_relation = new BatchRepertoryRelation();
            $package_info = $model_relation -> getInfoByRelationId($_GET['batch']);

            $repertory_info = Repertory::find($relation_info -> repertory_id);

            /*
            $batch_info = DB::table('batch_packages') -> where([
                'id' => $_GET['batch']
            ]) -> first();

            if($batch_info -> package_ids){
                $temp = explode(',',$batch_info -> package_ids);

                foreach($temp as $vo){
                    $temp_info = DB::table('packages') -> where([
                        'flag' => 0,
                        'id' => $vo
                    ]) -> first();
                    if($temp_info){
                        $data[] = $temp_info;
                    }

                }

            }else{
                $temp = [];
            }


            $batch_info -> count_packages = count($temp);
            */


        }else{
            /*
            //创建新托盘
            $batch_num = date('Ymd');
            $id = DB::table('batch_packages') -> insertGetId([
                'batch_num' => '0-'.$batch_num,
                'batch_num_base' => $batch_num,
                'created_at' => time(),
                'updated_at' => time(),
                'send_order_id' => 0
            ]);
            return redirect(url('createNewBatch').'?batch='.$id);
            */

            //如果没有开始创建，则把所有未出货的 物流单号 都给他展示出来
            $repertory_nums = DB::table('repertory')
                -> where('flag',0)
                -> where('status','<>', 3)
                -> where('is_fid','0')
                -> get();



        }


        return view('home.createNewBatch') -> with([
            'batch_info' => $batch_info,
            'data' => $package_info,
            'repertory_nums' => $repertory_nums,
            'repertory_info' => $repertory_info,
        ]);
    }

    //保存托盘号
    public function saveBatchPackages(){
        $info = DB::table('batch_packages') -> where([
            'batch_num' => $_GET['batch_num']
        ]) -> first();
        if($info){
            return redirect(url('createNewBatch'));
        }else{
            $id = DB::table('batch_packages') -> insertGetId([
                'batch_num' => '0-'.$_GET['batch_num'],
                'batch_num_base' => $_GET['batch_num'],
                'created_at' => time(),
                'updated_at' => time(),
                'send_order_id' => 0
            ]);
            return redirect(url('createNewBatch').'?batch='.$id);
        }
    }


    //保存新托盘之前 扫描物流单号 ajax
    function scanNumbersAjax(){
        //到货物流判断
        $wuliu_num = trim($_POST['wuliu_num']);

        //托盘编号  如果存在的话 在托盘上 新增到货物流编号
        $pici_id = trim($_POST['pici_id']);

        //判断下此单  是否再repertory 中
        $repertory_info = DB::table('repertory') -> where([
            'numbers' => $wuliu_num
        ]) -> first();

        if($repertory_info && $repertory_info -> is_fid){
            return [
                'code' => 'error',
                'msg' => '母单号不允许创建,请使用子单号'
            ];
        }


        //做过到货扫描 或者 此单号是子单号
        if($repertory_info && ($repertory_info -> update_type == 'scan' || $repertory_info -> fid)){

            if($pici_id){
                //批次号中 新增物流单号

                $relation = DB::table('batch_repertory_relation') -> where([
                    'batch_id' => $pici_id,
                    'repertory_id' => $repertory_info -> id,
                ]) -> first();

                if(!$relation){
                    //如果不存在关系 则添加 返回id
                    $relation_id = DB::table('batch_repertory_relation') -> insertGetId([
                        'batch_id' => $pici_id,
                        'repertory_id' => $repertory_info -> id,
                        'created_at' => time(),
                        'updated_at' => time(),
                    ]);
                    return [
                        'code' => 'success',
                        'msg' => $relation_id
                    ];
                }else{
                    return [
                        'code' => 'error',
                        'msg' => '该单号不是新物流单号'
                    ];
                }

            }else{
                //如果存在  则创建 新托盘编号
                $batch_num = date('Ymd');
                $id = DB::table('batch_packages') -> insertGetId([
                    'batch_num' => '0-'.$batch_num,
                    'batch_num_base' => $batch_num,
                    'created_at' => time(),
                    'updated_at' => time(),
                    'send_order_id' => 0,
                ]);

                //添加批次-到货物流关系
                $batch_model = new BatchList();
                $relation_id = $batch_model -> addBatchRepertoryRelation($id,$repertory_info -> id);

                return [
                    'code' => 'success',
                    'msg' => $relation_id
                ];
            }




        }else{
            //没有此 到货物流单号 不允许创建托盘
            return [
                'code' => 'error',
                'msg' => '未做单号到货扫描，请先进行单号到货扫描'
            ];
        }
    }



    //保存新托盘数据ajax
    public function createNewBatchAjax(){
        $wuliu_num = $_POST['wuliu_num'];
        //批次-到货物流编号id
        $batch_id = $_POST['batch_id'];

        //查下这个物流单号 有没有对应的包裹
        $data = DB::table('packages') -> where([
            'flag' => 0,
            'wuliu_num' => $wuliu_num
        ]) -> first();

        if(!$data){
            return [
                'code' => 'error',
                'msg' => '输入单号不存在,或者已删除'
            ];
        }else{
            $batch_model = new BatchList();

            $isset = $batch_model -> getBatchInfoByPackage($data -> id);

            /*
            //判断下此包裹 有没有出现过别的托盘里
            $isset = DB::table('batch_packages')
                -> where('package_ids','like',$data -> id .',%')
                -> orWhere('package_ids','like','%,'.$data -> id .',%')
                -> orWhere('package_ids','like','%,'.$data -> id) -> first();
            */

            if($isset){
                return [
                    'code' => 'error',
                    'msg' => '输入单号重复'
                ];
            }

            //批次-到货物流关系
            $relation = BatchRepertoryRelation::find($batch_id);

            //看这个包裹是否跟托盘中其他路线相同
            $check_res = $batch_model -> checkPackageRoute($data,$relation -> batch_id);
            if(!$check_res){
                //不是同一条路线
                return [
                    'code' => 'error',
                    'msg' => '跟其他包裹不是同一个路线'
                ];
            }

            //保存包裹 到托盘中
            //返回此批次-此到货物流单号中 有多少包裹
            $batch_model -> addBatchPackageRelation($relation -> batch_id,$relation -> repertory_id,$data -> id);

            //查询此批次有多少包裹
            $count_packages = $batch_model -> getPackagesByPici($relation -> batch_id);


            //批次信息
            $info_this = BatchList::find($relation -> batch_id);
            Log::info($info_this);

            if(!$info_this-> batch_num_base){
                //分割batch_num
                $temp_batch_num = explode('-',$info_this-> batch_num);

                $info_this-> batch_num_base = $temp_batch_num[1];
            }




            //物流单号内的上卡板数量 + 1
            $repertory_info = DB::table('repertory') -> where([
                'id' => $relation -> repertory_id
            ]) -> first();

            //判断 如果是小程序交货单来的 则不需要该表包裹状态
            if($repertory_info -> sub_type  == 4){
                DB::table('repertory') -> where([
                    'id' => $relation -> repertory_id
                ]) -> update([
                    'up_numbers' => intval($repertory_info -> up_numbers) + 1,
                    //状态改成 出货中
                    'status' => 1,
                ]);
            }else{
                DB::table('repertory') -> where([
                    'id' => $relation -> repertory_id
                ]) -> update([
                    'up_numbers' => intval($repertory_info -> up_numbers) + 1,
                    //状态改成 出货中
                    'status' => 1,
                    //包裹状态改为 部分贴单
                    'package_status' => 3
                ]);

                //查下 如果此物流单号上卡板数量 = 打包数量 则包裹状态改为全部贴单
                if(intval($repertory_info -> up_numbers) + 1 == $repertory_info -> dabao_num){
                    DB::table('repertory') -> where([
                        'id' => $relation -> repertory_id
                    ]) -> update([
                        'package_status' => 2,
                        'updated_at' => time(),
                    ]);
                }
            }






            //更新批次号
            DB::table('batch_packages') -> where([
                'id' => $relation -> batch_id
            ]) -> update([
                'batch_num' => $count_packages.'-'.  $info_this-> batch_num_base,
                'count_packages' => $count_packages,
                'updated_at' => time()
            ]);

            return [
                'code' => 'success',
                'msg' => '扫描成功'
            ];


        }
    }


    //删除托盘中的包裹
    public function  deleteNewBatch(){
        //这个是 托盘-到货物流的关系id
        $batch_id = $_POST['batch_id'];
        $package_id = $_POST['id'];

        $relation = BatchRepertoryRelation::find($batch_id);
        //在 包裹-批次关系中 删除
        $model_packages = new BatchPackagesRelation();
        $model_packages -> deleteRelation($package_id,$relation -> batch_id,$relation -> repertory_id);

        //更新批次号
        $batch_model = new BatchList();
        //查询此批次有多少包裹
        $count_packages = $batch_model -> getPackagesByPici($relation -> batch_id);
        //批次信息
        $info_this = BatchList::find($relation -> batch_id);
        Log::info($info_this);

        if(!$info_this-> batch_num_base){
            //分割batch_num
            $temp_batch_num = explode('-',$info_this-> batch_num);

            $info_this-> batch_num_base = $temp_batch_num[1];
        }


        //上卡板数量 - 1

        $repertory_info = DB::table('repertory') -> where([
            'id' => $relation -> repertory_id
        ]) -> first();

        DB::table('repertory') -> where([
            'id' => $relation -> repertory_id
        ]) -> update([
            'up_numbers' => intval($repertory_info -> up_numbers) - 1,
        ]);


        DB::table('batch_packages') -> where([
            'id' => $relation -> batch_id
        ]) -> update([
            'batch_num' => $count_packages.'-'.  $info_this-> batch_num_base,
            'count_packages' => $count_packages
        ]);


        echo 'success';

    }



    //熊谢拆单链接
    public function splitXXPackages(){
        if(!isset($_GET['num'])){
            $_GET['num'] = '010';
        }
        if(!isset($_GET['num']) || !in_array($_GET['num'],config('admin.xx_printer_set'))){
            echo '链接错误';exit;
        }


        return view('home.splitXXPackages') -> with([
            'num' => $_GET['num'],
            'config' => config('admin.xx_printer_set'),
            'url' => url('splitXXPackages').'?num='
        ]);
    }

    public function splitXXPackagesRes(){
        header("Content-type:text/html;charset=utf-8");
        $wuliu_number = trim($_POST['scan']);
        //拆分成几个
        $numbers = intval($_POST['numbers']);
        //打印机编号
        $num = trim($_POST['num']);


        //记录拆分
        $sp_numbers = [];
        for($i=1;$i<=$numbers;$i++){
            $temp = $wuliu_number.'-'.sprintf('%02s', $i);;
            $sp_numbers[] = $temp;
        }
        if(count($sp_numbers)){
            foreach($sp_numbers as $vo){
                $pdf_file = PrintSequeue::makeSPdf($vo);
                if($pdf_file){
                    PrintSequeue::addQueue(1,$vo,$pdf_file,$num);
                }
            }
        }
    }



    //拆分单号
    public function splitPackages(){
        if(!isset($_GET['num'])){
            $_GET['num'] = '009';
        }

        if(!isset($_GET['num']) || !in_array($_GET['num'],config('admin.printer_set'))){
            echo '链接错误';exit;
        }
        return view('home.splitPackages') -> with([
            'num' => $_GET['num'],
            'config' => config('admin.printer_set'),
            'url' => url('splitPackages').'?num='
        ]);
    }

    //拆分单号处理
    public function splitPackagesRes(){
        header("Content-type:text/html;charset=utf-8");
        $wuliu_number = trim($_POST['scan']);
        //拆分成几个
        $numbers = intval($_POST['numbers']);

        //打印机编号
        $num = trim($_POST['num']);

        //先看打你 是不是以 EK 打头的
        if(strpos($wuliu_number,'EK') != 0){
            return [
                'code' => 'error',
                'msg' => '单号格式错误'
            ];
        }


        $no_numbers = [];
        $data = NoSplitPackage::get();
        foreach($data as $vo){
            $no_numbers[] = $vo -> number;
        }



        //如果单号是这里边的 则 不允许拆
        if(in_array($wuliu_number,$no_numbers)){
            return  [
                'code' => 'error',
                'msg' => '不能拆此单'
            ];
        }

        $package_info = Package::where('wuliu_num',$wuliu_number) -> first();
        if(!$package_info){
            return  [
                'code' => 'error',
                'msg' => '没有此单号'
            ];
        }



        //通过 包裹的申报重量判断 拆几个
        /*
        if(floatval($package_info -> weight) <= 3.5 ){
            $numbers = 2;
        }elseif(floatval($package_info -> weight) > 3.5 && floatval($package_info -> weight) <= 5.5 ) {
            $numbers = 3;
        }elseif(floatval($package_info -> weight) > 5.5 && floatval($package_info -> weight) <= 7.5 ) {
            $numbers = 4;
        }else{
            $numbers = 5;
        }
        */

        $is_pre = false;
        //查看下拆过没有
        $split_info = SplitPackage::where('package_id',$package_info -> id) -> first();
        if($split_info){

            if(!$split_info -> is_true_scan){
                //这单是没有提前拆过的
                return  [
                    'code' => 'error',
                    'msg' => '已拆分过'
                ];
            }else{
                //这单是提前拆过的
                $is_pre = true;
            }


        }



        //物流单号 替换开头 HQ
        $wuliu_number = str_replace('EK','HQ',$wuliu_number);

        //记录拆分
        $sp_numbers = [];
        for($i=1;$i<=$numbers;$i++){
            $temp = $wuliu_number.'-'.sprintf('%02s', $i);;
            $sp_numbers[] = $temp;
            //记录关系
            if(!$is_pre){
                SplitPackage::insert([
                    'package_id' => $package_info -> id,
                    'sp_numbers' => $temp,
                    'created_at' => time(),
                    'updated_at' => time(),
                    'package_wuliu_num' => $package_info -> wuliu_num,
                    'address' => $package_info -> address,
                    'userid' => $package_info -> userid,
                    'province' => $package_info -> province,
                    'city' => $package_info -> city,
                    'zip' => $package_info -> zip,
                    'name' => $package_info -> name,
                    'tel' => $package_info -> tel,
                    'from_area' => $package_info -> from_area,
                    'no' => $i,
                    'is_true_scan' => 0
                ]);
            }else{
                //如果拆过的话， 把这单更新成 真实扫过
                SplitPackage::where('package_id',$package_info -> id) -> update([
                    'is_true_scan' => 0
                ]);
            }

        }

        //exit;

        if(count($sp_numbers)){
            foreach($sp_numbers as $vo){
                $pdf_file = PrintSequeue::makeSPdf($vo);
                //保存队列
                PrintSequeue::addQueue(1,$vo,$pdf_file,$num);
            }
        }

    }


    //初始化用户数据
    public function initUser(){
        $users = DB::table('wxuser') -> get();
        $model = new MallApi();
        foreach($users as $vo){
            if(!$vo -> erp_id ){
              continue;
            }
            $addres = $model -> getCustomer([
                'CustomerName' => str_replace('?','',$vo -> erp_id),
                'CustomerDesc' => '商城系统初始化添加',
                'Remark' => $vo -> nickname,
            ]);
            //Log::info($addres);
            //var_dump($addres);exit;
            if(!count($addres)){
              $model -> addCustomer([
                'CustomerName' => str_replace('?','',$vo -> erp_id),
                'CustomerDesc' => '商城系统初始化添加',
                'Remark' => $vo -> nickname,
              ]);
              var_dump($vo -> nickname);
            }
            sleep(1);
        }
    }



    //查交货单中 是否有重复包裹
    public function checkRepeatBags(){

        //交货单
        $send_order_list = DB::table('send_order_list')  ->  get();
        //var_dump($send_order_list);

        //dump($send_order_list);
        $package_ids_temps = [];
        $wrong_arr =  [];
        $wrong_arrs =  [];
        $count_package = 0;

        $table_package = 0;
        //dd($send_order_list);
        foreach($send_order_list as $k => $vo){

            //通过交货单号 找托盘
            $tuopan = DB::table('batch_packages') -> where([
                'send_order_id' => $vo -> id
            ]) -> get();


            $package_ids = [];
            foreach($tuopan as $value_tuopan){
                $package_ids_temp = explode(',',$value_tuopan -> package_ids);
                foreach($package_ids_temp as $value_temp){
                    $package_ids[] = $value_temp;
                }

            }



            //dd($package_ids);
            //把所有id 都找出来
            foreach($package_ids as $value_temp){

                $count_package ++;
                if(!in_array($value_temp,$package_ids_temps)){
                    $package_ids_temps[] = $value_temp;
                }else{
                    $wrong_arr[$vo -> id][] = $value_temp;
                }
            }





            //dd($vo);
        }




        dd($wrong_arr);
        dump($count_package);
        dump(count($package_ids_temps));
        dump(count(array_unique($package_ids_temps)));
        dump($table_package);

    }


    //ftp测试
    public function testftp(){
        $status  =  FTP :: connection() -> getDirListing('./Label');
        dd($status);
    }


    //计算最高最低价格
    public function testMinPrice(){

        $json = json_decode($json,true);
        $bill = $json['AwbBilled'];
        $min = 999999;
        $max  = 0;
        $kk = '';
        $kkk = '';
        $temp_arr = [];


        //全部的
        $res = [];

        foreach($bill as $k => $vo){
            $res[$vo['customer_code']][$k]['w_weight'] = $vo['w_weight'];
            $res[$vo['customer_code']][$k]['actual_amount'] = $vo['actual_amount'];
            continue;
            if($vo['w_weight'] <= 1){
                $temp = $vo['actual_amount'];



                if($temp < $min){
                    $min = $temp;
                    $kk = $k;
                }

                if($temp > $max){
                    $max = $temp;
                    $kkk = $k;
                }


            }




        }


        $cellData[] = [
            '客户',
            '重量',
            '价格',
        ];

        foreach($res as $k=> $vo){
            foreach($vo as $value){
                $cellData[] = [
                    (string)$k,
                    (string)$value['w_weight'],
                    (string)$value['actual_amount'],
                ];
            }

        }


        Excel::create(date('Y-m-d-H-i'),function($excel) use ($cellData){
            $excel->sheet('导出历史订单', function($sheet) use ($cellData){
                $sheet->rows($cellData);
                for ($i = 1; $i <= count($cellData[0]) + 1; $i++) {
                    $sheet->setWidth($this->cellLetter[$i - 1], 30);
                }
            });
        })->export('xlsx');
        //dd($res);
    }



    //创建公众号菜单
    function createMenu(){

        $config = [
            'app_id' => env('WECHAT_OFFICIAL_ACCOUNT_APPID'),
            'secret' => env('WECHAT_OFFICIAL_ACCOUNT_SECRET'),
            'token' => env('WECHAT_OFFICIAL_ACCOUNT_TOKEN'),

            'response_type' => 'array',

            'log' => [
                'level' => 'debug',
                'file' => __DIR__.'/wechat.log',
            ],
        ];

        $app = Factory::officialAccount($config);
        $buttons = [
            [
                "name"       => "商城",
                "sub_button" => [
                    [
                        "type" => "miniprogram",
                        "name" => "寰球商城",
                        "url" => "pages/index/index",
                        "appid" => "wx6bfef6b0c2c3995b",
                        "pagepath" => "pages/index/index"
                    ],
                    [
                        "type" => "view",
                        "name" => "我的库存",
                        "url" => "http://hqyp.fenith.com/market"
                    ],
                    [
                        "type" => "view",
                        "name" => "余额明细",
                        "url" => "http://hqyp.fenith.com/priceTable"
                    ],



                    // [
                    //     "type" => "click",
                    //     "name" => "欧洲轻奢",
                    //     "key"  => "M_OUZHOU"
                    // ],
                    // [
                    //     "type" => "click",
                    //     "name" => "日本直邮",
                    //     "key"  => "M_RIBEN"
                    // ],
                    // [
                    //     "type" => "click",
                    //     "name" => "德国直邮",
                    //     "key"  => "M_DEGUO"
                    // ],
                    // [
                    //     "type" => "click",
                    //     "name" => "澳洲直邮",
                    //     "key"  => "M_AOZHOU"
                    // ],
                    /*
                    [
                        "type" => "view",
                        "name" => "国内现货",
                        "url"  => "http://hqyp.fenith.com/market"
                    ],
                    [
                        "type" => "view",
                        "name" => "欧洲轻奢",
                        "url"  => "http://hqyp.fenith.com/market?url_type=2"
                    ],
                    [
                        "type" => "view",
                        "name" => "日本直邮",
                        "url" => "http://hqyp.fenith.com/market?url_type=6"
                    ],
                    [
                        "type" => "view",
                        "name" => "德国直邮",
                        "url" => "http://hqyp.fenith.com/market?url_type=4"
                    ],
                    [
                        "type" => "view",
                        "name" => "澳洲直邮",
                        "url" => "http://hqyp.fenith.com/market?url_type=5"
                    ],
                    */
                ],
            ],
            [
                "name"       => "物流返点",
                "sub_button" => [
                    /*
                    [
                        "type" => "view",
                        "name" => "香港入仓预报",
                        "url"  => "http://hqyp.fenith.com/repertory/home"
                    ],
                    [
                        "type" => "view",
                        "name" => "我要发货",
                        "url"  => "http://hqyp.fenith.com/etk/index"
                    ],
                    [
                        "type" => "view",
                        "name" => "我的国际物流",
                        "url"  => "http://hqyp.fenith.com/home"
                    ],
                    */
                    [
                        "type" => "miniprogram",
                        "name" => "我要返点",
                        "url" => "pages/back/back",
                        "appid" => "wx096e610968df2b18",
                        "pagepath" => "pages/back/back"
                    ],
                    [
                        "type" => "miniprogram",
                        "name" => "寰球物流助手",
                        "url" => "pages/index/index",
                        "appid" => "wx096e610968df2b18",
                        "pagepath" => "pages/index/index"
                    ]
                ],
            ],
            [
                "name"       => "服务中心",
                "sub_button" => [
                    [
                        "type" => "click",
                        "name" => "联系客服",
                        "key"  => "CALL_KF"
                    ],
                    [
                        "type" => "view",
                        "name" => "邀请好友",
                        "url"  => "http://hqyp.fenith.com/myfriend"
                    ],
                    /*
                    [
                        "type" => "click",
                        "name" => "特价机票",
                        "key" => "FLY"
                    ],
                    [
                        "type" => "click",
                        "name" => "民宿酒店",
                        "key" => "HOTEL"
                    ],
                    */
                ]
            ]
        ];

        dump(json_encode($buttons));

        $res = $app->menu->create($buttons);
        dd($res);
    }



    //关邮e通
    function eLoginRes(){
        //这是密码加密字符串
        file_put_contents(storage_path('logs/elogin.txt'),$_POST['data']);
        //保存加密字符串
        Log::info('保存加密字符串');
        Log::info(json_encode($_POST));
    }

    //保存关邮e通的cookie文件
    public function saveCookieFile(){
        exit;
        Log::info('save_cookie_file');
        Log::info(json_encode($_FILES));
        file_put_contents(storage_path('logs/elogin_cookie.txt'),file_get_contents($_FILES['cookie_file']['tmp_name']));
        sleep(1);

        //启动命令
        Log::info('登录成功开始抓取');

        //只拿35天以后的数据
        $time = time() - 86400*35;
        for($i = 0;$i<999999;$i+=10){
            $datas = DB::table('packages')
                -> where('wuliu_num','<>','')
                -> where('created_at','>=',$time)
                -> where('flag',0)
                -> where('is_end_mail',0)
                -> limit(10)
                -> offset($i)
                -> orderBy('id','desc')
                ->get();

            if(!$datas){
                echo 'ending';exit;
            }
            foreach($datas as $vo){
                Log::info('请求队列开始');
                dispatch(new GetMailStatus(Package::find($vo -> id)));
                sleep(1);

            }
            sleep(2);
        }

    }

    public function eLogin(){
        //重试登录 3次
        for ($i=0;$i<3;$i++){
            $login_res = Package::eLogin();
            if(!$login_res){
                continue;
            }
        }


    }


    //打印送货单
    public function repertoryPrint(){
        $repertory_id = $_GET['repertory_id'];
        $info = Repertory::find($repertory_id);

        $config = config('admin.repertory_sub_type');

        return view('home.pdfPage.deliverGoods') -> with([
            'info' => $info,
            'name' => $info -> name,
            'sub_type' => $config[$info -> sub_type],
            'canghao' => $info -> canghao,
            'tel' => $info -> tel,
            'card' => $info -> card,
            'num' => $info -> num,
            'numbers' => $info -> numbers,
            'weight' => $info -> weight,
            'title' => $info -> sub_type == 2?'送貨單':'簽收單'
        ]);

    }


    function test(){

        $area_id = 13;
        $queue_info = DB::table('sequeue')
            -> where([
                'type' => 3,
                'from_area' => $area_id,
                'date' => date('Ymd'),
            ]) -> first();
        if(!$queue_info){
            DB::table('sequeue')
                -> insertGetId([
                    'type' => 3,
                    'date' => date('Ymd'),
                    'order_num' => 1,
                    'from_area' => $area_id
                ]);
            $order_num = date('Ymd').'-'.sprintf('%03s',$area_id).'-1';
        }else{
            //取出来 + 1
            DB::table('sequeue')
                -> where([
                    'type' => 3,
                    'from_area' => $area_id,
                    'date' => date('Ymd'),
                ]) -> update([
                    'order_num' => intval($queue_info -> order_num) + 1
                ]);

            $order_num = date('Ymd').'-'.sprintf('%03s',$area_id).'-'.(intval($queue_info -> order_num) + 1);
        }


        dd($order_num);



        exit;
        //关邮e通返回值解析
        $data = DB::table('packages') -> where([
            'id' => $_GET['id']
        ]) -> first();
        $table = $data -> mail_status_table;



        $html = str_replace("\n","",trim($table));


        $pattern = "/<td style=\"text-align: center;\">([\s\S]*?)<\/td>/is";

        preg_match_all($pattern, $html, $output1);

        if(isset($output1[1])){
            $temp = [];
            foreach($output1[1] as $vo){
                if($vo){
                    $temp[] = $vo;
                }
            }
        }

        dd($temp);


        exit;
        $model = new Order();
        $res =  $model -> getPackageNum(20,'',39);
        dd($res);
        //1-125

        $id = [];
        for($i = 2;$i<=126;$i++){
            $id[] = $i;
        }
        for($i=0;$i<=125;$i++){

            $rand = rand(0,count($id)-1);

            echo $id[$rand];
            echo "</br>";
            unset($id[$rand]);
            $id = array_values($id);

        }

        /*
        for($i=0;$i<=125;$i++){
            $temp = rand(1,15);
            if($temp == 1){
                echo "一等奖CPB钻光隔离";
                echo "</br>";
            }elseif($temp == 2){
                echo "二等奖嘉娜宝酵素洁颜粉";
                echo "</br>";
            }elseif($temp == 3){
                echo "三等奖尤妮佳化妆棉";
                echo "</br>";
            }else{
                echo "谢谢参与";
                echo "</br>";
            }
        }


        /*
        for($i=0;$i<=125;$i++){
            //生成随机时间
            $rand_b = [
                rand(strtotime('2019-03-01 08:00:00'),strtotime('2019-03-01 23:00:00')),
                rand(strtotime('2019-03-02 08:00:00'),strtotime('2019-03-02 23:00:00')),
                rand(strtotime('2019-03-03 08:00:00'),strtotime('2019-03-03 23:00:00')),
            ];
            echo date('Y-m-d H:i:s',$rand_b[rand(0,2)]);
            echo "</br>";
        }
        */


        exit;
//更新unionid
        $config = [
            'app_id' => env('WECHAT_OFFICIAL_ACCOUNT_APPID'),
            'secret' => env('WECHAT_OFFICIAL_ACCOUNT_SECRET'),
        ];

        $app = Factory::officialAccount($config);
        $accessToken = $app->access_token;
        $token = $accessToken->getToken(); // token 数组  token['access_token'] 字符串

        $users = DB::table('wxuser')
            -> whereNull('unionid') -> orWhere('unionid','=','')
            -> get();

        foreach($users as $vo){
            $openid  = $vo -> openid;
            $url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$token['access_token'].'&openid='.$openid.'&lang=zh_CN';

            $res = file_get_contents($url);
            $json = json_decode($res,true);
            var_dump($json);
            if($json && isset($json['unionid'])){
                DB::table('wxuser') -> where([
                    'id' => $vo -> id
                ]) -> update([
                    'unionid' => $json['unionid']
                ]);
            }
        }

        exit;


        $info = DB::table('mp_scan_goods_info')
            -> where([
                'flag' => 0,
                'jap_name' => ''
            ])
            -> select([
                'id'
            ])
            -> get();
        foreach($info as $vo){
            dispatch(new Reptile(MpScanInfo::find($vo -> id)));
        }



        exit;

        //更新unionid
        $config = [
            'app_id' => env('WECHAT_OFFICIAL_ACCOUNT_APPID'),
            'secret' => env('WECHAT_OFFICIAL_ACCOUNT_SECRET'),
        ];

        $app = Factory::officialAccount($config);
        $accessToken = $app->access_token;
        $token = $accessToken->getToken(); // token 数组  token['access_token'] 字符串

        $users = DB::table('wxuser')
            -> where([
                'id' => 945
            ])
            //-> whereNull('unionid') -> orWhere('unionid','=','')
            -> get();

        foreach($users as $vo){
            $openid  = $vo -> openid;
            $url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$token['access_token'].'&openid='.$openid.'&lang=zh_CN';

            $res = file_get_contents($url);
            $json = json_decode($res,true);
            dump($json);
            if($json && isset($json['unionid'])){
                DB::table('wxuser') -> where([
                    'id' => $vo -> id
                ]) -> update([
                    'unionid' => $json['unionid']
                ]);
            }
        }
        exit;
        //echo 'success';exit;




        //dd(NumberQueue::addQueueNo(1));


        exit;
        //去除最早一拆二的，后来让你临时清空，并且备份的

        $split_temp_save = DB::table('packages_split_20181226') ->groupBy('package_id') -> select('package_id') -> get();

        $split_temp = DB::table('packages_split') ->groupBy('package_id') -> select('package_id') -> get();

        $commodity_temp = DB::table('commodity_code_temp') -> groupBy('package_id') -> select('package_id') -> get();


        $split_temp_arr = [];
        foreach($split_temp as $vo){
            $split_temp_arr[]  = $vo -> package_id;
        }

        foreach($split_temp_save as $vo){
            $split_temp_arr[]  = $vo -> package_id;
        }

        foreach($commodity_temp as $vo){
            $split_temp_arr[]  = $vo -> package_id;
        }
        $split_temp_arr = array_unique($split_temp_arr);
        foreach($split_temp_arr as $k => $vo){
            if($vo == null){
                unset($split_temp_arr[$k]);
            }
        }
Log::info('export number'.count($split_temp_arr));

        $packages =
            DB::table('packages')
                -> join('batch_packages_relation','packages.id','=','batch_packages_relation.package_id')
                -> join('batch_packages','batch_packages_relation.batch_id','=','batch_packages.id')
            -> where('packages.created_at','>=',strtotime('2018-12-01'))
                -> where(function($query){
                    $query -> whereNull('batch_packages.send_order_id') -> orWhere('batch_packages.send_order_id',0);
                })
                -> whereNotIn('packages.id',$split_temp_arr)
                -> select('packages.id','packages.wuliu_num','packages.created_at')
            -> get();
        //dd($packages);


        //12/1没有生成交货单的单号
        $cellData = [] ;
        foreach($packages as $k=> $vo){
            $cellData[] = [
                (string)$vo -> id,
                (string)$vo -> wuliu_num,
                (string)date('Y-m-d H:i',$vo -> created_at),
            ];
        }


        Excel::create(date('Y-m-d-H-i'),function($excel) use ($cellData){
            $excel->sheet('单号', function($sheet) use ($cellData){
                $sheet->rows($cellData);
            });
        })->export('xlsx');
    }


    public function printPdf(){
        return view('home.printPdf');
    }


    //群发消息
    public function sendMessageAll(){
        $config = [
            'app_id' => env('WECHAT_OFFICIAL_ACCOUNT_APPID'),
            'secret' => env('WECHAT_OFFICIAL_ACCOUNT_SECRET'),
            'token' => env('WECHAT_OFFICIAL_ACCOUNT_TOKEN'),

            'response_type' => 'array',

            'log' => [
                'level' => 'debug',
                'file' => __DIR__.'/wechat.log',
            ],
        ];

        $app = Factory::officialAccount($config);

        //发送状态
        //$send_res = $app->broadcasting->status('3147483657');

        //dd($send_res);


        //$list = $app->material->list('news', 0, 10);
        //dd($list);
        //exit;
        $mediaId = 'MXdM_V0UEtaKnLUyX1-yE6SGgjg9AxdoHK2JAOPBTGk';
        //$mediaId = 'MXdM_V0UEtaKnLUyX1-yE7VypnZBFmPp3YD09oMp5Ew';
        //$res = $app->broadcasting->sendNews($mediaId); //群发
        //dd($res);
        //预览
        //$res = $app->broadcasting->previewNews($mediaId, 'oHPu70XdPl1qeGJHkcOYjndhVDMs');
        //dd($res);
        $users = DB::table('wxuser') -> select(['openid'])  -> get();
        $openids = [];
        foreach($users as $vo){
            if($vo -> openid){
                $openids[] = $vo -> openid;
            }
        }
        //dd($openids);

        foreach($openids as $vo){
            //预览
            $res = $app->broadcasting->previewNews($mediaId, $vo);
            dump($res);
        }


        //$res = $app->broadcasting->sendNews($mediaId,$openids ); //测试小群



        /*
         [
            'oHPu70XdPl1qeGJHkcOYjndhVDMs', //对方正在输入
            'oHPu70YKqF7SgvGx4bna8id2xtM0', //寰球优品生活-Leo
            'oHPu70VqAuwNv1_oUoq9TjY3r5nY', //小周
            'oHPu70fFl6dCC4jLyFB7Jd_dQ4SA', //richer

        ]

         */

        //dd($res);

    }


    //获取素材列表
    public function getNewsList(){
        $config = [
            'app_id' => env('WECHAT_OFFICIAL_ACCOUNT_APPID'),
            'secret' => env('WECHAT_OFFICIAL_ACCOUNT_SECRET'),
            'token' => env('WECHAT_OFFICIAL_ACCOUNT_TOKEN'),

            'response_type' => 'array',

            'log' => [
                'level' => 'debug',
                'file' => __DIR__.'/wechat.log',
            ],
        ];

        $app = Factory::officialAccount($config);

        $list = $app->material->list('news', 0, 10);
        dd($list);
    }

    public function test2(){
        var_dump($_REQUEST);
    }

    //初始化批次
    public function initBatchPackage(){

        //初始化repertory
        $info = DB::table('repertory') -> get();
        foreach($info as $vo){
            $temp = preg_replace('# #','',strtoupper($vo -> numbers));

            DB::table('repertory') -> where([
                'id' => $vo -> id
            ]) -> update([
                'numbers' => $temp,
                'updated_at' => time()
            ]);
        }


        /*
        //修复数据
        //76 -205
        //$str = 'EK434686283HK,EK434728519HK,EK434728465HK,EK434686513HK,EK434686632HK,EK434728638HK,EK434686297HK,EK434686592HK,EK434686650HK,EK434686249HK,EK434686500HK,EK434686204HK,EK434686677HK,EK434686646HK,EK434686535HK,EK434728417HK,EK434728244HK,EK434686751HK,EK434686425HK,EK434728329HK,EK434686371HK,EK434686368HK,EK434044015HK,EK434686725HK,EK434686589HK,EK434728505HK,EK434728275HK,EK434686734HK,EK434728641HK,EK434686345HK,EK434686487HK,EK434686694HK,EK434686717HK,EK434728553HK,EK434728655HK,EK434686703HK,EK434728598HK,EK434686748HK,EK434728377HK,EK434686663HK,EK434732703HK,EK434686685HK,EK434763215HK,EK434728672HK,EK434728669HK,EK434686527HK,EK434686495HK,EK434686558HK,EK434686615HK,EK434686601HK,EK434763192HK,EK434686235HK,EK434686218HK,EK434686252HK,EK434730096HK,EK434686221HK,EK434686337HK,EK434686306HK,EK434686456HK,EK434686439HK,EK434686544HK,EK434044032HK,EK434737780HK,EK434686354HK,EK434686266HK,EK434763201HK,EK434686399HK,EK434686408HK,EK434686460HK,EK434686385HK,EK434686270HK,EK434686561HK,EK434686323HK,EK434686310HK,EK434686442HK,EK434686473HK';

        //60 -206
        $str =  'EK433806492HK,EK433806546HK,EK433806501HK,EK433806489HK,EK433806461HK,EK433806529HK,EK433806563HK,EK433806475HK,EK433806458HK,EK433806532HK,EK433806515HK,EK433806550HK,EK433806651HK,EK433806585HK,EK433806665HK,EK433806679HK,EK433806625HK,EK433806617HK,EK433806603HK,EK433806696HK,EK433806682HK,EK433806648HK,EK433806634HK,EK433806594HK,EK434043584HK,EK434043624HK,EK434043615HK,EK433806872HK,EK433806815HK,EK433806775HK,EK433807541HK,EK433806869HK,EK433806784HK,EK434043575HK,EK433806753HK,EK433806838HK,EK433806886HK,EK433806841HK,EK433806855HK,EK433807538HK,EK433806909HK,EK433806767HK,EK433806926HK,EK434043598HK,EK433806824HK,EK433806807HK,EK433806736HK,EK433806722HK,EK433806890HK,EK433806798HK,EK433806740HK,EK433806719HK,EK434043607HK,EK434043536HK,EK434043522HK,EK434043519HK,EK434043540HK,EK434043553HK,EK434043567HK,EK433806705HK';

        $arr = explode(',',$str);
        $count = 0;
        foreach($arr as $vo){
            $count ++ ;
            //查找包裹编号
            $package_info = DB::table('packages') -> where([
                'wuliu_num' => $vo
            ]) -> first();
            DB::table('batch_packages_relation') -> insert([
                'batch_id' => 206,
                'package_id' => $package_info -> id,
                'created_at' => time(),
                'updated_at' => time(),
                'repertory_id' => 178
            ]);
        }
        */




        //dd($arr);

    }



    public function saveImg(){
        Log::info(json_encode($_FILES));
        if(!isset($_FILES['upload-file']['tmp_name'])){
            return [
                'code' => 'error',
                'msg' => '没有文件'
            ];
        }


        //设置允许上传文件的类型
        $type=array("jpg","gif","bmp","jpeg","png");

        if(!in_array(strtolower($this -> fileext($_FILES['upload-file']['name'])),$type))
        {
            return [
                'code' => 'error',
                'msg' => '上传文件格式错误'
            ];
        }

        $config = env('APP_ENV');
        if($config != 'local'){
            if($_FILES['upload-file']['size'] >= 1024*1024 * 10){
                return [
                    'code' => 'error',
                    'msg' => '上传文件不能大于10M'
                ];
            }

        }



        $newFileName = md5(time().rand(10000,99999)).'.jpg';
        $is_move = move_uploaded_file($_FILES['upload-file']['tmp_name'],public_path().'/uploads/return/'.$newFileName);
        if(!$is_move){
            Log::info(json_encode($_FILES));
            return [
                'code' => 'error',
                'msg' => '上传失败'
            ];
        }
        //file_put_contents(public_path().'/uploads/return/'.$newFileName,file_get_contents($_FILES['upload-file']['tmp_name']));

        //$base=base64_decode($file);
        //Image::make(file_get_contents($file->getRealPath()))->save(public_path().'/uploads/return/'.$newFileName);
        //$res = $rew = \Intervention\Image\Facades\Image::make(file_get_contents($file->getRealPath()))->save(public_path().'/uploads/return/'.$newFileName,50 );

        return [
            'code' => 'success',
            'msg' => url('uploads/return').'/'.$newFileName
        ];
    }


    //获取文件后缀名函数
    function fileext($filename)
    {
        return substr(strrchr($filename, '.'), 1);
    }

    function ResizeImage($uploadfile,$maxwidth,$maxheight,$name)
    {
        //取得当前图片大小
        $width = imagesx($uploadfile);
        $height = imagesy($uploadfile);
        $i=0.5;
        //生成缩略图的大小
        if(($width > $maxwidth) || ($height > $maxheight))
        {
            $newwidth = $width * $i;
            $newheight = $height * $i;
            if(function_exists("imagecopyresampled"))
            {
                $uploaddir_resize = imagecreatetruecolor($newwidth, $newheight);
                imagecopyresampled($uploaddir_resize, $uploadfile, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
            }
            else
            {
                $uploaddir_resize = imagecreate($newwidth, $newheight);
                imagecopyresized($uploaddir_resize, $uploadfile, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
            }

            ImageJpeg ($uploaddir_resize,$name);
            ImageDestroy ($uploaddir_resize);
        }else {
            ImageJpeg ($uploadfile,$name);
        }
    }


    //扫码打印
    public function printScanPage(){
        //dd(url('pdf'));
        return view('home.printScanPage');
    }

    //扫码打印处理
    public function printScanRes(){
        $numbers = trim($_POST['numbers']);
        //查看此单号  有没有 -  有 - 说明是要拆的单号
        //if(strstr($numbers,'-')){
        if(false){
            $split_info = SplitPackage::where('sp_numbers',$numbers) -> first();
            if(!$split_info){
                return [
                    'code' => 'error',
                    'msg' => '没有此单号'
                ];
            }

            $package_info = Package::where([
                'split_id' => $split_info -> id,
                'flag' => 0
            ]) -> first();
            if(!$package_info){
                return [
                    'code' => 'error',
                    'msg' => '此单号没有对应包裹号'
                ];
            }


            if(!$package_info -> pdf){
                return [
                    'code' => 'error',
                    'msg' => '此单号对应的单号pdf还没有生成'
                ];
            }

            //添加pdf面单 到 008
            PrintSequeue::addQueue(0,$numbers,url('temp_pdf').'/'.$package_info -> pdf,'008');
            //修改 packages_split is_print 为 1
            SplitPackage::where('id',$split_info -> id) -> update([
                'is_print' => 1,
                'updated_at' => time()
            ]);
            return [
                'code' => 'success',
                'msg' => '扫描成功'
            ];
        }else{


            $package_info = Package::where([
                'wuliu_num' => $numbers,
                'flag' => 0
            ])-> first();
            if(!$package_info){
                $package_info = Package::where([
                    'package_num' => $numbers,
                    'flag' => 0
                ])-> first();
                if(!$package_info){

                    //判断是不是小程序编号
                    $temp_info = DB::table('mp_temp_package_number')
                        -> where([
                            'package_num' => $numbers
                        ]) -> first();
                    if($temp_info){
                        $order_info = DB::table('order')
                            -> where([
                                'flag' => 0,
                                'mp_package_id' => $temp_info -> id
                            ]) -> first();
                        if($order_info){
                            $package_info = DB::table('packages')
                                -> where([
                                    'flag' => 0,
                                    'order_id' => $order_info -> id
                                ]) -> first();
                        }
                    }

                    if(!$package_info){
                        return [
                            'code' => 'error',
                            'msg' => '此单号没有对应包裹号'
                        ];
                    }




                }

            }


            if(!$package_info -> pdf){
                return [
                    'code' => 'error',
                    'msg' => '此单号对应的单号pdf还没有生成'
                ];
            }

            if($package_info -> is_print){
                return [
                    'code' => 'error',
                    'msg' => '重复打印'
                ];
            }



            //添加pdf面单 到 008
            PrintSequeue::addQueue(2,$numbers,url('temp_pdf').'/'.$package_info -> pdf,'008');

            DB::table('packages')
                -> where([
                    'id' => $package_info -> id
                ]) -> update([
                    'is_print' => 1
                ]);



            return [
                'code' => 'success',
                'msg' => '扫描成功'
            ];
        }

    }


    //扫描商品编码
    public function scanCommodityCode(){
        return view('home.scanCommodityCode');
    }

    //扫描商品编码-包裹部分
    public function scanCommodityCodePackage(){
        //存储母单号
        //先看下母单号 存不存在
        $info = CommodityCodeTemp::where('number',trim($_POST['numbers'])) -> first();
        if($info){
            return [
                'id' => $info -> id
            ];
        }else{

            //找下这个单号所在的区域
            $package_info = Package::where('wuliu_num',trim($_POST['numbers'])) -> first();
            if($package_info){
                $area_id = $package_info -> from_area;
                $package_id = $package_info -> id;
            }else{
                $area_id = 0;
                $package_id = 0;
            }


            //增加
            $id = CommodityCodeTemp::insertGetId([
                'number' => trim($_POST['numbers']),
                'created_at' => time(),
                'updated_at' => time(),
                'area_id' => $area_id,
                'package_id' => $package_id
            ]);
            return [
                'id' => $id
            ];

        }

    }


    //扫描商品编码-商品部分
    public function scanCommodityCodeGoods(){
        $id = $_GET['id'];
        $package_info = CommodityCodeTemp::find($id);
        $code_info = CommodityCode::where('fid',$id) -> orderBy('id','desc') -> get();
        return view('home.scanCommodityCodeGoods') -> with([
            'package_info' => $package_info,
            'code_info' => $code_info
        ]);
    }

    public function scanCommodityCodeGoodsRes(){
        //增加
        CommodityCode::insert([
            'fid' => $_POST['fid'],
            'code' => $_POST['numbers'],
            'created_at' => time(),
            'updated_at' => time(),
        ]);
        return [
            'code' => 'success',
            'msg' => '成功'
        ];


        $info = CommodityCode::where([
            'fid' => $_POST['fid'],
            'code' => $_POST['numbers'],
        ]) -> first();
        if(!$info){
            //增加
            CommodityCode::insert([
                'fid' => $_POST['fid'],
                'code' => $_POST['numbers'],
                'created_at' => time(),
                'updated_at' => time(),
            ]);
            return [
                'code' => 'success',
                'msg' => '成功'
            ];
        }else{
            return [
                'code' => 'error',
                'msg' => '已存在'
            ];
        }
    }


    //删除
    public function deleteScanCommodityCode(){
        CommodityCode::where('id',$_POST['id']) -> delete();
        return [
            'code' => 'success',
            'msg' => '删除成功'
        ];
    }


    //查找包裹
    public function scanFindGoods(){
        $info = FindGoods::where('status',0) -> get();

        return view('home.scanFindGoods') -> with([
            'info' => $info
        ]);
    }

    public function scanFindGoodsRes(){
        $info = FindGoods::where('code',$_POST['numbers']) -> where('status',0) -> first();
        if($info){
            FindGoods::where('id',$info -> id) -> update([
                'status' => 1,
                'updated_at' => time()
            ]);
            return [
                'code' => 'success',
                'msg' => '找到了'
            ];
        }else{
            return [
                'code' => 'error',
                'msg' => '没有'
            ];
        }
    }



    //给单号，虚拟拆箱功能
    public function moniSplit(){
        if($_GET['ttttt'] == 'ttttt'){
            $numbers_temp = [
                'EK434728244HK',
                'EK434728275HK',
            ];

            $count_sp_numbers = 2;

            foreach($numbers_temp as $wuliu_number){
                $package_info = Package::where('wuliu_num',$wuliu_number) -> first();
                if(!$package_info){
                    echo $wuliu_number.',无此单号'."<br>";
                    continue;
                }

                //查看下拆过没有
                $split_info = SplitPackage::where('package_id',$package_info -> id) -> first();
                if($split_info){
                    echo $wuliu_number.',已经拆过'."<br>";
                    continue;
                }


                //记录拆分
                $sp_numbers = [];
                for($i=1;$i<=$count_sp_numbers;$i++){
                    $temp = $wuliu_number.'-'.sprintf('%02s', $i);;
                    $sp_numbers[] = $temp;
                    //记录关系
                    SplitPackage::insert([
                        'package_id' => $package_info -> id,
                        'sp_numbers' => $temp,
                        'created_at' => time(),
                        'updated_at' => time(),
                        'package_wuliu_num' => $package_info -> wuliu_num,
                        'address' => $package_info -> address,
                        'userid' => $package_info -> userid,
                        'province' => $package_info -> province,
                        'city' => $package_info -> city,
                        'zip' => $package_info -> zip,
                        'name' => $package_info -> name,
                        'tel' => $package_info -> tel,
                        'from_area' => $package_info -> from_area,
                        'no' => $i,
                        'is_pre' => 1,
                        'is_true_scan' => 1
                    ]);
                }

                echo $wuliu_number.',拆分成功'."<br>";




            }
        }else{
            echo 'error';
        }
    }


    //匹配包裹
    public function matchPackage(){
        $matchPackage_info = DB::table('match_packages')  -> where([
            'is_first' => 1,
            'flag' => 0
        ]) -> orderBy('id','asc') ->  get();

        //分成3波
        $temp = [];
        foreach($matchPackage_info as $k => $vo){
            $temp[$k] = $vo;
        }
        //dd($temp);



        return view('home.matchPackage') -> with([
            'data' => array_chunk($temp,3)
        ]);
    }

    public function matchPackageAjax(){
        $numbers = trim($_POST['numbers']);
        $match_type = $_POST['match_type']; //1需要添加  2 不需要


        //这个可能是标签  也可能是 包裹
        if(strstr($numbers,'-')){
            //标签 直接在 拆包裹里边找
            $sp_info = DB::table('packages_split_20181226') -> where('sp_numbers',$numbers) -> first();

            if($sp_info){
                //看下 是否存在matchpackaes 中
                $match_info = DB::table('match_packages') -> where('label',$sp_info -> sp_numbers) -> first();
                if($match_info){
                    //存在 返回重复扫描
                    return  [
                        'code' => 'error',
                        'msg' => '重复扫描'
                    ];
                }else{
                    //添加
                    $add_res = $this -> addMatchInfo($sp_info,1,$match_type,$numbers);
                    return $add_res;
                }

            }else{
                //在第二批拆单里边找
                $sp_info2 = DB::table('packages_split') -> where('sp_numbers',$numbers) -> first();
                if($sp_info2){



                    //看下 是否存在matchpackaes 中
                    $match_info = DB::table('match_packages') -> where('label',$sp_info2 -> sp_numbers) -> first();
                    if($match_info){
                        //存在 返回重复扫描
                        return  [
                            'code' => 'error',
                            'msg' => '重复扫描'
                        ];
                    }else{
                        //添加
                        $add_res = $this -> addMatchInfo($sp_info2,2,$match_type,$numbers);
                        return $add_res;
                    }



                }else{
                    //也没有 那就返回错误
                    return  [
                        'code' => 'error',
                        'msg' => '拆单中没有此单号'
                    ];
                }
            }
        }else{
            //扫描的面单
            //通过面单 查找package_id
            $package_info = Package::where('wuliu_num',$numbers) -> first();
            if($package_info && $package_info -> split_id){
                //通过包裹 查找sp_id
                $split_info = SplitPackage::find($package_info -> split_id);
                if($split_info){
                    //看下 是否存在matchpackaes 中
                    $match_info = DB::table('match_packages') -> where('label',$split_info -> sp_numbers) -> first();
                    if($match_info){
                        //存在 返回重复扫描
                        return  [
                            'code' => 'error',
                            'msg' => '重复扫描'
                        ];
                    }else{
                        //添加
                        $add_res = $this -> addMatchInfo($split_info,2,$match_type,$numbers);
                        return $add_res;
                    }

                }else{
                    return  [
                        'code' => 'error',
                        'msg' => '有单号，没有拆单信息'
                    ];
                }


            }else{
                return  [
                    'code' => 'error',
                    'msg' => '压根没有此单号'
                ];
            }



        }

    }

    public function findSplitPackage(){

    }

    public function addMatchInfo($sp_info,$table,$match_type,$scan_number){

        //$match_type = 1 又找 又增加 $match_type = 2 只找


        //看此包裹id 在匹配包裹表中 出现没有，如果没出现 is_first = 1
        $temp_info = DB::table('match_packages') -> where('package_id',$sp_info -> package_id) -> where('is_first',1) -> first();
        $package_info = Package::find($sp_info -> package_id);
        if($temp_info){
            if(!$temp_info -> number){
                //此包裹内的都找到了
                return [
                    'code' => 3,
                    'msg' => '此拆单已经组成包裹了'
                ];
            }



            DB::table('match_packages') -> insert([
                'created_at' => time(),
                'updated_at' => time(),
                'label' => $sp_info -> sp_numbers,
                'wuliu_num' => $sp_info -> package_wuliu_num,
                'sp_id' => $sp_info -> id,
                //他的兄弟 一共还有几个
                'number' => 0,
                'scan_number' => $scan_number,
                //包裹编号
                'package_num' => $package_info -> package_num ,
                'package_id' => $sp_info -> package_id,
                'is_first' => 0
            ]);

            //fid number -1
            DB::table('match_packages') -> where('id',$temp_info -> id) -> update([
                'number' => intval($temp_info -> number) - 1
            ]);


            $matchPackage_info = DB::table('match_packages') -> where([
                'is_first' => 1,
                'flag' => 0
            ]) -> orderBy('id','asc') ->  get();

            //找 此match_id 是第几个
            $find_num = 0;
            foreach($matchPackage_info as $vo){
                $find_num ++ ;
                if($vo -> id == $temp_info -> id){
                    break;
                }
            }






            //说明找到了
            //这是他的第一个兄弟
            //返回他的兄弟id
            return [
                'code' => 1,
                'f_info' => [
                    'package_id' => $temp_info -> package_id,
                    'label' => $temp_info -> label,
                    'package_num' => $temp_info -> package_num,
                    'wuliu_num' => $temp_info -> wuliu_num
                ],
                'msg' => '第'.$find_num.'个,标签:'.$temp_info -> label.',编号:'.$temp_info -> package_num.',单号:'.$temp_info -> wuliu_num
            ];


        }else{
            if($match_type != 1){
                return [
                    'code' => 5,
                    'msg' => '不匹配'
                ];
            }


            //他就是这个包裹的第一个 新增

            //在拆分表里 查看他的兄弟有几个
            if($table == '1'){
                //在第一次拆分里找
                $package_sp = DB::table('packages_split_20181226') -> where('package_id',$sp_info -> package_id) -> count();
            }else{
                $package_sp = DB::table('packages_split') -> where('package_id',$sp_info -> package_id) -> count();
            }

            //添加匹配单
            DB::table('match_packages') -> insert([
                'created_at' => time(),
                'updated_at' => time(),
                'label' => $sp_info -> sp_numbers,
                'wuliu_num' => $sp_info -> package_wuliu_num,
                //他的兄弟 一共还有几个
                'number' => $package_sp - 1,
                //包裹编号
                'package_num' => $package_info -> package_num ,
                'package_id' => $sp_info -> package_id,
                'is_first' => 1,
                'scan_number' => $scan_number,
            ]);
            return [
                'code' => 2,
                'msg' => '已添加到匹配单'
            ];

        }



    }

    public function deleteMatchData(){
        //删除
        $id = $_POST['id'];
        DB::table('match_packages') -> where('id',$id) -> update([
            'flag' => 1
        ]);
        return [
            'code' => 'success'
        ];
    }


    public function deleteAllMatchData(){
        DB::table('match_packages') -> delete();
        return [
            'code' => 'success'
        ];
    }



    //补冲打印
    public function exPrint(){
        return view('home.exPrint');
    }

    public function exPrintAjax(){
        $number = trim($_POST['numbers']);
        $pdf = PrintSequeue::makeSPdf($number);
        PrintSequeue::addQueue(5,$number,$pdf,'009');
    }



    public function addTempData(){
        $number = DB::table('temp_data') -> count();
        return view('home.addTempData') -> with([
            'number' => $number
        ]);
    }

    public function addTempDataRes(){
        //这个可能是标签  也可能是 包裹
        $numbers = trim($_POST['numbers']);
        if(strstr($numbers,'-')){
            //标签 直接在 拆包裹里边找
            $sp_info = DB::table('packages_split_20181226') -> where('sp_numbers',$numbers) -> first();

            if($sp_info){
                //看下 是否存在temp_data 中
                $match_info = DB::table('temp_data') -> where('label',$sp_info -> sp_numbers) -> first();
                if($match_info){
                    //存在 返回重复扫描
                    return  [
                        'code' => 'error',
                        'msg' => '重复扫描'
                    ];
                }else{
                    $package_info = Package::find($sp_info -> id);

                    DB::table('temp_data') -> insert([
                        'created_at' => time(),
                        'updated_at' => time(),
                        'label' => $sp_info -> sp_numbers,
                        'wuliu_num' => $sp_info -> package_wuliu_num,
                        //包裹编号
                        'package_num' => $package_info -> package_num ,
                        'package_id' => $sp_info -> package_id,
                        'scan_number' => $numbers,
                    ]);
                    return [
                        'code' => 'success',
                        'msg' => '已添加到匹配单'
                    ];

                }

            }else{
                //在第二批拆单里边找
                $sp_info2 = DB::table('packages_split') -> where('sp_numbers',$numbers) -> first();
                if($sp_info2){



                    //看下 是否存在matchpackaes 中
                    $match_info = DB::table('temp_data') -> where('label',$sp_info2 -> sp_numbers) -> first();
                    if($match_info){
                        //存在 返回重复扫描
                        return  [
                            'code' => 'error',
                            'msg' => '重复扫描'
                        ];
                    }else{

                        $package_info = Package::find($sp_info2 -> id);
                        DB::table('temp_data') -> insert([
                            'created_at' => time(),
                            'updated_at' => time(),
                            'label' => $sp_info2 -> sp_numbers,
                            'wuliu_num' => $sp_info2 -> package_wuliu_num,
                            //包裹编号
                            'package_num' => $package_info -> package_num ,
                            'package_id' => $sp_info2 -> package_id,
                            'scan_number' => $numbers,
                        ]);
                        return [
                            'code' => 'success',
                            'msg' => '已添加到匹配单'
                        ];
                    }



                }else{
                    //也没有 那就返回错误
                    return  [
                        'code' => 'error',
                        'msg' => '拆单中没有此单号'
                    ];
                }
            }
        }else{
            //扫描的面单
            //通过面单 查找package_id
            $package_info = Package::where('wuliu_num',$numbers) -> first();
            if($package_info && $package_info -> split_id){
                //通过包裹 查找sp_id
                $split_info = SplitPackage::find($package_info -> split_id);
                if($split_info){
                    //看下 是否存在matchpackaes 中
                    $match_info = DB::table('temp_data') -> where('label',$split_info -> sp_numbers) -> first();
                    if($match_info){
                        //存在 返回重复扫描
                        return  [
                            'code' => 'error',
                            'msg' => '重复扫描'
                        ];
                    }else{
                        DB::table('temp_data') -> insert([
                            'created_at' => time(),
                            'updated_at' => time(),
                            'label' => $split_info -> sp_numbers,
                            'wuliu_num' => $split_info -> package_wuliu_num,
                            //包裹编号
                            'package_num' => $package_info -> package_num ,
                            'package_id' => $split_info -> package_id,
                            'scan_number' => $numbers,
                        ]);
                        return [
                            'code' => 'success',
                            'msg' => '已添加到匹配单'
                        ];
                    }

                }else{
                    return  [
                        'code' => 'error',
                        'msg' => '有单号，没有拆单信息'
                    ];
                }


            }else{
                return  [
                    'code' => 'error',
                    'msg' => '压根没有此单号'
                ];
            }



        }
    }


    //盘点
    public function checkGoods(){
        $check_goods = DB::table('check_goods')
            -> where([
                'flag' => 0
            ])
            -> orderBy('id','desc')
            -> get();
        foreach($check_goods as $k => $vo){
            $temp = DB::table('check_goods_detail') -> where([
                'check_goods_id' => $vo -> id,
                'flag' => 0
            ]) -> count();
            $check_goods[$k] -> count_goods = $temp;
        }

        return view('home.checkGoods') -> with([
            'check_goods' => $check_goods
        ]);
    }

    //新增盘点
    public function addCheckGoods(){
        return view('home.addCheckGoods');
    }
    //新增盘点处理
    public function addCheckGoodsRes(){
        $id = DB::table('check_goods') -> insertGetId([
            'goods_number' => $_POST['goods_number'],
            'created_at' => time(),
            'updated_at' => time(),
        ]);
        return [
            'code' => 200,
            'msg' => $id
        ];
    }

    //新增盘点清单
    public function addCheckGoodsDetail(){
        $id = $_GET['id'];

        $info = DB::table('check_goods')
            -> where([
                'id' => $id
            ]) -> first();

        $check_goods = DB::table('check_goods_detail')
            -> where([
                'flag' => 0,
                'check_goods_id' => $id
            ])
            ->orderBy('id','desc')
            -> get();
        return view('home.addCheckGoodsDetail') -> with([
            'check_goods' => $check_goods,
            'info' => $info
        ]);
    }

    //新增盘点清单 处理
    public function addCheckGoodsDetailRes(){
        DB::table('check_goods_detail') -> insertGetId([
            'scan_goods_number' => trim($_POST['scan_goods_number']),
            'check_goods_id' => $_POST['check_goods_id'],
            'created_at' => time(),
            'updated_at' => time(),
        ]);
    }

    public function deleteCheckGoods(){
        DB::table('check_goods_detail') -> where([
            'id' => $_POST['id']
        ]) -> update([
            'flag' => 1
        ]);
        return [
            'code' => 200
        ];
    }





    public function makeMarkPdf(){
        //通过s_number 查找处理方案
        $repertory_info = Repertory::find($_GET['s_number']);
        $temp_arr = [];
        if($repertory_info -> deal_method){
            $config = config('admin.deal_method');
            $deal_method = $config[$repertory_info -> deal_method];
            $temp_arr = explode('，',$deal_method);
        }
        return view('home.pdfPage.markPdfPage') -> with([
            'arr' => $temp_arr
        ]);
    }

    public function saveCangwei(){
        //保存仓位号
        Repertory::where('id',$_POST['id']) -> update([
            'cangwei' => $_POST['cangwei']
        ]);
        return [
            'code' => 'success',
            'msg' => '保存成功'
        ];
    }


    //抽奖 通过openid获取 vip
    public function getVipByOpenid(){
        $openid = $_GET['openid'];
        $user = DB::table('wxuser')
            -> where('openid',$openid) -> first();
        if($user){
            return [
                'market_class' => $user ->market_class
            ];
        }else{
            return [
                'market_class' => 0
            ];
        }
    }


    //停止打印机
    public function stopPrinter(){
        DB::table('print_sequeue')
            -> where('result',0)
            -> update([
                'result' => 1
            ]);
        echo '已停止';
    }


    //扫描打印pdf
    public function scanPrintPdf(){
        return view('home.scanPrintPdf');
    }


    public function scanPrintPdfRes(){
        $numbers = trim($_POST['numbers']);
        //查看此单号  有没有 -  有 - 说明是要拆的单号
        //if(strstr($numbers,'-')){
        if(false){
            $split_info = SplitPackage::where('sp_numbers',$numbers) -> first();
            if(!$split_info){
                return [
                    'code' => 'error',
                    'msg' => '没有此单号'
                ];
            }

            $package_info = Package::where([
                'split_id' => $split_info -> id,
                'flag' => 0
            ]) -> first();
            if(!$package_info){
                return [
                    'code' => 'error',
                    'msg' => '此单号没有对应包裹号'
                ];
            }


            if(!$package_info -> pdf){
                return [
                    'code' => 'error',
                    'msg' => '此单号对应的单号pdf还没有生成'
                ];
            }

            SplitPackage::where('id',$split_info -> id) -> update([
                'is_print' => 1,
                'updated_at' => time()
            ]);
            return [
                'code' => 'success',
                'msg' => '扫描成功',
                'url' => url('temp_pdf').'/'.$package_info -> pdf
            ];
        }else{


            $package_info = Package::where([
                'wuliu_num' => $numbers,
                'flag' => 0
            ])-> first();
            if(!$package_info){
                $package_info = Package::where([
                    'package_num' => $numbers,
                    'flag' => 0
                ])-> first();
                if(!$package_info){

                    //判断是不是小程序编号
                    $temp_info = DB::table('mp_temp_package_number')
                        -> where([
                            'package_num' => $numbers
                        ]) -> first();
                    if($temp_info){
                        $order_info = DB::table('order')
                            -> where([
                                'flag' => 0,
                                'mp_package_id' => $temp_info -> id
                            ])->first();
                        if($order_info){
                            $package_info = DB::table('packages')
                                -> where([
                                    'flag' => 0,
                                    'order_id' => $order_info -> id
                                ]) -> first();
                        }
                    }

                    if(!$package_info){
                        return [
                            'code' => 'error',
                            'msg' => '此单号没有对应包裹号'
                        ];
                    }




                }

            }


            if(!$package_info -> pdf){
                return [
                    'code' => 'error',
                    'msg' => '此单号对应的单号pdf还没有生成'
                ];
            }

            //添加pdf面单 到 008
            //PrintSequeue::addQueue(2,$numbers,,'00');

            return [
                'code' => 'success',
                'msg' => '扫描成功',
                'url' => url('temp_pdf').'/'.$package_info -> pdf
            ];
        }

    }

    //a4纸 包裹打印
    function mpLabelPage(){
        $mp_temp_number_ids = explode(',',$_GET['ids']);

        //取出这些单号
        $number_info = DB::table('mp_temp_package_number')
            -> whereIn('id',$mp_temp_number_ids) -> select([
                'package_num'
            ]) -> get();
        return view('home.mpPdfPage') -> with([
            'url' => env('PDF_URL'),
            'number_info' => $number_info,
            'type' => 1
        ]);
    }

    //物流小程序单张打印
    function mpLabelOnePage(){
        $mp_temp_number_ids = explode(',',$_GET['ids']);

        //取出这些单号
        $number_info = DB::table('mp_temp_package_number')
            -> whereIn('id',$mp_temp_number_ids) -> select([
                'package_num'
            ]) -> get();
        return view('home.mpPdfOnePage') -> with([
            'url' => env('PDF_URL'),
            'number_info' => $number_info,
            'type' => 2
        ]);
    }






















}
