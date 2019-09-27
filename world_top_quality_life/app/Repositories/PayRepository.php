<?php

namespace App\Repositories;

use App\StockOrder;
use Illuminate\Support\Facades\DB;
use App\Repositories\PayByCrossRepository;
use App\Repositories\IdentityCardRepository;

class PayRepository extends BaseRepository
{
    public function __construct(PayByCrossRepository $payByCrossRepository,IdentityCardRepository $identityCardRepository)
    {
        $this->payByCross = $payByCrossRepository;
        $this->identityCardRepository = $identityCardRepository;
    }

    /**
     * 支付
     * @param int $orderId 订单Id
     * @param int $shopUserId 商城微信用户id 表mp_shop_users
     * @return array 返回支付结果
     */
    public function pay($business_id, $orderIds, $wxUserId, $payType = 1,$request)
    {
        $stockOrder = new StockOrder();
        DB::beginTransaction();
        try{
            // 订单正常性check
            $checkResult = $stockOrder->checkOrdersByIds($business_id, $wxUserId, $orderIds);

            // 订单不正常
            if ($checkResult['code'] !== 200) {
                return [
                    'success' => false,
                    'msg'     => $checkResult['msg']
                ];
            }

            // 获得订单
            $ordersResult = $stockOrder->getOrdersByIds($business_id, $wxUserId, $orderIds);

            $orders = $ordersResult['orderList'];
            $order_pay_arr = [];
            // 组装支付数据
            $paySum = 0;
            foreach ($orders as $key => $order) {
                $order_pay_arr[] = [
                    'id'             => $order->id,
                    'pay_price'      => $order->price_all,
                    'service_charge' => 0,
                    'cert_num'       => '',
                    'remark'         => '',
                ];
                $paySum = $this->math_add($paySum, $order->price_all);

            }

            // 余额支付
            if ($payType == 1) {
                // 先判断余额是否足够
                if (!$this->isAdequate($wxUserId, $paySum)) {
                    return [
                        'success' => false,
                        'msg'     => '账户余额不足'
                    ];
                }

                foreach ($orders as $key => $order) {
                    // 多次扣除wxuser的余额
                    DB::table('wxuser')
                    -> where('id', $wxUserId)
                    -> decrement('price', $order->price_all);

                    $wxUser = DB::table('wxuser')
                    -> where('id', $wxUserId)
                    -> first();

                    // 2是增加付款日志在price_log里
                    DB::table('price_log') -> insert([
                        'userid'       => $wxUserId,
                        'price'        => $order->price_all,
                        'created_at'   => time(),
                        'updated_at'   => time(),
                        'from_user_id' => 1,
                        'type'         => 8,//8商城小程序消费
                        'in_out'       => 1,// 1支出
                        'order_id'     => $order->id,
                        'end_price'    => $wxUser->price,
                    ]);
                }
            }
            elseif ($payType==3){
                //检查身份证
                if(!$request->filled('idCardId')){
                    return [
                        'success' => false,
                        'msg'     => '未选择身份证'
                    ];
                }
                //更新订单表里的身份证信息
                $id_card=$this->identityCardRepository->get([$request->idCardId]);
                if (!$id_card){
                    return [
                        'success' => false,
                        'msg'     => '该身份证不存在，请重新添加'
                    ];
                }
                DB::table('erp_stock_order')->whereIn('id',$orderIds)->update([
                    'idNumber'=>$id_card->idNumber,
                    'imageFront'=>$id_card->imageFront,
                    'imageBack'=>$id_card->imageBack,
                    'ident_id'=>$id_card->id,
                ]);

                //微信支付
                $first_order=DB::table('erp_stock_order')->whereIn('id', $orderIds)->first();
                $total_prices=DB::table('erp_stock_order')->whereIn('id', $orderIds)->sum('price');
                $total_freight=DB::table('erp_stock_order')->whereIn('id', $orderIds)->sum('freight');

                if (empty($first_order->idNumber)){
                    return [
                        'success' => false,
                        'msg'     => '该订单的身份证号不存在，请重新添加'
                    ];
                }
                $stock_order = new StockOrder();
                $stock_order->price=$total_prices;
                $stock_order->freight=$total_freight;
                $stock_order->order_num=$stock_order->createOnlineOrder($orderIds,$request);
                $stock_order->name=$id_card->name;
                $stock_order->phone=$first_order->phone;
                $stock_order->idNumber=$first_order->idNumber;
                $stock_order->created_at=$first_order->created_at;
                $stock_order->stock_order_ids=$orderIds;

                //其他检查todo

                //发起请求
                $request->mp='shop';
                $data = $this->payByCross->send($stock_order,$request);

                DB::commit();
                return [
                    'success' => true,
                    'data'     => $data
                ];
            }

            // 收款
            $payResult = $stockOrder->payOrder(
                $order_pay_arr,
                1,//后台操作员
                1,//收款账户id
                $business_id,
                10,//记录收款日志 10小程序订单收款, 7erp订单收款
                $payType //支付方式 1余额支付
            );

            if ($payResult['code'] === 200) {
                DB::commit();
                return [
                    'success' => true,
                    'msg'     => 'success'
                ];
            } else {
                DB::rollBack();
                return [
                    'success' => false,
                    'msg'     => $payResult['msg']
                ];
            }

        }catch (\Exception $exception){
            DB::rollBack();
            return [
                'code' => 500,
                'success' => false,
                'msg' => $exception->getMessage()
            ];
        }

    }

