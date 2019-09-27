<?php

namespace App\Repositories;

use App\MarketCoupon;
use App\Repositories\Markets\CouponFactory;
use App\Repositories\Markets\FreightFactory;
use App\Repositories\OssRepository;
use App\Special;
use App\ProductPrice;
use App\MpName;
use App\ProductClass;
use App\Tag;
use App\MpNameSpuList;
use App\WxUser;
use DB;

class MarketRepository extends BaseRepository
{
    /**
     * 营销相关的入口.
     */
    public function __construct()
    {
        $this->initClass(new FreightFactory() ,new CouponFactory());
    }

    private function initClass(
        $freightFactory,
        $couponFactory
    ){
        $this->freight=$freightFactory;
        $this->coupon=$couponFactory;
    }

    /**
     * 添加运费数据
     * @param $request
     * @throws \Exception
     * @return
     */
    public function freightAdd($request)
    {
        try{
            $result=$this->freight->getFreightInstance($request->type)->add($request);
        }catch (\Exception $e){
            return [
                'status'=>0,
                'msg'=>$e->getMessage()
            ];
        }
        return $result;
    }

    /**
     * 获得减免的运费
     * @param $v
     * @param $k
     * @param $mpExpressPrice
     * @return mixed
     * @throws \Exception
     */
    public function getFreightDecr($v, $k, $mpExpressPrice)
    {
        $result=$this->freight->getFreightInstance($v->type)->getFreightDecr($v,$k,$mpExpressPrice);

        return $result;
    }

    /**
     * 判断运费优惠是否包邮
     * @param $type
     * @return mixed
     * @throws \Exception
     */
    public function isFreightFree($type)
    {
        return $this->freight->getFreightInstance($type)->isFreightFree();
    }

    /**
     * 获取最优惠的运费
     * @param $arr
     * @return mixed|string
     * @throws \Exception
     */
    public function getBestFreight($arr)
    {
        //先获取当前可用的优惠
        $freights=$this->getAvailableFreights($arr);

        //再选出可用优惠中最优惠的
        $best_freight='';
        foreach($freights as $freight){
            //判断当前运费优惠是否包邮
            $bool=$this->isFreightFree($freight->type);
            if ($bool){
                $best_freight=$freight;//如果包邮，那就是最优惠的
                break;
            }
            if (!empty($best_freight)){
                if ($best_freight->decr < $freight->decr) $best_freight=$freight;//如果不包邮，看哪个优惠减运费减得多
            }else{
                $best_freight=$freight;
            }
        }
        return $best_freight;
    }


    /**
     * 获取当前可用的运费优惠
     * @param $arr
     * @return mixed
     * @throws \Exception
     */
    public function getAvailableFreights($arr)
    {
        //先取出当前页订单的各类id集合
        $special_id_arr=isset($arr['specials_ids'])?$arr['specials_ids']:[];
        $tag_id_arr=isset($arr['tags_ids'])?$arr['tags_ids']:[];
        $union_id_arr=isset($arr['unions_ids'])?$arr['unions_ids']:[];
        $sku_id_arr=isset($arr['skus_ids'])?$arr['skus_ids']:[];
        $cat_id_arr=isset($arr['cats_ids'])?$arr['cats_ids']:[];
        $mp_id_arr=isset($arr['mpnames_ids'])?$arr['mpnames_ids']:[];

        //再分别获取对应的类型关联的运费优惠
        $special_freights=$this->getFreightsByModel(Special::class,$special_id_arr);
        $sku_freights=$this->getFreightsByModel(ProductPrice::class,$sku_id_arr);
        $mp_freights=$this->getFreightsByModel(MpName::class,$mp_id_arr);
        $cat_freights=$this->getFreightsByModel(ProductClass::class,$cat_id_arr);
        $tag_freights=$this->getFreightsByModel(Tag::class,$tag_id_arr);
        $union_freights=$this->getFreightsByModel(MpNameSpuList::class,$union_id_arr);

        //接着检测获取到的优惠是否满足优惠条件
        $available_sku_freights=$this->checkAvailable($sku_freights,'skus',$arr);
        $available_special_freights=$this->checkAvailable($special_freights,'specials',$arr);
        $available_mp_freights=$this->checkAvailable($mp_freights,'mpnames',$arr);
        $available_cat_freights=$this->checkAvailable($cat_freights,'cats',$arr);
        $available_tag_freights=$this->checkAvailable($tag_freights,'tags',$arr);
        $available_union_freights=$this->checkAvailable($union_freights,'unions',$arr);

        //最后把满足条件的优惠条件组合去重并返回
        $available_freights=$available_sku_freights->concat($available_special_freights)
            ->concat($available_mp_freights)
            ->concat($available_cat_freights)
            ->concat($available_tag_freights)
            ->concat($available_union_freights)
            ->unique()->sortBy('id');

        return $available_freights;
    }

