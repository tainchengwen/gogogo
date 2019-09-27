<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class WareHouse extends Model
{
    //
    protected $table = 'erp_warehouse';


    protected $dateFormat = 'U';

    // 根据仓库name和事业部id获取仓库id
    public static function getWarehouseId($warehousename, $business_id) {
        $info = DB::table('erp_warehouse')
                    -> where([
                        'business_id' => $business_id,
                        'mp_name'     => $warehousename
                        ])
                    -> get();
        return $info;
    }

}
