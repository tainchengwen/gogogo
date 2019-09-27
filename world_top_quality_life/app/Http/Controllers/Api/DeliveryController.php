<?php

namespace App\Http\Controllers\Api;

use App\Account;
use App\Configure;
use App\GoodsList;
use App\Logistics;
use App\LogisticsInfo;
use App\MallApi;
use App\Port;
use App\ProductList;
use App\PurchaseOrder;
use App\PurchaseOrderGoods;
use App\ReceiveRecord;
use App\Stock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Repositories\MathRepository;

class DeliveryController extends Controller
{
    public function __construct(MathRepository $mathRepository)
    {
        $this->mathRepository = $mathRepository;
    }

    //港口发货 列表
    public function theList(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
            //'order_date_left' => 'sometimes|date',
            //'order_date_right' => 'sometimes|date',
            //筛选条件
            /*
             * 订单编号 order_num
             * 采购日期 order_date_left  order_date_right
             * 供应商 supplier_id 选项用supplier/theList options， 默认不传
             * 订单状态 order_status 发货未完成0   发货已完成1
             */
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        //已保存未发货、发货中、发货完成未入库、

        $list = DB::table('erp_purchase_order')
            -> leftJoin('erp_port','erp_purchase_order.port_id','erp_port.id')
            -> leftJoin('erp_supplier','erp_purchase_order.supplier_id','erp_supplier.id')

            -> select([
                'erp_purchase_order.*',
                'erp_port.name as port_name',
                'erp_supplier.name as supplier_name'
            ])
            -> where(function($query)use($request){
                $query -> where([
                    'erp_purchase_order.business_id' => $request -> business_id,
                    'erp_purchase_order.flag' => 0
                ]);

                if($request -> order_num){
                    $query -> where([
                        'erp_purchase_order.order_num' => $request -> order_num
                    ]);
                }

                if($request -> supplier_id){
                    $query -> where([
                        'erp_purchase_order.supplier_id' => $request -> supplier_id
                    ]);
                }

                if($request -> order_status == 1){
                    $query -> whereIn('erp_purchase_order.order_status',[3,4]);
                }else{
                    $query -> whereIn('erp_purchase_order.order_status',[0,1,2]);
                }

                if($request -> order_date_left){
                    $query -> where('erp_purchase_order.order_date','>=',strtotime($request -> order_date_left));
                }
                if($request -> order_date_right){
                    $query -> where('erp_purchase_order.order_date','<=',strtotime($request -> order_date_right));
                }

            })

            //-> whereIn('erp_purchase_order.order_status',[1,2,3])


            -> orderBy('erp_purchase_order.id','desc')
            -> paginate(isset($request -> per_page)?$request -> per_page:20);



        /*
        $list = DB::table('erp_purchase_order as order')
            -> leftJoin('erp_supplier','order.supplier_id','erp_supplier.id')
            -> leftJoin('erp_port','order.port_id','erp_port.id')
            -> select([
                'order.*',
                'erp_supplier.name as supplier_name',
                'erp_port.name as port_name',
            ]) -> paginate(isset($request -> per_page)?$request -> per_page:20);

*/

        $purchaseType = Configure::purchaseType();
        $currency = Configure::getCurrency();
        $orderStatus = Configure::getOrderStatus();
        foreach($list as $k => $vo){
            $list[$k] -> purchase_type_str = $purchaseType[$vo -> purchase_type];
            $list[$k] -> currency_str = $currency[$vo -> currency];
            $list[$k] -> status_str = $orderStatus[$vo -> order_status];
        }
        return $list;
    }


    //新增物流单号页面
    public function addLogisticsPage(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric', //事业部id
            'order_id_json' => 'required|json', //采购订单id  [1,3,4]

        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }


        //采购订单集合
        $order_info_arr = json_decode($request -> order_id_json,true);
        if(!count($order_info_arr)){
            return [
                'code' => 500,
                'msg' => '采购订单id的json格式有误'
            ];
        }

        //看下这些采购订单的到货港口 是不是一起的
        $order_ids = [];
        foreach($order_info_arr as $vo){
            $order_info = PurchaseOrder::find($vo);
            if(!$order_info || !in_array($order_info -> order_status,[1,2,3])){
                return [
                    'code' => 500,
                    'msg' => '采购订单单号错误'
                ];
            }
            if(in_array($vo,$order_ids)){
                return [
                    'code' => 500,
                    'msg' => '采购订单单号有重复'
                ];
            }else{
                $order_ids[] = $vo;
            }
        }

        //返回选中采购单号的详情
        $order_detail = DB::table('erp_purchase_order_goods as goods')
            -> leftJoin('erp_purchase_order as order','goods.order_id','order.id')
            -> leftJoin('erp_product_list','goods.product_id','erp_product_list.id')
            //商品去找类别、品牌、系列
            //类别
            -> leftJoin('erp_product_class as product_class','erp_product_list.class_id','product_class.id')
            //品牌
            -> leftJoin('erp_product_class as product_brand','erp_product_list.brand_id','product_brand.id')
            //系列
            -> leftJoin('erp_product_class as product_series','erp_product_list.series_id','product_series.id')
            -> select([
                'order.order_num', //订单编号
                'goods.id',
                'goods.number', //总数量
                'goods.deliver_number', //已发货数量
                'erp_product_list.product_name',
                'erp_product_list.model',
                'erp_product_list.product_no',
                'erp_product_list.number as product_number',
                'product_class.name as product_class_name',
                'product_brand.name as product_brand_name',
                'product_series.name as product_series_name',
            ])
            -> whereIn('goods.order_id',$order_ids)
            -> where([
                'goods.flag' => 0
            ])
            -> get();


