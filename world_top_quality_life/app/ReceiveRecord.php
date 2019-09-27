<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ReceiveRecord extends Model
{
    //
    protected $table = 'erp_receive_record';


    protected $dateFormat = 'U';

    //仓库收货
    static function addRecord($request,$user_id){
        //先取编号
        $receive_num = NumberQueue::addQueueNo(2);

        $storehouse_info = Storehouse::find($request -> storehouse_id);

        $logistics = Logistics::find($request -> id);

        //生成入库单
        $receive_id = ReceiveRecord::insertGetId([
            'receive_num' => $receive_num,
            'store_house_id' => $request -> storehouse_id,
            'warehouse_id' => $storehouse_info -> warehouse_id,
            'business_id' => $request -> business_id,
            'extras' => $request -> extras,
            'logistics_id' => $request -> id,
            'remark' => $request -> remark,
            'order_id' => $logistics -> order_id,
            'created_at' => time(),
            'updated_at' => time(),
            'operator_user_id' => $user_id
        ]);
        //判断运费类型
        //货物总重量
        $weight_all = 0;
        $weight_record = [];
        $unit_price_record = [];

        $volume_all = 0;
        $info_arr = json_decode($request -> receive_num_json,true);

        //生成入库单详情
        foreach($info_arr as $vo){
            //通过查 logistics_info 得到goods_id
            //[{"info_id":"2","receive_num":"34"},{}]
            $logistics_info = LogisticsInfo::find($vo['info_id']);
            //采购单详情
            $purchase_order_goods_info = PurchaseOrderGoods::find($logistics_info -> goods_id);
            //拿采购单
            $purchase_order = PurchaseOrder::find($purchase_order_goods_info -> order_id);

            //更新此次

            //计算仓库明细成本

            //从采购单开始计算
            //拿当前货物的单价
            $unit_price = floatval($purchase_order_goods_info -> price) * floatval($purchase_order -> weight_rate);

            if(!$logistics_info -> transfer_from_id){
                $cost_this = round($unit_price + $logistics_info -> cost,2);
            }else{
                //如果是转运单  则 循环找出总的成本
                $cost_this = $unit_price + $logistics_info -> cost;

                $transfer_from_logistics = $logistics_info;
                do{
                    $transfer_from_id = $transfer_from_logistics -> transfer_from_id;

                    $transfer_from_logistics = LogisticsInfo::find($transfer_from_id);

                    $cost_this += $transfer_from_logistics -> cost;

                }while($transfer_from_logistics -> transfer_from_id);
            }

            $record_id = ReceiveGoodsRecord::insertGetId([
                'goods_id' => $logistics_info -> id,
                'product_id' => $purchase_order_goods_info -> product_id,
                'receive_record_id' => $receive_id,
                'number' => $vo['receive_num'],
                'true_num' => $vo['receive_num'],
                'can_buy_num' => $vo['receive_num'],
                'business_id' => $request -> business_id,
                'store_house_id' => $request -> storehouse_id,
                'warehouse_id' => $storehouse_info -> warehouse_id,
                'created_at' => time(),
                'updated_at' => time(),
                'cost' => $cost_this
            ]);

        }
        if($receive_id){


            /*
            //修改采购物流单的状态
            Logistics::where('id',$request -> id) -> update([
                'status' => 2,
                'updated_at' => time()
            ]);
            */
            DB::commit();
            return $receive_id;
        }else{
            DB::rollBack();
            return false;
        }






    }

    //仓库收货
    static function addRecordNoTrans($request,$user_id){
        //先取编号
        $receive_num = NumberQueue::addQueueNo(2);

        $storehouse_info = Storehouse::find($request -> storehouse_id);

        $logistics = Logistics::find($request -> id);

        //生成入库单
        $receive_id = ReceiveRecord::insertGetId([
            'receive_num' => $receive_num,
            'store_house_id' => $request -> storehouse_id,
            'warehouse_id' => $storehouse_info -> warehouse_id,
            'business_id' => $request -> business_id,
            'extras' => $request -> extras,
            'logistics_id' => $request -> id,
            'remark' => $request -> remark,
            'order_id' => $logistics -> order_id,
            'created_at' => time(),
            'updated_at' => time(),
            'operator_user_id' => $user_id
        ]);
        //判断运费类型
        //货物总重量
        $weight_all = 0;
        $weight_record = [];
        $unit_price_record = [];

        $volume_all = 0;
        $info_arr = json_decode($request -> receive_num_json,true);

        //生成入库单详情
        foreach($info_arr as $vo){
            //通过查 logistics_info 得到goods_id
            //[{"info_id":"2","receive_num":"34"},{}]
            $logistics_info = LogisticsInfo::find($vo['info_id']);
            //采购单详情
            $purchase_order_goods_info = PurchaseOrderGoods::find($logistics_info -> goods_id);
            //拿采购单
            $purchase_order = PurchaseOrder::find($purchase_order_goods_info -> order_id);

            //更新此次

            //计算仓库明细成本

            //从采购单开始计算
            //拿当前货物的单价
            $unit_price = floatval($purchase_order_goods_info -> price) * floatval($purchase_order -> weight_rate);

            if(!$logistics_info -> transfer_from_id){
                $cost_this = round($unit_price + $logistics_info -> cost,2);
            }else{
                //如果是转运单  则 循环找出总的成本
                $cost_this = $unit_price + $logistics_info -> cost;

                $transfer_from_logistics = $logistics_info;
                do{
                    $transfer_from_id = $transfer_from_logistics -> transfer_from_id;

                    $transfer_from_logistics = LogisticsInfo::find($transfer_from_id);

                    $cost_this += $transfer_from_logistics -> cost;

                }while($transfer_from_logistics -> transfer_from_id);
            }

            $record_id = ReceiveGoodsRecord::insertGetId([
                'goods_id' => $logistics_info -> id,
                'product_id' => $purchase_order_goods_info -> product_id,
                'receive_record_id' => $receive_id,
                'number' => $vo['receive_num'],
                'true_num' => $vo['receive_num'],
                'can_buy_num' => $vo['receive_num'],
                'business_id' => $request -> business_id,
                'store_house_id' => $request -> storehouse_id,
                'warehouse_id' => $storehouse_info -> warehouse_id,
                'created_at' => time(),
                'updated_at' => time(),
                'cost' => $cost_this
            ]);

        }
        if($receive_id){


            /*
            //修改采购物流单的状态
            Logistics::where('id',$request -> id) -> update([
                'status' => 2,
                'updated_at' => time()
            ]);
            */
            return $receive_id;
        }else{
            return false;
        }






    }
}
