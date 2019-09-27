<?php

namespace App;

use App\Repositories\SKURepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Stock extends Model
{
    //
    protected $table = 'erp_stock';


    protected $dateFormat = 'U';

    //添加库存

    /**
     * @param $request
     * @param $receive_id 仓库收货记录Id
     */
    static function addStock($request,$receive_id){

        //通过库位id 拿 仓库id
        $storehouse_info = Storehouse::find($request -> storehouse_id);

        //仓库收货json
        $json_arr = json_decode($request -> receive_num_json,true);
        //入库json数组 [{"info_id":"2","receive_num":"34"},{}]

        //更新skuRepository
        $product_ids = [];

        foreach($json_arr as $vo){
            $logistics_info = LogisticsInfo::find($vo['info_id']);

            if($logistics_info -> goods_id){


                //通过 goods_id 找 erp_purchase_order_goods 的 信息
                $order_goods_info = PurchaseOrderGoods::where('id',$logistics_info -> goods_id) -> first();
                $goods_id = $order_goods_info -> product_id;
                $product_ids[] = $goods_id;
                $stock_info = Stock::where([
                    'business_id' => $request ->business_id,
                    'store_house_id' => $request -> storehouse_id,
                    'product_id' => $goods_id
                ])-> first();


                if($stock_info){
                    //更新库存
                    Stock::where([
                        'business_id' => $request ->business_id,
                        'store_house_id' => $request -> storehouse_id,
                        'product_id' => $goods_id
                    ]) -> update([
                        'enter_num' => intval($stock_info -> enter_num) + intval($vo['receive_num']), //入库数量
                        'current_num' => intval($stock_info -> current_num) + intval($vo['receive_num']), //当前数量
                        'can_buy_num' => intval($stock_info -> can_buy_num) + intval($vo['receive_num']), //可售数量
                        'updated_at' => time()
                    ]);

                }else{
                    Stock::insert([
                        'business_id' => $request ->business_id,
                        'store_house_id' => $request -> storehouse_id,
                        'warehouse_id' => $storehouse_info -> warehouse_id,
                        'product_id' => $goods_id,
                        'enter_num' => $vo['receive_num'], //入库数量
                        'current_num' => $vo['receive_num'], //当前数量
                        'can_buy_num' => $vo['receive_num'], //可售数量
                        'updated_at' => time(),
                        'created_at' => time(),
                    ]);

                }
            }
        }

        if(count($product_ids)){
            $skuRepository = new SKURepository();
            $skuRepository -> autoPutOnOrOff($product_ids);
        }


    }

    // /**
    //  * @param $stock_id 
    //  */
    // static function getStock( $stock_id )
    // {
    //     $stock = Stock::leftJoin('erp_product_list','erp_stock.product_id','erp_product_list.id')
    //     -> leftJoin('erp_product_price','erp_stock.product_id','erp_product_price.product_id')
    //     -> leftJoin('erp_storehouse','erp_stock.store_house_id','erp_storehouse.id')
    //     -> leftJoin('erp_warehouse','erp_storehouse.warehouse_id','erp_warehouse.id')
    //     -> leftJoin('erp_product_class as product_class','erp_product_list.class_id','product_class.id')
    //     -> leftJoin('erp_product_class as product_brand','erp_product_list.brand_id','product_brand.id')
    //     -> leftJoin('erp_product_class as product_series','erp_product_list.series_id','product_series.id')
    //     -> where('erp_stock.id',$stock_id)
    //     -> where('erp_stock.flag',0)
    //     -> select([
    //         'erp_stock.*',
    //         'erp_product_list.product_name',
    //         'erp_product_list.declared_price',
    //         'erp_product_list.model',
    //         'erp_product_list.number',
    //         'erp_product_list.product_no',
    //         'erp_product_list.image',
    //         'erp_product_list.declared_price',
    //         'erp_product_list.weight',
    //         'erp_product_price.price_a',
    //         'erp_product_price.price_b',
    //         'erp_product_price.price_c',
    //         'erp_product_price.price_d',
    //         'erp_product_price.price_s',
    //         'erp_storehouse.name as store_house_name',
    //         'erp_warehouse.name as warehouse_name',
    //         'product_class.name as product_class_name',
    //         'product_brand.name as product_brand_name',
    //         'product_series.name as product_series_name',
    //     ])
    //     -> first();

    //     return $stock;
    // }

    // /**
    //  * @param $stockIds  skuIds
    //  */
    // static function putOnProcess( $stockIds )
    // {
    //     // sku 上架
    //     //     如果可以上架成功，则直接修改所有关联的spu为上架状态
    //     // 错误数据
    //     $errors = [];

    //     // 上架校验1  必须维护了所有价格和各种图片标题等等
    //     foreach ($stockIds as $key => $stockId) {
    //         $stock = Stock::getStock($stockId);
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
    //         if ($stock->declared_price > 250) {
    //             $errors[] = $stock->product_no."-申报价格大于250";
    //             continue;
    //         }
    //         // 开始上架
    //         DB::beginTransaction();
    //         try {
    //             Stock::where('id', $stock->id)
    //             -> update([
    //                 'status' => 1
    //             ]);

    //             // 查询所有关联的SPU，统一修改状态为已上架
    //             DB::table('erp_spu_sku_link')
    //             ->where('erp_spu_sku_link.sku_id', $stockId)
    //             -> leftJoin('erp_spu_list','erp_spu_sku_link.spu_id','erp_spu_list.id')
    //             -> update([
    //                 'erp_spu_list.status' => 1
    //             ]);

    //             DB::commit();
    //         } catch (\Exception $e) {
    //             DB::rollBack();
    //             $errors[] = '数据库插入错误';
    //         }
    //     }

    //     return $errors;

    // }

    // 新校验sku上架check
    static function newGetStock( $product_id, $spu_id, $business_id )
    {
        $stock = DB::table('erp_spu_sku_link')
                    -> leftJoin('erp_product_list','erp_product_list.id','erp_spu_sku_link.sku_id')
                    -> leftJoin('erp_product_price','erp_product_list.id','erp_product_price.product_id')
                    -> leftJoin('erp_product_class as product_class','erp_product_list.class_id','product_class.id')
                    -> leftJoin('erp_product_class as product_brand','erp_product_list.brand_id','product_brand.id')
                    -> leftJoin('erp_product_class as product_series','erp_product_list.series_id','product_series.id')
                    -> where('erp_spu_sku_link.sku_id',$product_id)
                    -> where('erp_spu_sku_link.flag',0)
                    -> where('erp_spu_sku_link.business_id',$business_id)
                    -> where('erp_spu_sku_link.spu_id',$spu_id)
                    -> select([
                        'erp_spu_sku_link.sku_id as id',
                        'erp_product_list.product_name',
                        'erp_product_list.declared_price',
                        'erp_product_list.model',
                        'erp_product_list.number',
                        'erp_product_list.product_no',
                        'erp_product_list.image',
                        'erp_product_list.declared_price',
                        'erp_product_list.weight',
                        'erp_product_price.price_a',
                        'erp_product_price.price_b',
                        'erp_product_price.price_c',
                        'erp_product_price.price_d',
                        'erp_product_price.price_s',
                        'erp_spu_sku_link.status as status',
                        'product_class.name as product_class_name',
                        'product_brand.name as product_brand_name',
                        'product_series.name as product_series_name',
                    ])
                    -> first();
        return $stock;
    }

    //新上架
    static function putOnProcess( $productIds, $spu_id, $business_id )
    {
        // sku 上架
        //     如果可以上架成功，则直接修改所有关联的spu为上架状态
        // 错误数据
        $errors = [];

        // 上架校验1  必须维护了所有价格和各种图片标题等等
        foreach ($productIds as $key => $productId) {
            $stock = Stock::newGetStock($productId, $spu_id, $business_id);
            // 商品是否存在
            if(empty($stock)){
                $errors[] = '上架的商品不存在';
                continue;
            }
            // 价格维护
            if (empty($stock->price_s) || empty($stock->price_a) || empty($stock->price_b) || empty($stock->price_c) || empty($stock->price_d)) {
                $errors[] = $stock->product_no."-价格未维护";
                continue;
            }
            // 图片相关信息是否维护，如果没有pass
            if (empty($stock->image)) {
                $errors[] = $stock->product_no."-图片未维护";
                continue;
            }

            // 申报价格必须小于250
            if ((float)$stock->declared_price > 250 || (float)$stock->declared_price <= 0) {
                $errors[] = $stock->product_no."-申报价格大于250或等于0";
                continue;
            }

            // 申报价格必须小于250
            if ($stock->weight <= 0) {
                $errors[] = $stock->product_no."-重量小于或等于0";
                continue;
            }
            // 开始上架
            DB::beginTransaction();
            try {
                $check = DB::table('erp_stock')
                    -> where('erp_stock.product_id', $stock->id)
                    -> where('erp_stock.business_id',$business_id)
                    -> where('can_buy_num', '>', 0)
                    -> count();

                if ($check > 0) {
                    DB::table('erp_spu_sku_link')
                    ->where('erp_spu_sku_link.sku_id', $stock->id)
                    ->where('erp_spu_sku_link.spu_id', $spu_id)
                    -> leftJoin('erp_spu_list','erp_spu_sku_link.spu_id','erp_spu_list.id')
                    -> update([
                        'erp_spu_list.status' => 1
                    ]);
                }
                // 查询所有关联的SPU，统一修改状态为已上架
                DB::table('erp_spu_sku_link')
                    -> where('erp_spu_sku_link.spu_id', $spu_id)
                    -> where('erp_spu_sku_link.sku_id', $stock->id)
                    -> where('erp_spu_sku_link.flag', 0)
                    -> update([
                        'erp_spu_sku_link.status' => 1
                    ]);

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                $errors[] = '数据库插入错误';
            }
        }

        return $errors;

    }

    //新下架
    static function putOffProcess( $productIds, $spu_id, $business_id )
    {
        $errors = [];
        // 什么时候是不能下架的？下订单的时候要判断当前的状态
        foreach ($productIds as $key => $productId) {
            $stock = Stock::newGetStock($productId, $spu_id, $business_id);
            // 商品是否存在
            if(empty($stock)){
                $errors[] = '下架的商品不存在';
                continue;
            }
            // 未上架的商品是不能下架的
            if($stock->status === 0){
                $errors[] = $stock->product_no.'未上架';
                continue;
            }

            DB::beginTransaction();
            try {
                // 开始下架
                DB::table('erp_spu_sku_link')
                -> where('erp_spu_sku_link.sku_id', $stock->id)
                -> where('erp_spu_sku_link.spu_id', $spu_id)
                -> update([
                    'status' => 0
                ]);
                // sku 下架
                //     如果下架成功（包括数量卖完了调用下架），则去更新所有相关的spu状态
                //         拿到这些spuIDs,然后去搜索，如果旗下没有关联，则直接下架，如果至少有一个sku上架，就可以继续售卖
                
                $links = DB::table('erp_spu_sku_link')
                -> where('erp_spu_sku_link.spu_id', $spu_id)
                -> get();

                foreach ($links as $key => $link) {
                    // 找到旗下除了$stockId以外还有没有正常的单品
                    $otherPutOnNum = DB::table('erp_spu_sku_link')
                    -> leftJoin('erp_stock','erp_stock.product_id','erp_spu_sku_link.sku_id')
                    -> where('erp_spu_sku_link.spu_id', $link->spu_id)
                    -> where('erp_spu_sku_link.sku_id','!=', $stock->id)
                    -> where('erp_spu_sku_link.status','=', 1)
                    -> where('erp_stock.can_buy_num', '>',0)
                    -> count();

                    // 除此之外再无其他上架状态的SKU了
                    if (empty($otherPutOnNum)) {
                        // SPU修改为不显示
                        DB::table('erp_spu_list')
                        -> where('id', $link->spu_id)
                        -> update([
                            'status' => 0
                        ]);
                    }
                }
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                $errors[] = '数据库插入错误';
            }

        }

        return $errors;
    }

    // // 下架
    // /**
    //  * @param $stockIds  skuIds
    //  */
    // static function putOffProcess( $stockIds )
    // {
    //     $errors = [];
    //     // 什么时候是不能下架的？下订单的时候要判断当前的状态
    //     foreach ($stockIds as $key => $stockId) {
    //         $stock = Stock::getStock($stockId);
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

    //         DB::beginTransaction();
    //         try {
    //             // 开始下架
    //             DB::table('erp_stock')
    //             -> where('id', $stock->id)
    //             -> update([
    //                 'status' => 2
    //             ]);
    //             // sku 下架
    //             //     如果下架成功（包括数量卖完了调用下架），则去更新所有相关的spu状态
    //             //         拿到这些spuIDs,然后去搜索，如果旗下没有关联，则直接下架，如果至少有一个sku上架，就可以继续售卖
                
    //             $links = DB::table('erp_spu_sku_link')
    //             -> where('erp_spu_sku_link.sku_id', $stockId)
    //             -> get();

    //             foreach ($links as $key => $link) {
    //                 // 找到旗下除了$stockId以外还有没有正常的单品
    //                 $otherPutOnNum = DB::table('erp_spu_sku_link')
    //                 -> where('erp_spu_sku_link.spu_id', $link->spu_id)
    //                 -> where('erp_spu_sku_link.sku_id','!=', $stockId)
    //                 -> leftJoin('erp_stock','erp_spu_sku_link.sku_id','erp_stock.id')
    //                 -> where('erp_stock.status','=', 1)
    //                 -> count();

    //                 // 除此之外再无其他上架状态的SKU了
    //                 if (empty($otherPutOnNum)) {
    //                     // SPU修改为不显示
    //                     DB::table('erp_spu_list')
    //                     -> where('id', $link->spu_id)
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

}
