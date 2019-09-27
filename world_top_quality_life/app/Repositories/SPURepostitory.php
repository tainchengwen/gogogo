<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use App\Repositories\SKURepository;
use App\Repositories\CategoryRepository;
use App\Repositories\UnionRepository;
use EasyWeChat\Factory;
use App\Repositories\SpecialRepository;

class SPURepository extends BaseRepository
{
    public function __construct(
        SKURepository $skuRepository,
        CategoryRepository $categoryRepository,
        UnionRepository $unionRepository,
        SpecialRepository $specialRepository
    ){
        $this->skuRepository      = $skuRepository;
        $this->categoryRepository = $categoryRepository;
        $this->union = $unionRepository;
        $this->special=$specialRepository;
    }

    /**
     * 通过Id获得SPU
     */
    public function getSPUById($id)
    {
        $spu = DB::table('erp_mp_name_spu_link')
            -> leftJoin('erp_spu_list', 'erp_mp_name_spu_link.spu_id', 'erp_spu_list.id')
            -> leftJoin('erp_mp_name', 'erp_mp_name_spu_link.mp_name_id', 'erp_mp_name.id')
            -> where('erp_mp_name_spu_link.id', $id)
            -> where('erp_mp_name_spu_link.flag', 0)
            -> where('erp_spu_list.flag', 0)
            -> select([
                'erp_mp_name_spu_link.id',
                'erp_mp_name_spu_link.spu_id',
                'erp_mp_name_spu_link.mp_name_id',
                'erp_mp_name_spu_link.union_flag',
                'erp_mp_name.icon_image',
                'erp_mp_name.mp_name',
                'erp_spu_list.name',
                'erp_spu_list.type as spu_type',
                'erp_spu_list.sub_name',
                'erp_spu_list.details'
            ])
            -> first();

        //套餐
        /*if ($spu->spu_type==1 && $spu->union_flag==1){
            unset($spu);
        }*/

        if ($spu) {
            $spu->icon_image = empty($spu->icon_image) ? '' : getImageUrl($spu->icon_image);
        }

        return $spu;
    }

    public function checkSkuSpuStatus($spuId, $skuId)
    {
        // 检测关联关系

        // 检测可售数量
        return [
            'canAdd' => true,
            ''
        ];
    }

    /**
     * 组装完整spu
     */
    public function getAssembledSPUByRequestAndId($request, $id)
    {
        return [
            'spu'   => $this->getSPUById($id),
            'skus'  => $this->skuRepository->searchBySPUId($request, $id)
        ];
    }

    /**
     * spu列表相关方法
     *
     * 分页搜索
     */
    public function searchPaginateNew($request, $pageSize = 5)
    {
        //  馆区
        if($request->warehouse_name){
            //仓库
            $mpName = DB::table('erp_mp_name')
                -> where('mp_name', $request->warehouse_name)
                -> first();
            $mpNameId = $mpName->id;
        } else {
            $mpNameId = 0;
        }
        if($request->warehouse_id){
            $mpNameId = $request->warehouse_id;
        }

        // 先拿所有的正常的skus
        $erp_mp_name_spu_link_ids = DB::table('erp_product_price as sku')
            -> leftJoin('erp_spu_sku_link', 'sku.product_id', 'erp_spu_sku_link.sku_id')
            -> leftJoin('erp_spu_list', 'erp_spu_sku_link.spu_id', 'erp_spu_list.id')
            -> leftJoin('erp_mp_name_spu_link', function($q) {
                $q->on('erp_mp_name_spu_link.spu_id', '=', 'erp_spu_sku_link.spu_id')
                    ->on('erp_mp_name_spu_link.mp_name_id', '=', 'sku.mp_name_id');
            })
            -> where('sku.flag', 0)
            -> where('sku.status', 1)
            -> where('sku.is_show', 1)
            -> where('sku.has_stock', '>' , 0)
            -> where('erp_mp_name_spu_link.flag', 0)
            -> where(function($query) {
                $query->where([
                    ['erp_spu_list.type', '=', 1],
                    ['erp_mp_name_spu_link.union_flag', '=', 0],
                    ['sku.union_status', '=', 1],
                    ['sku.union_num', '>', 0]
                ])->orWhere('erp_spu_list.type', '=', 0);
            })
            -> where(function($query) use ($request, $mpNameId){
                if($request->warehouse_id){
                    //馆区
                    $query -> where('sku.mp_name_id', $mpNameId);
                }
                if($request->business_id){
                    $query -> where('erp_spu_list.business_id', $request->business_id)
                    ->orWhere('erp_spu_list.is_public', 1);
                }
            })
            -> select([
                'erp_mp_name_spu_link.id',
                'erp_mp_name_spu_link.mp_name_id',
                'erp_mp_name_spu_link.spu_id'
            ])
            -> groupBy('mp_name_id', 'spu_id', 'id')
            -> pluck('id');

        //  分类
        if($request->class_id){
            $ids = $this->categoryRepository->getCategoryIdsAndChild($request->class_id);
        } else {
            $ids = [];
        }

        //  标签
        $tag_name = $request->tag_name;

        $whereIn = [];
        if ($tag_name) {
            //如果 tag_name 传过来 tagname 则查关联表
            $tag = DB::table('erp_tags')
                ->where('name', $request->tag_name)
                ->first();

            if($tag){
                $spuIds = DB::table('erp_spu_tag_link')
                    ->where('tag_id', $tag->id)
                    ->select([
                        'spu_id'
                    ])
                    ->get();
                foreach ($spuIds as $key => $spuId) {
                    $whereIn[] = $spuId->spu_id;
                }
            }
        }

        $spus = DB::table('erp_mp_name_spu_link as link')
            -> leftJoin('erp_spu_list', 'link.spu_id', 'erp_spu_list.id')
            -> leftJoin('erp_mp_name', 'link.mp_name_id', 'erp_mp_name.id')
            -> where('erp_spu_list.flag', 0)
            -> whereIn('link.id', $erp_mp_name_spu_link_ids)
            -> where(function($query)use($request, $ids){
                //  分类
                if (!empty($ids)) {
                    $query -> whereIn('erp_spu_list.class_id', $ids);
                }
                //  关键词
                if(!empty($request->keyword)){
                    $query -> where('erp_spu_list.name','like','%'.trim($request->keyword).'%');
                }
            })
            //  标签
            -> where(function($query)use($whereIn, $tag_name){
                if($tag_name){
                    $query -> whereIn('erp_spu_list.id', $whereIn);
                }
            })
            -> select([
                'link.id',
                'link.mp_name_id',
                'erp_mp_name.icon_image',
                'erp_mp_name.mp_name',
                'erp_spu_list.id as spu_id',
                'erp_spu_list.name',
                'erp_spu_list.sub_name',
                'erp_spu_list.img as spu_image',
                'erp_spu_list.type as spu_type',
            ])
            -> orderBy('link.sort_index','desc')
            -> orderBy('link.weight_index','desc')
            -> orderBy('erp_spu_list.id','desc')
            -> paginate($pageSize);

        return $this->assembleFirstSKUNew($request, $spus);
    }

