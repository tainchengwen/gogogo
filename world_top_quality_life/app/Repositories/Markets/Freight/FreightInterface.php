<?php

namespace App\Repositories\Markets\Freight;

interface FreightInterface
{
    /*
     * 运费接口
     */

    //生成具体策略的json
    public function policyJson($request);

    //添加运费策略到数据库
    public function add($request);

    //获取减免的运费
    public function getFreightDecr($v,$k,$mpExpressPrice);

    //检测是否符合优惠条件
    public function checkIsAvailable($freight,$relation,$arr);

    //当前运费优惠是否包邮
    public function isFreightFree();

}
