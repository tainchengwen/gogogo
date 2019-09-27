<?php

namespace App\Repositories;

use App\MarketFreight;
use App\StockOrder;
use Illuminate\Support\Facades\DB;

class OrderRepository extends BaseRepository
{
    public function __construct(
        AddressRepository $addressRepository,
        MathRepository $mathRepository,
        SKURepository $skuRepository,
        CartRepository $cartRepository,
        FreightRepository $freightRepository,
        IdentityCardRepository $identityCardRepository,
        WarehouseRepository $warehouseRepository,
        SpecialRepository $specialRepository,
        MarketRepository $marketRepository
    ){
        $this->addressRepository      = $addressRepository;
        $this->mathRepository         = $mathRepository;
        $this->skuRepository          = $skuRepository;
        $this->freightRepository      = $freightRepository;
        $this->cartRepository         = $cartRepository;
        $this->warehouseRepository    = $warehouseRepository;
        $this->identityCardRepository = $identityCardRepository;
        $this->special = $specialRepository;
        $this->market = $marketRepository;
    }

    public function checkByCartIdsAndBusinessIdAndUserId($cartIds, $businessId, $userId,$type =1)
    {
        // 库存必须大于购买数量
        // 购物车列表

        //普通cart和套餐cart
        $cart_ids=DB::table('erp_shop_cart as cart')
            -> whereIn('cart.id', $cartIds)
            -> where('cart.businessId', $businessId)
            -> where('cart.userId', $userId)
            -> where('cart.isDel',0)
            -> where('cart.isOrder', 0)
            -> where('cart.union_id', 0)->pluck('id');

        $union_cart_ids=DB::table('erp_shop_cart as cart')
            -> whereIn('cart.id', $cartIds)
            -> where('cart.businessId', $businessId)
            -> where('cart.userId', $userId)
            -> where('cart.isDel',0)
            -> where('cart.isOrder', 0)
            -> where('cart.union_id', '>',0)->pluck('id');

        $list = DB::table('erp_shop_cart as cart')
        -> whereIn('cart.id', $cart_ids)
        // sku表
        -> leftJoin('erp_product_price', 'cart.skuId', 'erp_product_price.id')
        -> leftJoin('erp_product_list', 'erp_product_price.product_id', 'erp_product_list.id')
        -> where('cart.businessId', $businessId)
        -> where('cart.userId', $userId)
        -> where('cart.isDel',0)
        -> where('cart.isOrder', 0)
        // sku三状态
        -> where('erp_product_price.status', 1)
        -> where('erp_product_price.is_show', 1)
        -> where('erp_product_price.has_stock', '>', 0)
        -> select([
            'cart.id',
            'cart.num',
            'cart.special_id',
            'erp_product_price.has_stock as can_buy_num',
            'erp_product_list.is_public',
            'erp_product_list.id'
        ])
        -> get();

        if($cart_ids->isNotEmpty() && $list->isEmpty()) {
            return [
                'success' => false,
                'msg'     => '部分商品已下架或库存不足！'
            ];
        }

        if ($cart_ids->isNotEmpty() && $list->count() !== count($cart_ids)) {
            return [
                'success' => false,
                'msg'     => '部分商品已下架或库存不足！'
            ];
        }
        if($cart_ids->isNotEmpty()){
            foreach ($list as $key => $value) {
                //公有商品库存不足
                if($value->is_public == 1){
                    $stockInfo =  $this->skuRepository->fetchSkuStock($value->id);
                    $value->can_buy_num = $stockInfo['stock'];
                    if($type == 0){
                        $hasStoreNum = 0;
                        foreach ($stockInfo['data'] as $stockValue){
                            if($value->num > $stockValue->min_buy_num) {
                                $hasStoreNum++;
                            }
                        }
                        if($hasStoreNum == count($stockInfo['data'])){
                            return [
                                'success' => false,
                                'msg'     => '是否需要拆包操作判断',
                                'retry'   => 1
                            ];

                        }
                   }
                    if($stockInfo['stock'] < $value->num){
                        return [
                            'success' => false,
                            'msg'     => '部分商品已下架或库存不足！'
                        ];
                    }
                }
                if ($value->can_buy_num <  $value->num) {
                    return [
                        'success' => false,
                        'msg'     => '部分商品已下架或库存不足！'
                    ];
                }
                if ($value->special_id > 0){
                    $special=DB::table('erp_special_price')->where('id',$value->special_id)->where('flag',0)->first();
                    if (empty($special)){
                        return [
                            'success' => false,
                            'msg'     => '部分特价商品已下架或库存不足！'
                        ];
                    }
                }

            }
        }

        //套餐
        $unions=DB::table('erp_shop_cart')
            -> where('userId', $userId)
            -> where('businessId', $businessId)
            -> where('isDirect', 0)
            -> where('isDel', 0)
            -> where('isOrder', 0)
            -> whereIn('erp_shop_cart.id', $union_cart_ids)->get();

        if ($unions->isNotEmpty()){
            foreach ($unions as $vo){
                $prices=DB::table('erp_product_price')
                    ->leftJoin('erp_product_list','erp_product_price.product_id','erp_product_list.id')
                    ->leftJoin('erp_spu_sku_link','erp_product_list.id','erp_spu_sku_link.sku_id')
                    ->leftJoin('erp_mp_name_spu_link', function($q) {
                        $q->on('erp_product_price.mp_name_id', '=', 'erp_mp_name_spu_link.mp_name_id')
                            ->on('erp_spu_sku_link.spu_id', '=', 'erp_mp_name_spu_link.spu_id');
                    })
                    ->where('erp_mp_name_spu_link.id',$vo->spuId)
                    ->where('erp_spu_sku_link.flag',0)
                    //->where('erp_mp_name_spu_link.union_flag',0)
                    ->where('erp_mp_name_spu_link.flag',0)
                    ->where('erp_product_price.flag', 0)
                    ->where('erp_product_price.status', 1)
                    ->where('erp_product_price.union_status', 1)
                    ->where('erp_product_price.is_show', 1)
                    ->where('erp_product_price.has_stock', '>', 0)
                    ->select([
                        'erp_product_price.has_stock as can_buy_num',
                        'erp_product_price.union_num',
                        'erp_product_list.id',
                        'erp_product_list.is_public',
                    ])
                    ->get();

                if ($prices->isEmpty()){
                    return [
                        'success' => false,
                        'msg'     => '部分商品已下架或库存不足！'
                    ];
                }else{
                    $stock_status=0;
                    $can_buy_num=0;
                    foreach ($prices as $k=>$v){
                        if($v->is_public == 1)$v->can_buy_num =  $this->skuRepository->fetchSkuStock($v->id)['stock'];
                        if ($v->can_buy_num < $v->union_num){
                            $stock_status=1;
                            break;
                        }

                        if($k==0){
                            $can_buy_num=floor($v->can_buy_num / $v->union_num);
                        }else{
                            $can_buy_num=min($can_buy_num,floor($v->can_buy_num / $v->union_num));
                        }
                    }
                    if ($stock_status == 1 || $can_buy_num < 1 || $can_buy_num < $vo->num){
                        return [
                            'success' => false,
                            'msg'     => '部分商品已下架或库存不足！'
                        ];
                    }
                }
            }
        }

        return [
            'success' => true
        ];
    }