    public function assembleFirstSKUNew($request, $spus)
    {
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

        // 查询第一个(查询什么情况下的第一个)
        foreach ($spus as $key => $spu) {
            // spu的馆区图标
            $spus[$key]->icon_image = empty($spu->icon_image) ? '' : getImageUrl($spu->icon_image);

            $query = DB::table('erp_spu_sku_link')
                -> leftJoin('erp_product_list','erp_spu_sku_link.sku_id','erp_product_list.id')
                -> leftJoin('erp_product_price', function($q) use ($spu) {
                    $q->on('erp_product_price.product_id', '=', 'erp_spu_sku_link.sku_id')
                        ->where('erp_product_price.mp_name_id', '=', $spu->mp_name_id);
                })
                -> where('erp_spu_sku_link.spu_id', $spu->spu_id)
                -> where('erp_spu_sku_link.flag', 0)
                -> where('erp_product_price.flag', 0)
                -> where('erp_product_price.status', 1)
                -> where('erp_product_price.is_show', 1)
                -> where('erp_product_price.has_stock', '>', 0)
                -> select([
                    'erp_product_price.id',
                    'erp_product_list.product_no',
                    'erp_product_list.product_name',
                    'erp_product_list.image',
                    'erp_product_price.has_stock as can_buy_num',
                    'erp_product_price.union_num',
                    'erp_product_price.union_status',
                    'erp_product_price.price_' . $current . ' as currentPrice',
                    'erp_product_price.price_' . $next . ' as nextPrice',
                    'erp_product_price.price_d as originPrice',
                ])
                -> orderBy('erp_spu_sku_link.sort_index','desc')
                -> orderBy('erp_spu_sku_link.id','desc');

            //如果是套餐spu,重新组装下面的价格等信息
            if (isset($spu->spu_type) && $spu->spu_type==1){
                $skus=$query->where('erp_product_price.union_status',1)->get();

                if ($skus->isEmpty()){
                    unset($spus[$key]);
                    continue;
                }
                $sku=$this->union->joinUnionInfo($skus);
            }else{
                $sku=$query->first();
            }

            if (!$sku) {
                unset($spus[$key]);
                continue;
            }

            // 价格保留两位小数
            $sku->currentPrice =  $this->math_add($sku->currentPrice,0);
            if ($hasNextPrice) {
                $sku->nextPrice =  $this->math_add($sku->nextPrice,0);
            }
            $sku->originPrice =  $this->math_add($sku->originPrice,0);
            $sku->hasNextPrice = $hasNextPrice;
            $sku->nextVIPLevel = $nextVIPLevel;
            $sku->headImage = getImageUrl($sku->image);
            if (empty($spu->spu_image)) {
                $spus[$key]->spu_image = $sku->headImage;
            }else{
                $spus[$key]->spu_image=getImageUrl($spu->spu_image);
            }
            unset($sku->image);
            $spus[$key]->sku = $sku;
        }
        return $spus;
    }

