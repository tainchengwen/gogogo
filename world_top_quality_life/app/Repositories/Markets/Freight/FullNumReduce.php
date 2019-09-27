<?php

namespace App\Repositories\Markets\Freight;

class FullNumReduce implements FreightInterface
{
    use FreightTrait;

    public function policyJson($request)
    {
        $arr=[
            'full'=>intval($request->full),
            'decr'=>floatval($request->decr)
        ];

        return json_encode($arr);
    }

    public function getFreightDecr($v,$k,$mpExpressPrice)
    {
        if ($mpExpressPrice[$k]>=$v->decr){
            return $v->decr;
        }else{
            return $mpExpressPrice[$k];
        }
    }

    public function checkIsAvailable($freight,$relation,$arr)
    {
        $total=0;

        foreach ($freight->$relation as $v){
            if (isset($arr['number'][$relation][$v->id])) $total+=$arr['number'][$relation][$v->id];
        }

        return $total >= $freight->full;
    }

    public function isFreightFree()
    {
        return false;
    }
}
