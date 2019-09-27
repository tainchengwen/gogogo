<?php

namespace App\Http\Controllers\ApiShopMp;

use EasyWeChat\Factory;
use App\WxUsers as ShopMPUsers;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiShopMp\Controller;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    private function getWXConfig()
    {
        return  [
            'app_id' => env('MINI_TMP_SHOP_APPID'),
            'secret' => env('MINI_TMP_SHOP_SECRET'),
            // 下面为可选项
            // 指定 API 调用返回结果的类型：array(default)/collection/object/raw/自定义类名
            'response_type' => 'array'
        ];
    }

    /**
     * 通过code检查用户是否存在，存在返回token
     */
    public function check(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code'          => 'required'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($request, [], '缺少必要参数');
            // return new JsonResource($validator->errors());
        }

        $wxConfig = $this->getWXConfig();
        $app = Factory::miniProgram($wxConfig);
        $WXReturn = $app->auth->session($request -> code);

        if (empty($WXReturn['session_key'])) {
            return $this->errorResponse($request, [], 'code 不存在！');
        }

        // 拿到了 openId
        $mpUser = ShopMPUsers::where([
            'openid' => $WXReturn['openid']
        ])
        -> first();

        if (empty($mpUser)) {
            return $this->errorResponse($request, []);
        } else {
            $request->refreshToken = JWTAuth::fromUser($mpUser);
            return $this->successResponse($request, []);
        }
    }

    //小程序授权登录
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code'          => 'required',
            'iv'            => 'required',
            'encryptData'   => 'required',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($request, [], '缺少必要参数');
            // return new JsonResource($validator->errors());
        }

        // $wxConfig = [
        //     'app_id' => env('MINI_SHOP_APPID'),
        //     'secret' => env('MINI_SHOP_SECRET'),
        //     // 下面为可选项
        //     // 指定 API 调用返回结果的类型：array(default)/collection/object/raw/自定义类名
        //     'response_type' => 'array'
        // ];

        $wxConfig = $this->getWXConfig();

        $app = Factory::miniProgram($wxConfig);
        $WXReturn = $app->auth->session($request -> code);

        if (empty($WXReturn['session_key'])) {
            return $this->errorResponse($request, [], 'code 不存在！');
        }
        // 解密用户数据
        // unionId openId nickName avatarUrl
        $data = $app->encryptor->decryptData($WXReturn['session_key'], $request->iv, $request->encryptData);

        // shop
        $userinfo = ShopMPUsers::where([
                'openid' => $data['openId'],
            ])
            -> first();

        if(!$userinfo){
            //先查看总wxuser中是否存在此wxuser
            $wxuser_info = DB::table('wxuser')
                -> where([
                    'unionid' => $data['unionId']
                ]) -> first();

            if(!$wxuser_info){
                // 新建微信用户
                $wxuser_id = DB::table('wxuser') -> insertGetId([
                    'nickname'      => $data['nickName'],
                    'headimg'       => $data['avatarUrl'],
                    'unionid'       => $data['unionId'],
                    // 默认vip2
                    'market_class'  => 2,
                    'created_at'    => time(),
                    'updated_at'    => time(),
                ]);
                //添加小程序user
                $userid = ShopMPUsers::insertGetId([
                        'openid'        => $data['openId'],
                        'nickname'      => $data['nickName'],
                        'headimg'       => $data['avatarUrl'],
                        'unionid'       => $data['unionId'],
                        'user_id'       => $wxuser_id,
                        'created_at'    => time(),
                        'updated_at'    => time(),
                    ]);
                $user = ShopMPUsers::where([
                    'id' => $userid
                ]) -> first();
            }else{
                $wxuser_id = $wxuser_info -> id;
                //添加小程序user
                $userid = ShopMPUsers::insertGetId([
                        'openid'        => $data['openId'],
                        'nickname'      => $data['nickName'],
                        'headimg'       => $data['avatarUrl'],
                        'user_id'       => $wxuser_id,
                        'unionid'       => $data['unionId'],
                        'created_at'    => time(),
                        'updated_at'    => time(),
                    ]);

                $user = ShopMPUsers::where([
                    'id' => $userid
                ]) -> first();

            }
            $request->refreshToken = JWTAuth::fromUser($user);
            return $this->successResponse($request, [], '登录成功');
        }else{
            ShopMPUsers::where([
                    'id' => $userinfo -> id
                ]) -> update([
                    'nickname'      => $data['nickName'],
                    'headimg'       => $data['avatarUrl'],
                    'updated_at'    => time(),
                ]);
            $request->refreshToken = JWTAuth::fromUser($userinfo);
            return $this->successResponse($request, [], '登录成功');
        }

    }

    /**
     * 新的登录方法(允许登录为游客)
     * @param Request $request
     * @return array
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     */
    public function signIn(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code'          => 'required',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($request, [], '缺少必要参数');
        }

        $wxConfig = $this->getWXConfig();

        $app = Factory::miniProgram($wxConfig);
        $WXReturn = $app->auth->session($request -> code);//['session_key','openid','unionid'?]

        if (empty($WXReturn['session_key'])) {
            return $this->errorResponse($request, [], 'code 不存在！');
        }

        // shop
        $userinfo = ShopMPUsers::where([
            'openid' => $WXReturn['openid'],
        ])-> first();

        //老用户直接登录成功,新用户返回游客数据
        if(!$userinfo){
            //获取游客配置
            //$visitor=config('admin.visitor');
            //添加小程序user
            $userid = ShopMPUsers::insertGetId([
                'openid'        => $WXReturn['openid'],
                //'nickname'      => $visitor['nickname'],
                //'headimg'       => $visitor['headimg'],
                'user_id'       => 0,
                'unionid'       => isset($WXReturn['unionid'])?$WXReturn['unionid']:null,
                'created_at'    => time(),
                'updated_at'    => time(),
            ]);

            $user = ShopMPUsers::where([
                'id' => $userid
            ]) -> first();

            $request->refreshToken = JWTAuth::fromUser($user);
            return $this->successResponse($request, ['is_visitor'=>1], '登录成功');
        }else{
            ShopMPUsers::where([
                'id' => $userinfo -> id
            ]) -> update([
                'updated_at'    => time(),
            ]);
            $request->refreshToken = JWTAuth::fromUser($userinfo);
            $is_visitor=$userinfo->user_id?0:1;
            return $this->successResponse($request, ['is_visitor'=>$is_visitor], '登录成功');
        }

    }

    /**
     * 授权绑定账号
     * @param Request $request
     * @return array
     * @throws \EasyWeChat\Kernel\Exceptions\DecryptException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     */
    public function auth(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code'          => 'required',
            'iv'            => 'required',
            'encryptData'   => 'required',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($request, [], '缺少必要参数');
        }

        $wxConfig = $this->getWXConfig();

        $app = Factory::miniProgram($wxConfig);
        $WXReturn = $app->auth->session($request -> code);

        if (empty($WXReturn['session_key'])) {
            return $this->errorResponse($request, [], 'code 不存在！');
        }
        // 解密用户数据
        // unionId openId nickName avatarUrl
        $data = $app->encryptor->decryptData($WXReturn['session_key'], $request->iv, $request->encryptData);

        //先查看总wxuser中是否存在此wxuser
        $wxuser_info = DB::table('wxuser')
            -> where([
                'unionid' => $data['unionId']
            ]) -> first();

        if(!$wxuser_info){
            // 新建微信用户
            $wxuser_id = DB::table('wxuser') -> insertGetId([
                'nickname'      => $data['nickName'],
                'headimg'       => $data['avatarUrl'],
                'unionid'       => $data['unionId'],
                // 默认vip2
                'market_class'  => 2,
                'created_at'    => time(),
                'updated_at'    => time(),
            ]);
        }else{
            $wxuser_id = $wxuser_info -> id;
        }

        // shop
        $userinfo = ShopMPUsers::where([
            'openid' => $data['openId'],
        ])-> first();

        if(!$userinfo){
            //添加小程序user
            $userid = ShopMPUsers::insertGetId([
                'openid'        => $data['openId'],
                'nickname'      => $data['nickName'],
                'headimg'       => $data['avatarUrl'],
                'user_id'       => $wxuser_id,
                'unionid'       => $data['unionId'],
                'created_at'    => time(),
                'updated_at'    => time(),
            ]);

            $user = ShopMPUsers::where([
                'id' => $userid
            ]) -> first();

            //$request->refreshToken = JWTAuth::fromUser($user);
            return $this->successResponse($request, ['is_visitor'=>0], '授权成功');
        }else{
            ShopMPUsers::where([
                'id' => $userinfo -> id
            ]) -> update([
                'user_id'      => $wxuser_id,
                'unionid'      => $data['unionId'],
                'nickname'      => $data['nickName'],
                'headimg'       => $data['avatarUrl'],
                'updated_at'    => time(),
            ]);
            //$request->refreshToken = JWTAuth::fromUser($userinfo);
            return $this->successResponse($request, ['is_visitor'=>0], '授权成功');
        }

    }
}