        return $order_detail;

    }






    //新增物流
    public function addLogistics(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric', //事业部id
            //'true_num' => 'required', //真实单号非必填
            'destination_port_id' => 'required|numeric|exists:erp_port,id', //到货港口
            'freight_type' => 'required', //运费类型
            'currency' => 'required|numeric', //币种id
            'goods_list' => 'required|json',  //所需参数 goods_id（采购订单明细id,addLogisticsPage 返回的id），deliver_number(发货数量)，price
            'send_date' => 'required|date', //发货日期

            //'base' => 'required|numeric', //体积基数  -> 非必填
            'transportationCost' => 'required|numeric', //运输费用
            'incidental' => 'required|numeric', //杂费
            'remark' => 'max:500' //备注
            //'order_id_json' => 'required|json', //采购订单id  [1,3,4]



            //'remark' => '',
            //'goods_list.*.goods_id' => 'required|exists:erp_product_list,id|distinct', //采购订单订单内的商品id
            //'goods_list.*.deliver_number' => 'required|numeric', //采购订单内 商品数量
            //'goods_list.*.price' => 'required|numeric', //采购订单内 单价

        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }


        //验证采购订单的状态
        /*
        $order_info = PurchaseOrder::find($request -> order_id);
        if(!$order_info || $order_info -> status != 1){
            return [
                'code' => 500,
                'msg' => '无此数据或者状态有误'
            ];
        }
        */



        //每个订单的港口id  port_id 不能相同
        //校验 goods_list 的每个order_id
        $check_res = Logistics::checkGoodsList($request);
        if($check_res){
            return $check_res;
        }



        //创建物流单
        $logistics_id = Logistics::addInfo($request);
        if($logistics_id){
            return [
                'code' => 200,
                'msg' => '创建成功'
            ];
        }else{
            return [
                'code' => 500,
                'msg' => '创建失败'
            ];
        }
    }

    //运费测算
    public function calculateFreight(Request $request){
        $validator = Validator::make($request->all(), [
            'calcul_list' => 'required'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $json = json_decode($request -> calcul_list,true);

        //计算总体积
        $volumn_all = 0;
        //总物理重
        $weight_all = 0;


        foreach($json as $vo){
            $goods_info = DB::table('erp_purchase_order_goods')
                -> leftJoin('erp_product_list','erp_purchase_order_goods.product_id','erp_product_list.id')
                -> select([
                    'erp_product_list.*'
                ])
                -> where([
                    'erp_purchase_order_goods.id' => $vo['goods_id'],
                    'erp_purchase_order_goods.flag' => 0
                ]) -> first();
            if(!$goods_info){
                return [
                    'code' => 500,
                    'msg' => '没有此商品'
                ];
            }
            if(!$goods_info -> number){
                return [
                    'code' => 500,
                    'msg' => '商品编号'.$goods_info -> product_no.'没有维护包装数量'
                ];
            }


            if(!$goods_info -> physics_weight){
                return [
                    'code' => 500,
                    'msg' => '商品编号'.$goods_info -> product_no.'没有维护物理重'
                ];
            }
            if(!$goods_info -> product_long || !$goods_info -> product_wide || !$goods_info -> product_height){
                return [
                    'code' => 500,
                    'msg' => '商品编号'.$goods_info -> product_no.'没有维护长宽高'
                ];
            }else{
                $volumn = $goods_info -> product_long * $goods_info -> product_wide * $goods_info -> product_height;
            }


            $volumn_all += $volumn * intval($vo['deliver_number'])/intval($goods_info -> number);
            $weight_all += $goods_info -> physics_weight * intval($vo['deliver_number'])/intval($goods_info -> number);
        }

        return [
            'code' => 200,
            'tip' => '总体积'.$volumn_all.' 总物理重'.$weight_all
        ];






    }


    //港口收货列表
    public function receivingList(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }





        $list = Logistics::where('business_id',$request -> business_id)
            ->where(function($query)use($request){


                //货运编号
                if($request -> logistics_num){
                    $query -> where('logistics_num',trim($request -> logistics_num));
                }

                //发货日期
                if($request -> send_date_left){
                    $query -> where('send_date','>=',strtotime($request -> send_date_left));
                }
                if($request -> send_date_right){
                    $query -> where('send_date','<=',strtotime($request -> send_date_right));
                }

                //发货港口
                if($request -> start_port_id){
                    $query -> where('start_port_id',$request -> start_port_id);
                }

                //目的港口
                if($request -> destination_port_id){
                    $query -> where('destination_port_id',$request -> destination_port_id);
                }

                //采购单号
                if($request -> order_num){
                    //通过筛选采购单号
                    $order_info = PurchaseOrder::where('order_num',trim($request -> order_num)) -> first();
                    if($order_info){
                        $query -> where('order_id',$order_info -> id);
                    }
                }

                //真实运单号
                if($request -> true_num){
                    $query -> where('true_num',trim($request -> true_num));
                }

                //运输状态 已收货1 未收货(默认)0
                if($request -> status == '1' || $request -> status == '0'){
                    if($request -> status){
                        $query -> where('status','=',1);
                    }else{
                        $query -> where('status',0);
                    }
                }



            })
            -> where('flag',0)
            -> orderBy('id','desc')
            -> paginate(isset($request -> per_page)?$request -> per_page:20);



        $currency = Configure::getCurrency();
        $freightType = Configure::freightType();
        $status = Configure::getLogisticsStatus();
        foreach($list as $k => $vo){
            $list[$k] -> currency_str = $currency[$vo -> currency];
            $port_info = Port::find($vo -> destination_port_id);
            if($port_info){
                $list[$k] -> destination_port_str = $port_info -> name;
            }else{
                $list[$k] -> destination_port_str = '';
            }

            $start_port_info = Port::find($vo -> start_port_id);
            if($start_port_info){
                $list[$k] -> start_port_str = $start_port_info -> name;
            }else{
                $list[$k] -> start_port_str = '';
            }

            $list[$k] -> freight_type_str = $freightType[$vo -> freight_type];
            $list[$k] -> status_str = $status[$vo -> status];
        }
        return $list;

    }


    //物流单号详情
    public function logisticsInfo(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
            'id' => 'required|numeric|exists:erp_logistics,id'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $logisticsInfo = Logistics::where([
            'id' => $request -> id,
            'business_id' => $request -> business_id
        ]) -> first();
        if(!$logisticsInfo){
            return [
                'code' => 500,
                'msg' => '数据错误'
            ];
        }
        $port_info = Port::find($logisticsInfo -> destination_port_id);
        $logisticsInfo -> destination_port_name =  $port_info -> name;
        $start_port_info = Port::find($logisticsInfo -> start_port_id);
        $logisticsInfo -> start_port_name =  $start_port_info -> name;


        $logisticsInfo -> freight_type_str = Configure::freightType()[$logisticsInfo -> freight_type];
        $logisticsInfo -> currency_str = Configure::getCurrency()[$logisticsInfo -> currency];
        $logisticsInfo -> status_str = Configure::getLogisticsStatus()[$logisticsInfo -> status];


        $info = LogisticsInfo::where('logistics_id',$request -> id) -> get();
        foreach($info as $k => $vo){
            $goods_info = DB::table('erp_logistics_info')
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
                -> select([
                    'erp_product_list.*',
                    'product_class.name as product_class_name',
                    'product_brand.name as product_brand_name',
                    'product_series.name as product_series_name',
                    'erp_purchase_order.order_num', //采购编号
                    'erp_purchase_order_goods.number as  goods_number', //数量
                    'erp_purchase_order_goods.deliver_number', //发货数量
                    'erp_purchase_order_goods.scrap_num', //报废数量
                    'erp_purchase_order_goods.receive_num', //入库数量
                ])
                -> where('erp_logistics_info.id',$vo -> id)
                -> first();
            $goods_info -> last_notsend = intval($goods_info -> deliver_number) - intval($goods_info -> scrap_num) - intval($goods_info -> receive_num);
            $info[$k] -> goods_info = $goods_info;
        }
        $logisticsInfo -> orderInfo = $info;
        return $logisticsInfo;

    }


    //港口收货列表 收货操作
    public function receiveLogistics(Request $request){
        //更新 logistics 状态
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
            'id' => 'required|numeric|exists:erp_logistics,id',
            'receive_date' => 'required|date',
            'scrap_arr' => 'required|json', //维护  goods_id 报废数量，参数 info_id，scrap_num
            //'scrap_arr.*.info_id' => 'required|distinct|exists:erp_logistics_info,id',
            //'scrap_arr.*.scrap_num' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $info = Logistics::where([
            'id' => $request -> id,
            'business_id' => $request -> business_id
        ]) -> first();
        if(!$info){
            return [
                'code' => 500,
                'msg' => '数据错误'
            ];
        }
        if($info -> status == 1){
            return [
                'code' => 500,
                'msg' => '已收货'
            ];
        }

        Logistics::where([
            'id' => $request -> id,
            'business_id' => $request -> business_id
        ]) -> update([
            'status' => 1,
            'receive_date' => strtotime($request -> receive_date),
            'updated_at' => time()
        ]);

        //收货 把logistics_info 的 scrap_num 报废数量
        $scrap_arr = json_decode($request -> scrap_arr,true);
        foreach($scrap_arr as $vo){
            $loginfo = LogisticsInfo::find($vo['info_id']);
            LogisticsInfo::where([
                'id' => $vo['info_id']
            ]) -> update([
                'scrap_num' => $vo['scrap_num'],
                'updated_at' => time()
            ]);

            //物流单号报废了 跟 采购单反映下
            PurchaseOrderGoods::where([
                'id' => $loginfo -> goods_id
            ]) -> increment('scrap_num',$vo['scrap_num']);

        }


        return [
            'code' => 200,
            'msg' => '收货成功'
        ];
    }


    //仓库需要收货 列表
    public function receiveWareHouseList(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $list = Logistics::where(function($query)use($request){
                //货运编号
                if($request -> logistics_num){
                    $query -> where('logistics_num',trim($request -> logistics_num));
                }

                //采购单号
                if($request -> order_num){
                    //通过筛选采购单号
                    $order_info = PurchaseOrder::where('order_num',trim($request -> order_num)) -> first();
                    if($order_info){
                        $query -> where('order_id',$order_info -> id);
                    }
                }

                //发货日期
                if($request -> send_date_left){
                    $query -> where('send_date','>=',strtotime($request -> send_date_left));
                }
                if($request -> send_date_right){
                    $query -> where('send_date','<=',strtotime($request -> send_date_right));
                }

                //收货港口
                if($request -> destination_port_id){
                    $query -> where('destination_port_id',$request -> destination_port_id);
                }


            })
            -> where('business_id',$request -> business_id)
            -> where('status',1)
            -> where('flag',0)
            -> orderBy('id','desc')
            -> paginate(isset($request -> per_page)?$request -> per_page:20);

        $currency = Configure::getCurrency();
        $freightType = Configure::freightType();
        $status = Configure::getLogisticsStatus();
        foreach($list as $k => $vo){
            $list[$k] -> currency_str = $currency[$vo -> currency];
            $port_info = Port::find($vo -> destination_port_id);
            if($port_info){
                $list[$k] -> destination_port_str = $port_info -> name;
            }else{
                $list[$k] -> destination_port_str = '';
            }
            $start_port_info = Port::find($vo -> start_port_id);
            if($start_port_info){
                $list[$k] -> start_port_str = $start_port_info -> name;
            }else{
                $list[$k] -> start_port_str = '';
            }
            $list[$k] -> freight_type_str = $freightType[$vo -> freight_type];
            $list[$k] -> status_str = $status[$vo -> status];


            //运输费用
            $list[$k] -> totalnum = round(floatval($vo -> price)+floatval($vo -> freight) + floatval($vo -> substitute),2);
        }

        return $list;
    }





    //仓库收货 详情
    public function receiveWareHouseInfo(Request $request){

        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
            'id' => 'required|numeric|exists:erp_logistics,id'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $data = Logistics::where([
            'id' => $request -> id,
            'business_id' => $request -> business_id
        ]) -> first();
        if($data){
            $currency = Configure::getCurrency();
            $freightType = Configure::freightType();
            $status = Configure::getLogisticsStatus();
            $data -> currency_str = $currency[$data -> currency];
            $data -> freight_type_str = $freightType[$data -> freight_type];
            $data -> status_str = $status[$data -> status];
            $port_info = Port::find($data -> destination_port_id);
            if($port_info){
                $data -> destination_port_str = $port_info -> name;
            }else{
                $data -> destination_port_str = '';
            }

            //$info = LogisticsInfo::where('logistics_id',$request -> id) -> get();
            $info = DB::table('erp_logistics_info')
                -> leftJoin('erp_purchase_order_goods as goods','erp_logistics_info.goods_id','goods.id')
                -> leftJoin('erp_purchase_order as order','goods.order_id','order.id')


                -> leftJoin('erp_product_list','goods.product_id','erp_product_list.id')
                //商品去找类别、品牌、系列
                //类别
                -> leftJoin('erp_product_class as product_class','erp_product_list.class_id','product_class.id')
                //品牌
                -> leftJoin('erp_product_class as product_brand','erp_product_list.brand_id','product_brand.id')
                //系列
                -> leftJoin('erp_product_class as product_series','erp_product_list.series_id','product_series.id')
                -> select([
                    'order.order_num', //采购单编号
                    'erp_logistics_info.id',
                    'erp_logistics_info.number as sum_number', //总数量
                    'erp_logistics_info.scrap_num as scrap_num', //总报废数量
                    'erp_logistics_info.receive_num as receive_num', //仓库收到数量
                    'erp_logistics_info.deliver_number as delivernum', //已发出数量
                    'erp_product_list.product_name',
                    'erp_product_list.model',
                    'erp_product_list.product_no',
                    'erp_product_list.number as product_num', //商品数量
                    'product_class.name as product_class_name',
                    'product_brand.name as product_brand_name',
                    'product_series.name as product_series_name',

                ])
                -> where([
                    'logistics_id' => $request -> id
                ]) -> get();

            foreach($info as $k => $vo){
                //剩余数量
                $info[$k] -> lastnum = intval($vo -> sum_number) - intval($vo -> scrap_num) - intval($vo -> receive_num) - intval($vo -> delivernum);
            }


            $data -> orderInfo = $info;

            return $data;

        }else{
            return [
                'code' => 500,
                'msg' => '没有此条数据'
            ];
        }
    }


    //仓库收货时 判断
    function checkFromLogisticsInfo($logistics_info,$currency){

        $transfer_from_ids = [];
        $logistics_ids = [];
        foreach($logistics_info as $vo){
            if($vo -> transfer_from_id){
                $transfer_from_ids[] = $vo -> transfer_from_id;
            }
            if(!in_array($vo -> logistics_id,$logistics_ids)){
                $logistics_ids[] = $vo -> logistics_id;
            }
        }
        Log::info(json_encode($transfer_from_ids));
        Log::info(json_encode($logistics_ids));
        if(count($transfer_from_ids)){

            /*
            //直接判断 订单是否支付
            foreach($logistics_ids as $value_temp_id){
                $logistics_temp = DB::table('erp_logistics') -> where([
                    'id' => $value_temp_id
                ]) -> first();
                Log::info($logistics_temp -> currency);
                if($logistics_temp -> currency != $currency && $logistics_temp -> pay_status != 2){
                    return true;
                }
            }
            */


            foreach($transfer_from_ids as $value){
                $logistics_info_temp = DB::table('erp_logistics_info') -> where([
                    'id' => $value
                ]) -> get();
                $res = $this -> checkFromLogisticsInfo($logistics_info_temp,$currency);
                if($res){
                    return true;
                }
            }
        }else{
            //说明他是转运开始单
            //直接判断 订单是否支付

            foreach($logistics_ids as $value_temp_id){
                $logistics_temp = DB::table('erp_logistics') -> where([
                    'id' => $value_temp_id
                ]) -> first();
                if($logistics_temp -> currency != $currency && $logistics_temp -> pay_status != 2 ){
                    return true;
                }
            }

        }
    }


    //仓库收货 操作
    //status 1 => 2
    public function receiveWareHouse(Request $request){
        $validator = Validator::make($request->all(), [
//            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
            'id' => 'required|numeric|exists:erp_logistics,id', //物流单号
            'storehouse_id' => 'required|numeric:erp_storehouse,id', //库位id
            'extras' => 'required|numeric', //杂费
            'receive_num_json' => 'required|json', //入库json数组  [{"info_id":"2","receive_num":"34"},{}]    //receiveWareHouseInfo 返回的  的 erp_logistics_info 的 id , 入库数量

        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $data = Logistics::find($request -> id);

        if(!$data){
            return [
                'code' => 500,
                'msg' => '没有此条数据'
            ];
        }

        if($data -> status != 1){
            return [
                'code' => 500,
                'msg' => '不可操作'
            ];
        }


        //看下这条物流单有没有支付 没有支付不允许操作
        $error = '';

        //查看此事业部的本位币
        $business_info = DB::table('erp_business')
            -> where([
                'id' => $request -> business_id
            ]) -> first();
        if($business_info -> currency != $data -> currency && $data -> pay_status != 2){
            $error .= ' 该物流单未支付 ';
        }

        if(!$error){
            //往前翻 看她是不是转运订单 如果是 看下他的上一单 是不是外汇，如果是外汇 则必须支付
            $logistics_info = DB::table('erp_logistics_info') -> where([
                'logistics_id' => $request -> id
            ]) -> get();





            $res = $this -> checkFromLogisticsInfo($logistics_info,$business_info -> currency);
            if($res){
                $error .= ' 该物流单未支付 ';
            }
        }

        //判断采购单是否支付
        $erp_logistics_info = DB::table('erp_logistics_info')
            -> where([
                'logistics_id' => $request -> id,
                'flag' => 0
            ]) -> get();
        //取出此物流单所有的goods_id
        $goods_ids = [];
        foreach($erp_logistics_info as $vo){
            $goods_ids[] = $vo ->goods_id;
        }

        $erp_purchase_order_goods = DB::table('erp_purchase_order_goods')
            -> whereIn('id',$goods_ids)
            -> groupBy([
                'order_id'
            ]) -> get([
                'order_id'
            ]);

        foreach($erp_purchase_order_goods as $vo){
            $order = DB::table('erp_purchase_order')
                -> where([
                    'id' => $vo -> order_id
                ]) -> first();
            if($order -> pay_status != 2 && $business_info -> currency != $order -> currency){
                $error .= ' 该采购单未支付 ';

            }
        }

        if($error){
            return [
                'code' => 500,
                'msg' => $error
            ];
        }



        //status 1 => 2
        //检查json数组 这单是否有这些商品 info_id 是否重复
        $info_arr = json_decode($request -> receive_num_json,true);
        if(!is_array($info_arr) || !count($info_arr)){
            return [
                'code' => 500,
                'msg' => 'json为空'
            ];
        }
        $temp_info_ids = [];
        foreach($info_arr as $vo){
            if(!isset($vo['info_id']) || !isset($vo['receive_num']) || !$vo['info_id'] || !$vo['receive_num']){
                return [
                    'code' => 500,
                    'msg' => 'json参数格式错误'
                ];
            }
            if(in_array($vo['info_id'],$temp_info_ids)){
                return [
                    'code' => 500,
                    'msg' => 'info_id重复'
                ];
            }else{
                $temp_info_ids[] = $vo['info_id'];
            }
            //看下 此id 是否存在
            $loginfo = LogisticsInfo::find($vo['info_id']);
            if(!$loginfo || $loginfo -> logistics_id != $request -> id){
                return [
                    'code' => 500,
                    'msg' => 'info_id错误'
                ];
            }

            //总数量 - 已报废 = 剩余数量
            if(intval($loginfo -> number) - intval($loginfo -> scrap_num) < intval($vo['receive_num'])){
                return [
                    'code' => 500,
                    'msg' => 'receive_num错误'
                ];
            }
        }

        //校验成功
        DB::beginTransaction();
        try{




            //生成入库单 关联 物流单
            $user = auth('api')->user();
            $receive_id = ReceiveRecord::addRecord($request,$user -> id);
            if($receive_id){


                $status = 2; //'1' => '港口收货，仓库未收货', '2' => '仓库已收货',
                foreach($info_arr as $vo){

                    //增加收货数量
                    LogisticsInfo::where([
                        'id' => $vo['info_id']
                    ]) -> increment('receive_num',intval($vo['receive_num']));

                    $logistics_info = LogisticsInfo::find($vo['info_id']);

                    //总数量 - 报废数量 - 已收货数量 == 此次收货数量
                    //只要又一次不等 就是未收货
                    if(intval($logistics_info -> number) - intval($logistics_info -> scrap_num) <> intval($logistics_info -> receive_num)){
                        $status = 1;
                    }

                }

                //修改物流单的状态
                //修改 Logistics::find($request -> id); 的状态
                Logistics::where([
                    'id' => $request -> id
                ]) -> update([
                    'status' => $status
                ]);

                //收货之后，检测采购单是否全部收货，是的话更新采购单状态为:发货完成已入库
                $this->updatePurchaseOrderStatus($request -> id,$receive_id);

                //添加库存
                Stock::addStock($request,$receive_id);
                DB::commit();
                return [
                    'code' => 200,
                    'msg' => '收货操作成功'
                ];
            }else{
                DB::rollBack();
                return [
                    'code' => 500,
                    'msg' => '生成入货单失败'
                ];
            }
        }catch (\Exception $exception){
            DB::rollBack();
            return [
                'code' => 500,
                'msg' => $exception ->getMessage()
            ];
        }





    }


    //删除物流单号 = 港口收货删除
    public function deleteLogistics(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
            'id' => 'required|numeric|exists:erp_logistics,id'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }



        $log_info = Logistics::where([
            'business_id' => $request -> business_id,
            'id' => $request -> id,
            'flag' => 0
        ]) -> first();


        if(!$log_info){
            return [
                'code' => 500,
                'msg' => '没有此数据'
            ];
        }

        if($log_info -> status == 2){
            return [
                'code' => 500,
                'msg' => '已做入库，请先删除入库记录'
            ];
        }






        //删除港口物流订单 把物流订单已发的 退回去
        $logistics_info = DB::table('erp_logistics_info')
            -> where([
                'logistics_id' => $request -> id,
                'flag' => 0
            ]) -> get();

        //有receive_num 不允许删除
        foreach($logistics_info as $vo){
            if($vo -> receive_num){
                return [
                    'code' => 500,
                    'msg' => '仓库已收货、不允许删除'
                ];
            }
        }

        //检验， 如果 erp_logistics_info 中 含有
        foreach($logistics_info as $vo){
            //看 这id 有没有在别的 logistics_info中出现
            $temp_from = DB::table('erp_logistics_info')
                -> where([
                    'transfer_from_id' => $vo -> id,
                    'flag' => 0
                ]) -> first();
            if($temp_from){
                return [
                    'code' => 500,
                    'msg' => '这是转运中途订单，不允许删除'
                ];
            }
        }

        DB::beginTransaction();
        try{
            //全部的 erp_purchase_order id
            $erp_purchase_order_ids = [];
            //首先把港口收货的 都退回采购单
            foreach($logistics_info as $vo){
                $erp_purchase_order_goods = DB::table('erp_purchase_order_goods')
                    -> where([
                        'id' => $vo -> goods_id
                    ]) -> first();
                if($erp_purchase_order_goods){

                    //只有转运单第一单才需要判断
                    if(!$vo -> transfer_from_id){
                        DB::table('erp_purchase_order_goods')
                            -> where([
                                'id' => $vo -> goods_id
                            ]) -> update([
                                'deliver_number' =>  intval($erp_purchase_order_goods -> deliver_number) - intval($vo -> number),
                                'scrap_num' => intval($erp_purchase_order_goods -> scrap_num) -intval($vo -> scrap_num)
                            ]);
                    }



                    if(!in_array($erp_purchase_order_goods -> order_id,$erp_purchase_order_ids)){
                        $erp_purchase_order_ids[] = $erp_purchase_order_goods -> order_id;
                    }

                }

            }


            //把这些采购单的状态  都改为发货中 或者 已保存未发货
            foreach($erp_purchase_order_ids as $vo){
                $erp_purchase_order_temp = DB::table('erp_purchase_order_goods')
                    -> where([
                        'order_id' => $vo,
                        'flag' => 0
                    ]) -> get();

                //已保存未发货
                $order_status = 1;
                foreach($erp_purchase_order_temp as $value){
                    if($value -> deliver_number > 0){
                        $order_status = 2;
                        break;
                    }
                }

                DB::table('erp_purchase_order')
                    -> where([
                        'id' => $vo
                    ]) -> update([
                        'order_status' => $order_status
                    ]);
            }


            $temp_goods_id = [];
            //往前追溯 $logistics_info 判断途中  每单的状态
            foreach($logistics_info as $k => $vo){
                //看下这个 $vo -> id 是哪个单子的 transfer_from_id
                if($vo -> transfer_from_id){
                    $this -> checkLastLogistics($vo -> transfer_from_id,$vo -> number,$vo -> scrap_num);
                }

                if(!in_array($vo -> goods_id,$temp_goods_id)){
                    $temp_goods_id[] = $vo -> goods_id;
                }

            }


            //退完之后， 删除
            Logistics::where([
                'id' => $request -> id,
            ]) -> update([
                'flag' => 1,
                'updated_at' => time(),
                'is_transfer' => 0
            ]);


            //删除erp_logistics_info  之后 更新采购单状态

            $erp_purchase_order_goods = DB::table('erp_purchase_order_goods')
                -> whereIn('id',$temp_goods_id)
                -> where([
                    'flag' => 0
                ]) -> get();

            $temp_order_ids = [];
            foreach($erp_purchase_order_goods as $vo){
                if(!in_array($vo -> order_id,$temp_order_ids)){
                    $temp_order_ids[] =  $vo -> order_id;
                }
            }

            foreach($temp_order_ids as $vo){
                PurchaseOrder::checkOrderStatus($vo);
            }

            DB::table('erp_logistics_info')
                -> where([
                    'logistics_id' => $request -> id,
                ]) -> update([
                    'flag' => 1
                ]);


            DB::commit();
            return [
                'code' => 200,
                'msg' => '删除成功'
            ];
        }catch (\Exception $exception){
            DB::rollBack();
            return [
                'code' => 500,
                'msg' => '删除失败'
            ];
        }


    }


    //递归 判断  删除每个运单之后 trans_from_id 的情况
    //删除运单详情，除了要判断 上一单的状态， 还需要把转运数量 退回到上一单 from
    /**
     * @param $logistics_info_id transfer_from_id 转运单来自上个的erp_logistics_info id
     * @param $number 退回的数量
     * @param $scrap_num 报废的数量
     * @return bool
     */
    public function checkLastLogistics($logistics_info_id,$number,$scrap_num){
        if($logistics_info_id){

            //查找上一个 logistics_info_id
            $transfer_from_info = DB::table('erp_logistics_info')
                -> where([
                    'id' => $logistics_info_id,
                ]) -> first();
            if($transfer_from_info){
                DB::table('erp_logistics_info') -> where([
                    'id' => $logistics_info_id,
                ]) -> update([
                    'number' => intval($transfer_from_info -> number) + intval($number),
                ]);


                //通过 $logistics_info 修改 logistics 的状态
                $erp_logistics_info = DB::table('erp_logistics_info') -> where([
                    'logistics_id' => $transfer_from_info -> logistics_id,
                ]) -> get();


                //查看 number
                if(count($erp_logistics_info)){
                    //默认未收货
                    $logistics_status = 0;
                    $temp_transfer_from_ids = [];
                    $temp_transfer_from_ids_number = [];
                    $temp_goods_id = [];

                    foreach($erp_logistics_info as $value){
                        if($value -> transfer_from_id){
                            if(!in_array($value -> transfer_from_id,$temp_transfer_from_ids)){
                                $temp_transfer_from_ids[] = $value -> transfer_from_id;
                                $temp_transfer_from_ids_number[] = $value -> number;
                            }
                        }

                        if(!in_array($value -> goods_id,$temp_goods_id)){
                            $temp_goods_id[] = $value -> goods_id;
                        }

                    }


                    foreach($erp_logistics_info as $value){
                        //已发货数量 = 仓库收货数量
                        if($value -> deliver_number){
                            //如果有已发货数量 则 状态改为 港口收货，仓库未收货
                            $logistics_status = 1;
                            break;
                        }
                    }



                    //erp_logistics flag = 0 解释下：生成转运订单的时候 ，如果母单全部生成了转运订单，我就把母单删除了
                    if(!$logistics_status){
                        //说明这个运单  是未收货
                        DB::table('erp_logistics') -> where([
                            'id' => $transfer_from_info -> logistics_id
                        ]) -> update([
                            //'status' => $logistics_status,
                            'status' => 1, //他的转运来源 都应该是港口到货状态的
                            'flag' => 0
                        ]);
                    }else{
                        $logistics_status = 2;
                        //说明是 港口收货状态 再判断下 是否是仓库收货状态
                        foreach($erp_logistics_info as $value){
                            if($value -> deliver_number != $value -> receive_num){
                                $logistics_status = 1;
                                break;
                            }
                        }
                        DB::table('erp_logistics') -> where([
                            'id' => $transfer_from_info -> logistics_id
                        ]) -> update([
                            'status' => $logistics_status,
                            'flag' => 0
                        ]);
                    }
                    DB::table('erp_logistics_info') -> where([
                        'logistics_id' => $transfer_from_info -> logistics_id
                    ]) -> update([
                        'flag' => 0
                    ]);

                    //如果 这单上边 还有转运单 则递归继续判断
                    if(count($temp_transfer_from_ids)){
                        foreach($temp_transfer_from_ids as $key_temp => $value_temp){
                            $this -> checkLastLogistics($value_temp,$temp_transfer_from_ids_number[$key_temp]);
                        }
                    }else{
                        /*
                        //更新这单的 采购单状态  *****
                        if(count($temp_goods_id)){
                            $erp_purchase_order_goods = DB::table('erp_purchase_order_goods')
                                -> whereIn('id',$temp_goods_id)
                                -> where([
                                    'flag' => 0
                                ])
                                -> get();
                            if(count($erp_purchase_order_goods)){
                                $temp_order_ids = [];
                                foreach($erp_purchase_order_goods as $vo){
                                    if(!in_array($vo -> order_id,$temp_order_ids)){
                                        $temp_order_ids[] =  $vo -> order_id;
                                    }
                                }

                                foreach($temp_order_ids as $vo){

                                }


                            }
                        }


                        foreach($temp_goods_id as $vo){

                        }
                        */
                    }


                }
            }
            return true;
        }else{
            return true;
        }
    }



    //物流订单 付款列表
    public function payLogisticsList(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $list = DB::table('erp_logistics')
            //-> leftJoin('erp_purchase_order as order','erp_logistics.order_id','order.id')
            -> leftJoin('erp_port as fahuo_port','fahuo_port.id','erp_logistics.start_port_id')
            -> leftJoin('erp_port as mudi_port','erp_logistics.destination_port_id','mudi_port.id')
            -> select([
                'erp_logistics.*',
                'fahuo_port.name as fahuo_gangkou', //发货港口
                'mudi_port.name as mudi_gangkou', //目的港口
            ])
            -> where([
                'erp_logistics.business_id' => $request -> business_id,
            ]) -> where(function($query){
                $query -> where('erp_logistics.flag' , 0) -> orWhere('is_transfer',2);
            })
            -> where(function($query)use($request){
                if($request -> logistics_num){
                    $query -> where('erp_logistics.logistics_num',$request -> logistics_num);
                }
                if($request -> send_date_left){
                    $query -> where('erp_logistics.send_date','>=',strtotime($request -> send_date_left));
                }
                if($request -> send_date_right){
                    $query -> where('erp_logistics.send_date','<=',strtotime($request -> send_date_right));
                }
                if($request -> start_port_id){
                    $query -> where('erp_logistics.start_port_id',$request -> start_port_id);
                }
                if($request -> destination_port_id){
                    $query -> where('erp_logistics.destination_port_id',$request -> destination_port_id);
                }
                //运输状态
                if(isset($request -> delivery_status)){
                    if($request -> delivery_status == 1){
                        //已收货
                        $query -> where('erp_logistics.status','>',0);
                    }else{
                        //默认 未收货
                        $query -> where('erp_logistics.status',0);
                    }
                }

                //付款状态
                if($request -> pay_status == 1){
                    //付款已完成
                    $query -> where('erp_logistics.pay_status',2);
                }else{
                    $query -> where('erp_logistics.pay_status','<>',2);
                }
                //币种
                if($request -> currency){
                    $query -> where('erp_logistics.currency',$request -> currency);
                }


            })
            -> orderBy('erp_logistics.id','desc')
            -> paginate(isset($request -> per_page)?$request -> per_page:20);

        //运费类型
        $freight_type = Configure::freightType();
        //币种
        $currency = Configure::getCurrency();

        $pay_status = Configure::getOrderPayStatus();

        foreach($list as $k => $vo){
            $list[$k] -> currency_str = $currency[$vo -> currency];
            $list[$k] -> freight_type_str = $freight_type[$vo -> freight_type];
            $list[$k] -> pay_status_str = $pay_status[$vo -> pay_status]; //支付状态
        }

        return $list;

    }


    //物流订单支付详情
    public function payLogisticsInfo(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
            'id' => 'required|numeric|exists:erp_logistics',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $logisticsInfo = DB::table('erp_logistics')
            -> leftJoin('erp_port as fahuo_port','fahuo_port.id','erp_logistics.start_port_id')
            -> leftJoin('erp_port as mudi_port','erp_logistics.destination_port_id','mudi_port.id')
            -> select([
                'erp_logistics.*',
                'fahuo_port.name as fahuo_gangkou', //发货港口
                'mudi_port.name as mudi_gangkou', //目的港口
            ])
            -> where([
                'erp_logistics.id' => $request -> id
            ]) -> first();

        //运费类型
        $freight_type = Configure::freightType();
        //币种
        $currency = Configure::getCurrency();

        $logisticsInfo -> freight_type_str = $freight_type[$logisticsInfo -> freight_type];
        $logisticsInfo -> currency_str = $currency[$logisticsInfo -> currency];

        //应付金额
        $logisticsInfo -> needpay = $this->mathRepository->math_add($logisticsInfo -> transportationCost, $logisticsInfo -> incidental);
        //未付金额
        $logisticsInfo -> notpay_price = $this->mathRepository->math_sub($logisticsInfo -> needpay, $logisticsInfo -> pay_price);


        //查看运单明细
        $logisticsDetail = DB::table('erp_logistics_info')
            -> leftJoin('erp_purchase_order_goods as goods','erp_logistics_info.goods_id','goods.id')
            -> leftJoin('erp_purchase_order as order','goods.order_id','order.id')


            -> leftJoin('erp_product_list','goods.product_id','erp_product_list.id')
            //商品去找类别、品牌、系列
            //类别
            -> leftJoin('erp_product_class as product_class','erp_product_list.class_id','product_class.id')
            //品牌
            -> leftJoin('erp_product_class as product_brand','erp_product_list.brand_id','product_brand.id')
            //系列
            -> leftJoin('erp_product_class as product_series','erp_product_list.series_id','product_series.id')
            -> select([
                'order.order_num', //采购单编号
                'erp_logistics_info.id',
                'erp_logistics_info.price',
                'erp_logistics_info.number as sum_number', //总数量
                'erp_logistics_info.scrap_num as scrap_num', //总报废数量
                'erp_logistics_info.receive_num as receive_num', //仓库收到数量
                'erp_product_list.product_name',
                'erp_product_list.model',
                'erp_product_list.product_no',
                'product_class.name as product_class_name',
                'product_brand.name as product_brand_name',
                'product_series.name as product_series_name',
                'order.weight_rate as add_rate',
                'order.pay_price as pay_base'

            ])
            -> where([
                'erp_logistics_info.logistics_id' => $logisticsInfo -> id
            ])

            -> get();

        //付款记录
        $pay_record = DB::table('erp_purchase_order_pay')
            -> leftJoin('erp_account','erp_purchase_order_pay.account_id','erp_account.id')
            -> leftJoin('users','erp_purchase_order_pay.pay_user_id','users.id')
            -> where([
                'erp_purchase_order_pay.flag' => 0,
                'erp_purchase_order_pay.order_id' => $logisticsInfo -> id,
                'erp_purchase_order_pay.pay_type' => 1
            ])
            -> select([
                'erp_purchase_order_pay.*',
                'erp_purchase_order_pay.remark',
                'erp_account.account_name',
                'users.name as pay_person',
            ])
            -> get();


        return [
            'logistics_info' => $logisticsInfo,
            'logistics_detail' => $logisticsDetail,
            'pay_record' => $pay_record
        ];

    }

    public function addPayRecord(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
            'account_id' => 'required|numeric|exists:erp_account,id', //账户id
            'pay_amount' => 'required|numeric|min:0.01', //付款金额
            'pay_record_ids' => 'required|json', //充值记录id addPayInfo 返回的id [1,3,6]
            'logistics_id' => 'required|numeric|exists:erp_logistics,id'
            // 'service_charge' => '' 手续费
            //付款备注 remark
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }


        $order_info = Logistics::where([
            'id' => $request -> logistics_id,
        ])  -> where(function($query){
            $query -> where('flag',0) -> orWhere('is_transfer',2);
        })->  first();
        if(!$order_info){
            return [
                'code' => 500,
                'msg' => '没有此单号'
            ];
        }


        if((floatval($order_info -> incidental) + floatval($order_info -> transportationCost)) - floatval($order_info -> pay_price) < floatval($request -> pay_amount) ){
            return [
                'code' => 500,
                'msg' => '你这付款金额都大于运费金额了！'
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
        $pay_amount = floatval($request -> pay_amount) ;


        //已经扣的钱
        $payed_amount = 0;

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

                    $payed_amount_base += $payed_amount * floatval($record_info -> rate);

                    $record_price[] = $pay_amount;
                    $rate[] = $record_info -> rate;
                    $record_ids[] = $vo;

                    break;
                }


            }


            $user = auth('api')->user();
            foreach($record_price as $key => $vo_price){
                //添加总扣款记录

                $pay_id = DB::table('erp_purchase_order_pay')
                    -> insertGetId([
                        'order_id' => $request -> logistics_id,
                        'account_id' => $request -> account_id,
                        'pay_price' => $vo_price,
                        'pay_amount_base' => $vo_price * $rate[$key],
                        'service_charge' => $request -> service_charge,
                        'created_at' => time(),
                        'updated_at' => time(),
                        'pay_user_id' => $user -> id,
                        'pay_type' => 1, //运费订单付款
                    ]);


                Account::addLog([
                    'business_id' => $request -> business_id,
                    'account_id' => $request -> account_id,
                    'user_id' => $user -> id,
                    'log_type' => 3,
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
            DB::table('erp_logistics') -> where([
                'id' => $order_info -> id
            ]) -> increment('pay_price',$payed_amount);

            //更新 加权汇率

            //算下此订单 所有充值记录
            $record_price = DB::table('erp_purchase_order_pay')
                -> where([
                    'order_id' => $request -> logistics_id,
                    'pay_type' => 1,
                    'flag' => 0
                ]) -> sum('pay_price');
            $record_base = DB::table('erp_purchase_order_pay')
                -> where([
                    'order_id' => $request -> logistics_id,
                    'pay_type' => 1,
                    'flag' => 0
                ]) -> sum('pay_amount_base');
            $weight_rate = 1;
            if($record_price){
                $weight_rate = round(floatval($record_base)/floatval($record_price),5);
                DB::table('erp_logistics') -> where([
                    'id' => $order_info -> id
                ]) -> update([
                    'weight_rate' => $weight_rate
                ]);
            }



            if(floatval($order_info -> pay_price) + floatval($payed_amount) == floatval($order_info -> incidental) + floatval($order_info -> transportationCost) ){
                //标记为支付完成  pay_status = 2

                DB::table('erp_logistics') -> where([
                    'id' => $order_info -> id
                ]) -> update([
                    'pay_status' => 2,
                ]);

                //根据加权汇率 计算成本
                $erp_logistics_info = DB::table('erp_logistics_info') -> where([
                    'logistics_id' => $order_info -> id,
                    'flag' => 0
                ]) -> get();
                foreach($erp_logistics_info as $vo){
                    DB::table('erp_logistics_info') -> where([
                        'id' => $vo -> id
                    ]) -> update([
                        'cost' => round(floatval($vo -> cost)*$weight_rate,5)
                    ]);
                }




            }else{
                DB::table('erp_logistics') -> where([
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

    //撤销运费付款
    public function cancelPayRecord(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
            'id' => 'required|numeric'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $logistics = DB::table('erp_logistics')
            -> where([
                'id' => $request -> id,
                'business_id' => $request -> business_id,

            ]) -> where(function($query){
                $query -> where('flag',0) -> orWhere('is_transfer',2);
            }) -> first();


        if(!$logistics){
            return [
                'code' => 500,
                'msg' => '没有此运单'
            ];
        }


        //通过$logistics 找 $logistics_info
        $logistics_info = DB::table('erp_logistics_info') -> where([
            'logistics_id' => $request -> id,
            'flag' => 0
        ]) -> get();
        $logistics_info_goods_ids = [];
        foreach($logistics_info as $vo){
            $logistics_info_goods_ids[] = $vo ->goods_id;
        }



        //采购单下 有入库 则不允许删除
        $erp_purchase_order_goods = DB::table('erp_purchase_order_goods')
            -> where([
                'flag' => 0
            ])
            -> whereIn('id',$logistics_info_goods_ids) -> get();
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
                    'pay_type' => 1,
                    'flag' => 0
                ]) -> get();
            if(count($pay_info)){
                return $this -> cancelPublic($pay_info,$logistics,1);

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
                return $this -> cancelPublic($pay_record,$logistics,2);
            }


        }






    }

    //type = 1 全部撤销 type = 2 部分撤销
    private function cancelPublic($pay_info,$logistics,$type=1){
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
                        DB::table('erp_logistics') -> where([
                            'id' => $logistics -> id
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
            $logistics = DB::table('erp_logistics')
                -> where([
                    'id' => $logistics -> id
                ]) -> first();
            if($logistics -> pay_price){
                //部分付款
                $pay_status = 1;
            }else{
                //未付款
                $pay_status = 0;
            }


            DB::table('erp_logistics')
                -> where([
                    'id' => $logistics -> id
                ]) -> update([
                    'pay_status' => $pay_status,
                    'weight_rate' => 1
                ]);

            if($type == 2){
                //部分撤销，需要重新计算下 加权汇率
                $record_price = DB::table('erp_purchase_order_pay')
                    -> where([
                        'order_id' => $logistics -> id,
                        'pay_type' => 1,
                        'flag' => 0
                    ]) -> sum('pay_price');
                $record_base = DB::table('erp_purchase_order_pay')
                    -> where([
                        'order_id' => $logistics -> id,
                        'pay_type' => 1,
                        'flag' => 0
                    ]) -> sum('pay_amount_base');




                if($record_price){
                    DB::table('erp_logistics') -> where([
                        'id' => $logistics -> id
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


    //拆分地址
    public function getExtAddress(Request $request){
        $validator = Validator::make($request->all(), [
            'address' => 'required', //事业部id
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $address = $request -> address;
        $address = preg_replace('# #','',$address);
        $model = new MallApi();
        $res = $model -> getAddressBySF($address);
        return $res;
    }


    //港口转运 - 港口转运 需要 可转运订单=港口已收货
    public function portTransport(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $logistics = DB::table('erp_logistics')
            -> leftJoin('erp_port as start_port','erp_logistics.start_port_id','start_port.id')
            -> leftJoin('erp_port as end_port','erp_logistics.destination_port_id','end_port.id')
            -> where([
                'erp_logistics.business_id' => $request -> business_id,
                'erp_logistics.flag' => 0,
            ])
            -> select([
                'erp_logistics.*',
                'start_port.name as start_name',
                'end_port.name as current_name' //当前港口
            ])
            -> where(function($query)use($request){
                //采购编号
                if($request -> order_num){
                    //通过采购编号 查找采购订单id
                    $order = DB::table('erp_purchase_order')
                        -> where('order_num','like','%'.trim($request -> order_num).'%')
                        -> first();
                    if($order){
                        $order_goods_info = DB::table('erp_purchase_order_goods')
                            -> where([
                                'order_id' => $order -> id,
                                'flag' => 0
                            ]) -> first();
                        if($order_goods_info){
                            $logistics_info = DB::table('erp_logistics_info')
                                -> where([
                                    'flag' => 0,
                                    'goods_id' => $order_goods_info -> id
                                ]) -> first();
                            if($logistics_info){
                                $query -> where('erp_logistics.id',$logistics_info -> logistics_id);
                            }
                        }
                    }
                }
                //货运编号
                if($request -> trans_num){
                    $query -> where('erp_logistics.logistics_num','like','%'.trim($request -> trans_num).'%');
                }
                //实际运单号
                if($request -> true_num){
                    $query -> where('erp_logistics.true_num','like','%'.trim($request -> true_num).'%');
                }
                //发货日期
                if($request -> send_date_left){
                    $query -> where('erp_logistics.send_date','>=',strtotime($request -> send_date_left));
                }
                if($request -> send_date_right){
                    $query -> where('erp_logistics.send_date','<=',strtotime($request -> send_date_right));
                }

                //1:可转运订单  2:全部订单
                if($request -> order_status == 1){
                    //可转运
                    //港口到货
                    $query -> where('erp_logistics.status',1);
                }



            })
            -> orderBy('erp_logistics.id','desc')
            -> paginate(isset($request -> per_page)?$request -> per_page:20);
        $freight_type_config = Configure::freightType();
        foreach($logistics as $k => $vo){
            $logistics[$k] -> freight_type_str = $freight_type_config[$vo -> freight_type];
        }

        return $logistics;
    }

    //运单转运页面
    public function addPortTransport(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric', //事业部id
            'logistics_id_json' => 'required|json', //运单单号id  [1,3,4]

        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        //检查
        //运单单号集合
        $logistics_id_arr= json_decode($request -> logistics_id_json,true);
        if(!count($logistics_id_arr)){
            return [
                'code' => 500,
                'msg' => 'id的json格式有误'
            ];
        }

        //看下这些采购订单的到货港口 是不是一起的
        $logistics_ids = [];
        foreach($logistics_id_arr as $vo){
            $logistics_info = DB::table('erp_logistics')
                -> where([
                    'id' => $vo,
                    'flag' => 0
                ]) -> first();
            if(!$logistics_info){
                return [
                    'code' => 500,
                    'msg' => 'json参数中单号有误'
                ];
            }

            if($logistics_info -> status != 1){
                return [
                    'code' => 500,
                    'msg' => '运费订单状态有误，不允许转运'
                ];
            }


            if(in_array($vo,$logistics_ids)){
                return [
                    'code' => 500,
                    'msg' => '运费订单单号有重复'
                ];
            }else{
                $logistics_ids[] = $vo;
            }
        }






        //
    //返回选中采购单号的详情
        $order_detail = DB::table('erp_logistics_info')


            ->leftjoin('erp_purchase_order_goods as goods','erp_logistics_info.goods_id','goods.id')
            -> leftJoin('erp_purchase_order as order','goods.order_id','order.id')
            -> leftJoin('erp_product_list','goods.product_id','erp_product_list.id')
            //商品去找类别、品牌、系列
            //类别
            -> leftJoin('erp_product_class as product_class','erp_product_list.class_id','product_class.id')
            //品牌
            -> leftJoin('erp_product_class as product_brand','erp_product_list.brand_id','product_brand.id')
            //系列
            -> leftJoin('erp_product_class as product_series','erp_product_list.series_id','product_series.id')
            -> select([
                'order.order_num', //订单编号
                'erp_logistics_info.id',
                'goods.id as goods_id',
                'erp_logistics_info.number', //总数量
                'erp_logistics_info.receive_num', //已入库数量
                'erp_logistics_info.scrap_num', //报废数量   （剩余数量 = 总-已发货-已入库 -报废数量）

                'erp_product_list.product_name',
                'erp_product_list.model',
                'erp_product_list.product_no',
                'erp_product_list.id as product_id',
                'product_class.name as product_class_name',
                'product_brand.name as product_brand_name',
                'product_series.name as product_series_name',
                'erp_logistics_info.id as erp_logistics_info_id',
            ])
            -> whereIn('erp_logistics_info.logistics_id',$logistics_ids)
            -> where([
                'erp_logistics_info.flag' => 0
            ])
            -> get();

        return [
            'code' => 200,
            'data' => $order_detail
        ];

    }


    //运单转运提交
    public function addPortTransportRes(Request $request){
        $validator = Validator::make($request->all(), [
            //'logistics_id' => 'required|numeric', //需要货运订单id
            'business_id' => 'required|numeric', //事业部id
            //'true_num' => 'required', //真实单号非必填
            'destination_port_id' => 'required|numeric|exists:erp_port,id', //到货港口
            'freight_type' => 'required', //运费类型
            'currency' => 'required|numeric', //币种id
            'goods_list' => 'required|json',  //所需参数 (新增参数：之前所属物流单id - erp_logistics_info_id) ，deliver_number(发货数量)，price
            'send_date' => 'required|date', //发货日期

            //'base' => 'required|numeric', //体积基数  -> 非必填
            'transportationCost' => 'required|numeric', //运输费用
            'incidental' => 'required|numeric', //杂费
            'remark' => 'max:500' //备注
            //'order_id_json' => 'required|json', //采购订单id  [1,3,4]



            //'remark' => '',
            //'goods_list.*.goods_id' => 'required|exists:erp_product_list,id|distinct', //采购订单订单内的商品id
            //'goods_list.*.deliver_number' => 'required|numeric', //采购订单内 商品数量
            //'goods_list.*.price' => 'required|numeric', //采购订单内 单价

        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }


        //验证采购订单的状态
        //只能港口已收货的允许转运

        //每个订单的港口id  port_id 不能相同
        //校验 goods_list 的每个order_id
        $check_res = Logistics::checkTransferInfo($request);
        if($check_res){
            return $check_res;
        }




        //创建转运单
        $logistics_id = Logistics::addTransfer($request);
        if($logistics_id){
            return [
                'code' => 200,
                'msg' => '转运成功'
            ];
        }else{
            return [
                'code' => 500,
                'msg' => '转运失败'
            ];
        }



    }



    protected function updatePurchaseOrderStatus($logi_id,$receive_id)
    {
        //先获取该物流单包含的goods id
        $goods_ids=DB::table('erp_logistics_info')
            ->where('logistics_id',$logi_id)
            ->pluck('goods_id');

        //再获取采购单ids
        $purch_order_ids=DB::table('erp_purchase_order_goods')
            ->whereIn('id',$goods_ids)
            ->pluck('order_id')->unique();
        //再根据采购单查询是否已经收获完成并入库
        foreach ($purch_order_ids as $purch_order_id){
            //首先查询该采购单下的所有采购商品
            $goods=DB::table('erp_purchase_order_goods')
                ->where('flag',0)
                ->where('order_id',$purch_order_id)
                ->get();

            //检查每个采购商品是否已经全部收获，是的话就更新采购单状态
            $order_status=4;//发货完成已入库
            foreach ($goods as $good){
                //物流单详情ids
                $logistics_info_ids=DB::table('erp_logistics_info')
                    ->where('flag',0)
                    ->where('goods_id',$good->id)
                    ->pluck('id');

                //收货详数量的和
                $receive_goods_sum=DB::table('erp_receive_goods_record')
                    ->whereIn('goods_id',$logistics_info_ids)
                    ->where('flag',0)
                    ->sum('true_num');
                //报废的数量和
                $scrap_sum=DB::table('erp_logistics_info')
                    ->whereIn('id',$logistics_info_ids)
                    ->sum('scrap_num');
                //只要有一个采购goods没有收全，就不更新入库状态
                if ($good->number != ($receive_goods_sum + $scrap_sum)){
                    $order_status=3;//发货完成未入库
                    break;
                }
            }
            //更新状态
            if ($order_status == 4){
                DB::table('erp_purchase_order')
                    ->where('id',$purch_order_id)
                    ->update(['order_status'=>$order_status]);
            }
        }
    }



}
