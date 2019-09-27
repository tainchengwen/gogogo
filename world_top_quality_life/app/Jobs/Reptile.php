<?php

namespace App\Jobs;

use App\MpScanInfo;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Reptile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $mpScanInfo;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(MpScanInfo $mpScanInfo)
    {
        //扫描商品的id
        $this -> mpScanInfo = $mpScanInfo;
    }

    //翻译入口
    function translate($query, $from, $to)
    {
        $args = array(
            'q' => $query,
            'appid' => '20190221000269664',
            'salt' => rand(10000,99999),
            'from' => $from,
            'to' => $to,

        );
        $args['sign'] = $this -> buildSign($query, '20190221000269664', $args['salt'], 'GZaCC3BfHk54L9QUdq0n');
        $ret = $this -> call('http://api.fanyi.baidu.com/api/trans/vip/translate', $args);
        $ret = json_decode($ret, true);
        return $ret;
    }

    //加密
    function buildSign($query, $appID, $salt, $secKey)
    {/*{{{*/
        $str = $appID . $query . $salt . $secKey;
        $ret = md5($str);
        return $ret;
    }/*}}}*/

    //发起网络请求
    function call($url, $args=null, $method="post", $testflag = 0, $timeout = 10, $headers=array())
    {/*{{{*/
        $ret = false;
        $i = 0;
        while($ret === false)
        {
            if($i > 1)
                break;
            if($i > 0)
            {
                sleep(1);
            }
            $ret = $this -> callOnce($url, $args, $method, false, $timeout, $headers);
            $i++;
        }
        return $ret;
    }/*}}}*/

    function callOnce($url, $args=null, $method="post", $withCookie = false, $timeout = 10, $headers=array())
    {/*{{{*/
        $ch = curl_init();
        if($method == "post")
        {
            $data = $this -> convert($args);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_POST, 1);
        }
        else
        {
            $data = $this -> convert($args);
            if($data)
            {
                if(stripos($url, "?") > 0)
                {
                    $url .= "&$data";
                }
                else
                {
                    $url .= "?$data";
                }
            }
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if(!empty($headers))
        {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        if($withCookie)
        {
            curl_setopt($ch, CURLOPT_COOKIEJAR, $_COOKIE);
        }
        $r = curl_exec($ch);
        curl_close($ch);
        return $r;
    }/*}}}*/

    function convert(&$args)
    {/*{{{*/
        $data = '';
        if (is_array($args))
        {
            foreach ($args as $key=>$val)
            {
                if (is_array($val))
                {
                    foreach ($val as $k=>$v)
                    {
                        $data .= $key.'['.$k.']='.rawurlencode($v).'&';
                    }
                }
                else
                {
                    $data .="$key=".rawurlencode($val)."&";
                }
            }
            return trim($data, "&");
        }
        return $args;
    }/*}}}*/

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        $info = DB::table('mp_scan_goods_info')
            -> where([
                'id' => $this -> mpScanInfo -> id
            ]) -> first();

        $code = trim($info -> product_no);
        //用商品编码看下 这个东西有没有
        $info = DB::table('reptile_product_list') -> where([
            'product_no' => trim($info -> product_no)
        ]) -> first();
        if($info){
            return false;
        }

        $url = 'https://shopping.yahoo.co.jp/search?first=1&p='.$code;
        //Log::info($url);
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

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_USERAGENT, $agent);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1);

        curl_setopt($curl, CURLOPT_PROXYAUTH, CURLAUTH_BASIC); //开启代理认证模式
        curl_setopt($curl, CURLOPT_PROXY, "http://proxy.baibianip.com"); //本地服务器IP地址
        curl_setopt($curl, CURLOPT_PROXYPORT, 8000); //本地服务器端口
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $contents = curl_exec($curl);
        if (curl_error($curl)) {
            Log::info(curl_error($curl));
        }
        curl_close($curl);




        /*
        $url = 'https://shopping.yahoo.co.jp/search?first=1&p=4908049429690';
        $contents = file_get_contents($url);
        file_put_contents('/usr/local/var/www/htdocs/package/storage/logs/newtest.txt',$contents);exit;
*/


        //$contents = file_get_contents('/usr/local/var/www/htdocs/package/storage/logs/newtest.txt');
        //$contents = file_get_contents($url);
        $pattern = "/<div class=\"uiContentsList\">([\s\S]*?)<\/div>/iUs";

        preg_match_all($pattern, $contents, $output);


        $contents2 = isset($output[0][0])?$output[0][0]:'';
        if(!$contents2){
            Log::info('爬虫出错'.$code);
            return false;
        }
        $pattern_2 = "/<li([\s\S]*?)<\/li>/is";

        preg_match_all($pattern_2, $contents2, $output2);

        $temp = [];
        foreach($output2[0] as $k => $value){
            //拿图片 拿价格 拿商品名称

            //取图片
            $pattern_img = "/<img src([\s\S]*?)>\n/is";
            preg_match_all($pattern_img, $value, $image);

            //取名称
            $pattern_name = "/<span>([\s\S]*?)<\/span>/is";
            preg_match_all($pattern_name, $value, $name);

            /*
                0 => array:5 [▼
                        0 => "<span>ディープチャージ コラーゲン ドリンク 約10日分 【ファンケル 公式】</span>"
                        1 => "<span>2,468</span>"
                        2 => "<span>ポイント1倍</span>"
                        3 => "<span>通常ポイント</span>"
                        4 => "<span>（+1倍）</span>"
                      ]
            */
            if(isset($image[0][0]) && isset($name[0][0])){
                $temp[$k]['image'] = $image[0][0];

                $temp[$k]['name'] = isset($name[0][0])?$name[0][0]:'';
                $temp[$k]['price'] = isset($name[0][1])?$name[0][1]:'';
                $temp[$k]['temp1'] = isset($name[0][2])?$name[0][2]:'';
                $temp[$k]['temp2'] = isset($name[0][3])?$name[0][3]:'';
                $temp[$k]['temp3'] = isset($name[0][4])?$name[0][4]:'';
            }
        }


        foreach($temp as $k => $vo){
            if($k == 6){
                break;
            }

            //取中文名字
            $zh_name = $this -> translate(strip_tags($vo['name']),'jp','zh');
            if(isset($zh_name['trans_result'][0]['dst'])){
                $zh_name = $zh_name['trans_result'][0]['dst'];
            }else{
                $zh_name = '';
            }

            //英文名字
            $en_name = $this -> translate(strip_tags($vo['name']),'jp','en');
            if(isset($en_name['trans_result'][0]['dst'])){
                $en_name = $en_name['trans_result'][0]['dst'];
            }else{
                $en_name = '';
            }

            //插入数据库
            DB::table('reptile_product_list') -> insertGetId([
                'mp_scan_info_id' => $info -> id,
                'product_no' => $info -> product_no,
                'image' =>$vo['image'],
                'jap_name' => strip_tags($vo['name']),
                'price' => strip_tags($vo['price']),
                'zh_name' => $zh_name,
                'en_name' => $en_name,
                'created_at' => time(),
                'updated_at' => time(),
            ]);
        }
    }


}
