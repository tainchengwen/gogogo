<?php

namespace App\Repositories\Markets;

class FreightFactory
{
    protected $typeList;

    /**
     * 运费类型
     */
    public function __construct()
    {
        $this->typeList=[
            '1'=>'App\Repositories\Markets\Freight\FullReduce',    //满金额减
            '2'=>'App\Repositories\Markets\Freight\FullFree',    //满金额包邮
            '3'=>'App\Repositories\Markets\Freight\FullNumReduce',    //满数量减
            '4'=>'App\Repositories\Markets\Freight\FullNumFree',    //满数量包邮
        ];
    }

    /**
     * 返回相应的运费策略实例
     * @param $type
     * @return mixed
     * @throws \Exception
     * @return
     */
    public function getFreightInstance($type)
    {
        if (!array_key_exists($type, $this->typeList)) {
            throw new \Exception('该运费优惠类型不存在！');
        }
        $className = $this->typeList[$type];

        return new $className();
    }
}
