<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    //
    public function theList(){
        $list = DB::table('wxuser')
            -> select([
                'id',
                'nickname',
                'created_at',
                'headimg',
                'price', //余额
                'fandian', //返点金额
                'market_class', //商城等级
                'is_vip', //是否星标用户
                'from_userid',
            ])
            -> paginate();

        //找邀请人
        foreach($list as  $k => $vo){
            if($vo -> from_userid){
                $temp = DB::table('wxuser')
                    -> where([
                        'id' => $vo -> from_userid
                    ]) -> first();

                $list[$k] -> from_user_name = $temp -> nickname;
            }else{
                $list[$k] -> from_user_name = '';
            }
        }


        return $list;
    }










}
