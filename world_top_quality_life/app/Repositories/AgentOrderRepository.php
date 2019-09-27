<?php

namespace App\Repositories;

use App\StockOrder;
use Illuminate\Support\Facades\DB;

class AgentOrderRepository extends BaseRepository
{
    public function __construct(
        AddressRepository $addressRepository,
        MathRepository $mathRepository,
        SKURepository $skuRepository,
        AgentCartRepository $agentCartRepository,
        AgentfreightRepository $agentfreightRepository,
        IdentityCardRepository $identityCardRepository,
        WarehouseRepository $warehouseRepository
    ){
        $this->addressRepository      = $addressRepository;
        $this->mathRepository         = $mathRepository;
        $this->skuRepository          = $skuRepository;
        $this->agentfreightRepository = $agentfreightRepository;
        $this->agentCartRepository    = $agentCartRepository;
        $this->warehouseRepository    = $warehouseRepository;
        $this->identityCardRepository = $identityCardRepository;
    }


    /**
     * cartIds      代理的购物车Ids
     * businessId   代理的事业部Id
     * userId       当前用户的wxuserId
     */
    public function checkByCartIdsAndBusinessIdAndUserId($cartIds, $businessId, $userId)
    {
        $list =  DB::table('erp_agent_shop_cart as cart')
            -> whereIn('cart.id', $cartIds)
            // spu表
            -> leftJoin('erp_business_spu_link_new', 'cart.spuId', 'erp_business_spu_link_new.new_spu_id')
            ->leftJoin('erp_spu_list','erp_spu_list.id','cart.spuId')
            -> leftJoin('erp_product_list','erp_product_list.id','cart.skuId')
            -> where('cart.userId', $userId)
            -> where('cart.businessId', $businessId)
            -> where('cart.isDel', 0)
            -> where('cart.isOrder', 0)
            ->select([
                'cart.id',
                'cart.num',
                'erp_product_list.number as can_buy_num',
            ])->get();
        $list->each(function($item,$key)use($list){
            if($item->bindSpuId){
                $sku = DB::table('erp_product_list')
                    ->leftJoin('erp_spu_sku_link','erp_spu_sku_link.sku_id','erp_product_list.id')
                    ->where([
                        'erp_spu_sku_link.spu_id' => $item->bindSpuId,
                        'erp_product_list.product_no' => $item->product_no
                    ])
                    ->first();
                $item-> can_buy_num = $sku->number;
                if($item-> can_buy_num<=0)$list->splice($key);
            }
        });
        if($list->isEmpty()) {
            return [
                'success' => false,
                'msg'     => '部分商品已下架或库存不足！'
            ];
        }

        if ($list->count() !== count($cartIds)) {
            return [
                'success' => false,
                'msg'     => '部分商品已下架或库存不足！'
            ];
        }

        foreach ($list as $key => $value) {
            if ($value->can_buy_num <  $value->num) {
                return [
                    'success' => false,
                    'msg'     => '部分商品已下架或库存不足！'
                ];
            }
        }

        return [
            'success' => true
        ];
    }
    /**
     * 下单
     * @param int $businessId 事业部Id
     * @param array $cartIds 购物车ids,表erp_shop_cart
     * @param int $addressId 地址id,表erp_mp_shop_address
     * @param string $message 客户留言
     * @param int $shopUserId 商城微信用户id 表mp_shop_users
     * @return array 返回下单结果
     */
    public function order($request)
    {
        $cartIds     = $request->cartIds;
        $businessId  = $request->business_id;
        $addressId   = $request->addressId;
        $message     = $request->message;
        $send_remark = $request->send_remark ? $request->send_remark : 2;
        $idCardId    = $request->idCardId;
        /**
         * 拆包裹逻辑
         */
        $address = $this->addressRepository->get($addressId);

        // 是否需要身份证
        $isNeedIDCard = $this->isNeedIDCard($cartIds);
        if ($isNeedIDCard) {
            if (empty($idCardId)) {
                return [
                    'success' => false,
                    'msg'     => '请上传身份证'
                ];
            }
            $idCard = $this->identityCardRepository->get($idCardId);
            if (!$idCard) {
                return [
                    'success' => false,
                    'msg'     => '身份证异常'
                ];
            }
        } else {
            $idCard             = new \stdClass();
            $idCard->idNumber   = '';
            $idCard->imageFront = '';
            $idCard->imageBack  = '';
            $idCard->id         = '';
        }

        // sku列表
        $list = DB::table('erp_agent_shop_cart as cart')
            -> whereIn('cart.id', $cartIds)
            // spu表
            -> leftJoin('erp_business_spu_link', 'cart.spuId', 'erp_business_spu_link.id')
            -> leftJoin('erp_spu_list', 'erp_business_spu_link.spu_id', 'erp_spu_list.id')
            // sku表
            -> leftJoin('erp_agent_price', 'cart.skuId', 'erp_agent_price.id')
            -> leftJoin('erp_product_price', function($q) {
                $q->on('erp_product_price.product_id', '=', 'erp_agent_price.sku_id')
                ->on('erp_product_price.mp_name_id', '=', 'erp_agent_price.mp_name_id');
            })
            -> leftJoin('erp_product_list', 'erp_agent_price.sku_id', 'erp_product_list.id')
            // 馆区表
            -> leftJoin('erp_mp_name', 'erp_business_spu_link.mp_name_id', 'erp_mp_name.id')
            -> select([
                // 购物车id
                'cart.id as cartId',
                'cart.num',
                'cart.skuId as skuId',
                'erp_agent_price.price',
                // 馆区Id
                'erp_business_spu_link.mp_name_id',
                'erp_product_list.id as productId',
            ])
            -> get();

        // 按照馆区ID，分组
        $groupList = $this->arrayGrouping($list, 'mp_name_id');
        $stockOrder = new StockOrder();

        DB::beginTransaction();
        try{
            $orderIds = [];
            foreach ($groupList as $listKey => $list) {
                // 分组下单
                $order_info  = [];
                $tmp_cartIds = [];
                // 一个list 下一个订单
                foreach ($list as $skuKey => $sku) {
                    $order_info[] = [
                        'goods_id'   => $sku->productId,
                        'mp_name_id' => $listKey,
                        'price'      => $sku->price,
                        'number'     => $sku->num
                    ];
                    $tmp_cartIds[] = $sku->cartId;
                }
                // 本单运费
                $freight = $this->agentfreightRepository->computedFreight($tmp_cartIds, $addressId);

                // 从馆区Id 到 仓库id
                // $warehouse = $this->warehouseRepository->getWarehouseByMpNameAndCartIds($listKey ,$tmp_cartIds);

                $orderData = [
                    'order_info_json'  => json_encode($order_info),   //下单json 新增明细   [{"goods_id 商品id":"1","price价格":"124","number数量":"55"},{}]
                    'business_id'      => $businessId,    //事业部id
                    'sale_date'        => date('Y-m-d'),              //销售日期 date类型
                    'user_id'          => $request->user->wxUserId,   //wxuser的id
                    'sale_user_id'     => 55,                         //销售员users id
                    'freight'          => $freight,                   //运费
                    'substitute'       => 0,                          //代发费
                    'send_remark'      => $send_remark,               //发货备注 0发货1不发货2预定
                    'province'         => $address->province,         //收件人省
                    'city'             => $address->city,             //收件人市
                    'area'             => $address->area,             //收件人区
                    'tel'              => $address->phone,            //收件人手机
                    'phone'            => $address->phone,            //收件人电话
                    'name'             => $address->name,             //收件人姓名
                    'address'          => $address->detail,           //收件人地址
                    'user_remark'      => $message,                   //销售备注
                    'operator_user_id' => 1,                          //后台操作员users id
                    'insert_type'      => 2,                          //添加类型， erp添加值为0 自营小程序添加值为1 代理小程序添加为2
                    'idNumber'         => $idCard->idNumber,          //身份证号 varchar 18
                    'imageFront'       => $idCard->imageFront,        //文件名 比如  1a453fe7b8951db3885c52846a79ea75.jpg
                    'imageBack'        => $idCard->imageBack,          //文件名 比如  1a453fe7b8951db3885c52846a79ea75.jpg
                    'ident_id'        => $idCard->id
                ];

                $orderResult = $stockOrder->addAgentStockOrder($orderData);

                if ($orderResult['code'] === 200) {
                    $orderIds[] = $orderResult['stock_id'];
                } else {
                    DB::rollBack();
                    return [
                        'success' => false,
                        'msg'     => $orderResult['msg']
                    ];
                }
                // 下单成功更新对应的购物车记录 isOrder orderId orderTime  三个字段 表erp_agent_shop_cart
                $this->agentCartRepository->order($tmp_cartIds, $orderResult['stock_id']);

            }

            // 返回成功生成的的订单Ids
            $data = [
                'orderIds' => $orderIds
            ];

            DB::commit();

            return [
                'success'   => true,
                'msg'       => 'success',
                'data'      => $data
            ];
        }catch (\Exception $exception){
            DB::rollBack();

            return [
                'success'   => false,
                'msg'       => $exception->getMessage()
            ];
        }
    }

