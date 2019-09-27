<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AreaPriceLog extends Model
{
    //
    protected $table = 'area_price_log';


    protected $dateFormat = 'U';

    public function areas()
    {
        return $this->hasOne(AreaName::class,'area_id');
    }

    //添加日志
    public function addLog($area_id,$price,$type,$package_num = '',$remark=''){
        DB::table('area_price_log') -> insert([
            'type' => $type,
            'price' => $price,
            'package_num' => $package_num,
            'created_at' => time(),
            'updated_at' => time(),
            'area_id' => $area_id,
            'remark' => $remark
        ]);
    }




}
