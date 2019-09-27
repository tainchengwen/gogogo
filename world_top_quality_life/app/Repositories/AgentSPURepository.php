<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use App\Repositories\AgentCategoryRepository;

class AgentSPURepository extends BaseRepository
{
    public function __construct(
        AgentCategoryRepository $agentCategoryRepository
    )
    {
        $this->agentCategoryRepository = $agentCategoryRepository;
    }

    public function searchPaginate($request, $pageSize = 7)
    {
        // 先拿到当前代理的所有正常的sku
        $rightSkuIds = DB::table('erp_agent_price')
            -> leftJoin('erp_product_price as sku', function($q) {
                $q->on('sku.product_id', '=', 'erp_agent_price.sku_id')
                ->on('sku.mp_name_id', '=', 'erp_agent_price.mp_name_id');
            })
            -> leftJoin('erp_spu_sku_link', 'sku.product_id', 'erp_spu_sku_link.sku_id')
            -> leftJoin('erp_mp_name_spu_link', function($q) {
                $q->on('erp_mp_name_spu_link.spu_id', '=', 'erp_spu_sku_link.spu_id')
                ->on('erp_mp_name_spu_link.mp_name_id', '=', 'sku.mp_name_id');
            })
            -> leftJoin('erp_business_spu_link', function($q) {
                $q->on('erp_business_spu_link.spu_id', '=', 'erp_spu_sku_link.spu_id')
                ->on('erp_business_spu_link.mp_name_id', '=', 'sku.mp_name_id');
            })
            -> where('erp_agent_price.business_id', $request->business_id)
            -> where('erp_agent_price.status', 1)
            -> where('erp_agent_price.flag', 0)
            -> where('erp_agent_price.price', '>' , 0)
            -> where('sku.has_stock', '>', 0)
            -> where('sku.is_show', 1)
            -> where('sku.flag', 0)
            -> where('sku.status', 1)
            -> where('erp_spu_sku_link.flag', 0)
            -> where('erp_mp_name_spu_link.flag', 0)
            -> where('erp_business_spu_link.business_id', $request->business_id)
            -> where('erp_business_spu_link.flag', 0)
            -> select([
                'erp_business_spu_link.id',
                'erp_business_spu_link.mp_name_id',
                'erp_business_spu_link.spu_id'
            ])
            -> groupBy('mp_name_id', 'spu_id', 'id')
            -> pluck('id');
        

        $spus = DB::table('erp_business_spu_link as link')
            -> leftJoin('erp_spu_list', 'link.spu_id', 'erp_spu_list.id')
            -> leftJoin('erp_mp_name', 'link.mp_name_id', 'erp_mp_name.id')
            -> where('erp_spu_list.flag', 0)
            -> whereIn('link.id', $rightSkuIds)
            -> where(function($query)use($request){
                //  分类
                if (!empty($request->class_id)) {
                    $query -> where('link.class_id', $request->class_id);
                }
                //  关键词
                if(!empty($request->keyword)){
                    $query -> where('erp_spu_list.name','like','%'.trim($request->keyword).'%');
                }
            })
            -> select([
                'link.id',
                'link.mp_name_id',
                'erp_mp_name.icon_image',
                'erp_spu_list.id as spu_id',
                'erp_spu_list.name',
                'erp_spu_list.sub_name',
            ])
            -> orderBy('erp_spu_list.id','desc')
            -> paginate($pageSize);

        return $this->assembleFirstSKU($request, $spus);

    }