    /**
     * 取消订单
     * @param int $orderId 订单Id
     * @return array 返回取消结果
     */
    public function cancel($orderId, $business_id)
    {

        $stockOrder = new StockOrder();

        DB::beginTransaction();
        try{
            // 取消订单
            $result = $stockOrder->orderInfo($orderId);

            if ($result['code'] !== 200) {
                return [
                    'success' => false,
                    'msg'     => $result['msg']
                ];
            }

            $delResult = $stockOrder->deleteOrder($orderId, $result['order']->business_id);

            if ($delResult['code'] !== 200) {
                return [
                    'success' => false,
                    'msg'     => $delResult['msg']
                ];
            }

            // $this->skuRepository->dispatchCancel($orderId);

            DB::commit();

            return [
                'success'   => true,
                'msg'       => 'success',
            ];
        }catch (\Exception $exception){
            DB::rollBack();

            return [
                'success'   => false,
                'msg'       => $exception->getMessage()
            ];
        }

    }

    /**
     * 订单详情
     * @param int $orderId 订单Id
     * @return array 返回结果
     */
    public function getById($request, $orderId)
    {
        $stockOrder = new StockOrder();

        $result = $stockOrder->orderInfo($orderId);

        if ($result['code'] !== 200) {
            return [
                'success' => false,
                'msg'     => $result['msg']
            ];
        }
        $list = $this->agentCartRepository->getCartsByOrderId($orderId);

        $sumNum = 0;
        $price  = 0;
        foreach ($list as $key => $value) {
            // 拼装图片
            $list[$key]->image = $value->image ? getImageUrl($value -> image) : '';
            // 计算总数
            $sumNum += $value->num;
            // 精确乘法
            $price  += $this->mathRepository->math_mul($value->num, $value->unitPrice);
        }

        $pay_method = config('admin.stock_order_pay_method');

        $result['order']->payType      = $pay_method[$result['order']->pay_method ? $result['order']->pay_method : 1 ];
        $result['order']->purchaseList = $list;
        $result['order']->express      = '跨境配送';
        $result['order']->sumNum       = $sumNum;
        $result['order']->sale_date    = date('Y-m-d H:i:s', $result['order']->updated_at);

        //付款状态
        if($result['order']->pay_status){
            if($result['order']->send_status == 2){
                $status = '待收货';
            }elseif($result['order']->send_status == 1){
                $status = '已完成';
            }else {
                $status = '待发货';
            }
        }else{
            $status = '待付款';
        }

        $result['order']->status = $status;

        // 获取物流信息
        $delivers = DB::table('erp_deliver_goods_record')
            -> where('stock_order_id', $result['order']->id)
            -> where('flag', 0)
            -> select([
                'company',
                'deliver_num'
            ])
            -> get();

        if ($delivers->isEmpty()) {
            $result['order']->expressCompany = '';
            $result['order']->expressRecode  = [];
        } else {
            $expressCompany = '';
            $expressRecode  = [];
            foreach ($delivers as $key => $deliver) {
                $expressCompany  = $deliver->company;
                $expressRecode[] = $deliver->deliver_num;
            }
            $result['order']->expressCompany = $expressCompany;
            $result['order']->expressRecode  = $expressRecode;
        }

        return [
            'success'    => true,
            'msg'        => 'success',
            'order'      => $result['order'],
            'order_info' => $result['order_info']
        ];
    }

