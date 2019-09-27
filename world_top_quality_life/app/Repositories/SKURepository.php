<?php

namespace App\Repositories;

use Httpful\Request;
use Illuminate\Support\Facades\DB;
use mysql_xdevapi\Collection;
use App\Repositories\MarketRepository;
use App\Repositories\Markets\FreightFactory;

class SKURepository extends BaseRepository
{

    public function searchBySPUId($request, $spuId)
    {
        $this->market=new MarketRepository();

        $spu = DB::table('erp_mp_name_spu_link')
            -> leftJoin('erp_spu_list', 'erp_mp_name_spu_link.spu_id', 'erp_spu_list.id')
            -> where('erp_mp_name_spu_link.id', $spuId)
            -> where('erp_mp_name_spu_link.flag', 0)
            -> where('erp_spu_list.flag', 0)
            -> select([
                'erp_mp_name_spu_link.id',
                'erp_mp_name_spu_link.spu_id',
                'erp_mp_name_spu_link.mp_name_id',
                'erp_spu_list.name',
                'erp_spu_list.type as spu_type',
                'erp_spu_list.sub_name',
                'erp_spu_list.details'
            ])
            -> first();

        // vip等级价格map
        $market_class_map = config('admin.market_class');
        // 当前等级字母
        $current = $market_class_map[$request->user->market_class];
        // 是否有下一等级
        $hasNextPrice = $request->user->market_class <= 3;
        // 下一等级
        $nextVIPLevel = $request->user->market_class + 1;
        // 下一等级 字母
        $next = $hasNextPrice ? $market_class_map[$nextVIPLevel] : 's';

        $query = DB::table('erp_spu_sku_link')
        -> leftJoin('erp_product_list','erp_spu_sku_link.sku_id','erp_product_list.id')
        -> leftJoin('erp_product_price', function($q) use ($spu) {
            $q->on('erp_product_price.product_id', '=', 'erp_spu_sku_link.sku_id')
            ->where('erp_product_price.mp_name_id', '=', $spu->mp_name_id);
        })
        -> leftJoin('erp_sku_review',function($q){
            $q->on('erp_sku_review.business_id', '=', 'erp_product_list.business_id')
                ->on('erp_sku_review.sku_id','erp_product_list.id')
                ->where('erp_sku_review.status',1);
        })
        -> where('erp_spu_sku_link.spu_id', $spu->spu_id)
        -> where('erp_spu_sku_link.flag', 0)
        -> where('erp_product_price.flag', 0)
        -> where('erp_product_price.status', 1)
        -> where('erp_product_price.is_show', 1)
        -> where('erp_product_price.has_stock', '>', 0)
        -> select([
            'erp_product_price.id',
            'erp_product_price.product_id as skuId',
            'erp_product_price.min_unit',
            'erp_product_price.mp_name_id',
            'erp_product_list.product_no',
            'erp_product_list.is_public',
            'erp_product_list.class_id',
            'erp_product_list.product_name',
            'erp_product_list.image',
            'erp_product_price.has_stock as can_buy_num',
            'erp_product_price.union_num',
            'erp_product_price.price_' . $current . ' as currentPrice',
            'erp_product_price.price_' . $next . ' as nextPrice',
            'erp_product_price.price_a as vip3_price',
            'erp_product_price.price_b as vip2_price',
            'erp_product_price.price_d as originPrice',
            'erp_sku_review.can_buy_num as public_can_buy_num'
        ])
        -> orderBy('erp_spu_sku_link.sort_index','desc')
        -> orderBy('erp_spu_sku_link.id','desc');

        if (isset($spu->spu_type) && $spu->spu_type==1){
            $skus=$query->where('erp_product_price.union_status',1)->get();
            $union=new \stdClass();//拼接套餐信息
            if ($skus->isEmpty()){
                $union->invalid=true;
            }else{
                $union->info=[];
                $union->images=[];
                $union->id=0;
                $union->min_unit=1;
                $union->can_buy_num=0;
                $union->currentPrice=0;
                $union->nextPrice=0;
                $union->cartNum=0;
                $union->nextVIPLevel=$nextVIPLevel;
                $union->hasNextPrice=$hasNextPrice;
                $union->headImage='';
                $stock_status=0;
                foreach ($skus as $k=>$v){
                    if ($v->can_buy_num < $v->union_num){
                        $stock_status=1;
                        break;
                    }
                    if($v->is_public == 1){
                        //公有sku逻辑处理
                        $v->can_buy_num  = $this->fetchSkuStock($v->skuId)['stock'];
                    }
                    if($k==0){
                        $union->can_buy_num=floor($v->can_buy_num / $v->union_num);
                        $union->headImage=getImageUrl($v->image);
                        $union->id=$v->id;
                    }else{
                        $union->can_buy_num=min($union->can_buy_num,floor($v->can_buy_num / $v->union_num));
                    }
                    $union->info[]=['name'=>$v->product_name,'num'=>$v->union_num];
                    $union->images[]=['url'=>getImageUrl($v->image)];
                    $union->currentPrice += ($v->currentPrice*$v->union_num);
                    $union->nextPrice += ($v->nextPrice*$v->union_num);
                }
                if ($stock_status == 1 || $union->can_buy_num < 1){
                    $union->invalid=true;
                }
                $union->currentPrice=$this->math_add($union->currentPrice,0);
                $union->nextPrice=$this->math_add($union->nextPrice,0);
            }

            //优惠活动
            $union->market=[];
            $market_data=[
                'union'=>[$spuId],
                'vip'=>$request->user->market_class,
                'is_new'=>$request->user->is_new,
                'user_id'=>$request->user->wxUserId,
            ];
            $union->market['freight']=$this->market->getPartakeFreight($market_data);//运费
            $union->market['coupon']=$this->market->getPartakeCoupon($market_data);//优惠券
            //限购
            $union->cannot_buy_reason='库存不足';
            $limit_buy_data=[
                'user_id'=>$request->user->wxUserId,
                'spu_id'=>$spu->spu_id,
                'market_class'=>$request->user->market_class,
            ];
            $limit_buy_num=$this->market->checkLimitBuy($limit_buy_data);
            if ($limit_buy_num!=='unlimit' && ($union->can_buy_num > $limit_buy_num)){
                $union->can_buy_num = $limit_buy_num;
                $union->cannot_buy_reason='已达到限购数量';
            }

            return [$union];
        }else{
            $skus=$query->get();
            foreach ($skus as $key => $sku) {
                //判断是否为公有商品
                if($sku->is_public == 1){
                    $sku->can_buy_num  = $this->fetchSkuStock($sku->skuId)['stock'];
                }
                // 价格保留两位小数
                $skus[$key]->currentPrice =  $this->math_add($sku->currentPrice,0);
                if ($hasNextPrice) {
                    $skus[$key]->nextPrice =  $this->math_add($sku->nextPrice,0);
                }
                $skus[$key]->images       = $this->assembledImages($sku);
                $skus[$key]->hasNextPrice = $hasNextPrice;
                $skus[$key]->cartNum      = 0;
                $skus[$key]->nextVIPLevel = $nextVIPLevel;
                $skus[$key]->headImage    = getImageUrl($sku->image);
                unset($skus[$key]->image);
                //优惠活动
                $sku->market=[];
                //----获取sku的tag ids
                $tag_ids=DB::table('erp_tags')->leftJoin('erp_sku_tag_link','erp_tags.id','erp_sku_tag_link.tag_id')
                    ->where('erp_sku_tag_link.sku_id',$sku->skuId)->pluck('erp_tags.id');
                $market_data=[
                    'price_id'=>$sku->id,
                    'mp_id'=>$sku->mp_name_id,
                    'cat_id'=>$sku->class_id,
                    'tag_ids'=>$tag_ids,
                    'vip'=>$request->user->market_class,
                    'is_new'=>$request->user->is_new,
                    'user_id'=>$request->user->wxUserId,
                ];
                $sku->market['freight']=$this->market->getPartakeFreight($market_data);//运费
                $sku->market['coupon']=$this->market->getPartakeCoupon($market_data);//优惠券
                //限购
                $sku->cannot_buy_reason='库存不足';
                $limit_buy_data=[
                    'user_id'=>$request->user->wxUserId,
                    'spu_id'=>$spu->spu_id,
                    'market_class'=>$request->user->market_class,
                    'sku_id'=>$sku->skuId,
                ];
                $limit_buy_num=$this->market->checkLimitBuy($limit_buy_data);
                if ($limit_buy_num!=='unlimit' && ($sku->can_buy_num > $limit_buy_num)){
                    $sku->can_buy_num = $limit_buy_num;
                    $sku->cannot_buy_reason='已达到限购数量';
                }

            }

            //通过搜索结果传递product_price_id进的详情页，该sku放在第一个
            if($request->has('product_price_id')){
                foreach($skus as $key=>$sku){
                    if($sku->id==$request->product_price_id){
                        $first_sku=$sku;
                        $skus[$key]=$skus[0];
                        $skus[0]=$first_sku;
                        break;
                    }
                }
            }

            return $skus;
        }

    }

