<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Logistics extends Model
{
    //
    protected $table = 'erp_logistics';


    protected $dateFormat = 'U';


    //校验 校验港口 校验数量  创建物流单前的校验
    static function checkGoodsList($request){

        //校验freight_type
        //如果运费类型是体积 则体积基数必填
        if($request -> freight_type == 2 && !$request -> base){
            return [
                'code' => 500,
                'msg' => '体积基数必填'
            ];
        }


        //goods_list
        $goods_list_arr = json_decode($request -> goods_list,true);
        $temp_port_id = 0;
        $temp_goods_id_arr = [];
        if(!count($goods_list_arr)){
            return [
                'code' => 500,
                'msg' => '传递参数格式有误'
            ];
        }
        foreach($goods_list_arr as $k => $vo){
            if(!isset($vo['goods_id']) || !isset($vo['deliver_number'])){
                return [
                    'code' => 500,
                    'msg' => 'json参数必填'
                ];
            }
            if(in_array($vo['goods_id'],$temp_goods_id_arr)){
                return [
                    'code' => 500,
                    'msg' => '第'.($k + 1).'行采购订单数据重复'
                ];
            }else{
                $temp_goods_id_arr[] = $vo['goods_id'];
            }


            $order_goods_info = PurchaseOrderGoods::find($vo['goods_id']);
            if(!$order_goods_info || $order_goods_info -> flag){
                return [
                    'code' => 500,
                    'msg' => '第'.($k + 1).'行采购订单明细数据有误'
                ];
            }
            $order_info = PurchaseOrder::find($order_goods_info -> order_id);
            if(!$order_info || $order_info -> flag){
                return [
                    'code' => 500,
                    'msg' => '第'.($k + 1).'行采购订单数据有误'
                ];
            }

            //校验数量
            if(intval($vo['deliver_number']) > intval($order_goods_info -> number) - intval($order_goods_info -> deliver_number) ){
                return [
                    'code' => 500,
                    'msg' => '第'.($k + 1).'行采购数量有误'
                ];
            }

            //通过运费类型 查 商品重量、体积 有没有维护
            if($request -> freight_type == 1){
                //物理重 判断商品有没有维护物理重
                $goods_info = DB::table('erp_product_list')
                    -> where([
                        'id' => $order_goods_info -> product_id
                    ]) -> first();
                if(!$goods_info -> physics_weight){
                    return [
                        'code' => 500,
                        'msg' => $goods_info -> product_name.',没有维护物理重'
                    ];
                }
                if(!$goods_info -> number){
                    return [
                        'code' => 500,
                        'msg' => $goods_info -> product_name.',没有维护包装数量'
                    ];
                }
            }

            if($request -> freight_type == 2){
                //体积重 判断商品有没有维护长宽高
                $goods_info = DB::table('erp_product_list')
                    -> where([
                        'id' => $order_goods_info -> product_id
                    ]) -> first();
                if(!$goods_info -> product_long || !$goods_info -> product_wide || !$goods_info -> product_height){
                    return [
                        'code' => 500,
                        'msg' => $goods_info -> product_name.',没有维护长宽高'
                    ];
                }
                if(!$goods_info -> number){
                    return [
                        'code' => 500,
                        'msg' => $goods_info -> product_name.',没有维护包装数量'
                    ];
                }
            }






            //校验港口
            if($temp_port_id && $temp_port_id != $order_info -> port_id){
                return [
                    'code' => 500,
                    'msg' => '不是同一港口不能发货'
                ];
            }else{
                $temp_port_id = $order_info -> port_id;
            }




        }
    }


    //创建转运单的 校验
    static function checkTransferInfo($request){

        //校验freight_type
        //如果运费类型是体积 则体积基数必填
        if($request -> freight_type == 2 && !$request -> base){
            return [
                'code' => 500,
                'msg' => '体积基数必填'
            ];
        }



        //goods_list
        $goods_list_arr = json_decode($request -> goods_list,true);
        $temp_port_id = 0;
        $temp_goods_id_arr = [];
        if(!count($goods_list_arr)){
            return [
                'code' => 500,
                'msg' => '传递参数格式有误'
            ];
        }
        foreach($goods_list_arr as $k => $vo){
            if(!isset($vo['erp_logistics_info_id']) || !isset($vo['deliver_number'])){
                return [
                    'code' => 500,
                    'msg' => 'json参数必填'
                ];
            }

            //查看每个物流单的到货港口 是不是一致

            $logistics_info = DB::table('erp_logistics_info') -> where([
                'id' => $vo['erp_logistics_info_id']
            ]) -> first();
            $logistics = DB::table('erp_logistics') -> where([
                'id' => $logistics_info -> logistics_id
            ]) -> first();

            //先检验物流单状态 只有港口一收货状态 允许转运
            if(!$logistics || $logistics -> status != 1){
                return [
                    'code' => 500,
                    'msg' => '只有港口收货状态允许转运'
                ];
            }


            //校验港口
            if($temp_port_id && $temp_port_id != $logistics -> destination_port_id){
                return [
                    'code' => 500,
                    'msg' => '不是同一港口不能发货'
                ];
            }else{
                $temp_port_id = $logistics -> destination_port_id;
            }


            //if(intval($vo['deliver_number']) > intval($logistics_info -> number) - intval($logistics_info -> scrap_num) - intval($logistics_info ->transfer_num )  ){
            if(intval($vo['deliver_number']) > intval($logistics_info -> number) - intval($logistics_info -> scrap_num)   ){
                return [
                    'code' => 500,
                    'msg' => '转运数量不正确'
                ];
            }




            //通过运费类型 查 商品重量、体积 有没有维护
            if($request -> freight_type == 1){
                $purchase_order_goods = DB::table('erp_purchase_order_goods')
                    -> where([
                        'id' => $logistics_info -> goods_id
                    ]) -> first();

                //物理重 判断商品有没有维护重量
                $goods_info = DB::table('erp_product_list')
                    -> where([
                        'id' => $purchase_order_goods -> product_id
                    ]) -> first();
                if(!$goods_info -> physics_weight){
                    return [
                        'code' => 500,
                        'msg' => $goods_info -> product_name.',没有维护物理重量'
                    ];
                }
            }

            if($request -> freight_type == 2){
                $purchase_order_goods = DB::table('erp_purchase_order_goods')
                    -> where([
                        'id' => $logistics_info -> goods_id
                    ]) -> first();
                //体积重 判断商品有没有维护长宽高
                $goods_info = DB::table('erp_product_list')
                    -> where([
                        'id' => $purchase_order_goods -> product_id
                    ]) -> first();
                if(!$goods_info -> product_long || !$goods_info -> product_wide || !$goods_info -> product_height){
                    return [
                        'code' => 500,
                        'msg' => $goods_info -> product_name.',没有维护长宽高'
                    ];
                }
            }


        }
    }


    //创建物流单
    static function addInfo($request){
        //1张物流单号 对应 多条记录
        //添加物流单号
        $goods_list_arr = json_decode($request -> goods_list,true);
        $first_purchase_goods_info = PurchaseOrderGoods::find($goods_list_arr[0]['goods_id']);
        $first_purchase_order_info = PurchaseOrder::find($first_purchase_goods_info -> order_id);

        DB::beginTransaction();
        try{

            //
            $transportationCost = 0;
            if($request -> freight_type == 3){
                //按照个数 计算运费
                foreach($goods_list_arr as $k => $vo){
                    if(!$vo['price']){
                        $goods_list_arr[$k]['price'] = 0;
                    }
                    $transportationCost += floatval($vo['price']) * intval($vo['deliver_number']);
                }
                //$transportationCost += floatval($request -> transportationCost);
            }else{
                $transportationCost = $request -> transportationCost;
            }
            //总物理重
            $weight_all = 0;
            //总体积重
            $volume_weight_all = 0 ;
            //包装数量
            $product_numbers = [];


            //如果运费按照 物理重
            if($request -> freight_type == 1 ){

                foreach($goods_list_arr as $key => $vo){
                    $goods_list_arr[$key]['weight'] = 0;
                    $goods_info = DB::table('erp_purchase_order_goods')
                        -> leftJoin('erp_product_list','erp_purchase_order_goods.product_id','erp_product_list.id')
                        -> select([
                            'erp_product_list.*'
                        ])
                        -> where([
                            'erp_purchase_order_goods.id' => $vo['goods_id'],
                            'erp_purchase_order_goods.flag' => 0
                        ]) -> first();
                    if($goods_info){
                        if(!$goods_info -> number){
                            return [
                                'code' => 500,
                                'msg' => '商品编号'.$goods_info -> product_no.'，没有维护包装数量'
                            ];
                        }
                        $product_numbers[] = $goods_info -> number;
                        $goods_list_arr[$key]['weight'] = floatval($goods_info -> physics_weight)/intval($goods_info -> number);
                        $weight_all += floatval($goods_info -> physics_weight)/intval($goods_info -> number) * intval($vo['deliver_number']);
                    }
                }
            }

            //如果是按照 体积重收费
            if($request -> freight_type == 2){
                //体积基数
                $base = $request -> base;

                foreach($goods_list_arr as $key => $vo){
                    //单个体积重
                    $goods_list_arr[$key]['volume_weight'] = 0;
                    $goods_info = DB::table('erp_purchase_order_goods')
                        -> leftJoin('erp_product_list','erp_purchase_order_goods.product_id','erp_product_list.id')
                        -> select([
                            'erp_product_list.*'
                        ])
                        -> where([
                            'erp_purchase_order_goods.id' => $vo['goods_id'],
                            'erp_purchase_order_goods.flag' => 0
                        ]) -> first();
                    if($goods_info){
                        if(!$goods_info -> number){
                            return [
                                'code' => 500,
                                'msg' => '商品编号'.$goods_info -> product_no.'，没有维护包装数量'
                            ];
                        }
                        $product_numbers[] = $goods_info -> number;

                        //长*宽*高/体积基数=体积重量
                        $volume_weight_all += floatval($goods_info -> product_long) * floatval($goods_info -> product_wide) * floatval($goods_info -> product_height)/intval($goods_info -> number)/ floatval($base) * intval($vo['deliver_number']);
                        $goods_list_arr[$key]['volume_weight'] = floatval($goods_info -> product_long) * floatval($goods_info -> product_wide) * floatval($goods_info -> product_height) /intval($goods_info -> number)/ floatval($base);
                    }
                }


            }

            $log_id = DB::table('erp_logistics') -> insertGetId([
                'order_id' => $request -> order_id,
                'logistics_num' => NumberQueue::addQueueNo(1),
                'true_num' => $request -> true_num,
                'business_id' => $request -> business_id,
                'start_port_id' => $first_purchase_order_info -> port_id, //始发港口id
                'destination_port_id' => $request -> destination_port_id, //目的港口id
                'freight_type' => $request -> freight_type,
                'freight' => $request -> freight,
                'remark' => $request -> remark,
                'currency' => $request -> currency,
                'send_date' => strtotime($request -> send_date),
                'incidental' => $request -> incidental, //杂费
                'transportationCost' => $transportationCost, //运输费用
                'base' => $request -> base,
                'created_at' => time(),
                'updated_at' => time(),
                'pay_status' => $transportationCost?0:2
            ]);
            //计算成本
            //查看 运费类型


            //goods_list
            //purchase_order_goods 的id 得到 所有的order_id 去更新 每个订单的状态
            $purchase_order_goods_ids = [];
            foreach($goods_list_arr as $k => $vo){
                $cost = 0;
                //运费按照 个数 计算的话 更新成本
                if($request -> freight_type == 3){
                    $cost = isset($vo['price']) && $vo['price']?$vo['price']:0;
                }
                //物理重
                if($request -> freight_type == 1){
                    $cost = floatval($vo['weight'])/$weight_all*($transportationCost + floatval($request -> incidental?$request -> incidental:0));
                }
                //体积重
                if($request -> freight_type == 2){
                    $cost = floatval($vo['volume_weight'])/floatval($volume_weight_all)*($transportationCost + floatval($request -> incidental));
                }
                //
                $purchase_order_goods_ids[] = $vo['goods_id'];
                $id_temp = DB::table('erp_logistics_info') -> insertGetId([
                    'goods_id' => $vo['goods_id'],
                    'number' => $vo['deliver_number'],
                    'price' => isset($vo['price']) && $vo['price']?$vo['price']:0,
                    'logistics_id' => $log_id,
                    'created_at' => time(),
                    'updated_at' => time(),
                    'cost' => $cost
                ]);




                //减去
                PurchaseOrderGoods::where('id',$vo['goods_id']) -> increment('deliver_number',$vo['deliver_number']);
            }

            $purchase_orders = PurchaseOrderGoods::whereIn('id',$purchase_order_goods_ids)
                -> select([
                    'order_id'
                ]) -> get();
            $order_ids = [];
            foreach($purchase_orders as $vo){
                $order_ids[] = $vo -> order_id;
            }
            $order_ids = array_unique($order_ids);

            //判断每个订单的状态
            foreach($order_ids as $vo){
                $order_info = PurchaseOrder::find($vo);
                if($order_info){
                    //通过订单 查找订单详情
                    $goods_info = PurchaseOrderGoods::where([
                        'order_id' => $order_info -> id,
                        'flag' => 0
                    ]) -> get();
                    if($goods_info){
                        $is_end = true;
                        foreach($goods_info as $key => $value){
                            //如果有一单， deliver_number已发出数量 ！= number 总数量，则修改 这单状态为发货中，否则改为 发货完成未入库
                            if(intval($value -> number) != intval($value -> deliver_number) ){
                                PurchaseOrder::where('id',$order_info -> id) -> update([
                                    //发货中
                                    'order_status' => 2
                                ]);
                                $is_end = false;
                                break;
                            }
                        }

                        if($is_end){
                            //已发货
                            PurchaseOrder::where('id',$order_info -> id) -> update([
                                'order_status' => 3
                            ]);
                        }




                    }

                }
            }
            DB::commit();
            return $log_id;
        }catch (\Exception $exception){
            \Illuminate\Support\Facades\Log::info($exception ->getTraceAsString());
            DB::rollBack();
            return false;
        }






    }

    //创建转运单
    static function addTransfer($request){
        //1张物流单号 对应 多条记录
        //添加物流单号
        //(新增参数：之前所属物流单id - erp_logistics_info_id) ，deliver_number(发货数量)，price
        $goods_list_arr = json_decode($request -> goods_list,true);
        //找到发货港口
        $first_logistics_info = DB::table('erp_logistics_info')
            -> where([
                'id' => $goods_list_arr[0]['erp_logistics_info_id']
            ]) -> first();
        $first_logistics = DB::table('erp_logistics') -> where([
            'id' => $first_logistics_info -> logistics_id
        ]) -> first();

        DB::beginTransaction();
        try{

            //
            $transportationCost = 0;
            if($request -> freight_type == 3){
                //按照个数 计算运费
                foreach($goods_list_arr as $k => $vo){
                    if(!$vo['price']){
                        $goods_list_arr[$k]['price'] = 0;
                    }
                    $transportationCost += floatval($vo['price']) * intval($vo['deliver_number']);
                }
                //$transportationCost += floatval($request -> transportationCost);
            }else{
                $transportationCost = $request -> transportationCost;
            }
            $weight_all = 0;
            //总体积重
            $volume_weight_all = 0 ;
            //包装数量
            $product_numbers = [];


            //如果运费按照 物理重
            if($request -> freight_type == 1 ){



                foreach($goods_list_arr as $key => $vo){
                    //通过 $vo['erp_logistics_info_id'] 找到 erp_purchase_order_goods id
                    $erp_logistics_info= DB::table('erp_logistics_info') -> where([
                        'id' => $vo['erp_logistics_info_id']
                    ]) -> first();
                    $goods_list_arr[$key]['weight'] = 0;
                    $goods_info = DB::table('erp_purchase_order_goods')
                        -> leftJoin('erp_product_list','erp_purchase_order_goods.product_id','erp_product_list.id')
                        -> select([
                            'erp_product_list.*'
                        ])
                        -> where([
                            'erp_purchase_order_goods.id' => $erp_logistics_info -> goods_id,
                            'erp_purchase_order_goods.flag' => 0
                        ]) -> first();
                    if($goods_info){
                        if(!$goods_info -> number){
                            return [
                                'code' => 500,
                                'msg' => '商品编号'.$goods_info -> product_no.'，没有维护包装数量'
                            ];
                        }
                        $product_numbers[] = $goods_info -> number;
                        $goods_list_arr[$key]['weight'] = floatval($goods_info -> physics_weight)/intval($goods_info -> number);
                        $weight_all += floatval($goods_info -> physics_weight)/ intval($goods_info -> number)* intval($vo['deliver_number']);
                    }
                }
            }

            //如果是按照 体积重收费
            if($request -> freight_type == 2){
                //体积基数
                $base = $request -> base;

                foreach($goods_list_arr as $key => $vo){
                    //通过 $vo['erp_logistics_info_id'] 找到 erp_purchase_order_goods id
                    $erp_logistics_info= DB::table('erp_logistics_info') -> where([
                        'id' => $vo['erp_logistics_info_id']
                    ]) -> first();

                    //单个体积重
                    $goods_list_arr[$key]['volume_weight'] = 0;
                    $goods_info = DB::table('erp_purchase_order_goods')
                        -> leftJoin('erp_product_list','erp_purchase_order_goods.product_id','erp_product_list.id')
                        -> select([
                            'erp_product_list.*'
                        ])
                        -> where([
                            'erp_purchase_order_goods.id' => $erp_logistics_info -> goods_id,
                            'erp_purchase_order_goods.flag' => 0
                        ]) -> first();
                    if($goods_info){
                        if(!$goods_info -> number){
                            return [
                                'code' => 500,
                                'msg' => '商品编号'.$goods_info -> product_no.'，没有维护包装数量'
                            ];
                        }
                        $product_numbers[] = $goods_info -> number;

                        //长*宽*高/体积基数=体积重量
                        $volume_weight_all += floatval($goods_info -> product_long) * floatval($goods_info -> product_wide) * floatval($goods_info -> product_height)/ intval($goods_info -> number) / floatval($base) * intval($vo['deliver_number']);
                        $goods_list_arr[$key]['volume_weight'] = floatval($goods_info -> product_long) * floatval($goods_info -> product_wide) * floatval($goods_info -> product_height) / intval($goods_info -> number) / floatval($base);
                    }
                }


            }




            $log_id = DB::table('erp_logistics') -> insertGetId([
                'order_id' => $request -> order_id,
                'logistics_num' => NumberQueue::addQueueNo(1),
                'true_num' => $request -> true_num,
                'business_id' => $request -> business_id,
                'start_port_id' => $first_logistics -> destination_port_id, //始发港口id
                'destination_port_id' => $request -> destination_port_id, //目的港口id
                'freight_type' => $request -> freight_type,
                'freight' => $request -> freight,
                'remark' => $request -> remark,
                'currency' => $request -> currency,
                'send_date' => strtotime($request -> send_date),
                'incidental' => $request -> incidental, //杂费
                'transportationCost' => $transportationCost, //运输费用
                'base' => $request -> base,
                'created_at' => time(),
                'updated_at' => time(),
                'is_transfer' => 1,
                'pay_status' => $transportationCost?0:2
            ]);

            //goods_list
            //purchase_order_goods 的id 得到 所有的order_id 去更新 每个订单的状态
            $purchase_order_goods_ids = [];

            foreach($goods_list_arr as $k => $vo){

                $logistics_info = DB::table('erp_logistics_info') -> where([
                    'id' => $vo['erp_logistics_info_id']
                ]) -> first();

                /*
                $logistics = DB::table('erp_logistics') -> where([
                    'id' => $logistics_info -> logistics_id
                ]) -> first();
                */



                $cost = 0;
                //运费按照 个数 计算的话 更新成本
                if($request -> freight_type == 3){
                    $cost = isset($vo['price']) && $vo['price']?$vo['price']:0;
                }
                //物理重
                if($request -> freight_type == 1){
                    $cost = floatval($vo['weight'])/$weight_all*($transportationCost + floatval($request -> incidental));
                }
                //体积重
                if($request -> freight_type == 2){
                    $cost = floatval($vo['volume_weight'])/floatval($volume_weight_all)*($transportationCost + floatval($request -> incidental));
                }




                DB::table('erp_logistics_info') -> insertGetId([
                    'goods_id' => $logistics_info -> goods_id,
                    'number' => $vo['deliver_number'],
                    'price' => isset($vo['price']) && $vo['price']?$vo['price']:0,
                    'logistics_id' => $log_id,
                    'created_at' => time(),
                    'updated_at' => time(),
                    'transfer_from_id' => $vo['erp_logistics_info_id'],
                    'cost' => $cost
                ]);



                //减少原来物流单 的转移数量
                    DB::table('erp_logistics_info') -> where([
                    'id' => $vo['erp_logistics_info_id']
                ]) -> update([
                    'number' => intval($logistics_info -> number) - intval($vo['deliver_number']),
                    'transfer_num' => intval($logistics_info -> transfer_num) + intval($vo['deliver_number'])
                ]);

                self::checkLogistics($logistics_info -> logistics_id);

            }


            DB::commit();
            return $log_id;
        }catch (\Exception $exception){
            \Illuminate\Support\Facades\Log::info($exception ->getTraceAsString());
            DB::rollBack();
            return false;
        }






    }



    //判断每个物流单 的 物流详情 来判断是否删除
    static function checkLogistics($logistics_id){
        $logistics_infos = DB::table('erp_logistics_info')
            -> where([
                'logistics_id' => $logistics_id,
                'flag' => 0
            ]) -> get();
        if($logistics_infos){
            //查看是否有数量
            $is_number = 0;
            foreach($logistics_infos as $vo){
                if($vo -> number){
                    $is_number = 1;
                    break;
                }
            }
            if(!$is_number){
                //这个物流单内没有数量 则 这个单子应该作废掉
                DB::table('erp_logistics')
                    -> where([
                        'id' => $logistics_id
                    ]) -> update([
                        'flag' => 1,
                        'is_transfer' => 2
                    ]);

                DB::table('erp_logistics_info')
                    -> where([
                        'logistics_id' => $logistics_id,
                    ]) -> update([
                        'flag' => 1
                    ]);

            }


        }
    }


}
