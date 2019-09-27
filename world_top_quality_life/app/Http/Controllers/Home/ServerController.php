<?php

namespace App\Http\Controllers\Home;

use App\MallApi;
use App\WxUser;
use EasyWeChat\Factory;
use EasyWeChat\Kernel\Messages\Transfer;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ServerController extends Controller
{
    //
    public function index(Request $request){

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
        $openId = null;




        $message = $app->server->getMessage();
        //file_put_contents(storage_path('tttt.txt'),'begin '.PHP_EOL,FILE_APPEND);
        //file_put_contents(storage_path('tttt.txt'),json_encode($message).PHP_EOL,FILE_APPEND);
        $Msg = '';
        switch ($message['MsgType']) {
            case 'event':
                $open_id = $message['FromUserName'];
                $openId = $open_id;
                //判断是否是订阅时间
                if($message['Event'] == 'subscribe' && isset($message['Ticket']) && isset($message['EventKey']) ){

                    $request->session()->flush();

                    Log::info('关注');

                    //读取二维码中的信息 邀请人
                    $key = str_replace("qrscene_","",$message['EventKey']);
                    //获取到二维码的userid
                    $open_id = $message['FromUserName'];
                    $openId = $open_id;
                    $isset = DB::table('wxuser') -> where([
                        'openid' => $open_id,
                    ]) -> first();
                    $user = $app->user->get($openId);

                    if(!$isset){
                        Log::info('key_info:'.$key);
                        if($key == 'auto_c_class'){
                            //扫默认商城等级是1的码
                            $user['market_class'] = 2;
                            $user['source'] = 2;
                        }elseif($key == 'short_time_vip_2'){
                            //一个月vip2
                            $user['market_class'] = 2;
                            $user['hope_date'] = date('Y-m-d',time() + 86400*30);
                            $user['hope_market_class'] = 2;
                            $user['source'] = 1;

                        }else{
                            $user['key'] = $key;
                            $user['source'] = 3;
                        }
                        //添加用户
                        $this -> insertUsert($user);
                    }
                    break;
                }

                if($message['Event'] == 'subscribe'){
                    $Msg = "亲，您终于来啦☺️寰球优品在此恭候小主多时☕️

国内现货📦
欧洲轻奢💎
香港直邮🇭🇰
澳洲直邮🇦🇺
日本直邮🇯🇵
总有一款是你想要的

如需联系客服 请在服务中心点击联系客服，就可以与客服小姐姐面对面交流啦

推荐商城专属二维码，好友下单，自己可获专属返点优惠💰

商城专属二维码在公众号—服务中心—邀请好友—获取我的专属二维码

商城也可一件代发，有需要可向客服咨询
寰球优品 购你所想 达你所需🌸";
                    Log::info('关注');
                    //普通订阅
                    $open_id = $message['FromUserName'];
                    $isset = DB::table('wxuser') -> where([
                        'openid' => $open_id,
                    ]) -> first();
                    $user = $app->user->get($openId);
                    if(!$isset){
                        $user['key'] = '';
                        $this -> insertUsert($user);
                    }
                    //return '亲，您终于来啦☺️寰球优品在此恭候小主多时';
                    break;
                }


                if($message['Event'] == 'SCAN' && isset($message['EventKey']) ){
                    if($message['EventKey'] == 'short_time_vip_2'){
                        $open_id = $message['FromUserName'];

                        $user = DB::table('wxuser') -> where([
                            'openid' => $open_id
                        ]) -> first();
                        if($user){
                            //一个月vip2
                            DB::table('wxuser') -> where([
                                'id' => $user -> id
                            ]) -> update([
                                'market_class' => 2,
                                'hope_date' => date('Y-m-d',time() + 86400*30),
                                'hope_market_class' => 2
                            ]);
                        }
                    }
                }



                if($message['Event'] == 'unsubscribe'){
                    //清除所有session
                    $request->session()->flush();
                    break;

                }

                if($message['Event'] == 'CLICK'){
                    if(in_array($message['EventKey'],['FLY','HOTEL'])){
                        $Msg = '敬请期待!';
                    }
                    if($message['EventKey'] == 'CALL_KF'){
                        $Msg = '您可以直接在对话框内联系到客服!';
                    }
                    if(in_array($message['EventKey'],[
                        'M_GUONEI',
                        'M_OUZHOU',
                        'M_RIBEN',
                        'M_DEGUO',
                        'M_AOZHOU',
                    ])){
                        $Msg = '系统升级，预计2019/4/24恢复，查询和下单请直接回复。';
                    }

                }


                //return '收到事件消息';
                break;
        }



        //$response = $app->server->serve();

        $app->server->push(function($message) {
            return new Transfer();
        });

        if($Msg){
            $app->server->push(function ($message)use($Msg) {
                // $message['FromUserName'] // 用户的 openid
                // $message['MsgType'] // 消息类型：event, text....
                return $Msg;
            });
        }


        $response = $app->server->serve();

// 将响应输出
        return $response; // Laravel 里请使用：return $response;
    }


    public function insertUsert($user){

        $unionid = isset($user['unionid'])?$user['unionid']:'';

        if($unionid){
            $userinfo = DB::table('wxuser') -> where([
                'unionid' => $unionid
            ]) -> first();
            if($userinfo){
                DB::table('wxuser') -> where([
                    'id' => $userinfo -> id
                ]) -> update([
                    'openid' => $user['openid'],
                    'nickname' => $user['nickname'],
                    'headimg' => $user['headimgurl'],
                    'from_userid' => isset($user['key'])?$user['key']:0,
                    'market_class' => isset($user['market_class'])?$user['market_class']:2,
                    'hope_market_class' => isset($user['hope_market_class'])?$user['hope_market_class']:2,
                    'hope_date' => isset($user['hope_date'])?$user['hope_date']:'',
                    'source' => isset($user['source'])?$user['source']:0,
                ]);
                $id = $userinfo -> id;
            }else{
                $id = DB::table('wxuser') -> insertGetId([
                    'openid' => $user['openid'],
                    'nickname' => $user['nickname'],
                    'headimg' => $user['headimgurl'],
                    'unionid' => $unionid,
                    'created_at' => time(),
                    'updated_at' => time(),
                    'from_userid' => isset($user['key'])?$user['key']:0,
                    'market_class' => isset($user['market_class'])?$user['market_class']:2,
                    'hope_market_class' => isset($user['hope_market_class'])?$user['hope_market_class']:2,
                    'hope_date' => isset($user['hope_date'])?$user['hope_date']:'',
                    'source' => isset($user['source'])?$user['source']:0,
                ]);
            }
        }else{
            $id = DB::table('wxuser') -> insertGetId([
                'openid' => $user['openid'],
                'nickname' => $user['nickname'],
                'headimg' => $user['headimgurl'],
                'created_at' => time(),
                'updated_at' => time(),
                'from_userid' => isset($user['key'])?$user['key']:0,
                'market_class' => isset($user['market_class'])?$user['market_class']:2,
                'hope_market_class' => isset($user['hope_market_class'])?$user['hope_market_class']:2,
                'hope_date' => isset($user['hope_date'])?$user['hope_date']:'',
                'source' => isset($user['source'])?$user['source']:0,
            ]);
        }





        $model_user = new WxUser();
        $erp_id = $id.'-'.$user['nickname'];
        $is_reg = $model_user -> regErpId($erp_id,$user['nickname']);
        if($is_reg){
            DB::table('wxuser') -> where([
                'id' => $id
            ]) -> update([
                'erp_id' => str_replace('?','',$erp_id)
            ]);
        }

        //假如是有人邀请的 则向邀请人发信息
        if(isset($user['key']) && $user['key'] != 'auto_c_class'){
            $from_userinfo = DB::table('wxuser') -> where([
                'id' => $user['key'],
            ]) -> first();
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
            $app->template_message->send([
                'touser' => $from_userinfo -> openid,
                'template_id' => '7bSeqCXx6LlYU3eu0W7JnXSdGFalwptD4sBsPndA0a4',
                'url' => url('myfriend'),
                'data' => [
                    'first' => '恭喜，您邀请的好友已注册',
                    'keyword1' => $user['nickname'],
                    'keyword2' => date('Y-m-d H:i'),
                    'remark' => '感谢您的使用'
                ]
            ]);

        }


        //保存登陆信息

        session(['wechat_user' => [
            'openid' => $user['openid'],
            'nickname' => $user['nickname'],
            'headimg' => $user['headimgurl'],
            'apires' => json_encode($user)

        ]]);

        session([
            'home_user_id' => $id
        ]);
    }
}