    public function searchByKeyWord($request, $pageSize = 7)
    {
        $skus = DB::table('erp_product_price as sku')
            ->leftJoin('erp_product_list','sku.product_id','erp_product_list.id')
            -> leftJoin('erp_agent_price', function($q) {
                $q->on('sku.product_id', '=', 'erp_agent_price.sku_id')
                    ->on('sku.mp_name_id', '=', 'erp_agent_price.mp_name_id');
            })
            -> leftJoin('erp_spu_sku_link', 'sku.product_id', 'erp_spu_sku_link.sku_id')
            -> leftJoin('erp_mp_name_spu_link', function($q) {
                $q->on('erp_mp_name_spu_link.spu_id', '=', 'erp_spu_sku_link.spu_id')
                    ->on('erp_mp_name_spu_link.mp_name_id', '=', 'sku.mp_name_id');
            })
            -> leftJoin('erp_business_spu_link', function($q) {
                $q->on('erp_business_spu_link.spu_id', '=', 'erp_spu_sku_link.spu_id')
                    ->on('erp_business_spu_link.mp_name_id', '=', 'sku.mp_name_id');
            })
            ->leftJoin('erp_spu_list','erp_business_spu_link.spu_id','erp_spu_list.id')
            ->leftJoin('erp_mp_name','erp_business_spu_link.mp_name_id','erp_mp_name.id')
            -> where('erp_agent_price.business_id', $request->business_id)
            -> where('erp_agent_price.status', 1)
            -> where('erp_agent_price.flag', 0)
            -> where('erp_agent_price.price', '>' , 0)
            -> where('sku.has_stock', '>', 0)
            -> where('sku.is_show', 1)
            -> where('sku.flag', 0)
            -> where('sku.status', 1)
            -> where('erp_spu_sku_link.flag', 0)
            -> where('erp_mp_name_spu_link.flag', 0)
            -> where('erp_business_spu_link.business_id', $request->business_id)
            -> where('erp_business_spu_link.flag', 0)
            -> where('erp_spu_list.flag', 0)
            -> where(function($query)use($request){
                //  分类
                if (!empty($request->class_id)) {
                    $query -> where('erp_business_spu_link.class_id', $request->class_id);
                }
                //  关键词
                if(!empty($request->keyword)){
                    $query -> where('erp_product_list.product_name','like','%'.$request->keyword.'%')
                        ->orWhere('erp_spu_list.name','like','%'.$request->keyword.'%');
                }
            })
            -> select([
                'erp_business_spu_link.id as business_spu_link_id',
                'erp_business_spu_link.mp_name_id',
                'erp_mp_name.icon_image',
                'erp_spu_list.id as spu_id',
                'erp_spu_list.name as spu_name',
                'erp_spu_list.sub_name',//
                'erp_agent_price.id as agent_price_id',
                'erp_product_list.product_no',
                'erp_product_list.product_name',
                'erp_product_list.image',
                'sku.has_stock as can_buy_num',
                'sku.price_d',
                'erp_agent_price.price',
                'erp_agent_price.original_price'
            ])
            -> orderBy('erp_spu_sku_link.id','desc')
            -> paginate($pageSize);

        foreach ($skus as $key => $sku) {

            // 价格保留两位小数
            $sku->price =  $this->math_add($sku->price,0);
            //兼容旧版程序处理原价，如果没有设置原价就取price_d
            if($sku->original_price=='0'){
                $sku->original_price=$sku->price_d;
            }
            $sku->headImage = getImageUrl($sku->image);
            unset($sku->image);
        }
        return $skus;
    }


