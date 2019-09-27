<?php

namespace App\Repositories;

use App\ProductPrice;
use Illuminate\Support\Facades\DB;
use App\MpNameSpuList;
use App\SPUList;
use App\Repositories\SPURepository;
use App\Repositories\SKURepository;

class WarehouseRepository extends BaseRepository
{
    public function __construct(SPURepository $spuRepository, SKURepository $skuRepository)
    {
        $this->spuRepository = $spuRepository;
        $this->skuRepository = $skuRepository;
    }

    public function getWarehouseByMpNameAndCartIds($mp_name_id, $cartIds)
    {

        $warehouseList = DB::table('erp_warehouse')
        -> where('mp_name_id', $mp_name_id)
        -> where('flag', 0)
        -> select([
            'id'
        ])
        -> get();

        // 馆区下面有这么多的仓库
        $warehouseIds = [];
        foreach ($warehouseList as $key => $value) {
            $warehouseIds[] = $value->id;
        }

        // 先拿到所有购物车skus所在的仓库ids去重
        $cartWarehouses = DB::table('erp_shop_cart as cart')
        // sku表
        -> leftJoin('erp_product_price', 'cart.skuId', 'erp_product_price.id')
        -> leftJoin('erp_stock', 'erp_product_price.product_id', 'erp_stock.product_id')
        -> whereIn('erp_stock.warehouse_id', $warehouseIds)
        -> select([
            'erp_stock.warehouse_id'
        ])
        -> groupBy('erp_stock.warehouse_id')
        -> get();
        $cartWarehouseIds = [];
        foreach ($cartWarehouses as $key => $value) {
            $cartWarehouseIds[] = $value->warehouse_id;
        }

        // 然后取第一个(先取自营的)
        $firstWarehouse = DB::table('erp_warehouse')
        -> leftJoin('erp_business', 'erp_warehouse.business_id', 'erp_business.id')
        -> whereIn('erp_warehouse.id', $cartWarehouseIds)
        -> where('erp_warehouse.flag', 0)
        -> orderBy('erp_business.self_status', 'desc')
        -> orderBy('erp_warehouse.id', 'desc')
        -> select([
            'erp_warehouse.id',
            'erp_warehouse.business_id'
        ])
        -> first();

        return $firstWarehouse;
    }

    public function getWarehouses($request)
    {
        $warehouses = DB::table('erp_warehouse')
        -> where('flag', 0)
        -> where('business_id', $request->business_id)
        -> select([
            'id',
            'name',
            'image'
        ])
        -> get();

        foreach ($warehouses as $key => $warehouse) {
            $warehouses[$key]->imageUrl =  getImageUrl($warehouse -> image);
            unset($warehouses[$key]->image);
        }

        return $warehouses;
    }

    public function getMPWarehouses($request)
    {
        $warehouses = DB::table('erp_mp_name')
        -> where('flag', 0)
        // 在小程序中显示
        -> where('is_show', 1)
        -> select([
            'id',
            'mp_name as name',
            'image'
        ])
        -> get();

        foreach ($warehouses as $key => $warehouse) {
            $warehouses[$key]->imageUrl =  getImageUrl($warehouse -> image);
        }

        return $warehouses;
    }

    /**
     * 获取小程序名称列表
     */
    public function getAllMpNameList($request) {
        $mpnames = DB::table('erp_mp_name')
                        -> where('flag', 0)
                        -> select([
                            '*'
                        ])
                        -> get();
        return $mpnames;
    }

    /**
     * 获取事业部名称列表
     */
    public function getAllBusinessNameList($request) {
        $businessnames = DB::table('erp_business')
                        -> select([
                            'id',
                            'name'
                        ])
                        -> get();
        return $businessnames;
    }