    /**
     * 检测当前运费是否可用
     * @param $freights
     * @param $relation
     * @param $arr
     * @return mixed
     * @throws \Exception
     */
    public function checkAvailable($freights, $relation, $arr)
    {
        foreach ($freights as $k=>$freight){
            $bool=$this->freight->getFreightInstance($freight->type)->checkIsAvailable($freight,$relation,$arr);
            if (!$bool) unset($freights[$k]);
        }

        //留下满足vip等级的
        $final_freights=$freights->filter(function ($value, $key) use($arr) {
            if(empty($value->vip)){
                return true;
            }
            return in_array($arr['vip'],$value->audience);
        });

        return $final_freights;
    }

    /**
     * 获取当前页面商品正在参与的运费优惠
     * @param $arr
     * @return mixed
     */
    public function getPartakeFreight($arr)
    {
        $special_id_arr=isset($arr['special_id'])?$arr['special_id']:[];//现在限时特价统统包邮
        $price_id_arr=isset($arr['price_id'])?$arr['price_id']:[];
        $mp_id_arr=isset($arr['mp_id'])?$arr['mp_id']:[];
        $cat_id_arr=isset($arr['cat_id'])?$arr['cat_id']:[];
        $tag_id_arr=isset($arr['tag_ids'])?$arr['tag_ids']:[];
        $union_id_arr=isset($arr['union'])?$arr['union']:[];

        $special_freights=$this->getFreightsByModel(Special::class,$special_id_arr);
        $sku_freights=$this->getFreightsByModel(ProductPrice::class,$price_id_arr);
        $mp_freights=$this->getFreightsByModel(MpName::class,$mp_id_arr);
        $cat_freights=$this->getFreightsByModel(ProductClass::class,$cat_id_arr);
        $tag_freights=$this->getFreightsByModel(Tag::class,$tag_id_arr);
        $union_freights=$this->getFreightsByModel(MpNameSpuList::class,$union_id_arr);

        $partake_freights=$special_freights->concat($sku_freights)
            ->concat($mp_freights)
            ->concat($cat_freights)
            ->concat($tag_freights)
            ->concat($union_freights)
            ->unique('id')->sortBy('id');
        //留下满足vip等级的
        $final_freights=$partake_freights->filter(function ($value, $key) use($arr) {
            if(empty($value->vip)){
                return true;
            }
            return in_array($arr['vip'],$value->audience);
        });

        return $final_freights;
    }

    /**
     * 根据模型获取相关联的运费活动
     * @param $model
     * @param $id
     * @return \Illuminate\Support\Collection
     */
    public function getFreightsByModel($model, $id)
    {
        $result=$model::with(['freights'=>function($q){
            $q->where([
                ['flag','=',0],
                ['begin_at','<=',time()],
                ['end_at','>=',time()],
            ]);
        }])->find($id);

        //如果有多个的话
        if (!is_numeric($id)){
            $freights_col=collect();
            foreach ($result as $v){
                foreach ($v->freights as $freight){
                    $freights_col->push($freight);
                }
            }
            return $freights_col->unique('id');
        }

        return $result->freights;
    }

    /**
     * 添加优惠券数据
     * @param $request
     * @throws \Exception
     * @return
     */
    public function couponAdd($request)
    {
        try{
            $result=$this->coupon->getCouponInstance($request->type)->add($request);
        }catch (\Exception $e){
            return [
                'status'=>0,
                'msg'=>$e->getMessage()
            ];
        }
        return $result;
    }

