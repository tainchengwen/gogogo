<?php

namespace App\Http\Controllers\ApiShopMpAgent;

use EasyWeChat\Factory;
use App\WxUsers as ShopMPUsers;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiShopMpAgent\Controller;
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
            'app_id' => env('MINI_SHOP_APPID'),
            'secret' => env('MINI_SHOP_SECRET'),
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
            //'business_id'   => 'required',
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
            //检测business_id
            if(!empty($request->business_id)){
                $business_id=$request->input('business_id');
                $business=DB::table('erp_business')->where('id',$business_id)->first();
                if(!$business){
                    $business_id=49;
                }
            }else{
                //没有business_id就绑定到自营事业部id
                $business_id=49;
            }

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
                        'business_id'   => $business_id,
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
                        'business_id'   => $business_id,
                    ]);

                $user = ShopMPUsers::where([
                    'id' => $userid
                ]) -> first();

            }
            $request->refreshToken = JWTAuth::fromUser($user);
            return $this->successResponse($request, ['business_id'=>$business_id], '登录成功');
        }else{
            //兼容老用户未绑定businessid
            if(!$userinfo->business_id){
                $business_id=49;
                $update_data=[
                    'nickname'      => $data['nickName'],
                    'headimg'       => $data['avatarUrl'],
                    'business_id'   => $business_id,
                    'updated_at'    => time(),
                ];
            }else{
                $business_id=$userinfo->in_white_list == 1 && $request->business_id ? $request->business_id :$userinfo->business_id;
                $update_data=[
                    'nickname'      => $data['nickName'],
                    'headimg'       => $data['avatarUrl'],
                    'updated_at'    => time(),
                ];
            }
            ShopMPUsers::where([
                    'id' => $userinfo -> id
                ]) -> update($update_data);

            $request->refreshToken = JWTAuth::fromUser($userinfo);
            return $this->successResponse($request, ['business_id'=>$business_id], '登录成功');
        }

    }
}