    public function dispatchOrder($cartIds)
    {
        // 购物车Ids
        $list = DB::table('erp_shop_cart as cart')
            -> whereIn('cart.id', $cartIds)
            -> select([
                'spuId'
            ])
            -> groupBy('spuId')
            -> get();

        $needPutOffSkuIds = [];
        foreach ($list as $key => $value) {
            // 查找自己名下的的sku状态
            $survival = DB::table('erp_spu_sku_link as link')
                -> where('link.spu_id', $value->spuId)
                -> leftJoin('erp_stock','link.sku_id','erp_stock.product_id')
                -> where('erp_stock.can_buy_num', '>', 0)
                -> where('link.status', 1)
                -> get();

            if ($survival->isEmpty()) {
                $needPutOffSkuIds[] = $value->spuId;
            }
        }

        // 如果没有需要下架的spu，直接返回
        if (empty($needPutOffSkuIds)) {
            return true;
        }

        // 批量下架spu
        DB::table('erp_spu_list')
            -> whereIn('id', $needPutOffSkuIds)
            -> update([
                'status'     => 0,
                'updated_at' => time()
            ]);

        return true;
    }

    public function dispatchCancel($orderId)
    {
        // 查找该订单所有符合上架条件的spuIds
        $needPutOnSPUsList = DB::table('erp_shop_cart as cart')
            -> where('cart.isOrder', 1)
            -> where('cart.orderId', $orderId)
            -> leftJoin('erp_stock','cart.skuId','erp_stock.product_id')
            -> leftJoin('erp_spu_sku_link', function ($join) {
                $join-> on('cart.skuId', '=', 'erp_spu_sku_link.sku_id')
                    -> on('cart.spuId', '=','erp_spu_sku_link.spu_id');
            })
            -> where('erp_stock.can_buy_num', '>', 0)
            -> where('erp_spu_sku_link.status', 1)
            -> select([
                'cart.spuId'
            ])
            -> groupBy('cart.spuId')
            -> get();

        // 制作格式
        $needPutOnSPUs = [];
        foreach ($needPutOnSPUsList as $key => $value) {
            $needPutOnSPUs[] = $value->spuId;
        }

        if (empty($needPutOnSPUs)) {
            return true;
        }

        // 批量上架
        DB::table('erp_spu_list')
            -> whereIn('id', $needPutOnSPUs)
            -> update([
                'status'     => 1,
                'updated_at' => time()
            ]);

        return true;
    }

