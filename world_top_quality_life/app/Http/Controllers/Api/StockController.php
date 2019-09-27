<?php

namespace App\Http\Controllers\Api;

use App\ReceiveGoodsRecord;
use App\Stock;
use App\WareHouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class StockController extends Controller
{
    public function zeroList (Request $request)
    {
        // 就是曾经有的，现在库存数量为0的
        $validator = Validator::make($request->all(), [
            'business_id'   => 'required|numeric|exists:erp_business,id',
            'exclusiveSPUId'=> 'sometimes|numeric',
            'includeSPUId'  => 'sometimes|numeric',
            'warehouse'     => 'sometimes|numeric'
        ]);

        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        // 根据仓库name和事业部id获取仓库id
        $warehouse_id = Warehouse::getWarehouseId($request->warehousename, $request->business_id);
        $warehouse_ids = [];
        foreach ($warehouse_id as $key => $value) {
            array_push($warehouse_ids, $value->id);
        }

        // 苏州主库的首饰不看
        $excludeIds = DB::table('erp_stock')
            -> leftJoin('erp_product_list','erp_stock.product_id','erp_product_list.id')
            -> leftJoin('erp_product_class as product_class','erp_product_list.class_id','product_class.id')
            -> where('erp_stock.warehouse_id',75)
            -> where('erp_stock.can_buy_num',0)
            -> where('erp_stock.business_id',$request -> business_id)
            -> where('erp_stock.flag',0)
            -> where('product_class.id', 9)
            -> pluck('erp_stock.id');

        /*$list = DB::table('erp_stock')
            -> leftJoin('erp_product_list','erp_stock.product_id','erp_product_list.id')
            -> leftJoin('erp_storehouse','erp_stock.store_house_id','erp_storehouse.id')
            -> leftJoin('erp_warehouse','erp_stock.warehouse_id','erp_warehouse.id')
            //商品去找类别、品牌、系列
            //类别
            -> leftJoin('erp_product_class as product_class','erp_product_list.class_id','product_class.id')
            //品牌
            -> leftJoin('erp_product_class as product_brand','erp_product_list.brand_id','product_brand.id')
            //系列
            -> leftJoin('erp_product_class as product_series','erp_product_list.series_id','product_series.id')
            -> where('erp_stock.business_id',$request -> business_id)
            -> where('erp_stock.flag',0)
            -> where('erp_storehouse.is_unusual', 1)
            -> where('erp_stock.can_buy_num',0)
            -> whereNotIn('erp_stock.warehouse_id',[72]) // 临时不包含日本库
            -> whereNotIn('erp_stock.id', $excludeIds)
            -> where(function($query)use($request, $warehouse_ids){
                //仓库
                if($request -> warehousename){
                    $query -> whereIn('erp_stock.warehouse_id', $warehouse_ids);
                }
                if($request -> warehouse_id){
                    $query -> where('erp_stock.warehouse_id',$request -> warehouse_id);
                }
                //库位
                if($request -> storehouse_id){
                    $query -> where('erp_stock.store_house_id',$request -> storehouse_id);
                }
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
                    $query -> where('erp_product_list.product_no','like','%'.trim($request -> product_no).'%');
                }
                //商品名称
                if($request -> product_name){
                    $query -> where('erp_product_list.product_name','like','%'.trim($request -> product_name).'%');
                }
            })
            -> select([
                'erp_stock.*',
                'erp_product_list.product_name',
                'erp_product_list.weight as declared_weight',
                'erp_product_list.id as product_list_id',
                'erp_product_list.declared_price',
                'erp_product_list.model',
                'erp_product_list.number',
                'erp_product_list.product_no',
                'erp_product_list.image',
                'erp_storehouse.name as store_house_name',
                'erp_warehouse.name as warehouse_name',
                'product_class.name as product_class_name',
                'product_brand.name as product_brand_name',
                'product_series.name as product_series_name',
            ])
            -> orderBy('erp_stock.warehouse_id','desc')
            -> orderBy('erp_stock.store_house_id','desc')
            -> orderBy('product_class.name','asc')
            -> orderBy('product_brand.name','asc')
            -> orderBy('product_series.name','asc')
            -> orderBy('erp_stock.updated_at','desc')
            -> paginate(isset($request -> per_page)?$request -> per_page:20);*/

        //根据各馆区商品的安全库存显示
        $list=DB::table('erp_product_price')
            ->leftJoin('erp_mp_name','erp_product_price.mp_name_id','erp_mp_name.id')
            ->leftJoin('erp_product_list','erp_product_price.product_id','erp_product_list.id')
            //商品去找类别、品牌、系列
            //类别
            ->leftJoin('erp_product_class as product_class','erp_product_list.class_id','product_class.id')
            //品牌
            ->leftJoin('erp_product_class as product_brand','erp_product_list.brand_id','product_brand.id')
            //系列
            ->leftJoin('erp_product_class as product_series','erp_product_list.series_id','product_series.id')
            ->whereColumn('erp_product_price.has_stock','<=','erp_product_price.safe_stock')
            ->where('erp_product_price.flag',0)
            ->where('erp_product_price.status',1)
            ->where('erp_product_price.mp_name_id','>',0)
            ->where('erp_product_price.is_show',1)
            ->where(function($query)use($request, $warehouse_ids){
                //馆区
                if($request -> mp_id){
                    $query -> where('erp_mp_name.id', $request -> mp_id);
                }
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
                    $query -> where('erp_product_list.product_no','like','%'.trim($request -> product_no).'%');
                }
                //商品名称
                if($request -> product_name){
                    $query -> where('erp_product_list.product_name','like','%'.trim($request -> product_name).'%');
                }
            })
            -> select([
                'erp_mp_name.mp_name',
                'erp_product_price.id as price_id',
                'erp_product_price.has_stock',
                'erp_product_list.product_name',
                'erp_product_list.weight as declared_weight',
                'erp_product_list.id as product_list_id',
                'erp_product_list.declared_price',
                'erp_product_list.model',
                'erp_product_list.number',
                'erp_product_list.product_no',
                'erp_product_list.image',
                'product_class.name as product_class_name',
                'product_brand.name as product_brand_name',
                'product_series.name as product_series_name',
            ])
            -> orderBy('erp_product_price.id','desc')
            -> paginate(isset($request -> per_page)?$request -> per_page:20);

        return $this->stockfilter($list);
    }

    //库存列表
    public function theList(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id'   => 'required|numeric|exists:erp_business,id',
            'exclusiveSPUId'=> 'sometimes|numeric',
            'includeSPUId'  => 'sometimes|numeric',
            'warehouse'     => 'sometimes|numeric'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }


        // 不包含已关联某个SPU的SKU列表
        $exclusiveIds = [];
        if($request->exclusiveSPUId){
            // 取到了不该包含的Ids
            $links = DB::table('erp_spu_sku_link')
            -> where('spu_id', $request->exclusiveSPUId)
            -> select([
                '*'
            ]) -> get();

            // 遍历把id传到数组里
            foreach($links as $key => $link) {
                $exclusiveIds[] = $link->sku_id;
            }
        }

        // 只取已关联某个SPU的
        $includeIds = [];
        if($request->includeSPUId){
            // 取到了不该包含的Ids
            $links = DB::table('erp_spu_sku_link')
            -> where('spu_id', $request->includeSPUId)
            -> select([
                '*'
            ]) -> get();

            // 遍历把id传到数组里
            foreach($links as $key => $link) {
                $includeIds[] = $link->sku_id;
            }
        }

         // 根据仓库name和事业部id获取仓库id
        $warehouse_id = Warehouse::getWarehouseId($request->warehousename, $request->business_id);
        $warehouse_ids = [];
        foreach ($warehouse_id as $key => $value) {
            array_push($warehouse_ids, $value->id);
        }
        $list = DB::table('erp_stock')
            -> leftJoin('erp_product_list','erp_stock.product_id','erp_product_list.id')
            //-> leftJoin('erp_product_price','erp_stock.product_id','erp_product_price.product_id')
            -> leftJoin('erp_storehouse','erp_stock.store_house_id','erp_storehouse.id')
            -> leftJoin('erp_warehouse','erp_stock.warehouse_id','erp_warehouse.id')

            //商品去找类别、品牌、系列
                //类别
            -> leftJoin('erp_product_class as product_class','erp_product_list.class_id','product_class.id')
            //品牌
            -> leftJoin('erp_product_class as product_brand','erp_product_list.brand_id','product_brand.id')
            //系列
            -> leftJoin('erp_product_class as product_series','erp_product_list.series_id','product_series.id')
            ->where(function($query)use($exclusiveIds){
                if(count($exclusiveIds)){
                    $query -> whereNotIn('erp_stock.id',$exclusiveIds);
                }
            })
            ->where(function($query)use($includeIds, $request){
                if(count($includeIds)){
                    $query -> whereIn('erp_stock.id',$includeIds);
                }elseif($request->includeSPUId){
                    $query -> whereIn('erp_stock.id',[0]);
                }
            })
            -> where('erp_stock.business_id',$request -> business_id)
            -> where(function($query){
                $query -> where('erp_stock.can_buy_num','>',0) -> orWhere('erp_stock.waiting_num','>',0);
            })
            // -> where('erp_storehouse.is_unusual', 1)
            // -> where('erp_storehouse.is_unusual','>', 0 )
            -> where('erp_stock.flag',0)
            -> where(function($query)use($request, $warehouse_ids){
                //仓库
                if($request -> warehousename){
                    $query -> whereIn('erp_stock.warehouse_id', $warehouse_ids);
                }
                if($request -> warehouse_id){
                    $query -> where('erp_stock.warehouse_id',$request -> warehouse_id);
                }
                //库位
                if($request -> storehouse_id){
                    $query -> where('erp_stock.store_house_id',$request -> storehouse_id);
                }
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

                //上架状态
                if($request -> status || $request -> status === 0){
                    $query -> where('erp_stock.status',$request -> status);
                }
                // sku列表搜索
                if ($request -> skulist) {
                    // 正常品
                    $query -> where('erp_storehouse.is_unusual', 1);
                    // 产品数量大于0
                    $query -> where('erp_stock.can_buy_num', '>', 0);
                }
            })
            -> select([
                'erp_stock.*',
                'erp_product_list.product_name',
                'erp_product_list.weight as declared_weight',
                'erp_product_list.id as product_list_id',
                'erp_product_list.declared_price',
                'erp_product_list.model',
                'erp_product_list.number',
                'erp_product_list.product_no',
                'erp_product_list.image',
                'erp_storehouse.name as store_house_name',
                'erp_warehouse.name as warehouse_name',
                'product_class.name as product_class_name',
                'product_brand.name as product_brand_name',
                'product_series.name as product_series_name',

            ])
            -> orderBy('erp_stock.created_at', 'desc')
            -> orderBy('erp_stock.warehouse_id','desc')
            -> orderBy('erp_stock.store_house_id','desc')

            -> orderBy('product_class.name','asc')
            -> orderBy('product_brand.name','asc')
            -> orderBy('product_series.name','asc')

            -> orderBy('erp_stock.updated_at','desc')
            -> paginate(isset($request -> per_page)?$request -> per_page:20);
        foreach ($list as $key => $value) {
            $list[$key]->declared_price = (float)$value->declared_price;
            //计算当前数量
            $list[$key] -> current_num = intval($value -> can_buy_num) + intval($value -> waiting_num);
            $list[$key] -> created_at = date('Y-m-d H:i', $value->created_at);
            //$list[$key] -> image = url('uploads').'/'.$value -> image;
        }
        return $this->stockfilter($list);
    }

    //旧库存列表
    public function oldGetSKUList(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id'   => 'required|numeric|exists:erp_business,id',
            'exclusiveSPUId'=> 'sometimes|numeric',
            'includeSPUId'  => 'sometimes|numeric',
            'warehouse'     => 'sometimes|numeric'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        // 不包含已关联某个SPU的SKU列表
        $exclusiveIds = [];
        if($request->exclusiveSPUId){
            // 取到了不该包含的Ids
            $links = DB::table('erp_spu_sku_link')
            -> where('spu_id', $request->exclusiveSPUId)
            -> select([
                '*'
            ]) -> get();

            // 遍历把id传到数组里
            foreach($links as $key => $link) {
                $exclusiveIds[] = $link->sku_id;
            }
        }

        // 只取已关联某个SPU的
        $includeIds = [];
        if($request->includeSPUId){
            // 取到了不该包含的Ids
            $links = DB::table('erp_spu_sku_link')
            -> where('spu_id', $request->includeSPUId)
            -> select([
                '*'
            ]) -> get();

            // 遍历把id传到数组里
            foreach($links as $key => $link) {
                $includeIds[] = $link->sku_id;
            }
        }
        $list = DB::table('erp_product_list')
            -> leftJoin('erp_spu_sku_link','erp_product_list.id','erp_spu_sku_link.sku_id')
            -> leftJoin('erp_product_price','erp_product_list.id','erp_product_price.product_id')
            //商品去找类别、品牌、系列
                //类别
            -> leftJoin('erp_product_class as product_class','erp_product_list.class_id','product_class.id')
            //品牌
            -> leftJoin('erp_product_class as product_brand','erp_product_list.brand_id','product_brand.id')
            //系列
            -> leftJoin('erp_product_class as product_series','erp_product_list.series_id','product_series.id')
            ->where('erp_spu_sku_link.spu_id',$request->spu_id)
            ->where(function($query)use($exclusiveIds){
                if(count($exclusiveIds)){
                    $query -> whereNotIn('erp_product_list.id',$exclusiveIds);
                }
            })
            ->where(function($query)use($includeIds, $request){
                if(count($includeIds)){
                    $query -> whereIn('erp_product_list.id',$includeIds);
                }elseif($request->includeSPUId){
                    $query -> whereIn('erp_product_list.id',[0]);
                }
            })
            // -> where('erp_storehouse.is_unusual', 1)
            // -> where('erp_storehouse.is_unusual','>', 0 )
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
            })
            -> select([
                'erp_spu_sku_link.status as status',
                'erp_product_list.product_name',
                'erp_product_list.weight as declared_weight',
                'erp_product_list.id as id',
                'erp_product_list.declared_price',
                'erp_product_list.model',
                'erp_product_list.number',
                'erp_product_list.product_no',
                'erp_product_list.image',
                'erp_product_price.price_a',
                'erp_product_price.price_b',
                'erp_product_price.price_c',
                'erp_product_price.price_d',
                'erp_product_price.price_s',
                'product_class.name as product_class_name',
                'product_brand.name as product_brand_name',
                'product_series.name as product_series_name',

            ])

            -> paginate(isset($request -> per_page)?$request -> per_page:20);
        foreach ($list as $key => $value) {
            $list[$key]->declared_price = (float)$value->declared_price;
        }
        return $this->stockfilter($list);
    }

    //新库存列表
    public function getSKUList(Request $request){
        $validator = Validator::make($request->all(), [
            'spu_id' => 'required'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        // // 不包含已关联某个SPU的SKU列表
        // $exclusiveIds = [];
        // if($request->exclusiveSPUId){
        //     // 取到了不该包含的Ids
        //     $links = DB::table('erp_spu_sku_link')
        //     -> where('spu_id', $request->exclusiveSPUId)
        //     -> select([
        //         '*'
        //     ]) -> get();

        //     // 遍历把id传到数组里
        //     foreach($links as $key => $link) {
        //         $exclusiveIds[] = $link->sku_id;
        //     }
        // }

        // // 只取已关联某个SPU的
        // $includeIds = [];
        // if($request->includeSPUId){
        //     // 取到了不该包含的Ids
        //     $links = DB::table('erp_spu_sku_link')
        //     -> where('spu_id', $request->includeSPUId)
        //     -> select([
        //         '*'
        //     ]) -> get();

        //     // 遍历把id传到数组里
        //     foreach($links as $key => $link) {
        //         $includeIds[] = $link->sku_id;
        //     }
        // }
        $list = DB::table('erp_product_list')
            -> leftJoin('erp_spu_sku_link','erp_product_list.id','erp_spu_sku_link.sku_id')
            // -> leftJoin('erp_product_price','erp_product_list.id','erp_product_price.product_id')
            //商品去找类别、品牌、系列
                //类别
            -> leftJoin('erp_product_class as product_class','erp_product_list.class_id','product_class.id')
            //品牌
            -> leftJoin('erp_product_class as product_brand','erp_product_list.brand_id','product_brand.id')
            //系列
            -> leftJoin('erp_product_class as product_series','erp_product_list.series_id','product_series.id')
            -> where('erp_spu_sku_link.spu_id',$request->spu_id)
            -> where('erp_spu_sku_link.flag', 0)
            // ->where(function($query)use($exclusiveIds){
            //     if(count($exclusiveIds)){
            //         $query -> whereNotIn('erp_product_list.id',$exclusiveIds);
            //     }
            // })
            // ->where(function($query)use($includeIds, $request){
            //     if(count($includeIds)){
            //         $query -> whereIn('erp_product_list.id',$includeIds);
            //     }elseif($request->includeSPUId){
            //         $query -> whereIn('erp_product_list.id',[0]);
            //     }
            // })
            // -> where('erp_storehouse.is_unusual', 1)
            // -> where('erp_storehouse.is_unusual','>', 0 )
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
            })
            -> select([
                // 'erp_spu_sku_link.status as status',
                'erp_product_list.product_name',
                'erp_product_list.weight as declared_weight',
                'erp_product_list.id as id',
                'erp_product_list.declared_price',
                'erp_product_list.model',
                'erp_product_list.number',
                'erp_product_list.product_no',
                'erp_product_list.image',
                // 'erp_product_price.price_a',
                // 'erp_product_price.price_b',
                // 'erp_product_price.price_c',
                // 'erp_product_price.price_d',
                // 'erp_product_price.price_s',
                'product_class.name as product_class_name',
                'product_brand.name as product_brand_name',
                'product_series.name as product_series_name',

            ])

            -> paginate(isset($request -> per_page)?$request -> per_page:20);
        foreach ($list as $key => $value) {
            $list[$key]->declared_price = (float)$value->declared_price;
        }
        return $this->stockfilter($list);
    }

    //旧库存列表
    public function oldGetUnLinkSKUList(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id'   => 'required|numeric|exists:erp_business,id',
            'exclusiveSPUId'=> 'sometimes|numeric',
            'includeSPUId'  => 'sometimes|numeric',
            'warehouse'     => 'sometimes|numeric'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        // 不包含已关联某个SPU的SKU列表
        $exclusiveIds = [];
        if($request->exclusiveSPUId){
            // 取到了不该包含的Ids
            $links = DB::table('erp_spu_sku_link')
            -> where('spu_id', $request->exclusiveSPUId)
            -> select([
                '*'
            ]) -> get();

            // 遍历把id传到数组里
            foreach($links as $key => $link) {
                $exclusiveIds[] = $link->sku_id;
            }
        }

        // 只取已关联某个SPU的
        $includeIds = [];
        if($request->includeSPUId){
            // 取到了不该包含的Ids
            $links = DB::table('erp_spu_sku_link')
            -> where('spu_id', $request->includeSPUId)
            -> select([
                '*'
            ]) -> get();

            // 遍历把id传到数组里
            foreach($links as $key => $link) {
                $includeIds[] = $link->sku_id;
            }
        }
        $list = DB::table('erp_product_list')
            -> leftJoin('erp_product_price','erp_product_list.id','erp_product_price.product_id')
            //商品去找类别、品牌、系列
                //类别
            -> leftJoin('erp_product_class as product_class','erp_product_list.class_id','product_class.id')
            //品牌
            -> leftJoin('erp_product_class as product_brand','erp_product_list.brand_id','product_brand.id')
            //系列
            -> leftJoin('erp_product_class as product_series','erp_product_list.series_id','product_series.id')
            ->where(function($query)use($exclusiveIds){
                if(count($exclusiveIds)){
                    $query -> whereNotIn('erp_product_list.id',$exclusiveIds);
                }
            })
            ->where(function($query)use($includeIds, $request){
                if(count($includeIds)){
                    $query -> whereIn('erp_product_list.id',$includeIds);
                }elseif($request->includeSPUId){
                    $query -> whereIn('erp_product_list.id',[0]);
                }
            })
            // -> where('erp_storehouse.is_unusual', 1)
            // -> where('erp_storehouse.is_unusual','>', 0 )
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
            })
            -> select([
                'erp_product_list.product_name',
                'erp_product_list.weight as declared_weight',
                'erp_product_list.id as id',
                'erp_product_list.declared_price',
                'erp_product_list.model',
                'erp_product_list.number',
                'erp_product_list.product_no',
                'erp_product_list.image',
                'erp_product_price.price_a',
                'erp_product_price.price_b',
                'erp_product_price.price_c',
                'erp_product_price.price_d',
                'erp_product_price.price_s',
                'product_class.name as product_class_name',
                'product_brand.name as product_brand_name',
                'product_series.name as product_series_name',

            ])

            -> paginate(isset($request -> per_page)?$request -> per_page:20);
        foreach ($list as $key => $value) {
            $list[$key]->declared_price = (float)$value->declared_price;
        }
        return $this->stockfilter($list);
    }

    //新未关联SKU列表
    public function getUnLinkSKUList(Request $request){
        $validator = Validator::make($request->all(), [
            'spu_id'=> 'required|numeric',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        $exclusiveIds = DB::table('erp_spu_sku_link')
                            -> where('flag', 0)
                            -> where('spu_id', $request->spu_id)
                            -> groupBy('sku_id')
                            -> pluck('sku_id');
        $list = DB::table('erp_product_list')
            //商品去找类别、品牌、系列
                //类别
            -> leftJoin('erp_product_class as product_class','erp_product_list.class_id','product_class.id')
            //品牌
            -> leftJoin('erp_product_class as product_brand','erp_product_list.brand_id','product_brand.id')
            //系列
            -> leftJoin('erp_product_class as product_series','erp_product_list.series_id','product_series.id')
            -> whereNotIn('erp_product_list.id',$exclusiveIds)
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
                if($request -> name){
                    $query -> where('erp_product_list.product_name','like','%'.trim($request -> name).'%');
                }
            })
            -> select([
                'erp_product_list.product_name',
                'erp_product_list.weight as declared_weight',
                'erp_product_list.id as id',
                'erp_product_list.declared_price',
                'erp_product_list.model',
                'erp_product_list.number',
                'erp_product_list.product_no',
                'erp_product_list.image',
                'product_class.name as product_class_name',
                'product_brand.name as product_brand_name',
                'product_series.name as product_series_name',

            ])
            -> paginate(isset($request -> per_page)?$request -> per_page:20);
        foreach ($list as $key => $value) {
            $value->to_add=$value->id;//没啥用的字段，给前端做移除的标识
            $list[$key]->declared_price = (float)$value->declared_price;
        }
        return $this->stockfilter($list);
    }

    public function stockfilter($stocks)
    {
        foreach ($stocks as $key => $stock) {
            $stocks[$key]->imageUrl = empty($stock->image) ? '' : getImageUrl($stock -> image) ;
        }
        return $stocks;
    }

    //获取入库明细
    public function receiveGoodsList(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id',
            'store_house_id' => 'required|numeric|exists:erp_storehouse,id',
            'product_id' => 'required|numeric|exists:erp_product_list,id',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        //通过商品编号 查询入库商品明细
        $list = ReceiveGoodsRecord::getReceiveList($request);
        return $list;


    }

    //待发货明细
    public function deliverRecord(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id',
            'store_house_id' => 'required|numeric|exists:erp_storehouse,id',
            'product_id' => 'required|numeric|exists:erp_product_list,id',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }




        //查看这个库位 这个商品 待发货的商品
        $erp_stock_order_info = DB::table('erp_stock_order_info')
            -> leftJoin('erp_stock_order_info_receive','erp_stock_order_info.id','erp_stock_order_info_receive.stock_order_info_id')
            -> leftJoin('erp_receive_goods_record','erp_stock_order_info_receive.receive_goods_record_id','erp_receive_goods_record.id')
            -> leftJoin('erp_stock_order','erp_stock_order_info.stock_order_id','erp_stock_order.id')

            -> leftJoin('erp_storehouse','erp_receive_goods_record.store_house_id','erp_storehouse.id')
            -> leftJoin('erp_warehouse','erp_storehouse.warehouse_id','erp_warehouse.id')
            -> leftJoin('erp_product_list','erp_stock_order_info.product_id','erp_product_list.id')
            -> leftJoin('wxuser','erp_stock_order.user_id','wxuser.id')
            -> where([
                'erp_receive_goods_record.store_house_id' => $request -> store_house_id,
                'erp_stock_order_info.flag' => 0,
                'erp_stock_order_info.product_id' => $request -> product_id,
                'erp_stock_order.business_id' => $request -> business_id
            ])
            -> where(function($query){
                $query -> where('erp_stock_order.send_status',0) -> orWhere('erp_stock_order.send_status',2);
                $query -> where('erp_stock_order.send_remark',0);
            })
            ->select([
                'erp_stock_order.order_num', //订单编号
                'erp_warehouse.name as warehouse_name', //仓库名称
                'erp_product_list.product_no', //商品编号
                'wxuser.nickname', //客户
                'erp_stock_order_info.number',
                'erp_stock_order_info.send_num',
            ])
            -> get();
        foreach($erp_stock_order_info as $k => $vo){
            //待发数量
            $erp_stock_order_info[$k] -> waiting_num = intval($vo -> number) - intval($vo -> send_num);
        }

        return $erp_stock_order_info;


    }



    //仓库中未上架商品报表
    public function GoodsDownRecord(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        //商城商品
        $shop = DB::table('erp_product_price')
            ->leftJoin("erp_mp_name","erp_mp_name.id","erp_product_price.mp_name_id")
            -> where(function($query)use($request){
                $query->where("erp_product_price.is_show",1);
            })
            ->pluck('erp_product_price.product_id');

        //仓库商品
        $ware = DB::table('erp_product_list')
            ->leftJoin("erp_stock","erp_stock.product_id","erp_product_list.id")
            ->leftJoin("erp_warehouse","erp_warehouse.id","erp_stock.warehouse_id")
            ->leftJoin("erp_mp_name","erp_warehouse.mp_name_id","erp_mp_name.id")
            //类别
            -> leftJoin('erp_product_class as product_class','erp_product_list.class_id','product_class.id')
            //品牌
            -> leftJoin('erp_product_class as product_brand','erp_product_list.brand_id','product_brand.id')
            //系列
            -> leftJoin('erp_product_class as product_series','erp_product_list.series_id','product_series.id')
            ->where(function($query)use($request,$shop){
                if($request->business_id){
                    $query->where('erp_warehouse.business_id',$request->business_id);
                }
                $query->whereNotNUll('erp_stock.can_buy_num');
                $query->whereNotIn('erp_product_list.id',$shop);
                //商品编号
                if($request -> product_no){
                    $query -> where('erp_product_list.product_no','like','%'.trim($request -> product_no).'%');
                }
                //商品名称
                if($request -> product_name){
                    $query -> where('erp_product_list.product_name','like','%'.trim($request -> product_name).'%');
                }

            })
            ->select([
                "erp_product_list.*",
                "erp_stock.can_buy_num",
                "erp_stock.waiting_num",
                "erp_stock.current_num",
                "erp_stock.enter_num",
                'erp_warehouse.name as warehouse_name',
                'product_class.name as product_class_name',
                'product_brand.name as product_brand_name',
                'product_series.name as product_series_name',
            ])
            -> orderBy('product_class.name','asc')
            -> orderBy('product_brand.name','asc')
            -> orderBy('product_series.name','asc')
            -> orderBy('erp_stock.updated_at','desc')
            ->paginate(isset($request -> per_page)?$request -> per_page:20);
        foreach ($ware as $v){
            $v->image = getImageUrl($v->image);
        }


        return $ware;

    }




}
