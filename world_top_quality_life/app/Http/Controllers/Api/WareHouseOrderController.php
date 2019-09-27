<?php

namespace App\Http\Controllers\Api;

use App\Configure;
use App\NumberQueue;
use App\StockOrderInfo;
use App\PublicStockOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;


define('ReqURL','http://api.kdniao.com/api/EOrderService');
//define('ReqURL','http://api.kdniao.com/api/EOrderService');
class WareHouseOrderController extends Controller
{

    //仓库发货列表
    public function theList(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric', //事业部id
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        $user = auth('api')->user();

        $model_has_roles = DB::table('model_has_roles')
            -> leftJoin('roles','model_has_roles.role_id','roles.id')
            -> where([
                'model_has_roles.model_id' => $user -> id
            ])
            -> select([
                'roles.name as  roles_name'
            ])
            -> get();


        //是不是客服角色
        $is_kefu_roles = 0;
        if($model_has_roles){
            foreach($model_has_roles as $vo){
                if(strstr($vo -> roles_name,'客服')){
                    $is_kefu_roles = 1;
                    break;
                }
            }
        }

        //库存销售列表， 发货备注是 发货的 <> 2 的
        $list = DB::table('erp_stock_order as order')
            -> leftJoin('erp_warehouse','order.warehouse_id','erp_warehouse.id')
            -> leftJoin('wxuser','order.user_id','wxuser.id')
            -> leftJoin('users','order.operator_user_id','users.id')
            -> leftJoin('erp_business','order.business_id','erp_business.id')
            -> select([
                'order.*',
                'erp_warehouse.name as warehouse_name',
                'wxuser.nickname as nickname',
                'users.name as biller',
                'erp_business.name as business_name'
            ])

            -> where(function($query)use($is_kefu_roles,$user){
                if($is_kefu_roles){
                    $query -> where('order.operator_user_id',$user -> id);
                }
            })


            -> where(function($query)use($request,$user){
                if($request -> order_num){
                    $query -> where('order.order_num',$request -> order_num);
                }
                if($request -> sale_date_left){
                    $query -> where('order.sale_date','>=',strtotime($request -> sale_date_left));
                }
                if($request -> sale_date_right){
                    $query -> where('order.sale_date','<=',strtotime($request -> sale_date_right));
                }

                if($request -> user_id){
                    $query -> where('order.user_id',$request -> user_id);
                }

                if(isset($request -> send_status)){
                    if($request -> send_status == 0){
                        $query -> where('order.send_status','<>',1);
                    }else{
                        //（0:发货未完成、1:发货完成）
                        $query -> where('order.send_status',1);
                    }

                }


                if($request -> warehouse_id){
                    $query -> where('order.warehouse_id',$request -> warehouse_id);
                }
                if($request -> send_mark != ''){
                    Log::info($request -> send_mark);
                    //发货备注 0发货1不发货2预定
                    $query -> where('order.send_remark',$request -> send_mark);

                    /*
                    //非不发货
                    if($request -> send_mark == "3"){
                        $query -> where('order.send_remark',0) -> orWhere('order.send_remark',2);
                    }else{
                        $query -> where('order.send_remark',$request -> send_mark);
                    }
                    */

                }

                $super_send_order = config('admin.super_send_order');
                if(!in_array($user -> username,$super_send_order)){
                    $query -> where('order.business_id',$request -> business_id);
                }else{
                    $query -> whereIn('order.business_id',config('admin.super_send_order_business_id'));
                }


            })
            -> where([
            'order.flag' => 0 ,
            //'send_remark' => 0
        ])
            //-> whereIn('send_remark',[0,1])

            -> orderBy('order.id','desc')
            -> paginate(isset($request -> per_page)?$request -> per_page:20);


        foreach($list as $k => $vo){
            //0发货1不发货2预定
            if($vo -> send_remark == 2){
                $list[$k] -> send_remark_str = '预定';
            }elseif($vo -> send_remark == 1){
                $list[$k] -> send_remark_str = '不发货';
            }else{
                $list[$k] -> send_remark_str = '发货';
            }


            //send_status 发货状态 0未发货 1已发货 2发货中
            if($vo -> send_status == 2){
                $list[$k] -> order_status = '部分发货';
            }elseif($vo -> send_status == 1){
                $list[$k] -> order_status = '已发货';
            }else{
                $list[$k] -> order_status = '未发货';
            }

            //打印配货单
            if($vo -> print_distribution){
                $list[$k] -> distribute_order = '已打印';
            }else{
                $list[$k] -> distribute_order = '';
            }

            //打印面单
            if($vo -> print_express){
                $list[$k] -> express_order = '已打印';
            }else{
                $list[$k] -> express_order = '';
            }


            $list[$k] -> waittime = round((time() - intval($vo -> created_at))/60/60,2);

            //订单金额
            $list[$k] -> order_price = round(floatval($vo -> freight) + floatval($vo -> substitute) + floatval($vo -> price),2);

        }




        return $list;

    }

    //仓库发货详情
    public function sendOrderInfo(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric', //事业部id
            'id' => 'required|numeric|exists:erp_stock_order,id'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        //数据校验 查看此库存订单的状态
        $order_info = DB::table('erp_stock_order')
            -> leftJoin('erp_warehouse','erp_stock_order.warehouse_id','erp_warehouse.id')
            -> leftJoin('wxuser','erp_stock_order.user_id','wxuser.id')
            -> where([
            'erp_stock_order.id' => $request -> id,

        ])
            -> select([
                'erp_stock_order.*',
                'erp_warehouse.name as warehouse_name',
                'wxuser.nickname',
            ])
            -> first();



        if(!$order_info || $order_info -> flag == 1 ){
            return [
                'code' => 500,
                'msg' => '数据状态有误'
            ];
        }




        //展示仓库发货详情
        $stock_order_info = DB::table('erp_stock_order_info')
            -> leftJoin('erp_product_list','erp_stock_order_info.product_id','erp_product_list.id')
            //商品去找类别、品牌、系列
            //类别
            -> leftJoin('erp_product_class as product_class','erp_product_list.class_id','product_class.id')
            //品牌
            -> leftJoin('erp_product_class as product_brand','erp_product_list.brand_id','product_brand.id')
            //系列
            -> leftJoin('erp_product_class as product_series','erp_product_list.series_id','product_series.id')
            -> select([
                'erp_stock_order_info.*',
                'erp_product_list.product_name',
                'erp_product_list.model',
                'erp_product_list.product_no',
                'erp_product_list.number as product_num',
                'erp_product_list.weight as product_weight',
                'product_class.name as product_class_name',
                'product_brand.name as product_brand_name',
                'product_series.name as product_series_name',
            ])
            -> where([
                'erp_stock_order_info.stock_order_id' => $order_info -> id,
                'erp_stock_order_info.flag' => 0
            ])
            -> get();

        //动态计算 重量
        //$order_info -> weight

        $weight = 0;
        foreach($stock_order_info as $vo){
            $weight += floatval($vo -> product_weight) * intval($vo -> number);
        }
        if($weight <= 1){
            $weight += 0.2;
        }elseif($weight > 1 && $weight <= 2){
            $weight += 0.3;
        }else{
            $weight += 0.4;
        }

        $order_info -> weight = round($weight,2);




        //字段翻译
        //发货备注 0发货1不发货2预定
        switch ($order_info -> send_remark){
            case '0':$order_info -> send_remark_str =  '发货';break;
            case '1':$order_info -> send_remark_str =  '不发货';break;
            case '2':$order_info -> send_remark_str =  '预定';break;
        }



        //发放明细
        //按照入库顺序 展示每个商品的 发放明细
        $issue_details = DB::table('erp_stock_order_info_receive as order_receive')
            -> leftJoin('erp_stock_order_info','order_receive.stock_order_info_id','erp_stock_order_info.id')
            -> leftJoin('erp_receive_goods_record','receive_goods_record_id','erp_receive_goods_record.id')
            -> leftJoin('erp_receive_record','erp_receive_goods_record.receive_record_id','erp_receive_record.id')


            -> leftJoin('erp_product_list','erp_stock_order_info.product_id','erp_product_list.id')
            //商品去找类别、品牌、系列
            //类别
            -> leftJoin('erp_product_class as product_class','erp_product_list.class_id','product_class.id')
            //品牌
            -> leftJoin('erp_product_class as product_brand','erp_product_list.brand_id','product_brand.id')
            //系列
            -> leftJoin('erp_product_class as product_series','erp_product_list.series_id','product_series.id')

            -> leftJoin('erp_storehouse','erp_receive_goods_record.store_house_id','erp_storehouse.id')
            -> leftJoin('erp_warehouse','erp_receive_goods_record.warehouse_id','erp_warehouse.id')

            -> select([
                'erp_storehouse.name as erp_storehouse_name',
                'erp_warehouse.name as erp_warehouse_name',
                'erp_receive_record.receive_num as record_receive_num',

                'order_receive.id',
                'erp_product_list.product_name',
                'erp_product_list.model',
                'erp_product_list.product_no',
                'product_class.name as product_class_name',
                'product_brand.name as product_brand_name',
                'product_series.name as product_series_name',
                'order_receive.number as fafang_num', //仓库到货数量
                'order_receive.created_at as ruku_time', //入库时间
                'order_receive.send_num as receive_num', //已发出数量

            ])
            -> where('order_receive.flag',0)
            -> where('stock_order_id',$order_info -> id)
            -> get();

        foreach($issue_details as $k => $vo){
            $temp = intval($vo -> fafang_num) - intval($vo -> receive_num);
            $issue_details[$k] -> fafang_num = $temp;
            $issue_details[$k] -> last =       $temp;
            $issue_details[$k] -> ruku_time = date('Y-m-d H:i',$vo -> ruku_time);

        }


        //历史发货记录
        $history_record = DB::table('erp_deliver_goods_record') -> where([
            'stock_order_id' => $order_info -> id,
            'flag' => 0
        ])
            -> select([
                'express_html',
                'stock_order_no'
            ])
            -> get();



        return [
            'order' => $order_info, //发货详情
            'order_info' => $stock_order_info, //发货详情中 商品详情
            'issue_details' => $issue_details, //发货明细
            'history_record' => $history_record

        ];

    }