    public function assembledImages($sku)
    {
        $image_arr = [];
        //先把自己放进去
        if($sku->image){
            $image_arr[] = [
                'name'  => $sku->image,
                'url'   => getImageUrl($sku->image)
            ];
        }
        //查下多图
        $image_details = DB::table('mpshop_product_images') -> where([
            'product_no' => $sku -> product_no,
            'flag' => 0
        ]) -> get();

        if($image_details){
            foreach($image_details as $k => $vo){
                $image_arr[] = [
                    'name' => $vo -> image,
                    'url'  => getImageUrl($vo -> image)
                ];
            }
        }
        //[{name: 'food.jpg', url: 'https://xxx.cdn.com/xxx.jpg'}]
        return $image_arr;
    }


    /**
     * 根据库存自动上下架
     * @param: sku_ids 操作的sku的id
     */
    public function autoPutOnOrOff($sku_ids) {
        $mpNameList = DB::table('erp_product_price')
            -> leftJoin('erp_warehouse', 'erp_warehouse.mp_name_id', 'erp_product_price.mp_name_id')
            -> whereIn('erp_product_price.product_id', $sku_ids)
            -> where('erp_product_price.mp_name_id', '!=', 0)
            -> where('erp_product_price.flag', 0)
            -> where('erp_warehouse.flag', 0)
            -> distinct('mp_name_id')
            -> select([
                'erp_warehouse.id as warehouse_id',
                'erp_product_price.product_id',
                'erp_product_price.mp_name_id'
            ])
            -> get()
            -> groupBy(['product_id','mp_name_id']);
        //异常库位ids
        $unusual_store_house_ids=DB::table('erp_storehouse')->where('is_unusual',0)->pluck('id');

        foreach ($mpNameList as $mpnamelist => $value) {
            foreach ($value as $key => $val) {
                $mp_name_id = '';
                $product_id = '';
                $totalNum = 0;
                foreach ($val as $k => $v ) {
                    $mp_name_id = $v->mp_name_id;
                    $product_id = $v->product_id;
                    $nums = DB::table('erp_stock')
                        -> where('warehouse_id', $v->warehouse_id)
                        -> where('product_id', $v->product_id)
                        -> where('flag', 0)
                        -> whereNotIn('store_house_id', $unusual_store_house_ids)
                        -> pluck('can_buy_num');
                    if ($nums->isEmpty()) {
                        continue;
                    }  else {
                        foreach ($nums as $num => $numv) {
                            $totalNum = $totalNum + $numv;
                        }
                    }
                }
                if ($totalNum <= 0) {
                    DB::table('erp_product_price')
                        -> where([
                            'mp_name_id' => $mp_name_id,
                            'product_id' => $product_id,
                            'flag'       => 0
                        ])
                        -> update([
                            'has_stock' => 0
                        ]);
                } else {
                    DB::table('erp_product_price')
                        -> where([
                            'mp_name_id' => $mp_name_id,
                            'product_id' => $product_id,
                            'flag'       => 0
                        ])
                        -> update([
                            'has_stock' => $totalNum
                        ]);
                }
            }
        }




        // $wareHouseList = DB::table('erp_warehouse')
        //                      -> whereIn('mp_name_id', $mpNameList)
        //                      -> pluck('id');
        // foreach ($wareHouseList as $warehouse => $value) {
        //     foreach ($sku_ids as $sku_id => $v) {
        //         $nums = DB::table('erp_stock')
        //                     -> where([
        //                        'warehouse_id' => $value,
        //                        'product_id'   => $v
        //                     ])
        //                     -> pluck('can_buy_num');
        //         $totalNum = 0;
        //         foreach ($nums as $num => $vnum) {
        //             $totalNum = $totalNum + $vnum;
        //         }

        //         $mp_name_id = DB::table('erp_warehouse')
        //                           -> where('id', $value)
        //                           -> value('mp_name_id');
        //         if (!empty($nums)) {
        //             if ($totalNum < 0) {
        //                 var_dump('NUm<0');
        //                 var_dump($mp_name_id);
        //                 DB::table('erp_product_price')
        //                     -> where([
        //                         'mp_name_id' => $mp_name_id,
        //                         'product_id' => $v,
        //                         'flag'       => 0
        //                     ])
        //                     -> update([
        //                         'has_stock' => 0
        //                     ]);
        //             } else {
        //                 var_dump('NUm>>>>>>0');
        //                 var_dump($mp_name_id);
        //                 DB::table('erp_product_price')
        //                     -> where([
        //                         'mp_name_id' => $mp_name_id,
        //                         'product_id' => $v,
        //                         'flag'       => 0
        //                     ])
        //                     -> update([
        //                         'has_stock' => $totalNum
        //                     ]);
        //                     var_dump($totalNum);
        //             }
        //         }
        //     }
        // }
        // $totalNum = 0;
        // $num = DB::table('erp_stock')
        //            -> where('product_id', $sku_id)
        //            -> pluck('can_buy_num');
        // foreach ($num as $k => $v) {
        //     $totalNum = $totalNum + $v;
        // }
        // if ($totalNum <= 0) {
        //     DB::table('erp_product_price')
        //         -> whereIn('product_id', $sku_id)
        //         -> where('flag', 0)
        //         -> update([
        //             'has_stock' => 0
        //         ]);
        // } else {
        //     DB::table('erp_product_price')
        //         -> whereIn('product_id', $sku_id)
        //         -> where('flag', 0)
        //         -> update([
        //             'has_stock' => $totalNum
        //         ]);
        // }
    }

