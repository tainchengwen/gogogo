<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\StockOrder;
use App\NumberQueue;
use App\PurchaseOrder;
use App\PurchaseOrderGoods;
use App\Logistics;
use App\Supplier;
use App\LogisticsInfo;
use App\ReceiveRecord;
use Illuminate\Http\JsonResponse;
use App\Repositories\SKURepository;
use App\Repositories\MathRepository;

class PublicStockOrder extends Model
{
    protected $table = '';

    protected $dateFormat = 'U';


    //公有商品支付成功后调用
    //$order_id erp_stock_order id
    public static function publicOrderAfterPay($orderInfo){
        $orderGoods = DB::table('erp_public_order')
            ->leftJoin("erp_stock_order_info","erp_stock_order_info.id","erp_public_order.order_info_id")
            ->leftJoin("erp_sku_review",function($q){
                $q->on("erp_sku_review.sku_id","erp_stock_order_info.product_id")
                    ->on("erp_sku_review.business_id","erp_public_order.business_id");
            })
            ->leftJoin("erp_storehouse","erp_storehouse.id","erp_sku_review.to_store_house_id")
            ->leftJoin("erp_warehouse","erp_warehouse.id","erp_storehouse.warehouse_id")
            ->leftJoin("erp_business","erp_business.id",'erp_public_order.business_id')
            ->leftJoin("wxuser","erp_business.master_id",'wxuser.id')
            ->where("erp_stock_order_info.stock_order_id",$orderInfo->id)
            ->select([
                'erp_public_order.business_id as order_business_id',
                'erp_warehouse.business_id as now_business_id',
                'erp_warehouse.id as warehouse_id',
                'erp_storehouse.id as storehouse_id',
                'erp_sku_review.business_id as from_business_id',
                'erp_public_order.num',
                'wxuser.market_class',
                'erp_public_order.type',
                'erp_stock_order_info.id as order_info_id',
                'erp_stock_order_info.stock_order_id',
                'erp_stock_order_info.product_id',
                'erp_stock_order_info.special_id',
                'erp_stock_order_info.union_id',
                'erp_stock_order_info.union_number',
                'erp_sku_review.to_store_house_id as storehouse_id',
                'erp_sku_review.price as cost',
            ])
            ->get();
        if(!count($orderGoods)) return;

        //将需要同一个供应商 或平台的商品合并
        $market_class = config('admin.market_class');
        $purchaseTotal  = 0;
        $purchaseOrderGoods = [];
        $stockOrderGoods = [];
        $time = time();
        $order_business_id = 0;
        foreach ($orderGoods as $k=>$v){
            //如果供应商是代理商本人 则return；
            if($v->order_business_id == $v->from_business_id){
                unset($orderGoods[$k]);
                continue;
            }
            $price = DB::table("erp_product_price")->where('product_id',$v->product_id)->where('flag',0)->first();
            $level = 'price_'.$market_class[$v->market_class];
            $order_business_id =  $v->order_business_id;
            $stock_order_id   = $v->stock_order_id;
            $v->goods_price =  $price->$level;
            $purchaseTotal = bcadd($purchaseTotal,bcmul( $price->$level,$v->num,2),2);
        }
        if(!count($orderGoods))return;

        ////////////////////////////////代理向平台的 采购 及销售单操作/////////////////////////////////////////////////////////////////////////
        $order_id = NumberQueue::addQueueNo();
//        $aggentPort = DB::table('erp_port')->where(['business_id'=>$order_business_id,'flag'=>0])->first();
        $aggentPort = DB::table('erp_port') -> where([
            'business_id' => $order_business_id,
        ]) -> where('name','like','%虚拟1%') -> first();
        //向平台下采购单
        $agentPurchaseId = PurchaseOrder::insertGetId([
            'order_num' => $order_id,
            'business_id' => $order_business_id,
            'supplier_id' => 0,
            'order_date' => strtotime(date('Y-m-d',$time)),
            'purchase_type' => 1,
            'currency' => 1 ,
            'rate' => '1.000000',
            'port_id' => $aggentPort ->id,
            'remark' => '自动采购单',
            'create_user_id' => 3, //默认admin
            'update_user_id' => 3,
            'purchase_class' => 1,
            'created_at' => $time,
            'updated_at' => $time,
            'price'      => $purchaseTotal,
            'order_status' => 1
        ]);
        //记录关系
        DB::table("erp_public_stock_purchase_relation")->insert([
            'main_stock_id'=> $stock_order_id,
            'order_id'    => $agentPurchaseId,
            'type'       =>1
        ]);
        //同时平台向代理下销售单
        //判断平台是否有库存

        //平台向代理下销售单
        $agentOrder = StockOrder::where('id',$stock_order_id)
            ->select([
                'sale_date',
                'sale_user_id as user_id',
                'freight','province','city','area','tel','phone','name','address',
                'send_name','send_tel','send_phone','send_province','send_city','send_area','send_address',
                'insert_type','idNumber','imageFront','imageBack',
                'ident_id','market_freight_id','origin_freight','mp_id','created_at','updated_at','operator_user_id'
            ])
            ->first();
        $agentOrder->business_id = 0;
        $sasWare = DB::table('erp_warehouse')->where('business_id',0)->first();
        $agentOrder->warehouse_id = $sasWare->id;
        $agentOrder->price   = $purchaseTotal;
        $agentOrder->order_num = NumberQueue::addQueueNo(3);
        $sasStockOrderId = StockOrder::insertGetId($agentOrder->toArray());
        DB::table("erp_public_stock_purchase_relation")->insert([
            'main_stock_id'=> $stock_order_id,
            'order_id'    => $sasStockOrderId,
            'type'       =>0
        ]);
        foreach ($orderGoods as $v){
            $purchaseOrderGoods[] =[
                'created_at' => $time,
                'updated_at' => $time,
                'order_id' => $agentPurchaseId,
                'product_id' => $v->product_id,
                'price' => $v->goods_price,
                'number' => $v->num,
            ];
            $temp_stock_order_Info_id = StockOrderInfo::insertGetId([
                'stock_order_id' => $sasStockOrderId,
                'product_id'     => $v->product_id,
                'number'         => $v->num,
                'price'          => $v->goods_price,
                'created_at'     => $time,
                'updated_at'     => $time,
                'special_id'     => $v->special_id,
                'union_id'       => $v->union_id,
                'union_number'   => $v->union_number,
            ]);
            if($v->now_business_id == 0){
                $receive_info = ReceiveGoodsRecord::where([
                    'product_id'   => $v->product_id,
                    'warehouse_id' => $v->warehouse_id,
                    'business_id'  => $v->now_business_id,
                    'flag'         => 0
                ]) -> where('can_buy_num','>',0) -> first();
                if(!$receive_info){
                    return [
                        'code' => 1,
                        'msg' => "sku{$v->product_id}尚未入库",
                    ];
                }
                //记录receive_goods_record 与 stock_order_info 的关系
                DB::table('erp_stock_order_info_receive') -> insert([
                    'stock_order_info_id'     => $temp_stock_order_Info_id,
                    'receive_goods_record_id' => $receive_info -> id,
                    'number'                  => $v->num,
                    'created_at'              => $time,
                    'updated_at'              => $time,
                ]);


            }

        }
        PurchaseOrderGoods::insert($purchaseOrderGoods);

        //销售订单管理库存订单
        DB::table('erp_stock_order')->where('id',$sasStockOrderId)->update([
            'relate_purchase_order' => $agentPurchaseId
        ]);


        //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        /// ////////////////////////////////平台向供应商的采购 及销售单操作////////////////////////////////////////////////////////
        $supGroupGoods = [];
        foreach ($orderGoods as $ko=>$good){
            $ordertype = $good->type;
            //说明在平台公有仓库内
            if($good->now_business_id == 0){
                unset($orderGoods[$ko]);
                continue;
            }
            if(isset($supGroupGoods[$good->now_business_id]) && !empty($supGroupGoods[$good->now_business_id])){
                $supGroupGoods[$good->now_business_id][] = (array)$good;
            }else{
                $supGroupGoods[$good->now_business_id] = [(array)$good];
            }
        }
        if(!count($orderGoods))return;
        //如果商品不在平台仓库，向供应商采购
        //平台港口
        $sasPort = DB::table('erp_port') -> where([
            'business_id' => 0,
        ]) -> where('name','like','%虚拟1%') -> first();
        foreach ($supGroupGoods  as  $ks => $sup){
            $supPurchasePrice = 0;
            $supPurchaseGoods = [];
            $supStockOrderGoods =[];
            foreach ($sup as $supG){
                $supPurchasePrice = bcadd($supPurchasePrice,bcmul($supG['cost'],$supG['num'],2),2);
                $sup_warehouse_id = $supG['warehouse_id'];
            }
            $supBusiness  = DB::table('erp_business')->where('id',$ks)->first();
            $sas_order_id = NumberQueue::addQueueNo();
            $sasPurchaseId = PurchaseOrder::insertGetId([
                'order_num' => $sas_order_id,
                'business_id' => 0,
                'supplier_id' => $ks,
                'order_date' => strtotime(date('Y-m-d',$time)),
                'purchase_type' => 1,
                'currency' => $supBusiness-> currency,
                'rate' => '1.000000',
                'port_id' => $sasPort ->id,
                'remark' => '自动采购单',
                'create_user_id' => 3, //默认admin
                'update_user_id' => 3,
                'purchase_class' => 1,
                'created_at' => $time,
                'updated_at' => $time,
                'price'      => $supPurchasePrice,
                'order_status' => 1
            ]);
            DB::table("erp_public_stock_purchase_relation")->insert([
                'main_stock_id'=> $stock_order_id,
                'order_id'    => $sasPurchaseId,
                'type'       =>1
            ]);
            //看用户是否需要拆单
            $agentOrder->price = $supPurchasePrice;
            $agentOrder->order_num = NumberQueue::addQueueNo(3);
            $agentOrder->warehouse_id = $sup_warehouse_id;
            $agentOrder->business_id = $ks;
            $agentOrder->user_id = 0;
            if($ordertype == 1){//需要并单
                //封装订单信息
                $agentOrder->province = $agentOrder->send_province;
                $agentOrder->city = $agentOrder->send_city;
                $agentOrder->area = $agentOrder->send_area;
                $agentOrder->tel = '17195134748';
                $agentOrder->phone = '17195134748';
                $agentOrder->name = $agentOrder->send_name;
                $agentOrder->address = $agentOrder->send_address;
            }else{ //不需要并单  直接发货

            }
            $supStockOrderId = StockOrder::insertGetId($agentOrder->toArray());
            DB::table("erp_public_stock_purchase_relation")->insert([
                'main_stock_id'=> $stock_order_id,
                'order_id'    => $supStockOrderId,
                'type'       =>0
            ]);
            foreach ($sup as $supG2){
                $supPurchaseGoods[] = [
                    'created_at' => $time,
                    'updated_at' => $time,
                    'order_id' => $sasPurchaseId,
                    'product_id' => $supG2['product_id'],
                    'price' => $supG2['cost'],
                    'number' => $supG2['num'],
                ];
                $temp_stock_order_Info_id = StockOrderInfo::insertGetId([
                    'stock_order_id' => $supStockOrderId,
                    'product_id'     => $supG2['product_id'],
                    'number'         => $supG2['num'],
                    'price'          => $supG2['cost'],
                    'created_at'     => $time,
                    'updated_at'     => $time,
                    'special_id'     => $supG2['special_id'],
                    'union_id'       => $supG2['union_id'],
                    'union_number'   => $supG2['union_number'],
                ]);
                $receive_info = ReceiveGoodsRecord::where([
                    'product_id'   => $supG2['product_id'],
                    'warehouse_id' =>$supG2['warehouse_id'],
                    'business_id'  => $supG2['now_business_id'],
                    'flag'         => 0
                ]) -> where('can_buy_num','>',0) -> first();
                if(!$receive_info){
                    return [
                        'code' => 1,
                        'msg' => "sku{$supG2['product_id']}尚未入库",
                    ];
                }
                //记录receive_goods_record 与 stock_order_info 的关系
                DB::table('erp_stock_order_info_receive') -> insert([
                    'stock_order_info_id'     => $temp_stock_order_Info_id,
                    'receive_goods_record_id' => $receive_info -> id,
                    'number'                  => $supG2['num'],
                    'created_at'              => $time,
                    'updated_at'              => $time,
                ]);
            }
            PurchaseOrderGoods::insert($supPurchaseGoods);
            //销售订单管理库存订单
            DB::table('erp_stock_order')->where('id',$supStockOrderId)->update([
                'relate_purchase_order' => $sasPurchaseId
            ]);
        }
        ////////////////////////////////////////////////////////////////////////////////////

        return ['code'=>0];
    }