    //仓库发货提交处理
    public function sendOrderInfoRes(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric', //事业部id
            'stock_order_id' => 'required|numeric|exists:erp_stock_order,id',  //sendOrderInfo 中的 order -> id
            'company' => 'required|max:20', //物流公司名称
            'deliver_num' => 'required|max:50', //快递单号
            // 'deliver_remark'  => ''  物流备注
            //'deliver_price' => 'required|numeric', //运费
            //'user_remark' => '' 客户备注
            'weight' => 'required|numeric',  //重量
            'record_json' => 'required|json'// 发货明细  [{"info_id":"2","send_num":"4"}]  info_id, sendOrderInfo issue_details -> id
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }


        //校验
        $record_arr = json_decode($request -> record_json,true);
//        if(!count($record_arr)){
//            return [
//                'code' => 500,
//                'msg' => 'json参数必传'
//            ];
//        }

        if(count($record_arr)) {
            $temp_info_ids = [];
            foreach ($record_arr as $vo) {
                if (!isset($vo['info_id']) || !isset($vo['send_num']) || !$vo['info_id'] || !$vo['send_num']) {
                    return [
                        'code' => 500,
                        'msg' => 'json参数有误'
                    ];
                }
                if (in_array($vo['info_id'], $temp_info_ids)) {
                    return [
                        'code' => 500,
                        'msg' => 'json参数中info_id重复'
                    ];
                }

                //检验 erp_stock_order_info_receive
                $receive_info = DB::table('erp_stock_order_info_receive')
                    ->where('id', $vo['info_id'])
                    ->where('flag', 0)
                    //-> where('stock_order_info_id',$request -> stock_order_id)
                    ->first();

                if (!$receive_info) {
                    return [
                        'code' => 500,
                        'msg' => 'json参数中info_id有误'
                    ];
                }


                $stock_order_info_temp = DB::table('erp_stock_order_info')->where([
                    'id' => $receive_info->stock_order_info_id
                ])->first();

                if (intval($stock_order_info_temp->number) - intval($stock_order_info_temp->send_num) < intval($vo['send_num'])) {
                    return [
                        'code' => 500,
                        'msg' => '发货数量有误'
                    ];
                }


                $temp_info_ids[] = $vo['info_id'];
            }
        }


