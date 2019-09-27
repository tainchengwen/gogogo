<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MarketCoupon extends Model
{
    //
    protected $table = 'erp_market_coupons';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $fillable = [
        'name',
        'content',
        'type',
        'full',
        'decr',
        'is_plus',
        'is_need_receive',
        'number',
        'use_type',
        'use_term',
        'audience',
        'created_at',
        'begin_at',
        'end_at',
    ];

    public function getCreatedAtAttribute($value)
    {
        return date('Y-m-d H:i:s',$value);
    }

    public function getBeginAtAttribute($value)
    {
        return date('Y-m-d H:i:s',$value);
    }

    public function getEndAtAttribute($value)
    {
        return date('Y-m-d H:i:s',$value);
    }

    public function getAudienceAttribute()
    {
        $audience=explode(',',$this->attributes['audience']);
        return $audience;
    }

    /**
     * 获得此优惠券下的所有馆区
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function mpnames()
    {
        return $this->morphedByMany('App\MpName', 'link','erp_market_coupon_link');
    }

    /**
     * 获得此优惠券下的所有标签
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function tags()
    {
        return $this->morphedByMany('App\Tag', 'link','erp_market_coupon_link');
    }

    /**
     * 获得此优惠券下的所有分类
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function cats()
    {
        return $this->morphedByMany('App\ProductClass', 'link','erp_market_coupon_link');
    }

    /**
     * 获得此优惠券下的所有限时特价
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function specials()
    {
        return $this->morphedByMany('App\Special', 'link','erp_market_coupon_link');
    }

    /**
     * 获得此优惠券下的所有sku
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function skus()
    {
        return $this->morphedByMany('App\ProductPrice', 'link','erp_market_coupon_link');
    }

    /**
     * 获得此运费优惠下的所有套餐
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function unions()
    {
        return $this->morphedByMany('App\MpNameSpuList', 'link','erp_market_coupon_link');
    }

    /**
     * 拥有此优惠券的用户
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function wxusers()
    {
        return $this->belongsToMany('App\WxUser','erp_user_coupon_link','market_coupon_id','user_id')
            ->withPivot('status','created_at','invalid_at');
    }
}
