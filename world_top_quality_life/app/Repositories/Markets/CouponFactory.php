<?php

namespace App\Repositories\Markets;

class CouponFactory
{
    protected $typeList;

    /**
     * 优惠券类型
     */
    public function __construct()
    {
        $this->typeList=[
            '1'=>'App\Repositories\Markets\Coupon\Freight',    //运费优惠券
            '2'=>'App\Repositories\Markets\Coupon\Normal',    //普通优惠券
        ];
    }

    /**
     * 返回相应的优惠券实例
     * @param $type
     * @return mixed
     * @throws \Exception
     * @return
     */
    public function getCouponInstance($type)
    {
        if (!array_key_exists($type, $this->typeList)) {
            throw new \Exception('该优惠券类型不存在！');
        }
        $className = $this->typeList[$type];

        return new $className();
    }
}
