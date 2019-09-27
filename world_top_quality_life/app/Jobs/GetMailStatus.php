<?php

namespace App\Jobs;

use App\Package;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GetMailStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $package;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Package $package)
    {
        //
        $this -> package = $package;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //
        //
        if($this -> package -> wuliu_num){
            Log::info('mail_getStatus_start');
            $data_url = "http://ghzx.gdems.com/ghzx/email/mail_sbxc.action";     //数据所在地址
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
                'Referer' => 'http://ghzx.gdems.com/ghzx/jsp/query/query.jsp',
                'user-agent' => $agent
            ];

            $post_data = [
                'yjhm' => $this -> package -> wuliu_num
            ];

            $ch = curl_init();
            curl_setopt ($ch, CURLOPT_URL, $data_url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($post_data));
            //curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
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
            Log::info('请求结束');

            if(strpos($data, '说明')){
                Log::info('关邮e通跑成功了---yeahohohohoh');
                //直接保存table
                //Log::info($this -> package -> id);
                //Log::info('mail___');

                //Log::info(print_r($data,true));

                $is_end_mail = 0;
                if(strpos($data,'离开广州互换')){
                    $is_end_mail = 1;
                }

                DB::table('packages') -> where([
                    'id' => $this -> package -> id
                ]) -> update([
                    'mail_status_table' => $data,
                    'is_end_mail' => $is_end_mail
                ]);

                $package_info = DB::table('packages') -> where([
                    'id' => $this -> package -> id
                ]) -> first();

                //跑到关邮e通表里
                Package::insertInfoEms($package_info);


            }else{
                Log::info('mailGetStatus_Fail'.$data);
            }

        }



    }
}
