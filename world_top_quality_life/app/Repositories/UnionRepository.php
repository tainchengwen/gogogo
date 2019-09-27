<?php

namespace App\Repositories;

use DB;

class UnionRepository extends BaseRepository
{
    public function __construct()
    {
    }

    /**
     * 检测套餐是否正常
     * @param $spu
     * @return boolean
     */
    public function checkUnionStatus($spu,$type='')
    {
        $prices=DB::table('erp_product_price')
            -> leftJoin('erp_spu_sku_link','erp_product_price.product_id','erp_spu_sku_link.sku_id')
            -> leftJoin('erp_mp_name_spu_link', function($q) {
                $q->on('erp_spu_sku_link.spu_id', '=', 'erp_mp_name_spu_link.spu_id')
                    ->on('erp_product_price.mp_name_id', '=', 'erp_mp_name_spu_link.mp_name_id');
            })
            -> where('erp_spu_sku_link.spu_id', $spu->spu_id)
            -> where('erp_product_price.mp_name_id', $spu->mp_name_id)
            -> where('erp_spu_sku_link.flag', 0)
            -> where('erp_mp_name_spu_link.flag', 0)
            -> where('erp_product_price.union_status', 1)
            -> select([
                'erp_product_price.id',
                'erp_product_price.flag',
                'erp_product_price.status',
                'erp_product_price.is_show',
                'erp_product_price.has_stock',
                'erp_product_price.union_num',
                //'erp_product_price.union_status',
                'erp_mp_name_spu_link.union_flag',
            ])->get();


        foreach ($prices as $price){
            if ($price->flag == 1 || $price->status == 0 || $price->is_show == 0 || $price->has_stock <= 0
                || $price->union_num <= 0 || $price->has_stock < $price->union_num){
                return false;
            }
            if($type != 'special' && $price->union_flag == 1 ){
                return false;
            }
        }

        return true;
    }

    /**
     * 拼接套餐总价格信息
     * @param $skus
     * @return $sku
     */
    public function joinUnionInfo($skus)
    {
        $sku=new \stdClass();//拼接套餐信息
        $sku->id=null;
        $sku->product_no=null;
        $sku->product_name=null;
        $sku->image=null;
        $sku->can_buy_num=0;
        $sku->currentPrice=0;
        $sku->nextPrice=0;
        $sku->originPrice=0;
        $stock_status=0;
        foreach ($skus as $k=>$v){
            if ($v->can_buy_num < $v->union_num){
                $stock_status=1;
                break;
            }
            if($k==0){
                $sku->id=$v->id;
                $sku->product_no=$v->product_no;
                $sku->product_name=$v->product_name;
                $sku->image=$v->image;
                $sku->can_buy_num=floor($v->can_buy_num / $v->union_num);
            }else{
                $sku->can_buy_num=min($sku->can_buy_num,floor($v->can_buy_num / $v->union_num));
            }
            $sku->currentPrice += ($v->currentPrice*$v->union_num);
            $sku->nextPrice += ($v->nextPrice*$v->union_num);
            $sku->originPrice += ($v->originPrice*$v->union_num);
        }
        if ($stock_status == 1 || $sku->can_buy_num < 1){
            return false;
        }
        return $sku;
    }
}
