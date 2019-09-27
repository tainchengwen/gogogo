<?php
/**
 * Created by PhpStorm.
 * User: richer
 * Date: 2018/11/21
 * Time: 13:25
 */

namespace App\Http\Controllers\Auto;


use EasyWeChat\Factory;
use Illuminate\Support\Facades\DB;

class UpdateWxInfoController
{
    //更新微信名，微信头像
    public function updateWxInfo(){
        $config = [
            'app_id' => env('WECHAT_OFFICIAL_ACCOUNT_APPID'),
            'secret' => env('WECHAT_OFFICIAL_ACCOUNT_SECRET'),
            'token' => env('WECHAT_OFFICIAL_ACCOUNT_TOKEN'),

            'response_type' => 'array',
        ];

        $app = Factory::officialAccount($config);
        for($i = 0;$i<=9999999;$i+=10){
            $users = DB::table('wxuser') -> offset($i) -> limit(10) -> get();
            if(!count($users)){
                exit;
            }
            foreach($users as $vo){
                if(!$vo -> openid){
                    continue;
                }
                $user = $app->user->get($vo -> openid);
                //dd($user);
                if(count($user) && isset($user['nickname']) && isset($user['headimgurl'])){

                    DB::table('wxuser') -> where([
                        'openid' => $vo -> openid
                    ]) -> update([
                        'nickname' => $user['nickname'],
                        'headimg' => $user['headimgurl']
                    ]);

                }
            }

        }



    }
}