<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Price extends Model
{
    //
    protected $table = 'price_setting';


    protected $dateFormat = 'U';

    //获取配置
    public function getSetting(){

        $info = DB::table('price_setting') -> get();
        return $info->toArray();
    }


}
