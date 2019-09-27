<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PriceTemp extends Model
{
    //
    protected $table = 'price_temp';


    protected $dateFormat = 'U';

    public function areaname()
    {
        return $this->belongsTo(AreaName::class,'area_id');
    }

}
