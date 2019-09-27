<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class NumberQueue extends Model
{

    protected $table = 'erp_number_queue';


    protected $dateFormat = 'U';


    //采购订单生成订单 id
    static function addQueueNo($type = 0){

        switch ($type){
            case '0':$prefix = 'P'; break; //采购单编号
            case '1':$prefix = 'D'; break; //物流单编号
            case '2':$prefix = 'W'; break; //入库单编号
            case '3':$prefix = 'S'; break; //库存销售编号
            case '4':$prefix = 'I'; break; //发货编号
            case '5':$prefix = 'E'; break; //手动打印快递单编号
            case '6':$prefix = 'T'; break; //库位转移， 之后 生成新的入库单号

            default:$prefix = 'W';
        }

        $queue = DB::table('erp_number_queue') -> where([
            'date_str' => date('Ymd'),
            'number_type' => $type
        ]) -> first();
        if($queue){
            DB::table('erp_number_queue')
                -> where('id',$queue -> id)
                -> update([
                    'number' => intval($queue -> number) + 1,
                    'updated_at' => time()
                ]);
            if ($type ==3){
                return $prefix.'-'.date('Ymd').'-'.str_random(1).'-'.sprintf('%06s',intval($queue -> number) + 1);//防并发
            }
            return $prefix.'-'.date('Ymd').'-'.sprintf('%06s',intval($queue -> number) + 1);

        }else{
            DB::table('erp_number_queue') -> insert([
                'created_at' => time(),
                'updated_at' => time(),
                'date_str' => date('Ymd'),
                'number' => 1,
                'number_type' => $type
            ]);
            if ($type ==3){
                return $prefix.'-'.date('Ymd').'-'.str_random(1).'-000001';//防并发
            }
            return $prefix.'-'.date('Ymd').'-000001';
        }
    }
}
