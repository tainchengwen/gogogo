<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductPrice extends Model
{
    //
    protected $table = 'erp_product_price';

    public $timestamps = false;

    protected $dateFormat = 'U';

    /**
     * 获得此sku的所有运费优惠
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function freights()
    {
        return $this->morphToMany('App\MarketFreight', 'link','erp_market_freight_link');
    }

    /**
     * 获得此sku的所有优惠券
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function coupons()
    {
        return $this->morphToMany('App\MarketCoupon', 'link','erp_market_coupon_link');
    }
}
