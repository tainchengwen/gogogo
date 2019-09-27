<?php

namespace App\Http\Controllers\ApiMp;

use EasyWeChat\Factory;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    //小程序授权登录
    public function mpLogin(Request $request){
        $validator = Validator::make($request->all(), [
            'code' => 'required',
        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }
        $code = $request -> code;
        $config = [
            'app_id' => env('MINI_APPID'),
            'secret' => env('MINI_SECRET'),

            // 下面为可选项
            // 指定 API 调用返回结果的类型：array(default)/collection/object/raw/自定义类名
            'response_type' => 'array',

            'log' => [
                'level' => 'debug',
                'file' => __DIR__.'/wechat.log',
            ],
        ];

        $app = Factory::miniProgram($config);
        $app->auth->session($code);
    }


    //code2session
    public function code2Session(Request $request){
        $validator = Validator::make($request->all(), [
            'code' => 'required',
        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }
        $appid = env('MINI_APPID');
        $secret = env('MINI_SECRET');
        $code = $request -> code;
        $url = 'https://api.weixin.qq.com/sns/jscode2session?appid='.$appid.'&secret='.$secret.'&js_code='.$code.'&grant_type=authorization_code';

        $res = file_get_contents($url);

        Log::info('code2Session_return');

        if($res){
            Log::info($res);
        }

        Log::info($res);
        return $res;
    }

    //小程序注册 返回mp_user 的 id
    public function reg(Request $request){
        Log::info('reg');
        Log::info($request->all());
        
        Log::info('开始注册');

        $userinfo = DB::table('mp_users')
            -> where([
                'openid' => $request -> openid
            ]) -> first();
        if(!$userinfo){
            Log::info('不存在mp_user');
            $unionid = $request -> unionid;
            if($unionid){
                //$unionid = $union_openid -> unionid;
                //先查看总wxuser中是否存在此wxuser
                $wxuser_info = DB::table('wxuser')
                    -> where([
                        'unionid' => $unionid
                    ]) -> first();
                Log::info('注册wxuser的:'.$unionid);
                if(!$wxuser_info){
                    Log::info('不存在wxuser');
                    $wxuser_id = DB::table('wxuser') -> insertGetId([
                        'nickname' => $request -> nickname,
                        'headimg' => $request -> headimg,
                        'unionid' => $unionid,
                        'created_at' => time(),
                        'updated_at' => time(),
                    ]);
                    //添加小程序user
                    $userid = DB::table('mp_users')
                        -> insertGetId([
                            'openid' => $request -> openid,
                            'nickname' => $request -> nickname,
                            'headimg' => $request -> headimg,
                            'unionid' =>$unionid,
                            'created_at' => time(),
                            'updated_at' => time(),
                        ]);
                    return [
                        'code' => 200,
                        'userid' => $userid
                    ];
                }else{
                    Log::info('存在wxuser');
                    $wxuser_id = $wxuser_info -> id;

                    //添加小程序user
                    $userid = DB::table('mp_users')
                        -> insertGetId([
                            'openid' => $request -> openid,
                            'nickname' => $request -> nickname,
                            'headimg' => $request -> headimg,
                            'unionid' =>$unionid,
                            'created_at' => time(),
                            'updated_at' => time(),
                        ]);

                    return [
                        'code' => 200,
                        'userid' => $userid
                    ];


                }
                //Log::info('unionid_find_user:'.$unionid);

            }else{
                $userid = DB::table('mp_users')
                    -> insertGetId([
                        'openid' => $request -> openid,
                        'nickname' => $request -> nickname,
                        'headimg' => $request -> headimg,
                        'unionid' =>$unionid,
                        'created_at' => time(),
                        'updated_at' => time(),
                    ]);
                return [
                    'code' => 200,
                    'userid' => $userid
                ];
            }
        }else{
            Log::info('存在mp_user');
            DB::table('mp_users')
                -> where([
                    'id' => $userinfo -> id
                ]) -> update([
                    'nickname' => $request -> nickname,
                    'headimg' => $request -> headimg,
                    'updated_at' => time(),
                    'unionid' => $request -> unionid
                ]);


            return [
                'code' => 200,
                'userid' => $userinfo -> id
            ];
        }

    }


    //获取unionid
    public function getUnionid(){
        $config = [
            'app_id' => env('MINI_APPID'),
            'secret' => env('MINI_SECRET'),

            // 下面为可选项
            // 指定 API 调用返回结果的类型：array(default)/collection/object/raw/自定义类名
            'response_type' => 'array',

            'log' => [
                'level' => 'debug',
                'file' => __DIR__.'/wechat.log',
            ],
        ];

        $app = Factory::miniProgram($config);
        // 获取 access token 实例
        $accessToken = $app->access_token;
        $token = $accessToken->getToken(); // token 数组  token['access_token'] 字符串
        //$openid = 'ovcf15aNUSKoasQAFkPqe8DgM-kw';
        $openid = $_GET['openid'];
        $url = 'https://api.weixin.qq.com/wxa/getpaidunionid?access_token='.$token['access_token'].'&openid='.$openid;

        $res = file_get_contents($url);
        dd($res);
    }



}

