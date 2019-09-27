<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class FreightRepository extends BaseRepository
{
    public function __construct(AddressRepository $addressRepository)
    {
        $this->addressRepository = $addressRepository;
    }

    /**
     * 运费计算
     */
    public function computedFreight($cartIds, $addressId)
    {
        // 目的地省份
        $address               = $this->addressRepository->get($addressId);
        $shop_mp_provinces_map = config('admin.shop_mp_provinces_map');
        $province              = $shop_mp_provinces_map[$address->province];

        // sku列表
        $list = DB::table('erp_shop_cart as cart')
            -> whereIn('cart.id', $cartIds)
            // spu表
            -> leftJoin('erp_mp_name_spu_link', 'cart.spuId', 'erp_mp_name_spu_link.id')
            -> leftJoin('erp_spu_list', 'erp_mp_name_spu_link.spu_id', 'erp_spu_list.id')
            // sku表
            -> leftJoin('erp_product_price', 'cart.skuId', 'erp_product_price.id')
            -> leftJoin('erp_product_list', 'erp_product_price.product_id', 'erp_product_list.id')
            // 馆区表
            -> leftJoin('erp_mp_name', 'erp_mp_name_spu_link.mp_name_id', 'erp_mp_name.id')
            -> leftJoin('freight_temp_name','erp_mp_name.freight_temp_name_id','freight_temp_name.id')
            -> select([
            // 购物车id
            'cart.id as cartId',
            'cart.num',
            'cart.skuId as skuId',
            // // 仓库Id
            // 'erp_spu_list.warehouse_id',
            // 馆区Id
            'erp_mp_name_spu_link.mp_name_id',
            // 运费模板类型
            'freight_temp_name.country',
            // 当前商品的价格申报价格
            'erp_product_list.declared_price',
            'erp_product_list.id as productId',
            // sku重量
            'erp_product_list.weight',
            // 是否有重量要求
            'freight_temp_name.is_weight',
            'freight_temp_name.id as freight_temp_name_id',
            // 重量要求是多少
            'freight_temp_name.weight_info',
            // 该仓库价值限制
            'freight_temp_name.package_limit',
            ])
            -> get();

        // 按照仓库ID，分组
        $groupList = $this->arrayGrouping($list, 'mp_name_id');

        // 然后按照馆区计算每组的运费
        $freight = 0;

        //各馆区的运费信息
        $mp_freights=[];
        foreach ($groupList as $key => $value) {
            $freightWarehouse = $this->computedByWarehouse($value, $province);
            $freight = $this->math_add($freight, $freightWarehouse);
            $mp_freights[$key]=$freightWarehouse;
        }

        // 运费向上取整
        return ['total_freight'=>ceil($freight),'mp_freights'=>$mp_freights];
    }

    // 通过馆区计算运费
    private function computedByWarehouse($list, $province)
    {
        // 运费模板Id
        $freight_temp_name_id = (float)$list[0]->freight_temp_name_id;
        // 0国内1国外
        $country              = $list[0]->country;
        // 馆区关联的模板
        $freight_temp = DB::table('freight_temp')
        -> where('temp_name_id', $freight_temp_name_id)
        -> where('flag', 0)
        -> where('address','like','%'.$province.'%')
        -> select([
            'first_price',
            'firstWeight_cost',
            'first_weight',
            'secondWeight_cost',
            'secend_price',
            'secend_weight'
        ])
        -> first();

        if ($country === 1) {
            // 国外计算方式
            return $this->abroad($list, $freight_temp);
        } else {
            // 国内计算方式
            return $this->mainland($list, $freight_temp);
        }
    }

    // 境内运费
    private function mainland($list, $freight_temp)
    {
        // 平铺购物车
        $list = $this->tileCart($list);
        // 把所有的重量加起来
        return $this->computedByWeight($list, $freight_temp);
    }

    // 跨境运费
    private function abroad($list, $freight_temp)
    {
        // 是否有重量要求0没要求1有要求(暂时不做)
        // $is_weight            = $list[0]->is_weight;
        // $weight_info          = (float)$list[0]->is_weight;

        // 单个包裹的最大申报价值上限
        $limit = (float)$list[0]->package_limit;

        // 重置float
        foreach ($list as $key => $value) {
            $list[$key]->declared_price = (float)$value->declared_price;
        }

        // 平铺购物车
        $list       = $this->tileCart($list);
        // 按照价格倒序
        $sortResult = $this->arrayMultisortMy($list, 'declared_price', 'DESC');

        // 按照单个包裹申报价值上限-拆包裹
        $orders     = $this->unpack($sortResult, $limit);

        // 根据目的地运费模板和包裹重量计算运费
        $freightWarehouse = 0;
        foreach ($orders as $key => $order) {
            $freightOrder     = $this->computedByWeight($order, $freight_temp);
            $freightWarehouse = $this->math_add($freightWarehouse, $freightOrder);
        }

        return $freightWarehouse;
    }

    // 平铺多数量sku
    private function tileCart($list)
    {
        // 根据可能的一个商品买多个, 所以直接平铺
        foreach ($list as $key => $value) {
            if ($value->num > 1) {
                $list[$key]->index = 1;
                for ($i=0; $i < $value->num - 1; $i++) { 
                    $newValue =  clone $value;
                    $newValue->index = 2 + $i;
                    $list[] = $newValue;
                }
            }
        }
        return $list;
    }

    // 根据重量计算运费
    private function computedByWeight($list, $freight_temp)
    {
        // 初始重量(纸箱重量)
        $sumWeight = 0.2;
        foreach ($list as $key => $value) {
            $sumWeight = $this->math_add($sumWeight,$value->weight);
        }
        $freight = 0;
        // 该包裹是否大于首重
        if ($this->math_comp($sumWeight,$freight_temp->first_weight) <= 0) {
            // 就是首费
            $freight = $this->math_add($freight,$freight_temp->first_price);
        } else {
            // 超出首重的重量
            $overWeight   = $this->math_sub($sumWeight,$freight_temp->first_weight);
            // 几倍的续重
            $n            = ceil( $this->math_div($overWeight, $freight_temp->secend_weight) );
            // 续重总价
            $overPrice    = $this->math_mul($n, $freight_temp->secend_price);
            // 该包裹总运费
            $freight = $this->math_add($freight_temp->first_price, $overPrice);
        }
        return $freight;
    }

    // 拆包裹
    private function unpack($list, $limit)
    {
        $orders = [];

        while (count($list)) {
            $tmp = [];
            $tmp[] =  array_shift($list);

            foreach ($list as $key => $value) {
                if ($this->isUnderLimit($tmp, $value, $limit)) {
                    $tmp[] = $value;
                    unset($list[$key]);
                }
            }
            $orders[] = $tmp;

        }
        return $orders;
    }

    // 当前包裹+新商品价格，是否小于限制
    private function isUnderLimit($current, $add, $limit)
    {
        $sum = 0;
        foreach ($current as $key => $value) {
            $sum = $this->math_add($sum,$value->declared_price);
        }
        $newSum = $this->math_add($sum,$add->declared_price);
        
        return  $this->math_comp($newSum,$limit) <=0 ;
    }
}