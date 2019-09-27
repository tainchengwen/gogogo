<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ReceiveGoodsRecord extends Model
{
    //
    protected $table = 'erp_receive_goods_record';


    protected $dateFormat = 'U';


    static function getReceiveList($request){
        $list = DB::table('erp_receive_goods_record as record')
            -> leftJoin('erp_product_list','record.product_id','erp_product_list.id')
            -> leftJoin('erp_storehouse','record.store_house_id','erp_storehouse.id')
            -> leftJoin('erp_warehouse','erp_storehouse.warehouse_id','erp_warehouse.id')
            //商品去找类别、品牌、系列
            //类别
            -> leftJoin('erp_product_class as product_class','erp_product_list.class_id','product_class.id')
            //品牌
            -> leftJoin('erp_product_class as product_brand','erp_product_list.brand_id','product_brand.id')
            //系列
            -> leftJoin('erp_product_class as product_series','erp_product_list.series_id','product_series.id')

            -> where([
                'record.business_id' => $request -> business_id,
                'record.store_house_id' => $request -> store_house_id,
                'record.product_id' => $request -> product_id,

            ])
            -> select([
                'erp_product_list.*',
                'erp_product_list.number as product_num',
                'record.number as enter_num', //入库数量
                'record.number as current_num', //可售数量=当前数量
                'record.created_at',
                'erp_storehouse.name as storehouse_name',
                'erp_warehouse.name as warehouse_name',
                'product_class.name as product_class_name',
                'product_brand.name as product_brand_name',
                'product_series.name as product_series_name',
            ])
            -> orderBy('record.id','desc')
            -> get();



        return $list;
    }
}