    public function checkLimitByCartIds($cartIds,$businessId, $user_id, $market_class)
    {
        $carts=DB::table('erp_shop_cart as cart')
            -> leftJoin('erp_product_price', 'cart.skuId', 'erp_product_price.id')
            -> leftJoin('erp_mp_name_spu_link', 'cart.spuId', 'erp_mp_name_spu_link.id')
            -> leftJoin('erp_spu_list', 'erp_mp_name_spu_link.spu_id', 'erp_spu_list.id')
            -> whereIn('cart.id', $cartIds)
            -> where('cart.businessId', $businessId)
            -> where('cart.userId', $user_id)
            -> where('cart.isDel',0)
            -> where('cart.isOrder', 0)
            -> select([
                'erp_product_price.product_id as sku_id',
                'erp_spu_list.id as spu_id',
                'erp_spu_list.type',
                'cart.num',
            ])
            ->get();

        foreach ($carts as $cart){
            $limit_buy_data=[
                'user_id'=>$user_id,
                'spu_id'=>$cart->spu_id,
                'market_class'=>$market_class
            ];
            if ($cart->type==0){
                $limit_buy_data['sku_id']=$cart->sku_id;
            }
            $limit_buy_num=$this->market->checkLimitBuy($limit_buy_data);
            if ($limit_buy_num!=='unlimit' && ($cart->num > $limit_buy_num)){
                return ['success'=>false,'msg'=>'已达到限购数量'];
            }
        }

        return [
            'success' => true
        ];
    }


    /**
     * 下单 v1.1
     * @param int $businessId 事业部Id
     * @param array $cartIds 购物车ids,表erp_shop_cart
     * @param int $addressId 地址id,表erp_mp_shop_address
     * @param string $message 客户留言
     * @param int $shopUserId 商城微信用户id 表mp_shop_users
     * @return array 返回下单结果
     */
    public function order_1_1($request)
    {
        $type        = $request->type; //0的时候 首次进入 如果有拆单其情况 弹出弹框  1 不拆 2 拆
        $cartIds     = $request->cartIds;
        $businessId  = $request->business_id;
        $addressId   = $request->addressId;
        $send_addressId   = $request->has('send_addressId')?$request->send_addressId:'';
        $message     = $request->message;
        $send_remark = $request->send_remark ? $request->send_remark : 2;
        $idCardId    = $request->idCardId;
        $mp_selected_freight=$request->mpSelectedFreight;
        $mp_selected_coupons=$request->filled('mp_selected_coupons')?$request->mp_selected_coupons:[];
        //处理馆区绑定的优惠券数据
        $mp_selected_coupons_arr=[];
        foreach ($mp_selected_coupons as $v){
            $temp_arr=explode('-',$v);
            $mp_selected_coupons_arr[$temp_arr[0]][]=$temp_arr[1];
        }
        /**
         * 拆包裹逻辑
         */
        $address = $this->addressRepository->get($addressId);
        $send_address = empty($send_addressId)?$this->addressRepository->fetchDefaultSend($businessId):$this->addressRepository->getSend($send_addressId);

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
            $idCard->id         = '';
            $idCard->idNumber   = '';
            $idCard->imageFront = '';
            $idCard->imageBack  = '';
        }

        $market_class_map = config('admin.market_class');
        $current = $market_class_map[$request->user->market_class];

        // sku列表
        $list = DB::table('erp_shop_cart as cart')
            -> whereIn('cart.id', $cartIds)
            // spu表
            -> leftJoin('erp_mp_name_spu_link', 'cart.spuId', 'erp_mp_name_spu_link.id')
            -> leftJoin('erp_spu_list', 'erp_mp_name_spu_link.spu_id', 'erp_spu_list.id')
            // sku表
            -> leftJoin('erp_product_price', 'cart.skuId', 'erp_product_price.id')
            -> leftJoin('erp_product_list', 'erp_product_price.product_id', 'erp_product_list.id')
            // 馆区表
            -> leftJoin('erp_mp_name', 'erp_mp_name_spu_link.mp_name_id', 'erp_mp_name.id')
            -> select([
                // 购物车id
                'cart.id as cartId',
                'cart.num',
                'cart.skuId as skuId',
                'cart.spuId as spuId',
                'erp_product_price.price_' . $current . ' as currentPrice',
                // 仓库Id
                // 'erp_spu_list.warehouse_id',
                // 馆区Id
                'erp_mp_name_spu_link.mp_name_id',
                'erp_mp_name_spu_link.mp_name_id as mp_id',
                'erp_product_list.id as productId',
                'erp_product_list.id as product_list_id',
                'erp_product_list.class_id',
                'erp_spu_list.id as spu_id',
                'erp_spu_list.type as spu_type',
                'cart.special_id',//
                'cart.union_id',//
                'erp_spu_list.business_id',
                'erp_spu_list.is_public'
            ])
            -> get();