    //取消订单回滚
    public static function publicOrderCancel($stockOrderId){
        $relation = DB::table('erp_public_stock_purchase_relation')->where('main_stock_id',$stockOrderId)->get();
        if(count($relation)){
            foreach ($relation as $v){
                if($v->type == 1){
                    PurchaseOrder::where('id',$v->order_id)->update(['flag'=>1]);
                }else{
                    StockOrder::where('id',$v->order_id)->update([
                        'flag' => 1,
                        'updated_at' => time()
                    ]);
                    $stockOrderGoodsId = StockOrderInfo::where('stock_order_id',$stockOrderId)->pluck('id');
                    DB::table('erp_stock_order_info_receive')
                        ->whereIn('stock_order_info_id',$stockOrderGoodsId)
                        -> where('flag',0) -> update([
                            'flag' => 1
                        ]);
                }

            }


        }
    }



    //公有商品发货时调用
    public static function publicOrderSend($orderInfo){
        //处理采购单
        $purchaseId = $orderInfo->relate_purchase_order;
        $purchaseInfo = PurchaseOrder::where('id',$purchaseId)->first();

        $purchaseGoods = PurchaseOrderGoods::where([
            'order_id' => $purchaseId,
            'flag'     => 0
        ])->select([
            'price','id as goods_id','number as deliver_number'
        ])->get();

        //创建物流单 并收货
        $logisticsInfo = collect();
        $logisticsInfo->freight_type = 3;//默认个数
        $logisticsInfo->goods_list = json_encode($purchaseGoods);
        $logisticsInfo->destination_port_id  = $purchaseInfo->port_id;
        $logisticsInfo->send_date  =date('Y-m-d',time());
        $logisticsInfo->transportationCost  =0;

        if($purchaseInfo->supplier_id == 0){
            $logisticsInfo->currency = 1;
        }else{
            $businessInfo = DB::table('erp_business')->where('id',$purchaseInfo->supplier_id)->first();
            $logisticsInfo->currency = $businessInfo->currency;
        }
        $logistics_id = Logistics::addInfo($logisticsInfo);
        Logistics::where('id',$logistics_id)->update([
            'status' => 2,
            'receive_date'=>date('Y-m-d',time()),
            'updated_at' =>time()
        ]);
        //生成入库单
        $storehouseInfo = DB::table('erp_storehouse')->where(['business_id'=>$purchaseInfo->business_id,'flag'=>0])->first();
        $receiveInfo = collect();
        $receiveInfo->storehouse_id = $storehouseInfo->id;
        $receiveInfo->warehouse_id = $storehouseInfo->warehouse_id;
        $receiveInfo->business_id = $purchaseInfo->business_id;
        $receiveInfo->extras = 0;
        $receiveInfo->id = $logistics_id;
        $receiveInfo->remark = '';
        ReceiveRecord::addRecord($receiveInfo,3);
        foreach ($purchaseGoods as $v){
            PurchaseOrderGoods::where('id',$v->id)->update('receive_num',$v->deliver_number);
        }
        PurchaseOrder::where('id',$purchaseId)->update(['order_status'=>4]);
        //仓库收货

        //////////////////////销售单处理////////////////////////////
        //销售单加钱
        $account = DB::table('erp_account')->where([
            'business_id' => $orderInfo->business_id,
            'currency'    => $logisticsInfo->currency,
            'flag'        => 0
        ])->first();
        if(!$account){
            return [
                'code' => 500,
                'msg'  => '没有设置对应的币种账号'
            ];
        }
        // 组装支付数据
        $order_pay_arr = [[
            'id'             => $orderInfo->id,
            'pay_price'      => $orderInfo->price,
            'service_charge' => 0,
            'cert_num'       => '',
            'remark'         => '',
        ]];
        $stockOrder = new stockOrder();
        $payResult = $stockOrder->payOrder(
            $order_pay_arr,
            1,//后台操作员
            $account->id,//收款账户id
            $orderInfo->business_id,
            7,//记录收款日志 10小程序订单收款, 7erp订单收款
            1 //支付方式 1余额支付
        );
        if($payResult['code'] == 500){
            return [
                'code' => 500,
                'msg'  => $payResult['msg']
            ];
        }
        ///////////////////////采购单扣款/////////////////////////
        $accountPur = DB::table('erp_account')->where([
            'business_id' => $purchaseInfo->business_id,
            'currency'    => $logisticsInfo->currency,
            'flag'        => 0
        ])->first();
        if(!$accountPur){
            return [
                'code' => 500,
                'msg'  => '没有设置对应的币种账号'
            ];
        }
        $purchase_controller=app('App\Http\Controllers\Api\PurchaseOrderListController');

        $rechargeNum = DB::table('erp_account_recharge_record')->where([
            'account_id' =>  $accountPur->id,
            'flag'       =>  0,
        ])->pluck('id');
        if(!count($rechargeNum)){
            return [
                'code' => 500,
                'msg'  => '采购账户余额不足'
            ];
        }
        $request = collect();
        $request->business_id    = $purchaseInfo->business_id;
        $request->account_id     = $accountPur->id;
        $request->pay_amount     = $accountPur->price;
        $request->order_id       = $purchaseInfo->id;
        $request->service_charge = 0;
        $request->pay_record_ids = json_encode($rechargeNum);
        $purchaseResult =self::addPayRecordCopy($request);
        if($purchaseResult['code'] == 500){
            return [
                'code' => 500,
                'msg'  => 'purchase'.$purchaseResult['msg']
            ];
        }

        return ;
    }