    /**
     * 优惠券弹窗
     * @param $request
     * @return array
     */
    public function getCouponPopup($request)
    {
        $coupon_popup=[];
        $coupon_popup['is_need_popup']=0;//默认不需要弹窗
        //如果今天弹过窗，就不弹了
        if ($request->user->last_popup_at == date('Ymd')){
            return $coupon_popup;
        }

        $now=time();
        //获取当前用户有效的优惠券
        //更新优惠券过期状态
        event('coupons.checkInvalid',['user_id'=>$request->user->wxUserId]);
        $wxuser=WxUser::find($request->user->wxUserId);
        //如果不存在,则组装游客信息
        if (!$wxuser){
            $wxuser=WxUser::assembleVisitor();
        }

        $coupons=$wxuser->coupons()
            ->wherePivot('status',0)
            ->get();

        //如果是新人
        if ($request->user->is_new){
            //首先看看有没有可用的新人优惠券
            $has_new=collect();
            if ($coupons->isNotEmpty()){
                foreach ($coupons as $coupon){
                    if (empty($coupon->audience[0]) || in_array('new',$coupon->audience)){//audience为空代表全部人可用,包括新人
                        $coupon->invalid_at=$coupon->pivot->invalid_at;
                        $has_new->push($coupon);
                    }
                }
            }
            //如果有可用的新人优惠券，弹窗显示有可用的新人大礼包
            if ($has_new->isNotEmpty()){
                $coupon_popup['new']=['sum'=>$has_new->sum('decr'),'invalid_at'=>date('Y-m-d',$has_new->max('invalid_at'))];
                //更新该用户上次弹窗时间
                $wxuser->last_popup_at=date('Ymd');
                $wxuser->save();
                $coupon_popup['is_need_popup']=1;
                return $coupon_popup;
            }
        }
        //否则弹窗送符合条件的优惠券
        $has_coupons_ids=$coupons->pluck('id');
        $can_receive_coupons=MarketCoupon::whereNotIn('id',$has_coupons_ids)
            ->where('is_need_receive',0)
            ->whereColumn('number', '>', 'receive_count')
            ->where('flag', 0)
            ->where('begin_at', '<=', $now)
            ->where('end_at', '>=', $now)
            ->where(function ($q) use ($request){
                $q->where('audience','=','')
                    ->orWhere('audience','like','%'.$request->user->market_class.'%');
                if ($request->user->is_new){
                    $q->orWhere('audience','like','%new%');
                }
            })
            ->get();
        if ($can_receive_coupons->isNotEmpty()){
            //添加符合条件的优惠券到此用户
            $attach_normal_data=[];
            $attach_new_data=[];
            $can_receive_normal_coupons=collect();
            $can_receive_new_coupons=collect();
            foreach ($can_receive_coupons as $coupon){
                if ($coupon->use_type == 1){
                    $invalid_at = $now + ($coupon->use_term * 86399);
                }else{
                    $invalid_at = $coupon->use_term;
                }
                $coupon->invalid_at=$invalid_at;
                if (empty($coupon->audience[0]) || in_array('new',$coupon->audience)){
                    $attach_new_data[$coupon->id]=[
                        'status'=>0,
                        'created_at'=>$now,
                        'invalid_at'=>$invalid_at,
                    ];
                    $can_receive_new_coupons->push($coupon);
                }
                if (empty($coupon->audience[0]) || in_array($request->user->market_class,$coupon->audience)){
                    $attach_normal_data[$coupon->id]=[
                        'status'=>0,
                        'created_at'=>$now,
                        'invalid_at'=>$invalid_at,
                    ];
                    $can_receive_normal_coupons->push($coupon);
                }
            }
            if ($request->user->is_new && $can_receive_new_coupons->isNotEmpty()){
                $coupon_popup['new']=['sum'=>$can_receive_new_coupons->sum('decr'),'invalid_at'=>date('Y-m-d',$can_receive_new_coupons->max('invalid_at'))];
                $wxuser->coupons()->attach($attach_new_data);
                //更新优惠券已领取数量
                MarketCoupon::whereIn('id',array_keys($attach_new_data))->increment('receive_count');
            }else{
                $coupon_popup['normal']=$can_receive_normal_coupons;
                $wxuser->coupons()->attach($attach_normal_data);
                //更新优惠券已领取数量
                MarketCoupon::whereIn('id',array_keys($attach_normal_data))->increment('receive_count');
            }
            $coupon_popup['is_need_popup']=1;
            //更新该用户上次弹窗时间
            $wxuser->last_popup_at=date('Ymd');
            $wxuser->save();
        }

        return $coupon_popup;
    }

