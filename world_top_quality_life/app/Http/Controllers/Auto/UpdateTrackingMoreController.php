<?php
/**
 * Created by PhpStorm.
 * User: richer
 * Date: 2018/11/25
 * Time: 16:52
 */

namespace App\Http\Controllers\Auto;


use App\Order;
use Illuminate\Support\Facades\DB;

class UpdateTrackingMoreController
{

    //每天00:00 跑  更新trackingMore
    public function index(){

        $send_order_list = DB::table('send_order_list') -> where([
            'is_trackingMore' => 0
        ]) -> orderBy('id','desc') -> limit(10) -> get();


        foreach($send_order_list as $vo){
            $send_order_id = $vo -> id;
            //发起异步请求
            $model = new Order();
            $res = $model -> asyPost(url('asy/trackingMore'),[
                'id'=>$send_order_id,
                'sign' => 's1d1f2g3h4k5'
            ]);

            //发送成功后，修改值
            DB::table('send_order_list') -> where([
                'id'=>$send_order_id,
            ]) -> update([
                'updated_at' => time(),
                'is_trackingMore' => 1
            ]);
        }



    }
}