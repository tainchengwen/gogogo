<?php

namespace App\Repositories;

use DB;

class SaleDetailData extends BaseRepository
{

    /**
     * @description 检索某一时间范围内的销售记录（以发货记录为准）
     * @param int $start_time 查询数据的起始时间
     * @param int $end_time 查询数据的结束时间
     * @param int $class_id 查询指定商品分类的id
     * @return array 返回检索到的数据
     */
    public function searchData(int $business_id, int $start_time, int $end_time, int $class_id = -1) :array
    {
        return DB::table('erp_stock_order as order')
            ->leftJoin('erp_stock_order_info as orderInfo', 'orderInfo.stock_order_id', 'order.id')
            ->leftJoin('erp_stock_order_info_receive as orderInfoReceive', 'orderInfoReceive.stock_order_info_id', 'orderInfo.id')
            ->leftJoin('erp_receive_goods_record as receiveGoodsRecord', 'receiveGoodsRecord.id', 'orderInfoReceive.receive_goods_record_id')
            ->leftJoin('erp_deliver_goods_record_info as deliverRecordInfo', 'deliverRecordInfo.stock_order_info_receive_id', 'orderInfoReceive.id')
            ->leftJoin('erp_deliver_goods_record as deliverRecord', 'deliverRecord.id', 'deliverRecordInfo.deliver_goods_record_id')
            ->leftJoin('erp_product_list as product', 'product.id', 'orderInfo.product_id') // 商品名称
            ->leftJoin('erp_product_class as brand', 'product.brand_id', 'brand.id') // 品牌
            ->leftJoin('erp_product_class as productClass', 'productClass.id', 'product.class_id') // 类别
            ->leftJoin('erp_product_class as series', 'series.id', 'product.series_id') // 系列
            ->leftJoin('wxuser', 'order.user_id', 'wxuser.id') // 客户
            ->where([
                'order.flag' => 0,
                'order.send_status' => 1, // 发货完成的
                'orderInfo.flag' => 0,
                'orderInfoReceive.flag' => 0,
                'receiveGoodsRecord.flag' => 0,
                'deliverRecordInfo.flag' => 0,
                'deliverRecord.flag' => 0,
                'wxuser.in_black_list' => 0,
                'order.business_id' => $business_id
            ])
            ->whereBetween('deliverRecord.created_at', [$start_time, $end_time])
            ->where(function ($query) use ($class_id) {
                if ($class_id > -1) {
                    $query->where(['productClass.id' => $class_id]);
                }
            })
            ->select([
                'order.order_num', // 订单编号
                'wxuser.nickname', // 客户名称
                'deliverRecordInfo.send_num as number', // 发货数量
                'orderInfo.price', // 售价
                'receiveGoodsRecord.cost', // 成本
                'product.product_name', // 商品名称
                'product.product_no', // 商品编号
                'product.model', // 型号
                'brand.name as brand_name', // 品牌
                'productClass.name as category_name', // 类别
                'series.name as series_name', // 系列
                'deliverRecord.created_at', // 发货日期
                'productClass.id as category_id', // 类别id
            ])
            ->orderBy('productClass.id', 'asc')
            ->orderBy('deliverRecord.created_at', 'desc')
            ->get()
            ->toArray();
    }
}
