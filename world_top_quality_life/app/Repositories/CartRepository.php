<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use App\Repositories\SpecialRepository;
use App\Repositories\SKURepository;

class CartRepository extends BaseRepository
{
    public function __construct(SpecialRepository $specialRepository,MarketRepository $marketRepository,SKURepository $skuRepository)
    {
        $this->special=$specialRepository;
        $this->market=$marketRepository;
         $this->skuRepository = $skuRepository;
    }

    /**
     * 新增记录
     */
    public function add($request)
    {
        //检测是否套餐
        $union_id=0;
        $spu=DB::table('erp_spu_list')
            ->leftJoin('erp_mp_name_spu_link','erp_spu_list.id','erp_mp_name_spu_link.spu_id')
            ->where('erp_mp_name_spu_link.id',$request->spuId)
            ->select([
                'erp_spu_list.id',
                'erp_spu_list.type as spu_type',
            ])->first();
        if ($spu->spu_type==1){
            $union_id=$spu->id;
        }

        $special_id=0;
        //检测是否限时特价类型
        if($request->has('special_id') && !empty($request->special_id)){
            if ($spu->spu_type==1){
                $special=$this->special->getUnionSpecialSku(explode('_',$request->special_id));
            }else{
                $special=$this->special->getSpecialSku([$request->special_id]);
            }
            if ($special->special_code==0){
                $special_id=$request->special_id;
            }else{
                return ['status'=>false,'msg'=>'已失效'];
            }
        }
        //检测限购
        $limit_buy_data=[
            'user_id'=>$request->user->wxUserId,
            'spu_id'=>$spu->id,
            'market_class'=>$request->user->market_class
        ];
        if ($spu->spu_type==0){
            $limit_buy_data['sku_id']=DB::table('erp_product_price')->where('id',$request->skuId)->value('product_id');
        }
        $limit_buy_num=$this->market->checkLimitBuy($limit_buy_data);
        if ($limit_buy_num!=='unlimit' && ($request->num > $limit_buy_num)){
            return ['status'=>false,'msg'=>'已达到限购数量'];
        }

        // 如果已经存在一个了，则增加num
        $already = DB::table('erp_shop_cart')
        -> where([
            ['userId', $request->user->wxUserId],
            ['spuId', $request->spuId],
            ['skuId', $request->skuId],
            ['businessId', $request->business_id],
            ['isDel', 0],
            ['isDirect', 0],
            ['isOrder', 0],
            ['special_id', $special_id],
            ['union_id', $union_id],
        ])
        -> first();

        if (!empty($already)) {
            // 如果存在则直接num加一就可以了
            $current =  DB::table('erp_shop_cart')
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
            'createdTime'   => time(),
            'special_id'   => $special_id,
            'union_id'   => $union_id,
        ];
        return DB::table('erp_shop_cart') -> insertGetId($data);
    }

    public function addDirect($request)
    {
        //检测是否套餐
        $union_id=0;
        $spu=DB::table('erp_spu_list')
            ->leftJoin('erp_mp_name_spu_link','erp_spu_list.id','erp_mp_name_spu_link.spu_id')
            ->where('erp_mp_name_spu_link.id',$request->spuId)
            ->select([
                'erp_spu_list.id',
                'erp_spu_list.type as spu_type',
            ])->first();
        if ($spu->spu_type==1){
            $union_id=$spu->id;
        }

        $special_id=0;
        //检测是否限时特价类型
        if($request->has('special_id') && !empty($request->special_id)){
            if ($spu->spu_type==1){
                $special=$this->special->getUnionSpecialSku(explode('_',$request->special_id));
            }else{
                $special=$this->special->getSpecialSku([$request->special_id]);
            }
            if ($special->special_code==0){
                $special_id=$request->special_id;
            }
        }
        //检测限购
        $limit_buy_data=[
            'user_id'=>$request->user->wxUserId,
            'spu_id'=>$spu->id,
            'market_class'=>$request->user->market_class
        ];
        if ($spu->spu_type==0){
            $limit_buy_data['sku_id']=DB::table('erp_product_price')->where('id',$request->skuId)->value('product_id');
        }
        $limit_buy_num=$this->market->checkLimitBuy($limit_buy_data);
        if ($limit_buy_num!=='unlimit' && ($request->num > $limit_buy_num)){
            return ['status'=>false,'msg'=>'已达到限购数量'];
        }

        $data = [
            'userId'        => $request->user->wxUserId,
            'spuId'         => $request->spuId,
            'skuId'         => $request->skuId,
            'num'           => $request->num,
            'businessId'    => $request->business_id,
            'isDirect'      => 1,
            'createdTime'   => time(),
            'special_id'   => $special_id,
            'union_id'   => $union_id,
        ];
        $cartid=DB::table('erp_shop_cart') -> insertGetId($data);
        return ['status'=>true,'cartid'=>$cartid];
    }

