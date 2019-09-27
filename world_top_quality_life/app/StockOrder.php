<?php

namespace App;

use App\Repositories\SKURepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\ShopCart;
use App\PublicStockOrder;

class StockOrder extends Model
{
    protected $table = 'erp_stock_order';

    protected $dateFormat = 'U';

    protected $group_status=[
        1=>'拼单中未付款,请先付款',
        2=>'拼单中待发货',
        3=>'拼单成功未付款,请先付款',
        4=>'拼单成功待发货',
        5=>'拼单失败退款中',
    ];

    //添加库存销售订单

    /**
     * @param $arr_tmp
     * @param $type   0的时候 首次进入 如果有拆单其情况 弹出弹框  1 不拆 2 拆
     * @param string $from == 'agent' 就是 从 代理版小程序下
     * @return array
     */
    public function addStockOrder($arr_tmp,$type='2'){
        $skuRepository = new SKURepository();
        $arr = [
            'order_info_json'  => isset($arr_tmp['order_info_json'])?$arr_tmp['order_info_json']:'',    //下单json 新增明细   [{"goods_id 商品id":"1","price价格":"124","number数量":"55"},{}]
            'warehouse_id'     => isset($arr_tmp['warehouse_id'])?$arr_tmp['warehouse_id']:0,           //仓库id
            'business_id'      => isset($arr_tmp['business_id'])?$arr_tmp['business_id']:0,             //事业部id
            'sale_date'        => isset($arr_tmp['sale_date'])?$arr_tmp['sale_date']:date('Y-m-d'),     //销售日期 date类型
            'user_id'          => isset($arr_tmp['user_id'])?$arr_tmp['user_id']:0,                     //wxuser的id
            'sale_user_id'     => isset($arr_tmp['sale_user_id'])?$arr_tmp['sale_user_id']:0,           //销售员users id
            'freight'          => isset($arr_tmp['freight'])?$arr_tmp['freight']:0,                     //运费
            'substitute'       => isset($arr_tmp['substitute'])?$arr_tmp['substitute']:0,               //代发费
            'send_remark'      => isset($arr_tmp['send_remark'])?$arr_tmp['send_remark']:0,             //发货备注 0发货1不发货2预定
            'province'         => isset($arr_tmp['province'])?$arr_tmp['province']:'',                  //收件人省
            'city'             => isset($arr_tmp['city'])?$arr_tmp['city']:'',                          //收件人市
            'area'             => isset($arr_tmp['area'])?$arr_tmp['area']:'',                          //收件人区
            'tel'              => isset($arr_tmp['tel'])?$arr_tmp['tel']:'',                            //收件人手机
            'phone'            => isset($arr_tmp['phone'])?$arr_tmp['phone']:'',                        //收件人电话
            'name'             => isset($arr_tmp['name'])?$arr_tmp['name']:'',                          //收件人姓名
            'address'          => isset($arr_tmp['address'])?$arr_tmp['address']:'',                    //收件人地址
            'send_name'        => isset($arr_tmp['send_name'])?$arr_tmp['send_name']:'',                //发件人
            'send_tel'         => isset($arr_tmp['send_tel'])?$arr_tmp['send_tel']:'',                  //发件人
            'send_phone'       => isset($arr_tmp['send_phone'])?$arr_tmp['send_phone']:'',                  //发件人
            'send_province'    => isset($arr_tmp['send_province'])?$arr_tmp['send_province']:'',        //发件人
            'send_city'        => isset($arr_tmp['send_city'])?$arr_tmp['send_city']:'',                //发件人
            'send_area'        => isset($arr_tmp['send_area'])?$arr_tmp['send_area']:'',                //发件人
            'send_address'     => isset($arr_tmp['send_address'])?$arr_tmp['send_address']:'',          //发件人
            'remark'           => isset($arr_tmp['remark'])?$arr_tmp['remark']:'',                      //备注
            'sale_remark'      => isset($arr_tmp['sale_remark'])?$arr_tmp['sale_remark']:'',            //销售备注
            'user_remark'      => isset($arr_tmp['user_remark'])?$arr_tmp['user_remark']:'',            //客户备注
            'operator_user_id' => isset($arr_tmp['operator_user_id'])?$arr_tmp['operator_user_id']:0,   //后台操作员users id
            'insert_type'      => isset($arr_tmp['insert_type'])?$arr_tmp['insert_type']:0,             //添加类型， erp添加值为0 小程序添加值为1
            'idNumber'         => isset($arr_tmp['idNumber'])?$arr_tmp['idNumber']:'',             //身份证号码
            'imageFront'       => isset($arr_tmp['imageFront'])?$arr_tmp['imageFront']:'',             //身份证正面
            'imageBack'        => isset($arr_tmp['imageBack'])?$arr_tmp['imageBack']:'',             //身份证反面
            'ident_id'         => isset($arr_tmp['ident_id'])?$arr_tmp['ident_id']:0,             //身份证id
            'market_freight_id'=> isset($arr_tmp['market_freight_id'])?$arr_tmp['market_freight_id']:0,             //
            'origin_freight'=> isset($arr_tmp['origin_freight'])?$arr_tmp['origin_freight']:0,             //
            'coupon_decr'=> isset($arr_tmp['coupon_decr'])?$arr_tmp['coupon_decr']:0,             //
            'mp_id'=>isset($arr_tmp['mp_id'])?$arr_tmp['mp_id']:0,
        ];
        //校验商品明细 json
        $order_info_arr = json_decode($arr['order_info_json'],true);
        if(!count($order_info_arr)){
            return [
                'code' => 500,
                'msg'  => 'order_info_json 数据为空'
            ];
        }

        $temp_goods_id_arr = [];
        foreach($order_info_arr as $vo){
            if(!isset($vo['goods_id']) || !isset($vo['price']) || !isset($vo['number']) || !$vo['goods_id'] || !$vo['price'] || !$vo['number'] ){
                return [
                    'code' => 500,
                    'msg'  => '商品明细参数有误'
                ];
            }

            //判断goods_id 是否重复
            if(in_array($vo['goods_id'],$temp_goods_id_arr)){
                return [
                    'code' => 500,
                    'msg'  => '商品重复'
                ];
            }
            $temp_goods_id_arr[] = $vo['goods_id'];

            if($vo['is_public'] == 1){
                $publicStock =  $skuRepository -> fetchSkuStock($vo['goods_id']);
                if($publicStock['stock']<$vo['number'] ){
                    return [
                        'code' => 500,
                        'msg'  => '该仓库中没有此商品'
                    ];
                }
            }else{
                $good_info=['goods_id'=>$vo['goods_id'],'number'=>$vo['number']];
                $result=$this->checkWarehouseStock($arr,$good_info);
                if ($result['code']!=200)
                    return $result;
            }
        }
        DB::beginTransaction();
        try{
            //储存
            //订单id
            $stock_num = NumberQueue::addQueueNo(3);
            $stock_id = StockOrder::insertGetId([
                'order_num'        => $stock_num,
                'business_id'      => $arr['business_id'],
                'warehouse_id'     => $arr['warehouse_id'],
                'sale_date'        => strtotime($arr['sale_date']),
                'user_id'          => $arr['user_id'],
                'sale_user_id'     => $arr['sale_user_id'],
                'freight'          => $arr['freight'],
                'substitute'       => $arr['substitute'],
                'send_remark'      => $arr['send_remark'],
                'province'         => $arr['province'],
                'city'             => $arr['city'],
                'area'             => $arr['area'],
                'tel'              => $arr['tel'],
                'phone'            => $arr['phone'],
                'name'             => $arr['name'],
                'address'          => $arr['address'],
                'send_name'        => $arr['send_name'],
                'send_tel'         => $arr['send_tel'],
                'send_phone'         => $arr['send_phone'],
                'send_province'    => $arr['send_province'],
                'send_city'        => $arr['send_city'],
                'send_area'        => $arr['send_area'],
                'send_address'     => $arr['send_address'],
                'remark'           => $arr['remark'],
                'sale_remark'      => $arr['sale_remark'],
                'user_remark'      => $arr['user_remark'],
                'created_at'       => time(),
                'updated_at'       => time(),
                'operator_user_id' => $arr['operator_user_id'],
                'insert_type' => $arr['insert_type'],
                'idNumber' => $arr['idNumber'],
                'imageFront' => $arr['imageFront'],
                'imageBack' => $arr['imageBack'],
                'ident_id' => empty($arr['ident_id'])?0:$arr['ident_id'],
                'market_freight_id' => empty($arr['market_freight_id'])?0:$arr['market_freight_id'],
                'origin_freight' => empty($arr['origin_freight'])?0:$arr['origin_freight'],
                'mp_id'=>$arr['mp_id']
            ]);



            //销售金额
            $pay_price = 0;
            $product_ids = [];
            $updated_special_ids=[];
            foreach($order_info_arr as $vo){
                if(!isset($vo['goods_id']) || !isset($vo['price']) || !isset($vo['number']) || !$vo['goods_id'] || !$vo['price'] || !$vo['number'] ){
                    DB::rollBack();
                    return [
                        'code' => 500,
                        'msg'  => 'json参数有误'
                    ];
                }

                //库存下单详情
                $stock_order_info_id = StockOrderInfo::insertGetId([
                    'stock_order_id' => $stock_id,
                    'product_id'     => $vo['goods_id'],
                    'number'         => $vo['number'],     //这个数量 可能是从几个仓库到货订单里凑的
                    'price'          => $vo['price'],
                    'created_at'     => time(),
                    'updated_at'     => time(),
                    'special_id'     => isset($vo['special_id'])?$vo['special_id']:0,
                    'union_id'     => isset($vo['union_id'])?$vo['union_id']:0,
                    'union_number'     => isset($vo['union_id'])&&!empty($vo['union_id'])?$vo['number']/$vo['union_num'][$vo['goods_id']]:0,
                ]);
                $pay_price += intval($vo['number']) * floatval($vo['price']);
                $good_info=['goods_id'=>$vo['goods_id'],'number'=>$vo['number'],'business_id'=>$arr['business_id']];
                if($vo['is_public'] ==1){
                    $result=$this->updateStockPublic($good_info,$stock_order_info_id,$type);
                }else{
                    $result=$this->updateStock($arr,$good_info,$stock_order_info_id);
                }
                if ($result['code']!=200) {
                    return $result;
                }
                $product_ids = array_merge($product_ids,[$vo['goods_id']]);

                //更新特价已下单数量
                if (isset($vo['special_id']) && $vo['special_id'] != 0 && !in_array($vo['special_id'],$updated_special_ids)){
                    if (empty($vo['union_num'])){
                        $to_update_num=$vo['number'];
                    }else{
                        $to_update_num=$vo['number']/$vo['union_num'][$vo['goods_id']];
                    }
                    DB::table('erp_special_price')->whereIn('id',explode('_',$vo['special_id']))
                        ->increment('sold_num',$to_update_num);
                    $updated_special_ids[]=$vo['special_id'];
                }


            }

            if(count($product_ids)){
                $skuRepository -> autoPutOnOrOff($product_ids);
            }


            //更新销售金额  到订单中
            //减去优惠券
            $pay_price-=$arr['coupon_decr'];
            DB::table('erp_stock_order') -> where([
                'id' => $stock_id
            ]) -> update([
                'price' => $pay_price,
            ]);

            DB::commit();

            return [
                'code'     => 200,
                'msg'      => '成功',
                'stock_id' => $stock_id
            ];
        }catch (\Exception $exception){
            DB::rollBack();
            return [
                'code' => 500,
                'msg'  => $exception->getMessage()
            ];
        }
    }

