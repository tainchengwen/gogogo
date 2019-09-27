<?php

namespace App\Http\Controllers\Api;

use App\Account;
use App\Configure;
use App\Logistics;
use App\NumberQueue;
use App\Port;
use App\ProductList;
use App\PurchaseOrder;
use App\PurchaseOrderGoods;
use App\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Repositories\MathRepository;

class PurchaseOrderListController extends Controller
{
    public function __construct(MathRepository $mathRepository)
    {
        $this->mathRepository = $mathRepository;
    }

    //新增采购订单
    public function addOrder(Request $request){
        $validator = Validator::make($request->all(), [
            //供应商id
            'supplier_id' => 'required|numeric|exists:erp_supplier,id',
            'order_date' => 'required|date',
            'business_id' => 'required|numeric', //事业部id
            'purchase_type' => 'required|numeric', //采购类型 国内国外
            'currency' => 'required|numeric', //币种,
            'rate' => 'required|numeric', //汇率,
            'port_id' => 'required|numeric', //港口,
            'remark' => 'max:500', //港口,
            'freight'=>'nullable|numeric|min:0',

        ],[
            'supplier_id.required' => '供应商必填',
            'supplier_id.exists' => '没有此供应商',
            'order_date.required' => '采购日期必填',
            'business_id.required' => '事业部id必填',
            'purchase_type.required' => '采购类型必填',
            'currency.required' => '币种必填',
            'rate.required' => '汇率必填',
            'port_id.required' => '港口必填',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $order_id = NumberQueue::addQueueNo();
        $user = auth('api')->user();
        $insert_data=[
            'order_num' => $order_id,
            'business_id' => $request -> business_id,
            'supplier_id' => $request -> supplier_id,
            'order_date' => strtotime($request -> order_date),
            'purchase_type' => $request -> purchase_type,
            'currency' => $request -> currency,
            'rate' => $request -> rate,
            'port_id' => $request -> port_id,
            'remark' => $request -> remark,
            'create_user_id' => $user -> id,
            'update_user_id' => $user -> id,
            'purchase_class' => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ];
        if (!is_null($request->freight)){
            $insert_data['freight']=$request->freight;
        }
        PurchaseOrder::insert($insert_data);

        return [
            'code' => 200,
            'msg' => '新增采购订单成功'
        ];
    }


    //导入采购订单明细
    public function addOrderInfo(Request $request){

    }


    //采购订单列表
    public function theList(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric', //事业部id
            //'order_date_left' => 'sometimes|date',
            //'order_date_right' => 'sometimes|date',
            //筛选条件
            /*
             * 订单编号 order_num
             * 采购日期 order_date_left  order_date_right
             * 供应商 supplier_id 选项用supplier/theList options， 默认不传
             * 订单状态 order_status 未入库0(默认) 已入库1  全部2
             */

        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        // 这里需要检测，如果是商品编码，则搜索所有包含这个编码的采购单
        $orderIds = [];
        if ($request->product_no) {
            $product = DB::table('erp_product_list')
            -> where('flag', 0)
            -> where('product_no', $request->product_no)
            -> select(['id'])
            -> first();

            if ($product) {
                $orderIds = DB::table('erp_purchase_order_goods')
                -> where('product_id', $product->id)
                -> where('flag', 0)
                -> select('order_id')
                -> distinct('order_id')
                -> pluck('order_id');
            }
        }

        $where = [
            'erp_purchase_order.business_id' => $request -> business_id,
            'erp_purchase_order.flag' => 0
        ];

        if($request -> order_num){
            $where['order_num'] = $request -> order_num;
        }
        if($request -> supplier_id){
            $where['supplier_id'] = $request -> supplier_id;
        }
        if($request -> order_status == 1){
            $whereIn = [4];
        }elseif($request -> order_status == 2){
            $whereIn = [

            ];
        }else{
            $whereIn = [
                0,1,2,3
            ];

        }

        $list = DB::table('erp_purchase_order')
            -> leftJoin('users','erp_purchase_order.create_user_id','users.id')
            -> leftJoin('users as update_users','erp_purchase_order.update_user_id','update_users.id')
            -> where($where)
            -> where(function($query)use($request,$whereIn, $orderIds){
                if($request->product_no){
                    $query -> whereIn('erp_purchase_order.id', $orderIds);
                }
                if(count($whereIn)){
                    $query -> whereIn('erp_purchase_order.order_status',$whereIn);
                }
                if($request -> order_date_left){
                    $query -> where('erp_purchase_order.order_date','>=',strtotime($request -> order_date_left));
                }
                if($request -> order_date_right){
                    $query -> where('erp_purchase_order.order_date','<=',strtotime($request -> order_date_right));
                }
            })
            -> select([
                'erp_purchase_order.*',
                'users.name as reguser',
                'update_users.name as moduser',
            ])
            -> orderBy('id','desc')
            -> paginate(isset($request -> per_page)?$request -> per_page:20);



        //采购类型
        $purchaseType = Configure::purchaseType();
        $currency = Configure::getCurrency();
        $orderStatus = Configure::getOrderStatus();
        //采购类别
        $purchaseClass = Configure::purchaseClass();
        foreach($list as $k => $vo){
            $list[$k] -> purchase_type_str = $purchaseType[$vo -> purchase_type];
            $list[$k] -> currency_str = $currency[$vo -> currency];
            $list[$k] -> status_str = $orderStatus[$vo -> order_status];
            $list[$k] -> purchase_class_str = $purchaseClass[$vo -> purchase_class];
            //供应商
            $supplier_info = Supplier::find($vo -> supplier_id);
            if($supplier_info){
                $list[$k] -> supplier_name = $supplier_info -> name;
            }else{
                $list[$k] -> supplier_name = '';
            }

            //港口
            $port_info = Port::find($vo -> port_id);
            if($port_info){
                $list[$k] -> port_name = $port_info -> name;
            }else{
                $list[$k] -> port_name = '';
            }
            //总费用
            $list[$k]->total=$vo->freight?$vo->price+$vo->freight : $vo->price;
        }

        return $list;
    }


    //采购订单 新增商品明细
    public function addOrderGoodsList(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric', //事业部id
            'order_id' => 'required|numeric|exists:erp_purchase_order,id', //订单id
            'goods_list'=>'required|json', //商品列表 所需参数id，price，number  [{"id":"1","price":"23","number":"34"}]
            /*
            'goods_list.*.id'=>'required|exists:erp_product_list,id|distinct',
            'goods_list.*.price'=>'required',
            'goods_list.*.number'=>'required',
            */
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        //看这个order status 是否正确
        $order_info = PurchaseOrder::where([
            'id' => $request -> order_id,
            'business_id' => $request -> business_id,
            'flag' => 0
        ]) -> first();
        if(!$order_info || $order_info -> order_status > 1){
            return [
                'code' => 500,
                'msg' => '没有此数据'
            ];
        }



        $goods_json = $request -> goods_list;
        $goods_arr = json_decode($goods_json,true);
        if(!count($goods_arr)){
            return [
                'code' => 500,
                'msg' => 'json参数不能为空'
            ];
        }

        $temp_ids = [];
        foreach($goods_arr as $vo){
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
            /*
            //判断number
            if(intval($info -> number) < intval($vo['number'])){
                return [
                    'code' => 500,
                    'msg' => 'json中number有误'
                ];
            }
            */



        }

        DB::beginTransaction();
        try{

            //构造代理版 自动发货数据
            $temp_auto_send = [];
            $sum_price = 0;
            foreach($goods_arr as $k => $vo){
                $sum_price += floatval($vo['price']) * intval($vo['number']);
                $purchase_order_goods_id = PurchaseOrderGoods::insertGetId([
                    'created_at' => time(),
                    'updated_at' => time(),
                    'order_id' => $request -> order_id,
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
            $user = auth('api')->user();
            PurchaseOrder::where([
                'id' => $request -> order_id,
            ]) -> update([
                'order_status' => 1,
                'price' => $sum_price, //采购总金额
                'updated_at' => time(),
                'update_user_id' => $user -> id,
            ]);



            //如果是代理版事业部  下了采购订单，直接港口发货、港口收货，
            $business_info = DB::table('erp_business')
                -> where([
                    'id' => $request -> business_id
                ]) -> first();
            if($business_info -> business_type){
                //1、创建物流单
                //找一下目的港口id
                $port_info = DB::table('erp_port') -> where([
                    'business_id' => $request -> business_id,
                ]) -> where('name','like','%香港%') -> first();
                //自动发货
                $logistics_id = Logistics::addInfo((object)[
                    'order_id' => $request -> order_id,
                    'goods_list' => json_encode($temp_auto_send),
                    'transportationCost' => 0,
                    'freight_type' => 3,
                    'true_num' => '',
                    'business_id' => $request -> business_id,
                    'destination_port_id' => $port_info -> id, //目的港 id
                    'freight' => 0,
                    'remark' => '代理版事业部',
                    'currency' => 1,
                    'send_date' => date('Y-m-d H:i'),
                    'incidental' => 0,
                    'base' => 0,
                ]);

                //自动收货
                Logistics::where([
                    'id' => $logistics_id
                ]) -> update([
                    'status' => 1,
                    'receive_date' => time(),
                    'updated_at' => time()
                ]);



            }

            DB::commit();
            return [
                'code' => 200,
                'msg' => '新增明细成功'
            ];
        }catch (\Exception $exception){

            DB::rollBack();
            return [
                'code' => 500,
                'msg' => '新增明细失败'
            ];
        }




    }


    //采购订单详情
    function orderInfo(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric', //事业部id
            'order_id' => 'required|numeric|exists:erp_purchase_order,id'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }



        $orderInfo = DB::table('erp_purchase_order')
            //始发港
            -> leftJoin('erp_port','erp_purchase_order.port_id','erp_port.id')
            //供应商
            ->leftJoin('erp_supplier','erp_purchase_order.supplier_id','erp_supplier.id')
            -> where([
                'erp_purchase_order.id' => $request -> order_id,
                'erp_purchase_order.flag' => 0
            ])
            -> select([
                'erp_purchase_order.*',
                'erp_port.name as port_name',
                'erp_supplier.name as supplier_name',

            ])
            -> first();

        if(!$orderInfo){
            return [
                'code' => 500,
                'msg' => '没有此数据'
            ];
        }

        $purchaseType = Configure::purchaseType();
        $currency = Configure::getCurrency();

        $purchaseClass = Configure::purchaseClass();

        $orderInfo -> currency_str = $currency[$orderInfo -> currency];
        $orderInfo -> purchase_type_str = $purchaseType[$orderInfo -> purchase_type];
        $orderInfo -> purchase_class_str = $purchaseClass[$orderInfo -> purchase_class];


        $goods_list = DB::table('erp_purchase_order_goods')
            -> leftJoin('erp_purchase_order','erp_purchase_order_goods.order_id','erp_purchase_order.id')


            -> leftJoin('erp_product_list','erp_purchase_order_goods.product_id','erp_product_list.id')
            -> leftJoin('erp_product_class as product_class','erp_product_list.class_id','product_class.id')
            //品牌
            -> leftJoin('erp_product_class as product_brand','erp_product_list.brand_id','product_brand.id')
            //系列
            -> leftJoin('erp_product_class as product_series','erp_product_list.series_id','product_series.id')
            -> where([
                'order_id' => $request -> order_id,
                'erp_purchase_order_goods.flag' => 0
            ])
            -> select([
                'erp_purchase_order_goods.*',
                'erp_purchase_order_goods.number as product_num',
                'erp_product_list.product_name',
                'erp_product_list.model',
                'erp_product_list.id',
                'erp_product_list.number as number', //包装数量
                'erp_product_list.product_no',
                'erp_product_list.product_long',
                'erp_product_list.product_wide',
                'erp_product_list.product_height',
                'erp_product_list.physics_weight as product_weight',
                'erp_product_list.volume as product_volume',
                'erp_product_list.volume_weight as product_volume_weight',


                'product_class.name as product_class_name',
                'product_brand.name as product_brand_name',
                'product_series.name as product_series_name',

            ])
            -> get();

        $orderInfo -> goods_info = $goods_list;

        return get_object_vars($orderInfo);
    }


    //编辑采购订单
    public function editPurchaseOrder(Request $request){
        $validator = Validator::make($request->all(), [
            //供应商id
//            'supplier_id' => 'required|numeric|exists:erp_supplier,id',
            'order_date' => 'required',
            'business_id' => 'required|numeric', //事业部id
            'purchase_type' => 'required|numeric', //采购类型 国内国外
            'currency' => 'required|numeric', //币种,
            'rate' => 'required|numeric', //汇率,
            'port_id' => 'required|numeric', //港口,
            'remark' => 'max:500', //注释
            'order_id' => 'required|numeric|exists:erp_purchase_order,id',
            'goods_list'=>'required|json', //商品列表 所需参数id，price，number  [{"id":"1","price":"23","number":"34"}]
            'freight'=>'nullable|numeric|min:0',


        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $request -> order_date =  intval(intval($request -> order_date)/1000);

        DB::beginTransaction();
        try{

            //看这个order status 是否正确
            $order_info = PurchaseOrder::where([
                'id' => $request -> order_id,
                'business_id' => $request -> business_id,
                'flag' => 0
            ]) -> first();
            if(!$order_info || $order_info -> order_status > 1 || $order_info->pay_price != 0){
                return [
                    'code' => 500,
                    'msg' => '不可修改此数据'
                ];
            }

            $goods_json = $request -> goods_list;
            $goods_arr = json_decode($goods_json,true);
            if(!count($goods_arr)){
                return [
                    'code' => 500,
                    'msg' => 'json参数不能为空'
                ];
            }

            $temp_ids = [];
            foreach($goods_arr as $vo){
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


            //先删除他原来的采购明细
            DB::table('erp_purchase_order_goods')
                -> where([
                    'order_id' => $order_info -> id
                ]) -> update([
                    'flag' => 1
                ]);

            $sum_price = 0;
            foreach($goods_arr as $vo){
                $sum_price += floatval($vo['price']) * intval($vo['number']);
                PurchaseOrderGoods::insert([
                    'created_at' => time(),
                    'updated_at' => time(),
                    'order_id' => $request -> order_id,
                    'product_id' => $vo['id'],
                    'price' => $vo['price'],
                    'number' => $vo['number'],
                ]);
            }


            /*

             'supplier_id' => 'required|numeric|exists:erp_supplier,id',
            'order_date' => 'required|date',
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
            'purchase_type' => 'required|numeric', //采购类型 国内国外
            'currency' => 'required|numeric', //币种,
            'rate' => 'required|numeric', //汇率,
            'port_id' => 'required|numeric', //港口,
            'remark' => 'max:500', //注释
            */
            //将此条order 状态改为 已保存为发货
            //计算总价格
            $update_data=[
                'supplier_id' => $request -> supplier_id,
                'order_date' => $request -> order_date,
                'purchase_type' => $request -> purchase_type,
                'currency' => $request -> currency,
                'rate' => $request -> rate,
                'port_id' => $request -> port_id,
                'remark' => $request -> remark,
                'order_status' => 1,
                'price' => $sum_price, //采购总金额
                'updated_at' => time()
            ];
            if (!is_null($request->freight)){
                $update_data['freight']=$request->freight;
            }
            PurchaseOrder::where([
                'id' => $request -> order_id,
            ]) -> update($update_data);

            DB::commit();
            return [
                'code' => 200,
                'msg' => '编辑成功'
            ];
        }catch (\Exception $exception){
            DB::rollBack();
            return [
                'code' => 500,
                'msg' => $exception ->getMessage()
            ];
        }




    }



    //删除采购单
    public function deletePurchaseOrder(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric', //事业部id
            'order_id' => 'required|numeric|exists:erp_purchase_order,id'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $orderInfo = PurchaseOrder::where([
            'id' => $request -> order_id,
            'flag' => 0,
            'business_id' => $request -> business_id
        ]) -> first();

        if(!$orderInfo){
            return [
                'code' => 500,
                'msg' => '没有此数据'
            ];
        }

        if($orderInfo -> pay_price != 0){
            return [
                'code' => 500,
                'msg' => '已存在支付金额'
            ];
        }

        $erp_purchase_order_goods = DB::table('erp_purchase_order_goods')
            -> where([
                'order_id' => $orderInfo -> id,
                'flag' => 0
            ]) -> get();
        //看这个goods_id 有没有出现在其他物流单里
        foreach($erp_purchase_order_goods as $vo){
            $logistics_info = DB::table('erp_logistics_info')
                -> where([
                    'flag' => 0,
                    'goods_id' => $vo -> id
                ]) -> first();
            if($logistics_info){
                return [
                    'code' => 500,
                    'msg' => '已存在物流单，请先删除物流单'
                ];
            }
        }

        PurchaseOrder::where('id',$request -> order_id)
            -> update([
                'flag' => 1
            ]);


        return [
            'code' => 200,
            'msg' => '删除成功'
        ];


    }


    //采购订单付款列表
    public function payOrderList(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric', //事业部id

        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }


        $order_status = Configure::getOrderStatus();
        $pay_status = Configure::getOrderPayStatus();
        $currency_config = Configure::getCurrency();
        $purchase_type = Configure::purchaseType();


        $list = DB::table('erp_purchase_order as order')
            -> leftJoin('erp_supplier','order.supplier_id','erp_supplier.id')
            -> leftJoin('erp_port','order.port_id','erp_port.id')
            -> select([
                'order.*',
                'erp_supplier.name as supplier_name',
                'erp_port.name as port_name',

            ])
            -> where(function($query)use($request){
                //订单编号
                if($request -> order_num){
                    $query -> where('order.order_num',trim($request -> order_num));
                }
                //采购日期
                if($request -> order_date_left){
                    $query -> where('order_date','>=',strtotime($request -> order_date_left));
                }
                if($request -> order_date_right){
                    $query -> where('order_date','<=',strtotime($request -> order_date_right));
                }

                //支付状态
                if($request -> pay_status){
                    //支付完成
                    if($request -> pay_status == 1){
                        $query -> where('pay_status',2);
                    }

                }else{
                    $query -> where('pay_status','<>',2);
                }

                //币种
                if($request -> currency){
                    $query -> where('currency',$request -> currency);
                }
                //[{ label: '未保存', key: '0' },
                // { label: '已保存未发货', key: '1' },
                // { label: '发货中', key: '2' },
                // { label: '发货完成未入库', key: '3' },
                // { label: '发货完成已入库', key: '4' }]

                if($request -> order_status){
                    $query -> where('order_status',$request -> order_status);
                }

                //供应商
                if($request -> supplier_id){
                    $query -> where('supplier_id',$request -> supplier_id);
                }










            })
            -> where([
                'order.business_id' => $request -> business_id,
                'order.flag' => 0
            ])

                -> where('order.order_status','>',0)
            -> orderBy('id','desc')
            -> paginate(isset($request -> per_page)?$request -> per_page:20);

        foreach($list as $k => $vo){
            $list[$k] -> pay_status_str = $pay_status[$vo -> pay_status]; //支付状态
            $list[$k] -> order_status_str = $order_status[$vo -> order_status]; //订单状态
            $list[$k] -> currency_str = $currency_config[$vo -> currency]; //币种
            $list[$k] -> purchase_type_str = $purchase_type[$vo -> purchase_type]; //采购类型

            //未付款金额
            $list[$k] -> paynum = $this->mathRepository->math_sub($vo -> price, $vo -> pay_price);
        }

        return $list;
    }



    //采购订单导入下单
    public function exportPurchaseOrder(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric', //事业部id
            'order_id' => 'required|numeric|exists:erp_purchase_order,id', //订单id
            'goods_info' => 'required|json'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        //看这个order status 是否正确
        $order_info = PurchaseOrder::where([
            'id' => $request -> order_id,
            'business_id' => $request -> business_id,
            'flag' => 0
        ]) -> first();
        if(!$order_info || $order_info -> order_status > 1){
            return [
                'code' => 500,
                'msg' => '没有此数据'
            ];
        }
        $temp = [];
        $goods_info = json_decode($request -> goods_info,true);
        $error_products = [];
        foreach($goods_info as $k => $vo){
            $product_nos = [];
            if($vo['商品编号'] && $vo['单价'] && $vo['数量']){
                $product_info = DB::table('erp_product_list')
                    -> where([
                        'product_no' => trim($vo['商品编号'])
                    ]) -> first();
                if(in_array(trim($vo['商品编号']),$product_nos)){
                    return [
                        'code' => 500,
                        'msg' => '导入的商品编码重复'
                    ];
                }else{
                    $product_nos[] = trim($vo['商品编号']);
                }
                if($product_info){

                    $temp[$k]['product_no'] = $product_info -> product_no;
                    $temp[$k]['id'] = $product_info -> id;
                    $temp[$k]['price'] = floatval(trim($vo['单价']));
                    $temp[$k]['number'] = intval(trim($vo['数量']));
                }else{
                    $error_products[] = trim($vo['商品编号']);
                }
            }

        }

        if(count($error_products)){
            return [
                'code' => 500,
                'msg' => '商品编号为'.implode(',',$error_products).' 没有在商品库录入'
            ];
        }





        if(count($temp)){

            //检测下  有没有重复的
            foreach($temp as $vo){
                $product_temp = DB::table('erp_purchase_order_goods')
                    -> where([
                        'order_id' => $request -> order_id,
                        'product_id' => $vo['id'],
                        'flag' => 0
                    ]) -> first();
                if($product_temp){
                    return [
                        'code' => 500,
                        'msg' => '导出商品编号'.$vo['product_no'].'重复'
                    ];
                }
            }



            try{
                DB::beginTransaction();
                $sum_price = 0;
                foreach($temp as $vo){
                    $sum_price += floatval($vo['price']) * intval($vo['number']);
                    PurchaseOrderGoods::insert([
                        'created_at' => time(),
                        'updated_at' => time(),
                        'order_id' => $request -> order_id,
                        'product_id' => $vo['id'],
                        'price' => $vo['price'],
                        'number' => $vo['number'],
                    ]);
                }

                //将此条order 状态改为 已保存为发货
                //计算总价格
                $user = auth('api')->user();
                PurchaseOrder::where([
                    'id' => $request -> order_id,
                ]) -> update([
                    'order_status' => 1,
                    'price' => $sum_price, //采购总金额
                    'updated_at' => time(),
                    'update_user_id' => $user -> id,
                ]);

                DB::commit();
                return [
                    'code' => 200,
                    'msg' => '下单成功'
                ];
            }catch (\Exception $exception){
                DB::rollBack();
                return [
                    'code' => 500,
                    'msg' => '下单失败'
                ];
            }



        }else{
            return [
                'code' => 500,
                'msg' => '没有有效的数据'
            ];
        }






















    }



    //财务付款详情
    public function payOrderInfo(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric', //事业部id
            'id' => 'required|numeric|exists:erp_purchase_order'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $info = DB::table('erp_purchase_order as order')
            -> leftJoin('erp_supplier','order.supplier_id','erp_supplier.id')
            -> leftJoin('erp_port','order.port_id','erp_port.id')
            -> select([
                'order.*',
                'erp_supplier.name as supplier_name',
                'erp_port.name as port_name',

            ])
            -> where([
                'order.business_id' => $request -> business_id,
                'order.id' => $request -> id
            ])
            //-> where('pay_status','<>','2')
            -> first();

        $order_status    = Configure::getOrderStatus();
        $pay_status      = Configure::getOrderPayStatus();
        $currency_config = Configure::getCurrency();
        $purchase_type   = Configure::purchaseType();

        $info -> pay_status_str    = $pay_status[$info -> pay_status];                                     //支付状态
        $info -> order_status_str  = $order_status[$info -> order_status];                                 //订单状态
        $info -> currency_str      = $currency_config[$info -> currency];                                  //币种
        $info -> purchase_type_str = $purchase_type[$info -> purchase_type];                               //采购类型
        $info -> pay_base          = floatval($info -> pay_price) * $info -> weight_rate;
        $info -> add_rate          = $info -> weight_rate;
        $info -> paynum            = $this->mathRepository->math_add($info -> price, $info -> pay_price);  //未付款金额
        $info -> notpay_price      = $this->mathRepository->math_sub($info->price, $info->pay_price);

        //采购订单详情
        $order_detail = DB::table('erp_purchase_order_goods')
            -> leftJoin('erp_product_list','erp_purchase_order_goods.product_id','erp_product_list.id')
            -> leftJoin('erp_product_class as product_class','erp_product_list.class_id','product_class.id')
            //品牌
            -> leftJoin('erp_product_class as product_brand','erp_product_list.brand_id','product_brand.id')
            //系列
            -> leftJoin('erp_product_class as product_series','erp_product_list.series_id','product_series.id')
            -> where([
                'erp_purchase_order_goods.order_id' => $request -> id,
                'erp_purchase_order_goods.flag' => 0
            ])
            -> select([
                'erp_purchase_order_goods.*',
                'erp_purchase_order_goods.number as order_goods_num', //采购数量
                'erp_product_list.product_name',
                'erp_product_list.model',
                'erp_product_list.number',
                'erp_product_list.product_no',
                'product_class.name as product_class_name',
                'product_brand.name as product_brand_name',
                'product_series.name as product_series_name',
            ])
            -> get();




        //付款记录
        $pay_record = DB::table('erp_purchase_order_pay')
            -> leftJoin('erp_account','erp_purchase_order_pay.account_id','erp_account.id')
            -> leftJoin('users','erp_purchase_order_pay.pay_user_id','users.id')
            -> where([
                'erp_purchase_order_pay.flag' => 0,
                'erp_purchase_order_pay.order_id' => $info -> id,
                'erp_purchase_order_pay.pay_type' => 0
            ])
            -> select([
                'erp_purchase_order_pay.*',
                'erp_purchase_order_pay.service_charge as pay_cost',
                'erp_purchase_order_pay.pay_amount_base as pay_base',
                'erp_account.account_name',
                'users.name as pay_person',
            ])
            -> get();



        return [
            'order_info' => $info,
            'pay_record' => $pay_record,
            'order_detail' => $order_detail
        ];


    }

    //财务付款新增付款详情 分两部分，1、通过币种 取财务账户。2、通过账户id取充值记录
    public function addPayInfo(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric', //事业部id
            'id' => 'required|numeric|exists:erp_account' //账户id
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }


        //通过账户id 取记录
        $account = DB::table('erp_account') -> where([
            'id' => $request -> id,
            'flag' => 0,
            'business_id' => $request -> business_id
        ]) -> first();

        if(!$account){
            return [
                'code' => 500,
                'msg' => '没有此账户'
            ];
        }


        //取充值记录
        $record = DB::table('erp_account_recharge_record')
            -> where([
                'account_id' => $request -> id,
                'flag' => 0
            ]) -> where('price','>',0)
            -> orderBy('id','asc')
            -> get();

        return $record;
    }


    //新增财务付款
    public function addPayRecord(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric', //事业部id
            'account_id' => 'required|numeric|exists:erp_account,id', //账户id
            'pay_amount' => 'required|numeric|min:0.01', //付款金额
            'pay_record_ids' => 'required|json', //充值记录id addPayInfo 返回的id [1,3,6]
            'order_id' => 'required|numeric|exists:erp_purchase_order,id'
            // 'service_charge' => '' 手续费
            //付款备注 remark
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }


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

        // 剩余应付
        $residue = $this->mathRepository->math_sub($order_info->price,$order_info->pay_price);

        if($this->mathRepository->math_comp($residue, $request->pay_amount) < 0){
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


        $temp_ids = [];
        foreach($pay_record_ids as $vo){
            $record_info = DB::table('erp_account_recharge_record')
                -> where([
                    'id' => $vo,
                    'account_id' => $request -> account_id,
                    'flag' => 0
                ]) ->where('price','>',0) ->  first();
            if(!$record_info){
                return [
                    'code' => 500,
                    'msg' => '没有此条充值记录'
                ];
            }

            if(in_array($vo,$temp_ids)){
                return [
                    'code' => 500,
                    'msg' => 'json参数中id重复'
                ];
            }
            $temp_ids[] = $vo;
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

            $sum = $this->mathRepository->math_add($order_info -> pay_price, $payed_amount);
            // 比较大小，相等返回0
            if($this->mathRepository->math_comp($sum, $order_info -> price) == 0){
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

    //取消采购单付款
    public function cancelPayRecord(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric', //事业部id
            'id' => 'required|numeric'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }


        $order = DB::table('erp_purchase_order')
            -> where([
                'id' => $request -> id,
                'business_id' => $request -> business_id,
                'flag' => 0
            ]) -> first();


        if(!$order){
            return [
                'code' => 500,
                'msg' => '没有此订单'
            ];
        }



        //采购单下 有入库 则不允许删除
        $erp_purchase_order_goods = DB::table('erp_purchase_order_goods')
            -> where([
                'order_id' => $request -> id,
                'flag' => 0
            ]) -> get();
        if(count($erp_purchase_order_goods)){
            $goods_ids = [];
            foreach($erp_purchase_order_goods as $vo){
                $goods_ids[] = $vo -> id;
            }
            $erp_logistics_info = DB::table('erp_logistics_info')
                -> whereIn('goods_id',$goods_ids)
                -> get();
            if(count($erp_logistics_info)){
                foreach($erp_logistics_info as $vo){
                    if($vo -> receive_num){
                        return [
                            'code' => 500,
                            'msg' => '已入库，不可以撤销'
                        ];
                    }
                }
            }
        }


        //没有record_id 就是 全部撤销
        if(!$request -> record_id){
//看下扣款记录
            $pay_info = DB::table('erp_purchase_order_pay')
                -> where([
                    'order_id' =>$request -> id,
                    'pay_type' => 0,
                    'flag' => 0
                ]) -> get();
            if(count($pay_info)){
                return $this -> cancelPublic($pay_info,$order,1);

            }else{
                return [
                    'code' => 500,
                    'msg' => '并没有付款记录'
                ];
            }
        }else{
            //单笔撤销
            $pay_record = DB::table('erp_purchase_order_pay')
                -> where([
                    'id' => $request -> record_id,
                    'flag' => 0
                ]) -> get();
            if(!count($pay_record)){
                return [
                    'code' => 500,
                    'msg' => '并没有此条付款记录'
                ];
            }else{
                return $this -> cancelPublic($pay_record,$order,2);
            }


        }




    }

    //type = 1 全部撤销 type = 2 部分撤销
    private function cancelPublic($pay_info,$order,$type=1){
        DB::beginTransaction();
        try{
            //取消付款
            foreach($pay_info as $vo){
                $temp_pay_detail = DB::table('erp_purchase_order_pay_detail')
                    -> where([
                        'pay_id' => $vo -> id,
                        'flag' => 0
                    ]) -> get();
                if(count($temp_pay_detail)){
                    foreach($temp_pay_detail as $value){

                        //充值记录充值金额退回去
                        DB::table('erp_account_recharge_record')
                            -> where([
                                'id' => $value -> recharge_record_id
                            ]) -> increment('price',abs($value -> pay_price));


                        //把账户的 充值金额 退回去
                        DB::table('erp_account')
                            -> where([
                                'id' => $value ->account_id
                            ]) -> increment('balance',abs($value -> pay_price));

                        //采购单的已付款金额 退回去
                        DB::table('erp_purchase_order') -> where([
                            'id' => $order -> id
                        ]) -> decrement('pay_price',abs($value -> pay_price));


                    }
                }

                DB::table('erp_purchase_order_pay') -> where([
                    'id' => $vo -> id
                ]) -> update([
                    'flag' => 1
                ]);

                DB::table('erp_purchase_order_pay_detail')
                    -> where([
                        'pay_id' => $vo -> id,
                    ]) -> update([
                        'flag' => 1
                    ]);


            }

            //此订单改为未付款
            $order = DB::table('erp_purchase_order')
                -> where([
                    'id' => $order -> id
                ]) -> first();
            if($order -> pay_price){
                //部分付款
                $pay_status = 1;
                $pay_price = $order -> pay_price;
            }else{
                //未付款
                $pay_status = 0;
                $pay_price = 0;
            }

            //不过 如果已付款金额 小于2分的话 就变成未付款
            if(floatval($order -> pay_price) < 0.02){
                //未付款
                $pay_status = 0;
                $pay_price = 0;
            }


            DB::table('erp_purchase_order')
                -> where([
                    'id' => $order -> id
                ]) -> update([
                    'pay_status' => $pay_status,
                    'weight_rate' => 1,
                    'pay_price' => $pay_price
                ]);

            if($type == 2){
                //部分撤销，需要重新计算下 加权汇率
                $record_price = DB::table('erp_purchase_order_pay')
                    -> where([
                        'order_id' => $order -> id,
                        'pay_type' => 0,
                        'flag' => 0
                    ]) -> sum('pay_price');
                $record_base = DB::table('erp_purchase_order_pay')
                    -> where([
                        'order_id' => $order -> id,
                        'pay_type' => 0,
                        'flag' => 0
                    ]) -> sum('pay_amount_base');




                if($record_price){
                    DB::table('erp_purchase_order') -> where([
                        'id' => $order -> id
                    ]) -> update([
                        'weight_rate' => round(floatval($record_base)/floatval($record_price),5)
                    ]);
                }
            }




            DB::commit();
            return [
                'code' => 200,
                'msg' => '撤销成功'
            ];
        }catch (\Exception $exception){
            DB::rollBack();
            return [
                'code' => 500,
                'msg' => $exception->getTraceAsString()
            ];
        }

    }










}