    /**
     * 订单列表
     */
    public function search($request)
    {
        $stockOrder = new StockOrder();

        // * @param $business_id 事业部id
        // * @param $user_id wxuser表的 id
        // * @param $sale_status 付款状态 0未付款 1 已付款
        // * @param $send_status 发货状态 0未发货 1已发货 2发货中

        switch ($request->filter) {
            // 待付款
            case 'obligation':
                $sale_status = 0;
                $send_status = 999;
                break;
            // 待发货
            case 'waitingShip':
                $sale_status = 1;
                $send_status = 0;
                break;
            // 待收货
            case 'waitingReceiving':
                $sale_status = 1;
                $send_status = 2;
                break;
            // 已完成
            case 'completed':
                $sale_status = 1;
                $send_status = 1;
                break;
            // 全部
            default:
                $sale_status = 999;
                $send_status = 999;
                break;
        }

        $result = $stockOrder->agentOrderList(
            $request->business_id,
            $request->user->wxUserId,
            $sale_status,
            $send_status
        );

        $orders = $result['orderList'];

        foreach ($orders as $key => $order) {
            $skus = DB::table('erp_agent_shop_cart')
            -> where('isOrder', 1)
            -> where('orderId', $order->id)
            -> select([
                'id',
                'num',
                'spuName',
                'skuName',
                'unitPrice',
                'product_no',
                'image'
            ])
            -> get();

            $skuNum = 0;
            foreach ($skus as $k => $sku) {
                $skuNum += $sku->num;
                // 拼装图片
                $skus[$k]->image = $sku->image ? getImageUrl($sku -> image) : '';
            }
            // 日期修改
            $orders[$key]->skuNum = $skuNum;
            $orders[$key]->data = date('Y-m-d', $order->sale_date);
            $orders[$key]->skus = $skus;
        }

        return [
            'success' => true,
            'msg'     => 'success',
            'data'    => $orders
        ];
    }

