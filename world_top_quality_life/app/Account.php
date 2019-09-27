<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Account extends Model
{
    //
    protected $table = 'erp_account';


    protected $dateFormat = 'U';


    //添加账户金额变化日志
    static function addLog($arr = [

    ]){
        DB::table('erp_account_log')
            -> insertGetId([
                'account_id' => isset($arr['account_id'])?$arr['account_id']:0,
                'user_id' => isset($arr['user_id'])?$arr['user_id']:0,
                'log_type' => isset($arr['log_type'])?$arr['log_type']:0,
                'price' => isset($arr['price'])?$arr['price']:0,
                'parameter_id' => isset($arr['parameter_id'])?$arr['parameter_id']:0,
                'business_id' => isset($arr['business_id'])?$arr['business_id']:0,
                'created_at' => time(),
                'updated_at' => time(),
            ]);
        return true;
    }



}