    public function searchByKeyWord($request,$pageSize = 5)
    {
        //  馆区
        if($request->warehouse_name){
            //仓库
            $mpName = DB::table('erp_mp_name')
                -> where('mp_name', $request->warehouse_name)
                -> first();
            $mpNameId = $mpName->id;
        } else {
            $mpNameId = 0;
        }

        //  分类
        if($request->class_id){
            $ids = $this->categoryRepository->getCategoryIdsAndChild($request->class_id);
        } else {
            $ids = [];
        }

        //  标签
        $tag_name = $request->tag_name;

        $whereIn = [];
        if ($tag_name) {
            //如果 tag_name 传过来 tagname 则查关联表
            $tag = DB::table('erp_tags')
                ->where('name', $request->tag_name)
                ->first();

            if($tag){
                $spuIds = DB::table('erp_spu_tag_link')
                    ->where('tag_id', $tag->id)
                    ->select([
                        'spu_id'
                    ])
                    ->get();
                foreach ($spuIds as $key => $spuId) {
                    $whereIn[] = $spuId->spu_id;
                }
            }
        }

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

        $skus=DB::table('erp_product_price')
            ->leftJoin('erp_spu_sku_link','erp_product_price.product_id','erp_spu_sku_link.sku_id')
            ->leftJoin('erp_spu_list','erp_spu_sku_link.spu_id','erp_spu_list.id')
            ->leftJoin('erp_product_list','erp_spu_sku_link.sku_id','erp_product_list.id')
            -> leftJoin('erp_mp_name_spu_link', function($q) {
                $q->on('erp_mp_name_spu_link.spu_id', '=', 'erp_spu_sku_link.spu_id')
                    ->on('erp_mp_name_spu_link.mp_name_id', '=', 'erp_product_price.mp_name_id');
            })
            ->leftJoin('erp_mp_name','erp_mp_name_spu_link.mp_name_id','erp_mp_name.id')
            -> where('erp_spu_list.flag', 0)
            -> where('erp_product_price.flag', 0)
            -> where('erp_product_price.status', 1)
            -> where('erp_product_price.is_show', 1)
            -> where('erp_product_price.has_stock', '>', 0)
            -> where('erp_mp_name_spu_link.flag', 0)
            -> where('erp_spu_sku_link.flag', 0)
            -> where(function($query) {
                $query->where([
                    ['erp_spu_list.type', '=', 1],
                    ['erp_mp_name_spu_link.union_flag', '=', 0],
                    ['erp_product_price.union_status', '=', 1],
                    ['erp_product_price.union_num', '>', 0]
                ])->orWhere('erp_spu_list.type', '=', 0);
            })
            ->where(function($query)use($request){
                if($request->business_id){
                    $query->where('erp_spu_list.business_id',$request->business_id)
                        ->orWhere('erp_spu_list.is_public',1);
                }
            })
            -> where(function($query) use ($request, $mpNameId){
                if($request->warehouse_name){
                    //馆区
                    $query -> where('erp_product_price.mp_name_id', $mpNameId);
                }
            })
            -> where(function($query)use($ids){
                //  分类
                if (!empty($ids)) {
                    $query -> whereIn('erp_spu_list.class_id', $ids);
                }
            })
            //  标签
            -> where(function($query)use($tag_name,$whereIn){
                if($tag_name){
                    $query -> whereIn('erp_spu_list.id', $whereIn);
                }
            })
            ->where(function($query)use($request){
                $query -> where('erp_product_list.product_name','like','%'.$request->keyword.'%')
                    ->orWhere('erp_spu_list.sub_name','like','%'.$request->keyword.'%')
                    ->orWhere('erp_spu_list.name','like','%'.$request->keyword.'%');
            })
            -> select([
                'erp_spu_list.id as spu_id',
                'erp_product_list.id as sku_id',
                'erp_mp_name_spu_link.mp_name_id',
                'erp_mp_name_spu_link.id as mp_name_spu_link_id',
                'erp_mp_name.icon_image',
                'erp_mp_name.mp_name',
                'erp_spu_list.name as spu_name',
                'erp_spu_list.sub_name as spu_sub_name',
                'erp_spu_list.img as spu_image',
                'erp_spu_list.type as spu_type',
                'erp_product_price.id as product_price_id',//
                'erp_product_list.product_no',
                'erp_product_list.product_name',
                'erp_product_list.image',
                'erp_product_price.has_stock as can_buy_num',
                'erp_product_price.price_' . $current . ' as currentPrice',
                'erp_product_price.price_' . $next . ' as nextPrice',
            ])
            -> orderBy('erp_product_list.id','desc')->distinct()
            -> paginate($pageSize);

        foreach ($skus as $key => $sku) {
            //处理套餐
            if (isset($sku->spu_type) && $sku->spu_type==1){
                $check_result=$this->union->checkUnionStatus($sku);
                if (!$check_result){
                    unset($skus[$key]);
                    continue;
                }

                $result=DB::table('erp_spu_sku_link')
                    -> leftJoin('erp_product_list','erp_spu_sku_link.sku_id','erp_product_list.id')
                    -> leftJoin('erp_mp_name_spu_link','erp_spu_sku_link.spu_id','erp_mp_name_spu_link.spu_id')
                    -> leftJoin('erp_product_price', function($q) use ($sku) {
                        $q->on('erp_product_price.product_id', '=', 'erp_spu_sku_link.sku_id')
                            ->on('erp_product_price.mp_name_id', '=', 'erp_mp_name_spu_link.mp_name_id');
                    })
                    -> where('erp_mp_name_spu_link.union_flag',0)
                    -> where('erp_spu_sku_link.flag',0)
                    -> where('erp_mp_name_spu_link.flag',0)
                    -> where('erp_mp_name_spu_link.id',$sku->mp_name_spu_link_id)
                    -> where('erp_product_price.flag', 0)
                    -> where('erp_product_price.status', 1)
                    -> where('erp_product_price.is_show', 1)
                    -> where('erp_product_price.has_stock', '>', 0)
                    -> where('erp_product_price.union_num', '>', 0)
                    -> where('erp_product_price.union_status',1)
                    -> select([
                        'erp_product_price.id',
                        'erp_product_list.id as spu_id',
                        'erp_mp_name_spu_link.id as mp_name_id',
                        'erp_product_list.product_no',
                        'erp_product_list.product_name',
                        'erp_product_list.image',
                        'erp_product_price.has_stock as can_buy_num',
                        'erp_product_price.union_num',
                        'erp_product_price.union_status',
                        'erp_product_price.price_' . $current . ' as currentPrice',
                        'erp_product_price.price_' . $next . ' as nextPrice',
                        'erp_product_price.price_d as originPrice',
                    ])
                    -> orderBy('erp_spu_sku_link.sort_index','desc')
                    -> orderBy('erp_spu_sku_link.id','desc')->get();

                if ($result->isEmpty()){
                    unset($skus[$key]);
                    continue;
                }
                $join_sku=$this->union->joinUnionInfo($result);

                if (!$join_sku) {
                    unset($skus[$key]);
                    continue;
                }
                $sku->spu_sub_name=$sku->spu_name;
                $sku->sku_img=getImageUrl($sku->image);
                $sku->can_buy_num=$join_sku->can_buy_num;
                $sku->currentPrice=$join_sku->currentPrice;
                $sku->nextPrice=$join_sku->nextPrice;
            }
            // spu的馆区图标
            $sku->icon_image = empty($sku->icon_image) ? '' : getImageUrl($sku->icon_image);
            $sku->spu_image = empty($sku->spu_image) ? '' : getImageUrl($sku->spu_image);

            // 价格保留两位小数
            $sku->currentPrice =  $this->math_add($sku->currentPrice,0);
            if ($hasNextPrice) {
                $sku->nextPrice =  $this->math_add($sku->nextPrice,0);
            }
            $sku->hasNextPrice = $hasNextPrice;
            $sku->nextVIPLevel = $nextVIPLevel;
            $sku->sku_img = getImageUrl($sku->image);
            unset($sku->image);
        }
        return $skus;
    }