    /**
     * 获取套餐spu下关联的sku信息
     * @param Request $request
     * @return Collection
     */
    public function getSkusUnion($request)
    {
        $inskuids = DB::table('erp_spu_sku_link')
            -> where('erp_spu_sku_link.flag', 0)
            -> where('erp_spu_sku_link.spu_id', $request->spu_id)
            -> pluck('sku_id');

        $list = DB::table('erp_product_list')
            -> leftJoin('erp_product_price', function ($join) use ($request) {
                $join-> on('erp_product_list.id', '=', 'erp_product_price.product_id')
                    -> where('erp_product_price.mp_name_id', '=', $request->mp_id)
                    -> where('erp_product_price.flag', '=', 0);
            })
            -> leftJoin('erp_product_class as product_class', 'erp_product_list.class_id', 'product_class.id')
            -> leftJoin('erp_product_class as product_brand', 'erp_product_list.brand_id', 'product_brand.id')
            -> leftJoin('erp_product_class as product_series', 'erp_product_list.series_id', 'product_series.id')
            -> whereIn('erp_product_list.id', $inskuids)
            -> where(function($query)use($request){
                //产品类别
                if($request -> class_id){
                    $query -> where('erp_product_list.class_id',$request -> class_id);
                }
                //产品品牌
                if($request -> brand_id){
                    $query -> where('erp_product_list.brand_id',$request -> brand_id);
                }

                //产品系列
                if($request -> series_id){
                    $query -> where('erp_product_list.series_id',$request -> series_id);
                }

                //商品编号
                if($request -> product_no){
                    $query -> where('erp_product_list.product_no',$request -> product_no);
                }
                //商品名称
                if($request -> product_name){
                    $query -> where('erp_product_list.product_name','like','%'.trim($request -> product_name).'%');
                }
            })
            -> select([
                'erp_product_list.id as id',
                'erp_product_list.product_no as product_no',
                'erp_product_list.product_name as product_name',
                'product_class.name as product_class_name',
                'product_brand.name as product_brand_name',
                'product_series.name as product_series_name',
                'erp_product_price.id as product_price_id',
                'erp_product_price.price_a',
                'erp_product_price.price_b',
                'erp_product_price.price_c',
                'erp_product_price.price_d',
                'erp_product_price.price_s',
                'erp_product_price.union_num',
                'erp_product_price.union_status',
                'erp_product_price.status as status',
                'erp_product_price.has_stock as has_stock',
                'erp_product_price.is_show as is_show',
                'erp_product_price.mp_name_id as mpnameid',
                'erp_product_price.min_unit as min_unit'
            ])
            -> orderBy('id')
            -> get();

        return $list;
    }

