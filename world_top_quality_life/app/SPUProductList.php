<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SPUProductList extends Model
{
    // 商品-单品关联表
    protected $table = 'erp_spu_product_list';

    protected $dateFormat = 'U';
}