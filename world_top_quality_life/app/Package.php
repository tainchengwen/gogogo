<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Package extends Model
{
    //
    //
    protected $table = 'packages';


    protected $dateFormat = 'U';

    public function order(){
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function splitPackages(){
        return $this -> hasOne(SplitPackage::class);
    }

    //关邮e通数据
    public function packageEms(){
        return $this -> hasOne(PackageEms::class,'package_id');
    }

    //user
    public function wxusers(){
        return $this -> belongsTo(WxUser::class,'userid');
    }

    //申报
    public function paratemer(){
        return $this -> belongsTo(PackageGoodsParatemer::class,'goods_id');
    }

    //区域
    public function areas(){
        return $this -> belongsTo(AreaName::class,'from_area','id');
    }



    //登录关邮e通
    static function eLogin(){
        \Illuminate\Support\Facades\Log::info('开始登录参数');
        $agent_arr = [
            'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.2995.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/57.0.2986.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/56.0.2924.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/53.0.2785.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/48.0.2564.0 Safari/537.36'
        ];

        $agent = $agent_arr[rand(0,count($agent_arr)-1)];
        $headers = [
            //'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
            'Accept-Encoding' => 'gzip, deflate',
            'Accept-Language' => 'zh-CN,zh;q=0.9,en;q=0.8',
            'Connection' => 'keep-alive',
            'Host' => 'ghzx.gdems.com',
            'Origin' => 'http://ghzx.gdems.com',
            'Referer' => 'http://ghzx.gdems.com/ghzx/jsp/query/query.jsp',
            'User-Agent' => $agent
        ];


        $login_url = 'http://ghzx.gdems.com/ghzx/common/index.jsp';

        $cookie_file = storage_path('logs/elogin_cookie.txt');    //cookie文件存放位置（自定义）

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_URL, $login_url);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);


        //curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC); //开启代理认证模式
        //curl_setopt($ch, CURLOPT_PROXY, "http://proxy.baibianip.com"); //本地服务器IP地址
        //curl_setopt($ch, CURLOPT_PROXYPORT, 8000); //本地服务器端口



        $logHtml = curl_exec($ch);
        if (curl_error($ch)) {
            \Illuminate\Support\Facades\Log::info(curl_error($ch));
        }
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        // 根据头大小去获取头信息内容
        $header = substr($logHtml, 0, $headerSize);

        curl_close($ch);

        \Illuminate\Support\Facades\Log::info('开始访问首页结束');


        //dd($logHtml);
        $pattern = "/user_l_key1\" value=\"([\s\S]*?)\"\/>/is";

        preg_match_all($pattern, $logHtml, $output1);

        $pattern = "/user_l_key2\" value=\"([\s\S]*?)\"\/>/is";

        preg_match_all($pattern, $logHtml, $output2);

        $pattern = "/user_l_key3\" value=\"([\s\S]*?)\"\/>/is";

        preg_match_all($pattern, $logHtml, $output3);

        if(isset($output1[1][0]) && isset($output2[1][0]) && $output3[1][0]){

            //self::eloginRes($output1[1][0],$output2[1][0],$output3[1][0]);
            //获取加密后的密码
            $pwd = file_get_contents('http://127.0.0.1:3000?firstKey='.$output1[1][0].'&secondKey='.$output2[1][0].'&thirdKey='.$output3[1][0]);

            \Illuminate\Support\Facades\Log::info('获取pwd结束1'.$pwd);
            sleep(5);
            //$pwd = file_get_contents(storage_path('logs/elogin.txt'));
            \Illuminate\Support\Facades\Log::info('获取pwd结束2');
            sleep(5);
            //获取验证码session
            $login_url = 'http://ghzx.gdems.com/ghzx//VerifyCodeServlet';
            //$cookie = file_get_contents(storage_path('logs/elogin_cookie_file.txt'));
            //$headers['Cookie'] = $cookie;
            //$cookie_file = storage_path('logs/elogin_cookie.txt');    //cookie文件存放位置（自定义）


            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_URL, $login_url);
            //curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
            //curl_setopt($ch, CURLOPT_COOKIE, $cookie_file);
            //curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC); //开启代理认证模式
            //curl_setopt($ch, CURLOPT_PROXY, "http://proxy.baibianip.com"); //本地服务器IP地址
            //curl_setopt($ch, CURLOPT_PROXYPORT, 8000); //本地服务器端口

            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
            $contents = curl_exec($ch);
            if (curl_error($ch)) {
                \Illuminate\Support\Facades\Log::info(curl_error($ch));
            }
            curl_close($ch);

            file_put_contents(storage_path('logs/elogin_qrcode.jpg'),$contents);

            sleep(10);
            //识别验证码
            \Illuminate\Support\Facades\Log::info('识别验证码开始');

            $ch = curl_init('http://op.juhe.cn/vercode/index');
            $cfile = curl_file_create(storage_path('logs/elogin_qrcode.jpg'), 'image/png', 'pic.png');
            $data = array(
                'key' => '27bdcaf9ad810fbbe5aa0e41c0552b88', //请替换成您自己的key
                'codeType' => '1005', // 验证码类型代码，请在https://www.juhe.cn/docs/api/id/60/aid/352查询
                'image' => $cfile,
            );
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $response = curl_exec($ch);
            curl_close($ch);

            \Illuminate\Support\Facades\Log::info($response);

            $arr = json_decode($response,true);
            sleep(5);


            \Illuminate\Support\Facades\Log::info('识别验证码结束');

            $post_data = [
                'user_l_loginName' => '17302102232',
                'user_l_pwpwd' => '*************',
                'user_l_key1' => $output1[1][0],
                'user_l_key2' => $output2[1][0],
                'user_l_key3' => $output3[1][0],
                'user_l_despwpwd' => $pwd,
                'user_l_veryCode' => $arr['result']
            ];



            $headers['Accept'] = '*/*';
            $headers['Content-Type'] = 'application/x-www-form-urlencoded; charset=UTF-8';
            $headers['Referer'] = 'http://ghzx.gdems.com/ghzx/common/index.jsp';
            $headers['X-Requested-With'] = 'XMLHttpRequest';
            $headers['Content-Length'] = strlen(http_build_query($post_data));

            //\Illuminate\Support\Facades\Log::info(json_encode($headers));


            $post_url = 'http://ghzx.gdems.com/ghzx/user/user_login.action';
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_URL, $post_url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
            curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($post_data));
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
            //curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
            //curl_setopt($ch, CURLOPT_COOKIE, $cookie_file);

            //curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC); //开启代理认证模式
            //curl_setopt($ch, CURLOPT_PROXY, "http://proxy.baibianip.com"); //本地服务器IP地址
            //curl_setopt($ch, CURLOPT_PROXYPORT, 8000); //本地服务器端口


            $contents = curl_exec($ch);
            if (curl_error($ch)) {
                \Illuminate\Support\Facades\Log::info(curl_error($ch));
            }
            curl_close($ch);
            \Illuminate\Support\Facades\Log::info('登录结果'.$contents);
            if(strstr($contents,'ok')){

                /*
                //登录成功  把 cookie文件 给服务器发过去
                //登录成功  把 cookie文件 给服务器发过去
                $ch = curl_init('http://hqyp.fenith.com/saveCookieFile');
                $cfile = curl_file_create(storage_path('logs/elogin_cookie.txt'),'text/plain','elogin_cookie.txt');
                $data = array(
                    'cookie_file' => $cfile,
                );

                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                //curl_setopt($ch, CURLOPT_TIMEOUT, 0);
                $response = curl_exec($ch);
                curl_close($ch);
                if($response == 'success'){

                }


                //dd($response);

                exit;
                */


                return true;
            }else{
                return false;
            }



            //dd($pwd);
        }

    }

    //更新关邮e通数据
    static function insertInfoEms($package_info){
        $table = $package_info -> mail_status_table;

        $html = str_replace("\n","",trim($table));


        $pattern = "/<td style=\"text-align: center;\">([\s\S]*?)<\/td>/is";

        preg_match_all($pattern, $html, $output1);

        if(isset($output1[1])){
            $temp = [];
            foreach($output1[1] as $vo){
                $temp[] = $vo;
            }
        }
        //三个一联
        $temp_info = array_chunk($temp,3);
        //dd($temp_info);

        $id = $package_info -> id;

        $ems_info = DB::table('ems_package') -> where([
            'package_id' => $id
        ]) -> first();

        //送货时间 = 生成交货单时间
        //通过包裹 找 托盘
        $relations = DB::table('batch_packages_relation')
            -> where([
                'package_id' => $id
            ]) -> first();

        $send_order_time = '';
        $into_time = '';
        if($relations){
            if($relations -> repertory_id){
                //查找此物流单的 到货时间
                $log = DB::table('repertory_log')
                    -> where([
                        'type' => 2,
                        'repertory_id' => $relations -> repertory_id
                    ]) -> first();
                if($log){
                    $into_time = $log -> created_at;
                }
            }


            //查看次批次 是否生成交货单号
            $batch_packages = DB::table('batch_packages')
                -> where([
                    'id' => $relations -> batch_id
                ]) -> first();
            if($batch_packages && $batch_packages -> send_order_id){
                //通过批次 找 交货单
                $send_order_list = DB::table('send_order_list')
                    -> where([
                        'id' => $batch_packages -> send_order_id
                    ]) -> first();
                if($send_order_list){
                    //生成交货单的时间
                    $send_order_time = $send_order_list -> created_at;
                }
            }
        }




        if(!$ems_info){

            $ems_id = DB::table('ems_package')
                -> insertGetId([
                    'package_id' => $id,
                    'created_at' => time(),
                    'updated_at' => time(),
                    'ems_json' => json_encode($temp_info),
                    'send_time' => $send_order_time,
                    'into_time' => $into_time
                ]);
        }else{
            $ems_id = $ems_info -> id;
            DB::table('ems_package')
                -> where([
                    'id' => $ems_id
                ]) -> update([
                    'ems_json' => json_encode($temp_info),
                    'updated_at' => time(),
                    'send_time' => $send_order_time,
                    'into_time' => $into_time
                ]);
        }


        //dump($temp_info);
        //更新状态
        $ems_status = 0;
        //税金
        $tax = 0;

        $is_end_mail = 0;
        foreach($temp_info as $vo){
            $time = strtotime($vo[0]);
            if(strstr($vo[1],'邮件已离开广州互换局，准备发往收件地')){
                //放行时间  	release_time
                DB::table('ems_package') -> where([
                    'id' => $ems_id
                ]) -> update([
                    'release_time' => $time
                ]);

            }









            if(strstr($vo[1],'海关已审核邮件电子申报信息，邮政企业正在理货，邮件实物尚未送交海关')){
                //查验时间
                DB::table('ems_package') -> where([
                    'id' => $ems_id
                ]) -> update([
                    'inspection_time' => $time
                ]);
            }

            if(strstr($vo[1],'税款')){
                //把税金抓下来
                $index = mb_strpos($vo[1],'税款');

                $tax = mb_substr($vo[1],intval($index)+3);

                DB::table('ems_package') -> where([
                    'id' => $ems_id
                ]) -> update([
                    'taxes' => $tax
                ]);
            }


        }


        if(!count($temp_info)){
            exit;
        }

        //判断最后一条数据的状态
        $temp_info_last = $temp_info[intval(count($temp_info)-1)][1];
        if(strstr($temp_info_last,'邮件已离开广州互换局，准备发往收件地')){
            //放行
            $ems_status = 2;
            $is_end_mail = 1;

        }
        if(strstr($temp_info_last,'邮件未到达广州互换局')){
            //未到达海关
            $ems_status = 1;
        }
        if(strstr($temp_info_last,'海关已审核邮件电子申报信息，邮政企业正在理货，邮件实物尚未送交海关')){
            //查验
            $ems_status = 4;
        }
        if(strstr($temp_info_last,'办理退运') || strstr($temp_info_last,'补充申报') || strstr($temp_info_last,'海关已办结邮件退运手续')){
            //退运
            $ems_status = 3;
        }

        if(strstr($temp_info_last,'税款')){
            //待缴税
            $ems_status = 5;
        }

        if(strstr($temp_info_last,'您的退运申请已通过EMS初审，正在排队交海关审核，请耐心等待')){
            //退运申请已通过
            $ems_status = 6;
        }
        if(strstr($temp_info_last,'邮件需要按货物报关或办理退运，请根据邮政短信或纸质通知单指引办理手续, 如有疑问请联系邮政部门')){
            //需办理退运
            $ems_status = 8;
        }
        if(strstr($temp_info_last,'您的退运申请已提交，等待EMS初审')){
            //退运申请已提交
            $ems_status = 9;
        }
        if(strstr($temp_info_last,'邮件已到达广州互换局')){
            //等待向海关申报
            $ems_status = 10;
        }
        //识别到税款 并且 税金为0 为0税金放行状态
        if(strstr($temp_info_last,'税款') && $tax == '0'){
            //0 税款放行
            $ems_status = 11;
            $is_end_mail = 1;
        }


        if($is_end_mail){
            DB::table('packages') -> where([
                'id' => $package_info -> id
            ]) -> update([
                'is_end_mail' => 1
            ]);
        }





        if($ems_status){
            if($ems_status == 5){
                //修改为待缴税的时候 看看是否已经缴税了
                DB::table('ems_package') -> where([
                    'id' => $ems_id,
                    'is_update_pay_mail' => 0 //只有没有更新过的 可以改
                ]) -> update([
                    'ems_status' => $ems_status
                ]);
            }else{
                DB::table('ems_package') -> where([
                    'id' => $ems_id
                ]) -> update([
                    'ems_status' => $ems_status
                ]);
            }

        }

    }

    //获取关邮e通缴费数据
    static function getPayLog(){
        //JSESSIONID=368D8AC826ABB40BB89D6584E70B9A0A

    }




}
