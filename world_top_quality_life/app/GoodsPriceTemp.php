<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GoodsPriceTemp extends Model
{
    //
    protected $table = 'goods_price_temp';


    protected $dateFormat = 'U';

    public function goodslist()
    {
        return $this->belongsTo(GoodsList::class,'goods_id');
    }


}