    public function storeSkusUnion($request)
    {
        foreach($request->list as $k=>$v){
            if($v['union_num'] <= 0 || $v['price_s'] <= 0 || $v['price_a'] <= 0 || $v['price_b'] <= 0 || $v['price_c'] <= 0 || $v['price_d'] <= 0){
                return ['code'=>202,'msg'=>'数据错误'];
            }
        }

        DB::transaction(function() use($request) {
            foreach ($request->list as $v){
                $info = DB::table('erp_product_price')
                    -> where([
                        'product_id' => $v['id'],
                        'mp_name_id' => $request->mpnameid,
                        'flag'       => 0
                    ])
                    -> first();
                if ($info) {
                    // 修改
                    DB::table('erp_product_price')
                        -> where([
                            'id' => $info->id,
                        ])
                        -> update([
                            'price_s'       => $v['price_s'],
                            'price_a'       => $v['price_a'],
                            'price_b'       => $v['price_b'],
                            'price_c'       => $v['price_c'],
                            'price_d'       => $v['price_d'],
                            'updated_at'    =>  time(),
                            'union_num'=>$v['union_num'],
                            'union_status'=>empty($v['union_status'])?0:$v['union_status'],
                        ]);
                } else {
                    DB::table('erp_product_price')
                        -> insert([
                            'product_id' => $v['id'],
                            'mp_name_id' => $request->mpnameid,
                            'price_s'    => $v['price_s'],
                            'price_a'    => $v['price_a'],
                            'price_b'    => $v['price_b'],
                            'price_c'    => $v['price_c'],
                            'price_d'    => $v['price_d'],
                            'is_show'    => 1,
                            'created_at' => time(),
                            'updated_at' => time(),
                            'union_num'=>$v['union_num'],
                            'union_status'=>empty($v['union_status'])?0:$v['union_status'],
                        ]);
                    $this->autoPutOnOrOff([$v['id']]);
                }
                $sku = DB::table('erp_product_list')
                    -> where('id', $v['id'])
                    -> first();

                if (!empty($sku->image) && (float)$sku->declared_price <= 250 && (float)$sku->declared_price > 0 && $sku->weight > 0) {
                    DB::table('erp_product_price')
                        -> where([
                            'product_id' => $v['id'],
                            'mp_name_id' => $request->mpnameid,
                            'flag'       => 0
                        ])
                        -> update([
                            'status' => 1
                        ]);
                }

            }
        });

        return ['code'=>200,'msg'=>'保存成功'];
    }

