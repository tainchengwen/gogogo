<?php

namespace App\Http\Controllers\ApiMpV1;

use App\WxUser;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use EasyWeChat\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Resources\Json\JsonResource;

class UserController extends Controller
{
    //生成自己的二维码
    public function makeQrCode(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:mp_users,id',
        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }

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


        $response = $app->app_code->getUnlimit($request -> user_id, [

        ]);
        return $response;



    }

    //此用户邀请的人
    public function invitePersons(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:mp_users,id',
        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }


        //找到此用户的wxuser_id
        $model = new WxUser();
        $wxuser_id = $model -> getWxUserId($request -> user_id);
        $users = DB::table('wxuser')
            -> where([
                'from_userid' => $wxuser_id
            ])
            -> select([
                'nickname',
                'headimg'
            ])
            -> get();
        return $users;



    }



    //消费记录
    public function priceRecord(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:mp_users,id',
        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }

        $model = new WxUser();
        $userid = $model -> getWxUserId($request -> user_id);


        $price_log = DB::table('price_log') -> where([
            'userid' => $userid,
        ])
            -> where(function($query)use($request){
                if($request -> price_type == 1){
                    //收入
                    $query -> where('in_out',0);
                }elseif($request -> price_type == 2){
                    //支出
                    $query -> where('in_out',1);
                }
            })
        -> orderBy('created_at','desc') ->  get();
        //配置
        $configs = config('admin.price_log_type');
        foreach($price_log as $k => $vo){
            $price_log[$k] -> type_name = $configs[$vo -> type];
            if($vo -> in_out == 0){
                //收入
                $price_log[$k] -> price = '+'.$vo -> price;
            }else{
                $price_log[$k] -> price = '-'.$vo -> price;
            }
            $price_log[$k] -> created_at_str = date('Y-m-d H:i',$vo -> created_at);
        }

        return $price_log;


    }


}