    public function home($request,$catgories)
    {
        $recommended = [];

        // 先拿到当前代理的所有正常的sku
        $rightSkuIds = DB::table('erp_agent_price')
            -> leftJoin('erp_product_price as sku', function($q) {
                $q->on('sku.product_id', '=', 'erp_agent_price.sku_id')
                ->on('sku.mp_name_id', '=', 'erp_agent_price.mp_name_id');
            })
            -> leftJoin('erp_spu_sku_link', 'sku.product_id', 'erp_spu_sku_link.sku_id')
            -> leftJoin('erp_mp_name_spu_link', function($q) {
                $q->on('erp_mp_name_spu_link.spu_id', '=', 'erp_spu_sku_link.spu_id')
                ->on('erp_mp_name_spu_link.mp_name_id', '=', 'sku.mp_name_id');
            })
            -> leftJoin('erp_business_spu_link', function($q) {
                $q->on('erp_business_spu_link.spu_id', '=', 'erp_spu_sku_link.spu_id')
                ->on('erp_business_spu_link.mp_name_id', '=', 'sku.mp_name_id');
            })
            -> where('erp_agent_price.business_id', $request->business_id)
            -> where('erp_agent_price.status', 1)
            -> where('erp_agent_price.flag', 0)
            -> where('erp_agent_price.price', '>' , 0)
            -> where('sku.has_stock', '>', 0)
            -> where('sku.is_show', 1)
            -> where('sku.flag', 0)
            -> where('sku.status', 1)
            -> where('erp_spu_sku_link.flag', 0)
            -> where('erp_mp_name_spu_link.flag', 0)
            -> where('erp_business_spu_link.business_id', $request->business_id)
            -> where('erp_business_spu_link.flag', 0)
            -> select([
                'erp_business_spu_link.id',
                'erp_business_spu_link.mp_name_id',
                'erp_business_spu_link.spu_id'
            ])
            -> groupBy('mp_name_id', 'spu_id', 'id')
            -> pluck('id');


        foreach ($catgories as $key => $value) {
            $request->class_id = $value->id;
            $recommended[] = [
                'id'           => $value->id,
                'categoryName' => $value->title,
                'spus'         => $this->tmp_search($request, $rightSkuIds, 12)
            ];
        }

        return $recommended;
    }

    private function tmp_search($request, $rightSkuIds ,$limit = 5)
    {
        $spus = DB::table('erp_business_spu_link as link')
            -> leftJoin('erp_spu_list', 'link.spu_id', 'erp_spu_list.id')
            -> leftJoin('erp_mp_name', 'link.mp_name_id', 'erp_mp_name.id')
            -> where('erp_spu_list.flag', 0)
            -> whereIn('link.id', $rightSkuIds)
            -> where(function($query)use($request){
                //  分类
                if (!empty($request->class_id)) {
                    $query -> where('link.class_id', $request->class_id);
                }
                //  关键词
                if(!empty($request->keyword)){
                    $query -> where('erp_spu_list.name','like','%'.trim($request->keyword).'%');
                }
            })
            -> select([
                'link.id',
                'link.mp_name_id',
                'erp_mp_name.icon_image',
                'erp_spu_list.id as spu_id',
                'erp_spu_list.name',
                'erp_spu_list.sub_name',
            ])
            -> orderBy('erp_spu_list.id','desc')
            -> limit($limit)
            -> get();

        return $this->assembleFirstSKU($request, $spus);
    }


    public function assembleFirstSKU($request, $spus)
    {

        // 查询第一个(查询什么情况下的第一个)
        foreach ($spus as $key => $spu) {
            // spu的馆区图标
            $sku = DB::table('erp_spu_sku_link')
            -> leftJoin('erp_product_list','erp_spu_sku_link.sku_id','erp_product_list.id')
            -> leftJoin('erp_agent_price', function($q) use ($spu) {
                $q->on('erp_agent_price.sku_id', '=', 'erp_spu_sku_link.sku_id')
                ->where('erp_agent_price.mp_name_id', '=', $spu->mp_name_id);
            })
            -> leftJoin('erp_product_price', function($q) {
                $q->on('erp_product_price.product_id', '=', 'erp_agent_price.sku_id')
                ->on('erp_product_price.mp_name_id', '=', 'erp_agent_price.mp_name_id');
            })
            -> where('erp_spu_sku_link.spu_id', $spu->spu_id)
            -> where('erp_agent_price.business_id', $request->business_id)
            -> where('erp_agent_price.status', 1)
            -> where('erp_agent_price.flag', 0)
            -> where('erp_agent_price.price', '>' , 0)
            -> where('erp_product_price.flag', 0)
            -> where('erp_product_price.status', 1)
            -> where('erp_product_price.is_show', 1)
            -> where('erp_product_price.has_stock', '>', 0)
            -> select([
                'erp_agent_price.id',
                'erp_product_list.product_no',
                'erp_product_list.product_name',
                'erp_product_list.image',
                'erp_product_price.has_stock as can_buy_num',
                'erp_product_price.price_d',
                'erp_agent_price.price',
                'erp_agent_price.original_price'
            ])
            -> orderBy('erp_spu_sku_link.id','desc')
            -> first();

            if (!$sku) {
                unset($spus[$key]);
                continue;
            }

            // 价格保留两位小数
            $sku->price =  $this->math_add($sku->price,0);
            //兼容旧版程序处理原价，如果没有设置原价就取price_d
            if($sku->original_price=='0'){
                $sku->original_price=$sku->price_d;
            }
            $sku->headImage = getImageUrl($sku->image);
            unset($sku->image);
            $spus[$key]->sku = $sku;
        }
        return $spus;
    }