    /**
     * 组装支付信息
     */
    public function assemble($request)
    {
        $cartIds    = $request->cartIds;
        $businessId = $request->business_id;
        $addressId  = $request->addressId;
        $idCardId   = $request->idCardId;

        if ($this->addressRepository->checkExistAddressById($addressId, $request->user->wxUserId)) {
            // 如果输入一个地址，就按照这个地址算价格
            $address = $this->addressRepository->get($addressId);
        } else {
            // 如果没有地址，看看有没有默认地址，如果有默认地址就按默认地址计算
            $address = $this->addressRepository->getDefault($request->user->wxUserId);
        }

        // 是否有需要身份证
        $isNeedIDCard = $this->isNeedIDCard($cartIds);
        // 只要存在境外sku就需要

        // 如果需要
        if ($isNeedIDCard) {
            // 需要并且传了
            if ($idCardId) {
                $idCard = $this->identityCardRepository->get($idCardId);
            // 需要但是没传
            } else {
                // 如果没有地址，看看有没有默认地址，如果有默认地址就按默认地址计算
                $idCard = $this->identityCardRepository->getDefault($request->user->wxUserId);
            }
        // 不需要
        } else {
            $idCard = new \stdClass();
        }

        // 商品相关信息
        // 当前的商品售价
        $list = DB::table('erp_agent_shop_cart as cart')
        -> whereIn('cart.id', $cartIds)
        // spu表
        -> leftJoin('erp_business_spu_link', 'cart.spuId', 'erp_business_spu_link.id')
        -> leftJoin('erp_spu_list', 'erp_business_spu_link.spu_id', 'erp_spu_list.id')
        // sku表
        -> leftJoin('erp_agent_price', 'cart.skuId', 'erp_agent_price.id')
        -> leftJoin('erp_product_price', function($q) {
            $q->on('erp_product_price.product_id', '=', 'erp_agent_price.sku_id')
            ->on('erp_product_price.mp_name_id', '=', 'erp_agent_price.mp_name_id');
        })
        -> leftJoin('erp_product_list', 'erp_agent_price.sku_id', 'erp_product_list.id')
        -> where('erp_product_price.status', 1)
        -> where('erp_product_price.is_show', 1)
        -> where('erp_product_price.has_stock', '>' ,0)
        -> where('cart.businessId', $businessId)
        -> where('cart.userId', $request->user->wxUserId)
        -> where('cart.isDel',0)
        -> where('cart.isOrder', 0)
        -> select([
            'erp_product_list.image',
            'erp_spu_list.name as spuName',
            'erp_product_list.product_name',
            'erp_product_list.product_no',
            'cart.num',
            'erp_agent_price.price',
        ])
        -> get();

        $sumNum = 0;
        $price  = 0;
        $skuSumPrice = 0;
        // 运费
        if (!empty((array)$address)) {
            $expressPrice = $this->agentfreightRepository->computedFreight($cartIds, $address->id);
            $expressPrice = $this->math_add($expressPrice, 0);
            $showExpressPrice = true;
        } else {
            $expressPrice = $this->math_add(0, 0);
            $showExpressPrice = false;
        }

        foreach ($list as $key => $value) {
            // 拼装图片
            $list[$key]->image = $value->image ? getImageUrl($value -> image) : '';
            // 计算总数
            $sumNum += $value->num;
            // 精确乘法
            $price       += $this->math_mul($value->num, $value->price);
            $skuPrice     = $this->math_mul($value->num, $value->price);
            $skuSumPrice  = $this->math_add($skuSumPrice, $skuPrice);
        }

        // 加上运费
        $price = $this->math_add($price, $expressPrice);

        // 余额
        return [
            // 收货地址
            'address'           => $address,
            // 购物清单
            'purchaseList'      => $list,
            // 配送方式
            'express'           => '跨境配送',
            // 支付方式
            'payType'           => [
                'code' => '3',//'balance',
                'name' => '在线支付',//'余额支付'
            ],
            // 总金额
            'price'             => $price,
            // 购物列表总价格
            'skuSumPrice'       => $skuSumPrice,
            'showExpressPrice'  => $showExpressPrice,
            // 快递费用
            'expressPrice'      => $expressPrice,
            // 商品数
            'sumNum'            => $sumNum,
            'cartIds'           => $cartIds,
            // 是否需要身份证
            'isNeedIDCard'      => $isNeedIDCard,
            // 身份证
            'idCard'            => $idCard
        ];
    }




    public function isNeedIDCard($cartIds)
    {
        $count = DB::table('erp_agent_shop_cart')
        -> whereIn('erp_agent_shop_cart.id', $cartIds)
        -> leftJoin('erp_business_spu_link', 'erp_agent_shop_cart.spuId', 'erp_business_spu_link.id')
        -> leftJoin('erp_mp_name', 'erp_business_spu_link.mp_name_id', 'erp_mp_name.id')
        -> leftJoin('freight_temp_name', 'erp_mp_name.freight_temp_name_id', 'freight_temp_name.id')
        -> where('freight_temp_name.country', '>', 0)
        -> count();

        return $count > 0;
    }

}
