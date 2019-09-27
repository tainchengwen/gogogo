<?php

namespace App\Http\Controllers\ApiShopMp;

use App\MarketCoupon;
use App\WxUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use DB;

class CouponController extends Controller
{
    /**
     * 个人中心-优惠券
     * @param Request $request
     * @return array
     */
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:0,1,2'
        ]);
        if ($validator->fails()) {
            // 返回正确的格式结果
            return $this->errorResponse($request, [], '缺少必要参数');
        }
        //更新优惠券过期状态
        event('coupons.checkInvalid',['user_id'=>$request->user->wxUserId]);

        $data=[];

        $data['count']=DB::table('erp_user_coupon_link')
            //->where('status',$request->status)
            ->where('status',0)//未使用的优惠券总数
            ->where('user_id','=',$request->user->wxUserId)
            ->count();

        $coupons=DB::table('erp_user_coupon_link')
            ->leftJoin('erp_market_coupons','erp_user_coupon_link.market_coupon_id','erp_market_coupons.id')
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
            ->where('erp_user_coupon_link.status',$request->status)
            ->where('erp_user_coupon_link.user_id','=',$request->user->wxUserId)
            ->select([
                'erp_user_coupon_link.id',
                'erp_market_coupons.full',
                'erp_market_coupons.decr',
                DB::raw('from_unixtime(erp_user_coupon_link.created_at,"%Y-%m-%d") as begin,
                from_unixtime(erp_user_coupon_link.invalid_at,"%Y-%m-%d") as end,
                group_concat(erp_product_class.name) as cats,
                group_concat(erp_product_class.id) as cats_ids,
                group_concat(erp_mp_name.mp_name) as mpnames,
                group_concat(erp_mp_name.id) as mpnames_ids,
                group_concat(erp_tags.name) as tags,
                group_concat(erp_tags.id) as tags_ids,
                group_concat(erp_product_list.product_name) as skus,
                group_concat(erp_product_list.id) as skus_ids,
                group_concat(erp_spu_list.name) as unions,
                group_concat(erp_mp_name_spu_link.id) as unions_ids'),
            ])
            ->groupBy('erp_user_coupon_link.id')
            ->orderBy('erp_user_coupon_link.id','desc')
            ->get();

        foreach ($coupons as $coupon){
            $link_detail='';
            !empty($coupon->mpnames) && $link_detail.=$coupon->mpnames.'等馆区,';
            !empty($coupon->cats) && $link_detail.=$coupon->cats.'等分类,';
            !empty($coupon->tags) && $link_detail.=$coupon->tags.'等标签,';
            !empty($coupon->skus) && $link_detail.=$coupon->skus.'等商品,';
            !empty($coupon->unions) && $link_detail.=$coupon->unions.'等套餐,';
            $coupon->link_detail=rtrim($link_detail,',');
        }

        if ($request->status == 0){
            foreach ($coupons as $coupon){
                if (!empty($coupon->mpnames_ids)){
                    $coupon->redirect_type='mp';
                    $coupon->redirect_param=explode(',',$coupon->mpnames_ids)[0];
                }elseif (!empty($coupon->cats_ids)){
                    $coupon->redirect_type='cat';
                    $temp_cats_ids=explode(',',$coupon->cats_ids);
                    $coupon->redirect_param=DB::table('erp_spu_category')->leftJoin('erp_spu_list','erp_spu_category.id','erp_spu_list.class_id')
                        ->leftJoin('erp_spu_sku_link','erp_spu_list.id','erp_spu_sku_link.spu_id')
                        ->leftJoin('erp_product_list','erp_spu_sku_link.sku_id','erp_product_list.id')
                        ->leftJoin('erp_product_class','erp_product_list.class_id','erp_product_class.id')
                        ->where('erp_product_class.flag',0)
                        ->whereIn('erp_product_class.id',$temp_cats_ids)->value('erp_spu_category.id');
                }elseif (!empty($coupon->skus_ids)){
                    $coupon->redirect_type='sku';
                    $temp_skus_ids=explode(',',$coupon->skus_ids);
                    $coupon->redirect_param=DB::table('erp_mp_name_spu_link')
                        ->leftJoin('erp_spu_list','erp_mp_name_spu_link.spu_id','erp_spu_list.id')
                        ->leftJoin('erp_spu_sku_link','erp_spu_list.id','erp_spu_sku_link.spu_id')
                        ->leftJoin('erp_product_list','erp_spu_sku_link.sku_id','erp_product_list.id')
                        ->leftJoin('erp_product_price',function ($q){
                            $q->on('erp_product_list.id','erp_product_price.product_id')
                                ->on('erp_mp_name_spu_link.mp_name_id','erp_product_price.mp_name_id');
                        })
                        ->whereIn('erp_product_price.id',$temp_skus_ids)->value('erp_mp_name_spu_link.id');
                }elseif (!empty($coupon->unions_ids)){
                    $coupon->redirect_type='union';
                    $coupon->redirect_param=explode(',',$coupon->unions_ids)[0];
                }elseif (!empty($coupon->tags_ids)){
                    $coupon->redirect_type='tag';
                    $coupon->redirect_param=explode(',',$coupon->tags_ids)[0];
                }else{
                    $coupon->redirect_type='none';
                    $coupon->redirect_param='';
                }
            }
        }
        $data['list']=$coupons;

        return $this->successResponse($request, $data);
    }

    /**
     * 领券中心列表
     * @param Request $request
     * @return array
     */
    public function receiveList(Request $request)
    {
        //更新已拥有优惠券过期状态
        event('coupons.checkInvalid',['user_id'=>$request->user->wxUserId]);
        //获取已有的正常优惠券ids
        $wxuser=WxUser::find($request->user->wxUserId);
        $coupons_ids=$wxuser->coupons()->wherePivot('status',0)->pluck('erp_market_coupons.id');

        /*$can_receive_coupons=MarketCoupon::whereNotIn('id',$coupons_ids)
            ->where('is_need_receive',1)
            ->whereColumn('number', '>', 'receive_count')
            ->where('flag', 0)
            ->where('begin_at', '<=', time())
            ->where('end_at', '>=', time())
            ->where(function ($q) use ($request){
                $q->where('audience','=','')
                    ->orWhere('audience','like','%'.$request->user->market_class.'%');
                if ($request->user->is_new){
                    $q->orWhere('audience','like','%new%');
                }
            })
            ->select([
                '*',
                DB::raw('from_unixtime(begin_at,"%Y-%m-%d") as begin,from_unixtime(end_at,"%Y-%m-%d") as end')
            ])
            ->get();*/

        $can_receive_coupons=DB::table('erp_market_coupons')
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
            ->whereNotIn('erp_market_coupons.id',$coupons_ids)
            ->where('erp_market_coupons.is_need_receive',1)
            ->whereColumn('erp_market_coupons.number', '>', 'erp_market_coupons.receive_count')
            ->where('erp_market_coupons.flag', 0)
            ->where('erp_market_coupons.begin_at', '<=', time())
            ->where('erp_market_coupons.end_at', '>=', time())
            ->where(function ($q) use ($request){
                $q->where('erp_market_coupons.audience','=','')
                    ->orWhere('erp_market_coupons.audience','like','%'.$request->user->market_class.'%');
                if ($request->user->is_new){
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

        foreach ($can_receive_coupons as $coupon){
            $link_detail='';
            !empty($coupon->mpnames) && $link_detail.=$coupon->mpnames.'等馆区,';
            !empty($coupon->cats) && $link_detail.=$coupon->cats.'等分类,';
            !empty($coupon->tags) && $link_detail.=$coupon->tags.'等标签,';
            !empty($coupon->skus) && $link_detail.=$coupon->skus.'等商品,';
            !empty($coupon->unions) && $link_detail.=$coupon->unions.'等套餐,';
            $coupon->link_detail=rtrim($link_detail,',');
        }

        return $this->successResponse($request, $can_receive_coupons);
    }

    /**
     * 领取优惠券
     * @param Request $request
     * @return array
     */
    public function receive(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required'
        ]);
        if ($validator->fails()) {
            // 返回正确的格式结果
            return $this->errorResponse($request, [], '缺少必要参数');
        }

        //检测是否已存在
        $first=DB::table('erp_user_coupon_link')
            ->where('user_id',$request->user->wxUserId)
            ->where('market_coupon_id',$request->id)
            ->where('status',0)->first();

        if ($first) {
            return $this->errorResponse($request, [], '已领取过该优惠券');
        }

        $wxuser=WxUser::find($request->user->wxUserId);
        $coupon=MarketCoupon::find($request->id);

        if ($coupon->use_type == 1){
            $invalid_at = time() + ($coupon->use_term * 86399);
        }else{
            $invalid_at = $coupon->use_term;
        }

        $wxuser->coupons()->attach($coupon->id,[
            'status'=>0,
            'created_at'=>time(),
            'invalid_at'=>$invalid_at,
        ]);

        //更新优惠券已领取数量
        $coupon->increment('receive_count');

        return $this->successResponse($request, [], '领取成功');
    }
}