    public function flagSkusUnion($request)
    {
        DB::table('erp_mp_name_spu_link')
            ->where('mp_name_id',$request->mp_id)
            ->where('spu_id',$request->spu_id)
            ->update(
                ['union_flag'=>$request->flag]
            );

        return ['code'=>200,'msg'=>'更新成功'];
    }

    //获取公有sku下的 库存
    public function fetchSkuStock($skuId){

        $info = DB::table('erp_stock')
             ->leftJoin('erp_sku_review',function($q){
                 $q->on('erp_sku_review.to_store_house_id','erp_stock.store_house_id')
                 ->on('erp_sku_review.sku_id','erp_stock.product_id');
             })
            ->leftJoin("erp_storehouse","erp_storehouse.id",'erp_sku_review.to_store_house_id')
            ->where('erp_sku_review.status',1)
            ->where('erp_stock.product_id',$skuId)
            ->select([
                'erp_stock.product_id',
                'erp_stock.can_buy_num',
                "erp_storehouse.warehouse_id",
                "erp_storehouse.id as storehouse_id",
                'erp_sku_review.can_buy_num as public_can_buy_num',
                'erp_sku_review.business_id'
            ])
            ->get()->toArray();
        if(empty($info)) return 0;
        $private = $public = 0;
        foreach ($info as $v){
            $private += $v->can_buy_num;
            $public += $v->public_can_buy_num;
            $v->min_buy_num = min($v->can_buy_num,$v->public_can_buy_num);
        }
        return [
            'stock'           => min($public,$private),
            'singleMaxStock'  => max(array_column($info,'min_buy_num')),
            'data'            => $info
        ];
    }
}