    /**
     * spu列表相关方法
     *
     * 分页搜索
     */
    public function searchPaginate($request, $pageSize = 5)
    {
        // 自营默认全部代理
        $whereIn = [];
        $tag_name = $request->tag_name;

        if ($tag_name) {
            //如果 tag_name 传过来 tagname 则查关联表
            $tag = DB::table('erp_tags')
                ->where('name', $request->tag_name)
                ->first();

            if($tag){
                $spuIds = DB::table('erp_spu_tag_link')
                    ->where('tag_id', $tag->id)
                    ->select([
                        'spu_id'
                    ])
                    ->get();
                foreach ($spuIds as $key => $spuId) {
                    $whereIn[] = $spuId->spu_id;
                }
            }
        }

        $warehousesIds = [];

        if ($request->warehouse_name) {
            //如果 tag_name 传过来 tagname 则查关联表
            $warehouses = DB::table('erp_warehouse')
                ->where('mp_name', $request->warehouse_name)
                ->where('business_id', $request->business_id)
                ->where('flag', 0)
                ->where('mp_flag', 1)
                ->get();

            foreach ($warehouses as $key => $value) {
                $warehousesIds[] = $value->id;
            }
        }

        if($request->class_id){
            $ids = $this->categoryRepository->getCategoryIdsAndChild($request->class_id);
            $where[] = [
                'class_id','=',$request->class_id
            ];
        } else {
            $ids = [];
        }
        // 还要分页
        $spus = DB::table('erp_spu_list as spu')
            -> leftJoin('erp_warehouse','spu.warehouse_id','erp_warehouse.id')
            -> where('spu.status',1)
            -> where(function($query)use($request, $ids, $warehousesIds){
                if (!empty($ids)) {
                    $query -> whereIn('spu.class_id', $ids);
                }
                if($request->business_id){
                    //事业部
                    $query -> where('erp_warehouse.business_id','=',$request->business_id);
                }
                if($request->warehouse_id){
                    //仓库
                    $query -> where('spu.warehouse_id','=',$request->warehouse_id);
                }
                if($request->warehouse_name){
                    //仓库
                    $query -> whereIn('spu.warehouse_id',$warehousesIds);
                }
                if(!empty($request->keyword)){
                    $query -> where('spu.name','like','%'.trim($request->keyword).'%');
                }
            })
            -> where(function($query)use($whereIn, $tag_name){
                if($tag_name){
                    $query -> whereIn('spu.id',$whereIn);
                }
            })
            -> orderBy('spu.id','desc')
            -> select([
                'spu.id',
                'spu.name',
                'spu.sub_name',
                'spu.details',
                'spu.status',
                'spu.class_id',
                'spu.warehouse_id'
            ])
            -> paginate($pageSize);

        return $this->assembleFirstSKU($request, $spus);
    }


