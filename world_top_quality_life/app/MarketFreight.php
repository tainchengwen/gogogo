<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MarketFreight extends Model
{
    //
    protected $table = 'erp_market_freight';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $fillable = ['name','content','type','policy','created_at','begin_at','end_at','vip'];

    protected $appends = ['full','decr','audience'];

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

    public function getFullAttribute()
    {
        return json_decode($this->attributes['policy'],true)['full'];
    }

    public function getDecrAttribute()
    {
        $policy=json_decode($this->attributes['policy'],true);
        return isset($policy['decr'])?$policy['decr']:'';
    }

    public function getAudienceAttribute()
    {
        $audience=explode(',',$this->attributes['vip']);
        return $audience;
    }

    /**
     * 获得此运费优惠下的所有馆区
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function mpnames()
    {
        return $this->morphedByMany('App\MpName', 'link','erp_market_freight_link');
    }

    /**
     * 获得此运费优惠下的所有标签
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function tags()
    {
        return $this->morphedByMany('App\Tag', 'link','erp_market_freight_link');
    }

    /**
     * 获得此运费优惠下的所有分类
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function cats()
    {
        return $this->morphedByMany('App\ProductClass', 'link','erp_market_freight_link');
    }

    /**
     * 获得此运费优惠下的所有限时特价
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function specials()
    {
        return $this->morphedByMany('App\Special', 'link','erp_market_freight_link');
    }

    /**
     * 获得此运费优惠下的所有sku
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function skus()
    {
        return $this->morphedByMany('App\ProductPrice', 'link','erp_market_freight_link');
    }

    /**
     * 获得此运费优惠下的所有套餐
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function unions()
    {
        return $this->morphedByMany('App\MpNameSpuList', 'link','erp_market_freight_link');
    }
}
