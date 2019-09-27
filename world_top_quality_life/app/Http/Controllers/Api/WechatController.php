<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Validator;
use DB;

class WechatController extends Controller
{
    public function checkWeChatSubscribe(Request $request)
    {
        $access_token = $this->generateAccessToken();
        $openid = empty($request->openid) ? '' : $request->openid;
        $getUrl = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=$access_token&openid=$openid&lang=zh_CN";
        $res = $this->curl($getUrl, 'get');
        $res = json_decode($res);

        return [
            'subscribe' => empty($res->subscribe) ? 0 : $res->subscribe
        ];
    }

    private function getAccessToken()
    {
        $access_token = DB::table('wx_access_token')
        ->orderBy('id', 'desc')
        ->first();
        if (empty($access_token)) {
            //生成新的并保存返回
            return $this->generateAccessToken();
        }
        if (time() - $access_token->update_time <= $access_token->expires - 100 ) {
            //有效，返回token  
            return $access_token->access_token;
        }
        // 生成token
        return $this->generateAccessToken();
    }

    private function generateAccessToken()
    {
        $appid = env('WECHAT_OFFICIAL_ACCOUNT_APPID');
        $secret = env('WECHAT_OFFICIAL_ACCOUNT_SECRET');
        $wxAccessTokenUrl = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appid&secret=$secret";
        $res = $this->curl($wxAccessTokenUrl, 'get');
        $res = json_decode($res);

        DB::table("wx_access_token")->insert([
            'access_token' => $res->access_token,
            'expires'      => $res->expires_in,
            'update_time'  => time()
        ]);

        return $res->access_token;
    }

    public function curl($url,$method,$post_data = 0){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if ($method == 'post') {
            curl_setopt($ch, CURLOPT_POST, 1);
     
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        }elseif($method == 'get'){
            curl_setopt($ch, CURLOPT_HEADER, 0);
        }
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

}