    public function home($request,$deepOne)
    {
        $recommended = [];
        // 先拿所有的正常的skus
        $list = DB::table('erp_product_price as sku')
            -> leftJoin('erp_spu_sku_link', 'sku.product_id', 'erp_spu_sku_link.sku_id')
            -> leftJoin('erp_spu_list','erp_spu_sku_link.spu_id','erp_spu_list.id')
            -> leftJoin('erp_mp_name_spu_link', function($q) {
                $q->on('erp_mp_name_spu_link.spu_id', '=', 'erp_spu_sku_link.spu_id')
                    ->on('erp_mp_name_spu_link.mp_name_id', '=', 'sku.mp_name_id');
            })
            ->where(function($query)use($request){
                if($request->business_id){
                    $query->where('erp_spu_list.business_id',$request->business_id)
                           ->orWhere('erp_spu_list.is_public',1);
                }
            })
            -> where('sku.flag', 0)
            -> where('sku.status', 1)
            -> where('sku.is_show', 1)
            -> where('sku.has_stock', '>' , 0)
            -> where('erp_mp_name_spu_link.flag', 0)
            -> where(function($query) {
                $query->where([
                    ['erp_spu_list.type', '=', 1],
                    ['erp_mp_name_spu_link.union_flag', '=', 0],
                    ['sku.union_status', '=', 1],
                    ['sku.union_num', '>', 0]
                ])->orWhere('erp_spu_list.type', '=', 0);
            })
            -> select([
                'erp_mp_name_spu_link.id',
                'erp_mp_name_spu_link.mp_name_id',
                'erp_mp_name_spu_link.spu_id',
                'erp_spu_list.type as spu_type',
            ])
            -> groupBy('mp_name_id', 'spu_id', 'id','spu_type')->get();

        //移除不正常的套餐
        foreach ($list as $k=>$v){
            if ($v->spu_type==1){
                $result=$this->union->checkUnionStatus($v);
                if (!$result){
                    unset($list[$k]);
                }
            }
        }

        $list=$list-> pluck('id');
        foreach ($deepOne as $key => $value) {
            $request->class_id = $value->id;
            $recommended[] = [
                'id'           => $value->id,
                'categoryName' => $value->name,
                'spus'         => $this->tmp_search($request, $list, 10)
            ];
        }

        return $recommended;
    }

    private function tmp_search($request, $erp_mp_name_spu_link_ids ,$limit = 5)
    {
        //  分类
        if($request->class_id){
            $ids = $this->categoryRepository->getCategoryIdsAndChild($request->class_id);
        } else {
            $ids = [];
        }

        $spus = DB::table('erp_mp_name_spu_link as link')
            -> leftJoin('erp_spu_list', 'link.spu_id', 'erp_spu_list.id')
            -> where('erp_spu_list.flag', 0)
            -> leftJoin('erp_mp_name', 'link.mp_name_id', 'erp_mp_name.id')
            -> whereIn('link.id', $erp_mp_name_spu_link_ids)
            -> where(function($query)use($request, $ids){
                //  分类
                if (!empty($ids)) {
                    $query -> whereIn('erp_spu_list.class_id', $ids);
                }
                //  关键词
                if(!empty($request->keyword)){
                    $query -> where('erp_spu_list.name','like','%'.trim($request->keyword).'%');
                }
            })
            -> select([
                'link.id',
                'link.mp_name_id',
                'erp_mp_name.mp_name',
                'erp_mp_name.icon_image',
                'erp_spu_list.id as spu_id',
                'erp_spu_list.name',
                'erp_spu_list.sub_name',
                'erp_spu_list.img as spu_image',
                'erp_spu_list.type as spu_type',
            ])
            -> orderBy('link.sort_index','desc')
            -> orderBy('link.weight_index','desc')
            -> orderBy('erp_spu_list.id','desc')
            -> limit($limit)
            -> get();

        return $this->assembleFirstSKUNew($request, $spus);
    }

    /**
     * spu列表相关方法
     *
     * 指定查询哪几条
     */
    public function search($request, $limit = 5)
    {
        // 先拿所有的正常的skus
        $list = DB::table('erp_product_price as sku')
            -> leftJoin('erp_spu_sku_link', 'sku.product_id', 'erp_spu_sku_link.sku_id')
            -> leftJoin('erp_mp_name_spu_link', function($q) {
                $q->on('erp_mp_name_spu_link.spu_id', '=', 'erp_spu_sku_link.spu_id')
                    ->on('erp_mp_name_spu_link.mp_name_id', '=', 'sku.mp_name_id');
            })
            -> where('sku.flag', 0)
            -> where('sku.status', 1)
            -> where('sku.is_show', 1)
            -> where('sku.has_stock', '>' , 0)
            -> where('erp_mp_name_spu_link.flag', 0)

            -> select([
                'erp_mp_name_spu_link.id',
                'erp_mp_name_spu_link.mp_name_id',
                'erp_mp_name_spu_link.spu_id'
            ])
            -> groupBy('mp_name_id', 'spu_id', 'id')
            -> get();

        $erp_mp_name_spu_link_ids = [];
        foreach ($list as $key => $value) {
            $erp_mp_name_spu_link_ids[] = $value->id;
        }

        //  分类
        if($request->class_id){
            $ids = $this->categoryRepository->getCategoryIdsAndChild($request->class_id);
        } else {
            $ids = [];
        }

        $spus = DB::table('erp_mp_name_spu_link as link')
            -> leftJoin('erp_spu_list', 'link.spu_id', 'erp_spu_list.id')
            -> where('erp_spu_list.flag', 0)
            -> whereIn('link.id', $erp_mp_name_spu_link_ids)
            -> where(function($query)use($request, $ids){
                //  分类
                if (!empty($ids)) {
                    $query -> whereIn('erp_spu_list.class_id', $ids);
                }
                //  关键词
                if(!empty($request->keyword)){
                    $query -> where('erp_spu_list.name','like','%'.trim($request->keyword).'%');
                }
            })
            -> select([
                'link.id',
                'link.mp_name_id',
                'erp_spu_list.id as spu_id',
                'erp_spu_list.name',
                'erp_spu_list.sub_name'
            ])
            -> orderBy('erp_spu_list.id','desc')
            -> limit($limit)
            -> get();

        return $this->assembleFirstSKUNew($request, $spus);
    }

