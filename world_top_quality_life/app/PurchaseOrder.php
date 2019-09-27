<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PurchaseOrder extends Model
{
    //
    protected $table = 'erp_purchase_order';


    protected $dateFormat = 'U';

    //检验每个采购单的状态
    static function checkOrderStatus($order_id){
        //看下此采购单的采购详情
        $erp_purchase_order_goods = DB::table('erp_purchase_order_goods')
            -> where([
                'flag' => 0 ,
                'order_id' => $order_id
            ]) -> get();
        //区分 发货中2  还是  已保存未发货1

        $order_status = 1;
        foreach($erp_purchase_order_goods as $vo){
            if($vo -> deliver_number){
                $order_status = 2;
                break;
            }
        }
        DB::table('erp_purchase_order') -> where([
            'id' => $order_id
        ]) -> update([
            'order_status' => $order_status
        ]);

    }

    //采购给展鹏 供应商 为 该事业部
    //展鹏事业部 添加采购单
    /**
     * @param $arr
     * @param $order_type order_type == 1 展鹏给事业部下采购单。 0 事业部给展鹏下采购单
     * @return mixed
     */
    static function addAgentPurchaseOrder($arr,$order_type=0){
        $business_info = DB::table('erp_business') -> where([
            'id' => $arr['business_id']
        ]) -> first();
        if(!$order_type){
            $this_business_id = config('admin.zhanpeng_business_id');
            //其他事业部$arr['business_id'] 作为供应商 下采购单 给 展鹏
            //此事业部名字的供应商
            $erp_supplier = DB::table('erp_supplier') -> where([
                'name' => $arr['business_id'].$business_info -> name.'-代理事业部',
                'business_id' => $this_business_id,
            ])-> first();
            if($erp_supplier){
                $erp_supplier_id = $erp_supplier -> id;
            }else{
                $erp_supplier_id = DB::table('erp_supplier') -> insertGetId([
                    'name' => $arr['business_id'].$business_info -> name.'-代理事业部',
                    'business_id' => $this_business_id,
                ]);
            }

            $purchase_order_id = self::publicAddOrder($this_business_id,$erp_supplier_id,$arr);

            return $purchase_order_id;
        }else{
            //展鹏事业部 作为供应商 下采购单 给 $arr['business_id']
            //看这个事业部 有没有叫展鹏的供应商
            $erp_supplier = DB::table('erp_supplier')
                -> where('name','like','%展鹏%')
                -> where([
                    'business_id' => $arr['business_id'],
                ])
                -> first();
            if($erp_supplier){
                $erp_supplier_id = $erp_supplier -> id;
            }else{
                $erp_supplier_id = DB::table('erp_supplier') -> insertGetId([
                    'name' => '展鹏',
                    'business_id' => $arr['business_id'],
                ]);
            }
            $this_business_id = $arr['business_id'];

            $purchase_order_id = self::publicAddOrder($this_business_id,$erp_supplier_id,$arr);

            return $purchase_order_id;


        }

    }



    //公共方法 采购单 发货单 收货单
    static function publicAddOrder($this_business_id,$erp_supplier_id,$arr){
        //下采购订单 发货港口为 虚拟1
        $order_num = NumberQueue::addQueueNo();
        $port_info = DB::table('erp_port') -> where([
            'business_id' => $this_business_id,
        ]) -> where('name','like','%虚拟1%') -> first();
        $purchase_order_id = PurchaseOrder::insertGetId([
            'order_num' => $order_num,
            'business_id' => $this_business_id,
            'supplier_id' => $erp_supplier_id,
            'order_date' => time(),
            'purchase_type' => 1,
            'currency' => 1,
            'rate' => 1,
            'port_id' => $port_info?$port_info->id:0,
            'remark' => '',
            'create_user_id' => 1,
            'update_user_id' => 1,
            'purchase_class' => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ]);



        //添加明细
        $temp_ids = [];
        foreach($arr['goods_arr'] as $vo){
            if(!isset($vo['id']) || !isset($vo['price']) || !isset($vo['number']) ){
                return [
                    'code' => 500,
                    'msg' => 'json参数错误'
                ];
            }
            if(in_array($vo['id'],$temp_ids)){
                return [
                    'code' => 500,
                    'msg' => 'json中id重复'
                ];
            }else{
                $temp_ids[] = $vo['id'];
            }


            //检验id是否存在
            $info = ProductList::find($vo['id']);
            if(!$info){
                return [
                    'code' => 500,
                    'msg' => 'json中id不存在'
                ];
            }
            if(!intval($vo['number'])){
                return [
                    'code' => 500,
                    'msg' => 'json中number有误'
                ];
            }
        }

        $sum_price = 0;
        $temp_auto_send = [];
        foreach($arr['goods_arr'] as $k => $vo){
            $sum_price += floatval($vo['price']) * intval($vo['number']);
            $purchase_order_goods_id = PurchaseOrderGoods::insertGetId([
                'created_at' => time(),
                'updated_at' => time(),
                'order_id' => $purchase_order_id,
                'product_id' => $vo['id'],
                'price' => $vo['price'],
                'number' => $vo['number'],
            ]);

            $temp_auto_send[$k]['goods_id'] = $purchase_order_goods_id;
            $temp_auto_send[$k]['deliver_number'] = $vo['number'];
            $temp_auto_send[$k]['price'] = 0;
        }




        //将此条order 状态改为 已保存为发货
        //计算总价格

        PurchaseOrder::where([
            'id' => $purchase_order_id,
        ]) -> update([
            'order_status' => 1,
            'price' => $sum_price, //采购总金额
            'updated_at' => time(),
            'update_user_id' => 0,
        ]);

        //创建物流单
        //找一下目的港口id
        $port_info = DB::table('erp_port') -> where([
            'business_id' => $this_business_id,
        ]) -> where('name','like','%虚拟2%') -> first();
        //自动发货
        $logistics_id = Logistics::addInfo((object)[
            'order_id' => $purchase_order_id,
            'goods_list' => json_encode($temp_auto_send),
            'transportationCost' => 0,
            'freight_type' => 3,
            'true_num' => '',
            'business_id' => $this_business_id,
            'destination_port_id' => $port_info?$port_info->id:0, //目的港 id
            'freight' => 0,
            'remark' => '代理版事业部',
            'currency' => 1,
            'send_date' => date('Y-m-d H:i'),
            'incidental' => 0,
            'base' => 0,
        ]);


        //自动港口收货
        Logistics::where([
            'id' => $logistics_id
        ]) -> update([
            'status' => 1,
            'receive_date' => time(),
            'updated_at' => time()
        ]);

        //自动仓库收货
        //生成入库单
        //先取编号
        $store_house_info = DB::table('erp_storehouse') -> where([
            'business_id' => $this_business_id
        ])-> where('name','like','%虚拟2%') -> first();
        if(!$store_house_info){
            $store_house_info = DB::table('erp_storehouse') -> where([
                'business_id' => $this_business_id
            ])-> first();
        }
        //dd($store_house_info);
        $receive_num = NumberQueue::addQueueNo(2);



        //[{"info_id":"2","receive_num":"34"},{}]
        $temp = [];

        $erp_logistics_info = DB::table('erp_logistics_info') -> where([
            'logistics_id' => $logistics_id
        ]) -> get();
        foreach($erp_logistics_info as $k => $vo){
            $temp[] = [
                'info_id' => $vo->id, //info_id 是物流单
                'receive_num' => $vo -> number
            ];
        }

        $receive_id = ReceiveRecord::addRecord((object)[
            'id' => $logistics_id,
            'storehouse_id' => $store_house_info -> id,
            'business_id' => $this_business_id,
            'extras' => 0,
            'remark' => '',
            'receive_num_json' => json_encode($temp)
        ],0);

        //添加库存





        //[{"info_id":"2","receive_num":"34"},{}]
        if($store_house_info){
            Stock::addStock((object)[
                'business_id' => $this_business_id,
                'storehouse_id' => $store_house_info -> id,
                'receive_num_json' => json_encode($temp),
            ],$receive_id);
        }






        //港口收货详情 港口收货成本。。。。

        return $purchase_order_id;
    }



}