    public function getAssembledSPUByRequestAndId($request, $id)
    {
        $spu = DB::table('erp_business_spu_link as link')
            -> leftJoin('erp_spu_list', 'link.spu_id', 'erp_spu_list.id')
            -> leftJoin('erp_mp_name', 'link.mp_name_id', 'erp_mp_name.id')
            -> where('erp_spu_list.flag', 0)
            -> where('link.id', $id)
            -> select([
                'link.id',
                'link.mp_name_id',
                'erp_mp_name.icon_image',
                'erp_spu_list.id as spu_id',
                'erp_spu_list.name',
                'erp_spu_list.sub_name',
                'erp_spu_list.details',
            ])
            -> first();
        if (!$spu) {
            return [
                'success' => false,
                'msg'     => '商品不存在或已下架'
            ];
        }

        $skus = DB::table('erp_spu_sku_link')
        -> leftJoin('erp_product_list','erp_spu_sku_link.sku_id','erp_product_list.id')
        -> leftJoin('erp_agent_price', function($q) use ($spu) {
            $q->on('erp_agent_price.sku_id', '=', 'erp_spu_sku_link.sku_id')
            ->where('erp_agent_price.mp_name_id', '=', $spu->mp_name_id);
        })
        -> leftJoin('erp_product_price', function($q) {
            $q->on('erp_product_price.product_id', '=', 'erp_agent_price.sku_id')
            ->on('erp_product_price.mp_name_id', '=', 'erp_agent_price.mp_name_id');
        })
        -> where('erp_spu_sku_link.spu_id', $spu->spu_id)
        -> where('erp_agent_price.business_id', $request->business_id)
        -> where('erp_agent_price.status', 1)
        -> where('erp_agent_price.flag', 0)
        -> where('erp_agent_price.price', '>' , 0)
        -> where('erp_product_price.flag', 0)
        -> where('erp_product_price.status', 1)
        -> where('erp_product_price.is_show', 1)
        -> where('erp_product_price.has_stock', '>', 0)
        -> select([
            'erp_agent_price.id',
            'erp_product_list.product_no',
            'erp_product_list.product_name',
            'erp_product_list.image',
            'erp_product_price.has_stock as can_buy_num',
            'erp_product_price.price_d',
            'erp_product_price.min_unit',
            'erp_agent_price.price',
            'erp_agent_price.original_price',
        ])
        -> orderBy('erp_spu_sku_link.id','desc')
        -> get();

        if ($skus->isEmpty()) {
            return [
                'success' => false,
                'msg'     => '商品不存在或已下架'
            ];
        }

        foreach ($skus as $key => $sku) {
            $skus[$key]->price        = $this->math_add($sku->price,0);
            //兼容旧版程序处理原价，如果没有设置原价就取price_d
            if($sku->original_price=='0'){
                $skus[$key]->original_price=$sku->price_d;
            }
            $skus[$key]->images       = $this->assembledImages($sku);
            $skus[$key]->headImage    = getImageUrl($sku->image);
            unset($skus[$key]->image);
        }

        return [
            'success' => true,
            'msg'     => 'success',
            'data'    => [
                'spu'   => $spu,
                'skus'  => $skus
            ]
        ];
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
 

}