    /**
     * spu列表相关方法
     *
     * 查询构造器
     */
    private function queryBuilder($request)
    {
        $where = [];
        if($request->warehouse_id){
            //仓库
            $where[] = [
                'warehouse_id','=',$request->warehouse_id
            ];
        }
        if(!empty($request->keyword)){
            $where[] = [
                'name','like','%'.trim($request->keyword).'%'
            ];
        }
        return $where;
    }

    /**
     * spu列表相关方法
     *
     * 组装第一个sku
     */
    private function assembleFirstSKU($request, $spus)
    {
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

        // 查询第一个(查询什么情况下的第一个)
        foreach ($spus as $key => $spu) {
            // 根据spu去找第一个sku
            $sku = DB::table('erp_spu_sku_link')
                -> leftJoin('erp_product_list','erp_spu_sku_link.sku_id','erp_product_list.id')
                -> leftJoin('erp_stock','erp_spu_sku_link.sku_id','erp_stock.product_id')
                -> leftJoin('erp_product_price','erp_spu_sku_link.sku_id','erp_product_price.product_id')
                -> where('erp_spu_sku_link.spu_id', $spu->id)
                -> where('erp_spu_sku_link.flag', 0)
                -> where('erp_spu_sku_link.status', 1)
                -> whereNotNull('erp_stock.can_buy_num')
                -> where('erp_stock.can_buy_num', '>', 0)
                -> where('erp_stock.warehouse_id', $spu->warehouse_id)
                -> where('erp_stock.business_id', $request->business_id)
                -> select([
                    'erp_product_list.id',
                    'erp_stock.can_buy_num',
                    'erp_product_list.product_no',
                    'erp_product_list.product_name',
                    'erp_product_list.image',
                    'erp_product_price.price_' . $current . ' as currentPrice',
                    'erp_product_price.price_' . $next . ' as nextPrice',
                ])
                -> orderBy('erp_spu_sku_link.sort_index','desc')
                -> orderBy('erp_spu_sku_link.id','desc')
                -> get();

            if ($sku->isEmpty()) {
                unset($spus[$key]);
                continue;
            }
            // 要做去重
            // 去重
            // 把canbuynum加在一起
            $tmp = '';
            foreach ($sku as $k => $v) {
                if(empty($tmp)) {
                    $tmp = $v;
                } else {
                    $tmp->can_buy_num = $tmp->can_buy_num + $v->can_buy_num;
                }
            }
            $sku = $tmp;
            // 价格保留两位小数
            $sku->currentPrice =  $this->math_add($sku->currentPrice,0);
            if ($hasNextPrice) {
                $sku->nextPrice =  $this->math_add($sku->nextPrice,0);
            }
            $sku->hasNextPrice = $hasNextPrice;
            $sku->nextVIPLevel = $nextVIPLevel;
            $sku->headImage = getImageUrl($sku->image);
            unset($sku->image);
            $spus[$key]->sku = $sku;
            // 图片
        }

        return $spus;
    }

    /**
     * 获取一个SPU中SKU的数量以及标签名字
     */
    public function getSkuNum($list) {
        foreach ($list as $key => $value) {
            // 查询单品个数
            $list[$key]['skuNum'] = DB::table('erp_spu_sku_link')
                -> where('spu_id',$value->id)
                -> where('flag', 0)
                -> count();
            $tags = DB::table('erp_spu_tag_link')
                -> where('erp_spu_tag_link.spu_id', $value->id)
                -> leftJoin('erp_tags','erp_spu_tag_link.tag_id','erp_tags.id')
                -> orderBy('erp_tags.id','desc')
                -> select([
                    'erp_tags.name'
                ]) -> get();

            $list[$key]['tags'] = $this->switchArray2String($tags, 'name');
        }
        return $list;
    }

    /**
     * 获取每个SPU的分类，形如XX/XX形式
     */
    public function getCategoryNameAll($list) {
        foreach ($list as $key => $value) {
            $list[$key]['selectedOptions'] = $this->getBreadCategroy($value->class_id);
            $categorynamelist = $this->getBreadCategroy($value->class_id);
            $categorynameall = $this->getCategoryName($categorynamelist);
            $list[$key]['categorynameall'] = $categorynameall;
        }
        return $list;
    }


