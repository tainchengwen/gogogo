<?php

namespace App\Http\Controllers\Api;

use App\Configure;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ReceiveGoodsController extends Controller
{
    //仓库明细（成本）
    public function stockPriceInfo(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }

        $list = DB::table('erp_receive_goods_record')
            -> leftJoin('erp_receive_record','erp_receive_goods_record.receive_record_id','erp_receive_record.id')
            -> leftJoin('erp_storehouse','erp_receive_record.store_house_id','erp_storehouse.id')
            -> leftJoin('erp_warehouse','erp_storehouse.warehouse_id','erp_warehouse.id')
            -> leftJoin('erp_logistics_info','erp_receive_goods_record.goods_id','erp_logistics_info.id')
            -> leftJoin('erp_purchase_order_goods','erp_logistics_info.goods_id','erp_purchase_order_goods.id')
            -> leftJoin('erp_purchase_order','erp_purchase_order_goods.order_id','erp_purchase_order.id')
            -> leftJoin('erp_product_list','erp_purchase_order_goods.product_id','erp_product_list.id')
            //商品去找类别、品牌、系列
            //类别
            -> leftJoin('erp_product_class as product_class','erp_product_list.class_id','product_class.id')
            //品牌
            -> leftJoin('erp_product_class as product_brand','erp_product_list.brand_id','product_brand.id')
            //系列
            -> leftJoin('erp_product_class as product_series','erp_product_list.series_id','product_series.id')

            -> where([
                'erp_receive_goods_record.business_id' => $request -> business_id
            ])
            -> where(function($query)use($request){
                //入库编号
                if($request -> receive_num){
                    $query -> where('erp_receive_record.receive_num','like','%'.trim($request -> receive_num).'%');
                }
                //仓库
                if($request -> warehouse_id){
                    $query -> where('erp_storehouse.warehouse_id',$request -> warehouse_id);
                }
                //库位
                if($request -> storehouse_id){
                    $query -> where('erp_receive_record.store_house_id',$request -> storehouse_id);
                }

                //入库日期
                if($request -> receive_date_left){
                    $query -> where('erp_receive_goods_record.created_at','>=',strtotime($request -> receive_date_left));
                }
                if($request -> receive_date_right){
                    $query -> where('erp_receive_goods_record.created_at','<=',strtotime($request -> receive_date_right));
                }

                //产品编号
                if($request -> product_no){
                    $query -> where('erp_product_list.product_no','like','%'.trim($request -> product_no).'%');
                }

                //产品类别
                if($request -> class_id){
                    $query -> where('erp_product_list.class_id',$request -> class_id);
                }

                //品牌
                if($request -> brand_id){
                    $query -> where('erp_product_list.brand_id',$request -> brand_id);
                }

                //系列
                if($request -> series_id){
                    $query -> where('erp_product_list.series_id',$request -> series_id);
                }

                //订单编号
                if($request -> order_num){
                    $query -> where('erp_purchase_order.order_num','like','%'.trim($request -> order_num).'%');
                }

            })
            -> where([
                'erp_receive_goods_record.flag' => 0
            ])
            -> select([
                'erp_receive_goods_record.id',
                'erp_warehouse.name as warehouse_name',
                'erp_storehouse.name as storehouse_name',
                'erp_product_list.product_no',
                'product_class.name as product_class_name',
                'product_brand.name as product_brand_name',
                'product_series.name as product_series_name',
                'erp_product_list.product_name',
                'erp_product_list.model',
                'erp_product_list.number as product_number',
                'erp_receive_goods_record.number as ruku_number',
                'erp_receive_goods_record.can_buy_num as current_number',
                'erp_receive_goods_record.created_at',
                'erp_receive_goods_record.cost'
            ])
            -> orderBy('erp_receive_goods_record.id','desc')

            -> paginate(isset($request -> per_page)?$request -> per_page:20);


        return $list;

    }


    //仓库明细（成本） 详情
    public function stockPriceDetail(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
            'id' => 'required|numeric'
        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }

        $info = DB::table('erp_receive_goods_record')
            -> where([
                'id' => $request -> id,
                'business_id' => $request -> business_id,
                'flag' => 0
            ]) -> first();
        if(!$info){
            return [
                'code' => 500,
                'msg' => '没有此条数据'
            ];
        }

        //查此收货记录的
        //采购记录
        //发货记录
        //商品信息

        //循环 去找erp_logistics_info


        $logistics_infos = [];

        $erp_logistics_info_id = $info -> goods_id;

        do{
            $temp_logistics_info = DB::table('erp_logistics_info')
                -> leftJoin('erp_logistics','erp_logistics_info.logistics_id','erp_logistics.id')
                -> leftJoin('erp_purchase_order_goods','erp_logistics_info.goods_id','erp_purchase_order_goods.id')
                -> leftJoin('erp_purchase_order','erp_purchase_order_goods.order_id','erp_purchase_order.id')


                //发货港
                -> leftJoin('erp_port','erp_logistics.start_port_id','erp_port.id')
                //目的港
                -> leftJoin('erp_port as mudi','erp_logistics.destination_port_id','mudi.id')

                //商品信息
                -> leftJoin('erp_product_list','erp_purchase_order_goods.product_id','erp_product_list.id')


                -> select([
                    'erp_product_list.product_long', //长
                    'erp_product_list.product_wide', //宽
                    'erp_product_list.product_height', //高
                    'erp_product_list.physics_weight', //物理重量
                    'erp_product_list.number as num',
                    'erp_logistics.logistics_num', //转运单号
                    'erp_logistics.freight_type', //计费方式
                    'erp_logistics.base', //体积基数
                    'erp_logistics.transportationCost', //运输费用
                    'erp_logistics.incidental', //杂费
                    'erp_logistics_info.cost as unitprice', //运费单价
                    'erp_purchase_order_goods.price as price', //运费单价
                    'erp_logistics_info.transfer_from_id',
                    'erp_port.name as fahuo_port', //发货港
                    'mudi.name as mudi_port', //目的港
                    'erp_purchase_order.order_num', //采购单号
                    'erp_purchase_order.weight_rate as rate', //汇率显示的 是 加权税率
                    'erp_purchase_order.currency', //币种
                ])
                -> where([
                    'erp_logistics_info.id' => $erp_logistics_info_id
                ])
                -> first();

            //币种
            $currency = Configure::getCurrency()[$temp_logistics_info -> currency];
            $temp_logistics_info -> currency = $currency;

            //计费方式
            $freight_type = Configure::freightType()[$temp_logistics_info -> freight_type];
            $temp_logistics_info -> freight_type = $freight_type;

            $logistics_infos[] = $temp_logistics_info;
            $erp_logistics_info_id = $temp_logistics_info -> transfer_from_id;
        }while($erp_logistics_info_id);








        /*

        $logistics_info = DB::table('erp_logistics_info')
            -> leftJoin('erp_logistics','erp_logistics_info.logistics_id','erp_logistics.id')
            -> leftJoin('erp_purchase_order_goods','erp_logistics_info.goods_id','erp_purchase_order_goods.id')
            -> leftJoin('erp_purchase_order','erp_purchase_order_goods.order_id','erp_purchase_order.id')


            //发货港
            -> leftJoin('erp_port','erp_purchase_order.port_id','erp_port.id')
            //目的港
            -> leftJoin('erp_port as mudi','erp_logistics.destination_port_id','mudi.id')

            //商品信息
            -> leftJoin('erp_product_list','erp_purchase_order_goods.product_id','erp_product_list.id')


            -> select([
                'erp_product_list.product_long', //长
                'erp_product_list.product_wide', //宽
                'erp_product_list.product_height', //高
                'erp_product_list.weight', //物理重量
                'erp_logistics.logistics_num', //转运单号
                'erp_logistics.freight_type', //计费方式
                'erp_logistics.base', //体积基数
                'erp_logistics.transportationCost', //运输费用
                'erp_logistics.incidental', //杂费
                'erp_logistics_info.price', //运费单价
                'erp_port.name as fahuo_port', //发货港
                'mudi.name as mudi_port', //目的港
                'erp_purchase_order.order_num', //采购单号
                'erp_purchase_order.rate', //汇率
                'erp_purchase_order.currency', //币种
            ])
            -> where([
                'erp_logistics_info.id' => $info -> goods_id
            ]) -> first();

        //币种
        $currency = Configure::getCurrency()[$logistics_info -> currency];
        $logistics_info -> currency = $currency;

        //计费方式
        $freight_type = Configure::freightType()[$logistics_info -> freight_type];
        $logistics_info -> freight_type = $freight_type;
        */


        return [
            'code' => 200,
            'data' => array_reverse($logistics_infos)
        ];










    }


    //仓库收货记录
    public function receiveList(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }


        $list = DB::table('erp_receive_record')
            -> leftJoin('users','erp_receive_record.operator_user_id','users.id')
            -> leftJoin('erp_storehouse','erp_receive_record.store_house_id','erp_storehouse.id')
            -> leftJoin('erp_warehouse','erp_storehouse.warehouse_id','erp_warehouse.id')
            -> leftJoin('erp_logistics','erp_receive_record.logistics_id','erp_logistics.id')

            -> leftJoin('erp_purchase_order','erp_receive_record.order_id','erp_purchase_order.id')

            -> where([
                'erp_receive_record.business_id' => $request -> business_id
            ])
            -> where(function($query)use($request){
                //入库编号
                if($request -> receive_num){
                    $query -> where('erp_receive_record.receive_num','like','%'.trim($request -> receive_num).'%');
                }

                //入库日期
                if($request -> receive_date_left){
                    $query -> where('erp_receive_record.created_at','>=',strtotime($request -> receive_date_left));
                }
                if($request -> receive_date_right){
                    $query -> where('erp_receive_record.created_at','<=',strtotime($request -> receive_date_right));
                }

                //订单编号
                if($request -> order_num){
                    $query -> where('erp_purchase_order.order_num','like','%'.trim($request -> order_num).'%');
                }

            })

            -> select([
                'erp_receive_record.id',
                'erp_receive_record.remark',
                'erp_receive_record.receive_num',
                'erp_receive_record.created_at',
                'erp_receive_record.extras',
                'erp_warehouse.name as warehouse_name',
                'erp_storehouse.name as storehouse_name',

                'users.name as operator_name'
            ])
            -> where([
                'erp_receive_record.flag' => 0
            ])
            -> orderBy('erp_receive_record.id','desc')
            -> paginate(isset($request -> per_page)?$request -> per_page:20);

        foreach($list as $k => $vo){
            $list[$k] -> created_at = date('Y-m-d H:i',$vo -> created_at);
        }


        return $list;

    }


    //通过仓库收货记录id
    public function receiveInfo(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
            'id' => 'required|numeric'
        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }

        //通过收货编号 那 收货详情
        $info = DB::table('erp_receive_goods_record')
            -> leftJoin('erp_receive_record','erp_receive_goods_record.receive_record_id','erp_receive_record.id')
            -> leftJoin('erp_storehouse','erp_receive_record.store_house_id','erp_storehouse.id')
            -> leftJoin('erp_warehouse','erp_storehouse.warehouse_id','erp_warehouse.id')
            -> leftJoin('erp_logistics_info','erp_receive_goods_record.goods_id','erp_logistics_info.id')
            -> leftJoin('erp_logistics','erp_logistics_info.logistics_id','erp_logistics.id')

            -> leftJoin('erp_purchase_order_goods','erp_logistics_info.goods_id','erp_purchase_order_goods.id')
            -> leftJoin('erp_purchase_order','erp_purchase_order_goods.order_id','erp_purchase_order.id')
            -> leftJoin('erp_product_list','erp_purchase_order_goods.product_id','erp_product_list.id')
            //商品去找类别、品牌、系列
            //类别
            -> leftJoin('erp_product_class as product_class','erp_product_list.class_id','product_class.id')
            //品牌
            -> leftJoin('erp_product_class as product_brand','erp_product_list.brand_id','product_brand.id')
            //系列
            -> leftJoin('erp_product_class as product_series','erp_product_list.series_id','product_series.id')

            -> where([
                'erp_receive_goods_record.receive_record_id' => $request -> id
            ])

            -> select([
                'erp_receive_goods_record.id',
                'erp_receive_record.receive_num',
                'erp_purchase_order.order_num',
                'erp_logistics.logistics_num',
                'erp_warehouse.name as warehouse_name',
                'erp_storehouse.name as storehouse_name',
                'erp_product_list.product_no',
                'product_class.name as product_class_name',
                'product_brand.name as product_brand_name',
                'product_series.name as product_series_name',
                'erp_product_list.product_name',
                'erp_product_list.model',
                'erp_product_list.number as product_number',
                'erp_receive_goods_record.number as ruku_number',
                'erp_receive_goods_record.can_buy_num as current_number',
                'erp_receive_goods_record.created_at',
                'erp_receive_goods_record.cost'
            ]) -> get();


        return [
            'code' => 200,
            'data' => $info
        ];




    }


    //删除仓库收货记录
    public function deleteReceiveRecord(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
            'id' => 'required|numeric'
        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }

        $info = DB::table('erp_receive_record')
            -> where([
                'id' => $request -> id,
                'business_id' => $request -> business_id,
                'flag' => 0
            ]) -> first();
        if(!$info){
            return [
                'code' => 500,
                'msg' => '没有此条数据'
            ];
        }

        DB::beginTransaction();
        try{

            //删除仓库收货记录， 先判断 此条仓库收货记录 有没有被用
            $record_info = DB::table('erp_receive_goods_record')
                -> where([
                    'receive_record_id' => $request -> id,
                    'flag' => 0
                ]) -> get();
            if(count($record_info)){
                foreach($record_info as $vo){
                    //看下 此id 有没有存在于  erp_stock_adjust
                    $erp_stock_adjust = DB::table('erp_stock_adjust')
                        -> where([
                            'receive_goods_record_id' => $vo -> id
                        ]) -> first();
                    if($erp_stock_adjust){
                        return [
                            'code' => 500,
                            'msg' => '已发生库位转移,不允许删除'
                        ];
                    }
                }
            }
            if(!count($record_info)){

                DB::table('erp_receive_record')
                    -> where([
                        'id' => $request -> id,
                    ]) -> update([
                        'flag' => 1
                    ]);
                DB::table('erp_receive_goods_record')
                    -> where([
                        'receive_record_id' => $request -> id,
                        'flag' => 0
                    ]) -> update([
                        'flag' => 1
                    ]);
                DB::commit();
                return [
                    'code' => 200,
                    'msg' => '删除成功'
                ];
            }else{
                //查看下每个仓库收货记录明细的 数量 跟 可售数量 是不是相等
                foreach($record_info as $vo){
                    if($vo -> number != $vo -> can_buy_num){
                        return [
                            'code' => 500,
                            'msg' => '已售出，不允许删除'
                        ];
                    }
                }

                DB::table('erp_receive_record')
                    -> where([
                        'id' => $request -> id,
                    ]) -> update([
                        'flag' => 1
                    ]);

                //看下 这条 仓库收货记录 是不是 库位转移过来的
                $erp_stock_adjust = DB::table('erp_stock_adjust') -> where([
                    'to_receive_record_id' => $request -> id,
                    'flag' => 0
                ]) -> first();
                if($erp_stock_adjust){
                    //如果是库位转移过来的 则 退回可售数量
                    Log::info($erp_stock_adjust -> receive_goods_record_id);
                    $erp_receive_goods_record = DB::table('erp_receive_goods_record')
                        -> where([
                            'id' => $erp_stock_adjust -> receive_goods_record_id
                        ]) -> first();
                    //把这个的数量 退回去
                    DB::table('erp_receive_goods_record') -> where([
                        'id' => $erp_stock_adjust -> receive_goods_record_id
                    ]) -> update([
                        'can_buy_num' => intval($erp_receive_goods_record -> can_buy_num) + intval($erp_stock_adjust -> transfer_num)
                    ]);



                    $stock = DB::table('erp_stock') -> where([
                        'flag' => 0,
                        'business_id' => $request -> business_id,
                        'store_house_id' => $erp_stock_adjust -> from_storehouse_id,
                        'product_id' => $erp_receive_goods_record -> product_id,
                    ]) -> first();
                    if($stock){
                        //退回可售数量
                        DB::table('erp_stock') -> where([
                            'id' => $stock -> id
                        ]) -> update([
                            'can_buy_num' => intval($stock -> can_buy_num) + intval($erp_stock_adjust -> transfer_num),
                        ]);
                    }

                    //他的库存 减少
                    $stock_this = DB::table('erp_stock') -> where([
                        'flag' => 0,
                        'business_id' => $request -> business_id,
                        'store_house_id' => $erp_stock_adjust -> to_storehouse_id,
                        'product_id' => $erp_receive_goods_record -> product_id,
                    ]) -> first();
                    if($stock_this){
                        //退回可售数量
                        DB::table('erp_stock') -> where([
                            'id' => $stock_this -> id
                        ]) -> update([
                            'can_buy_num' => intval($stock_this -> can_buy_num) - intval($erp_stock_adjust -> transfer_num),
                        ]);
                    }


                    DB::table('erp_stock_adjust') -> where([
                        'id' => $erp_stock_adjust -> id
                    ]) -> update([
                        'flag' => 1
                    ]);




                }else{
                    foreach($record_info as $vo){

                        $erp_logistics_info = DB::table('erp_logistics_info')
                            -> where([
                                'id' => $vo -> goods_id
                            ]) -> first();
                        if($erp_logistics_info){

                            DB::table('erp_logistics_info') -> where([
                                'id' => $vo -> goods_id
                            ]) -> update([
                                'receive_num' => $erp_logistics_info -> receive_num - $vo -> number
                            ]);

                            //清除库存

                            $stock = DB::table('erp_stock')
                                -> where([
                                    'business_id' => $vo -> business_id,
                                    'store_house_id' => $vo -> store_house_id,
                                    'product_id' => $vo -> product_id,
                                ]) -> first();

                            if($stock){
                                DB::table('erp_stock')
                                    -> where([
                                        'id' => $stock -> id
                                    ]) -> update([
                                        //减去入库数量
                                        'enter_num' => intval($stock -> enter_num) - intval($vo -> number),
                                        //减去可售数量
                                        'can_buy_num' => intval($stock -> can_buy_num) - intval($vo -> number),
                                        //减去当前数量
                                        'current_num' => intval($stock -> current_num) - intval($vo -> number),
                                    ]);
                            }



                        }

                    }


                    //删除
                    //把 erp_logistics_info 的 receive_num 退回去 然后 判断下 erp_logistics 的状态
                    $logistics_id = $info -> logistics_id;

                    if($logistics_id){
                        //更新此物流单的状态
                        DB::table('erp_logistics')
                            -> where([
                                'id' => $logistics_id
                            ]) -> update([
                                'status' => 1
                            ]);
                    }


                }






                DB::table('erp_receive_goods_record')
                    -> where([
                        'receive_record_id' => $request -> id,
                        'flag' => 0
                    ]) -> update([
                        'flag' => 1
                    ]);



                DB::commit();
                return [
                    'code' => 200,
                    'msg' => '删除成功'
                ];


            }
        }catch (\Exception $exception){
            DB::rollBack();
            return [
                'code' => 500,
                'msg' => $exception ->getTraceAsString()
            ];
        }


    }

}