        //校验成功
        DB::beginTransaction();
        try{

            $count_erp_deliver_goods_record = DB::table('erp_deliver_goods_record')
                -> where([
                    'stock_order_id' => $request -> stock_order_id,
                    'flag' => 0
                ]) -> count();
            $order_info = DB::table('erp_stock_order') -> where([
                'id' => $request -> stock_order_id
            ]) -> first();
            if($order_info->relate_purchase_order){
                //自动流程处理  处理该销售单对应的采购单 及关联管理
                PublicStockOrder::publicOrderSend($order_info);
            }
            $order_number = $order_info -> order_num . '-' .(intval($count_erp_deliver_goods_record) + 1);
            $user = auth('api')->user();
            $record_id = DB::table('erp_deliver_goods_record') -> insertGetId([
                'record_num' => NumberQueue::addQueueNo(4),
                'stock_order_id' => $request -> stock_order_id, //销售订单id
                'send_user_id' => $user -> id,
                'business_id' => $order_info -> business_id,
                'company' => $request -> company,
                'deliver_num' =>  $request -> deliver_num,
                'deliver_remark' =>  $request -> deliver_remark,
                'deliver_price' =>  $request -> deliver_price,
                'user_remark' =>  $request -> user_remark,
                'weight' => $request -> weight,
                'created_at' => time(),
                'updated_at' => time(),
                'stock_order_no' => $order_number,
                'express_html' => $request -> express_html
            ]);


            //判断 此订单 有没有发货记录。 如果有发货记录 则 运费累加 如果没有 则运费更新
            $erp_deliver_goods_record = DB::table('erp_deliver_goods_record') -> where([
                'stock_order_id' => $request -> stock_order_id,
                'flag' => 0
            ]) -> get();
            if (!$order_info->pay_status){
                if(count($erp_deliver_goods_record) > 1){
                    //累加
                    DB::table('erp_stock_order') -> where([
                        'id' => $request -> stock_order_id
                    ]) -> increment('freight',floatval($request -> deliver_price));
                }else{
                    //更新
                    DB::table('erp_stock_order') -> where([
                        'id' => $request -> stock_order_id
                    ]) -> update([
                        'freight' => floatval($request -> deliver_price)
                    ]);
                }
            }


            foreach($record_arr as $vo){

                $receive_info = DB::table('erp_stock_order_info_receive')
                    -> where('id',$vo['info_id'])
                    -> first();


                //增加已发数量
                DB::table('erp_stock_order_info') -> where([
                    'id' => $receive_info -> stock_order_info_id
                ]) -> increment('send_num',intval($vo['send_num']));

                //增加 erp_stock_order_info_receive 的 已发数量
                DB::table('erp_stock_order_info_receive')
                    -> where('id',$vo['info_id'])
                    -> increment('send_num',intval($vo['send_num']));

                //通过
                $erp_receive_goods_record = DB::table('erp_receive_goods_record')
                    -> where([
                        'id' => $receive_info ->receive_goods_record_id
                    ]) -> first();
                if($erp_receive_goods_record){
                    $logistics_info_id = $erp_receive_goods_record -> goods_id;

                    DB::table('erp_logistics_info') -> where([
                        'id' => $logistics_info_id
                    ]) -> increment('deliver_number',intval($vo['send_num']));
                }



                DB::table('erp_deliver_goods_record_info') -> insertGetId([
                    'deliver_goods_record_id' => $record_id,
                    'stock_order_info_receive_id' => $vo['info_id'],
                    'send_num' => $vo['send_num'],
                    'created_at' => time(),
                    'updated_at' => time(),
                ]);



                $stock_info = DB::table('erp_stock')
                    -> where([
                        'business_id' => $erp_receive_goods_record -> business_id,
                        'product_id' => $erp_receive_goods_record -> product_id,
                        'store_house_id' => $erp_receive_goods_record -> store_house_id,
                        'flag' => 0
                    ]) -> first();

                Log::info([
                    'type' => 'warehouseOrderController-532',
                    'waiting_num' => intval($stock_info -> waiting_num) - intval($vo['send_num'])
                ]);

                DB::table('erp_stock')
                    -> where([
                        'id' => $stock_info -> id
                    ]) -> update([
                        //减少待发货数量
                        'waiting_num' => intval($stock_info -> waiting_num) - intval($vo['send_num']),
                        'current_num' => intval($stock_info -> current_num) - intval($vo['send_num']),
                        'updated_at' => time()
                    ]);


            }



            //判断这单的状态 发货中、已发货、
            //看这单 的发货 有没有完成
            $stock_order_info = DB::table('erp_stock_order_info') -> where([
                'stock_order_id' => $request -> stock_order_id,
                'flag' => 0
            ])
                -> select([
                    'number',
                    'send_num'
                ])
                -> get();

            $is_all = false;
            foreach($stock_order_info as $vo){
                if($vo -> number != $vo -> send_num){
                    $is_all = true;
                    break;
                }
            }

            //发货状态 0未发货 1已发货 2发货中
            if($is_all){
                //发货中
                DB::table('erp_stock_order') -> where([
                    'id' => $request -> stock_order_id
                ]) -> update([
                    'send_status' => 2
                ]);
            }else{
                //已发货
                DB::table('erp_stock_order') -> where([
                    'id' => $request -> stock_order_id
                ]) -> update([
                    'send_status' => 1
                ]);
            }







            DB::commit();
            //检查下此 销售订单的货 是否都发完 erp_stock_order
            return [
                'code' => 200,
                'msg' => '成功'
            ];

        }catch (\Exception $exception){
            DB::rollback();
            return [
                'code' => 500,
                'msg' => $exception -> getMessage()
            ];
        }

    }





    //发货记录
    public function sendOrderRecord(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric', //事业部id
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }


        $record = DB::table('erp_deliver_goods_record as record')
            -> leftJoin('erp_stock_order','record.stock_order_id','erp_stock_order.id')
            -> leftJoin('wxuser','erp_stock_order.user_id','wxuser.id')

            -> where(function($query)use($request){
                if($request -> record_num){
                    $query -> where('record.record_num','like','%'.$request -> record_num.'%');
                }

                if($request -> order_num){
                    $query -> where('erp_stock_order.order_num','like','%'.$request -> order_num.'%');
                }

                if($request -> send_date_left){
                    $query -> where('record.created_at','>=',strtotime($request -> send_date_left));
                }

                if($request -> send_date_right){
                    $query -> where('record.created_at','<=',strtotime($request -> send_date_right));
                }

                if($request -> deliver_num){
                    $query -> where('record.deliver_num',$request -> deliver_num);
                }

                if($request -> user_id){
                    $query -> where('erp_stock_order.user_id',$request -> user_id);
                }

            })

            -> select([
                'record.record_num', //发货编号
                'erp_stock_order.order_num', //销售编号
                'erp_stock_order.name as receive_name', //收货人
                'wxuser.nickname', //客户名称
                'record.created_at', //发货时间
                'record.company', //物流公司
                'record.deliver_num', //物流单号
                'record.deliver_remark', //物流备注
                'record.deliver_price', //运费
                'record.id'
            ])
            -> where([
                'record.business_id' => $request -> business_id,
                'record.flag' => 0
            ])
            -> orderBy('record.id','desc')
            -> paginate(isset($request -> per_page)?$request -> per_page:20);
        return $record;

    }

    //发货记录明细
    public function sendOrderRecordInfo(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric', //事业部id
            'id' => 'required|numeric|exists:erp_deliver_goods_record,id' //发货记录id
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $record = DB::table('erp_deliver_goods_record as record')
            -> leftJoin('erp_stock_order','record.stock_order_id','erp_stock_order.id')
            -> select([
                'record.*',
                'erp_stock_order.remark as order_remark'
            ])
            -> where([
            'record.id' => $request -> id
        ]) -> first();

        $erp_stock_order = DB::table('erp_stock_order')
            -> where([
                'id' => $record -> stock_order_id
            ]) -> first();

        //仓库
        $ware_house = DB::table('erp_warehouse') -> where([
            'id' => $erp_stock_order ->warehouse_id
        ]) -> first();

        $wxuser = DB::table('wxuser') -> where([
            'id' => $erp_stock_order -> user_id
        ]) -> first();

        $erp_stock_order -> warehouse_name = $ware_house -> name;

        $erp_stock_order -> nickname = $wxuser -> nickname;


        $record_info = DB::table('erp_deliver_goods_record_info as record_info')
            -> leftJoin('erp_deliver_goods_record','erp_deliver_goods_record.id','record_info.deliver_goods_record_id')
            -> leftJoin('erp_stock_order_info_receive','record_info.stock_order_info_receive_id','erp_stock_order_info_receive.id')
            -> leftJoin('erp_receive_goods_record as receive_record','erp_stock_order_info_receive.receive_goods_record_id','receive_record.id')


            -> leftJoin('erp_product_list','receive_record.product_id','erp_product_list.id')
            //商品去找类别、品牌、系列
            //类别
            -> leftJoin('erp_product_class as product_class','erp_product_list.class_id','product_class.id')
            //品牌
            -> leftJoin('erp_product_class as product_brand','erp_product_list.brand_id','product_brand.id')
            //系列
            -> leftJoin('erp_product_class as product_series','erp_product_list.series_id','product_series.id')
            -> leftJoin('erp_storehouse','erp_storehouse.id','receive_record.store_house_id')
            -> leftJoin('erp_warehouse','erp_warehouse.id','erp_storehouse.warehouse_id')
            -> select([
                'erp_product_list.product_no', //商品编号
                'erp_product_list.product_name', //商品名称
                'erp_product_list.number as product_number', //商品数量
                'erp_product_list.model as model',
                'erp_storehouse.name as store_house_name', //库位
                'erp_warehouse.name as warehouse_name', //仓库
                'product_class.name as product_class_name', //类别
                'product_brand.name as product_brand_name', //品牌
                'product_series.name as product_series_name', //系列
                'record_info.send_num', //发货数量
            ])

            -> where([
                'erp_deliver_goods_record.id' => $request -> id,
                'record_info.flag' => 0
            ])
            -> get();


        //展示仓库发货详情
        $stock_order_info = DB::table('erp_stock_order_info')
            -> leftJoin('erp_product_list','erp_stock_order_info.product_id','erp_product_list.id')
            //商品去找类别、品牌、系列
            //类别
            -> leftJoin('erp_product_class as product_class','erp_product_list.class_id','product_class.id')
            //品牌
            -> leftJoin('erp_product_class as product_brand','erp_product_list.brand_id','product_brand.id')
            //系列
            -> leftJoin('erp_product_class as product_series','erp_product_list.series_id','product_series.id')
            -> select([
                'erp_stock_order_info.*',
                'erp_product_list.product_name',
                'erp_product_list.model',
                'erp_product_list.product_no',
                'erp_product_list.number as product_num',
                'product_class.name as product_class_name',
                'product_brand.name as product_brand_name',
                'product_series.name as product_series_name',
            ])
            -> where([
                'erp_stock_order_info.stock_order_id' => $record -> stock_order_id,
                'erp_stock_order_info.flag' => 0
            ])
            -> get();


        return [
            'record' => $record,
            'record_info' => $record_info,
            'erp_stock_order' => $erp_stock_order,
            'stock_order_info' => $stock_order_info
        ];


    }

    //编辑发货记录
    public function editSendOrderRecord(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric', //事业部id
            'id' => 'required|numeric|exists:erp_deliver_goods_record,id' //发货记录id
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }




        $record = DB::table('erp_deliver_goods_record')
            -> where([
                'id' => $request -> id,
                'flag' => 0
            ]) -> first();
        if(!$record){
            return [
                'code' => 500,
                'msg' => '没有此发货记录'
            ];
        }


        DB::table('erp_deliver_goods_record')
            -> where([
                'id' => $request -> id,
            ]) -> update([
                'company' => $request -> company?$request -> company:$record -> company,
                'deliver_num' => $request -> deliver_num?$request -> deliver_num:$record -> deliver_num,
                'deliver_remark' => $request -> deliver_remark?$request -> deliver_remark:$record -> deliver_remark,
                'deliver_price' => $request -> deliver_price,
                'weight' => $request -> weight
            ]);


        //修改订单的运费
        $erp_stock_order = DB::table('erp_stock_order') -> where([
            'id' => $record -> stock_order_id
        ]) -> first();
        if($erp_stock_order){
            DB::table('erp_stock_order') -> where([
                'id' => $record -> stock_order_id
            ]) -> update([
                'freight' =>  floatval($erp_stock_order -> freight) - floatval($record  -> deliver_price) +  floatval($request -> deliver_price)
            ]);

            //判断订单支付状态
            $erp_stock_order = DB::table('erp_stock_order') -> where([
                'id' => $record -> stock_order_id
            ]) -> first();

            //应付
            $to_pay=$this->math_add($this->math_add($erp_stock_order -> price,$erp_stock_order -> freight),$erp_stock_order -> substitute);
            //已付
            $paid=$erp_stock_order -> pay_price;
            if ($this->math_comp($to_pay,$paid) == 1){
            //if(floatval($erp_stock_order -> pay_price) <  floatval($erp_stock_order -> price) + floatval($erp_stock_order -> freight)+ floatval($erp_stock_order -> substitute)  ){
                DB::table('erp_stock_order') -> where([
                    'id' => $record -> stock_order_id
                ]) -> update([
                    'pay_status' => 0
                ]);
            }else{
                DB::table('erp_stock_order') -> where([
                    'id' => $record -> stock_order_id
                ]) -> update([
                    'pay_status' => 1
                ]);
            }

        }




        return [
            'code' => 200,
            'msg' => '修改成功'
        ];



    }


    //删除发货记录
    public function deleteSendOrderRecord(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric', //事业部id
            'id' => 'required|numeric|exists:erp_deliver_goods_record,id' //发货记录id
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        $user = auth('api')->user();
        $super_send_order = config('admin.super_send_order');

        if(!in_array($user -> username,$super_send_order)){
            $info = DB::table('erp_deliver_goods_record')
                -> where([
                    'id' => $request -> id,
                    'business_id' => $request -> business_id,
                    'flag' => 0
                ]) -> first();
        }else{
            $info = DB::table('erp_deliver_goods_record')
                -> where([
                    'id' => $request -> id,
                    'flag' => 0
                ])-> first();
        }





        if(!$info){
            return [
                'code' => 500,
                'msg' => '没有此条记录'
            ];
        }






        DB::beginTransaction();
        try{
            //删除发货记录 销售单 已发数量 退回。  销售订单发货状态修改
            DB::table('erp_deliver_goods_record')
                -> where([
                    'id' => $request -> id,
                ]) -> update([
                    'flag' => 1,
                    'updated_at' => time()
                ]);

            //销售订单的运费 退回
            /*if ($request->business_id!=49){
                DB::table('erp_stock_order') -> where([
                    'id' => $info -> stock_order_id
                ]) -> decrement('freight',floatval($info -> deliver_price));
            }*/

            //删除发货明细 通过删除发货明细 删除仓库到货记录
            $record_info = DB::table('erp_deliver_goods_record_info')
                -> where([
                    'deliver_goods_record_id' => $request -> id,
                    'flag' => 0
                ])  -> get();
            //已发数量退回
            foreach($record_info as $vo){
                //通过 $vo ->stock_order_info_receive_id 查找 erp_stock_order_info_receive 表
                $erp_stock_order_info_receive = DB::table('erp_stock_order_info_receive')
                    -> where([
                        'id' => $vo -> stock_order_info_receive_id
                    ]) -> first();
                if($erp_stock_order_info_receive){
                    DB::table('erp_stock_order_info_receive')
                        -> where([
                            'id' => $vo -> stock_order_info_receive_id
                        ]) -> update([
                            'send_num' => intval($erp_stock_order_info_receive -> send_num) - intval($vo -> send_num)
                        ]);
                    //收货明细
                    /*
                        $erp_receive_goods_record = DB::table('erp_receive_goods_record')
                            -> where([
                                'id' => $erp_stock_order_info_receive -> receive_goods_record_id
                            ]) -> first();
                    */




                    $erp_receive_goods_record = DB::table('erp_receive_goods_record')
                        -> where([
                            'id' => $erp_stock_order_info_receive -> receive_goods_record_id
                        ]) -> first();
                    if($erp_receive_goods_record){
                        $logistics_info = DB::table('erp_logistics_info')
                            -> where([
                                'id' => $erp_receive_goods_record -> goods_id
                            ]) -> first();
                        if($logistics_info){
                            DB::table('erp_logistics_info')
                                -> where([
                                    'id' => $erp_receive_goods_record -> goods_id
                                ]) -> update([
                                    'deliver_number' => intval($logistics_info -> deliver_number) - intval($vo -> send_num)
                                ]);
                        }

                        //处理库存

                        $stock_info = DB::table('erp_stock')
                            -> where([
                                'business_id' => $erp_receive_goods_record -> business_id,
                                'product_id' => $erp_receive_goods_record -> product_id,
                                'store_house_id' => $erp_receive_goods_record -> store_house_id,
                                'flag' => 0
                            ]) -> first();

                        Log::info([
                            'type' => 'warehouseOrderController-1000',
                            'waiting_num' => intval($stock_info -> waiting_num) + intval($vo -> send_num)
                        ]);

                        DB::table('erp_stock')
                            -> where([
                                'id' => $stock_info -> id
                            ]) -> update([
                                //减少待发货数量
                                'waiting_num' => intval($stock_info -> waiting_num) + intval($vo -> send_num),
                                'current_num' => intval($stock_info -> current_num) - intval($vo -> send_num),
                                'updated_at' => time()
                            ]);


                    }



                    //销售订单详情
                    $erp_stock_order_info = DB::table('erp_stock_order_info')
                        -> where([
                            'id' => $erp_stock_order_info_receive -> stock_order_info_id
                        ]) -> first();
                    if($erp_stock_order_info){
                        //销售订单 已发货数量 退回
                        DB::table('erp_stock_order_info') -> where([
                            'id' => $erp_stock_order_info -> id
                        ]) -> update([
                            'send_num' => intval($erp_stock_order_info -> send_num) - intval($vo -> send_num)
                        ]);
                    }




                }


            }

            DB::table('erp_deliver_goods_record_info')
                -> where([
                    'deliver_goods_record_id' => $request -> id,
                    'flag' => 0
                ]) -> update([
                    'flag' => 1,
                    'updated_at' => time()
                ]);


            //$info -> stock_order_id
            // 销售订单 修改发货状态
            //send_status  0未发货 1已发货 2发货中
            $erp_stock_order_info = DB::table('erp_stock_order_info')
                -> where([
                    'stock_order_id' => $info -> stock_order_id,
                    'flag' => 0
                ]) -> get();

            $send_status = 0;
            foreach($erp_stock_order_info as $vo){
                //如果销售数量 <> 已发货数量
                if($vo -> send_num){
                    $send_status = 2;
                    break;
                }
            }


            DB::table('erp_stock_order')
                -> where([
                    'id' => $info -> stock_order_id
                ]) -> update([
                    'send_status' =>$send_status
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
                'msg' => $exception->getTraceAsString()
            ];
        }





    }



    //配货单
    public function peihuo(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric', //事业部id
            'id' => 'required|numeric|exists:erp_stock_order,id' //订单id
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $order = DB::table('erp_stock_order')
            -> leftJoin('wxuser','erp_stock_order.user_id','wxuser.id')
            -> leftJoin('erp_business','erp_business.id','erp_stock_order.business_id')
            -> select([
                'erp_stock_order.*',
                'wxuser.nickname',
                'erp_business.name as business_name'
            ])
            -> where([
                'erp_stock_order.id' => $request -> id,
                //'erp_stock_order.business_id' => $request -> business_id
            ]) -> first();

        $order_info = [];
        if($order){
            //send_remark
            //发货备注 0发货1不发货2预定
            if($order -> send_remark == 2){
                $order -> send_remark = '预定';
            }elseif($order -> send_remark == 1){
                $order -> send_remark = '不发货';
            }else{
                $order -> send_remark = '发货';
            }

            $order -> nickname = $order ->business_name.'-'. $order -> nickname;


            $order_info = DB::table('erp_stock_order_info')
                -> leftJoin('erp_product_list','erp_stock_order_info.product_id','erp_product_list.id')
                //商品去找类别、品牌、系列
                //类别
                -> leftJoin('erp_product_class as product_class','erp_product_list.class_id','product_class.id')
                //品牌
                -> leftJoin('erp_product_class as product_brand','erp_product_list.brand_id','product_brand.id')
                //系列
                -> leftJoin('erp_product_class as product_series','erp_product_list.series_id','product_series.id')

                -> select([
                    'erp_stock_order_info.number as num',
                    'erp_product_list.product_name',
                    'erp_product_list.model',
                    'erp_product_list.product_no',
                    'erp_product_list.number as product_number',
                    'product_class.name as product_class_name',
                    'product_brand.name as product_brand_name',
                    'product_series.name as product_series_name',
                ])
                -> where([
                    'erp_stock_order_info.flag' => 0,
                    'erp_stock_order_info.stock_order_id' => $order -> id
                ]) -> get();



        }

        //更新此订单为 已打印配货单
        DB::table('erp_stock_order')
            -> where([
                'id' => $request -> id
            ]) -> update([
                'print_distribution' => 1
            ]);

        return [
            'order' => $order,
            'order_info' => $order_info
        ];

        //$record = DB::table('erp_deliver_goods_record')







    }

    //快递单打印
    public function printPage(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric', //事业部id
            'id' => 'required|numeric' //订单id
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $order_info = DB::table('erp_stock_order')
            -> leftJoin('erp_business','erp_stock_order.business_id','erp_business.id')
            -> select([
                'erp_stock_order.*',
                'erp_business.name as erp_business_name'
            ])
            -> where([
                'erp_stock_order.id' => $request -> id
            ])
            -> first();




        $user = auth('api')->user();
        $super_send_order = config('admin.super_send_order');

        if(!in_array($user -> username,$super_send_order)){
            $express_numbers_info = DB::table('erp_express_numbers')
                -> where([
                    'business_id' => $request -> business_id
                ]) -> first();
        }else{
            $express_numbers_info = DB::table('erp_express_numbers')
                -> whereIn('business_id',config('admin.super_send_order_business_id')) -> first();
        }






        if(!$express_numbers_info){
            return [
                'code' => 500,
                'msg' => '此事业部没有快递单号'
            ];
        }
        if(!$order_info -> name){
            return [
                'code' => 500,
                'msg' => '缺少收件人'
            ];
        }

        if(!$order_info -> tel && !$order_info -> phone){
            return [
                'code' => 500,
                'msg' => '缺少收件电话'
            ];
        }
        if(!$order_info -> province){
            return [
                'code' => 500,
                'msg' => '缺少收件省份'
            ];
        }
        if(!$order_info -> city){
            return [
                'code' => 500,
                'msg' => '缺少收件城市'
            ];
        }
        if(!$order_info -> area){
            return [
                'code' => 500,
                'msg' => '缺少收件区'
            ];
        }
        if(!$order_info -> address){
            return [
                'code' => 500,
                'msg' => '缺少收件地址'
            ];
        }

        //更新此订单为 已打印快递单
        DB::table('erp_stock_order')
            -> where([
                'id' => $request -> id
            ]) -> update([
                'print_express' => 1
            ]);


        //取 电子面单的序号
        $count_erp_deliver_goods_record = DB::table('erp_deliver_goods_record')
            -> where([
                'stock_order_id' => $request -> id
            ]) -> count();
        $order_number = $order_info -> order_num . '-' .(intval($count_erp_deliver_goods_record) + 1);


        //构造电子面单提交信息
        if($request -> miandan_type == 1){
            $eorder = [];
            $eorder["ShipperCode"] = "ZTO";
            $eorder["OrderCode"] = $order_number;
            $eorder["CustomerName"] = $express_numbers_info -> customer_name; //顺丰不需要，中通需要 ZTO_1000872032
            $eorder["ExpType"] = 1;
            $eorder["PayType"] = 1;
        } else {
            $eorder = [];
            $eorder["ShipperCode"] = "SF";
            $eorder["OrderCode"] = $order_number;
            $eorder["CustomerName"] = $express_numbers_info -> customer_name; //顺丰不需要，中通需要 ZTO_1000872032
            $eorder["ExpType"] = 1;
            // 没传则默认为陆运
            if (!in_array($request->transType, [1,2])) {
                $request->transType = 1;
            }
            if (!in_array($request->payType, [1,2,3])) {
                $request->payType = 1;
            }
            // 陆运还是空运  1-	陆运 2-	空运
            $eorder["TransType"] = $request->transType;
            // 1-现付，2-到付，3-月结
            $eorder["PayType"] = $request->payType;
            if ($request->payType === 3) {
                $eorder["MonthCode"] = config('admin.SFMonthCode');
            }
        }

        $sender = [];
        $sender["Name"] = $order_info -> send_name;
        $sender["Mobile"] = $order_info -> send_tel?$order_info -> send_tel:$order_info -> send_phone;
        $sender["ProvinceName"] = $order_info -> send_province;
        $sender["CityName"] = $order_info -> send_city;
        $sender["ExpAreaName"] = $order_info -> send_area;
        $sender["Address"] = $order_info -> send_address;

        //通过订单 取收件人

        $receiver = [];
        $receiver["Name"] = $order_info -> name;
        $receiver["Mobile"] = $order_info -> tel?$order_info -> tel:$order_info -> phone;
        $receiver["ProvinceName"] = $order_info -> province;
        $receiver["CityName"] = $order_info -> city;
        $receiver["ExpAreaName"] = $order_info -> area;
        $receiver["Address"] = $order_info -> address;

        $commodityOne = [];
        $commodityOne["GoodsName"] = $order_number;
        $commodity = [];
        $commodity[] = $commodityOne;

        $eorder["Sender"] = $sender;
        $eorder["Receiver"] = $receiver;
        $eorder["Commodity"] = $commodity;
        $eorder["IsReturnPrintTemplate"] = 1;


        //调用电子面单
        $jsonParam = json_encode($eorder, JSON_UNESCAPED_UNICODE);

        //$jsonParam = JSON($eorder);//兼容php5.2（含）以下

        //echo "电子面单接口提交内容：<br/>".$jsonParam;
        $jsonResult = $this -> submitEOrder($jsonParam,$express_numbers_info -> user_name,$express_numbers_info -> app_key);
        $result = json_decode($jsonResult, true);
        //return $result;
        if($result['Success']){
            return $result;
            //return $result['PrintTemplate'] ;
        }else{
            return $result;
        }


        echo  $jsonResult -> Order -> PrintTemplate;

        /*
        echo "<br/><br/>电子面单提交结果:<br/>".$jsonResult;

//解析电子面单返回结果
        */

        /*
        $result = json_decode($jsonResult, true);
        echo "<br/><br/>返回码:".$result["ResultCode"];
        if($result["ResultCode"] == "100") {
            echo "<br/>是否成功:".$result["Success"];exit;
        }
        else {
            echo "<br/>电子面单下单失败";exit;
        }
        */






    }




    /**
     * Json方式 调用电子面单接口
     */
    function submitEOrder($requestData,$username,$appkey){
        $datas = array(
            'EBusinessID' => $username,
            'RequestType' => '1007',
            'RequestData' => urlencode($requestData) ,
            'DataType' => '2',
        );
        $datas['DataSign'] = $this -> encrypt($requestData, $appkey);
        $result=$this -> sendPost(ReqURL, $datas);

        //根据公司业务处理返回的信息......

        return $result;
    }


    /**
     *  post提交数据
     * @param  string $url 请求Url
     * @param  array $datas 提交的数据
     * @return url响应返回的html
     */
    function sendPost($url, $datas) {
        $temps = array();
        foreach ($datas as $key => $value) {
            $temps[] = sprintf('%s=%s', $key, $value);
        }
        $post_data = implode('&', $temps);
        $url_info = parse_url($url);
        if(empty($url_info['port']))
        {
            $url_info['port']=80;
        }
        $httpheader = "POST " . $url_info['path'] . " HTTP/1.0\r\n";
        $httpheader.= "Host:" . $url_info['host'] . "\r\n";
        $httpheader.= "Content-Type:application/x-www-form-urlencoded\r\n";
        $httpheader.= "Content-Length:" . strlen($post_data) . "\r\n";
        $httpheader.= "Connection:close\r\n\r\n";
        $httpheader.= $post_data;
        $fd = fsockopen($url_info['host'], $url_info['port']);
        fwrite($fd, $httpheader);
        $gets = "";
        $headerFlag = true;
        while (!feof($fd)) {
            if (($header = @fgets($fd)) && ($header == "\r\n" || $header == "\n")) {
                break;
            }
        }
        while (!feof($fd)) {
            $gets.= fread($fd, 128);
        }
        fclose($fd);

        return $gets;
    }

    /**
     * 电商Sign签名生成
     * @param data 内容
     * @param appkey Appkey
     * @return DataSign签名
     */
    function encrypt($data, $appkey) {
        return urlencode(base64_encode(md5($data.$appkey)));
    }
    /**************************************************************
     *
     *  使用特定function对数组中所有元素做处理
     *  @param  string  &$array     要处理的字符串
     *  @param  string  $function   要执行的函数
     *  @return boolean $apply_to_keys_also     是否也应用到key上
     *  @access public
     *
     *************************************************************/
    function arrayRecursive(&$array, $function, $apply_to_keys_also = false)
    {
        static $recursive_counter = 0;
        if (++$recursive_counter > 1000) {
            die('possible deep recursion attack');
        }
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $this -> arrayRecursive($array[$key], $function, $apply_to_keys_also);
            } else {
                $array[$key] = $function($value);
            }

            if ($apply_to_keys_also && is_string($key)) {
                $new_key = $function($key);
                if ($new_key != $key) {
                    $array[$new_key] = $array[$key];
                    unset($array[$key]);
                }
            }
        }
        $recursive_counter--;
    }


    /**************************************************************
     *
     *  将数组转换为JSON字符串（兼容中文）
     *  @param  array   $array      要转换的数组
     *  @return string      转换得到的json字符串
     *  @access public
     *
     *************************************************************/
    function JSON($array) {
        $this -> arrayRecursive($array, 'urlencode', true);
        $json = json_encode($array);
        return urldecode($json);
    }


    //仓库退货
    public function returnGoods(Request $request)
    {
        // 最麻烦最恶心的就是仓库退货，仓库退货是退到港口
    }


    //仓库交易明细
    public function transactionDetails(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric', //事业部id
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        //查找仓库的 入库 出库 记录
        $info = DB::select('
select * from (select erp_receive_record.receive_num as number,erp_receive_goods_record.created_at,erp_product_list.product_no,erp_product_list.product_name,erp_product_list.model,erp_receive_goods_record.true_num as num,class.name as product_class_name,brand.name as product_brand_name,series.name as product_series_name,"入库" as reason,"" as order_num,erp_receive_record.id as id from erp_receive_goods_record 
left join erp_receive_record on  erp_receive_goods_record.receive_record_id = erp_receive_record.id
left join erp_product_list on erp_receive_goods_record.product_id = erp_product_list.id
left join erp_product_class as class on erp_product_list.class_id = class.id
left join erp_product_class as brand on erp_product_list.brand_id = brand.id
left join erp_product_class as series on erp_product_list.series_id = series.id

where erp_receive_goods_record.flag = 0 and erp_product_list.product_no = ?  and erp_receive_record.business_id = ? and erp_receive_goods_record.warehouse_id = ? and erp_receive_goods_record.store_house_id = ?  and erp_receive_record.created_at >= ? and erp_receive_record.created_at <= ?

union all 

select erp_deliver_goods_record.record_num as number,erp_deliver_goods_record_info.created_at,erp_product_list.product_no,erp_product_list.product_name,erp_product_list.model,CONCAT("-",erp_deliver_goods_record_info.send_num) as num,class.name as product_class_name,brand.name as product_brand_name,series.name as product_series_name,"销售" as reason,erp_stock_order.order_num as order_num,erp_stock_order.id as id from erp_deliver_goods_record_info 
left join erp_deliver_goods_record on erp_deliver_goods_record_info.deliver_goods_record_id = erp_deliver_goods_record.id
left join erp_stock_order_info_receive on erp_deliver_goods_record_info.stock_order_info_receive_id = erp_stock_order_info_receive.id
left join erp_receive_goods_record on erp_receive_goods_record.id = erp_stock_order_info_receive.receive_goods_record_id
left join erp_stock_order_info on erp_stock_order_info_receive.stock_order_info_id = erp_stock_order_info.id
left join erp_stock_order on erp_stock_order_info.stock_order_id = erp_stock_order.id
left join erp_product_list on erp_stock_order_info.product_id = erp_product_list.id
left join erp_product_class as class on erp_product_list.class_id = class.id
left join erp_product_class as brand on erp_product_list.brand_id = brand.id
left join erp_product_class as series on erp_product_list.series_id = series.id

where erp_deliver_goods_record_info.flag = 0 and erp_product_list.product_no = ?  and  erp_deliver_goods_record.business_id = ? and erp_receive_goods_record.warehouse_id = ? and erp_receive_goods_record.store_house_id = ? and erp_deliver_goods_record.created_at >= ? and erp_deliver_goods_record.created_at <= ?  


union all 

select "" as number,erp_stock_adjust.created_at,erp_product_list.product_no,erp_product_list.product_name,erp_product_list.model,erp_stock_adjust.transfer_num as num,class.name as product_class_name,brand.name as product_brand_name,series.name as product_series_name,case adjust_type when "0" then "库位移出" when "1" then "报废" when "2" then "调帐" else "其他" end  reason,"" as order_num,1 as id from erp_stock_adjust 
left join erp_receive_goods_record  on erp_stock_adjust.receive_goods_record_id = erp_receive_goods_record.id
left join erp_receive_record on  erp_receive_goods_record.receive_record_id = erp_receive_record.id
left join erp_product_list on erp_receive_goods_record.product_id = erp_product_list.id
left join erp_product_class as class on erp_product_list.class_id = class.id
left join erp_product_class as brand on erp_product_list.brand_id = brand.id
left join erp_product_class as series on erp_product_list.series_id = series.id

where  erp_stock_adjust.flag = 0 and erp_product_list.product_no = ?  and erp_receive_record.business_id = ? and erp_receive_goods_record.warehouse_id = ? and erp_receive_goods_record.store_house_id = ?  and erp_stock_adjust.created_at >= ? and erp_stock_adjust.created_at <= ?




) una  order by una.created_at desc





',[

            $request -> product_no,
            $request -> business_id,
            $request -> warehouse_id,
            $request -> storehouse_id,
            strtotime($request -> deal_date_left),
            strtotime($request -> deal_date_right.' 23:59:59'),

            $request -> product_no,
            $request -> business_id,
            $request -> warehouse_id,
            $request -> storehouse_id,
            strtotime($request -> deal_date_left),
            strtotime($request -> deal_date_right.' 23:59:59'),

            $request -> product_no,
            $request -> business_id,
            $request -> warehouse_id,
            $request -> storehouse_id,
            strtotime($request -> deal_date_left),
            strtotime($request -> deal_date_right.' 23:59:59'),
        ]);


        foreach($info as $k => $vo){
            $info[$k] -> dealtime = date('Y-m-d H:i',$vo -> created_at);

            //正负
            switch ($vo -> reason){
                case '报废': $info[$k] -> num = '-'.$vo -> num;break;
                case '库位移出':$info[$k] -> num = '-'.$vo -> num;break;
                case '入库':
                    //入库 判断下 他是不是从库位转移来的
                    $temp = DB::table('erp_stock_adjust') -> where([
                        'to_receive_record_id' => $vo -> id
                    ]) -> first();
                    if($temp){
                        //如果是库位转移来的 则 显示原仓库 原库位 erp_receive_goods_record
                        $from_storehouse_info = DB::table('erp_storehouse')
                            -> leftJoin('erp_warehouse','erp_storehouse.warehouse_id','erp_warehouse.id')
                            -> select([
                                'erp_storehouse.name as erp_storehouse_name',
                                'erp_warehouse.name as erp_warehouse_name',
                            ])
                            -> where([
                            'erp_storehouse.id' => $temp -> from_storehouse_id
                        ]) -> first();
                        if($from_storehouse_info){
                            $info[$k] -> source_warehouse = $from_storehouse_info -> erp_warehouse_name;
                            $info[$k] -> source_storehouse = $from_storehouse_info -> erp_storehouse_name;
                        }
                    }
                    break;
            }


        }

        return [
            'data' => $info
        ];

    }


    //仓库交易明细 库存销售详情
    public function oneTransactionDetail(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric', //事业部id
            'id' => 'required',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }


        //显示这个销售单的明细
        $order = DB::table('erp_stock_order as order')
            -> leftJoin('wxuser','order.user_id','wxuser.id')
            -> leftJoin('erp_warehouse','order.warehouse_id','erp_warehouse.id')
            -> leftJoin('users','order.sale_user_id','users.id')
            -> select([
                'order.*',
                'wxuser.nickname',
                'erp_warehouse.name as warehouse_name',
                'users.name as sale_user_name'
            ])
            -> where([
                'order.id' => $request -> id,
            ])
            -> first();

        $order -> send_name = $order -> send_name.' '.$order -> send_tel.' '.$order -> send_phone.' '.$order -> send_province.' '.$order -> send_city.' '.$order -> send_area.' '.$order -> send_address;
        $order -> name = $order -> name.' '.$order -> tel.' '.$order -> phone.' '.$order -> province.' '.$order -> city.' '.$order -> area.' '.$order -> address;


        $order_info = DB::table('erp_stock_order_info')

            -> leftJoin('erp_stock_order','erp_stock_order_info.stock_order_id','erp_stock_order.id')

            -> leftJoin('erp_product_list','erp_stock_order_info.product_id','erp_product_list.id')
            //类别
            -> leftJoin('erp_product_class as product_class','erp_product_list.class_id','product_class.id')
            //品牌
            -> leftJoin('erp_product_class as product_brand','erp_product_list.brand_id','product_brand.id')
            //系列
            -> leftJoin('erp_product_class as product_series','erp_product_list.series_id','product_series.id')

            -> where([
                'erp_stock_order_info.stock_order_id' => $request -> id,
                'erp_stock_order_info.flag' => 0
            ]) -> select([
                'erp_stock_order_info.*',
                'erp_stock_order_info.number as order_info_num',
                'erp_product_list.*',
                'erp_stock_order_info.price',
                'erp_product_list.id as product_id',
                'erp_stock_order.warehouse_id',
                'erp_product_list.number as product_num',
                'product_class.name as product_class_name',
                'product_brand.name as product_brand_name',
                'product_series.name as product_series_name',
            ]) -> get();

        return [
            'code' => 200,
            'order' => $order,
            'order_info' => $order_info
        ];



    }


    //手动打印快递单
    public function printExpressPage(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric', //事业部id
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }





        $user = auth('api')->user();
        $super_send_order = config('admin.super_send_order');

        if(!in_array($user -> username,$super_send_order)){
            $express_numbers_info = DB::table('erp_express_numbers')
                -> where([
                    'business_id' => $request -> business_id
                ]) -> first();
        }else{
            $express_numbers_info = DB::table('erp_express_numbers')
                -> whereIn('business_id',config('admin.super_send_order_business_id')) -> first();
        }






        if(!$express_numbers_info){
            return [
                'code' => 500,
                'msg' => '此事业部没有快递单号'
            ];
        }
        if(!$request -> name){
            return [
                'code' => 500,
                'msg' => '缺少收件人'
            ];
        }

        if(!$request -> tel && !$request -> phone){
            return [
                'code' => 500,
                'msg' => '缺少收件电话'
            ];
        }
        if(!$request -> province){
            return [
                'code' => 500,
                'msg' => '缺少收件省份'
            ];
        }
        if(!$request -> city){
            return [
                'code' => 500,
                'msg' => '缺少收件城市'
            ];
        }
        if(!$request -> area){
            return [
                'code' => 500,
                'msg' => '缺少收件区'
            ];
        }
        if(!$request -> address){
            return [
                'code' => 500,
                'msg' => '缺少收件地址'
            ];
        }


        //请求打印面单 的 单号
        $order_number = NumberQueue::addQueueNo(5);

        $eorder = [];

        //构造电子面单提交信息
        if($request -> miandan_type == 1){
            $eorder["ShipperCode"] = "ZTO";
            $eorder["OrderCode"] = $order_number;
            $eorder["CustomerName"] = $express_numbers_info -> customer_name; //顺丰不需要，中通需要 ZTO_1000872032
            $eorder["ExpType"] = 1;
            $eorder["PayType"] = 1;
        } else {
            $eorder["ShipperCode"] = "SF";
            $eorder["OrderCode"] = $order_number;
            $eorder["CustomerName"] = $express_numbers_info -> customer_name; //顺丰不需要，中通需要 ZTO_1000872032
            $eorder["ExpType"] = 1;
            // 没传则默认为陆运
            if (!in_array($request->transType, [1,2])) {
                $request->transType = 1;
            }
            if (!in_array($request->payType, [1,2,3])) {
                $request->payType = 1;
            }
            // 陆运还是空运  1-	陆运 2-	空运
            $eorder["TransType"] = $request->transType;
            // 1-现付，2-到付，3-月结
            $eorder["PayType"] = $request->payType;
            if ($request->payType === 3) {
                $eorder["MonthCode"] = config('admin.SFMonthCode');
            }
        }

        $sender = [];
        $sender["Name"] = $request -> send_name;
        $sender["Mobile"] = $request -> send_tel?$request -> send_tel:$request -> send_phone;
        $sender["ProvinceName"] = $request -> send_province;
        $sender["CityName"] = $request -> send_city;
        $sender["ExpAreaName"] = $request -> send_area;
        $sender["Address"] = $request -> send_address;

        //通过订单 取收件人

        $receiver = [];
        $receiver["Name"] = $request -> name;
        $receiver["Mobile"] = $request -> tel?$request -> tel:$request -> phone;
        $receiver["ProvinceName"] = $request -> province;
        $receiver["CityName"] = $request -> city;
        $receiver["ExpAreaName"] = $request -> area;
        $receiver["Address"] = $request -> address;

        $commodityOne = [];
        $commodityOne["GoodsName"] = $request -> goods_info . ','.$request -> remark;
        $commodity = [];
        $commodity[] = $commodityOne;

        $eorder["Sender"] = $sender;
        $eorder["Receiver"] = $receiver;
        $eorder["Commodity"] = $commodity;
        $eorder["IsReturnPrintTemplate"] = 1;

        //$eorder["Remark"] = '发达阿斯顿发阿斯顿发苏打粉';


        //调用电子面单
        $jsonParam = json_encode($eorder, JSON_UNESCAPED_UNICODE);

        //$jsonParam = JSON($eorder);//兼容php5.2（含）以下

        //echo "电子面单接口提交内容：<br/>".$jsonParam;
        $jsonResult = $this -> submitEOrder($jsonParam,$express_numbers_info -> user_name,$express_numbers_info -> app_key);
        $result = json_decode($jsonResult, true);
        //return $result;
        if($result['Success']){

            //记录单号 以及 html
            DB::table('erp_express_log') -> insertGetId([
                'order_num' => $result['Order']['OrderCode'],
                'express_num' =>  $result['Order']['LogisticCode'],
                'express_type' =>  $result['Order']['ShipperCode'],
                'express_html' =>  $result['PrintTemplate'],
                'created_at' => time(),
                'updated_at' => time(),

            ]);


            return $result;
            //return $result['PrintTemplate'] ;
        }else{
            return $result;
        }






    }


    //快递单报表
    public function sendOrderRecordSheet(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric', //事业部id
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }


        $user = auth('api')->user();
        $model_has_roles = DB::table('model_has_roles')
            -> leftJoin('roles','model_has_roles.role_id','roles.id')
            -> where([
                'model_has_roles.model_id' => $user -> id
            ])
            -> select([
                'roles.name as  roles_name'
            ])
            -> get();


        //是不是客服角色
        $is_kefu_roles = 0;
        if($model_has_roles){
            foreach($model_has_roles as $vo){
                if(strstr($vo -> roles_name,'客服')){
                    $is_kefu_roles = 1;
                    break;
                }
            }
        }


        /*
         {
                "record_num": "I-20190429-000001",   发货编号
                "send_name": "发货人",   发货人
                "send_tel": "发货人手机",  发货联系手机

                "send_address": "天津市天津城区南开区11111", 联系人地址
                "name": null,  收货人
                "tel": null,  收货 联系电话
                "address": "",  收货人地址
                "stock_order_no": "S-20190427-000001-1",  销售单号
                "company": "1",  快递名称
                "deliver_num": "1",  快递单号
                "weight": null  重量
            }
         */

        $erp_deliver_goods_record = DB::table('erp_deliver_goods_record')
            -> leftJoin('erp_stock_order','erp_deliver_goods_record.stock_order_id','erp_stock_order.id')
            -> leftJoin('users','erp_stock_order.operator_user_id','users.id')
            -> leftJoin('erp_business','erp_deliver_goods_record.business_id','erp_business.id')
            -> where([
                'erp_deliver_goods_record.business_id' => $request -> business_id,
                'erp_deliver_goods_record.flag' => 0
            ])
            -> select([
                'erp_deliver_goods_record.record_num',
                'erp_stock_order.send_name',
                'erp_stock_order.send_tel',
                'erp_stock_order.send_phone',
                'erp_stock_order.send_province',
                'erp_stock_order.send_city',
                'erp_stock_order.send_area',
                'erp_stock_order.send_address',

                'erp_stock_order.name',
                'erp_stock_order.tel',
                'erp_stock_order.phone',
                'erp_stock_order.province',
                'erp_stock_order.city',
                'erp_stock_order.area',
                'erp_stock_order.address',
                'erp_stock_order.freight',

                'erp_deliver_goods_record.stock_order_no',
                'erp_deliver_goods_record.company',
                'erp_deliver_goods_record.deliver_num',
                'erp_deliver_goods_record.deliver_remark',
                'erp_deliver_goods_record.weight',
                'erp_business.name as erp_business_name',
                'users.name as operator_user',

            ])

            -> where(function($query)use($is_kefu_roles,$user){
                if($is_kefu_roles){
                    $query -> where('erp_stock_order.operator_user_id',$user -> id);
                }
            })

            -> where(function($query)use($request){
                if($request -> express_date_left){
                    $query -> where('erp_deliver_goods_record.created_at','>=',strtotime($request -> express_date_left));
                }

                if($request -> express_date_right){
                    $query -> where('erp_deliver_goods_record.created_at','<=',strtotime($request -> express_date_right));
                }

            })

            -> orderBy('erp_deliver_goods_record.id','desc')
            -> paginate(isset($request -> per_page)?$request -> per_page:20);

        foreach($erp_deliver_goods_record as $k => $vo){
            $erp_deliver_goods_record[$k] -> send_address = $vo -> send_province.$vo -> send_city.$vo -> send_area.$vo -> send_address;
            $erp_deliver_goods_record[$k] -> address = $vo -> province.$vo -> city.$vo -> area.$vo -> address;
            $erp_deliver_goods_record[$k] -> send_tel = $vo -> send_tel?$vo -> send_tel:$vo -> send_phone;
            $erp_deliver_goods_record[$k] -> tel = $vo -> tel?$vo -> tel:$vo -> phone;
        }

        return [
            'code' => 200,
            'msg' => 'success',
            'data' => $erp_deliver_goods_record
        ];


    }



}
