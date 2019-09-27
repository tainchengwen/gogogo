<?php

namespace App\Http\Controllers;

use App\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TranslateController extends Controller
{
    public function test(){
        for($i=0;$i<=1000000;$i+=10){
            $product_list = DB::table('reptile_product_list')
                -> where([
                    'en_name' => ''
                ])
                -> offset($i)
                -> limit(10)
                -> get();
            if(!count($product_list)){
                exit;
            }

            foreach($product_list as $vo){
                //英文名字
                $en_name = $this -> translate(strip_tags($vo -> jap_name),'jp','en');
                \Illuminate\Support\Facades\Log::info($en_name);
                if(isset($en_name['trans_result'][0]['dst'])){
                    $en_name = $en_name['trans_result'][0]['dst'];
                }else{
                    $en_name = '';
                }


                if($en_name){
                    DB::table('reptile_product_list') -> where([
                        'id' => $vo -> id
                    ]) -> update([
                        'en_name' => $en_name
                    ]);
                }

            }

            sleep(1);

            //exit;


        }




        $code = 'に一致する商品は見つかりませんでした';
        $msg = $this -> translate($code,'jp','en');
        dd($msg);
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
}