    //销售订单详情
    /**
     * @param $stock_order_id 销售订单id
     * @return array
     */
    public function orderInfo($stock_order_id){
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
                'order.id' => $stock_order_id,
            ])
            -> first();
        if(!$order){
            return [
                'code' => 500,
                'msg' => '没有此订单'
            ];
        }
        //总金额
        //$order -> price_all = round(floatval($order -> price) + floatval($order -> substitute) + floatval($order -> freight),2);
        $order -> price_all =bcadd(bcadd($order -> price,$order -> substitute,2),$order -> freight,2);

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
                'erp_stock_order_info.stock_order_id' => $stock_order_id,
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
            'msg' => '成功',
            'order' => $order,
            'order_info' => $order_info
        ];

    }

    /**
     * 每个用户的销售订单列表
     * @param $business_id 事业部id
     * @param $user_id wxuser表的 id
     * @param $sale_status 付款状态 0未付款 1 已付款 999全部
     * @param $send_status 发货状态 0未发货 1已发货 2发货中 999全部
     * @return array
     */
    public function orderList($business_id,$user_id,$sale_status=0,$send_status=0)
    {
        $orders = DB::table('erp_stock_order as order')
            -> leftJoin('wxuser','order.user_id','wxuser.id')
            -> leftJoin('erp_warehouse','order.warehouse_id','erp_warehouse.id')
            -> leftJoin('users','order.sale_user_id','users.id')
            //-> leftJoin('erp_refunds','order.id','erp_refunds.order_id')
            -> select([
                'order.*',
                'erp_warehouse.name as erp_warehouse_name',
                'users.name as sale_user_name',
                'wxuser.nickname',
                /*'erp_refunds.result as refund_result',
                'erp_refunds.reason as refund_reason',*/
            ])
            -> where([
                // 'order.business_id' => $business_id,
                'order.user_id'     => $user_id,
                'order.flag'        => 0,
            ])
            -> whereIn('order.insert_type', [0,1])
            -> where(function($query)use($sale_status,$send_status){

                //付款状态
                if($sale_status != 999){
                    //0未付款 1 已付款
                    $query -> where('order.pay_status',$sale_status);
                }

                //订单状态
                if($send_status != 999){
                    //发货状态 0未发货 1已发货 2发货中
                    $query -> where('order.send_status',$send_status);
                }

            })
            -> orderBy('id','desc')
            -> paginate(20);

        //send_status 发货状态 0未发货 1已发货 2发货中
        foreach($orders as $k => $vo){
            /*if ($vo->refund_result == 1){
                $vo->refund_result_str='退款申请已同意';
            }elseif ($vo->refund_result == 2){
                $vo->refund_result_str='退款申请已拒绝';
            }elseif ($vo->refund_result === 0){
                $vo->refund_result_str='退款申请正在审核中';
            }*/

            //团购状态
            if ($vo->group_status){
                $vo->group_status_str=$this->group_status[$vo->group_status];
            }

            if($vo -> send_status == 2){
                $orders[$k] -> send_status_str = '发货中';
            }elseif($vo -> send_status == 1){
                $orders[$k] -> send_status_str = '已发货';
            }else{
                $orders[$k] -> send_status_str = '未发货';
            }

            //付款状态
            if($vo -> pay_status){
                $orders[$k] -> pay_status_str = '已付款';
            }else{
                $orders[$k] -> pay_status_str = '未付款';
            }

            //订单总价格
            $orders[$k] -> price_all = round(floatval($vo -> price) + floatval($vo -> substitute) + floatval($vo -> freight),2);

        }

        return [
            'code' => 200,
            'msg' => '成功',
            'orderList' => $orders
        ];
    }

    /**
     * 每个用户的销售订单列表
     * @param $business_id 事业部id
     * @param $user_id wxuser表的 id
     * @param $sale_status 付款状态 0未付款 1 已付款 999全部
     * @param $send_status 发货状态 0未发货 1已发货 2发货中 999全部
     * @return array
     */
    public function agentOrderList($business_id,$user_id,$sale_status=0,$send_status=0)
    {
        $orders = DB::table('erp_stock_order as order')
            -> leftJoin('wxuser','order.user_id','wxuser.id')
            -> leftJoin('erp_warehouse','order.warehouse_id','erp_warehouse.id')
            -> leftJoin('users','order.sale_user_id','users.id')
            -> select([
                'order.*',
                'erp_warehouse.name as erp_warehouse_name',
                'users.name as sale_user_name',
                'wxuser.nickname'
            ])
            -> where('order.insert_type', 2)
            -> where([
                'order.user_id'     => $user_id,
                'order.flag'        => 0
            ])
            -> where(function($query)use($sale_status,$send_status){

                //付款状态
                if($sale_status != 999){
                    //0未付款 1 已付款
                    $query -> where('order.pay_status',$sale_status);
                }

                //订单状态
                if($send_status != 999){
                    //发货状态 0未发货 1已发货 2发货中
                    $query -> where('order.send_status',$send_status);
                }

            })
            -> orderBy('id','desc')
            -> paginate(20);

        //send_status 发货状态 0未发货 1已发货 2发货中
        foreach($orders as $k => $vo){

            if($vo -> send_status == 2){
                $orders[$k] -> send_status_str = '发货中';
            }elseif($vo -> send_status == 1){
                $orders[$k] -> send_status_str = '已发货';
            }else{
                $orders[$k] -> send_status_str = '未发货';
            }

            //付款状态
            if($vo -> pay_status){
                $orders[$k] -> pay_status_str = '已付款';
            }else{
                $orders[$k] -> pay_status_str = '未付款';
            }

            //订单总价格
            $orders[$k] -> price_all = round(floatval($vo -> price) + floatval($vo -> substitute) + floatval($vo -> freight),2);

        }

        return [
            'code' => 200,
            'msg' => '成功',
            'orderList' => $orders
        ];
    }

    /**
     * 检测是否有已支付订单
     * @param array $orderIds
     */
    public function checkOrdersByIds($business_id, $user_id, $orderIds)
    {
        // 检测1 business_id是否对的上
        // 检测2 user_id 是否对的上
        // 检测3 orderIds 是否为已付款或者已删除的订单

        $orderIdsCount = count($orderIds);

        $count = DB::table('erp_stock_order')
            -> where([
                'flag'        => 0,
                'pay_status'  => 0,
                // 'business_id' => $business_id,
                'user_id'     => $user_id,
            ])
            -> whereIn('id', $orderIds)
            -> count();

        if ($orderIdsCount == $count) {
            return [
                'code'     => 200,
                'msg'      => 'success'
            ];
        } else {
            return [
                'code'     => 500,
                'msg'      => '订单异常'
            ];
        }
    }

    public function getOrdersByIds($business_id, $user_id, $orderIds)
    {
        $orders = DB::table('erp_stock_order')
            -> where([
                // 'business_id' => $business_id,
                'user_id'     => $user_id,
                'flag'        => 0,
                // 'pay_status'  => 0,

            ])
            -> whereIn('id', $orderIds)
            -> orderBy('id','desc')
            -> get();

        foreach($orders as $k => $vo){
            //订单总价格
            $orders[$k] -> price_all = round(floatval($vo -> price) + floatval($vo -> substitute) + floatval($vo -> freight),2);
        }

        return [
            'code' => 200,
            'msg' => '成功',
            'orderList' => $orders
        ];
    }


    //撤销付款
    public function cancelPayOrder($stock_order_id,$user_id,$business_id,$erp_stock_order_pay_id){
        $stock_order = DB::table('erp_stock_order') -> where([
            'id' => $stock_order_id,
            'flag' => 0
        ]) -> first();
        if(!$stock_order){
            return [
                'code' => 500,
                'msg' => '没有此订单'
            ];
        }


        //看付过款没有
        if(!$stock_order -> pay_price){
            return [
                'code' => 500,
                'msg' => '此订单没有付款'
            ];
        }


        //开始撤销付款
        DB::beginTransaction();
        try{

            if($erp_stock_order_pay_id){
                //如果有这个 就是撤销单笔付款记录
                //查找pay记录
                $erp_stock_order_pay = DB::table('erp_stock_order_pay')
                    -> where([
                        'id' => $erp_stock_order_pay_id,
                        'order_id' => $stock_order_id,
                        'flag' => 0
                    ]) -> first();
                if(!$erp_stock_order_pay){
                    return [
                        'code' => 500,
                        'msg' => '撤销失败'
                    ];
                }

                if($erp_stock_order_pay ->pay_method == 2){
                    //如果是 erp用余额支付的， 则 退换余额
                    DB::table('wxuser') -> where([
                        'id' => $stock_order -> user_id
                    ]) -> increment('price',floatval($erp_stock_order_pay -> price));

                    $wxuser = DB::table('wxuser') -> where([
                        'id' => $stock_order -> user_id
                    ]) -> first();

                    DB::table('price_log') -> insert([
                        'userid'       => $stock_order -> user_id,
                        'price'        => floatval($erp_stock_order_pay -> price),
                        'created_at'   => time(),
                        'updated_at'   => time(),
                        'from_user_id' => 1,
                        'type'         => 9,//撤销
                        'in_out'       => 0,// 收入
                        'order_id'     => $stock_order->id,
                        'end_price'    => $wxuser->price,
                    ]);


                }

                //删除此条pay记录
                DB::table('erp_stock_order_pay')
                    -> where([
                        'id' => $erp_stock_order_pay_id,
                    ]) -> update([
                        'flag' => 1
                    ]);

                //订单已付金额减少
                DB::table('erp_stock_order')
                    -> where([
                        'id' => $stock_order_id,
                    ]) -> update([
                        'pay_price' => floatval($stock_order -> pay_price) - floatval($erp_stock_order_pay -> price) + floatval($erp_stock_order_pay -> service_charge),
                        'pay_status' => 0,
                    ]);

                Log::info('cancelPayOrder方法-id为'.$stock_order_id.'erp_stock_order更新pay_status为:0');

                //账户余额 减少
                $erp_purchase_order_pay = DB::table('erp_purchase_order_pay')
                    -> where([
                        'order_id' => $stock_order_id,
                        'pay_type'       => 2,
                    ]) -> first();
                if($erp_purchase_order_pay){
                    //账户余额减少 日志
                    Account::addLog([
                        'business_id'  => $business_id,
                        'account_id'   => $erp_purchase_order_pay -> account_id,
                        'user_id'      => $user_id,
                        'log_type'     => 99,
                        'price'        => floatval($erp_stock_order_pay -> price) - floatval($erp_stock_order_pay -> service_charge),
                        'parameter_id' => $stock_order_id
                    ]);

                    //减少账户余额
                    DB::table('erp_account')
                        -> where([
                            'id' => $erp_purchase_order_pay -> account_id
                        ]) -> decrement('balance',floatval($erp_stock_order_pay -> price) - floatval($erp_stock_order_pay -> service_charge));
                }

                DB::commit();
                return [
                    'code' => 200,
                    'msg' => '撤销成功'
                ];



            }




            //查找pay记录
            $erp_stock_order_pay_record = DB::table('erp_stock_order_pay')
                -> where([
                    'order_id' => $stock_order_id,
                    'flag' => 0
                ]) -> get();
            DB::table('erp_stock_order_pay')
                -> where([
                    'order_id' => $stock_order_id,
                ]) -> update([
                    'flag' => 1
                ]);
            if(count($erp_stock_order_pay_record)){

                foreach($erp_stock_order_pay_record as $vo){
                    $erp_stock_order_pay = $vo;
                    $stock_order = DB::table('erp_stock_order') -> where([
                        'id' => $stock_order_id
                    ]) -> first();

                    //订单已付金额减少
                    DB::table('erp_stock_order')
                        -> where([
                            'id' => $stock_order_id,
                        ]) -> update([
                            'pay_price' => floatval($stock_order -> pay_price) - floatval($erp_stock_order_pay -> price) + floatval($erp_stock_order_pay -> service_charge),
                            'pay_status' => 0,
                        ]);

                    Log::info('cancelPayOrder方法-id为'.$stock_order_id.'erp_stock_order更新pay_status为:0');

                    //判断 如果是erp余额支付 则撤销 把余额返还
                    if($erp_stock_order_pay ->pay_method == 2){
                        //如果是 erp用余额支付的， 则 退换余额
                        DB::table('wxuser') -> where([
                            'id' => $stock_order -> user_id
                        ]) -> increment('price',floatval($erp_stock_order_pay -> price));

                        $wxuser = DB::table('wxuser') -> where([
                            'id' => $stock_order -> user_id
                        ]) -> first();

                        DB::table('price_log') -> insert([
                            'userid'       => $stock_order -> user_id,
                            'price'        => floatval($erp_stock_order_pay -> price),
                            'created_at'   => time(),
                            'updated_at'   => time(),
                            'from_user_id' => 1,
                            'type'         => 9,//撤销
                            'in_out'       => 0,// 收入
                            'order_id'     => $stock_order->id,
                            'end_price'    => $wxuser->price,
                        ]);
                    }


                    //账户余额 减少
                    $erp_purchase_order_pay = DB::table('erp_purchase_order_pay')
                        -> where([
                            'order_id' => $stock_order_id,
                            'pay_type'       => 2,
                        ]) -> first();
                    if($erp_purchase_order_pay){
                        //账户余额减少 日志
                        Account::addLog([
                            'business_id'  => $business_id,
                            'account_id'   => $erp_purchase_order_pay -> account_id,
                            'user_id'      => $user_id,
                            'log_type'     => 99,
                            'price'        => floatval($erp_stock_order_pay -> price) - floatval($erp_stock_order_pay -> service_charge),
                            'parameter_id' => $stock_order_id
                        ]);

                        //减少账户余额
                        DB::table('erp_account')
                            -> where([
                                'id' => $erp_purchase_order_pay -> account_id
                            ]) -> decrement('balance',floatval($erp_stock_order_pay -> price) - floatval($erp_stock_order_pay -> service_charge));
                    }
                }




                DB::commit();
                return [
                    'code' => 200,
                    'msg' => '撤销成功'
                ];

            }else{
                DB::rollBack();
                return [
                    'code' => 500,
                    'msg' => '撤销失败'
                ];
            }


        }catch (\Exception $exception){
            DB::rollBack();
            return [
                'code' => 500,
                'msg' => $exception->getTraceAsString()
            ];
        }




    }

    //代理版 添加库存销售单
    public function addAgentStockOrder($arr_tmp)
    {

        $arr = [
            'order_info_json'  => isset($arr_tmp['order_info_json'])?$arr_tmp['order_info_json']:'',    //下单json 新增明细   [{"goods_id 商品id":"1","price价格":"124","number数量":"55"},{}]
            'warehouse_id'     => isset($arr_tmp['warehouse_id'])?$arr_tmp['warehouse_id']:0,           //仓库id
            'business_id'      => isset($arr_tmp['business_id'])?$arr_tmp['business_id']:0,             //事业部id
            'sale_date'        => isset($arr_tmp['sale_date'])?$arr_tmp['sale_date']:date('Y-m-d'),     //销售日期 date类型
            'user_id'          => isset($arr_tmp['user_id'])?$arr_tmp['user_id']:0,                     //wxuser的id
            'sale_user_id'     => isset($arr_tmp['sale_user_id'])?$arr_tmp['sale_user_id']:0,           //销售员users id
            'freight'          => isset($arr_tmp['freight'])?$arr_tmp['freight']:0,                     //运费
            'substitute'       => isset($arr_tmp['substitute'])?$arr_tmp['substitute']:0,               //代发费
            'send_remark'      => isset($arr_tmp['send_remark'])?$arr_tmp['send_remark']:0,             //发货备注 0发货1不发货2预定
            'province'         => isset($arr_tmp['province'])?$arr_tmp['province']:'',                  //收件人省
            'city'             => isset($arr_tmp['city'])?$arr_tmp['city']:'',                          //收件人市
            'area'             => isset($arr_tmp['area'])?$arr_tmp['area']:'',                          //收件人区
            'tel'              => isset($arr_tmp['tel'])?$arr_tmp['tel']:'',                            //收件人手机
            'phone'            => isset($arr_tmp['phone'])?$arr_tmp['phone']:'',                        //收件人电话
            'name'             => isset($arr_tmp['name'])?$arr_tmp['name']:'',                          //收件人姓名
            'address'          => isset($arr_tmp['address'])?$arr_tmp['address']:'',                    //收件人地址
            'send_name'        => isset($arr_tmp['send_name'])?$arr_tmp['send_name']:'',                //发件人
            'send_tel'         => isset($arr_tmp['send_tel'])?$arr_tmp['send_tel']:'',                  //发件人
            'send_phone'       => isset($arr_tmp['send_phone'])?$arr_tmp['send_phone']:'',                  //发件人
            'send_province'    => isset($arr_tmp['send_province'])?$arr_tmp['send_province']:'',        //发件人
            'send_city'        => isset($arr_tmp['send_city'])?$arr_tmp['send_city']:'',                //发件人
            'send_area'        => isset($arr_tmp['send_area'])?$arr_tmp['send_area']:'',                //发件人
            'send_address'     => isset($arr_tmp['send_address'])?$arr_tmp['send_address']:'',          //发件人
            'remark'           => isset($arr_tmp['remark'])?$arr_tmp['remark']:'',                      //备注
            'sale_remark'      => isset($arr_tmp['sale_remark'])?$arr_tmp['sale_remark']:'',            //销售备注
            'user_remark'      => isset($arr_tmp['user_remark'])?$arr_tmp['user_remark']:'',            //客户备注
            'operator_user_id' => isset($arr_tmp['operator_user_id'])?$arr_tmp['operator_user_id']:0,   //后台操作员users id
            'insert_type'      => isset($arr_tmp['insert_type'])?$arr_tmp['insert_type']:0,             //添加类型， erp添加值为0 小程序添加值为1
            'idNumber'         => isset($arr_tmp['idNumber'])?$arr_tmp['idNumber']:'',             //身份证号码
            'imageFront'       => isset($arr_tmp['imageFront'])?$arr_tmp['imageFront']:'',             //身份证正面
            'imageBack'        => isset($arr_tmp['imageBack'])?$arr_tmp['imageBack']:'',             //身份证反面
            'ident_id'         => isset($arr_tmp['ident_id'])?$arr_tmp['ident_id']:0,
        ];


        //校验商品明细 json
        $order_info_arr = json_decode($arr['order_info_json'],true);
        if(!count($order_info_arr)){
            return [
                'code' => 500,
                'msg'  => 'order_info_json 数据为空'
            ];
        }

        //每个商品的 取货顺序
        $all_product_list = [];

        $temp_goods_id_arr = [];
        foreach($order_info_arr as $k => $vo){
            if(!isset($vo['goods_id']) || !isset($vo['price']) || !isset($vo['number']) || !$vo['goods_id'] || !$vo['price'] || !$vo['number'] ){
                return [
                    'code' => 500,
                    'msg'  => '商品明细参数有误'
                ];
            }

            //判断goods_id 是否重复
            if(in_array($vo['goods_id'],$temp_goods_id_arr)){
                return [
                    'code' => 500,
                    'msg'  => '商品重复'
                ];
            }
            $temp_goods_id_arr[] = $vo['goods_id'];
            //去别的事业部看看， 是否够剩下的这个数量
            //优先取这个事业部
            $data = $this -> getProductWeight($vo['goods_id'], $vo['mp_name_id'] ,$arr['business_id']);

            //需要的数量
            $need_num = intval($vo['number']);

            foreach ($data as $key => $value) {
                $value->price = $vo['price'];
                if (intval($value->can_buy_num) < $need_num) {
                    $need_num                   -= intval($value->can_buy_num);
                    $all_product_list[$k][$key] = $value;
                } elseif (intval($value->can_buy_num) > $need_num) {
                    $value->can_buy_num         = $need_num;
                    $need_num                   -= $need_num;
                    $all_product_list[$k][$key] = $value;
                    break;
                } else {
                    $value->can_buy_num         = $need_num;
                    $need_num                   -= $need_num;
                    $all_product_list[$k][$key] = $value;
                    break;
                }

            }


            //dd($need_num);
            if($need_num > 0){
                return [
                    'code' => 500,
                    'msg' => '数量不够'
                ];
            }




        }



        DB::beginTransaction();
        try{
            //以事业部维度 整理数据
            $temp_business_data = [];
            foreach($all_product_list as $k => $value){
                //dd($value);
                foreach($value as $temp_key => $temp_value){
                    //dd($temp_value);
                    $temp_business_data[$temp_value -> business_id][] = $temp_value;
                }
            }


            //剩下的 是 其他事业部 需要下采购单 到展鹏的
            //unset($temp_business_data[$arr['business_id']]);
            //dd($temp_business_data);
            if(count($temp_business_data)){
                //如果有 就需要从其他事业部取
                foreach($temp_business_data as $k=>$vo){

                    $business_id = $k;

                    //找到这个事业部绑定的用户
                    $business_info = DB::table('erp_business') -> where([
                        'id' => $business_id
                    ]) -> first();
                    if(!$business_info || !$business_info -> user_id){
                        DB::rollBack();
                        return [
                            'code' => 500,
                            'msg' => '事业部没有绑定用户'
                        ];
                    }
                    $user_arr = explode(',',$business_info -> user_id);
                    //这个事业部绑定的user_id
                    $this_business_user_id = $user_arr[0];



                    $goods_arr = [];
                    foreach($vo as $key => $value){
                        $goods_arr[] = [
                            'id' => $value -> product_id,
                            'price' => $value -> price,
                            'number' => $value -> can_buy_num
                        ];
                    }
                    //如果这个事业部是展鹏 则 直接销售 不需要采购
                    if($business_id != config('admin.zhanpeng_business_id')){
                        //展鹏 向 这个事业部采购


                        // ? 港口不知道 是哪个港口发给哪个港口
                        $purchase_id = PurchaseOrder::addAgentPurchaseOrder([
                            'business_id' => $business_id,
                            'goods_arr' => $goods_arr, //采购明细 id price number

                        ],0);
                        if(!$purchase_id){
                            DB::rollBack();
                            return [
                                'code' => 500,
                                'msg' => '该事业部数据有误'
                            ];
                        }
                    }

                    //构建销售单 数组
                    //一个仓库一个销售单

                    $stock_arr = [];
                    foreach($vo as $key => $value){
                        $stock_arr[$value -> erp_warehouse][] = [
                            'goods_id' => $value -> product_id,
                            'price' => $value -> price,
                            'number' => $value -> can_buy_num
                        ];
                    }

                    //按照仓库、下订单
                    //先 下展鹏事业部 的销售订单
                    //展鹏 开 销售单 给 this_business_id 的 this_business_user_id
                    //dd($stock_arr);
                    foreach($stock_arr as $erp_warehouse_id => $stock_arr_temp){

                        $res = $this -> addStockOrder([
                            'order_info_json'  => json_encode($stock_arr_temp),    //下单json 新增明细   [{"goods_id 商品id":"1","price价格":"124","number数量":"55"},{}]
                            'warehouse_id'     => $erp_warehouse_id,           //仓库id
                            'business_id'      => config('admin.zhanpeng_business_id'),             //事业部id
                            'sale_date'        => date('Y-m-d'),     //销售日期 date类型
                            'user_id'          => $this_business_user_id,                     //wxuser的id
                            'sale_user_id'     => 0,           //销售员users id
                            'freight'          => 0,                     //运费
                            'substitute'       => 0,               //代发费
                            'send_remark'      => 1,             //发货备注 0发货1不发货2预定
                            'province'         => isset($arr_tmp['province'])?$arr_tmp['province']:'',                  //收件人省
                            'city'             => isset($arr_tmp['city'])?$arr_tmp['city']:'',                          //收件人市
                            'area'             => isset($arr_tmp['area'])?$arr_tmp['area']:'',                          //收件人区
                            'tel'              => isset($arr_tmp['tel'])?$arr_tmp['tel']:'',                            //收件人手机
                            'phone'            => isset($arr_tmp['phone'])?$arr_tmp['phone']:'',                        //收件人电话
                            'name'             => isset($arr_tmp['name'])?$arr_tmp['name']:'',                          //收件人姓名
                            'address'          => isset($arr_tmp['address'])?$arr_tmp['address']:'',                    //收件人地址
                            'send_name'        => isset($arr_tmp['send_name'])?$arr_tmp['send_name']:'',                //发件人
                            'send_tel'         => isset($arr_tmp['send_tel'])?$arr_tmp['send_tel']:'',                  //发件人
                            'send_phone'       => isset($arr_tmp['send_phone'])?$arr_tmp['send_phone']:'',                  //发件人
                            'send_province'    => isset($arr_tmp['send_province'])?$arr_tmp['send_province']:'',        //发件人
                            'send_city'        => isset($arr_tmp['send_city'])?$arr_tmp['send_city']:'',                //发件人
                            'send_area'        => isset($arr_tmp['send_area'])?$arr_tmp['send_area']:'',                //发件人
                            'send_address'     => isset($arr_tmp['send_address'])?$arr_tmp['send_address']:'',          //发件人
                            'remark'           => isset($arr_tmp['remark'])?$arr_tmp['remark']:'',                      //备注
                            'sale_remark'      => isset($arr_tmp['sale_remark'])?$arr_tmp['sale_remark']:'',            //销售备注
                            'user_remark'      => isset($arr_tmp['user_remark'])?$arr_tmp['user_remark']:'',            //客户备注
                            'operator_user_id' => 0,   //后台操作员users id
                            'insert_type'      => isset($arr_tmp['insert_type'])?$arr_tmp['insert_type']:0,             //添加类型， erp添加值为0 小程序添加值为1
                            'idNumber'         => isset($arr_tmp['idNumber'])?$arr_tmp['idNumber']:'',             //身份证号码
                            'imageFront'       => isset($arr_tmp['imageFront'])?$arr_tmp['imageFront']:'',             //身份证正面
                            'imageBack'        => isset($arr_tmp['imageBack'])?$arr_tmp['imageBack']:'',             //身份证反面
                            'ident_id'        => isset($arr_tmp['ident_id'])?$arr_tmp['ident_id']:0,
                        ]);


                    }


                    //this_business_id 开 采购单 供应商 为 展鹏
                    //business_id 为 $arr['business_id']
                    $purchase_id = PurchaseOrder::addAgentPurchaseOrder([
                        'business_id' => $arr['business_id'],
                        'goods_arr' => $goods_arr, //采购明细 id price number

                    ],1);

                    if(!$purchase_id){
                        return [
                            'code' => 500,
                            'msg' => '该事业部数据有误'
                        ];
                    }



                    //这个事业部 已经有货了  直接开采购单
                }
            }



            //直接在这个事业部下单
            //仓库 找到 虚拟2库
            $erp_warehouse_info = DB::table('erp_warehouse')
                -> where([
                    'business_id' => $arr_tmp['business_id']
                ])
                -> where('name','like','%虚拟2库%') -> first();

            //dump($arr_tmp['business_id']);
            //dump($erp_warehouse_info);

            $stock_order_res = $this -> addStockOrder([
                'order_info_json'  => isset($arr_tmp['order_info_json'])?$arr_tmp['order_info_json']:'',    //下单json 新增明细   [{"goods_id 商品id":"1","price价格":"124","number数量":"55"},{}]
                'warehouse_id'     => $erp_warehouse_info -> id,           //仓库id
                'business_id'      => $arr_tmp['business_id'],             //事业部id
                'sale_date'        => isset($arr_tmp['sale_date'])?$arr_tmp['sale_date']:date('Y-m-d'),     //销售日期 date类型
                'user_id'          => isset($arr_tmp['user_id'])?$arr_tmp['user_id']:0,                     //wxuser的id
                'sale_user_id'     => isset($arr_tmp['sale_user_id'])?$arr_tmp['sale_user_id']:0,           //销售员users id
                'freight'          => isset($arr_tmp['freight'])?$arr_tmp['freight']:0,                     //运费
                'substitute'       => isset($arr_tmp['substitute'])?$arr_tmp['substitute']:0,               //代发费
                'send_remark'      => isset($arr_tmp['send_remark'])?$arr_tmp['send_remark']:0,             //发货备注 0发货1不发货2预定
                'province'         => isset($arr_tmp['province'])?$arr_tmp['province']:'',                  //收件人省
                'city'             => isset($arr_tmp['city'])?$arr_tmp['city']:'',                          //收件人市
                'area'             => isset($arr_tmp['area'])?$arr_tmp['area']:'',                          //收件人区
                'tel'              => isset($arr_tmp['tel'])?$arr_tmp['tel']:'',                            //收件人手机
                'phone'            => isset($arr_tmp['phone'])?$arr_tmp['phone']:'',                        //收件人电话
                'name'             => isset($arr_tmp['name'])?$arr_tmp['name']:'',                          //收件人姓名
                'address'          => isset($arr_tmp['address'])?$arr_tmp['address']:'',                    //收件人地址
                'send_name'        => isset($arr_tmp['send_name'])?$arr_tmp['send_name']:'',                //发件人
                'send_tel'         => isset($arr_tmp['send_tel'])?$arr_tmp['send_tel']:'',                  //发件人
                'send_phone'       => isset($arr_tmp['send_phone'])?$arr_tmp['send_phone']:'',                  //发件人
                'send_province'    => isset($arr_tmp['send_province'])?$arr_tmp['send_province']:'',        //发件人
                'send_city'        => isset($arr_tmp['send_city'])?$arr_tmp['send_city']:'',                //发件人
                'send_area'        => isset($arr_tmp['send_area'])?$arr_tmp['send_area']:'',                //发件人
                'send_address'     => isset($arr_tmp['send_address'])?$arr_tmp['send_address']:'',          //发件人
                'remark'           => isset($arr_tmp['remark'])?$arr_tmp['remark']:'',                      //备注
                'sale_remark'      => isset($arr_tmp['sale_remark'])?$arr_tmp['sale_remark']:'',            //销售备注
                'user_remark'      => isset($arr_tmp['user_remark'])?$arr_tmp['user_remark']:'',            //客户备注
                'operator_user_id' => isset($arr_tmp['operator_user_id'])?$arr_tmp['operator_user_id']:0,   //后台操作员users id
                'insert_type'      => isset($arr_tmp['insert_type'])?$arr_tmp['insert_type']:0,             //添加类型， erp添加值为0 小程序添加值为1
                'idNumber'         => isset($arr_tmp['idNumber'])?$arr_tmp['idNumber']:'',             //身份证号码
                'imageFront'       => isset($arr_tmp['imageFront'])?$arr_tmp['imageFront']:'',             //身份证正面
                'imageBack'        => isset($arr_tmp['imageBack'])?$arr_tmp['imageBack']:'',             //身份证反面
                'ident_id'        => isset($arr_tmp['ident_id'])?$arr_tmp['ident_id']:'',
            ]);
            //dd($stock_order_res);


            if($stock_order_res){
                if($stock_order_res['code'] == '500'){
                    DB::rollBack();
                    return $stock_order_res;
                }else{
                    DB::commit();
                    return $stock_order_res;
                }
            }else{
                DB::rollBack();
                return [
                    'code' => 500,
                    'msg' => '有误'
                ];
            }
        }catch (\Exception $exception){
            DB::rollBack();
            return [
                'code' => 500,
                'msg' => $exception -> getTraceAsString()
            ];
        }

















        //代理版小程序下单

        //凑齐每个商品

        //优先取本事业部 看是否有库存。 没有 去其他事业部找

        //1、先按照逻辑 按照货物数量 取货。

        //2、货物来自于哪几个事业部。 分事业部 下本事业部的采购订单

        //3、创建销售单


    }




    //在其他事业部获取库存

    /**
     * @param $product_id 商品id
     * @param $num 数量
     */
    public function getStockOtherBusiness($product_id,$num){
        //根据商品id 获取 库存拥有这个商品的 事业部权重
        $business_info = $this -> getProductWeight($product_id);
        $num_all = 0;
        foreach($business_info as $k => $vo){
            $num_all += $vo -> can_buy_num;
        }
        if($num > $num_all){
            return [
                'code' => 500,
                'msg' => '商品id'.$product_id.'数量不足'
            ];
        }

        foreach($business_info as $k => $vo){
            $this_can_buy_num = intval($vo -> can_buy_num);

            if($this_can_buy_num < $num ){
                //取 $this_can_buy_num

                continue;
            }elseif($this_can_buy_num > $num){
                //取$num

                continue;
            }else{
                //$this_can_buy_num == $num  取完结束

                break;
            }


        }


        for($i = $num;$i >0 ;$i-- ){

        }


    }



    public function addStockFromBusiness($business_id,$product_id,$store_house_id){
        //$business = DB::table('')
    }



    //根据权重 取某商品的库存
    public function getProductWeight($product_id, $mp_name_id ,$this_business_id)
    {
        $data = DB::table('erp_stock')
            -> leftJoin('erp_business','erp_stock.business_id','erp_business.id')
            -> leftJoin('erp_storehouse','erp_stock.store_house_id','erp_storehouse.id')
            -> leftJoin('erp_warehouse','erp_storehouse.warehouse_id','erp_warehouse.id')
            -> where([
                'erp_stock.product_id' => $product_id,
                'erp_stock.flag' => 0,
                //必须取
            ])
            -> where('can_buy_num','>',0)
            -> where('erp_warehouse.mp_name_id', $mp_name_id)
            -> orderBy('erp_business.id','desc')
            //-> orderByRaw('FIELD(erp_stock.business_id,'.$this_business_id.');')
            //-> orderByRaw(DB::raw("FIELD(erp_stock.business_id, $this_business_id)"))
            -> select([
                'erp_business.id as business_id',
                'erp_stock.can_buy_num',
                'erp_stock.store_house_id',
                'erp_warehouse.id as erp_warehouse',
                'erp_stock.product_id'
            ])
            -> get();

        //优先取展鹏的货
        $new_temp = [];
        foreach($data as $k => $vo){
            if($vo -> business_id == config('admin.zhanpeng_business_id')){
                $new_temp[] = $vo;
                unset($data[$k]);
            }
        }
        foreach($data as $k => $vo){
            $new_temp[] = $vo;
        }

        return $new_temp;
    }


    //删除库存销售单
    public function deleteOrder($stock_order_id,$business_id){
        $stock_order = DB::table('erp_stock_order')
            -> where([
                'id'          => $stock_order_id,
                'business_id' => $business_id,
                'flag'        => 0
            ]) -> first();
        if(!$stock_order){
            return [
                'code' => 500,
                'msg'  => '没有此数据'
            ];
        }

        if($stock_order -> pay_status){
            return [
                'code' => 500,
                'msg'  => '该订单已支付，不可删除'
            ];
        }

        $deliver_goods_record = DB::table('erp_deliver_goods_record')
            -> where([
                'stock_order_id' => $stock_order_id,
                'flag'           => 0
            ]) -> first();
        if($deliver_goods_record){
            return [
                'code' => 500,
                'msg'  => '已存在发货记录，不可删除'
            ];
        }

        DB::beginTransaction();
        try{
            PublicStockOrder::publicOrderCancel($stock_order_id);
            //销售详情
            $stock_order_info = DB::table('erp_stock_order_info')
                -> where([
                    'stock_order_id' => $stock_order -> id,
                    'flag'           => 0
                ]) -> get();

            $product_ids = [];
            $updated_special_ids=[];
            foreach($stock_order_info as $vo){
                $temp = DB::table('erp_stock_order_info_receive')
                    -> where([
                        'stock_order_info_id' => $vo -> id,
                        'flag'                => 0
                    ]) -> get();
                foreach($temp as $value){
                    // 把每个 receive_goods_record_id 的数量 退回去 $value -> number
                    $receive_goods_record = DB::table('erp_receive_goods_record')
                        -> leftJoin('erp_logistics_info','erp_receive_goods_record.goods_id','erp_logistics_info.id')
                        -> leftJoin('erp_purchase_order_goods','erp_logistics_info.goods_id','erp_purchase_order_goods.id')
                        -> leftJoin('erp_product_list','erp_product_list.id','erp_purchase_order_goods.product_id')
                        -> select([
                            'erp_receive_goods_record.*',
                            'erp_purchase_order_goods.product_id',
                            'erp_product_list.is_public'
                        ])
                        -> where([
                            'erp_receive_goods_record.id' => $value -> receive_goods_record_id
                        ]) -> first();


                    DB::table('erp_receive_goods_record')
                        -> where([
                            'id' => $value -> receive_goods_record_id
                        ]) -> update([
                            'can_buy_num' => intval($value -> number) + intval($receive_goods_record -> can_buy_num)
                        ]);

                    $product_ids[] = $receive_goods_record -> product_id;
                    $stock = DB::table('erp_stock')
                        -> where([
                            'business_id' => $business_id,
                            'store_house_id' => $receive_goods_record -> store_house_id,
                            'product_id' => $receive_goods_record -> product_id,
                            'flag' => 0
                        ]) -> first();
                    Log::info([
                        'type' => 'stockOrder-1271',
                        'waiting_num' => intval($stock -> waiting_num) - intval($value -> number)
                    ]);
                    DB::table('erp_stock')
                        -> where([
                            'id' => $stock -> id
                        ]) -> update([
                            'can_buy_num' => intval($stock -> can_buy_num) + intval($value -> number),
                            'waiting_num' => intval($stock -> waiting_num) - intval($value -> number),
                            'current_num' => intval($stock -> current_num) + intval($value -> number),
                            'updated_at' => time()
                        ]);
                    if($receive_goods_record->is_public == 1){
                        $public =  DB::table("erp_sku_review")->where([
                            'business_id' => $business_id,
                            'status' => 1,
                            'sku_id' => $receive_goods_record -> product_id,
                        ])->first();
                        DB::table("erp_sku_review")->where([
                            'business_id' => $business_id,
                            'status' => 1,
                            'sku_id' => $receive_goods_record -> product_id,
                        ])->increment('can_buy_num', intval($value -> number));

                        DB::table('erp_sku_public_log')->insert([
                            'sku_id' => $receive_goods_record -> product_id,
                            'business_id' => $business_id,
                            'new_num' => $public->can_buy_num + intval($value -> number),
                            'old_num' => $public->can_buy_num ,
                            'type' => 'orderback',
                            'created_at' => date("Y-m-d H:i:s", time()),
                            'order_id'  =>$vo->id
                        ]);

                    }
                }

                DB::table('erp_stock_order_info_receive')
                    -> where([
                        'stock_order_info_id' => $vo -> id,
                        'flag' => 0
                    ]) -> update([
                        'flag' => 1
                    ]);


                //更新限时特价库存
                if ($vo->special_id != 0 && !in_array($vo->special_id,$updated_special_ids)){
                    DB::table('erp_special_price')->whereIn('id',explode('_',$vo->special_id))->decrement('sold_num',$vo->union_number);
                    $updated_special_ids[]=$vo->special_id;
                }

            }

            if(count($product_ids)){
                $skuRepository = new SKURepository();
                $skuRepository -> autoPutOnOrOff($product_ids);
            }


            DB::table('erp_stock_order') -> where([
                'id' => $stock_order -> id
            ]) -> update([
                'flag' => 1,
                'updated_at' => time()
            ]);
            DB::table('erp_stock_order_info') -> where([
                'stock_order_id' => $stock_order -> id
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
                'msg' => $exception ->getMessage()
            ];
        }




    }


    //库存销售单支付

    /**
     * @param $order_pay_arr  销售订单支付所需数组 [
     * [
     * 'id'=>'订单id',
     * 'pay_price'=>'支付金额',
     * 'service_charge'=>'服务费',
     * 'cert_num'=>'交易凭证号',
     * 'remark'=>'用户备注',
     * ]
     * ]
     * @param $user_id 后台操作员
     * @param $account_id 收款账户id
     * @param $business_id 事业部id
     * @param $log_type 记录收款日志 10小程序订单收款, 7erp订单收款
     * @param $pay_method 支付方式  见 admin.php stock_order_pay_method
     *
     * @return array
     */
    public function payOrder($order_pay_arr,$user_id,$account_id,$business_id,$log_type=7,$pay_method=0){
        DB::beginTransaction();
        try{
            $stock_order_pay_method = config('admin.stock_order_pay_method');
            if($pay_method && empty($stock_order_pay_method[$pay_method])){
                return [
                    'code' => 500,
                    'msg'  => '没有此支付方式'
                ];
            }
            if(!$account_id){
                return [
                    'code' => 500,
                    'msg'  => '收款账号错误'
                ];
            }
            $account_info = DB::table('erp_account')
                -> where([
                    'id' => $account_id
                ]) -> first();
            if(!$account_info){
                return [
                    'code' => 500,
                    'msg'  => '没有此收款账户'
                ];
            }

            if(!$user_id){
                return [
                    'code' => 500,
                    'msg'  => '缺少后台操作员'
                ];
            }






            foreach($order_pay_arr as $vo){
                //判断订单是否存在
                $order_temp = DB::table('erp_stock_order')
                    -> where([
                        'id'   => $vo['id'],
                        'flag' => 0
                    ]) -> first();
                if(!$order_temp){
                    DB::rollBack();
                    return [
                        'code' => 500,
                        'msg'  => '没有此订单'
                    ];
                }



                DB::table('erp_stock_order_pay') -> insertGetId([
                    'order_id'       => $vo['id'],
                    'price'          => $vo['pay_price'],
                    'service_charge' => $vo['service_charge'],
                    'cert_num'       => $vo['cert_num'],
                    'remark'         => $vo['remark'],
                    'account_id'     => $account_id,
                    'created_at'     => time(),
                    'updated_at'     => time(),
                    'pay_user_id'    => $user_id,
                    'pay_method' => $pay_method
                ]);

                $vo['service_charge'] = $vo['service_charge']?$vo['service_charge']:0;

                Log::info("记录付款记录:".$vo['id']);
                Log::info(json_encode($vo));
                Log::info('pay_price:'.$order_temp -> pay_price);
                Log::info('price:'.$order_temp -> price);
                Log::info('freight:'.$order_temp -> freight);
                Log::info('substitute:'.$order_temp -> substitute);
                Log::info("总付款金额: ". (floatval($vo['pay_price']) - floatval($vo['service_charge']) + floatval($order_temp -> pay_price)) );
                Log::info("应付金额: ". (floatval($order_temp -> price) + floatval($order_temp -> freight) + floatval($order_temp -> substitute))   );

                //付款后 修改销售订单状态
                $paid=bcadd(bcsub($vo['pay_price'],$vo['service_charge'],2),$order_temp -> pay_price,2);//已付
                $to_pay=bcadd(bcadd($order_temp -> price,$order_temp -> freight,2),$order_temp -> substitute,2);//需付
                //if( floatval((floatval($vo['pay_price']) - floatval($vo['service_charge']) + floatval($order_temp -> pay_price))) >= floatval((floatval($order_temp -> price) + floatval($order_temp -> freight) + floatval($order_temp -> substitute)))  ){
                if(bccomp($paid,$to_pay,2) != -1){
                    //已支付
                    $pay_status = 1;
                }else{
                    $pay_status = 0;
                }

                //订单已付款金额增加
                $update_data=[
                    'pay_price' => floatval($vo['pay_price']) - floatval($vo['service_charge']) + floatval($order_temp -> pay_price),
                    'pay_status' => $pay_status,
                    'pay_method' => $pay_method
                ];
                //原装正品和施华洛世奇馆区直接改为发货
                if ($pay_status && ($order_temp->mp_id==5 || $order_temp->mp_id==7)){
                    $update_data['send_remark']=0;
                }
                DB::table('erp_stock_order')
                    -> where([
                        'id' => $vo['id'],
                    ]) -> update($update_data);

                Log::info('payOrder方法-id为'.$vo['id'].'的erp_stock_order更新pay_status为:'.$pay_status);

                //账户余额增加
                Account::addLog([
                    'business_id'  => $business_id,
                    'account_id'   => $account_id,
                    'user_id'      => $user_id,
                    'log_type'     => $log_type,
                    'price'        => floatval($vo['pay_price']) - floatval($vo['service_charge']),
                    'parameter_id' => $vo['id']
                ]);

                //账户余额增加
                $pay_id = DB::table('erp_purchase_order_pay')
                    -> insertGetId([
                        'order_id'       => $vo['id'],
                        'pay_price'      => floatval($vo['pay_price']),
                        'service_charge' => floatval($vo['service_charge']),
                        'account_id'     => $account_id,
                        'pay_type'       => 2,
                        'pay_user_id'    => $user_id,
                        'created_at'     => time(),
                        'updated_at'     => time()
                    ]);



                //增加账户余额
                DB::table('erp_account')
                    -> where([
                        'id' => $account_id
                    ]) -> increment('balance',floatval($vo['pay_price']) - floatval($vo['service_charge']));

                if($pay_method == 2){
                    $wxuser_id = $order_temp -> user_id;
                    //如果是erp的余额支付， 则 需要扣除余额
                    DB::table('wxuser')
                        -> where('id', $wxuser_id)
                        -> decrement('price', floatval($vo['pay_price']) );

                    $wxUser = DB::table('wxuser')
                        -> where('id', $wxuser_id)
                        -> first();

                    // 2是增加付款日志在price_log里
                    DB::table('price_log') -> insert([
                        'userid'       => $wxuser_id,
                        'price'        => floatval($vo['pay_price']),
                        'created_at'   => time(),
                        'updated_at'   => time(),
                        'from_user_id' => 1,
                        'type'         => 10,//erp扣款
                        'in_out'       => 1,// 1支出
                        'order_id'     => $vo['id'],
                        'end_price'    => $wxUser->price,
                    ]);
                }

                //更新团购单状态
                DB::table('erp_market_group_buyers')->where('order_id',$order_temp->id)->update(['order_status'=>1]);
                if ($order_temp->group_status==1){
                    DB::table('erp_stock_order')->where('id',$order_temp->id)->update(['group_status'=>2]);
                }elseif ($order_temp->group_status==3){
                    DB::table('erp_stock_order')->where('id',$order_temp->id)->update(['group_status'=>4]);
                }


            }
            DB::commit();
            return [
                'code' => 200,
                'msg' => '支付成功'
            ];
        }catch (\Exception $exception){
            DB::rollBack();
            return [
                'code' => 500,
                'msg' => $exception->getMessage()
            ];
        }



    }



    //编辑库存销售单
    public function editOrder($stock_order_id,$order_info_json,$warehouse_id,$business_id,$order_field){


        //先判断 库存销售订单的状态
        $stock_order_info = DB::table('erp_stock_order')
            -> where([
                'id' => $stock_order_id
            ]) -> first();
        if($stock_order_info -> send_status > 0){
            return [
                'code' => 500,
                'msg' => '已发货不允许编辑'
            ];
        }


        //校验商品明细 json
        $order_info_arr = json_decode($order_info_json,true);
        if(!count($order_info_arr)){
            return [
                'code' => 500,
                'msg' => '参数有误'
            ];
        }


        $temp_goods_id_arr = [];
        foreach($order_info_arr as $vo){
            if(!isset($vo['goods_id']) || !isset($vo['price']) || !isset($vo['number']) || !$vo['goods_id'] || !$vo['price'] || !$vo['number'] ){
                return [
                    'code' => 500,
                    'msg' => '参数有误'
                ];
            }

            //判断goods_id 是否重复
            if(in_array($vo['goods_id'],$temp_goods_id_arr)){
                return [
                    'code' => 500,
                    'msg' => '参数中商品id重复'
                ];
            }
            $temp_goods_id_arr[] = $vo['goods_id'];

            //判断goods_id 是否在 库位中
            $stock_num = Stock::where('warehouse_id',$warehouse_id) -> where([
                'product_id' => $vo['goods_id'],
                'flag' => 0
            ]) -> sum('can_buy_num');

            $temp = DB::table('erp_stock_order_info') -> where([
                'stock_order_id' => $stock_order_id,
                'product_id' => $vo['goods_id'],
                'flag' => 0
            ]) -> first();
            /*
            if(!$temp){
                return [
                    'code' => 500,
                    'msg' => '数据有误'
                ];
            }
            */

            $stock_num += intval($temp?$temp -> number:0);
            if(!$stock_num){
                return [
                    'code' => 500,
                    'msg' => '该仓库中没有此商品'
                ];
            }

            //看库存够不够
            if(intval($stock_num) < $vo['number']){
                return [
                    'code' => 500,
                    'msg' => '数量不够'
                ];
            }


        }

        DB::beginTransaction();
        try{
            //订单id
            $stock_id = $stock_order_id;
            $stock_order = StockOrder::where([
                'id' => $stock_id
            ]) -> first();


            DB::table('erp_stock_order') -> where([
                'id' => $stock_id
            ]) -> update([
                'freight'          => $order_field -> freight,
                'substitute'       => $order_field -> substitute,
                'send_remark'      => $order_field -> send_remark,
                'province'         => $order_field -> province,
                'city'             => $order_field -> city,
                'area'             => $order_field -> area,
                'tel'              => $order_field -> tel,
                'phone'            => $order_field -> phone,
                'name'             => $order_field -> name,
                'address'          => $order_field -> address,
                'send_name'        => $order_field -> send_name,
                'send_tel'         => $order_field -> send_tel,
                'send_phone'         => $order_field -> send_phone,
                'send_province'    => $order_field -> send_province,
                'send_city'        => $order_field -> send_city,
                'send_area'        => $order_field -> send_area,
                'send_address'     => $order_field -> send_address,
                'remark'           => $order_field -> remark,
                'sale_remark'      => $order_field -> sale_remark,
                'user_remark'      => $order_field -> user_remark,
                'sale_date' => strtotime($order_field -> sale_date),
            ]);

            //判断 如果是

            //删除旧的订单详情开始
            $order_info = DB::table('erp_stock_order_info')
                -> where([
                    'stock_order_id' => $stock_id,
                    'flag' => 0
                ]) -> get();

            //销售订单编辑，先把旧记录删掉，把仓库收货记录数量都还回去。 再重新下单
            foreach($order_info as $k => $vo){

                $receive = DB::table('erp_stock_order_info_receive')
                    -> where([
                        'stock_order_info_id' => $vo -> id,
                        'flag' => 0
                    ]) -> get();

                foreach($receive as $key => $value){
                    //把这些数量 都退到 receive_goods_record 里
                    DB::table('erp_stock_order_info_receive')
                        -> where([
                            'id' => $value -> id
                        ]) -> update([
                            'flag' => 1
                        ]);

                    //退回入库
                    //入库记录
                    $receive_goods_record = DB::table('erp_receive_goods_record')
                        -> where([
                            'id' => $value -> receive_goods_record_id,
                        ]) -> first();

                    // + can_buy_num
                    DB::table('erp_receive_goods_record')
                        -> where([
                            'id' => $value -> receive_goods_record_id
                        ]) -> update([
                            'can_buy_num' => intval($receive_goods_record -> can_buy_num) + intval($value -> number),
                            'updated_at' => time()
                        ]);
                    $stock_info = DB::table('erp_stock')
                        -> where([
                            'business_id' => $stock_order -> business_id,
                            'store_house_id' => $receive_goods_record -> store_house_id,
                            'product_id' => $receive_goods_record -> product_id,
                            'flag' => 0
                        ]) -> first();
                    if($stock_info){
                        //增加 库存里的 可售数量
                        //减少待发货数量
                        DB::table('erp_stock') -> where([
                            'id' => $stock_info -> id
                        ]) -> update([
                            'can_buy_num' => intval($stock_info -> can_buy_num) + intval($value -> number),
                            'waiting_num' => intval($stock_info -> waiting_num) - intval($value -> number),
                        ]);
                        Log::info([
                            'type' => 'stockOrder-1719',
                            'waiting_num' => intval($stock_info -> waiting_num) - intval($value -> number)
                        ]);
                    }
                }

                DB::table('erp_stock_order_info')
                    -> where([
                        'id' => $vo -> id
                    ]) -> update([
                        'flag' => 1
                    ]);

            }


            //把旧的退掉结束



            //销售金额
            $pay_price = 0;
            foreach($order_info_arr as $vo){
                if(!isset($vo['goods_id']) || !isset($vo['price']) || !isset($vo['number']) || !$vo['goods_id'] || !$vo['price'] || !$vo['number'] ){
                    DB::rollBack();
                    return [
                        'code' => 500,
                        'msg' => 'json参数有误'
                    ];
                }

                //库存下单详情
                $stock_order_info_id = StockOrderInfo::insertGetId([
                    'stock_order_id' => $stock_id,
                    'product_id'     => $vo['goods_id'],
                    'number'         => $vo['number'],     //这个数量 可能是从几个仓库到货订单里凑的
                    'price'          => $vo['price'],
                    'created_at'     => time(),
                    'updated_at'     => time()
                ]);



                $pay_price += intval($vo['number']) * floatval($vo['price']);

                //库存下单详情 对应多条仓库到货记录扣除记录
                //查找 receive_goods_record ， 的每个商品 到库记录， 去凑销售数量
                $number = intval($vo['number']);

                while ($number > 0 ){

                    //找 这个仓库 的入库记录
                    $receive_info = ReceiveGoodsRecord::where([
                        'product_id'   => $vo['goods_id'],
                        'warehouse_id' => $warehouse_id,
                        'business_id'  => $business_id,
                        'flag'         => 0
                    ]) -> where('can_buy_num','>',0) -> first();


                    if(!$receive_info){
                        DB::rollBack();
                        return [
                            'code' => 500,
                            'msg' => '数据有误，该仓库下没有可售数量',
                        ];
                    }


                    //如果需要买的数量 比到货记录的可售数量大
                    if($number >= $receive_info -> can_buy_num ){
                        $number -= $receive_info -> can_buy_num;
                        //更新can_buy_num

                        ReceiveGoodsRecord::where('id',$receive_info -> id) -> update([
                            'can_buy_num' => 0,
                            'updated_at' => time(),
                        ]);

                        //记录receive_goods_record 与 stock_order_info 的关系
                        DB::table('erp_stock_order_info_receive') -> insert([
                            'stock_order_info_id'     => $stock_order_info_id,
                            'receive_goods_record_id' => $receive_info -> id,
                            'number'                  => $receive_info -> can_buy_num,
                            'created_at'              => time(),
                            'updated_at'              => time(),
                        ]);

                        $stock_info = DB::table('erp_stock')
                            -> where([
                                'business_id'    => $business_id,
                                'store_house_id' => $receive_info -> store_house_id,
                                'product_id'     => $vo['goods_id'],
                                'flag'           => 0
                            ]) -> first();
                        if($stock_info){
                            DB::table('erp_stock')
                                -> where([
                                    'id' => $stock_info -> id
                                ]) -> update([
                                    //减去 可售数量
                                    'can_buy_num' => intval($stock_info -> can_buy_num) - intval($receive_info -> can_buy_num),
                                    //增加 待发货里那个
                                    'waiting_num' => intval($stock_info -> waiting_num) + intval($receive_info -> can_buy_num),
                                ]);
                            Log::info([
                                'type' => 'stockOrder-1824',
                                'waiting_num' => intval($stock_info -> waiting_num) + intval($receive_info -> can_buy_num)
                            ]);
                        }

                    }else{
                        ReceiveGoodsRecord::where('id',$receive_info -> id) -> update([
                            'can_buy_num' => intval($receive_info -> can_buy_num) - $number,
                            'updated_at'  => time(),
                        ]);

                        //记录receive_goods_record 与 stock_order_info 的关系
                        //代表此次发货  是从哪次仓库收货记录中取的
                        DB::table('erp_stock_order_info_receive') -> insert([
                            'stock_order_info_id'     => $stock_order_info_id,
                            'receive_goods_record_id' => $receive_info -> id,
                            'number'                  => $number,
                            'created_at'              => time(),
                            'updated_at'              => time(),
                        ]);


                        $stock_info = DB::table('erp_stock')
                            -> where([
                                'business_id'    => $business_id,
                                'store_house_id' => $receive_info -> store_house_id,
                                'product_id'     => $vo['goods_id'],
                                'flag'           => 0
                            ]) -> first();

                        if($stock_info){
                            DB::table('erp_stock')
                                -> where([
                                    'id' => $stock_info -> id
                                ]) -> update([
                                    //减去 可售数量
                                    'can_buy_num' => intval($stock_info -> can_buy_num) - intval($number),
                                    //增加 待发货里那个
                                    'waiting_num' => intval($stock_info -> waiting_num) + intval($number),
                                ]);
                            Log::info([
                                'type' => 'stockOrder-1866',
                                'waiting_num' => intval($stock_info -> waiting_num) + intval($number)
                            ]);
                        }
                        break;
                    }

                }
            }


            //付款后 修改销售订单状态
            /*if($stock_order -> pay_price - $stock_order -> service_charge == $stock_order -> price + $stock_order -> freight + $stock_order -> substitute){
                //已支付
                $pay_status = 1;
            }else{
                $pay_status = 0;
            }*/

            $pay_status = $stock_order->pay_status;



            //更新销售金额  到订单中
            DB::table('erp_stock_order') -> where([
                'id' => $stock_id
            ]) -> update([
                'price'      => $pay_price,
                'pay_status' => $pay_status
            ]);
            DB::commit();

            Log::info('editOrder方法-id为'.$stock_id.'的erp_stock_order更新pay_status为:'.$pay_status);


            return [
                'code' => 200,
                'msg' => '成功'
            ];





        }catch (\Exception $exception){
            DB::rollBack();
            return [
                'code' => 500,
                'msg'  => $exception->getTraceAsString()
            ];
        }




    }


    /**
     *生成在线支付订单单号
     * $order_ids array
     */
    public function createOnlineOrder($order_ids,$request)
    {
        //用户wxUserId_时间戳_10位随机字符串
        $online_order=$request->user->wxUserId.'_'.date('YmdHis').'_'.str_random(10);

        DB::table('erp_stock_order')->whereIn('id',$order_ids)->update([
            'online_order_num'=>$online_order
        ]);

        //备份
        foreach ($order_ids as $id){
            $erp_stock_order=DB::table('erp_stock_order')->where('id',$id)->first();
            DB::table('erp_online_order_backup')->insert([
                'order_id'=>$id,
                'online_order_num'=>$online_order,
                'before_pay_status'=>$erp_stock_order->pay_status,
                'before_pay_method'=>$erp_stock_order->pay_method,
                'after_pay_status'=>0,
                'after_pay_method'=>0,
                'created_at'=>date('Y-m-d H:i:s'),
            ]);
        }

        return $online_order;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function shopCarts()
    {
        return $this->hasMany(ShopCart::class,'orderId');
    }

    public static function asynOprate($orderId)
    {
        //更改订单状态为已付款
        DB::table('erp_stock_order')
            ->where('online_order_num', $orderId)
            -> update(['pay_status' => 1,'pay_method'=>3,'sale_date'=>time()]);
        //更改备份记录
        DB::table('erp_online_order_backup')
            ->where('online_order_num', $orderId)
            ->update([
                'after_pay_status'=>1,
                'after_pay_method'=>3,
                'updated_at'=>date('Y-m-d H:i:s'),
            ]);

        //更改post data记录表
        DB::table('erp_online_pay_post_data')
            ->where('orderId', $orderId)
            -> update(['payResult' => 1]);

        //其他需要更改逻辑
        $list=DB::table('erp_stock_order')
            ->where('online_order_num', $orderId)
            ->where('pay_status', 1)
            ->where('pay_method', 3)
            ->get();
        foreach ($list as $vo){

            PublicStockOrder::publicOrderAfterPay($vo);
            //原装正品和施华洛世奇馆区直接改为发货
            if ($vo->mp_id==5 || $vo->mp_id==7){
                DB::table('erp_stock_order')->where('id', $vo->id)->update(['send_remark' => 0]);
            }
            //其他逻辑
            $pay_price=$vo->freight+$vo->price;
            DB::table('erp_stock_order')->where('id',$vo->id)->update(['pay_price'=>$pay_price]);
            //更新团购单状态
            DB::table('erp_market_group_buyers')->where('order_id',$vo->id)->update(['order_status'=>1]);
            if ($vo->group_status==1){
                DB::table('erp_stock_order')->where('id',$vo->id)->update(['group_status'=>2]);
            }elseif ($vo->group_status==3){
                DB::table('erp_stock_order')->where('id',$vo->id)->update(['group_status'=>4]);
            }
            //order pay
            $order_pay=DB::table('erp_stock_order_pay')->where('order_id',$vo->id)->first();
            if(!$order_pay){
                DB::table('erp_stock_order_pay') -> insertGetId([
                    'order_id'       => $vo->id,
                    'price'          => $pay_price,
                    'service_charge' => 0,
                    'cert_num'       => '',
                    'remark'         => '',
                    'account_id'     => 1,
                    'created_at'     => time(),
                    'updated_at'     => time(),
                    'pay_user_id'    => 1,
                    'pay_method' => 3
                ]);
            }
            //price_log
            $price_log=DB::table('price_log')->where('order_id',$vo->id)->first();
            if(!$price_log){
                $wxUser = DB::table('wxuser')
                    -> where('id', $vo->user_id)
                    -> first();
                DB::table('price_log') -> insert([
                    'userid'       => $vo->user_id,
                    'price'        => $pay_price,
                    'created_at'   => time(),
                    'updated_at'   => time(),
                    'from_user_id' => 1,
                    'type'         => 8,//8商城小程序消费
                    'in_out'       => 1,// 1支出
                    'order_id'     => $vo->id,
                    'end_price'    => $wxUser->price,
                ]);
            }
        }
    }

    private function checkWarehouseStock($arr,$good_info)
    {
        //判断goods_id 是否在 库位中
        $stock_info = Stock::where('warehouse_id',$arr['warehouse_id']) -> where('product_id',$good_info['goods_id']) -> get();
        //dd($stock_info);
        if(!count($stock_info)){
            return [
                'code' => 500,
                'msg'  => '该仓库中没有此商品'
            ];
        }

        //检查number
        //检查 该仓库下 是否有 can_buy_num > num

        $this_can_buy_num = DB::table('erp_stock')
            -> where([
                'business_id'  => $arr['business_id'],
                'warehouse_id' => $arr['warehouse_id'],
                'product_id'   => $good_info['goods_id'],
                'flag'         => 0
            ])
            -> where('can_buy_num','>',0)
            -> sum('can_buy_num');
        if(intval($this_can_buy_num) < intval($good_info['number'])){
            return [
                'code' => 500,
                'msg'  => '仓库中数量不足'
            ];
        }
        return ['code'=>200];
    }

    private function updateStock($arr,$good_info,$stock_order_info_id)
    {
        $product_ids[] = $good_info['goods_id'];
        //库存下单详情 对应多条仓库到货记录扣除记录
        //查找 receive_goods_record ， 的每个商品 到库记录， 去凑销售数量
        $number = intval($good_info['number']);
        while ($number > 0 ){
            //找 这个仓库 的入库记录
            $receive_info = ReceiveGoodsRecord::where([
                'product_id'   => $good_info['goods_id'],
                'warehouse_id' => $arr['warehouse_id'],
                'business_id'  => $arr['business_id'],
            ]) -> where('can_buy_num','>',0) -> first();
            if(!$receive_info){
                DB::rollBack();
                return [
                    'code' => 500,
                    'msg'  => '数据有误，该仓库下没有可售数量',
                ];
            }
            if($number >= $receive_info -> can_buy_num ) {
                $number -= $receive_info->can_buy_num;
                //更新can_buy_num

                ReceiveGoodsRecord::where('id', $receive_info->id)->update([
                    'can_buy_num' => 0,
                    'updated_at' => time(),
                ]);

                //记录receive_goods_record 与 stock_order_info 的关系
                DB::table('erp_stock_order_info_receive')->insert([
                    'stock_order_info_id' => $stock_order_info_id,
                    'receive_goods_record_id' => $receive_info->id,
                    'number' => $receive_info->can_buy_num,
                    'created_at' => time(),
                    'updated_at' => time(),
                ]);

                //减去 库存里的 可售数量
                DB::table('erp_stock')
                    ->where([
                        'business_id' => $arr['business_id'],
                        'store_house_id' => $receive_info->store_house_id,
                        'product_id' => $good_info['goods_id'],
                        'flag' => 0
                    ])->decrement('can_buy_num', intval($receive_info->can_buy_num));

                //增加待发货数量
                DB::table('erp_stock')
                    ->where([
                        'business_id' => $arr['business_id'],
                        'store_house_id' => $receive_info->store_house_id,
                        'product_id' => $good_info['goods_id'],
                        'flag' => 0
                    ])->increment('waiting_num', intval($receive_info->can_buy_num));
            }else {
                ReceiveGoodsRecord::where('id', $receive_info->id)->update([
                    'can_buy_num' => intval($receive_info->can_buy_num) - $number,
                    'updated_at' => time(),
                ]);

                //记录receive_goods_record 与 stock_order_info 的关系
                //代表此次发货  是从哪次仓库收货记录中取的
                DB::table('erp_stock_order_info_receive')->insert([
                    'stock_order_info_id' => $stock_order_info_id,
                    'receive_goods_record_id' => $receive_info->id,
                    'number' => $number,
                    'created_at' => time(),
                    'updated_at' => time(),
                ]);

                //减去 库存里的 可售数量
                DB::table('erp_stock')
                    ->where([
                        'business_id' => $arr['business_id'],
                        'store_house_id' => $receive_info->store_house_id,
                        'product_id' => $good_info['goods_id'],
                        'flag' => 0
                    ])->decrement('can_buy_num', intval($number));

                //增加待发货数量
                DB::table('erp_stock')
                    ->where([
                        'business_id' => $arr['business_id'],
                        'store_house_id' => $receive_info->store_house_id,
                        'product_id' => $good_info['goods_id'],
                        'flag' => 0
                    ])->increment('waiting_num', intval($number));
                break;
            }
        }
        return ['code'=>200,'product_ids'=>$product_ids];
    }

    private function updateStockPublic($good_info,$stock_order_info_id,$type)
    {
        //库存下单详情 对应多条仓库到货记录扣除记录
        //查找 receive_goods_record ， 的每个商品 到库记录， 去凑销售数量
        $number = intval($good_info['number']);
        $skuRepository = new SKURepository();
        $public_stock =$skuRepository -> fetchSkuStock($good_info['goods_id']);
        $baseInfo  = [
            'number'  => $number,
            'stock_order_info_id' => $stock_order_info_id,
            'type'    => $type
        ];
        //先去消耗代理商自己的库存
        foreach ($public_stock['data'] as $k=>$v){
            if($v->business_id == $good_info['business_id']){
                $storeInfo = [
                    'business_id'  => $v->business_id,
                    'warehouse_id' => $v->warehouse_id,
                    'store_house_id' => $v->storehouse_id,
                ];
                if($number <= $v->min_buy_num){
                    $baseInfo['number'] = $number;
                    $number = 0;
                }else{
                    $baseInfo['number'] = $v->min_buy_num;
                    $number -= $v->min_buy_num;
                    unset($public_stock['data'][$k]);
                }
                $res =$this->baseStockOff($baseInfo,$good_info,$storeInfo);
                if($res['code'] != 200){
                    return $res;
                }
                break;
            }
        }
        if($number > 0 ){
            foreach ($public_stock['data'] as $v){
                if($number >0 ){
                    $storeInfo = [
                        'business_id'  => $v->business_id,
                        'warehouse_id' => $v->warehouse_id,
                        'store_house_id' => $v->storehouse_id,
                    ];
                    if($number > $v->min_buy_num){
                        $baseInfo['number'] =  $v->min_buy_num;
                        $number -= $v->min_buy_num;
                    }else{
                        $baseInfo['number'] = $number;
                        $number = 0;
                    }
                    $res =$this->baseStockOff($baseInfo,$good_info,$storeInfo);
                    if($res['code'] != 200){
                        return $res;
                    }
                }
            }
        }
        return ['code'=> 200];

    }

    private function baseStockOff($baseInfo,$good_info,$arr){
        $product_ids[] = $good_info['goods_id'];
        $number = $baseInfo['number'];
        $stock_order_info_id = $baseInfo['stock_order_info_id'];
        $public = DB::table("erp_sku_review")->where([
            'business_id' => $arr['business_id'],
            'status' => 1,
            'sku_id' => $good_info['goods_id'],
        ])->first();
        DB::table('erp_public_order')->insert([
            'order_info_id' => $stock_order_info_id,
            'business_id'   => $arr['business_id'],
            'num'           => $number,
            'created_at'    => time(),
            'type'          => $baseInfo['type']
            ]);
        //找 这个仓库 的入库记录
        $receive_info = ReceiveGoodsRecord::where([
            'product_id'   => $good_info['goods_id'],
            'store_house_id' => $arr['store_house_id'],
            'business_id'  => $arr['business_id'],
        ]) -> where('can_buy_num','>',0) -> first();
        if(!$receive_info){
            DB::rollBack();
            return [
                'code' => 500,
                'msg'  => '数据有误，该仓库下没有可售数量',
            ];
        }
        //去掉公有库存

        DB::table("erp_sku_review")->where([
            'business_id' => $arr['business_id'],
            'status' => 1,
            'sku_id' => $good_info['goods_id'],
        ])->decrement('can_buy_num', $number);

        DB::table('erp_sku_public_log')->insert([
            'sku_id' => $good_info['goods_id'],
            'business_id' => $arr['business_id'],
            'new_num' => $public->can_buy_num -$number,
            'old_num' => $public->can_buy_num ,
            'type' => 'order',
            'created_at' => date("Y-m-d H:i:s", time()),
            'order_id'  =>$stock_order_info_id
        ]);
        ReceiveGoodsRecord::where('id',$receive_info -> id) -> update([
            'can_buy_num' => intval($receive_info -> can_buy_num) - $number,
            'updated_at'  => time(),
        ]);

        //记录receive_goods_record 与 stock_order_info 的关系
        //代表此次发货  是从哪次仓库收货记录中取的
        DB::table('erp_stock_order_info_receive') -> insert([
            'stock_order_info_id'     => $stock_order_info_id,
            'receive_goods_record_id' => $receive_info -> id,
            'number'                  => $number,
            'created_at'              => time(),
            'updated_at'              => time(),
        ]);

        //减去 库存里的 可售数量
        DB::table('erp_stock')
            -> where([
                'business_id'    => $arr['business_id'],
                'store_house_id' => $receive_info -> store_house_id,
                'product_id'     => $good_info['goods_id'],
                'flag'           => 0
            ]) -> decrement('can_buy_num',intval($number));

        //增加待发货数量
        DB::table('erp_stock')
            -> where([
                'business_id'    => $arr['business_id'],
                'store_house_id' => $receive_info -> store_house_id,
                'product_id'     => $good_info['goods_id'],
                'flag'           => 0
            ]) -> increment('waiting_num',intval($number));
        return ['code'=>200,'product_ids'=>$product_ids];
    }
    /**
     *生成在线支付订单单号
     * $order_ids array
     */
    public function createRechargeOrder($request)
    {
        //用户wxUserId_时间戳_10位随机字符串
        $online_order=$request->user->wxUserId.'_'.date('YmdHis').'_'.str_random(10);
        $data = [
            'online_order_num'=> $online_order,
            'user_id'         => $request->user->wxUserId,
            'created_at'      => time(),
            'updated_at'      => time(),
            'business_id'     => $request->business_id,
            'pay_status'      => 0,
            'price'           => $request->payMoney
        ];
        $oid = DB::table('erp_recharge_order')->insertGetId($data);

        //备份
        DB::table('erp_online_order_backup')->insert([
            'order_id'         => $oid,
            'online_order_num' => $online_order,
            'before_pay_status'=> 0,
            'before_pay_method'=> 0,
            'after_pay_status' => 0,
            'after_pay_method' => 0,
            'created_at'       => date('Y-m-d H:i:s'),
        ]);

        return [
            'id'        => $oid,
            'order_num' => $online_order
        ];
    }


    public static function asynOprateRecharge($orderId)
    {
        $order = DB::table('erp_recharge_order')
            ->where('online_order_num',$orderId)
            ->first();
        DB::table('erp_recharge_order')
            ->where('online_order_num',$orderId)
            ->update([
                'pay_status' => 1 ,
                'pay_price'  => $order->price,
                'updated_at' => time()
            ]);
        //更改备份记录
        DB::table('erp_online_order_backup')
            ->where('online_order_num', $orderId)
            ->update([
                'after_pay_status'=>1,
                'after_pay_method'=>3,
                'updated_at'=>date('Y-m-d H:i:s'),
            ]);
        $userinfo =  DB::table('wxuser')->where([
            'id' => $order->user_id
        ]) -> first();
        DB::table('wxuser')->where([
            'id' => $order->user_id
        ]) ->update([
            'price' => bcadd($userinfo->price,$order->pay_price,2)
        ]);
        //添加日志
        DB::table('price_log') -> insert([
            'userid' => $order->user_id,
            'price'  => $order->pay_price,
            'type' => 0,
            'created_at' => time(),
            'updated_at' => time(),
            'from_user_id' =>1,
            'in_out' => 0 ,// 0收入1支出
            'end_price' => bcadd($userinfo->price,$order->pay_price,2)
        ]);
    }
}