    /**
     * 支付页面
     * @param int $orderId 订单Id
     * @param int $shopUserId 商城微信用户id 表mp_shop_users
     * @return array 返回支付结果
     */
    public function show($business_id, $wxUserId ,$orderIds)
    {
        $stockOrder = new StockOrder();

        // 订单编号 多个
        $ordersResult = $stockOrder->getOrdersByIds($business_id, $wxUserId, $orderIds);
        $orders = $ordersResult['orderList'];
        if (count($orderIds) !== count($orders)) {
            return [
                'success' => false,
                'msg'     => '部分订单已支付'
            ];
        }
        $orderNos = [];
        $ident_ids = [];
        // 商品金额
        $sumPrice = 0;
        // 快递运费
        $sumFreight = 0;
        // 会员优惠
        $vipDiscount = '0.00';
        // 实付金额
        $payPrice = 0;

        foreach ($orders as $key => $order) {
            $orderNos[] = $order->order_num;
            $ident_ids[] = $order->ident_id;
            // 总价格
            $sumPrice = $this->math_add($sumPrice,$order->price);
            // 总运费
            $sumFreight = $this->math_add($sumFreight,$order->freight);
        }
        $payPrice = $this->math_add($sumPrice,$sumFreight);

        $idCard = $this->identityCardRepository->get($ident_ids);//身份证

        // 支付方式选择
        $stock_order_pay_method     = config('admin.stock_order_pay_method');
        $stock_order_pay_method_img = config('admin.stock_order_pay_method_img');

        $payMethods = [];
        foreach ($stock_order_pay_method as $key => $value) {
            if ($key === 1) {
                $wxUser = DB::table('wxuser')
                -> where('id', $wxUserId)
                -> first();

                $payMethods[] = [
                    'id'      => $key,
                    'name'    => $value,
                    'icon'    => $stock_order_pay_method_img[$key],
                    'balance' => $this->math_add($wxUser->price, 0),               //为了保留两位小数
                    'enough'  => $this->math_comp($wxUser->price, $payPrice) >= 0
                ];

            }elseif ($key==3){
                $payMethods[] = [
                    'id'      => $key,
                    'name'    => $value,
                    'icon'    => $stock_order_pay_method_img[$key],
                ];
            }
            // else{
            //     var_dump($key);exit;

            //     $payMethods[] = [
            //         'id'   => $key,
            //         'name' => $value,
            //         'icon' => $stock_order_pay_method_img[$key],
            //     ];
            // }
            // 如果余额支付，同时判断一下余额是否足够

        }

        // 总产品数量
        $sumSkuNum = 0;
        $carts = DB::table('erp_shop_cart')
        -> where('isDel', 0)
        -> where('isOrder', 1)
        -> whereIn('orderId', $orderIds)
        -> get();

        foreach ($carts as $key => $cart) {
            $sumSkuNum += $cart->num;
        }

        $payPrice = $this->math_sub($payPrice,$vipDiscount);

        $data = [
            'info'        => count($orderIds) > 1 ? '多国商品已分拆订单' : '',
            'orderNos'    => $orderNos,
            'sumPrice'    => $sumPrice,
            'sumFreight'  => $sumFreight,
            'vipDiscount' => $vipDiscount,
            'payPrice'    => $payPrice,
            'sumSkuNum'   => $sumSkuNum,
            'payMethods'  => $payMethods,
            'idCard'       => $idCard
        ];

        return [
            'success' => true,
            'msg'     => 'success',
            'data'    => $data
        ];
    }

    public function isAdequate($wxUserId, $paySum)
    {
        $wxUser = DB::table('wxuser')
        -> where('id', $wxUserId)
        -> first();
        return $this->math_comp($wxUser->price, $paySum) >= 0;
    }


    /**
     * 账户充值
     * @param int $orderId 订单Id
     * @param int $shopUserId 商城微信用户id 表mp_shop_users
     * @return array 返回支付结果
     */
    public function rechargePay($request)
    {
        $stock_order = new StockOrder();
        DB::beginTransaction();
        try{
            $order  =$stock_order->createRechargeOrder($request);
            $stock_order->price          = $request->payMoney;
            $stock_order->order_num      = $order['order_num'];
            $stock_order->created_at     = time();
            $stock_order->order_id= $order['id'];
            //其他检查todo

            //发起请求
            $request->mp='shop';
            $request->isRecharge='1';
            $data = $this->payByCross->sendRecharge($stock_order,$request);

            DB::commit();
            return [
                'success' => true,
                'data'     => $data
            ];

            // 收款
            $payResult = $stockOrder->payOrder(
                $order_pay_arr,
                1,//后台操作员
                1,//收款账户id
                $business_id,
                10,//记录收款日志 10小程序订单收款, 7erp订单收款
                $payType //支付方式 1余额支付
            );

            if ($payResult['code'] === 200) {
                DB::commit();
                return [
                    'success' => true,
                    'msg'     => 'success'
                ];
            } else {
                DB::rollBack();
                return [
                    'success' => false,
                    'msg'     => $payResult['msg']
                ];
            }

        }catch (\Exception $exception){
            DB::rollBack();
            return [
                'code' => 500,
                'success' => false,
                'msg' => $exception->getMessage()
            ];
        }

    }

}