        foreach ($list as $value){
            if($value->spu_type==0 && $value->special_id != 0){//限时特价
                $special=$this->special->getSpecialSku(explode('_',$value->special_id));
                if(empty($special)){
                    return [
                        'success' => false,
                        'msg'     => '部分特价商品已下架，请先删除该商品'
                    ];
                }
                if($special->special_code != 0){
                    return [
                        'success' => false,
                        'msg'     => '部分特价商品已过期，请先删除该商品'
                    ];
                }
                if($special->can_buy_num < $value->num){
                    return [
                        'success' => false,
                        'msg'     => '部分特价商品库存不足，请先删除该商品'
                    ];
                }
                $value->currentPrice=$special->price;
            }
            //套餐
            $value->productIds=[];
            $value->union_num=[];
            if ($value->spu_type==1){
                if ($value->special_id != 0){
                    $unionsku=$this->special->getUnionSpecialSku(explode('_',$value->special_id));
                    if($unionsku->special_code != 0){
                        return [
                            'success' => false,
                            'msg'     => '部分特价商品已过期，请先删除该商品'
                        ];
                    }
                    $value->currentPrice=$unionsku->price;
                    $value->skuName=$unionsku->info;
                    $value->union_num=$unionsku->union_num;
                    $value->union_price=$unionsku->union_price;
                    $value->productIds=$unionsku->productIds;
                }else{
                    $union_info=$this->getUnionInfo($value->spuId,$current);
                    $value->currentPrice=$union_info['currentPrice'];
                    $value->productIds=$union_info['productIds'];
                    $value->union_num=$union_info['union_num'];
                    $value->union_price=$union_info['union_price'];
                }
            }
            //如果是团购,取团购价
            if ($request->group){
                if (isset($request->groups['group_buy_type']) && $request->groups['group_buy_type']==1){//团购页原价购买
                    if ($request->group->spu_type==1){
                        $value->union_price=DB::table('erp_market_group_details')
                            ->leftJoin('erp_product_price','erp_market_group_details.price_id','erp_product_price.id')
                            ->where('erp_market_group_details.group_id',$request->group->id)
                            ->pluck('erp_market_group_details.origin_price','erp_product_price.product_id')->toArray();
                    }else{
                        $value->currentPrice=DB::table('erp_market_group_details')
                            ->where(['group_id'=>$request->group->id,'price_id'=>$request->groups['price_id']])
                            ->value('origin_price');
                    }
                }else{//团购价
                    if ($request->group->spu_type==1){
                        $value->union_price=DB::table('erp_market_group_details')
                            ->leftJoin('erp_product_price','erp_market_group_details.price_id','erp_product_price.id')
                            ->where('erp_market_group_details.group_id',$request->group->id)
                            ->pluck('erp_market_group_details.group_price','erp_product_price.product_id')->toArray();
                    }else{
                        $group_detail=DB::table('erp_market_group_details')
                            ->where(['group_id'=>$request->group->id,'price_id'=>$request->groups['price_id']])
                            ->select('group_price','id')->first();
                        $value->currentPrice=$group_detail->group_price;
                        $request->groups=array_merge($request->groups,['group_detail_id'=>$group_detail->id]);
                    }
                }
            }
        }

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
                    if ($sku->spu_type == 1){
                        foreach ($sku->productIds as $vv){
                            $order_info[] = [
                                'goods_id' => $vv,
                                'price'    => $sku->union_price[$vv],
                                'number'   => $sku->num*$sku->union_num[$vv],
                                'special_id'   => $sku->special_id,
                                'productIds'   => $sku->productIds,
                                'union_id'   => $sku->union_id,
                                'union_num'   => $sku->union_num,
                                'business_id'   => $sku->business_id,
                                'is_public'   => $sku->is_public,
                            ];
                        }
                    }else{
                        $order_info[] = [
                            'goods_id' => $sku->productId,
                            'price'    => $sku->currentPrice,
                            'number'   => $sku->num,
                            'special_id'   => $sku->special_id,
                            'productIds'   => $sku->productIds,
                            'union_id'   => $sku->union_id,
                            'union_num'   => $sku->union_num,
                            'business_id'   => $sku->business_id,
                            'is_public'   => $sku->is_public
                        ];
                    }
                    $tmp_cartIds[] = $sku->cartId;
                    $sku->skuPrice=$sku->currentPrice * $sku->num;
                    //限购数据
                    $limit_buy_data=[
                        'user_id'=>$request->user->wxUserId,
                        'spu_id'=>$sku->spu_id,
                        'market_class'=>$request->user->market_class,
                    ];
                    if ($sku->spu_type==0){
                        $limit_buy_data['sku_id']=$sku->productId;
                    }
                    $limit_buy_num=$this->market->checkLimitBuy($limit_buy_data);
                    $limit_insert_data=[];
                    if ($limit_buy_num!=='unlimit'){
                        $limit_insert_data[]=[
                            'user_id'=>$request->user->wxUserId,
                            'spu_id'=>$sku->spu_id,
                            'sku_id'=>$sku->productId,
                            'created_at'=>date('Ymd'),
                        ];
                    }
                }

                // 本单运费
                $expressPriceResult = $this->freightRepository->computedFreight($tmp_cartIds, $addressId);
                $freight = $expressPriceResult['total_freight'];//最后会减去其他运费优惠
                $origin_freight = $expressPriceResult['total_freight'];//记录没有优惠时的运费

                // 从馆区Id 到 仓库id
                $warehouse = $this->warehouseRepository->getWarehouseByMpNameAndCartIds($listKey ,$tmp_cartIds);
                //运费优惠&优惠券
                //检测
                $arr=$this->assemble_params($list,$request);
                $market_freight_id=0;
                if (isset($mp_selected_freight[$listKey]) && !empty($mp_selected_freight[$listKey])){
                    //获取当前可用的运费优惠
                    $available_freights=$this->market->getAvailableFreights($arr['arr'])->pluck('id')->toArray();
                    //如果已选择的优惠不在可用的列表中
                    if (!in_array($mp_selected_freight[$listKey], $available_freights)){
                        return [
                            'success' => false,
                            'msg'     => '该运费优惠不适用'
                        ];
                    }
                    $market_freight=MarketFreight::find($mp_selected_freight[$listKey]);
                    if ($market_freight){
                        if ($this->market->isFreightFree($market_freight->type)){
                            $freight=0;
                        }else{
                            $freight=($freight > $market_freight->decr)?$freight - $market_freight->decr : 0;
                        }
                        $market_freight_id=$market_freight->id;
                    }
                }
                $mp_coupons_check_result=[];
                if (isset($mp_selected_coupons_arr[$listKey]) && !empty($mp_selected_coupons_arr[$listKey])){
                    //检测
                    $mp_coupons_check_result=$this->market->checkAvailableCoupons($arr['arr'],$mp_selected_coupons_arr[$listKey]);
                    if (!$mp_coupons_check_result['success']){
                        return [
                            'success' => false,
                            'msg'     => '该优惠券不适用'
                        ];
                    }
                    if ($freight>0){//减运费
                        if ($freight>=$mp_coupons_check_result['freight_decr']){
                            $freight-=$mp_coupons_check_result['freight_decr'];
                        }else{
                            $freight=0;
                        }
                    }
                }

                //如果有限时特价直接包邮
                foreach ($list as $skuKey => $sku) {
                    if($sku->special_id != 0){
                        $freight=0;
                        $market_freight_id=0;
                        break;
                    }
                }

                $orderData = [
                    'order_info_json'  => json_encode($order_info),   //下单json 新增明细   [{"goods_id 商品id":"1","price价格":"124","number数量":"55"},{}]
                    'warehouse_id'     => $warehouse->id,             //仓库id
                    'business_id'      => $businessId,                //事业部id
                    'sale_date'        => date('Y-m-d'),       //销售日期 date类型
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
                    'user_remark'      => $message,                   //客户备注
                    'operator_user_id' => 1,                          //后台操作员users id
                    'insert_type'      => 1,                          //添加类型， erp添加值为0 小程序添加值为1
                    'idNumber'         => $idCard->idNumber,          //身份证号 varchar 18
                    'imageFront'       => $idCard->imageFront,        //文件名 比如  1a453fe7b8951db3885c52846a79ea75.jpg
                    'imageBack'        => $idCard->imageBack,         //文件名 比如  1a453fe7b8951db3885c52846a79ea75.jpg
                    'ident_id'         => $idCard->id,                 //erp_shopmp_identity_card的id
                    'market_freight_id'=> $market_freight_id,                 //
                    'origin_freight'=> $origin_freight,                 //
                    'coupon_decr'=> isset($mp_coupons_check_result['normal_decr'])?$mp_coupons_check_result['normal_decr']:0,//
                    'mp_id'=>$listKey
                ];

                //如果有，追加发货地址
                if (!empty($send_address)){
                    $send_address_data=[
                        'send_province'         => $send_address->province,         //发件人省
                        'send_city'             => $send_address->city,             //发件人市
                        'send_area'             => $send_address->area,             //发件人区
                        'send_tel'              => $send_address->phone,            //发件人手机
                        'send_phone'            => $send_address->phone,            //发件人手机
                        'send_name'             => $send_address->name,             //发件人姓名
                        'send_address'          => $send_address->detail,           //发件人地址
                    ];
                    $orderData=array_merge($orderData,$send_address_data);
                }

                $orderResult = $stockOrder->addStockOrder($orderData,$type);

                if ($orderResult['code'] === 200) {
                    $orderIds[] = $orderResult['stock_id'];
                } else {
                    DB::rollBack();
                    return [
                        'success' => false,
                        'msg'     => $orderResult['msg']
                    ];
                }
                // 下单成功更新对应的购物车记录 isOrder orderId orderTime 三个字段 表erp_shop_cart
                $this->cartRepository->order($tmp_cartIds, $orderResult['stock_id']);
                //更新该订单使用的优惠券及优惠券的使用次数和该优惠券状态
                if (isset($mp_selected_coupons_arr[$listKey])){
                    event('coupons.updateStatus',[
                        'user_id'=>$request->user->wxUserId,
                        'order_id'=>$orderResult['stock_id'],
                        'coupon_ids'=>$mp_selected_coupons_arr[$listKey],
                    ]);
                }
                //更新参团或开团信息
                if ($request->group){
                    $total=0;
                    foreach ($order_info as $v){
                        $total+=$v['price']*$v['number'];
                    }
                    DB::table('erp_stock_order')->where('id',$orderResult['stock_id'])->update(['group_price'=>$total]);
                    if (!isset($request->groups['group_buy_type']) || $request->groups['group_buy_type']==2){
                        $this->market->dealGroup($request,$orderResult['stock_id'],$request->user->wxUserId);
                    }
                }

                //更新限购
                foreach ($limit_insert_data as &$v){
                    if (!empty($v)){
                        $v['order_id']=$orderResult['stock_id'];
                    }
                }
                if (!empty($limit_insert_data)){
                    DB::table('erp_limit_buy')->insert($limit_insert_data);
                }

            }
            //更新用户新人字段
            if ($request->user->is_new==1){
                DB::table('wxuser')->where('id',$request->user->wxUserId)->update(['is_new'=>0]);
            }

            // /**
            //  * 下单成功检测对应的sku(stock)表的can_buy_num 如果为0触发一系列操作
            //  * 根据已改变的can_buy_num判断可能触发的操作，注意调用dispatchOrder的位置
            //  */
            // $this->skuRepository->dispatchOrder($request->cartIds);

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
        /**
         * 拆包裹逻辑
         */
        $address = $this->addressRepository->get($addressId);

        $market_class_map = config('admin.market_class');
        $current = $market_class_map[$request->user->market_class];

        // sku列表
        $list = DB::table('erp_shop_cart as cart')
            -> whereIn('cart.id', $cartIds)
            // spu表
            -> leftJoin('erp_mp_name_spu_link', 'cart.spuId', 'erp_mp_name_spu_link.id')
            -> leftJoin('erp_spu_list', 'erp_mp_name_spu_link.spu_id', 'erp_spu_list.id')
            // sku表
            -> leftJoin('erp_product_price', 'cart.skuId', 'erp_product_price.id')
            -> leftJoin('erp_product_list', 'erp_product_price.product_id', 'erp_product_list.id')
            // 馆区表
            -> leftJoin('erp_mp_name', 'erp_mp_name_spu_link.mp_name_id', 'erp_mp_name.id')
            -> select([
                // 购物车id
                'cart.id as cartId',
                'cart.num',
                'cart.skuId as skuId',
                'erp_product_price.price_' . $current . ' as currentPrice',
                // 仓库Id
                // 'erp_spu_list.warehouse_id',
                // 馆区Id
                'erp_mp_name_spu_link.mp_name_id',
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
                        'goods_id' => $sku->productId,
                        'price'    => $sku->currentPrice,
                        'number'   => $sku->num
                    ];
                    $tmp_cartIds[] = $sku->cartId;
                }
                // 本单运费
                $expressPriceResult = $this->freightRepository->computedFreight($tmp_cartIds, $addressId);
                $freight = $expressPriceResult['total_freight'];

                // 从馆区Id 到 仓库id
                $warehouse = $this->warehouseRepository->getWarehouseByMpNameAndCartIds($listKey ,$tmp_cartIds);

                $orderData = [
                    'order_info_json'  => json_encode($order_info),   //下单json 新增明细   [{"goods_id 商品id":"1","price价格":"124","number数量":"55"},{}]
                    'warehouse_id'     => $warehouse->id,             //仓库id
                    'business_id'      => $warehouse->business_id,    //事业部id
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
                    'user_remark'      => $message,                   //用户备注
                    'operator_user_id' => 1,                          //后台操作员users id
                    'insert_type'      => 1,                          //添加类型， erp添加值为0 小程序添加值为1
                    'idNumber'         => $address->idNumber,         //身份证号 varchar 18
                    'imageFront'       => $address->imageFront,       //文件名 比如  1a453fe7b8951db3885c52846a79ea75.jpg
                    'imageBack'        => $address->imageBack         //文件名 比如  1a453fe7b8951db3885c52846a79ea75.jpg
                ];

                $orderResult = $stockOrder->addStockOrder($orderData);

                if ($orderResult['code'] === 200) {
                    $orderIds[] = $orderResult['stock_id'];
                } else {
                    DB::rollBack();
                    return [
                        'success' => false,
                        'msg'     => $orderResult['msg']
                    ];
                }
                // 下单成功更新对应的购物车记录 isOrder orderId orderTime 三个字段 表erp_shop_cart
                $this->cartRepository->order($tmp_cartIds, $orderResult['stock_id']);

            }

            // /**
            //  * 下单成功检测对应的sku(stock)表的can_buy_num 如果为0触发一系列操作
            //  * 根据已改变的can_buy_num判断可能触发的操作，注意调用dispatchOrder的位置
            //  */
            // $this->skuRepository->dispatchOrder($request->cartIds);

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
        $cartIds = $this->cartRepository->getCartIdsByOrderId($orderId);
        // vip等级价格map
        $market_class_map = config('admin.market_class');
        // 当前等级字母
        $current = $market_class_map[$request->user->market_class];

        // 这里应该看到历史信息

        // 商品相关信息
        // 当前的商品购买价格
        $list = DB::table('erp_shop_cart as cart')
            -> whereIn('cart.id', $cartIds)
            // spu表
            -> leftJoin('erp_mp_name_spu_link', 'cart.spuId', 'erp_mp_name_spu_link.id')
            -> leftJoin('erp_spu_list', 'erp_mp_name_spu_link.spu_id', 'erp_spu_list.id')
            // sku表
            -> leftJoin('erp_product_price', 'cart.skuId', 'erp_product_price.id')
            -> leftJoin('erp_product_list', 'erp_product_price.product_id', 'erp_product_list.id')
            -> leftJoin('erp_stock_order_info', function($q) use ($orderId) {
                $q->on('erp_stock_order_info.product_id', '=', 'erp_product_list.id')
                    ->where('erp_stock_order_info.stock_order_id', '=', $orderId);
            })
            -> where('erp_stock_order_info.flag', 0)
            -> select([
                'erp_product_list.image',
                'erp_spu_list.name as spuName',
                'erp_spu_list.type as spu_type',
                'erp_product_list.product_name',
                'erp_product_list.product_no',
                'cart.num',
                'cart.spuId',
                'cart.special_id',
                'erp_product_price.price_' . $current . ' as currentPrice',
            ])
            -> get();
        $sumNum = 0;
        $price  = 0;
        foreach ($list as $key => $value) {

            if($value->spu_type==0 && $value->special_id > 0){//限时特价
                $special_price=DB::table('erp_special_price')
                    ->where(['id'=>$value->special_id,'flag'=>0])
                    ->value('price');
                if(!empty($special_price)){
                    $value->currentPrice=$special_price;
                }
            }

            //套餐
            if ($value->spu_type==1){
                if ($value->special_id != 0){
                    $unionsku=$this->special->getUnionSpecialSku(explode('_',$value->special_id));
                    $value->currentPrice=$unionsku->price;
                    $value->sku_names=$unionsku->info;
                }else{
                    $union_info=$this->getUnionInfo($value->spuId,$current);
                    $value->currentPrice=$union_info['currentPrice'];
                    $value->sku_names=$union_info['sku_names'];
                }
            }

            if ($result['order']->group_price>0){
                $value->currentPrice=$result['order']->group_price;
            }
            // 拼装图片
            $list[$key]->image = $value->image ? getImageUrl($value -> image) : '';
            // 计算总数
            $sumNum += $value->num;
            // 精确乘法
            $price  += $this->mathRepository->math_mul($value->num, $value->currentPrice);
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

        //优惠活动
        $result['order']->market['freight']=$this->math_sub($result['order']->origin_freight,$result['order']->freight);
        unset($result['order']->freight);

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

        $result = $stockOrder->orderList(
            $request->business_id,
            $request->user->wxUserId,
            $sale_status,
            $send_status
        );

        $orders = $result['orderList'];

        // vip等级价格map
        $market_class_map = config('admin.market_class');
        // 当前等级字母
        $current = $market_class_map[$request->user->market_class];

        foreach ($orders as $key => $order) {
            $skus = DB::table('erp_shop_cart as cart')
                -> where('isOrder', 1)
                -> where('orderId', $order->id)
                // spu表
                -> leftJoin('erp_mp_name_spu_link', 'cart.spuId', 'erp_mp_name_spu_link.id')
                -> leftJoin('erp_spu_list', 'erp_mp_name_spu_link.spu_id', 'erp_spu_list.id')
                // sku表
                -> leftJoin('erp_product_price', 'cart.skuId', 'erp_product_price.id')
                -> leftJoin('erp_product_list', 'erp_product_price.product_id', 'erp_product_list.id')
                -> leftJoin('erp_stock_order_info', function($q) use ($order) {
                    $q->on('erp_stock_order_info.product_id', '=', 'erp_product_list.id')
                        ->where('erp_stock_order_info.stock_order_id', '=', $order->id);
                })
                -> where('erp_stock_order_info.flag', 0)
                -> select([
                    'erp_product_list.image',
                    'erp_spu_list.name as spuName',
                    'erp_spu_list.type as spu_type',
                    'erp_product_list.product_name',
                    'erp_product_list.product_no',
                    'cart.num',
                    'cart.spuId',
                    'cart.special_id',
                    'erp_product_price.price_' . $current . ' as currentPrice',
                ])
                -> get();
            $skuNum = 0;
            foreach ($skus as $k => $sku) {
                if($sku->spu_type==0 && $sku->special_id != 0){//限时特价
                    $special_price=DB::table('erp_special_price')
                        ->where(['id'=>$sku->special_id,'flag'=>0])
                        ->value('price');
                    if(!empty($special_price)){
                        $sku->currentPrice=$special_price;
                    }
                }

                //套餐
                if ($sku->spu_type==1){
                    if ($sku->special_id != 0){
                        $unionsku=$this->special->getUnionSpecialSku(explode('_',$sku->special_id));
                        $sku->currentPrice=$unionsku->price;
                        $sku->sku_names=$unionsku->info;
                    }else{
                        $union_info=$this->getUnionInfo($sku->spuId,$current);
                        $sku->currentPrice=$union_info['currentPrice'];
                        $sku->sku_names=$union_info['sku_names'];
                    }
                }

                $skuNum += $sku->num;
                // 拼装图片
                $skus[$k]->image = $sku->image ? getImageUrl($sku -> image) : '';
                //团购价
                if ($order->group_price>0){
                    $sku->currentPrice=$order->group_price;
                }
            }

            $count = DB::table('erp_stock_order_info')
                -> where('stock_order_id', $order->id)
                -> where('flag', 0)
                -> select([
                    'number'
                ])
                -> sum('number');

            // 日期修改
            $orders[$key]->skuNum = $count;
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
     * 组装支付信息 1.1版本
     */
    public function assemble_1_1($request)
    {
        $cartIds    = $request->cartIds;
        $businessId = $request->business_id;
        $addressId  = $request->addressId;
        $send_addressId  = $request->filled('sendAddressId')?$request->sendAddressId:'';
        $selected_coupon  = $request->filled('selected_coupon')?$request->selected_coupon:[];
        $idCardId   = $request->idCardId;

        // vip等级价格map
        $market_class_map = config('admin.market_class');
        // 当前等级字母
        $current = $market_class_map[$request->user->market_class];

        if ($this->addressRepository->checkExistAddressById($addressId, $request->user->wxUserId)) {
            // 如果输入一个地址，就按照这个地址算价格
            $address = $this->addressRepository->get($addressId);
        } else {
            // 如果没有地址，看看有没有默认地址，如果有默认地址就按默认地址计算
            $address = $this->addressRepository->getDefault($request->user->wxUserId);
        }
        if ($this->addressRepository->checkExistSendAddressById($send_addressId, $request->user->wxUserId)) {
            $send_address = $this->addressRepository->getSend($send_addressId);
        } else {
            //默认发货地址
            $send_address = $this->addressRepository->getSendDefault($request->user->wxUserId);
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
        // 当前的商品购买价格
        $list = DB::table('erp_shop_cart as cart')
        -> whereIn('cart.id', $cartIds)
        // spu表
        -> leftJoin('erp_mp_name_spu_link', 'cart.spuId', 'erp_mp_name_spu_link.id')
        -> leftJoin('erp_spu_list', 'erp_mp_name_spu_link.spu_id', 'erp_spu_list.id')
        -> leftJoin('erp_mp_name', 'erp_mp_name_spu_link.mp_name_id', 'erp_mp_name.id')
        // sku表
        -> leftJoin('erp_product_price', 'cart.skuId', 'erp_product_price.id')
        -> leftJoin('erp_product_list', 'erp_product_price.product_id', 'erp_product_list.id')
        -> where('erp_product_price.status', 1)
        -> where('erp_product_price.is_show', 1)
        -> where('erp_product_price.has_stock', '>' ,0)
        -> where('cart.businessId', $businessId)
        -> where('cart.userId', $request->user->wxUserId)
        -> where('cart.isDel',0)
        -> where('cart.isOrder', 0)
        -> select([
            'erp_product_list.image',
            'erp_mp_name_spu_link.id as mp_spu_id',
            'erp_spu_list.name as spuName',
            'erp_mp_name.id as mp_id',
            'erp_mp_name.mp_name',
            'erp_product_list.product_name',
            'erp_product_list.product_no',
            'erp_product_list.class_id',
            'erp_product_list.id as product_list_id',
            'cart.num',
            'cart.spuId',
            'cart.skuId',
            'erp_spu_list.type as spu_type',
            'cart.special_id',//
            'erp_product_price.price_' . $current . ' as currentPrice',
        ])
        -> get();
        $sumNum = 0;
        $price  = 0;
        $skuSumPrice = 0;
        // ------------会员折扣（暂定）--------------
        $vipDiscount = $this->math_add(0, 0);
        // 运费
        if (!empty((array)$address)) {
            $expressPriceResult = $this->freightRepository->computedFreight($cartIds, $address->id);
            $mpExpressPrice = $expressPriceResult['mp_freights'];
            $expressPrice = $expressPriceResult['total_freight'];
            $expressPrice = $this->math_add($expressPrice, 0);
            $showExpressPrice = true;
        } else {
            $mpExpressPrice = [];
            $expressPrice = $this->math_add(0, 0);
            $showExpressPrice = false;
        }


        foreach ($list as $key => $value) {
            if($value->spu_type==0 && $value->special_id > 0){//限时特价
                $special_price=DB::table('erp_special_price')
                    ->where(['id'=>$value->special_id,'flag'=>0])
                    ->value('price');
                if(!empty($special_price)){
                    $value->currentPrice=$special_price;
                }
            }

            //套餐
            if ($value->spu_type==1){
                if ($value->special_id > 0){
                    $unionsku=$this->special->getUnionSpecialSku(explode('_',$value->special_id));
                    $value->currentPrice=$unionsku->price;
                    $value->skuName=$unionsku->info;
                }else{
                    $union_info=$this->getUnionInfo($value->spuId,$current);
                    $value->currentPrice=$union_info['currentPrice'];
                    $value->sku_names=$union_info['sku_names'];
                }
            }

            // 拼装图片
            $list[$key]->image = $value->image ? getImageUrl($value -> image) : '';
            // 计算总数
            $sumNum += $value->num;

            //如果从团购进来的，取团购价,因为团购只能通过立即购买且只能买一个,所以必定是这个商品
            if ($request->group){
                if ($request->filled('group_buy_type') && $request->group_buy_type==1){//团购页原价购买
                    if ($request->group->spu_type==1){
                        $value->currentPrice=$request->group->union_origin_price;
                    }else{
                        $value->currentPrice=DB::table('erp_market_group_details')
                            ->where(['group_id'=>$request->group->id,'price_id'=>$request->price_id])
                            ->value('origin_price');
                    }
                }else{//团购价
                    if ($request->group->spu_type==1){
                        $value->currentPrice=$request->group->union_group_price;
                    }else{
                        $value->currentPrice=DB::table('erp_market_group_details')
                            ->where(['group_id'=>$request->group->id,'price_id'=>$request->price_id])
                            ->value('group_price');
                    }
                }
                $market_groups['group_id']=$request->group->id;
                $request->has('price_id') && $market_groups['price_id']=$request->price_id;
                $request->has('group_buy_type') && $market_groups['group_buy_type']=$request->group_buy_type;
                $request->has('open_group_id') && $market_groups['open_group_id']=$request->open_group_id;
            }
            // 精确乘法
            $price       += $this->math_mul($value->num, $value->currentPrice);
            $skuPrice     = $this->math_mul($value->num, $value->currentPrice);
            $skuSumPrice  = $this->math_add($skuSumPrice, $skuPrice);
            $value->skuPrice=$skuPrice;
        }

        // 减去会员折扣
        $price = $this->math_sub($price, $vipDiscount);

        // 加上运费
        $price = $this->math_add($price, $expressPrice);

        //按馆区分组
        $list=$list->groupBy('mp_name');
        $purchaseList=[];
        foreach ($list as $k=>$v){
            if (empty($mpExpressPrice)){
                $mp_freights=0;
            }else{
                $mp_freights=$this->math_add($mpExpressPrice[$v->first()->mp_id],0);
            }
            $purchaseList[]=[
                'mp_name'=>$k,
                'list'=>$v,
                'mp_freights'=>$mp_freights
            ];
        }

        //优惠活动
        //--运费
        $best_freights=[];
        $market_coupons=[];
        foreach ($list as $value){
            $params_arr=$this->assemble_params($value,$request);
            //如果有限时特价，直接包邮，限时特价暂不参与优惠券
            if (isset($params_arr['arr']['specials_ids'])){
                $best_freights[$params_arr['mp_id']]='free_shipping';
            }else{
                $best_freights[$params_arr['mp_id']]=$this->market->getBestFreight($params_arr['arr']);//获取最优惠的运费
            }
            $market_coupons[$params_arr['mp_id']]=$this->market->getAvailableCoupons($params_arr['arr']);//优惠券
        }
        //--优惠券
        $deal_coupons_result=$this->dealCouponsList($market_coupons,$selected_coupon);

        $total_decr_freights=0;
        $total_decr_coupons=0;
        $mp_selected_freight=[];
        if ($showExpressPrice){
            //获取总共减去的运费
            $result=$this->getTotalDecrFreight($best_freights,$mpExpressPrice,$deal_coupons_result['mp_freight_coupon']);
            $total_decr_freights=$result[0];
            $mp_selected_freight=$result[1];
            $total_decr_coupons+=$result[2];
        }

        $price=$this->math_sub($price,$total_decr_freights);//减去总的运费优惠
        if ($price > $deal_coupons_result['total_normal_coupon_decr']){
            $total_decr_coupons+=$deal_coupons_result['total_normal_coupon_decr'];
            $price=$this->math_sub($price,$deal_coupons_result['total_normal_coupon_decr']);//减去普通优惠券
        }

        // 余额
        return [
            //优惠相关
            'market'=>[
                'freights'=>[
                    'decr'=>$this->math_add($total_decr_freights,0),
                    'mp_selected_freight'=>$mp_selected_freight
                ],
                'coupons'=>[
                    'list'=>$deal_coupons_result['list'],
                    'count'=>count($selected_coupon),//已选择了多少张优惠券
                    'total'=>$total_decr_coupons,//已减了多少优惠
                    'mp_selected_coupons'=>$deal_coupons_result['mp_selected_coupons']
                ],
                'groups'=>isset($market_groups)?$market_groups:[],
            ],
            // 收货地址
            'address'           => $address,
            //发货地址
            'sendAddress'           => $send_address,
            // 购物清单
            'purchaseList'      => $purchaseList,
            // 配送方式
            'express'           => '跨境配送',
            // 支付方式
            'payType'           => [
                'code' => 'balance',
                'name' => '余额支付'
            ],
            // 总金额
            'price'             => $price,
            // 购物列表总价格
            'skuSumPrice'       => $skuSumPrice,
            // 会员折扣
            'vipDiscount'       => $vipDiscount,
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


    /**
     * 组装支付信息
     */
    public function assemble($request)
    {
        $cartIds    = $request->cartIds;
        $businessId = $request->business_id;
        $addressId  = $request->addressId;
        // vip等级价格map
        $market_class_map = config('admin.market_class');
        // 当前等级字母
        $current = $market_class_map[$request->user->market_class];

        if ($this->addressRepository->checkExistAddressById($addressId, $request->user->wxUserId)) {
            // 如果输入一个地址，就按照这个地址算价格
            $address = $this->addressRepository->get($addressId);
        } else {
            // 如果没有地址，看看有没有默认地址，如果有默认地址就按默认地址计算
            $address = $this->addressRepository->getDefault($request->user->wxUserId);
        }
        // 商品相关信息
        // 当前的商品购买价格
        $list = DB::table('erp_shop_cart as cart')
            -> whereIn('cart.id', $cartIds)
            // spu表
            -> leftJoin('erp_mp_name_spu_link', 'cart.spuId', 'erp_mp_name_spu_link.id')
            -> leftJoin('erp_spu_list', 'erp_mp_name_spu_link.spu_id', 'erp_spu_list.id')
            // sku表
            -> leftJoin('erp_product_price', 'cart.skuId', 'erp_product_price.id')
            -> leftJoin('erp_product_list', 'erp_product_price.product_id', 'erp_product_list.id')
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
                'erp_product_price.price_' . $current . ' as currentPrice',
            ])
            -> get();

        $sumNum = 0;
        $price  = 0;
        $skuSumPrice = 0;
        // ------------会员折扣（暂定）--------------
        $vipDiscount = $this->math_add(0, 0);;
        // 运费
        if (!empty((array)$address)) {
            $expressPriceResult = $this->freightRepository->computedFreight($cartIds, $address->id);
            $expressPrice = $expressPriceResult['total_freight'];
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
            $price       += $this->math_mul($value->num, $value->currentPrice);
            $skuPrice     = $this->math_mul($value->num, $value->currentPrice);
            $skuSumPrice  = $this->math_add($skuSumPrice, $skuPrice);
        }

        // 减去会员折扣
        $price = $this->math_sub($price, $vipDiscount);

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
                'code' => 'balance',
                'name' => '余额支付'
            ],
            // 总金额
            'price'             => $price,
            // 购物列表总价格
            'skuSumPrice'       => $skuSumPrice,
            // 会员折扣
            'vipDiscount'       => $vipDiscount,
            'showExpressPrice'  => $showExpressPrice,
            // 快递费用
            'expressPrice'      => $expressPrice,
            // 商品数
            'sumNum'            => $sumNum,
            'cartIds'           => $cartIds
        ];
    }

    public function isNeedIDCard($cartIds)
    {
        $count = DB::table('erp_shop_cart')
            -> whereIn('erp_shop_cart.id', $cartIds)
            -> leftJoin('erp_mp_name_spu_link', 'erp_shop_cart.spuId', 'erp_mp_name_spu_link.id')
            -> leftJoin('erp_mp_name', 'erp_mp_name_spu_link.mp_name_id', 'erp_mp_name.id')
            -> leftJoin('freight_temp_name', 'erp_mp_name.freight_temp_name_id', 'freight_temp_name.id')
            -> where('freight_temp_name.country', '>', 0)
            -> count();

        return $count > 0;
    }

    public function getUnionInfo($spuId,$current)
    {
        $prices=DB::table('erp_product_price')
            ->leftJoin('erp_product_list','erp_product_price.product_id','erp_product_list.id')
            ->leftJoin('erp_spu_sku_link','erp_product_list.id','erp_spu_sku_link.sku_id')
            ->leftJoin('erp_mp_name_spu_link', function($q) {
                $q->on('erp_product_price.mp_name_id', '=', 'erp_mp_name_spu_link.mp_name_id')
                    ->on('erp_spu_sku_link.spu_id', '=', 'erp_mp_name_spu_link.spu_id');
            })
            ->where('erp_mp_name_spu_link.id',$spuId)
            ->where('erp_spu_sku_link.flag',0)
            ->where('erp_mp_name_spu_link.union_flag',0)
            ->where('erp_mp_name_spu_link.flag',0)
            ->where('erp_product_price.flag', 0)
            ->where('erp_product_price.status', 1)
            ->where('erp_product_price.union_status', 1)
            ->where('erp_product_price.is_show', 1)
            ->where('erp_product_price.has_stock', '>', 0)
            ->select([
                'erp_product_price.product_id',
                'erp_product_price.union_num',
                'erp_product_list.product_name',
                'erp_product_price.price_' . $current . ' as currentPrice',
            ])
            ->get();

        $currentprice=0;
        $productIds=[];
        $union_num=[];
        $union_price=[];
        $sku_names=[];
        foreach ($prices as $k=>$v){
            $price=$this->math_mul($v->currentPrice,$v->union_num);
            $currentprice=$this->math_add($currentprice,$price);
            $productIds[]=$v->product_id;
            $union_num[$v->product_id]=$v->union_num;
            $union_price[$v->product_id]=$v->currentPrice;
            $sku_names[]=['name'=>$v->product_name,'num'=>$v->union_num];
        }
        return [
            'currentPrice'=>$currentprice,
            'productIds'=>$productIds,
            'union_num'=>$union_num,
            'union_price'=>$union_price,
            'sku_names'=>$sku_names,
        ];

    }

    protected function assemble_params($value,$request)
    {
        $arr=[];
        $mp_id='';
        foreach ($value as $v){
            if ($v->spu_type == 1 && $v->special_id == 0){
                $arr['money']['unions'][$v->spuId]=$v->skuPrice;
                $arr['number']['unions'][$v->spuId]=$v->num;
                $arr['unions_ids'][]=$v->spuId;
            }else{
                $tag_ids=DB::table('erp_tags')->leftJoin('erp_sku_tag_link','erp_tags.id','erp_sku_tag_link.tag_id')
                    ->where('erp_sku_tag_link.sku_id',$v->product_list_id)->pluck('erp_tags.id');
                foreach ($tag_ids as $tag_id){
                    if (isset($arr['tags']) && in_array($tag_id,$arr['tags'])){
                        $arr['money']['tags'][$tag_id]+=$v->skuPrice;
                        $arr['number']['tags'][$tag_id]+=$v->num;
                    }else{
                        $arr['money']['tags'][$tag_id]=$v->skuPrice;
                        $arr['number']['tags'][$tag_id]=$v->num;
                        $arr['tags_ids'][]=$tag_id;
                    }
                }
                if ($v->special_id != 0){
                    $arr['money']['specials'][$v->special_id]=$v->skuPrice;
                    $arr['number']['specials'][$v->special_id]=$v->num;
                    $arr['specials_ids'][]=$v->special_id;
                }
                $arr['money']['skus'][$v->skuId]=$v->skuPrice;
                $arr['number']['skus'][$v->skuId]=$v->num;
                $arr['skus_ids'][]=$v->skuId;

                if (isset($arr['mpnames_ids']) && in_array($v->mp_id,$arr['mpnames_ids'])){
                    $arr['money']['mpnames'][$v->mp_id]+=$v->skuPrice;
                    $arr['number']['mpnames'][$v->mp_id]+=$v->num;
                }else{
                    $arr['money']['mpnames'][$v->mp_id]=$v->skuPrice;
                    $arr['number']['mpnames'][$v->mp_id]=$v->num;
                    $arr['mpnames_ids'][]=$v->mp_id;
                }

                if (isset($arr['cats_ids']) && in_array($v->class_id,$arr['cats_ids'])){
                    $arr['money']['cats'][$v->class_id]+=$v->skuPrice;
                    $arr['number']['cats'][$v->class_id]+=$v->num;
                }else{
                    $arr['money']['cats'][$v->class_id]=$v->skuPrice;
                    $arr['number']['cats'][$v->class_id]=$v->num;
                    $arr['cats_ids'][]=$v->class_id;
                }
            }
            isset($v->mp_id) && $mp_id=$v->mp_id;
        }
        $arr['vip']=$request->user->market_class;
        $arr['user_id']=$request->user->wxUserId;

        return [
            'arr'=>$arr,
            'mp_id'=>$mp_id,
        ];
    }

    protected function getTotalDecrFreight($freights,$mpExpressPrice,$mp_freight_coupon)
    {
        if (empty($mpExpressPrice)){
            return [0,[]];
        }
        $total_decr_freights=0;//所有减掉的运费钱
        $total_decr_freight_coupons=0;//运费优惠券总共减掉的钱
        $mp_selected_freight=[];
        foreach ($freights as $k=>$v){
            if (empty($v))
                continue;
            if ($v=='free_shipping'){    //如果有限时特价，直接包邮
                $total_decr_freights+=$mpExpressPrice[$k];
                continue;
            }
            //获取当前运费营销活动减掉的钱
            $freight_decr=$this->market->getFreightDecr($v,$k,$mpExpressPrice);
            $mpExpressPrice[$k]-=$freight_decr;//减去运费营销后还剩的钱
            $total_decr_freights+=$freight_decr;

            $mp_selected_freight[$k]=$v->id;//该订单选择的优惠，馆区id=>优惠id
        }
        //如果运费还能减，就减运费优惠券
        foreach ($mp_freight_coupon as $k=>$v){
            if ($mpExpressPrice[$k]>0){
                if ($mpExpressPrice[$k] > $mp_freight_coupon[$k]){
                    $total_decr_freight_coupons+=$mp_freight_coupon[$k];
                }else{
                    $total_decr_freight_coupons+=$mpExpressPrice[$k];
                }
                $total_decr_freights+=$total_decr_freight_coupons;
            }
        }

        return [
            $total_decr_freights,
            $mp_selected_freight,
            $total_decr_freight_coupons
        ];
    }
    /**
     *  返回账单需要参数
     * @param int $orderId 订单Id
     * @return array 返回结果
     */
    public function getPayById($request, $orderId)
    {
        $stockOrder = new StockOrder();

        $result = $stockOrder->orderInfo($orderId);
//        $cartIds = $this->cartRepository->getCartIdsByOrderId($orderId);
//
//        // 这里应该看到历史信息
//
//        // 商品相关信息
//        // 当前的商品购买价格
//        $list = DB::table('erp_shop_cart as cart')
//            -> whereIn('cart.id', $cartIds)
//            // spu表
//            -> leftJoin('erp_mp_name_spu_link', 'cart.spuId', 'erp_mp_name_spu_link.id')
//            -> leftJoin('erp_spu_list', 'erp_mp_name_spu_link.spu_id', 'erp_spu_list.id')
//            // sku表
//            -> leftJoin('erp_product_price', 'cart.skuId', 'erp_product_price.id')
//            -> leftJoin('erp_product_list', 'erp_product_price.product_id', 'erp_product_list.id')
//            -> leftJoin('erp_stock_order_info', function($q) use ($orderId) {
//                $q->on('erp_stock_order_info.product_id', '=', 'erp_product_list.id')
//                    ->where('erp_stock_order_info.stock_order_id', '=', $orderId);
//            })
//            -> where('erp_stock_order_info.flag', 0)
//            -> select([
//                'erp_product_list.image',
//                'erp_spu_list.name as spuName',
//                'erp_spu_list.type as spu_type',
//                'erp_product_list.product_name',
//                'erp_product_list.product_no',
//                'cart.num',
//                'cart.spuId',
//                'cart.special_id',
//            ])
//            -> get();
        // 这里应该看到历史信息

        // 商品相关信息
        // 当前的商品购买价格
        $list = DB::table('erp_product_list')
            // spu表
            -> leftJoin('erp_stock_order_info', function($q) use ($orderId) {
                $q->on('erp_stock_order_info.product_id', '=', 'erp_product_list.id')
                    ->where('erp_stock_order_info.stock_order_id', '=', $orderId);
            })
            -> where('erp_stock_order_info.flag', 0)
            -> select([
                'erp_product_list.image',
                'erp_product_list.product_name',
                'erp_product_list.product_no',
                'erp_stock_order_info.number'
            ])
            -> get();
        $sumNum = 0;
        foreach ($list as $key => $value) {
            $sumNum += $value->number;
            $value->image = getImageUrl( $value->image);
        }

        $result['order']->purchaseList = $list;
        $result['order']->sumNum       = $sumNum;


        return $result['order'];
    }

    protected function dealCouponsList($mp_coupons,$selected=[])
    {
        $list=[];
        $type1_list=[];
        $type2_list=[];
        $type1_selected_list=[];
        $type1_can_selecte_list=[];
        $type1_cannot_selecte_list=[];
        $type2_selected_list=[];
        $type2_can_selecte_list=[];
        $type2_cannot_selecte_list=[];
        //总共减去的优惠金额
        $total_freight_coupon_decr=0;
        $total_normal_coupon_decr=0;
        $mp_freight_coupon=[];
        //馆区绑定的优惠券id，传到前端，下订单再传回来
        $mp_selected_coupons=[];

        //所有可用的优惠券列表
        foreach ($mp_coupons as $mp=>$v){
            foreach ($v as $vv){
                if (isset($list[$vv->id])){
                    $list[$vv->id]->mp[$mp]=$mp;
                }else{
                    $vv->mp[$mp]=$mp;
                    $list[$vv->id]=$vv;
                }
            }
        }

        //按类型分开优惠券
        foreach ($list as $k=>$v){
            if ($v->type==1){
                $type1_list[$k]=$v;
            }
            if ($v->type==2){
                $type2_list[$k]=$v;
            }
        }

        foreach ($selected as $v){
            //type1
            if (isset($type1_list[$v])){
                $type1_selected_list[]=$type1_list[$v];
                //取第一个馆区
                $first_mp=array_values($type1_list[$v]->mp)[0];
                $mp_selected_coupons[]=$first_mp.'-'.$v;//绑定馆区用的优惠券id
                $total_freight_coupon_decr+=$type1_list[$v]->total_decr;
                $mp_freight_coupon[$first_mp]=$type1_list[$v]->total_decr;//用于计算每个馆区对应的运费
                unset($type1_list[$v]);
                foreach ($type1_list as $kk=>$vv){
                    if (count($vv->mp)==1 && in_array($first_mp,$vv->mp)){
                        $type1_cannot_selecte_list[]=$vv;
                        unset($type1_list[$kk]);
                    }else{
                        unset($type1_list[$kk]->mp[$first_mp]);
                    }
                }
            }
            //type2
            if (isset($type2_list[$v])){
                $type2_selected_list[]=$type2_list[$v];
                //取第一个馆区
                $first_mp=array_values($type2_list[$v]->mp)[0];
                $mp_selected_coupons[]=$first_mp.'-'.$v;//绑定馆区用的优惠券id
                $total_normal_coupon_decr+=$type2_list[$v]->total_decr;
                unset($type2_list[$v]);
                foreach ($type2_list as $kk=>$vv){
                    if (count($vv->mp)==1 && in_array($first_mp,$vv->mp)){
                        $type2_cannot_selecte_list[]=$vv;
                        unset($type2_list[$kk]);
                    }else{
                        unset($type2_list[$kk]->mp[$first_mp]);
                    }
                }
            }
        }
        //剩下的就是可选的
        $type1_can_selecte_list=$type1_list;
        $type2_can_selecte_list=$type2_list;

        /*return [
            'list'=>[
                'freight'=>[
                    'selected'=>$type1_selected_list,
                    'can_selecte'=>array_values($type1_can_selecte_list),
                    'cannot_selecte'=>$type1_cannot_selecte_list,
                ],
                'normal'=>[
                    'selected'=>$type2_selected_list,
                    'can_selecte'=>array_values($type2_can_selecte_list),
                    'cannot_selecte'=>$type2_cannot_selecte_list,
                ]
            ],
            'mp_selected_coupons'=>$mp_selected_coupons,
            'total_normal_coupon_decr'=>$total_normal_coupon_decr,
            'total_freight_coupon_decr'=>$total_freight_coupon_decr,
            'mp_freight_coupon'=>$mp_freight_coupon,
        ];*/
        $freight_list=[];
        foreach ($type1_can_selecte_list as $v){
            $v->selecte_status='0';//可以选择的
            $freight_list[]=$v;
        }
        foreach ($type1_selected_list as $v){
            $v->selecte_status='1';//已选择的
            $freight_list[]=$v;
        }
        foreach ($type1_cannot_selecte_list as $v){
            $v->selecte_status='2';//不能选择的
            $freight_list[]=$v;
        }

        $normal_list=[];
        foreach ($type2_can_selecte_list as $v){
            $v->selecte_status='0';
            $normal_list[]=$v;
        }
        foreach ($type2_selected_list as $v){
            $v->selecte_status='1';
            $normal_list[]=$v;
        }
        foreach ($type2_cannot_selecte_list as $v){
            $v->selecte_status='2';
            $normal_list[]=$v;
        }

        return [
            'list'=>[
                'freight'=>$freight_list,
                'normal'=>$normal_list
            ],
            'mp_selected_coupons'=>$mp_selected_coupons,
            'total_normal_coupon_decr'=>$total_normal_coupon_decr,
            'total_freight_coupon_decr'=>$total_freight_coupon_decr,
            'mp_freight_coupon'=>$mp_freight_coupon,
        ];
    }

}