    //代理商设置sku数量 库存调整
    public static function publicSkuAutoPurchase(){


    }


    //私有Sku采购库存
    public static function privateSkuPurchaseStock($request){
        $order_id = NumberQueue::addQueueNo();
        $supplier = Supplier::where([
            'business_id'  => $request->business_id,
            'name'         => '默认'
        ])->first();
        if(!$supplier){
            $supplier = collect();
            $supplier -> id = Supplier::insertGetId([
                'business_id' => $request->business_id,
                'name'        => '默认',
                'created_at' => time(),
                'updated_at' => time(),
            ]);
        }
        $time = time();
        $orderDate = date('Y-m-d',time());
        $skus= $request->skuInfo;
        $purchaseTotal =  0;
        foreach ($skus as $v) {
            $purchaseTotal += bcmul($v->number,$v->origin_price,2);
        }
        $port = DB::table('erp_port') -> where([
            'business_id' => $request -> business_id,
        ]) -> where('name','like','%虚拟1%') -> first();

        $purchaseId = PurchaseOrder::insertGetId([
            'order_num'         => $order_id,
            'business_id'       => $request -> business_id,
            'supplier_id'       => $supplier -> id,
            'order_date'        =>  strtotime($orderDate),
            'purchase_type'     => 1,
            'currency'          => 1,
            'rate'              => '1.000000',
            'port_id'           => $port -> id,
            'remark'            => '',
            'create_user_id'    => 3,
            'update_user_id'    => 3,
            'purchase_class'    => 1,
            'created_at'        => $time,
            'updated_at'        => $time,
            'price'             => $purchaseTotal,
            'order_status'      => 4
        ]);
        $purchaseGoods = [];
        foreach ($skus as $v) {
            $purchaseOrderGoodsid = PurchaseOrderGoods::insertGetId([
                'created_at' => $time,
                'updated_at' => $time,
                'order_id' => $purchaseId,
                'product_id' => $v->id,
                'price' => $v->origin_price,
                'number' => $v->number,
                'receive_num' =>$v->number
            ]);
            $purchaseGoods[] = [
                'price'    => $v->origin_price,
                'goods_id' => $purchaseOrderGoodsid,
                'deliver_number' => $v->number
            ];
        }

        //创建物流单 并收货
        $logisticsInfo = collect();
        $logisticsInfo->freight_type = 3;//默认个数
        $logisticsInfo->goods_list = json_encode($purchaseGoods);
        $logisticsInfo->destination_port_id  = $port -> id;
        $logisticsInfo->send_date  =$orderDate;
        $logisticsInfo->transportationCost  =0;
        $logisticsInfo->currency = 1;
        $logisticsInfo->order_id = 0;
        $logisticsInfo->true_num = '';  //物流单号
        $logisticsInfo->freight = 0;
        $logisticsInfo->remark = 'auto';
        $logisticsInfo->incidental = '0';
        $logisticsInfo->base = '0';
        $logisticsInfo->business_id = $request -> business_id;

        $logistics_id = Logistics::addInfo($logisticsInfo);
        Logistics::where('id',$logistics_id)->update([
            'status' => 2,
            'receive_date'=>strtotime($orderDate),
            'updated_at' =>$time,
            'pay_status' => 2
        ]);
        //生成入库单
        $storehouseInfo = DB::table('erp_storehouse')->where(['business_id'=>$request -> business_id,'flag'=>0])->first();
        $receiveInfo = collect();
        $receiveInfo->storehouse_id = $storehouseInfo->id;
        $receiveInfo->warehouse_id = $storehouseInfo->warehouse_id;
        $receiveInfo->business_id = $request -> business_id;
        $receiveInfo->extras = 0;
        $receiveInfo->id = $logistics_id;
        $receiveInfo->remark = '';
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
        $receiveInfo->receive_num_json = json_encode($temp);
        ReceiveRecord::addRecordNoTrans($receiveInfo,3);


        ///////////////////////采购单先充值 在扣款/////////////////////////
        $purchaseInfo = collect();
        $purchaseInfo->business_id = $request -> business_id;
        $purchaseInfo->currency    = 1;
        $purchaseInfo->id    = $purchaseId;
        $purchaseInfo->price    = $purchaseTotal;
        $payData = self::purchasePay($purchaseInfo);
        PurchaseOrder::where('id',$purchaseId)->update([
            'order_status'=>4
        ]);
        if($payData['code'] != 200) return $payData;

        return ['code'=>200];

    }
    ///////////////////////采购单先充值 在扣款/////////////////////////
    public static function purchasePay($purchaseInfo){
        $accountPur = DB::table('erp_account')->where([
            'business_id' => $purchaseInfo->business_id,
            'currency'    => $purchaseInfo->currency,
            'flag'        => 0
        ])->first();
        if(!$accountPur){
            return [
                'code' => 500,
                'msg'  => '没有设置对应的币种账号'
            ];
        }


        $recharge_record_id = DB::table('erp_account_recharge_record') -> insertGetId([
            'account_id' => $accountPur -> id,
            'recharge_num' => $purchaseInfo -> price,
            'service_charge' => 0,
            'rate' => 1,
            'base_currency' => $purchaseInfo -> price,
            'price' => $purchaseInfo -> price,
            'remark' => 'auto',
            'created_at' => time(),
            'updated_at' => time(),
            'create_user_id' => 3
        ]);
        Account::addLog([
            'business_id' => $purchaseInfo->business_id,
            'account_id' => $accountPur -> id,
            'user_id' => 3,
            'log_type' => 1,
            'price' => $purchaseInfo -> price,
            'parameter_id' => $recharge_record_id
        ]);
        //账户余额 + base_currency
        $res = DB::table('erp_account') -> where([
            'id' => $accountPur -> id,
        ]) -> increment('balance',$purchaseInfo -> price);
        if(!$res){
            return [
                'code' => 500,
                'msg'  => '自动充值失败'
            ];
        }
        $rechargeNum = DB::table('erp_account_recharge_record')->where([
            'account_id' =>  $accountPur->id,
            'flag'       =>  0,
        ])->pluck('id');
        if(!count($rechargeNum)){
            return [
                'code' => 500,
                'msg'  => '采购账户余额不足'
            ];
        }
        $request = collect();
        $request->business_id    = $purchaseInfo->business_id;
        $request->account_id     = $accountPur->id;
        $request->pay_amount     = $purchaseInfo->price;
        $request->order_id       = $purchaseInfo->id;
        $request->service_charge = 0;
        $request->pay_record_ids = json_encode($rechargeNum);
        $purchaseResult =self::addPayRecordCopy($request);
        if($purchaseResult['code']!= 200){
            return $purchaseResult;
        }
        return ['code'=>200];
    }