    /**
     * 获取详情页需要领取的优惠券
     * @param $arr
     * @return mixed
     */
    public function getPartakeCoupon($arr)
    {
        //$special_id_arr=isset($arr['special_id'])?$arr['special_id']:[];//暂时不做限时特价
        $price_id=isset($arr['price_id'])?$arr['price_id']:'';
        $mp_id=isset($arr['mp_id'])?$arr['mp_id']:'';
        $cat_id=isset($arr['cat_id'])?$arr['cat_id']:'';
        $tag_id=isset($arr['tag_ids'])?$arr['tag_ids']:[];//这个参数是集合或数组
        $union_id=isset($arr['union'])?$arr['union']:'';
        $is_new=isset($arr['is_new'])?$arr['is_new']:0;
        $vip=isset($arr['vip'])?$arr['vip']:'9999';
        $user_id=isset($arr['user_id'])?$arr['user_id']:0;

        //当前详情页商品关联的优惠券
        $linked_coupons=DB::table('erp_market_coupon_link')
            ->where(function ($q) use($price_id){
                $q->where('link_type','App\ProductPrice')->where('link_id',$price_id);
            })
            ->orWhere(function ($q) use($mp_id){
                $q->where('link_type','App\MpName')->where('link_id',$mp_id);
            })
            ->orWhere(function ($q) use($cat_id){
                $q->where('link_type','App\ProductClass')->where('link_id',$cat_id);
            })
            ->orWhere(function ($q) use($tag_id){
                $q->where('link_type','App\Tag')->whereIn('link_id',$tag_id);
            })
            ->orWhere(function ($q) use($union_id){
                $q->where('link_type','App\MpNameSpuList')->where('link_id',$union_id);
            })
            ->distinct()
            ->pluck('market_coupon_id');

        //所有的可领取优惠券
        /*$coupons=DB::table('erp_market_coupons')
            ->where('is_need_receive',1)
            ->whereColumn('number', '>', 'receive_count')
            ->where('flag', 0)
            ->where('begin_at', '<=', time())
            ->where('end_at', '>=', time())
            ->whereIn('id', $linked_coupons)
            ->where(function ($q) use ($is_new,$vip){
                $q->where('audience','=','')
                    ->orWhere('audience','like','%'.$vip.'%');
                if ($is_new){
                    $q->orWhere('audience','like','%new%');
                }
            })
            ->get();*/
        $coupons=DB::table('erp_market_coupons')
            ->leftJoin('erp_market_coupon_link','erp_market_coupons.id','erp_market_coupon_link.market_coupon_id')
            //分类
            ->leftJoin('erp_product_class',function($q){
                $q->on('erp_market_coupon_link.link_id','erp_product_class.id')
                    ->where('erp_market_coupon_link.link_type','App\ProductClass');
            })
            //馆区
            ->leftJoin('erp_mp_name',function($q){
                $q->on('erp_market_coupon_link.link_id','erp_mp_name.id')
                    ->where('erp_market_coupon_link.link_type','App\MpName');
            })
            //标签
            ->leftJoin('erp_tags',function($q){
                $q->on('erp_market_coupon_link.link_id','erp_tags.id')
                    ->where('erp_market_coupon_link.link_type','App\Tag');
            })
            //sku
            ->leftJoin('erp_product_price',function($q){
                $q->on('erp_market_coupon_link.link_id','erp_product_price.id')
                    ->where('erp_market_coupon_link.link_type','App\ProductPrice');
            })
            ->leftjoin('erp_product_list','erp_product_price.product_id','erp_product_list.id')
            //套餐
            ->leftJoin('erp_mp_name_spu_link',function($q){
                $q->on('erp_market_coupon_link.link_id','erp_mp_name_spu_link.id')
                    ->where('erp_market_coupon_link.link_type','App\MpNameSpuList');
            })
            ->leftjoin('erp_spu_list','erp_mp_name_spu_link.spu_id','erp_spu_list.id')
            ->where('erp_market_coupons.is_need_receive',1)
            ->whereColumn('erp_market_coupons.number', '>', 'erp_market_coupons.receive_count')
            ->where('erp_market_coupons.flag', 0)
            ->where('erp_market_coupons.begin_at', '<=', time())
            ->where('erp_market_coupons.end_at', '>=', time())
            ->whereIn('erp_market_coupons.id', $linked_coupons)
            ->where(function ($q) use ($is_new,$vip){
                $q->where('erp_market_coupons.audience','=','')
                    ->orWhere('erp_market_coupons.audience','like','%'.$vip.'%');
                if ($is_new){
                    $q->orWhere('erp_market_coupons.audience','like','%new%');
                }
            })
            ->select([
                'erp_market_coupons.*',
                DB::raw('from_unixtime(erp_market_coupons.begin_at,"%Y-%m-%d") as begin,
                    from_unixtime(erp_market_coupons.end_at,"%Y-%m-%d") as end,
                    group_concat(erp_product_class.name) as cats,
                    group_concat(erp_mp_name.mp_name) as mpnames,
                    group_concat(erp_tags.name) as tags,
                    group_concat(erp_product_list.product_name) as skus,
                    group_concat(erp_spu_list.name) as unions'),
            ])
            ->groupBy('erp_market_coupons.id')
            ->orderBy('erp_market_coupons.id','desc')
            ->get();

        //找出当前用户已领取能正常使用的优惠券
        //更新优惠券过期状态
        event('coupons.checkInvalid',['user_id'=>$user_id]);
        $has_coupons=DB::table('erp_user_coupon_link')
            ->where('status',0)
            ->where('user_id',$user_id)
            ->distinct()
            ->pluck('market_coupon_id');

        foreach ($coupons as $coupon){
            if ($has_coupons->contains($coupon->id)){
                $coupon->receive_status=1;//已领取状态
            }else{
                $coupon->receive_status=0;//可以领取状态
            }
            $coupon->begin_at=date('Y-m-d',$coupon->begin_at);
            $coupon->end_at=date('Y-m-d',$coupon->end_at);

            $link_detail='';
            !empty($coupon->mpnames) && $link_detail.=$coupon->mpnames.'等馆区,';
            !empty($coupon->cats) && $link_detail.=$coupon->cats.'等分类,';
            !empty($coupon->tags) && $link_detail.=$coupon->tags.'等标签,';
            !empty($coupon->skus) && $link_detail.=$coupon->skus.'等商品,';
            !empty($coupon->unions) && $link_detail.=$coupon->unions.'等套餐,';
            $coupon->link_detail=rtrim($link_detail,',');
        }

        return $coupons;
    }

    /**
     * 获取可用的优惠券
     * @param $arr
     * @return mixed
     */
    public function getAvailableCoupons($arr)
    {
        //取出该用户拥有的优惠券
        event('coupons.checkInvalid',['user_id'=>$arr['user_id']]);
        $has_coupons=DB::table('erp_user_coupon_link')
            ->where('status',0)
            ->where('user_id',$arr['user_id'])
            ->distinct()
            ->get();

        $has_coupon_ids=$has_coupons->pluck('market_coupon_id');

        $available_coupons=$this->filterCoupons($arr,$has_coupon_ids);
        foreach ($available_coupons as $coupon){
            foreach ($has_coupons as $v){
                if ($v->market_coupon_id==$coupon->id){
                    $coupon->begin_at=date('Y-m-d',$v->created_at);
                    $coupon->end_at=date('Y-m-d',$v->invalid_at);
                }
            }
        }

        return $available_coupons;
    }

    public function filterCoupons($arr,$ids)
    {
        $coupons=DB::table('erp_market_coupons')
            ->leftJoin('erp_market_coupon_link','erp_market_coupons.id','erp_market_coupon_link.market_coupon_id')
            //分类
            ->leftJoin('erp_product_class',function($q){
                $q->on('erp_market_coupon_link.link_id','erp_product_class.id')
                    ->where('erp_market_coupon_link.link_type','App\ProductClass');
            })
            //馆区
            ->leftJoin('erp_mp_name',function($q){
                $q->on('erp_market_coupon_link.link_id','erp_mp_name.id')
                    ->where('erp_market_coupon_link.link_type','App\MpName');
            })
            //标签
            ->leftJoin('erp_tags',function($q){
                $q->on('erp_market_coupon_link.link_id','erp_tags.id')
                    ->where('erp_market_coupon_link.link_type','App\Tag');
            })
            //sku
            ->leftJoin('erp_product_price',function($q){
                $q->on('erp_market_coupon_link.link_id','erp_product_price.id')
                    ->where('erp_market_coupon_link.link_type','App\ProductPrice');
            })
            //套餐
            ->leftJoin('erp_mp_name_spu_link',function($q){
                $q->on('erp_market_coupon_link.link_id','erp_mp_name_spu_link.id')
                    ->where('erp_market_coupon_link.link_type','App\MpNameSpuList');
            })
            ->whereIn('erp_market_coupons.id',$ids)
            ->select([
                'erp_market_coupons.id',
                'erp_market_coupons.full',
                'erp_market_coupons.decr',
                'erp_market_coupons.type',
                'erp_market_coupons.is_plus',
                DB::raw('group_concat(erp_product_class.id) as cat_ids,
                group_concat(erp_mp_name.id) as mpname_ids,
                group_concat(erp_tags.id) as tag_ids,
                group_concat(erp_product_price.id) as sku_ids,
                group_concat(erp_mp_name_spu_link.id) as unions_ids'),
            ])
            ->groupBy('erp_market_coupons.id')
            ->orderBy('erp_market_coupons.id','desc')
            ->get();

        $available_coupons=$coupons->filter(function ($value, $key) use($arr) {
            $total=0;
            foreach (explode(',',$value->mpname_ids) as $v){
                isset($arr['money']['mpnames'][$v]) && $total+=$arr['money']['mpnames'][$v];
            }
            if ($total >= $value->full){
                if ($value->is_plus){//每满减
                    $value->total_decr=floor($total/$value->full)*$value->decr;
                }else{
                    $value->total_decr=$value->decr;
                }
                return true;
            }
            $total=0;
            foreach (explode(',',$value->tag_ids) as $v){
                isset($arr['money']['tags'][$v]) && $total+=$arr['money']['tags'][$v];
            }
            if ($total >= $value->full){
                if ($value->is_plus){//每满减
                    $value->total_decr=floor($total/$value->full)*$value->decr;
                }else{
                    $value->total_decr=$value->decr;
                }
                return true;
            }
            $total=0;
            foreach (explode(',',$value->cat_ids) as $v){
                isset($arr['money']['cats'][$v]) && $total+=$arr['money']['cats'][$v];
            }
            if ($total >= $value->full){
                if ($value->is_plus){//每满减
                    $value->total_decr=floor($total/$value->full)*$value->decr;
                }else{
                    $value->total_decr=$value->decr;
                }
                return true;
            }
            $total=0;
            foreach (explode(',',$value->sku_ids) as $v){
                isset($arr['money']['skus'][$v]) && $total+=$arr['money']['skus'][$v];
            }
            if ($total >= $value->full){
                if ($value->is_plus){//每满减
                    $value->total_decr=floor($total/$value->full)*$value->decr;
                }else{
                    $value->total_decr=$value->decr;
                }
                return true;
            }
            $total=0;
            foreach (explode(',',$value->unions_ids) as $v){
                isset($arr['money']['unions'][$v]) && $total+=$arr['money']['unions'][$v];
            }
            if ($total >= $value->full){
                if ($value->is_plus){//每满减
                    $value->total_decr=floor($total/$value->full)*$value->decr;
                }else{
                    $value->total_decr=$value->decr;
                }
                return true;
            }
            return false;
        });

        return $available_coupons;
    }

    /**
     * 提交订单时检测
     * @param $arr
     * @param $coupon_ids
     * @return array
     */
    public function checkAvailableCoupons($arr, $coupon_ids)
    {
        //一个馆区最多用两张优惠券
        if (count($coupon_ids)>2){
            return [
                'success' => false,
            ];
        }
        //取出用户拥有的优惠券
        $user_has_coupons=DB::table('erp_user_coupon_link')
            ->where('status',0)
            ->where('user_id',$arr['user_id'])
            ->whereIn('market_coupon_id',$coupon_ids)->count();
        if ($user_has_coupons != count($coupon_ids)){
            return [
                'success' => false,
            ];
        }
        //取出优惠券
        $available_coupons=$this->filterCoupons($arr,$coupon_ids);
        if ($available_coupons->count() != count($coupon_ids)){
            return [
                'success' => false,
            ];
        }
        //看类型
        $type=0;
        $freight_decr=$normal_decr=0;
        foreach ($available_coupons as $coupon){
            if ($coupon->type==$type){
                return [
                    'success' => false,
                ];
            }
            $type=$coupon->type;
            if ($coupon->type==1){
                $freight_decr=$coupon->total_decr;
            }
            if ($coupon->type==2){
                $normal_decr=$coupon->total_decr;
            }
        }

        return [
            'success' => true,
            'freight_decr'     => $freight_decr,
            'normal_decr'     => $normal_decr,
        ];
    }

    /**
     * 首页团购列表
     * @return mixed
     */
    public function groupIndex()
    {
        $spus=$this->getNowGroups();
        return $spus;
    }

    /**
     * 获取当前进行中的团购
     * @param int $page_size
     * @param int $num
     * @return mixed
     */
    public function getNowGroups($page_size=0, $num=6)
    {
        //团购套餐ids
        $union_group_ids=$this->getUnionGroupIds();
        //普通团购ids
        $normal_group_ids=$this->getNormalGroupIds();
        $query=DB::table('erp_market_groups')
            ->leftJoin('erp_mp_name_spu_link','erp_market_groups.mp_spu_id','erp_mp_name_spu_link.id')
            ->leftJoin('erp_spu_list','erp_mp_name_spu_link.spu_id','erp_spu_list.id')
            ->leftJoin('erp_mp_name','erp_mp_name_spu_link.mp_name_id','erp_mp_name.id')
            ->whereIn('erp_market_groups.id',$union_group_ids)
            ->orWhereIn('erp_market_groups.id',$normal_group_ids)
            ->select([
                'erp_market_groups.id as group_id',
                'erp_market_groups.mp_spu_id',
                'erp_market_groups.group_people_num',
                'erp_market_groups.union_origin_price',
                'erp_market_groups.union_group_price',
                'erp_spu_list.name',
                'erp_spu_list.img',
                'erp_spu_list.sub_name',
                'erp_spu_list.type',
                'erp_mp_name.mp_name',
            ])
            ->orderBy('erp_market_groups.id','desc');
        if ($page_size==0){
            $spus=$query->take($num)->get();
        }else{
            $spus=$query->paginate($page_size);
        }

        //取关联的第一个sku
        foreach ($spus as $spu){
            $first_sku=DB::table('erp_market_group_details')
                ->leftJoin('erp_product_price','erp_market_group_details.price_id','erp_product_price.id')
                ->leftJoin('erp_product_list','erp_product_price.product_id','erp_product_list.id')
                ->where('erp_market_group_details.group_id',$spu->group_id)
                ->where(function($q){
                    $q->whereColumn('erp_market_group_details.group_num','>','erp_market_group_details.buy_count')
                        ->orWhere('erp_market_group_details.group_num',0);//是套餐的情况下会出现
                })
                ->select([
                    'erp_market_group_details.price_id',
                    'erp_market_group_details.origin_price',
                    'erp_market_group_details.group_price',
                    'erp_product_list.image',
                ])
                ->first();
            if (empty($spu->img)){
                $spu->image=getImageUrl($first_sku->image);
            }else {
                $spu->image = getImageUrl($spu->img);
            }
            //套餐就不用取了,组装一个
            if ($spu->type==1){
                $first_sku=(object)['price_id'=>0,'origin_price'=>$spu->union_origin_price,'group_price'=>$spu->union_group_price];
            }
            $spu->sku=$first_sku;
            //差价，立省多少钱
            $spu->sku->diff=$spu->sku->origin_price - $spu->sku->group_price;
        }
        return $spus;
    }

    public function getUnionGroupIds()
    {
        //团购套餐ids
        $union_group_ids=DB::table('erp_market_groups')
            ->where('spu_type',1)
            ->where('flag',0)
            ->where('begin_at','<=',time())
            ->where('end_at','>=',time())
            ->whereColumn('union_group_num','>','union_buy_count')
            ->whereRaw('(union_group_num % group_people_num) < (union_group_num - union_buy_count)')
            ->pluck('id');
        return $union_group_ids;
    }

    public function getNormalGroupIds()
    {
        //普通团购ids
        $normal_group_ids=DB::table('erp_market_group_details')
            ->leftJoin('erp_market_groups','erp_market_group_details.group_id','erp_market_groups.id')
            ->where('erp_market_groups.spu_type',0)
            ->where('erp_market_groups.flag',0)
            ->where('erp_market_groups.begin_at','<=',time())
            ->where('erp_market_groups.end_at','>=',time())
            ->whereColumn('erp_market_group_details.group_num','>','erp_market_group_details.buy_count')
            ->selectRaw('erp_market_group_details.group_id,
                erp_market_groups.group_people_num,
                sum(erp_market_group_details.group_num) as total_group_num,
                sum(erp_market_group_details.buy_count) as total_buy_count')
            ->groupBy('erp_market_group_details.group_id')
            ->havingRaw('(total_group_num % group_people_num) < (total_group_num - total_buy_count)')
            ->pluck('erp_market_group_details.group_id');
        return $normal_group_ids;
    }

    public function dealGroup($request,$order_id,$user_id)
    {
        if (isset($request->groups['open_group_id']) && $request->groups['open_group_id']>0){
            //是拼团
            $open_id=$request->groups['open_group_id'];
        }else{
            //开团
            $open_id=DB::table('erp_market_open_groups')->insertGetId([
                'group_id'=>$request->group->id,
                'created_at'=>time(),
                'invalid_at'=>time()+($request->group->duration*60),
            ]);
        }
        //加入团
        DB::table('erp_market_group_buyers')->insert([
            'open_id'=>$open_id,
            'user_id'=>$user_id,
            'group_detail_id'=>isset($request->groups['group_detail_id'])?$request->groups['group_detail_id']:0,
            'order_id'=>$order_id,
            'order_status'=>0,
            'created_at'=>time(),
            'is_bot'=>0,
        ]);
        //更新数量
        if ($request->group->spu_type){
            DB::table('erp_market_groups')->where('id',$request->group->id)->increment('union_buy_count');
        }else{
            DB::table('erp_market_group_details')->where('id',$request->groups['group_detail_id'])->increment('buy_count');
        }
        //更新团状态
        $count=DB::table('erp_market_group_buyers')->where('open_id',$open_id)->count();
        if ($request->group->group_people_num <= $count){
            DB::table('erp_market_open_groups')->where('id',$open_id)->update(['status'=>1]);
            //更新订单团购状态
            DB::table('erp_stock_order')->where('id',$order_id)->update(['group_status'=>3]);
        }else{
            DB::table('erp_stock_order')->where('id',$order_id)->update(['group_status'=>1]);
        }
    }

    public function fetchInviteInfo($request)
    {
        $arrContextOptions=array(
            "ssl"=>array(
                "verify_peer"=>false,
                "verify_peer_name"=>false,
            ),
        );
        //获取当前用户头像并上传到oss
        $local_head_img=public_path('uploads/images').'/'.$request->user->wxUserId.'.png';
        $head_img=file_get_contents($request->user->headimg,false, stream_context_create($arrContextOptions));
        file_put_contents($local_head_img,$head_img);
        $oss=new OssRepository();
        $result=$oss->uploadFile($local_head_img);
        unlink($local_head_img);

        $info=DB::table('erp_market_open_groups')
            ->leftJoin('erp_market_groups','erp_market_open_groups.group_id','erp_market_groups.id')
            ->leftJoin('erp_mp_name_spu_link','erp_market_groups.mp_spu_id','erp_mp_name_spu_link.id')
            ->leftJoin('erp_spu_list','erp_mp_name_spu_link.spu_id','erp_spu_list.id')
            ->leftJoin('erp_mp_name','erp_mp_name_spu_link.mp_name_id','erp_mp_name.id')
            ->where('erp_market_open_groups.id',$request->id)
            ->select([
                'erp_mp_name.mp_name',
                'erp_spu_list.name',
                'erp_spu_list.sub_name',
                'erp_spu_list.img',
                'erp_market_groups.id',
                'erp_market_groups.spu_type',
                'erp_market_groups.group_people_num',
                'erp_market_groups.union_origin_price',
                'erp_market_groups.union_group_price',
            ])
            ->first();

        $detail=DB::table('erp_market_group_buyers')
            ->leftJoin('erp_market_group_details','erp_market_group_buyers.group_detail_id','erp_market_group_details.id')
            ->leftJoin('erp_product_price','erp_market_group_details.price_id','erp_product_price.id')
            ->leftJoin('erp_product_list','erp_product_price.product_id','erp_product_list.id')
            ->where('erp_market_group_buyers.open_id',$request->id)
            ->where('erp_market_group_buyers.user_id',$request->user->wxUserId)
            ->select([
                'erp_market_group_details.origin_price',
                'erp_market_group_details.group_price',
                'erp_product_list.image',
            ])
            ->first();

        $buyers=DB::table('erp_market_group_buyers')
            ->leftJoin('wxuser',function($q){
                $q->on('erp_market_group_buyers.user_id','wxuser.id')
                    ->where('erp_market_group_buyers.is_bot',0);
            })
            ->leftJoin('wxuser_bot',function($q){
                $q->on('erp_market_group_buyers.user_id','wxuser_bot.id')
                    ->where('erp_market_group_buyers.is_bot',1);
            })
            ->where('erp_market_group_buyers.order_status',1)
            ->where('erp_market_group_buyers.open_id',$request->id)
            ->select([
                'erp_market_group_buyers.is_bot',
                'wxuser.headimg',
                'wxuser_bot.head_img',
            ])
            ->orderBy('erp_market_group_buyers.id','asc')
            ->get();

        $info->diff_user=$info->group_people_num - $buyers->count();

        if ($info->spu_type==1){
            $info->origin_price=$info->union_origin_price;
            $info->group_price=$info->union_group_price;
        }else{
            $info->origin_price=$detail->origin_price;
            $info->group_price=$detail->group_price;
        }

        if (empty($info->img)){
            if ($info->spu_type==1){
                $union_detail_img=DB::table('erp_market_group_details')
                    ->leftJoin('erp_product_price','erp_market_group_details.price_id','erp_product_price.id')
                    ->leftJoin('erp_product_list','erp_product_price.product_id','erp_product_list.id')
                    ->where('erp_market_group_details.group_id',$info->id)
                    ->value('erp_product_list.image');
                $info->image=substr($union_detail_img,8);
            }else{
                $info->image=substr($detail->image,8);
            }
        }else {
            $info->image=substr($info->img,8);
        }

        return [
            'head_img'=>substr($result,8),
            'mp'=>$info->mp_name,
            'name'=>$info->name,
            'sub_name'=>$info->sub_name,
            'image'=>$info->image,
            'origin_price'=>$info->origin_price,
            'group_price'=>$info->group_price,
            'group_people_num'=>$info->group_people_num,
            'diff_user'=>$info->diff_user,
        ];
    }

    //检测限购
    public function checkLimitBuy($data)
    {
        $user_id=$data['user_id'];
        $spu_id=$data['spu_id'];
        $sku_id=isset($data['sku_id'])?$data['sku_id']:0;
        $market_class=$data['market_class'];
        $vip='vip'.$market_class;

        $spu=DB::table('erp_spu_list')->find($spu_id);

        $where=[
            'user_id'=>$user_id,
            'spu_id'=>$spu_id,
        ];
        if (!$spu->type==1){
            //不是套餐
            $where['sku_id']=$sku_id;
        }

        if ($spu->limit_buy_type==0){
            $can_buy_num='unlimit';
        }else{
            $limit_num=$spu->limit_buy_type==1?$spu->limit_buy_number:$spu->$vip;
            if (empty($limit_num)){
                $can_buy_num='unlimit';
            }else{
                $nearby_date=[];
                for ($i=0; $i<$spu->cycle; $i++){
                    $nearby_date[] = date('Ymd' ,strtotime( '-'.$i.' days'));
                }
                $limits=DB::table('erp_limit_buy')->where($where)->whereIn('created_at',$nearby_date)->count();
                $can_buy_num=$limits >= $limit_num?0:$limit_num - $limits;
            }
        }

        return $can_buy_num;
    }
}
