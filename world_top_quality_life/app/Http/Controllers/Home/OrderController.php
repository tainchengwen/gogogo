<?php

namespace App\Http\Controllers\Home;

use App\Order;
use App\PayImage;
use EasyWeChat\Factory;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    //
    public function index(){
        /*
        session([
            'home_user_id' => 1
        ]);
        */

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
                    'target_url' => url('order')
                ]);
                return $oauth->redirect(url('redirect'));
            }
        }else{
            $userid = 41;
        }


        $query = DB::table('order') -> where([
            ['userid' ,'=', $userid],
            ['flag','=','0'],
        ]);

        /**
         * status=0 待付款
         * status=1 待填地址
         * status=2 待收货
         * status=3 已完成
         */

        if(isset($_GET['status'])){
            /*
            $status = [$_GET['status']];
            if($_GET['status'] == 2){
                $status = [2,3,5];
            }
            if($_GET['status'] == 3){
                $status = [4];
            }
            */
            switch ($_GET['status']){
                case 0:$query -> where('pay_status','=',0);break;
                case 1:$query -> where('status','=','0');break;
                case 2:$query -> whereIn('status',[1,2,3]);break;
                case 3:$query -> where('status','=',4);break;
            }
        }else{
            //全部订单
            //$status = [0,1,2,3,4,5];
            //订单状态不为 用户取消状态的
            $query -> where('status','!=','9');
        }



        $order = $query
            //-> whereIn('status',$status)
            -> orderBy('created_at','desc')
            -> get();

        //$order_status = config('admin.order_status');
        $order_status = config('admin.order_status_new');
        $pay_status = config('admin.pay_status_new');

        foreach($order as $k => $vo){
            $order[$k] -> status_name = $order_status[$vo -> status]; //订单状态翻译
            $order[$k] -> pay_status_name = $pay_status[$vo -> pay_status]; //支付状态翻译



        }



        $newarr = [];
        foreach($order as $vo){
            $newarr[$vo->date_time][] = $vo;
        }

        //查看is_look是0的数量
        $count_look = DB::table('order') -> where([
            'userid' => $userid,
            'is_look' => 0
        ]) -> count();

            /*
        $date_time = DB::table('order') ->select('date_time')-> where([
            'userid' => $userid
        ]) -> groupBy('date_time')-> get();
            */



        return view('home.order')->with([
            'order' => $newarr,
            'count_look' => $count_look
        ]);
    }


    //填写地址
    public function writeAdd($id){
        $userid = session('home_user_id');
        $config = env('APP_ENV');
        if($config == 'local'){
            $userid = '41';
            $order_info = DB::table('order') -> where([
                'id' => $id,
            ]) -> first();

        }else{
            if(!$userid){
                //回去回调用
                return redirect()->action('Home\OrderController@ifNotUserid',['url'=>url('/writeAdd/'.$id)]);
            }

            $order_info = $this ->  shouldRedirect($id,0);
            if($order_info['redirect_url']){
                return redirect($order_info['redirect_url']);
            }
            $order_info = $order_info['order_info'];
        }


        $address_infos = [];
        $json_encode_data = '';
        //如果是通过选择地址页面来的
        if(isset($_GET['address_this']) && isset($_GET['address_index']) &&   $_GET['address_this'] && $_GET['address_index']){
            //拼接address_ids 数组

            //如果之前保存有地址
            if(isset($_GET['address_ids']) && $_GET['address_ids']){
                /*
                //分解地址ids
                $address_ids = explode(',',$_GET['address_ids']);
                $address_infos = DB::table('user_address')
                    -> whereIn('id',$address_ids)
                    -> where('user_id',$userid)
                    -> get();
                */
                $json_decode_data = json_decode($_GET['address_ids'],true);

            }else{
                $json_decode_data = [];
            }
            $json_decode_data[$_GET['address_index']] = $_GET['address_this'];
            $json_encode_data = json_encode($json_decode_data);
            foreach($json_decode_data as $key => $vo){
                $address_infos[$key] = DB::table('user_address') -> where([
                    'id' => $vo,
                    'user_id' => $userid
                ]) -> first();
            }

        }
        /*
        $config = [
            'app_id' => env('WECHAT_OFFICIAL_ACCOUNT_APPID'),
            'secret' => env('WECHAT_OFFICIAL_ACCOUNT_SECRET'),
        ];

        $app = Factory::officialAccount($config);
        */

        //$app->jssdk->buildConfig(['checkJsApi','openAddress','editAddress'], $debug = true, $beta = false, $json = true);

        //dump($json_encode_data);

        return view('home.order_address')->with([
            'order_info' => $order_info,
            'address_infos' => $address_infos,

            //要添加地址待的参数
            'address_ids' => $json_encode_data,
            //

            //'app' => $app
        ]);
    }

    //取消订单（取消订单 确认收货 同一个接口）
    public function cancelOrder(Request $request){
        if($request -> input('type') == 2){
            //确认收货
            DB::table('order') -> where([
                'id' => $request -> input('id')
            ]) -> update([
                'status' => 4
            ]);

        }else{
            //取消订单

            $order_info = DB::table('order') -> where([
                'id' => $request -> input('id')
            ]) -> first();

            $area_name = DB::table('area_name') -> where([
                'id' => $order_info -> from_area
            ]) -> first();

            $packages = DB::table('packages') -> where([
                'order_id' => $request -> input('id')
            ]) -> orderBy('sequeue_num','desc') -> first();

            if($packages && $packages -> sequeue_num){
                /*
                //查询pcakages的sequeue 是不是最大的
                $is_big = DB::table('sequeue') -> where([
                    'date' => $order_info -> date_time,
                    'order_num' => $packages -> sequeue_num,
                    'from_area' => $area_name -> area_num,
                    'type' => 1
                ]) -> first();
                if($is_big){
                    //如果是最大的 则退回去
                    //查下有多少包裹
                    $count_packages = DB::table('packages') -> where([
                        'order_id' => $request -> input('id')
                    ]) -> count();
                    //减去
                    DB::table('sequeue') -> where([
                        'date' => $order_info -> date_time,
                        'from_area' => $area_name -> area_num,
                        'type' => 1
                    ]) -> decrement('order_num',$count_packages);
                }
                */
            }





            //标记删除订单
            DB::table('order') -> where([
                'id' => $request -> input('id')
            ]) -> update([
                'flag' => 1
            ]);
            DB::table('packages') -> where([
                'order_id' => $request -> input('id')
            ]) -> update([
                'flag' => 1
            ]);





        }

        echo 'success';
    }

    //订单支付页面
    public function payOrder($id){
        //需要看下这个订单是不是他的
        $userid = session('home_user_id');
        $config = env('APP_ENV');
        if($config != 'local'){
            if(!$userid){
                //回去回调用
                return redirect()->action('Home\OrderController@ifNotUserid',['url'=>url('/payOrder/'.$id)]);
                //$this -> ifNotUserid(url('/payOrder/'.$id));
            }

            $order_info = $this ->  shouldRedirect($id,1);
            if($order_info['redirect_url']){
                return redirect($order_info['redirect_url']);
            }
            $order_info = $order_info['order_info'];
        }else{
            $order_info = DB::table('order') -> where('id',$id) -> first();
        }


        //将他的is_look更新为1
        DB::table('order') -> where([
            'id' => $id,
        ]) -> update([
            'is_look' => 1
        ]);

        return view('home.orderPay') -> with([
            'order_info' => $order_info
        ]);

    }


    //订单支付处理
    public function payEnd($id){
        //查下此订单是不是他的
        //需要看下这个订单是不是他的
        $userid = session('home_user_id');
        if(!$userid){
            //回去回调用
            return redirect()->action('Home\OrderController@ifNotUserid',['url'=>url('/payOrder/'.$id)]);
            //$this -> ifNotUserid(url('/payOrder/'.$id));
        }

        $order_info = $this ->  shouldRedirect($id,1);
        if($order_info['redirect_url']){
            return redirect($order_info['redirect_url']);
        }
        $order_info = $order_info['order_info'];

        //检查他余额够不够
        $userinfo = DB::table('wxuser') -> where([
            'id' => $userid,
        ]) -> first();
        if($userinfo -> price < $order_info -> pay_price){
            //余额不足
            return redirect('payOrder/'.$id) -> with('message','余额不足，请充值');
        }


        //添加日志
        DB::table('price_log') -> insert([
            'userid' => $userid,
            'price' => floatval($order_info -> pay_price),
            'type' => 1,
            'created_at' => time(),
            'updated_at' => time(),
            'in_out' => 1, // 0收入1支出
            'end_price' => floatval($userinfo -> price) - floatval($order_info -> pay_price)
        ]);



        //余额够 扣除 并且账单状态修改
        DB::table('wxuser') -> where([
            'id' => $userid
        ]) -> update([
            'price' => floatval($userinfo -> price) - floatval($order_info -> pay_price),
            'updated_at' => time()
         ]);

        //支付成功后 判断下发货状态 如果发货状态为已填地址 则把发货状态改为待发货
        if($order_info -> status == 5){
            $order_info_status = 1;
        }else{
            $order_info_status = $order_info -> status;
        }

        DB::table('order') -> where([
            'id' => $id
        ]) -> update([
            'pay_status' => 1, //支付成功 支付状态改为已支付
            'status' => $order_info_status,
            'pay_time' => time(),
            'updated_at' => time()
        ]);



        return redirect('paySuccess/'.$id) -> with('message','支付成功');


    }
    //线下支付
    public function payEndType($id,$type){
        $orderinfo = DB::table('order') -> where([
            'id' => $id
        ]) -> first();

        //通过order的区域 查找支付图片
        $imgs = PayImage::where([
            'area_id' => $orderinfo -> from_area,
            'type' => $type
        ]) -> first();
        //有区域支付图片的 优先
        if(!$imgs){
            $imgs = DB::table('payimage') -> where([
                'type' => $type,
                'area_id' => 0
            ]) -> first();
        }

        if($type == 1){
            $typename = '微信';
        }else{
            $typename = '支付宝';
        }

        return view('home.orderPayType')->with([
            'type' => $type,
            'typename' => $typename,
            'orderinfo' => $orderinfo,
            'image' => asset('uploads/'.$imgs -> image)
        ]);
    }

    public function paySuccess($id){
        //需要看下这个订单是不是他的
        $userid = session('home_user_id');
        if(!$userid){
            //回去回调用
            return redirect()->action('Home\OrderController@ifNotUserid',['url'=>url('/payOrder/'.$id)]);
            //$this -> ifNotUserid(url('/payOrder/'.$id));
        }

        $order_info = DB::table('order') -> where([
            'id' => $id,
            'userid' => $userid,
        ]) -> first();
        if(empty($order_info)){
            return redirect('home');
        }

        //付款结束
        return view('home.pay_success') -> with([
            'order_info' => $order_info
        ]);
    }


    //订单填写地址提交
    public function subOrderAddress(Request $request){
        if(empty($request -> input('address_num')) || empty($request -> input('names')) || empty($request -> input('tels')) || empty($request -> input('address')) || empty($request -> input('order_id')  )){

            return [
                'code' => 'error',
                'msg' => '数据错误，输入缺少',
            ];

        }
        //包裹数量
        $count_package = intval($request -> input('countPackage'));
        //地址数量
        //$count_address = intval($request -> input('address_num'));
        $names = $request -> input('names');
        //名字已存在的次数 假如存在1次  则还可以使用两次
        $count_names = [];
        $tels = $request -> input('tels');
        $count_tels = [];
        $address = $request -> input('address');
        $count_address = [];



        $codes = $request -> input('codes');
        $provinces = $request -> input('provinces');
        $citys = $request -> input('citys');



        $userid = $request -> input('userid');
        $order_id = $request -> input('order_id');
        //判断下地址是否重复


        $order_info = DB::table('order') -> where([
            'id' => $order_id
        ]) -> first();



        //判断下姓名 电话 地址  是否存在超过3次
        $names_count_arr = array_count_values($names);
        foreach($names_count_arr as $k => $vo){
            if($vo >3){
                return [
                    'code' => 'error',
                    'msg' => '所填写姓名：'.trim($k).' 重复',
                ];
            }



            $isset = DB::table('packages') -> where([
                'userid' => $userid,
                'wuliu_num' => '',
                'name' => trim($k),
                'flag' => 0
            ])  -> where('order_id','<>',$order_id) -> count();
            if($vo + $isset > 3){
                return [
                    'code' => 'error',
                    'msg' => '与之前订单姓名：'.trim($k).' 重复',
                ];
                //echo '与之前订单姓名：'.trim($k).' 重复';exit;
            }



        }

        $tels_count_arr = array_count_values($tels);
        foreach($tels_count_arr as $k => $vo){
            if($vo >3){
                return [
                    'code' => 'error',
                    'msg' => '所填写电话：'.trim($k).' 重复',
                ];
                //echo '所填写电话：'.trim($k).' 重复';exit;
            }

            $isset = DB::table('packages') -> where([
                'userid' => $userid,
                'wuliu_num' => '',
                'tel' => trim($k),
                'flag' => 0
            ])  -> where('order_id','<>',$order_id) -> count();
            if($vo + $isset > 3){
                return [
                    'code' => 'error',
                    'msg' => '与之前订单电话：'.trim($k).' 重复',
                ];
                //echo '与之前订单电话：'.trim($k).' 重复';exit;
            }

        }

        $address_count_arr = array_count_values($address);
        foreach($address_count_arr as $k => $vo){
            if($vo >3){
                return [
                    'code' => 'error',
                    'msg' => '所填写地址：'.trim($k).' 重复',
                ];
                //echo '所填写地址：'.trim($k).' 重复';exit;
            }

            $isset = DB::table('packages') -> where([
                'userid' => $userid,
                'wuliu_num' => '',
                'address' => trim($k),
                'flag' => 0
            ])  -> where('order_id','<>',$order_id) -> count();
            if($vo + $isset > 3){
                return [
                    'code' => 'error',
                    'msg' => '与之前订单地址：'.trim($k).' 重复',
                ];
                //echo '与之前订单地址：'.trim($k).' 重复';exit;
            }
        }






        /*
        //先拼接这些地址
        $address_temp = [];
        for($i = 0 ;$i<$count_address;$i++){
            $address_temp[] = trim($names[$i]).','.trim($tels[$i]).','.trim($address[$i]);
        }
        if(count($address_temp) != count(array_unique($address_temp))){
            //有重复的值
            echo 'repeat_address';exit;
        }
        )
        */

        /*
        //跟之前的记录相比 有没有重复的

        //先比较名字  --》 前台填写地址 跟后台 个人下单公用
        foreach($names as $vo){
            $isset = DB::table('packages') -> where([
                'userid' => $userid,
                'wuliu_num' => '',
                'name' => trim($vo),
                'flag' => 0
            ])  -> count();
            //查下每个名字 以前用过多少次
            //如果此名字存在过  判断下存在与几个包裹。如果存在规定的3个包裹 则不允许
            if($isset >= 3){
                echo '姓名：'.trim($vo).' 重复';exit;
            }else{
                //已经使用的次数保存
                $count_names[] = $isset;
            }
        }

        //电话
        foreach($tels as $vo){
            $isset = DB::table('packages') -> where([
                'userid' => $userid,
                'wuliu_num' => '',
                'tel' => trim($vo),
                'flag' => 0
            ])  -> count();
            if($isset >= 3){
                echo '电话：'.trim($vo).' 重复';exit;
            }else{
                //已经使用的次数保存
                $count_tels[] = $isset;
            }
        }

        //地址
        foreach($address as $vo){
            $isset = DB::table('packages') -> where([
                'userid' => $userid,
                'wuliu_num' => '',
                'address' => trim($vo),
                'flag' => 0
            ])  -> count();
            if($isset >= 3){
                echo '地址：'.trim($vo).' 重复';exit;
            }else{
                //已经使用的次数保存
                $count_address[] = $isset;
            }
        }

        //新增验证

        //把所有地址 可以用的次数累加起来  跟  总需要的次数 比较 如果小 则报错。

        $count_temp = 0; //此变量 用来保存 目前可以用作多少个包裹
        foreach($count_names as $k => $vo){
            //取$count_name count_tels count_address 中的最大值 用的最多的次数
            $temp = max($count_names[$k],$count_tels[$k],$count_address[$k]);

            $count_temp += 3 - intval($temp);

        }




        if($count_temp < $count_package){
            //不行 说明地址不够用。
            foreach($count_names as $key => $vo){
                //有存在超过1的就报错
                if($vo >= 1){
                    echo '名字：'.trim($names[$key]).' 重复';exit;
                }
            }
            foreach($count_tels as $key => $vo){
                //有存在超过1的就报错
                if($vo >= 1){
                    echo '电话：'.trim($tels[$key]).' 重复';exit;
                }
            }
            foreach($count_address as $key => $vo){
                //有存在超过1的就报错
                if($vo >= 1){
                    echo '地址：'.trim($address[$key]).' 重复';exit;
                }
            }
        }

        */

        //验证完毕=======>





        //分配地址给订单下的包裹
        //将订单下的包裹全部取出来
        $packages = DB::table('packages') -> where([
            'order_id' => $order_id,
            'flag' => 0
        ]) -> get();


        //判断下填写的数量 跟包裹的数量是否匹配
        if(count($packages) != count($names) || count($packages) != count($tels) || count($packages) != count($address) ){
            return [
                'code' => 'error',
                'msg' => '数据错误,填写数量跟包裹数量不匹配',
            ];
        }


        $package_ids = [];
        foreach($packages as $vo){
            $package_ids[] = $vo -> id;
        }


        //给每对地址 分配uuid
        $temp_arr_uuid = [];
        foreach($names as $k => $vo){
            $temp_arr_uuid[$vo.$tels[$k].$address[$k]] = $this->create_uuid();
        }

        //为每个包裹添加地址
        foreach($packages as $key => $value){
            $uuid = $temp_arr_uuid[$names[$key].$tels[$key].$address[$key]];
            DB::table('packages') -> where([
                'id' => $value -> id
            ]) -> update([
                'uuid' => $uuid,
                'address' => $provinces[$key].$citys[$key].$address[$key],
                'zip' => $codes[$key],
                'name' => $names[$key],
                'province' => $provinces[$key],
                'city' => $citys[$key],
                'tel' => $tels[$key],
            ]);
        }



        //!!!!!!!店员 走自己的一套逻辑
        //如果填写地址成功了， 则给店员 发送请求打印信息
        if($order_info -> printer_userid){
            //先吧此订单 变成虚拟支付
            DB::table('order') -> where([
                'id' => $order_info -> id
            ]) -> update([
                'updated_at' => time(),
                'pay_status' => 1,
                'pay_type' => 9
            ]);

            //等他的全部wuliu_num 生成了 才可以去打印

            //通过 printer_id 拿到路线
            $printer_info = DB::table('printer_manage') -> where([
                'user_id' => $order_info -> printer_userid
            ]) -> first();

            $packages = DB::table('packages') -> where([
                'order_id' => $order_info -> id,
                'flag' => 0
            ]) ->join('goods_paratemer', 'packages.goods_id', '=', 'goods_paratemer.id')
                ->select('packages.*', 'goods_paratemer.*','packages.id as id')
                -> get();

            $apiRes = [];

            foreach($packages as $vol) {
                if ($vol->wuliu_num) {
                    continue;
                }

                $model_order = new Order();
                $apiRes[] = $model_order->sendPackage($vol, $order_info, $printer_info->route_id);
            }

            //遍历 apires 有一个错误 就返回

            foreach($apiRes as $k => $vo){
                   if($vo['result'] == 'error'){
                       return [
                           'code' => 'api_error',
                           'msg' => $apiRes,
                       ];
                       //echo json_encode($apiRes);exit;
                   }
            }

            //下单成功！ 加入店员请求队列
            //订单状态先改为 待发货
            DB::table('order') -> where([
                'id' => $order_id
            ]) -> update([
                'status' => 1,
            ]);

            //加入打印店员请求队列
            DB::table('print_request') -> insert([
                'order_id' => $order_info -> id,
                'created_at' => time(),
                'updated_at' => time(),
                'printer_id' => $order_info -> printer_userid
            ]);
            //给店员发送模板消息
            $env = env('APP_ENV');
            if($env != 'local') {
                //打印机店员的信息
                $print_user = DB::table('wxuser') -> where([
                    'id' => $order_info -> printer_userid
                ]) -> first();



                //算下她有多少打印任务
                $print_info = DB::table('print_request') -> where([
                    'printer_id' => $order_info -> printer_userid,
                    'flag' => 0
                ]) -> count();

                $config = [
                    'app_id' => env('WECHAT_OFFICIAL_ACCOUNT_APPID'),
                    'secret' => env('WECHAT_OFFICIAL_ACCOUNT_SECRET'),
                ];

                $app = Factory::officialAccount($config);
                $res = $app->template_message->send([
                    'touser' => $print_user->openid,
                    'template_id' => 'BqvuFiP3oPwszHf7daTFYycLg878ekhZaRUus7qLLEw',
                    'url' => url('etk/managePrintPage'),
                    'data' => [
                        'first' => '您有新的订单需要打印',
                        'keyword1' => $order_info -> order_num,
                        'keyword2' => '打印任务',
                        'keyword3' => $print_info,
                        'remark' => '请尽快确认是否允许打印'
                    ]
                ]);
            }

        }





        //填写完地址后 发货状态改为已填地址，如果已支付的话 改为待发货

        if($order_info -> pay_status == 1){
            //待发货
            $order_info_status = 1;
        }else{
            //已填地址
            $order_info_status = 5;
        }

        DB::table('order') -> where([
            'id' => $order_id
        ]) -> update([
            'status' => $order_info_status,
        ]);

        /*
        $package_ids = [];
        foreach($packages as $vo){
            $package_ids[] = $vo -> id;
        }

        //用名字 地址 电话 取找package_id
        $index_package_id = 0;
        foreach($count_names as $k => $vo){
            //取$count_name count_tels count_address 中的最大值 用的最多的次数
            $temp = max($count_names[$k],$count_tels[$k],$count_address[$k]);
            $uuid = $this -> create_uuid();
            for($i = $temp;$i <  3; $i++){
                if(isset($package_ids[$index_package_id])){
                    //假如可以循环两次 取两个包裹 保存
                    DB::table('packages') -> where([
                        'id' => $package_ids[$index_package_id]
                    ]) -> update([
                        'uuid' => $uuid,
                        'address' => $address[$k],
                        'zip' => $codes[$k],
                        'name' => $names[$k],
                        'province' => $provinces[$k],
                        'city' => $citys[$k],
                        'tel' => $tels[$k],
                    ]);
                    $index_package_id ++;
                }
            }
        }

        */

        return [
            'code' => 'success',
            'msg' => '',
        ];

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


    //订单详情
    public function orderInfo($id){
        //需要看下这个订单是不是他的
        $userid = session('home_user_id');
        $config = env('APP_ENV');
        if($config == 'local'){
            $userid = 41;
            $order_info  = DB::table('order') -> where([
                'id' => $id
            ]) -> first();
        }else{
            if(!$userid){
                //回去回调用
                return redirect()->action('Home\OrderController@ifNotUserid',['url'=>url('/orderInfo/'.$id)]);
                //$this -> ifNotUserid(url('/orderInfo/'.$id));
            }

            $order_info = $this ->  shouldRedirect($id,2);
            if(isset($order_info['redirect_url']) && $order_info['redirect_url']){
                return redirect($order_info['redirect_url']);
            }
            $order_info = $order_info['order_info'];
        }



        //通过配置 读取status 翻译
        $configs = config('admin.order_status_new');
        $order_info ->  status_name = $configs[$order_info -> status];

        //包裹详情也弄出来
        $packages = DB::table('packages') -> where([
            'order_id' => $id,
            'flag' => 0
        ]) -> get();

        $model = new Order();
        foreach($packages as $k => $vo){
            $packages[$k] -> trackingStatus = $model -> transTrackingStatus($vo -> trackingStatus);
        }


        //按照uuid 分组
        $uuid_res = [];
        foreach($packages as $vo){
            $uuid_res[$vo -> uuid][] = $vo;
        }

        //dd($uuid_res);

        return view('home.orderInfo') -> with([
            'order_info' => $order_info,
            'packages' => $packages,
            'uuid_res' => $uuid_res
        ]);
    }


    //判断订单应该去哪个地址
    /*
     * $id 订单id
     * $status 这个订单应该是哪个状态
     */
    public function shouldRedirect($id,$status){
        $userid = session('home_user_id');
        $order_info = DB::table('order') -> where([
            'id' => $id,
            'userid' => $userid,
        ]) -> first();
        //如果不是他自己的订单 就返回
        if(empty($order_info)){
            return [
                'redirect_url' => 'home',
            ];
        }

        //判断  $status
         switch ($status){
             case 0 :
                 if($order_info -> status == 0){
                     return [
                         'redirect_url' => '',
                         'order_info' => $order_info,
                     ];
                 }
                 break; //填写地址页面
             case 1 :
                 if($order_info -> pay_status == 0){
                     return [
                         'redirect_url' => '',
                         'order_info' => $order_info,
                     ];
                 }
                 break; //订单支付页面 订单请求
             case 2 :
                 return [
                     'redirect_url' => '',
                     'order_info' => $order_info,
                 ];
                 break; //订单详情页面

         }




        //如果此订单的状态 就是他目前的状态 则返回true
        /*
        if(in_array($order_info -> status,$status)){
            return [
                'redirect_url' => '',
                'order_info' => $order_info,
            ];
        }
        */




        //如果此订单的状态 跟 目前的状态不一致 则看他该去哪里
        if($order_info -> status == 0 ){
            return [
                'redirect_url' => 'writeAdd/'.$id,
            ];
        }

        if($order_info -> pay_status == 0 ){
            return [
                'redirect_url' => 'payOrder/'.$id,
            ];
        }

        return [
            'redirect_url' => 'orderInfo/'.$id,
        ];

    }


    //如果没有userid 的时候
    //应该跳转的url
    public function ifNotUserid(){
        $url = $_GET['url'];
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
            'target_url' => $url
        ]);
        return $oauth->redirect(url('redirect'));
    }

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

        return view('home.checkAddress') -> with([
            'address' => $address
        ]);
    }

    public function addAddress(){
        if(isset($_GET['address_index'])){
            $return_url = url('checkAddress').'?address_index='.$_GET['address_index'].'&address_ids='.$_GET['address_ids'].'&order_id='.$_GET['order_id'];
        }else{
            $return_url = url('checkAddress');
        }


        //dd($return_url);
        return view('home.addAddress') -> with([
            'return_url' => $return_url
        ]);
    }


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
        echo 'success';
    }

    //编辑地址
    public function editAddress(){
        if(isset($_GET['address_index'])){
            $return_url = url('checkAddress').'?address_index='.$_GET['address_index'].'&address_ids='.$_GET['address_ids'].'&order_id='.$_GET['order_id'];
        }else{
            $return_url = url('checkAddress');
        }

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
        //echo $return_url;

        return view('home.editAddress') -> with([
            'address' => $address,
            //拼写地址
            'address_value' => $address_value,
            'return_url' => $return_url
        ]);

    }








}
