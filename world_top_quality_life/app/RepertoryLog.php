<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RepertoryLog extends Model
{
    //
    protected $table = 'repertory_log';


    protected $dateFormat = 'U';


    //状态改变，修改日志
    static function addLog($repertory_info,$type){
            $log = RepertoryLog::where([
                'repertory_id' => $repertory_info -> id,
                'type' => $type,
            ]) -> first();
            if(!$log){
                //如果没log 新增log
                RepertoryLog::insert([
                    'repertory_id' => $repertory_info -> id,
                    'type' => $type,
                    'created_at' => time(),
                    'updated_at' => time(),
                ]);
            }
    }

}
