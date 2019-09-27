<?php

namespace App\Repositories\Markets\Coupon;

use App\MarketCoupon;

trait CouponTrait
{
    public function add($request)
    {
        $data=[
            'name'=>$request->name,
            'content'=>$request->content,
            'type'=>$request->type,
            'full'=>$request->full,
            'decr'=>$request->decr,
            'is_plus'=>$request->is_plus,
            'is_need_receive'=>$request->is_need_receive,
            'number'=>$request->number,
            'use_type'=>$request->use_type,
            'use_term'=>$request->use_type==1?$request->use_term:strtotime($request->use_term),
            'audience'=>implode(',',$request->audience),
            'created_at'=>time(),
            'begin_at'=>strtotime($request->begin_at),
            'end_at'=>strtotime($request->end_at),
        ];

        $coupon_model=MarketCoupon::create($data);

        !empty($request->selectmp) && $coupon_model->mpnames()->attach($request->selectmp);

        !empty($request->selectcate) && $coupon_model->cats()->attach($request->selectcate);

        !empty($request->selecttags) && $coupon_model->tags()->attach($request->selecttags);

        !empty($request->selectskus) && $coupon_model->skus()->attach($request->selectskus);

        !empty($request->selectunions) && $coupon_model->unions()->attach($request->selectunions);

        return [
            'status'=>1,
            'msg'=>'添加成功'
        ];
    }
}