    //新增财务付款
    public static function addPayRecordCopy($request){

        $order_info = PurchaseOrder::where([
            'id' => $request -> order_id,
            'flag' => 0
        ])  ->  first();
        if(!$order_info){
            return [
                'code' => 500,
                'msg' => '没有此单号'
            ];
        }
        $math = new MathRepository();
        // 剩余应付
        $residue = $math->math_sub($order_info->price,$order_info->pay_price);

        if($math->math_comp($residue, $request->pay_amount) < 0){
            return [
                'code' => 500,
                'msg' => '你这付款金额都大于采购但金额了！'
            ];
        }

        $pay_record_ids = json_decode($request -> pay_record_ids,true);
        if(!count($pay_record_ids)){
            return [
                'code' => 500,
                'msg' => 'json参数不能为空'
            ];
        }

        //用来计算 还需要几条记录扣钱
        $pay_amount = floatval($request -> pay_amount) ; //3500


        //已经扣的付款
        $payed_amount = 0;

        //已经扣的本位币
        $payed_amount_base = 0;

        DB::beginTransaction();
        try{

            //记录扣款账号 扣款金额
            $temp_arr = [];
            $record_price = [];
            $rate = [];
            $record_ids = [];
            foreach($pay_record_ids as $vo){

                $record_info = DB::table('erp_account_recharge_record')
                    -> where([
                        'id' => $vo
                    ])  ->  first();

                if($record_info -> price <= 0){
                    continue;
                }


                //1000 3500

                if (floatval($record_info -> price) <= $pay_amount){
                    DB::table('erp_account_recharge_record')
                        -> where([
                            'id' => $vo
                        ]) -> update([
                            'price' => 0
                        ]);

                    //添加扣款记录
                    $temp_arr[] = [
                        'record_id' => $vo,
                        'price' => floatval($record_info -> price),
                    ];
                    //已经扣的钱
                    $payed_amount += floatval($record_info -> price);

                    $payed_amount_base += floatval($record_info -> price) * floatval($record_info -> rate);

                    $pay_amount -= floatval($record_info -> price);
                    $record_price[] = $record_info -> price;
                    $rate[] = $record_info -> rate;
                    $record_ids[] = $vo;

                }else{

                    DB::table('erp_account_recharge_record')
                        -> where([
                            'id' => $vo
                        ]) -> update([
                            'price' => floatval($record_info -> price) - $pay_amount
                        ]);
                    //添加扣款记录
                    $temp_arr[] = [
                        'record_id' => $vo,
                        'price' => $pay_amount
                    ];

                    $payed_amount +=  $pay_amount;

                    $payed_amount_base += floatval($pay_amount) * floatval($record_info -> rate);

                    $record_price[] = $pay_amount;
                    $rate[] = $record_info -> rate;
                    $record_ids[] = $vo;
                    break;
                }
            }


            foreach($record_price as $key => $vo_price){
                if(!$vo_price){
                    continue;
                }
                //添加总扣款记录
                $user = auth('api')->user();
                $pay_id = DB::table('erp_purchase_order_pay')
                    -> insertGetId([
                        'order_id' => $request -> order_id,
                        'account_id' => $request -> account_id,
                        'pay_price' => $vo_price,
                        'pay_amount_base' => $vo_price * $rate[$key],
                        'service_charge' => $request -> service_charge,
                        'created_at' => time(),
                        'updated_at' => time(),
                        'pay_user_id' => $user -> id,
                        'pay_type' => 0, //采购订单付款
                    ]);


                Account::addLog([
                    'business_id' => $request -> business_id,
                    'account_id' => $request -> account_id,
                    'user_id' => $user -> id,
                    'log_type' => 2,
                    'price' => $vo_price * $rate[$key],
                    'parameter_id' => $pay_id
                ]);


                DB::table('erp_purchase_order_pay_detail')
                    -> insertGetId([
                        'account_id' => $request -> account_id,
                        'pay_price' => $vo_price,
                        'recharge_record_id' => $record_ids[$key],
                        'pay_id' => $pay_id,
                        'created_at' => time(),
                        'updated_at' => time(),
                    ]);

            }




            //修改订单的 已支付的余额
            DB::table('erp_purchase_order') -> where([
                'id' => $order_info -> id
            ]) -> increment('pay_price',$payed_amount);

            //更新 加权汇率

            //算下此订单 所有充值记录
            $record_price = DB::table('erp_purchase_order_pay')
                -> where([
                    'order_id' => $request -> order_id,
                    'pay_type' => 0,
                    'flag' => 0
                ]) -> sum('pay_price');
            $record_base = DB::table('erp_purchase_order_pay')
                -> where([
                    'order_id' => $request -> order_id,
                    'pay_type' => 0,
                    'flag' => 0
                ]) -> sum('pay_amount_base');




            if($record_price){
                DB::table('erp_purchase_order') -> where([
                    'id' => $order_info -> id
                ]) -> update([
                    'weight_rate' => round(floatval($record_base)/floatval($record_price),5)
                ]);
            }

            $sum = $math->math_add($order_info -> pay_price, $payed_amount);
            // 比较大小，相等返回0
            if($math->math_comp($sum, $order_info -> price) == 0){
                //标记为支付完成  pay_status = 2

                DB::table('erp_purchase_order') -> where([
                    'id' => $order_info -> id
                ]) -> update([
                    'pay_status' => 2
                ]);


            }else{
                DB::table('erp_purchase_order') -> where([
                    'id' => $order_info -> id
                ]) -> update([
                    'pay_status' => 1 //部分支付
                ]);
            }


            //扣账户余额
            DB::table('erp_account')
                -> where([
                    'id' => $request -> account_id
                ]) -> decrement('balance',$payed_amount);


            DB::commit();
            return [
                'code' => 200,
                'msg' => '付款成功'
            ];
        }catch (\Exception $exception){
            DB::rollBack();
            return [
                'code' => 500,
                'msg' => $exception ->getMessage()
            ];
        }





    }


