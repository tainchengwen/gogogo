<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class AgentCartRepository extends BaseRepository
{
    /**
     * 新增记录
     */
    public function add($request)
    {
        // 如果已经存在一个了，则增加num
        $already = DB::table('erp_agent_shop_cart')
        -> where([
            ['userId', $request->user->wxUserId],
            ['spuId', $request->spuId],
            ['skuId', $request->skuId],
            ['businessId', $request->business_id],
            ['isDel', 0],
            ['isDirect', 0],
            ['isOrder', 0]
        ])
        -> first();

        if (!empty($already)) {
            // 如果存在则直接num加一就可以了
            $current =  DB::table('erp_agent_shop_cart')
            ->where('id', $already->id)
            -> update([
                'num'          => $already->num + $request->num,
                'updateTime'   => time()
            ]);
            return $current;
        }

        $data = [
            'userId'        => $request->user->wxUserId,
            'spuId'         => $request->spuId,
            'skuId'         => $request->skuId,
            'num'           => $request->num,
            'businessId'    => $request->business_id,
            'createdTime'   => time()
        ];
        return DB::table('erp_agent_shop_cart') -> insertGetId($data);
    }

    public function addDirect($request)
    {
        $data = [
            'userId'        => $request->user->wxUserId,
            'spuId'         => $request->spuId,
            'skuId'         => $request->skuId,
            'num'           => $request->num,
            'businessId'    => $request->business_id,
            'isDirect'      => 1,
            'createdTime'   => time()
        ];
        return DB::table('erp_agent_shop_cart') -> insertGetId($data);
    }

    // 下单对购物车的处理
    public function getCartIdsByOrderId($orderId)
    {
        $cartIdsObj = DB::table('erp_agent_shop_cart')
            -> where('isDel', 0)
            -> where('isOrder', 1)
            -> where('orderId', $orderId)
            -> select([
                'id'
            ])
            -> get();
        $cartIds = [];
        foreach ($cartIdsObj as $key => $cartIdObj) {
            $cartIds[] = $cartIdObj->id;
        }
        return $cartIds;
    }

    public function getCartsByOrderId($orderId)
    {
        $list = DB::table('erp_agent_shop_cart')
            -> where('isDel', 0)
            -> where('isOrder', 1)
            -> where('orderId', $orderId)
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
        return $list;
    }

    // 下单对购物车的处理
    public function order($cartIds, $orderId)
    {
        // 需要回填很多信息
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
        -> select([
            'cart.id',
            'erp_spu_list.name as spuName',
            'erp_product_list.product_name as skuName',
            'erp_agent_price.price as unitPrice',
            'erp_product_list.product_no',
            'erp_product_list.image'
        ])
        -> get();

        foreach ($list as $key => $cart) {
            DB::table('erp_agent_shop_cart')
            ->where('id', $cart->id)
            -> update([
                'spuName'    => $cart->spuName,
                'skuName'    => $cart->skuName,
                'unitPrice'  => $cart->unitPrice,
                'product_no' => $cart->product_no,
                'image'      => $cart->image,
                'isOrder'    => 1,
                'orderId'    => $orderId,
                'orderTime'  => time()
            ]);
        }

        return true;
    }

    /**
     * 批量删除记录
     */
    public function deleteByIds(array $ids)
    {
        DB::table('erp_agent_shop_cart')
            ->whereIn('id', $ids)
            -> update([
                'isDel'     => 1,
                'delTime'   => time()
            ]);
        return true;
    }

    /**
     * 确权
     */
    public function checkManagePermision($request)
    {

        $cart = DB::table('erp_agent_shop_cart')
            -> where('id', $request->id)
            -> first();

        if (empty($cart)) {
            return [
                'check' => false,
                'msg'   => '购物车不存在！'
            ];
        }

        if ($cart->userId !== $request->user->wxUserId) {
            return [
                'check' => false,
                'msg'   => '无权操作！'
            ];
        }

        if ($cart->isDel) {
            return [
                'check' => false,
                'msg'   => '已删除！'
            ];
        }

        if ($cart->isOrder) {
            return [
                'check' => false,
                'msg'   => '已下单！'
            ];
        }

        return [
            'check' => true,
            'msg'   => ''
        ];
    }

    /**
     * 修改记录
     */
    public function updateBuyNum($id, $num)
    {
        DB::table('erp_agent_shop_cart')
            ->where('id', $id)
            -> update([
                'num'          => $num,
                'updateTime'   => time()
            ]);
        return true;
    }

    public function getNum($request)
    {
        $count = DB::table('erp_agent_shop_cart')
        -> where('userId', $request->user->wxUserId)
        -> where('businessId', $request->business_id)
        -> where('isDirect', 0)
        -> where('isDel', 0)
        -> where('isOrder', 0)
        -> select([
            'num'
        ])
        -> count();

        return ['currentSumCartNum' => $count];
    }

     /**
     * 查询
     */
    public function searchAll1($request)
    {

        $list = DB::table('erp_agent_shop_cart as cart')
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
            -> where('cart.userId', $request->user->wxUserId)
            -> where('cart.businessId', $request-> business_id)
            -> where('cart.isDel', 0)
            -> where('cart.isOrder', 0)
            -> where('cart.isDirect', 0)
            -> select([
                'cart.id',
                'cart.spuId',
                'cart.skuId',
                'cart.num',
                // spuname
                'erp_spu_list.name as spuName',
                // skuname
                'erp_product_list.product_name as skuName',
                // 首图
                'erp_product_list.image',
                // can_buy_num
                // 状态 1
                'erp_product_price.status as selfStatus',
                // 状态 2
                'erp_product_price.is_show',
                // 状态 3
                'erp_product_price.has_stock as can_buy_num',
                // 状态 4
                'erp_agent_price.status as agentStatus',
                // 状态 5
                'erp_agent_price.price',
                'erp_product_price.min_unit',
            ])
            -> get();

        foreach ($list as $key => $sku) {
            // 当前是否有效 三状态全OK
            $list[$key]->valid = $sku->selfStatus === 1
            && $sku->is_show === 1
            && $sku->can_buy_num > 0
            && $sku->agentStatus === 1
            && $sku->price > 0
            ? true
            : false;

            // 首图
            $list[$key]->headImage = $sku->image ? getImageUrl($sku -> image) : '' ;
            unset($list[$key]->selfStatus);
            unset($list[$key]->is_show);
            unset($list[$key]->agentStatus);
        }

        return $list;
    }

    /**
     * 查询
     */
    public function searchAll($request)
    {
        $list = DB::table('erp_agent_shop_cart as cart')
            // spu表
            -> leftJoin('erp_business_spu_link_new', 'cart.spuId', 'erp_business_spu_link_new.new_spu_id')
            ->leftJoin('erp_spu_list','erp_spu_list.id','cart.spuId')
            -> leftJoin('erp_product_list','erp_product_list.id','cart.skuId')
            -> where('cart.userId', $request->user->wxUserId)
            -> where('cart.businessId', $request-> business_id)
            -> where('cart.isDel', 0)
            -> where('cart.isOrder', 0)
            -> where('cart.isDirect', 0)
            ->select([
                'cart.id',
                'cart.spuId',
                'cart.skuId',
                'cart.num',
                'erp_business_spu_link_new.spu_id as bindSpuId',
                'erp_product_list.price',
                'erp_product_list.number as can_buy_num',
                'erp_product_list.product_no',
                'erp_product_list.sku_info',
                'erp_spu_list.img',
                'erp_spu_list.name',
                'erp_spu_list.sub_name',
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

            }
        });
        foreach ($list as $key => $sku) {
            // 当前是否有效 三状态全OK
            $list[$key]->valid =
            $sku->can_buy_num > 0
            && $sku->price > 0
                ? true
                : false;

            // 首图
            $list[$key]->headImage = $sku->img ? getImageUrl($sku -> img) : '' ;
            $list[$key]->skuShow= '';
            if($sku->sku_info){
                $skuInfo = json_decode($sku->sku_info,1);
                foreach ($skuInfo as $v){
                    if($list[$key]->skuShow){
                        $list[$key]->skuShow .= ','.$v['q'].':'.$v['a'] ;
                    }else{
                        $list[$key]->skuShow = $v['q'].':'.$v['a'] ;
                    }


                }
            }
        }

        return $list;
    }
}