    /**
     * 重置标签项的显示
     */
    public function switchArray2String($array, $key, $split = '、')
    {
        $t = [];
        foreach ($array as $k => $v) {
            array_push($t, $v->$key);
        }
        return implode($split, $t);
    }

    /**
     * 获取分类数组
     */
    public function getBreadCategroy($categroyId)
    {
        // 获取分类数组
        $category = DB::table('erp_spu_category')
            -> where('id', $categroyId)
            -> first();
        if(!empty($category)){
            $selectedOptions = explode(",", $category->path.','.$category->id);
            array_shift($selectedOptions);
            foreach ($selectedOptions as $key => $selectedOption) {
                $selectedOptions[$key] = (int)$selectedOption;
            }
            return $selectedOptions;
        }else{
            return [];
        }
    }

    /**
     * 获取分类信息的拼接完整形式形如XX/XX
     */
    public function getCategoryName($list) {
        $categorynameall = '';
        if (count($list) === 1) {
            $categoryname = DB::table('erp_spu_category')
                -> where('erp_spu_category.id',current($list))
                -> value('name');
            $categorynameall = $categoryname;
        } else {
            $categorynamelist = '';
            $categorynamefirst = DB::table('erp_spu_category')
                -> where('erp_spu_category.id',array_shift($list))
                -> value('name');
            foreach ($list as $key => $value) {
                $categoryname = DB::table('erp_spu_category')
                    -> where('erp_spu_category.id',$value)
                    -> value('name');
                $categorynamelist = $categorynamelist . '/' . $categoryname;
            }
            $categorynameall = $categorynamefirst . $categorynamelist;
        }
        return $categorynameall;
    }

    /**
     * 生成小程序二维码
     */
    public function mpQRCode($scene,$path){
        //不存在则生成新的码
        $wxConfig = [
            'app_id' => env('MINI_TMP_SHOP_APPID'),
            'secret' => env('MINI_TMP_SHOP_SECRET'),
            'response_type' => 'array'
        ];
        $info=DB::table('erp_setting')->where('business_id',49)->first();

        $ossLogoName = explode(":",$info->logo)[1]; // logo的oss路径名称
        $app = Factory::miniProgram($wxConfig);
        $response = $app->app_code->getUnlimit($scene,$path);
        if ($response instanceof \EasyWeChat\Kernel\Http\StreamResponse) {
            $filename = $response->save(public_path('uploads/images'));
            //上传oss
            $oss=new OssRepository();
            $result=$oss->uploadFile(public_path('uploads/images').'/'.$filename);
            $ossQrcodeName = explode(':', $result)[1];

            //删除本地文件
            unlink(public_path('uploads/images').'/'.$filename);

            return $this -> ossWatermarkQRCode('fenithcdn', $ossQrcodeName, $ossLogoName);
        }
    }

    /**
     * @description 将小程序太阳码中间的logo更换成用户的头像
     * @param $bucket 存储空间名称
     * @param $endpoint 存储空间所在地域的访问域名
     * @param $objectName 图片文件名称（在oss上的路径）
     * @param $logo 小程序太阳码中间的logo图片（在oss上的路径）
     * @return 返回一个带店铺logo图片的小程序太阳码图片的 绝对路径
     */
    public function ossWatermarkQRCode(string $bucket, string $objectName, string $logo, string $endpoint = 'oss-cn-shanghai.aliyuncs.com') :string
    {
        // 白底图片base64编码，防止logo是png图片
        $blankBase64 = base64_encode('upload/blank.png?x-oss-process=image/resize,m_fixed,w_140,h_140,limit_0/circle,r_70');
        $urlSafeBase64Blank = str_replace(array('+','/','='), array('-','_',''), $blankBase64);
        // logo base64编码
        $base64Image = base64_encode($logo . '?x-oss-process=image/' . 'resize,w_140,h_140/circle,r_70');
        $urlSafeBase64Image = str_replace(array('+','/','='), array('-','_',''), $base64Image);

        return "{$objectName}?x-oss-process=image/resize,w_160";
    }