    // 下单对购物车的处理
    public function getCartIdsByOrderId($orderId)
    {
        $cartIdsObj = DB::table('erp_shop_cart')
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
        $list = DB::table('erp_shop_cart')
            -> where('isDel', 0)
            -> where('isOrder', 1)
            -> where('orderId', $orderId)
            -> select([
                'userId',
                'spuId',
                'skuId',
                'num',
                'businessId'
            ])
            -> get();
        return $list;
    }

    // 下单对购物车的处理
    public function order($cartIds, $orderId)
    {
        DB::table('erp_shop_cart')
            ->whereIn('id', $cartIds)
            -> update([
                'isOrder'   => 1,
                'orderId'   => $orderId,
                'orderTime' => time()
            ]);
        return true;
    }

    /**
     * 批量删除记录
     */
    public function deleteByIds(array $ids)
    {
        DB::table('erp_shop_cart')
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

        $cart = DB::table('erp_shop_cart')
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

    public function checkLimitBuyNum($request)
    {
        $cart=DB::table('erp_shop_cart as cart')
        -> leftJoin('erp_product_price', 'cart.skuId', 'erp_product_price.id')
        -> leftJoin('erp_mp_name_spu_link', 'cart.spuId', 'erp_mp_name_spu_link.id')
        -> leftJoin('erp_spu_list', 'erp_mp_name_spu_link.spu_id', 'erp_spu_list.id')
        -> where('cart.id', $request->id)
        -> where('cart.businessId', $request->business_id)
        -> where('cart.userId', $request->user->wxUserId)
        -> where('cart.isDel',0)
        -> where('cart.isOrder', 0)
        -> select([
            'erp_product_price.product_id as sku_id',
            'erp_spu_list.id as spu_id',
            'erp_spu_list.type',
            'cart.num',
        ])
        ->first();

        $limit_buy_data=[
            'user_id'=>$request->user->wxUserId,
            'spu_id'=>$cart->spu_id,
            'market_class'=>$request->user->market_class
        ];
        if ($cart->type==0){
            $limit_buy_data['sku_id']=$cart->sku_id;
        }
        $limit_buy_num=$this->market->checkLimitBuy($limit_buy_data);
        if ($limit_buy_num!=='unlimit' && ($request->num > $limit_buy_num)){
            return ['success'=>false,'msg'=>'已达到限购数量'];
        }

        return [
            'success' => true
        ];
    }

    /**
     * 修改记录
     */
    public function updateBuyNum($id, $num)
    {
        // 校验UserID 和 business_id是不是对

        DB::table('erp_shop_cart')
            ->where('id', $id)
            -> update([
                'num'          => $num,
                'updateTime'   => time()
            ]);
        return true;
    }

    public function getNum($request)
    {
        $count = DB::table('erp_shop_cart')
        -> leftJoin('erp_product_price', 'erp_shop_cart.skuId', 'erp_product_price.id')
        -> where('userId', $request->user->wxUserId)
        -> where('businessId', $request->business_id)
        -> where('isDirect', 0)
        -> where('isDel', 0)
        -> where('isOrder', 0)
        //-> where('union_id',0)
        -> where('erp_product_price.has_stock','>', 0)
        -> where('erp_product_price.status', 1)
        -> where('erp_product_price.is_show', 1)
        -> select([
            'num'
        ])
        -> count();

        return ['currentSumCartNum' => $count];
    }

     /**
     * 查询
     */
    public function searchAll($request)
    {
        // vip等级价格map
        $market_class_map = config('admin.market_class');
        // 当前等级字母
        $current = $market_class_map[$request->user->market_class];

        $list = DB::table('erp_shop_cart as cart')
            ->where('cart.userId', $request->user->wxUserId)
            ->where('cart.businessId', $request->business_id)
            ->where('cart.isDel', 0)
            ->where('cart.isOrder', 0)
            ->where('cart.isDirect', 0)
            // spu表
            -> leftJoin('erp_mp_name_spu_link', 'cart.spuId', 'erp_mp_name_spu_link.id')
            -> leftJoin('erp_spu_list', 'erp_mp_name_spu_link.spu_id', 'erp_spu_list.id')
            -> leftJoin('erp_mp_name', 'erp_mp_name_spu_link.mp_name_id', 'erp_mp_name.id')
            // sku表
            -> leftJoin('erp_product_price', 'cart.skuId', 'erp_product_price.id')
            -> leftJoin('erp_product_list', 'erp_product_price.product_id', 'erp_product_list.id')
            -> select([
                'cart.id',
                'cart.spuId',
                'cart.num',
                'cart.special_id',
                'erp_mp_name.mp_name',
                // spuname
                'erp_spu_list.name as spuName',
                // skuname
                'erp_product_list.product_name as skuName',
                'erp_product_list.id as sku_id',
                // can_buy_num
                'erp_product_price.has_stock as can_buy_num',
                'erp_product_price.status',
                'erp_product_price.is_show',
                'erp_product_price.min_unit',
                // 首图
                'erp_product_list.image',
                // currentPrice
                'erp_product_price.price_' . $current . ' as currentPrice',
                //套餐
                'erp_spu_list.type as spu_type',
                'erp_spu_list.is_public',
                'erp_mp_name_spu_link.union_flag',
            ])
            -> get();
        foreach ($list as $key => $sku) {
            //套餐
            if($sku->is_public == 1)$sku->can_buy_num= $this->skuRepository->fetchSkuStock($sku->sku_id)['stock'];

            if ($sku->spu_type==1){
                if ($sku->special_id != 0){
                    $unionsku=$this->special->getUnionSpecialSku(explode('_',$sku->special_id));
                    if($unionsku->special_code != 0){
                        DB::table('erp_shop_cart')->where('id',$sku->id)->update(['isDel'=>1]);
                        unset($list[$key]);
                        continue;
                    }
                    $sku->currentPrice=$unionsku->price;
                    $sku->skuName=$unionsku->info;
                    $sku->can_buy_num=$unionsku->can_buy_num;
                }else{
                    $prices=DB::table('erp_product_price')
                        ->leftJoin('erp_product_list','erp_product_price.product_id','erp_product_list.id')
                        ->leftJoin('erp_spu_sku_link','erp_product_list.id','erp_spu_sku_link.sku_id')
                        ->leftJoin('erp_mp_name_spu_link', function($q) {
                            $q->on('erp_product_price.mp_name_id', '=', 'erp_mp_name_spu_link.mp_name_id')
                                ->on('erp_spu_sku_link.spu_id', '=', 'erp_mp_name_spu_link.spu_id');
                        })
                        ->where('erp_mp_name_spu_link.id',$sku->spuId)
                        ->where('erp_spu_sku_link.flag',0)
                        ->where('erp_mp_name_spu_link.union_flag',0)
                        ->where('erp_mp_name_spu_link.flag',0)
                        ->where('erp_product_price.flag', 0)
                        ->where('erp_product_price.status', 1)
                        ->where('erp_product_price.union_status', 1)
                        ->where('erp_product_price.is_show', 1)
                        ->where('erp_product_price.has_stock', '>', 0)
                        ->select([
                            'erp_product_list.is_public',
                            'erp_product_list.id as sku_id',
                            'erp_product_price.id',
                            'erp_product_price.min_unit',
                            'erp_product_list.product_no',
                            'erp_product_list.product_name',
                            'erp_product_list.image',
                            'erp_product_price.has_stock as can_buy_num',
                            'erp_product_price.union_num',
                            'erp_product_price.price_' . $current . ' as currentPrice',
                        ])
                        ->orderBy('id','asc')
                        ->get();

                    if ($prices->isEmpty()){
                        unset($list[$key]);
                        continue;
                    }else{
                        $stock_status=0;
                        $sku->currentPrice=0;
                        $sku->skuName=[];
                        foreach ($prices as $k=>$v){
                            if ($v->can_buy_num < $v->union_num){
                                $stock_status=1;
                                break;
                            }
                            if($v->is_public==1){
                                $v->can_buy_num = $this->skuRepository->fetchSkuStock($v->sku_id)['stock'];
                            }
                            if($k==0){
                                $sku->can_buy_num=floor($v->can_buy_num / $v->union_num);
                                $sku->image=$v->image;
                            }else{
                                $sku->can_buy_num=min($sku->can_buy_num,floor($v->can_buy_num / $v->union_num));
                            }
                            $sku->currentPrice += ($v->currentPrice*$v->union_num);
                            $sku->skuName[]=['name'=>$v->product_name,'num'=>$v->union_num];
                        }
                        if ($stock_status == 1 || $sku->can_buy_num < 1){
                            unset($list[$key]);
                            continue;
                        }
                    }
                }
                $sku->status=1;
                $sku->is_show=1;
                $sku->min_unit=1;
            }

            if($sku->spu_type != 1 && $sku->special_id != 0){//限时特价
                $special=$this->special->getSpecialSku(explode('_',$sku->special_id));
                if(empty($special) || $special->special_code != 0){
                    DB::table('erp_shop_cart')->where('id',$sku->id)->update(['isDel'=>1]);
                    unset($list[$key]);
                    continue;
                }else{
                    $sku->currentPrice=$special->price;
                }
            }

            // 当前是否有效 三状态全OK
            $list[$key]->valid = $sku->can_buy_num > 0 && $sku->status === 1 && $sku->is_show === 1 ? true : false;
            if(!$list[$key]->valid){
                unset($list[$key]);
                continue;
            }
            // 首图
            $list[$key]->headImage = $sku->image ? getImageUrl($sku -> image) : '' ;
            unset($list[$key]->image);
            unset($list[$key]->status);
            unset($list[$key]->is_show);
        }

        return array_values($list->toArray());
    }
}
