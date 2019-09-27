<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CheckToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        //return $next($request);
        $header_token = $request -> sign;
        $header_token = base64_decode($header_token);

        if(!strstr($header_token,'&&')){
            return response([
                    'code' => '500',
                    'result' => '签名错误'
                ]);
        }

        //通过 && 分割
        $name_pass = explode('&&',$header_token);
        if(count($name_pass) != 2){
            return response([
                'code' => '500',
                'result' => '签名错误'
            ]);
        }
        $username = $name_pass[0];
        $password = $name_pass[1];

        //var_dump($username.' '.$password);
        //先看下是否有此用户
        $userinfo = DB::table('admin_users') -> where('username','=',$username) -> first();
        if(!$userinfo){
            return response([
                'code' => '500',
                'result' => '验证失败'
            ]);
        }
        //验证账号密码 是否正确  验证余额
        if(!Hash::check( $password,$userinfo -> password)){
            return response([
                'code' => '500',
                'result' => '验证失败'
            ]);
        }

        //验证余额
        $area_info = DB::table('area_name') -> where([
            'id' => $userinfo -> from_area
        ]) -> first();
        if($area_info -> price <= 0){
            return response([
                'code' => '500',
                'result' => '余额不足'
            ]);
        }


        return $next($request);
    }
}
