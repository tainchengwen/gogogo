<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GoodsList extends Model
{
    //
    protected $table = 'goods_list';


    protected $dateFormat = 'U';


    public function goodsPriceTemp()
    {
        return $this->hasMany(GoodsPriceTemp::class);
    }


    public function setPicturesAttribute($pictures)
    {
        if (is_array($pictures)) {
            $this->attributes['image_detail'] = json_encode($pictures);
        }
    }

    public function getPicturesAttribute($pictures)
    {
        return json_decode($pictures, true) ?: [];
    }
}
