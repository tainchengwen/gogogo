<?php

namespace App\Jobs;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Package;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class GetMailPayLog implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        Log::info('抓取关邮e通 缴纳税金 开始eeeeeeee');
        //先登录
        $res = Package::eLogin();
        if(!$res){
            Log::info('登录失败-重试第1次');
            $res2 = Package::eLogin();
            if(!$res2){
                Log::info('登录失败-重试第2次');
                $res3 = Package::eLogin();
                if(!$res3){
                    exit;
                }

            }
        }

        //JSESSIONID=0DA7D028484C9E14590A51C44DAC4469
        //Log::info('mail_getStatus_start');
        $data_url = "http://ghzx.gdems.com/ghzx/email/wsjf_sdcxBatch.action";     //数据所在地址
        //$cookie = 'JSESSIONID=A56E1EF8C8C460C55ADFA136A7E5D70D; ehide=1';
        //$cookie = 'JSESSIONID=D5E226E22F192A0AB9FBBE4601B06607; ehide=1';
        $cookie_file = storage_path('logs/elogin_cookie.txt');    //cookie文件存放位置（自定义）
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
            //'Cookie' => $cookie,
            'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
            'Accept' => '*/*',
            'Accept-Encoding' => 'gzip, deflate',
            'Accept-Language' => 'zh-CN,zh;q=0.9,en;q=0.8',
            'Connection' => 'keep-alive',
            'Host' => 'ghzx.gdems.com',
            'Origin' => 'http://ghzx.gdems.com',
            'Referer' => 'http://ghzx.gdems.com/ghzx/filterJsp/tax/check.jsp',
            'user-agent' => $agent
        ];


        $start_time = strtotime("-5 days");
        $end_time = strtotime("+1 day");
        //dump(date('Y-m-d H:i:s',$start_time));
        //dump(date('Y-m-d H:i:s',$end_time));

        $post_data = [
            'queryFs' => 'plcx',
            'beginDate' => date('Y-m-d',$start_time),
            'endDate' => date('Y-m-d',$end_time),
            'total' => '0,1000',
            'selectZfzt' => '2',
            'sjhm' => '13237051757',
            'yjhm' => '',
            'phoneNum' => '13237051757',
        ];

        $ch = curl_init();
        curl_setopt ($ch, CURLOPT_URL, $data_url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($post_data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        //curl_setopt($ch, CURLOPT_COOKIE, $cookie);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
        curl_setopt($ch, CURLOPT_USERAGENT, $agent);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);

        //curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC); //开启代理认证模式
        //curl_setopt($ch, CURLOPT_PROXY, "http://proxy.baibianip.com"); //本地服务器IP地址
        //curl_setopt($ch, CURLOPT_PROXYPORT, 8000); //本地服务器端口
        //curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $data = curl_exec($ch);
        curl_close($ch);
        //Log::info($data);


        $html = str_replace("\n","",trim($data));


        $pattern = "/<tr class=\"active\">([\s\S]*?)<\/tr>/is";

        preg_match_all($pattern, $html, $output);

        //Log::info(json_encode($output));

        //取物流单号的正则
        $pattern_wuliu_num = "/<input type=\"checkbox\" value=\"([\s\S]*?)\" disabled=\"disabled\"/is";

        //取缴税时间正则
        $pattern_time = "/<td>20([\s\S]*?)<\/td>/is";
        Log::info('请求结束 开始分析数据');
        foreach($output[0] as $vo){
            //Log::info($vo);
            preg_match_all($pattern_wuliu_num, $vo, $wuliu_num);
            //税单时间
            $wuliu_num = $wuliu_num[1][0];

            preg_match_all($pattern_time, $vo, $time);
            //海关出税单时间
            $datetime = $time[1][0];

            $info = DB::table('pay_ems_number')
                -> where([
                    'wuliu_num' => $wuliu_num
                ]) -> first();
            if(!$info){
                DB::table('pay_ems_number')
                    -> insertGetId([
                        'wuliu_num' => $wuliu_num,
                        'bill_time' => strtotime($datetime),
                        'created_at' => time()
                    ]);
            }


            $package_info = DB::table('packages') -> where([
                'wuliu_num' => $wuliu_num,
                'flag' => 0
            ]) -> first();
            if(!$package_info){
                continue;
            }
            Log::info('物流单：'.$wuliu_num.' 税款同步完毕');



            DB::table('ems_package') -> where([
                'package_id' => $package_info -> id,
                'is_update_pay_mail' => 0
            ])
                //已放行的 不做修改
                -> where('ems_status','<>',2)
                -> update([
                    'ems_status' => 7,
                    'is_update_pay_mail' => 1,
                    'mail_pay_time' => $datetime //海关出税单的时间
                ]);
            //Log::info($wuliu_num);
            //Log::info($datetime);
            //Log::info('');

        }


    }
}