    /**
     * 获取所有正常的warehouse列表中的所有信息
     */
    public function getAllWarehouseList($request) {
        $where = [];
        if ($request->mpid) {
            $where = [
                'mp_name_id' => $request->mpid
            ];
        }
        $wherebusiness = [];
        if ($request->businessid) {
            $wherebusiness = [
                'business_id' => $request->businessid
            ];
        }
        $warehouses = DB::table('erp_warehouse')
                        -> leftJoin('erp_business','erp_warehouse.business_id','erp_business.id')
                        -> leftJoin('erp_mp_name','erp_warehouse.mp_name_id','erp_mp_name.id')
                        -> where('erp_warehouse.flag', 0)
                        -> where($where)
                        -> where($wherebusiness)
                        -> where(function($query)use($request){
                            if($request->name){
                                $query -> where('erp_warehouse.name', 'like', '%'."{$request->name}".'%');
                            }
                        })
                        -> select([
                            'erp_warehouse.agent_status',
                            'erp_warehouse.id',
                            'erp_warehouse.name',
                            'erp_warehouse.freight_temp_name_id',
                            'erp_warehouse.image',
                            'erp_warehouse.flag',
                            'erp_warehouse.describe',
                            'erp_business.name as business_name',
                            'erp_business.id as business_id',
                            'erp_mp_name.mp_name as mp_name',
                            'erp_mp_name.id as mp_name_id'
                        ])
                        -> orderBy('id')
                        -> paginate(isset($request->per_page)?$request->per_page:20);
        foreach ($warehouses as $key => $warehouse) {
            $warehouses[$key]->imageUrl =  getImageUrl($warehouse->image);
            unset($warehouses[$key]->image);
        }

        return $warehouses;
    }

    /**
     * 给仓库配置小程序名称
     */
    public function addLinkMpName($request) {
        DB::table('erp_warehouse')
            -> whereIn('id',$request->warehouseids)
            -> update([
                'mp_name_id' => $request->mpnameid
            ]);
    }

    /**
     * 获取和馆区名称已关联的SPU列表
     */
    public function getLinkedSpu($request) {
        //0普通1套餐
        $type=0;
        if ($request->type){
            $type=$request->type;
        }

        $wherecategory = [];
        if($request->class_id){
            $wherecategory[] = [
                'class_id',$request->class_id
            ];
        }
        $list = MpNameSpuList::where([
                                'erp_mp_name_spu_link.mp_name_id' => $request->id,
                                'erp_mp_name_spu_link.flag'       => 0
                                ])
                    -> leftJoin('erp_spu_list', 'erp_spu_list.id', 'erp_mp_name_spu_link.spu_id')
                    -> leftJoin('erp_spu_category','erp_spu_list.class_id','erp_spu_category.id')

                    -> where($wherecategory)
                    -> where(function($query)use($request){
                        if($request->spuname){
                            $query -> where('erp_spu_list.name', 'like', '%'."{$request->spuname}".'%');
                        }
                    })
                    -> where('erp_spu_list.type',$type)
                    -> orderBy('erp_spu_list.id','desc')
                    -> select([
                        'erp_spu_list.class_id as class_id',
                        'erp_spu_list.name',
                        'erp_spu_list.sub_name',
                        'erp_mp_name_spu_link.spu_id as id',
                        'erp_mp_name_spu_link.status',
                        'erp_mp_name_spu_link.union_flag',
                        'erp_spu_category.id as category_id',
                        'erp_mp_name_spu_link.id as link_id',
                        'erp_mp_name_spu_link.sort_index',
                        'erp_mp_name_spu_link.weight_index',
                        'erp_spu_list.limit_buy_type',
                        'erp_spu_list.limit_buy_number',
                        'erp_spu_list.vip0',
                        'erp_spu_list.vip1',
                        'erp_spu_list.vip2',
                        'erp_spu_list.vip3',
                        'erp_spu_list.vip4',
                        'erp_spu_list.cycle',
                    ])
                    -> paginate(isset($request->per_page)?$request->per_page:20);
        // $list = $this->spuRepository->getSkuNum($list);
        $list = $this->spuRepository->getCategoryNameAll($list);
        return $list;
        // $list = DB::table('erp_spu_sku_link')
        // -> leftJoin('erp_spu_list', 'erp_spu_list.id', 'erp_spu_sku_link.spu_id')
        // -> leftJoin('erp_mp_name_spu_link', 'erp_spu_sku_link.spu_id', 'erp_mp_name_spu_link.spu_id')
        // -> leftJoin('erp_product_price', function($join) use ($request) {
        //     $join->on('erp_product_price.product_id', '=', 'erp_spu_sku_link.sku_id')
        //             ->where('erp_product_price.mp_name_id', '=', "$request->id");
        // })
        // -> where([
        //     'erp_mp_name_spu_link.mp_name_id' => $request->id,
        //     'erp_mp_name_spu_link.flag'       => 0
        // ])
        // -> where(function($query)use($request){
        //     if($request->spuname){
        //         $query -> where('erp_spu_list.name', 'like', '%'."{$request->spuname}".'%');
        //     }
        // })
        // -> select([
        //     'erp_spu_list.name',
        //     'erp_spu_list.sub_name',
        //     'erp_mp_name_spu_link.spu_id as id',
        //     'erp_product_price.status'
        // ])
        // -> paginate(isset($request->per_page)?$request->per_page:20);
        // return $list;
        // $list = Db::table('erp_product_price')
        //             -> leftJoin('erp_spu_sku_link', 'erp_spu_sku_link.sku_id', 'erp_product_price.product_id')
        //             -> leftJoin('erp_spu_list', 'erp_spu_list.spu_id', 'erp_spu_sku_link.spu')
        //             -> where('erp_product_price.mp_name_id', $request->id)
        // $list = DB::table('erp_mp_name_spu_link')
        //             -> leftJoin('erp_spu_sku_link', 'erp_mp_name_spu_link.spu_id', 'erp_spu_sku_link.spu_id')
        //             -> leftJoin('erp_spu_list', 'erp_spu_list.id', 'erp_mp_name_spu_link.spu_id')
        //             -> leftJoin('erp_product_price', function ($join) use ($request) {
        //                 $join-> on('erp_product_price.product_id', '=', 'erp_spu_sku_link.sku_id')
        //                         -> where('erp_product_price.mp_name_id', '=', $request->id);
        //             })
        //             // -> where('erp_product_price.mp_name_id', $request->id)
        //             // -> where('erp_product_price.flag', 0)
        //             -> where('erp_mp_name_spu_link.flag', 0)
        //             -> where('erp_spu_sku_link.flag', 0)
        //             -> select([
        //                 'erp_spu_list.id as id',
        //                 'erp_spu_list.name as name',
        //                 'erp_spu_list.sub_name as sub_name',
        //                 'erp_product_price.status as status',
        //             ])
        //             // -> distinct()
        //             // -> get();
        //             // -> paginate(isset($request->per_page)?$request->per_page:20)
        //             // -> groupBy('id');
        // // var_dump($list);exit;
    }