    //批量库存转移
    public static function multiSkuStockChange($sku){
        if(!count($sku)) return ['code' => 500,'msg'=> '参数错误'];
        foreach ($sku as $v){
            //从库位 到 库位store_house_id
            $info = DB::table('erp_product_list')
                -> leftJoin('erp_receive_goods_record','erp_receive_goods_record.product_id','erp_product_list.id')
                -> leftJoin('erp_receive_record','erp_receive_goods_record.receive_record_id','erp_receive_record.id')
                -> leftJoin('erp_logistics','erp_receive_record.logistics_id','erp_logistics.id')
                -> where([
                    'erp_product_list.id' => $v['sku_id'],
                ])
                -> select([
                    'erp_receive_record.logistics_id',
                    'erp_logistics.order_id as logistics_order_id',
                    'erp_receive_goods_record.*',
                ])
                -> first();
            if(!$info){
                return [
                    'code' => 500,
                    'msg' => '无此数据'
                ];
            }
            $adjust_type  =$v['type']=='reduce'?1:2 ;
            $adjust_id = DB::table('erp_stock_adjust') -> insertGetId([
                'from_storehouse_id' => $info -> store_house_id,
                'receive_goods_record_id' => $info -> id,
                'transfer_num' => $v['number'],
                'created_at' => time(),
                'updated_at' => time(),
                'adjust_type' =>$adjust_type,
            ]);

            if($adjust_type == 1){
                //报废
                DB::table('erp_receive_goods_record')
                    -> where([
                        'id' => $info -> id
                    ]) -> update([
                        //可售数量 减去
                        'can_buy_num' => intval($info -> can_buy_num) - intval($v['number'])
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
                        'can_buy_num' => intval($stock_info -> can_buy_num) - intval($v['number']),
                        'current_num' => intval($stock_info -> current_num) - intval($v['number']),
                    ]);
                }

            }else{
                //调账
                DB::table('erp_receive_goods_record')
                    -> where([
                        'id' => $info -> id
                    ]) -> update([
                        //可售数量 减去
                        'can_buy_num' => intval($info -> can_buy_num) + intval($v['number'])
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
                        'can_buy_num' => intval($stock_info -> can_buy_num) + intval($v['number']),
                        'current_num' => intval($stock_info -> current_num) + intval($v['number']),
                    ]);
                }

            }

            if($info -> product_id){
                $skuRepository = new SKURepository();
                $skuRepository -> autoPutOnOrOff([$info -> product_id]);
            }

            return ['code'=> '200','msg'=>'success'];

        }



    }
}