    //获取商品价格详情
    public function fetchGoodsInfo($request){
        $market_class_map = config('admin.market_class');
        $total2 = '0.00';
        $total3 = '0.00';
        if($request->type  == 'common') {

            $list = DB::table('erp_product_list')
                ->leftJoin("erp_spu_sku_link", 'erp_product_list.id', 'erp_spu_sku_link.sku_id')
                ->leftJoin("erp_mp_name_spu_link", "erp_mp_name_spu_link.spu_id", 'erp_spu_sku_link.spu_id')
                ->leftJoin("erp_spu_list", "erp_spu_list.id", "erp_mp_name_spu_link.spu_id")
                ->leftJoin("erp_product_price","erp_product_price.product_id","erp_product_list.id")
                ->where("erp_product_price.union_status",1)
                ->where('erp_mp_name_spu_link.id', $request->spuId)
                ->select([
                    "erp_product_list.id",
                    "erp_product_list.image",
                    "erp_spu_list.name",
                    "erp_spu_list.type",
                    "erp_product_price.id as pid",
                    "erp_product_price.price_a",
                    "erp_product_price.price_b",
                    "erp_product_price.price_c",
                    "erp_product_price.price_d",
                    "erp_product_price.price_s",
                    "erp_product_price.union_num"
                ])
                ->orderBy('erp_product_list.id','asc')
                ->get()->toArray();
            $imageArr =[];
            foreach($list as $v){
                $imageArr[$v->id] = getImageUrl($v->image);
            }
            $image = getImageUrl($list[0]->image);
            $type = $list[0]->type;
            $name = $list[0]->name;
            foreach ($list as $k => $v) {
                $vip2price = "price_" . $market_class_map[2];
                $vip3price = "price_" . $market_class_map[3];
                if ($type == 1) {
                    $now2 = bcmul($v->$vip2price, $v->union_num, 2);
                    $total2 = bcadd($total2, $now2, 2);

                    $now3 = bcmul($v->$vip3price, $v->union_num, 2);
                    $total3 = bcadd($total3, $now3, 2);
                }else {
                    if($v->pid == $request->skuId){
                        $total2 = $v->$vip2price;
                        $total3 = $v->$vip3price;
                        $image = $imageArr[$v->id];
                    }
                }
            }
        }else{
            $special_ids=explode('_',$request->spuId);
            $list = DB::table('erp_product_list')
                ->leftJoin('erp_special_price',"erp_special_price.sku_id","erp_product_list.id")
                ->leftJoin("erp_product_price","erp_product_price.product_id","erp_product_list.id")
                ->leftJoin("erp_mp_name_spu_link", "erp_mp_name_spu_link.id", 'erp_special_price.mp_spu_link_id')
                ->leftJoin("erp_spu_list", "erp_spu_list.id", "erp_mp_name_spu_link.spu_id")
                ->where("erp_special_price.flag",'0')
                ->where("erp_product_price.mp_name_id",'5')
                ->whereIn("erp_special_price.id",$special_ids)
                ->select([
                    'erp_product_list.image',
                    'erp_special_price.price',
                    'erp_special_price.num',
                    'erp_product_price.union_num',
                    'erp_product_price.price_d',
                    "erp_product_list.id",
                    "erp_spu_list.type",
                ])
                ->orderBy('erp_product_list.id','desc')
                ->get();
            $image = getImageUrl($list[0]->image);
            foreach ($list as $k=>$v){
                if($v->type ==1 ){
                    $now = $v->union_num>0 ? bcmul($v->price,$v->union_num,2):$v->price ;
                    $now2 = $v->union_num>0 ? bcmul($v->price_d,$v->union_num,2):$v->price_d ;
                    $total2  = bcadd($total2,$now,2);
                    $total3  = bcadd($total3,$now2,2);
                }else{
                    $total2  = $v->price;
                    $total3  = $v->price_d;

                }

            }

            $name = DB::table("erp_spu_list")
                ->leftJoin("erp_mp_name_spu_link","erp_mp_name_spu_link.spu_id","erp_spu_list.id")
                ->leftJoin("erp_special_price","erp_special_price.mp_spu_link_id","erp_mp_name_spu_link.id")
                ->where("erp_special_price.flag",'0')
                ->whereIn("erp_special_price.id",$special_ids)
                ->value('erp_spu_list.name');
        }




        return [
            'vip2price' => $total2,
            'vip3price' => $total3,
            'name'      => $name,
            'image'     => $image
        ];





    }




    //获取限时特价商品
    public function fetchSpecialInfo($date){
        $list=$this->special->getSpecialByDate($date);
//        dd($list);
//        $list = DB::table('erp_product_list')
//            ->leftJoin('erp_product_price',"erp_product_price.product_id","erp_product_list.id")
//            ->leftJoin('erp_special_price',"erp_special_price.sku_id","erp_product_list.id")
//            ->leftJoin('erp_mp_name_spu_link',"erp_special_price.mp_spu_link_id","erp_mp_name_spu_link.id")
//            ->leftJoin("erp_spu_list","erp_spu_list.id","erp_mp_name_spu_link.spu_id")
//            ->where("erp_special_price.flag",'0')
//            ->where("erp_product_price.mp_name_id",'5')
//            ->whereIn("erp_special_price.id",$special_ids)
//            ->select([
//                'erp_product_list.image',
//                'erp_product_list.product_name',
//                'erp_special_price.price',
//                'erp_product_price.union_num',
//                "erp_spu_list.name",
//                "erp_spu_list.sub_name",
//                "erp_spu_list.type",
//                "erp_special_price.id",
//                "erp_product_price.price_d"
//            ])
//            ->groupBy("erp_special_price.id")
//            ->orderBy('erp_special_price.id','asc')
//            ->get();
        foreach ($list as $k=>$v){
            $v->showname = $v->spu_name;
            $v->price   = floatval($v->special_price);
            $v->price_d   = floatval($v->price_d);
            $v->image  =str_replace('https://fenithcdn.oss-cn-shanghai.aliyuncs','http://cdn.fenith', $v->image);

        }
        return $list;

    }

    function getImageUrl($image)
    {
        if (empty($image)){
            return '';
        }
        if(starts_with($image,'ali_oss:')){
            $arr=explode(':',$image);
            $url='http://cdn.fenith.com/'.$arr[1];
        }else{
            $url=url('uploads/images/'.$image);
        }
        return $url;
    }

}