    /**
     * 获取和馆区名称未关联的SPU列表
     */
    public function getUnLinkedSpu($request) {
        //0普通1套餐
        $type=0;
        if ($request->type){
            $type=$request->type;
        }

        $spuids = DB::table('erp_mp_name_spu_link')
                    -> leftJoin('erp_spu_list', 'erp_spu_list.id', 'erp_mp_name_spu_link.spu_id')
                    -> where([
                        'erp_mp_name_spu_link.mp_name_id' => $request->id,
                        'erp_mp_name_spu_link.flag'       => 0
                    ])
                    -> where('erp_spu_list.type',$type)
                    -> pluck('spu_id');
        $wherecategory = [];
        if($request->class_id){
            $wherecategory[] = [
                'class_id',$request->class_id
            ];
        }
        $list = SPUList::where('erp_spu_list.flag', 0)
                        ->leftJoin('erp_spu_category','erp_spu_list.class_id','erp_spu_category.id')
                        -> whereNotIn('erp_spu_list.id', $spuids)
                        -> where($wherecategory)
                        -> where(function($query)use($request){
                            if($request->spuname){
                                $query -> where('erp_spu_list.name', 'like', '%'."{$request->spuname}".'%');
                            }
                        })
                        -> where('erp_spu_list.type',$type)
                        -> orderBy('erp_spu_list.id','desc')
                        -> select([
                            'erp_spu_list.class_id as class_id',
                            'erp_spu_category.id as category_id',
                            'erp_spu_list.id as id',
                            'erp_spu_list.name',
                            'erp_spu_list.sub_name',
                        ])
                        -> paginate(isset($request->per_page)?$request->per_page:20);
        $list = $this->spuRepository->getCategoryNameAll($list);
        return $list;
    }

