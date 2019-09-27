<?php

namespace App;

use EasyWeChat\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class WxUser extends Model
{
    //
    protected $table = 'wxuser';


    protected $dateFormat = 'U';

    public function order(){
        return $this->hasOne(Order::class);
    }

    public function checkIsReg($user){
        $isset = DB::table('wxuser') -> where([
            'openid' => $user['openid'],
            'flag'=>0
        ]) -> first();
        if($isset){
            //如果存在 判断下是否保存了nickname headimg 这些
            if(!$isset->nickname || !$isset->headimg ){
                //更新下
                DB::table('wxuser') -> where([
                    'openid' => $user['openid'],
                ]) -> update([
                    'nickname' => $user['nickname'],
                    'headimg' => $user['headimg'],
                    'unionid' => $user['unionid'],
                    'updated_at' => time(),
                ]);
                $isset = DB::table('wxuser') -> where([
                    'openid' => $user['openid'],
                    'flag'=>0
                ]) -> first();

            }

            if(!$isset -> erp_id){
                //如果不存在erp_id
                $erp_id = $isset -> id.'-'.$isset -> nickname;
                $res_res = $this -> regErpId($erp_id,$isset -> nickname);
                if($res_res){
                    //更新
                    DB::table('wxuser') -> where([
                        'id' => $isset -> id
                    ]) -> update([
                        'erp_id' => $erp_id
                    ]);
                }

            }

            //保存登陆信息
            session([
                'home_user_id' => $isset -> id
            ]);
            return true;
        }else{
            //没有就注册
            //先找union_id
            $unionid = isset($user['unionid'])?$user['unionid']:0;
            /*
            $get_res = $this -> getUnionid($user['openid']);
            $unionid = '';
            if($get_res){
                $json = json_decode($get_res,true);
                if(isset($json['unionid'])){
                    $unionid = $json['unionid'];
                }

            }
            */

            if($unionid){
                $wxuser = DB::table('wxuser') ->  where([
                    'unionid' => $unionid
                ]) -> first();
                if($wxuser){
                    //更新
                    DB::table('wxuser') -> where([
                        'unionid' => $unionid
                    ]) -> update([
                        'openid' => $user['openid'],
                        'nickname' => $user['nickname'],
                        'headimg' => $user['headimg'],
                        'updated_at' => time()
                    ]);
                    $id = $wxuser -> id;
                }else{
                    $id = DB::table('wxuser') -> insertGetId([
                        'openid' => $user['openid'],
                        'nickname' => $user['nickname'],
                        'headimg' => $user['headimg'],
                        'unionid' => $unionid,
                        'created_at' => time(),
                        'updated_at' => time(),
                    ]);
                }


            }else{
                $id = DB::table('wxuser') -> insertGetId([
                    'openid' => $user['openid'],
                    'nickname' => $user['nickname'],
                    'headimg' => $user['headimg'],
                    'unionid' => $unionid,
                    'created_at' => time(),
                    'updated_at' => time(),
                ]);
            }

            if($id){
                $erp_id = $id.'-'.$user['nickname'];
                $is_reg = $this -> regErpId($erp_id,$user['nickname']);
                if($is_reg){
                    DB::table('wxuser') -> where([
                        'id' => $id
                    ]) -> update([
                        'erp_id' => $erp_id
                    ]);

                }
            }



            //保存登陆信息
            session([
                'home_user_id' => $id,
            ]);
            return false;
        }

    }

    static function  getUnionid($openid){
        return false;
        //先找union_id
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
        $accessToken = $app->access_token;
        $token = $accessToken->getToken(); // token 数组  token['access_token'] 字符串

        $url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$token['access_token'].'&openid='.$openid.'&lang=zh_CN';

        $get_res = file_get_contents($url);

        return $get_res;
    }


    //查看下 erp_id 是否在erp注册
    public function regErpId($erp_id,$nickname){
        $model = new MallApi();
        $customer_res = $model -> getCustomer([
            'CustomerName' => str_replace('?','',$erp_id),
        ]);
        if(count($customer_res)){
            foreach($customer_res as $vo){
                if($vo['CustomerName'] == $erp_id){
                    return false;
                }
            }
        }

        //如果没有 直接新增
        $model -> addCustomer([
            'CustomerName' => str_replace('?','',$erp_id),
            'CustomerDesc' => '商城系统初始化添加',
            'Remark' => $nickname,
        ]);
        return true;

    }




    //查看是否签约
    public function checkIsSign($user){
        return true;
        $isset = DB::table('wxuser') -> where([
            'openid' => $user['openid'],
            'is_sign' => 1,
            'flag' => 0
        ]) -> first();
        if($isset){
            return true;
        }else{
            return false;
        }
    }

    //开始签约
    public function sign(){

    }



    //通过小程序userid 获取 微信userid
    public function getWxUserId($mp_userid){
        $mp_user = DB::table('mp_users') -> where([
            'id' => $mp_userid
        ]) -> first();
        $wxuser = DB::table('wxuser') -> where([
            'unionid' => $mp_user -> unionid
        ]) -> first();
        return $wxuser -> id;
    }

    /**
     * 获得用户拥有的优惠券
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function coupons()
    {
        return $this->belongsToMany('App\MarketCoupon','erp_user_coupon_link','user_id','market_coupon_id')
            ->withPivot('status','created_at','invalid_at');
    }

    /**
     * 组装游客信息
     * @return \Illuminate\Foundation\Application|mixed
     */
    public static function assembleVisitor()
    {
        //读取游客配置信息
        $visitor=config('admin.visitor');
        //生成wxuser实例
        $wxuser=app(self::class);
        //组装游客信息
        $wxuser->id=0;
        $wxuser->nickname=$visitor['nickname'];
        $wxuser->headimg=$visitor['headimg'];
        $wxuser->market_class=$visitor['market_class'];
        $wxuser->price=$visitor['price'];
        $wxuser->fandian=$visitor['fandian'];
        $wxuser->is_new=$visitor['is_new'];
        //返回
        return $wxuser;
    }
}
