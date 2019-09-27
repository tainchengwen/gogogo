<?php

namespace App\Http\Controllers\Api;

use App\NumberQueue;
use App\ReceiveGoodsRecord;
use App\ReceiveRecord;
use App\Repositories\SKURepository;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class StockAdjustController extends Controller
{
    //库存调整列表
    public function theList(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id

        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }

        //所有的入库商品明细
        $list = DB::table('erp_receive_goods_record')
            -> leftJoin('erp_storehouse','erp_receive_goods_record.store_house_id','erp_storehouse.id')
            -> leftJoin('erp_warehouse','erp_storehouse.warehouse_id','erp_warehouse.id')
            -> leftJoin('erp_receive_record','erp_receive_goods_record.receive_record_id','erp_receive_record.id')
            -> leftJoin('erp_logistics_info','erp_receive_goods_record.goods_id','erp_logistics_info.id')
            -> leftJoin('erp_purchase_order_goods','erp_logistics_info.goods_id','erp_purchase_order_goods.id')

            -> leftJoin('erp_product_list','erp_purchase_order_goods.product_id','erp_product_list.id')
            //商品去找类别、品牌、系列
            //类别
            -> leftJoin('erp_product_class as product_class','erp_product_list.class_id','product_class.id')
            //品牌
            -> leftJoin('erp_product_class as product_brand','erp_product_list.brand_id','product_brand.id')
            //系列
            -> leftJoin('erp_product_class as product_series','erp_product_list.series_id','product_series.id')
            -> select([
                'erp_receive_record.receive_num as storage_num',
                'erp_receive_goods_record.number as housednum',
                'erp_receive_goods_record.can_buy_num as num',
                'erp_receive_goods_record.created_at',
                'erp_receive_goods_record.id',
                'erp_warehouse.name as warehouse_name',
                'erp_storehouse.name as storehouse_name',
                'erp_product_list.product_no',
                'erp_product_list.product_name',
                'erp_product_list.model',
                'erp_product_list.number as product_number',
                'product_class.name as product_class_name',
                'product_brand.name as product_brand_name',
                'product_series.name as product_series_name',
            ])

            -> where([
                'erp_receive_goods_record.business_id' => $request -> business_id,
                'erp_receive_goods_record.flag' => 0
            ])
            -> where(function($query)use($request){
                if($request -> storage_num){
                    $query -> where('erp_receive_record.receive_num','like','%'.trim($request -> storage_num).'%');
                }
                if($request -> warehouse_id){
                    $query -> where('erp_storehouse.warehouse_id',$request -> warehouse_id);
                }
                if($request -> storehouse_id){
                    $query -> where('erp_storehouse.id',$request -> storehouse_id);
                }


                if($request -> storage_date_left){
                    $query -> where('erp_receive_goods_record.created_at','>=',strtotime($request -> storage_date_left));
                }
                if($request -> storage_date_right){
                    $query -> where('erp_receive_goods_record.created_at','<=',strtotime($request -> storage_date_right));
                }
                if($request -> product_no){
                    $query -> where('erp_product_list.product_no',$request -> product_no);
                }
                if(isset($request -> storage_number)){
                    if(!$request -> storage_number == 1){
                        $query -> where('erp_receive_goods_record.can_buy_num','>',0);
                    }
                }
            })
            -> orderBy('erp_receive_goods_record.id','desc')
            -> paginate(isset($request -> per_page)?$request -> per_page:20);

        foreach($list as $k => $vo){
            $list[$k] -> create_at = date('Y-m-d H:i',$vo -> created_at);
        }



        return $list;
    }


    //库位转移
    public function stockAdjustInfo(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
            'id' => 'required|numeric'
        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }

        $info = DB::table('erp_receive_goods_record')
            -> leftJoin('erp_storehouse','erp_receive_goods_record.store_house_id','erp_storehouse.id')
            -> leftJoin('erp_warehouse','erp_storehouse.warehouse_id','erp_warehouse.id')
            -> leftJoin('erp_receive_record','erp_receive_goods_record.receive_record_id','erp_receive_record.id')
            -> leftJoin('erp_logistics_info','erp_receive_goods_record.goods_id','erp_logistics_info.id')
            -> leftJoin('erp_purchase_order_goods','erp_logistics_info.goods_id','erp_purchase_order_goods.id')

            -> leftJoin('erp_product_list','erp_purchase_order_goods.product_id','erp_product_list.id')
            //商品去找类别、品牌、系列
            //类别
            -> leftJoin('erp_product_class as product_class','erp_product_list.class_id','product_class.id')
            //品牌
            -> leftJoin('erp_product_class as product_brand','erp_product_list.brand_id','product_brand.id')
            //系列
            -> leftJoin('erp_product_class as product_series','erp_product_list.series_id','product_series.id')
            -> select([
                'erp_receive_record.receive_num as storage_num',
                'erp_receive_goods_record.number as housednum',
                'erp_receive_goods_record.can_buy_num as num',
                'erp_receive_goods_record.created_at',
                'erp_warehouse.name as warehouse_name',
                'erp_storehouse.name as storehouse_name',
                'erp_product_list.product_no',
                'erp_product_list.product_name',
                'erp_product_list.model',
                'erp_product_list.number as product_number',
                'product_class.name as product_class_name',
                'product_brand.name as product_brand_name',
                'product_series.name as product_series_name',
            ])
            -> where([
                'erp_receive_goods_record.business_id' => $request -> business_id,
                'erp_receive_goods_record.id' => $request -> id,
            ])
            -> first();
        if(!$info){
            return [
                'code' => 500,
                'msg' => '无此数据'
            ];
        }


        return [
            'code' => 200,
            'data' => $info
        ];

    }


    //库存转移处理
    public function stockAdjustRes(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
            'id' => 'required|numeric',
            'to_warehouse_id' => 'required|numeric',
            'to_storehouse_id' => 'required|numeric',
            'transfer_num' => 'required|numeric|min:1' //转移数量
        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }

        //从库位 到 库位store_house_id
        $info = DB::table('erp_receive_goods_record')
            -> leftJoin('erp_receive_record','erp_receive_goods_record.receive_record_id','erp_receive_record.id')
            -> leftJoin('erp_logistics','erp_receive_record.logistics_id','erp_logistics.id')
            -> where([
                'erp_receive_goods_record.id' => $request -> id,
                'erp_receive_goods_record.business_id' => $request -> business_id
            ])
            -> select([
                'erp_receive_record.logistics_id',
                'erp_logistics.order_id as logistics_order_id',
                'erp_receive_goods_record.*',
                //'erp_logistics.logistics_num'
            ])
            -> first();
        if(!$info){
            return [
                'code' => 500,
                'msg' => '无此数据'
            ];
        }

        //看这个转移数量 是否在库里有
        if(intval($info -> can_buy_num) < intval($request -> transfer_num)){
            return [
                'code' => '500',
                'msg' => '没有这么多数量'
            ];
        }





        try{
            DB::beginTransaction();
            $adjust_id = DB::table('erp_stock_adjust') -> insertGetId([
                'from_storehouse_id' => $info -> store_house_id,
                'to_storehouse_id' => $request -> to_storehouse_id,
                'receive_goods_record_id' => $info -> id,
                'extras' => $request -> extras,
                'remark' => $request -> remark,
                'transfer_num' => $request -> transfer_num,
                'created_at' => time(),
                'updated_at' => time(),
            ]);

            DB::table('erp_receive_goods_record')
                -> where([
                    'id' => $request -> id,
                ]) -> update([
                    'can_buy_num' => intval($info -> can_buy_num) - intval($request -> transfer_num),
                    //'number' => intval($info -> number) - intval($request -> transfer_num),
                ]);

            //生成 新的入库单
            //生成入库单
            //先取编号
            $receive_num = NumberQueue::addQueueNo(6);
            //生成入库单

            //to_receive_goods_id
            $receive_id = ReceiveRecord::insertGetId([
                'receive_num' => $receive_num,
                'store_house_id' => $request -> to_storehouse_id,
                'warehouse_id' => $request -> to_warehouse_id,
                'business_id' => $request -> business_id,
                'extras' => $request -> extras,
                'logistics_id' => $info -> logistics_id,
                'order_id' => $info -> logistics_order_id,
                'created_at' => time(),
                'updated_at' => time(),
                'receive_type' => $adjust_id
            ]);

            DB::table('erp_stock_adjust') -> where([
                'id' => $adjust_id
            ]) -> update([
                'to_receive_record_id' => $receive_id
            ]);

            ReceiveGoodsRecord::insert([
                'goods_id' => $info -> goods_id,
                'product_id' => $info -> product_id,
                'receive_record_id' => $receive_id,
                'number' => intval($request -> transfer_num),
                'true_num' => intval($request -> transfer_num),
                'can_buy_num' => intval($request -> transfer_num),
                'business_id' => $request -> business_id,
                'store_house_id' => $request -> to_storehouse_id,
                'warehouse_id' => $request -> to_warehouse_id,
                'created_at' => time(),
                'updated_at' => time(),
                'cost' => $info -> cost
            ]);

            //对应库存变化 被转移的可售数量减少
            $from_stock = DB::table('erp_stock') -> where([
                'flag' => 0,
                'business_id' => $request -> business_id,
                'store_house_id' => $info -> store_house_id,
                'product_id' => $info -> product_id,
            ]) -> first();
            if($from_stock){
                DB::table('erp_stock') -> where([
                    'id' => $from_stock -> id
                ]) -> update([
                    //可售数量减少
                    'can_buy_num' => intval($from_stock -> can_buy_num) - intval($request -> transfer_num),
                    //当前数量 减少
                    'current_num' => intval($from_stock -> current_num) - intval($request -> transfer_num),
                    //'enter_num' => intval($from_stock -> enter_num) - intval($request -> transfer_num),
                ]);
            }

            //接受转移的可售数量增加
            $stock = DB::table('erp_stock') -> where([
                'flag' => 0,
                'business_id' => $request -> business_id,
                'store_house_id' => $request -> to_storehouse_id,
                'product_id' => $info -> product_id,
            ]) -> first();
            if(!$stock){
                //如果没有 增加库存
                DB::table('erp_stock') -> insertGetId([
                    'flag' => 0,
                    'business_id' => $request -> business_id,
                    'store_house_id' => $request -> to_storehouse_id,
                    'warehouse_id' => $request -> to_warehouse_id,
                    'can_buy_num' => intval($request -> transfer_num),
                    'current_num' => intval($request -> transfer_num),
                    'enter_num' => intval($request -> transfer_num),
                    'product_id' => $info -> product_id,
                    'created_at' => time(),
                    'updated_at' => time(),
                ]);
            }else{
                DB::table('erp_stock') -> where([
                    'id' => $stock -> id
                ]) -> update([
                    'can_buy_num' => intval($stock -> can_buy_num) + intval($request -> transfer_num),
                    'current_num' => intval($stock -> current_num) + intval($request -> transfer_num),
                    //'enter_num' => intval($stock -> enter_num) + intval($request -> transfer_num),
                ]);
            }

            if($info -> product_id){
                $skuRepository = new SKURepository();
                $skuRepository -> autoPutOnOrOff([$info -> product_id]);
            }



            DB::commit();
            return [
                'code' => 200,
                'msg' => '调整成功'
            ];
        }catch (\Exception $exception){
            DB::rollBack();
            return [
                'code' => 500,
                'msg' => $exception ->getMessage()
            ];
        }

    }



    //库存调整-报废 调账
    public function changeStockNum(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
            'id' => 'required|numeric',
            'adjust_type' => 'required|numeric', //1报废 2调账
            'transfer_num' => 'required|numeric' //转移数量
        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }

        //从库位 到 库位store_house_id
        $info = DB::table('erp_receive_goods_record')
            -> leftJoin('erp_receive_record','erp_receive_goods_record.receive_record_id','erp_receive_record.id')
            -> leftJoin('erp_logistics','erp_receive_record.logistics_id','erp_logistics.id')
            -> where([
                'erp_receive_goods_record.id' => $request -> id,
                'erp_receive_goods_record.business_id' => $request -> business_id
            ])
            -> select([
                'erp_receive_record.logistics_id',
                'erp_logistics.order_id as logistics_order_id',
                'erp_receive_goods_record.*',
                //'erp_logistics.logistics_num'
            ])
            -> first();
        if(!$info){
            return [
                'code' => 500,
                'msg' => '无此数据'
            ];
        }

        //看这个转移数量 是否在库里有 报废
        if($request -> adjust_type == 1 && intval($info -> can_buy_num) < intval($request -> transfer_num)){
            return [
                'code' => '500',
                'msg' => '没有这么多数量'
            ];
        }



        try{
            DB::beginTransaction();
            $adjust_id = DB::table('erp_stock_adjust') -> insertGetId([
                'from_storehouse_id' => $info -> store_house_id,
                'receive_goods_record_id' => $info -> id,
                'remark' => $request -> remark,
                'transfer_num' => $request -> transfer_num,
                'created_at' => time(),
                'updated_at' => time(),
                'adjust_type' => $request -> adjust_type
            ]);

            if($request -> adjust_type == 1){
                //报废
                DB::table('erp_receive_goods_record')
                    -> where([
                        'id' => $request -> id
                    ]) -> update([
                        //可售数量 减去
                        'can_buy_num' => intval($info -> can_buy_num) - intval($request -> transfer_num)
                    ]);

                //这个商品的库存 也相应变化
                $stock_info = DB::table('erp_stock') -> where([
                    'business_id' => $info -> business_id,
                    'product_id' => $info -> product_id,
                    'store_house_id' => $info -> store_house_id,
                    'flag' => 0
                ]) -> first();
                if($stock_info){
                    DB::table('erp_stock') -> where([
                        'id' => $stock_info -> id
                    ]) -> update([
                        'can_buy_num' => intval($stock_info -> can_buy_num) - intval($request -> transfer_num),
                        'current_num' => intval($stock_info -> current_num) - intval($request -> transfer_num),
                    ]);
                }

            }else{
                //调账
                DB::table('erp_receive_goods_record')
                    -> where([
                        'id' => $request -> id
                    ]) -> update([
                        //可售数量 减去
                        'can_buy_num' => intval($info -> can_buy_num) + intval($request -> transfer_num)
                    ]);
                //这个商品的库存 也相应变化
                $stock_info = DB::table('erp_stock') -> where([
                    'business_id' => $info -> business_id,
                    'product_id' => $info -> product_id,
                    'store_house_id' => $info -> store_house_id,
                    'flag' => 0
                ]) -> first();
                if($stock_info){
                    DB::table('erp_stock') -> where([
                        'id' => $stock_info -> id
                    ]) -> update([
                        'can_buy_num' => intval($stock_info -> can_buy_num) + intval($request -> transfer_num),
                        'current_num' => intval($stock_info -> current_num) + intval($request -> transfer_num),
                    ]);
                }

            }

            if($info -> product_id){
                $skuRepository = new SKURepository();
                $skuRepository -> autoPutOnOrOff([$info -> product_id]);
            }




            DB::commit();
            return [
                'code' => 200,
                'msg' => '调整成功'
            ];
        }catch (\Exception $exception){
            DB::rollBack();
            return [
                'code' => 500,
                'msg' => $exception ->getMessage()
            ];
        }






    }

}