    /**
     * 馆区关联SPU
     */
    public function addLinkSpus($request) {
        DB::beginTransaction();
        try {
            $insertData = [];
            foreach ($request->spuIds as $spuId) {
                $count = DB::table('erp_mp_name_spu_link')
                            -> where('mp_name_id', $request->mpnameid)
                            -> where('spu_id', $spuId)
                            -> where('flag', 0)
                            -> count();
                if (empty($count)) {
                    array_push($insertData, ['spu_id' => $spuId, 'mp_name_id' => $request->mpnameid]);
                }
            }
            DB::table('erp_mp_name_spu_link')->insert($insertData);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'code' => 500,
                'msg' => '关联失败'
            ];
        }
        return [
            'code' => 200,
            'msg' => '关联成功'
        ];
    }

    /**
     * 馆区取消关联SPU
     */
    public function removeLinkSpus($request) {
        DB::table('erp_mp_name_spu_link')
            -> where('mp_name_id', $request->mpnameid)
            -> whereIn('spu_id', $request->spuIds)
            -> where('flag', 0)
            -> update([
                'flag' => 1
            ]);
        DB::table('erp_business_spu_link')
            -> where('mp_name_id', $request->mpnameid)
            -> where('flag', 0)
            -> whereIn('spu_id', $request->spuIds)
            -> update([
                'flag' => 1
            ]);
        return [
            'code' => 200,
            'msg' => '取消关联成功'
        ];
    }

    /**
     * 获取馆区已关联SKU
     */
    public function getLinkedSku($request) {
        $mpNameId = $request->id;
        $spuids = DB::table('erp_mp_name_spu_link')
                      -> where([
                          'erp_mp_name_spu_link.mp_name_id' => $request->id,
                          'erp_mp_name_spu_link.flag'       => 0
                        ])
                      -> pluck('spu_id');
        $inskuids = DB::table('erp_spu_sku_link')
                      -> where('erp_spu_sku_link.flag', 0)
                      -> whereIn('erp_spu_sku_link.spu_id', $spuids)
                      -> pluck('sku_id');

        $list = DB::table('erp_product_list')
                    -> leftJoin('erp_product_price', function ($join) use ($mpNameId) {
                        $join-> on('erp_product_list.id', '=', 'erp_product_price.product_id')
                        -> where('erp_product_price.mp_name_id', '=', "$mpNameId")
                        -> where('erp_product_price.flag', '=', 0);
                    })
                    // 添加 spuid 信息
                    ->leftJoin('erp_spu_sku_link','erp_product_list.id','erp_spu_sku_link.sku_id')
                    ->where('erp_spu_sku_link.flag', 0)
                    ->leftJoin('erp_mp_name_spu_link','erp_spu_sku_link.spu_id','erp_mp_name_spu_link.spu_id')
                    ->where('erp_mp_name_spu_link.flag', 0)
                    ->where('erp_mp_name_spu_link.mp_name_id', $request->id)

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
                        'erp_mp_name_spu_link.id as mpSpuLinkId',
                        'erp_product_list.id as id',
                        'erp_product_list.product_no as product_no',
                        'erp_product_list.product_name as product_name',
                        'erp_product_list.weight as declared_weight',
                        'erp_product_list.declared_price',
                        'erp_product_list.model',
                        'erp_product_list.number',
                        'product_class.name as product_class_name',
                        'product_brand.name as product_brand_name',
                        'product_series.name as product_series_name',
                        'erp_product_price.id as product_price_id',
                        'erp_product_price.price_a',
                        'erp_product_price.price_b',
                        'erp_product_price.price_c',
                        'erp_product_price.price_d',
                        'erp_product_price.price_s',
                        'erp_product_price.market_price',
                        'erp_product_price.status as status',
                        'erp_product_price.has_stock as has_stock',
                        'erp_product_price.is_show as is_show',
                        'erp_product_price.mp_name_id as mpnameid',
                        'erp_product_price.min_unit as min_unit',
                        'erp_product_price.safe_stock',
                    ])
                    -> orderBy('id')
                    -> paginate(isset($request->per_page)?$request->per_page:20);

        foreach ($list as $k => $v) {
            $list[$k]->price_s = (float)$v->price_s;
            $list[$k]->price_a = (float)$v->price_a;
            $list[$k]->price_b = (float)$v->price_b;
            $list[$k]->price_c = (float)$v->price_c;
            $list[$k]->price_d = (float)$v->price_d;
            //取出关联的运费优惠
            $v->freights=collect();
            if (!empty($v->product_price_id)){
                $v->freights=ProductPrice::with(['freights'=>function($q){
                    $q->where('flag', 0);
                }])
                    -> find($v->product_price_id)->freights;
            }
        }
        return $list;
    }

    /**
     * 馆区内SKU价格维护
     */
    public function mainTainPrice($request) {
        $info = DB::table('erp_product_price')
                    -> where([
                        'product_id' => $request->id,
                        'mp_name_id' => $request->mpnameid,
                        'flag'       => 0
                    ])
                    -> first();
        if(empty($request->min_unit)){
            $request->min_unit=1;
        }
        if ($info) {
            $price_id=$info->id;
            // 修改
            DB::table('erp_product_price')
                -> where([
                    'id' => $info->id,
                ])
                -> update([
                    'price_s'       => $request -> price_s,
                    'price_a'       => $request -> price_a,
                    'price_b'       => $request -> price_b,
                    'price_c'       => $request -> price_c,
                    'price_d'       => $request -> price_d,
                    'min_unit'      => $request -> has('min_unit')?$request->min_unit:1,
                    'safe_stock'    => $request -> filled('safe_stock')?$request->safe_stock:0,
                    'market_price'  => $request -> has('market_price')?$request->market_price:0,
                    'updated_at'    => time()
                ]);
        } else {
            $price_id=DB::table('erp_product_price')
                -> insertGetId([
                    'product_id' => $request -> id,
                    'mp_name_id' => $request -> mpnameid,
                    'price_s'    => $request -> price_s,
                    'price_a'    => $request -> price_a,
                    'price_b'    => $request -> price_b,
                    'price_c'    => $request -> price_c,
                    'price_d'    => $request -> price_d,
                    'min_unit'   => $request -> has('min_unit')?$request->min_unit:1,
                    'safe_stock' => $request -> filled('safe_stock')?$request->safe_stock:0,
                    'market_price'=>$request -> has('market_price')?$request->market_price:0,
                    'created_at' => time(),
                    'updated_at' => time()
                ]);
        }

        //更新运费优惠关联
        $price_model=ProductPrice::find($price_id);
        $price_model->freights()->sync($request->freight_ids);

        $sku = DB::table('erp_product_list')
                    -> where('id', $request->id)
                    -> first();

        if (empty($sku->image)) {
            return [
                'code' => 200,
                'msg'  => '除价格外还有内容未维护'
            ];
        } else if((float)$sku->declared_price > 250 || (float)$sku->declared_price <= 0){
            return [
                'code' => 200,
                'msg'  => '除价格外还有内容未维护'
            ];
        } else if($sku->weight <= 0){
            return [
                'code' => 200,
                'msg'  => '除价格外还有内容未维护'
            ];
        } else {
            DB::table('erp_product_price')
                -> where([
                    'product_id' => $request->id,
                    'mp_name_id' => $request->mpnameid,
                    'flag'       => 0
                ])
                -> update([
                    'status' => 1
                ]);
        }
        return [
            'code' => 200,
            'msg'  => '成功'
        ];
    }


    /**
     * 新校验sku上架check
     */
    // public function newGetStock( $product_id, $mpnameid )
    // {
    //     $stock = DB::table('erp_product_list')
    //                 -> leftJoin('erp_product_price','erp_product_list.id','erp_product_price.product_id')
    //                 -> leftJoin('erp_product_class as product_class','erp_product_list.class_id','product_class.id')
    //                 -> leftJoin('erp_product_class as product_brand','erp_product_list.brand_id','product_brand.id')
    //                 -> leftJoin('erp_product_class as product_series','erp_product_list.series_id','product_series.id')
    //                 -> where('erp_product_list.flag',0)
    //                 -> where('erp_product_list.id', $product_id)
    //                 -> where('erp_product_price.mp_name_id', $mpnameid)
    //                 -> select([
    //                     'erp_product_list.product_name',
    //                     'erp_product_list.declared_price',
    //                     'erp_product_list.model',
    //                     'erp_product_list.number',
    //                     'erp_product_list.product_no',
    //                     'erp_product_list.image',
    //                     'erp_product_list.declared_price',
    //                     'erp_product_list.weight',
    //                     'erp_product_price.price_a',
    //                     'erp_product_price.price_b',
    //                     'erp_product_price.price_c',
    //                     'erp_product_price.price_d',
    //                     'erp_product_price.price_s',
    //                     'erp_product_price.status as status',
    //                     'product_class.name as product_class_name',
    //                     'product_brand.name as product_brand_name',
    //                     'product_series.name as product_series_name',
    //                 ])
    //                 -> first();
    //     return $stock;
    // }

    /**
     * 馆区上架SKU
     */
    // public function putOnProcess($request) {
    //     // 错误数据
    //     $errors = [];
    //     $productIds = $request->skuIds;
    //     $can_buy_num = 0;
    //     // 上架校验  必须维护了所有价格和各种图片标题等等
    //     foreach ($productIds as $key => $productId) {
    //         $stock = $this->newGetStock($productId,$request->mpnameid);

    //         // $can_buy_num_list = DB::table('erp_stock')
    //         //                         -> where ('erp_stock_product_id', $productId)
    //         //                         -> pluck('can_buy_num');
    //         // foreach ($can_buy_num_list as $k => $v) {
    //         //     $can_buy_num = $can_buy_num + $v;
    //         // }
    //         // if ($can_buy_num <= 0) {
    //         //     $errors[] = $stock->product_no."商品可售数量小于0";
    //         //     continue;
    //         // }
    //         // 商品是否存在
    //         if(empty($stock)){
    //             $errors[] = '上架的商品不存在';
    //             continue;
    //         }
    //         // 价格维护
    //         if (empty($stock->price_s) || empty($stock->price_a) || empty($stock->price_b) || empty($stock->price_c) || empty($stock->price_d)) {
    //             $errors[] = $stock->product_no."-价格未维护";
    //             continue;
    //         }
    //         // 图片相关信息是否维护，如果没有pass
    //         if (empty($stock->image)) {
    //             $errors[] = $stock->product_no."-图片未维护";
    //             continue;
    //         }
    //         // 申报价格必须小于250
    //         if ((float)$stock->declared_price > 250 || (float)$stock->declared_price <= 0) {
    //             $errors[] = $stock->product_no."-申报价格大于250或等于0";
    //             continue;
    //         }
    //         // 申报重量必须大于0
    //         if ($stock->weight <= 0) {
    //             $errors[] = $stock->product_no."-重量小于或等于0";
    //             continue;
    //         }
    //         // 开始上架
    //         DB::beginTransaction();
    //         try {
    //             $check = DB::table('erp_stock')
    //                 -> where('erp_stock.product_id', $productId)
    //                 -> where('can_buy_num', '>', 0)
    //                 -> count();

    //             if ($check > 0) {
    //                 DB::table('erp_product_price')
    //                     ->where('erp_product_price.product_id', $productId)
    //                     ->where('erp_product_price.mp_name_id', $request->mpnameid)
    //                     ->where('erp_product_price.flag', 0)
    //                     -> update([
    //                         'erp_product_price.status' => 1
    //                     ]);
    //             }
    //             // 查询所有关联的SPU，统一修改状态为已上架
    //             // $spuIds = DB::table('erp_spu_sku_link')
    //             //               -> where('erp_spu_sku_link.sku_id', $productId)
    //             //               -> where('erp_spu_sku_link.flag', 0)
    //             //               -> pluck('spu_id');
    //             // DB::table('erp_mp_name_spu_link')
    //             //     -> where('erp_mp_name_spu_link.flag', 0)
    //             //     -> whereIn('erp_mp_name_spu_link.spu_id', $spuIds)
    //             //     -> update([
    //             //         'erp_mp_name_spu_link.status' => 1
    //             //     ]);
    //             DB::commit();
    //         } catch (\Exception $e) {
    //             DB::rollBack();
    //             $errors[] = '数据库插入错误';
    //         }
    //     }

    //     return $errors;
    // }

    /**
     * 馆区下架SKU
     */
    // public function putOffProcess($request) {
    //     $errors = [];
    //     // 什么时候是不能下架的？下订单的时候要判断当前的状态
    //     $productIds = $request->skuIds;
    //     $can_buy_num = 0;
    //     foreach ($productIds as $key => $productId) {
    //         $stock = $this->newGetStock($productId,$request->mpnameid);
    //         // 商品是否存在
    //         if(empty($stock)){
    //             $errors[] = '下架的商品不存在';
    //             continue;
    //         }
    //         // 未上架的商品是不能下架的
    //         if($stock->status === 0){
    //             $errors[] = $stock->product_no.'未上架';
    //             continue;
    //         }
    //         // 开始下架
    //         DB::beginTransaction();
    //         try {
    //             DB::table('erp_product_price')
    //             -> where('erp_product_price.product_id', $productId)
    //             -> where('erp_product_price.mp_name_id', $request->mpnameid)
    //             -> where('erp_product_price.flag', 0)
    //             -> update([
    //                 'status' => 0
    //             ]);
    //             // sku 下架
    //             //     如果下架成功（包括数量卖完了调用下架），则去更新所有相关的spu状态
    //             //         拿到这些spuIDs,然后去搜索，如果旗下没有关联，则直接下架，如果至少有一个sku上架，就可以继续售卖

    //             // 查找符合馆区的SPU

    //             $spuIds = DB::table('erp_spu_sku_link')
    //                         -> where('erp_spu_sku_link.sku_id', $productId)
    //                         -> where('erp_spu_sku_link.flag', 0)
    //                         -> pluck('spu_id');


    //             $spuId = DB::table('erp_mp_name_spu_link')
    //                         -> whereIn('erp_mp_name_spu_link.spu_id', $spuIds)
    //                         -> where('erp_mp_name_spu_link.mp_name_id', $request->mpnameid)
    //                         -> where('erp_mp_name_spu_link.flag', 0)
    //                         -> get();

    //             foreach ($spuId as $key => $link) {
    //                 // 找到旗下除了$stockId以外还有没有正常的单品
    //                 $otherPutOnNum = DB::table('erp_spu_sku_link')
    //                 -> leftJoin('erp_stock','erp_stock.product_id','erp_spu_sku_link.sku_id')
    //                 -> leftJoin('erp_product_price', 'erp_product_price.product_id', 'erp_spu_sku_link.spu_id')
    //                 -> leftJoin('erp_mp_name_spu_link', 'erp_spu_sku_link.spu_id', 'erp_mp_name_spu_link.spu_id')
    //                 -> where('erp_product_price.mp_name_id', $request->mpnameid)
    //                 -> where('erp_mp_name_spu_link.mp_name_id', $request->mpnameid)
    //                 -> where('erp_spu_sku_link.spu_id', $link->spu_id)
    //                 -> where('erp_spu_sku_link.sku_id','!=', $productId)
    //                 -> where('erp_spu_sku_link.flag','=', 0)
    //                 -> where('erp_stock.can_buy_num', '>',0)
    //                 -> count();
    //                 // 除此之外再无其他上架状态的SKU了
    //                 if (empty($otherPutOnNum)) {
    //                     // SPU修改为不显示
    //                     DB::table('erp_mp_name_spu_link')
    //                     -> where('spu_id', $link->spu_id)
    //                     -> update([
    //                         'status' => 0
    //                     ]);
    //                 }
    //             }
    //             DB::commit();
    //         } catch (\Exception $e) {
    //             DB::rollBack();
    //             $errors[] = '数据库插入错误';
    //         }

    //     }

    //     return $errors;
    // }

    /**
     * 馆区内SKU显示
     */
    public function skuShowPutOn($request) {
     foreach ($request->id as $k => $v) {
        $info = DB::table('erp_product_price')
                    -> where([
                        'product_id' => $v,
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
                    'is_show' => 1
                ]);
            $this->skuRepository->autoPutOnOrOff($request->id);
        } else {
            DB::table('erp_product_price')
                -> insert([
                    'product_id' => $v,
                    'mp_name_id' => $request -> mpnameid,
                    'created_at' => time(),
                    'updated_at' => time(),
                    'is_show'    => 1
                ]);
            $this->skuRepository->autoPutOnOrOff($request->id);
        }
     }
        return [
            'code' => 200,
            'msg'  => '修改成功'
        ];
    }

    /**
     * 馆区内SKU关闭显示
     */
    public function skuShowPutOff($request) {
        foreach ($request->id as $k => $v) {
        DB::table('erp_product_price')
            -> where([
                'product_id' => $v,
                'mp_name_id' => $request->mpnameid,
                'flag'       => 0
            ])
            -> update([
                'is_show' => 0
            ]);
        $this->skuRepository->autoPutOnOrOff($request->id);
        }
        return [
            'code' => 200,
            'msg'  => '修改成功'
        ];
    }

}
