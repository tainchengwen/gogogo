<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\MpName;
use App\MarketFreight;
use App\MarketCoupon;
use DB;
use Illuminate\Support\Facades\Validator;
use App\Repositories\MarketRepository;

class MarketController extends Controller
{
    public function __construct(MarketRepository $marketRepository)
    {
        $this->market=$marketRepository;
    }

    //运费
    public function freightList(Request $request)
    {
        $where=[];
        if ($request->filled('name'))
            $where[]=['name','like','%'.$request->name.'%'];
        if ($request->filled('created_at')){
            $begin=strtotime($request->created_at);
            $end=$begin+86399;
            $where[]=['created_at','>=',$begin];
            $where[]=['created_at','<=',$end];
        }
        $ferights=MarketFreight::with([
                'mpnames:mp_name',
                'tags:name',
                'cats:name',
                'skus:erp_product_price.id',
                'unions:erp_mp_name_spu_link.id'
            ])
            ->where('flag',0)
            ->where($where)
            ->orderBy('id','desc')
            ->paginate();

        return $ferights;
    }

    public function freightSkuList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'=>'required',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $skus=DB::table('erp_product_list')
            ->leftJoin('erp_product_price','erp_product_list.id','erp_product_price.product_id')
            ->leftJoin('erp_market_freight_link',function ($q){
                $q->on('erp_product_price.id','erp_market_freight_link.link_id')
                    ->where('erp_market_freight_link.link_type','App\ProductPrice');
            })
            ->leftJoin('erp_mp_name','erp_product_price.mp_name_id','erp_mp_name.id')
            ->where('erp_market_freight_link.market_freight_id',$request->id)
            ->select([
                'erp_mp_name.mp_name',
                'erp_product_list.product_no',
                'erp_product_list.product_name',
            ])
            ->paginate();

        return $skus;
    }

    public function freightAdd(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'=>'required',
            'content' =>'required',
            'type' =>'required|in:1,2,3,4',
            'full' =>'required|numeric|min:1',
            'begin_at' =>'required|date_format:Y-m-d H:i:s',
            'end_at' =>'required|date_format:Y-m-d H:i:s',
            'selectmp' =>'sometimes|array',
            'selectcate' =>'sometimes|array',
            'selecttags' =>'sometimes|array',
            'selectskus' =>'sometimes|array',
            'selectunions' =>'sometimes|array',
            'decr' =>'sometimes|numeric|min:1',
            'audience' =>'sometimes|array',//适用人群
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $result=$this->market->freightAdd($request);
        return $result;
    }

    public function freightEdit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'=>'required|integer',
            'name'=>'required',
            'content' =>'required',
            'begin_at' =>'required|date_format:Y-m-d H:i:s',
            'end_at' =>'required|date_format:Y-m-d H:i:s',
            'selectmp' =>'sometimes|array',
            'selectcate' =>'sometimes|array',
            'selecttags' =>'sometimes|array',
            'selectskus' =>'sometimes|array',
            'selectunions' =>'sometimes|array',
            'audience' =>'sometimes|array',//适用人群
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $freight=MarketFreight::find($request->id);
        $freight->name=$request->name;
        $freight->content=$request->input('content');
        $freight->begin_at=strtotime($request->begin_at);
        $freight->end_at=strtotime($request->end_at);
        $freight->vip=implode(',',$request->audience);
        $freight->save();
        //更新关联表
        $freight->mpnames()->sync($request->selectmp);
        $freight->cats()->sync($request->selectcate);
        $freight->tags()->sync($request->selecttags);
        $freight->skus()->sync($request->selectskus);
        $freight->unions()->sync($request->selectunions);

        return [
            'status'=>1,
            'msg'=>'更新成功'
        ];
    }

    public function freightDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'=>'required',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $freights=MarketFreight::find($request->id);
        foreach ($freights as $freight){
            $freight->flag=1;
            $freight->save();
        }

        return [
            'status'=>1,
            'msg'=>'删除成功'
        ];
    }

    //优惠
    public function couponAdd(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'=>'required',
            'content' =>'required',
            'type' =>'required|in:1,2',
            'full' =>'required|numeric|min:1',
            'decr' =>'required|numeric|min:1',
            'is_plus' =>'required|in:0,1',
            'is_need_receive' =>'required|in:0,1',
            'number' =>'required|integer|min:1',
            'begin_at' =>'required|date_format:Y-m-d H:i:s',
            'end_at' =>'required|date_format:Y-m-d H:i:s',
            'selectmp' =>'sometimes|array',
            'selectcate' =>'sometimes|array',
            'selecttags' =>'sometimes|array',
            'selectskus' =>'sometimes|array',
            'selectunions' =>'sometimes|array',
            'audience' =>'sometimes|array',//适用人群
            'use_type' =>'required|in:1,2',
            'use_term' =>'required',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $result=$this->market->couponAdd($request);
        return $result;
    }

    public function couponList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' =>'required|in:1,2',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $where=[];
        if ($request->filled('name'))
            $where[]=['name','like','%'.$request->name.'%'];
        if ($request->filled('created_at')){
            $begin=strtotime($request->created_at);
            $end=$begin+86399;
            $where[]=['created_at','>=',$begin];
            $where[]=['created_at','<=',$end];
        }
        $coupons=MarketCoupon::with([
                'mpnames:mp_name',
                'tags:name',
                'cats:name',
                'skus:erp_product_price.id',
                'unions:erp_mp_name_spu_link.id'
            ])
            //->where('flag',0)
            ->where($where)
            ->where('type',$request->type)
            ->orderBy('id','desc')
            ->paginate();

        return $coupons;
    }

    public function couponSkuList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'=>'required',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $skus=DB::table('erp_product_list')
            ->leftJoin('erp_product_price','erp_product_list.id','erp_product_price.product_id')
            ->leftJoin('erp_market_coupon_link',function ($q){
                $q->on('erp_product_price.id','erp_market_coupon_link.link_id')
                    ->where('erp_market_coupon_link.link_type','App\ProductPrice');
            })
            ->leftJoin('erp_mp_name','erp_product_price.mp_name_id','erp_mp_name.id')
            ->where('erp_market_coupon_link.market_coupon_id',$request->id)
            ->select([
                'erp_mp_name.mp_name',
                'erp_product_list.product_no',
                'erp_product_list.product_name',
            ])
            ->paginate();

        return $skus;
    }

    public function couponUseAndReceiveDetail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'=>'required',
            'type'=>'required|in:1,2',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        if ($request->type == 1){
            //已使用
            $list=DB::table('erp_order_coupon_link')
                ->leftJoin('erp_stock_order','erp_order_coupon_link.order_id','erp_stock_order.id')
                ->leftJoin('wxuser','erp_stock_order.user_id','wxuser.id')
                ->select([
                    'wxuser.id',
                    'wxuser.nickname',
                    'wxuser.mobile',
                    'wxuser.market_class',
                    'erp_stock_order.order_num',
                    'erp_stock_order.created_at as used_at'
                ])
                ->where('erp_order_coupon_link.market_coupon_id',$request->id)
                ->orderBy('used_at','desc')
                ->get();
        }else{
            //已领取
            $list=DB::table('erp_user_coupon_link')
                ->leftJoin('wxuser','erp_user_coupon_link.user_id','wxuser.id')
                ->select([
                    'wxuser.id',
                    'wxuser.nickname',
                    'wxuser.mobile',
                    'wxuser.market_class',
                    'erp_user_coupon_link.status',
                    'erp_user_coupon_link.created_at as received_at'
                ])
                ->where('erp_user_coupon_link.market_coupon_id',$request->id)
                ->orderBy('received_at','desc')
                ->get();
        }
        foreach ($list as $v){
            isset($v->used_at) && $v->used_at=date('Y-m-d H:i:s',$v->used_at);
            isset($v->received_at) && $v->received_at=date('Y-m-d H:i:s',$v->received_at);
        }
        return $list;
    }

    public function couponDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'=>'required',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $coupon=MarketCoupon::find($request->id);
        $coupon->flag=1;
        $coupon->save();

        return [
            'status'=>1,
            'msg'=>'关闭成功'
        ];
    }

    public function couponEdit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'=>'required|integer',
            'name'=>'required',
            'content' =>'required',
            'begin_at' =>'required|date_format:Y-m-d H:i:s',
            'end_at' =>'required|date_format:Y-m-d H:i:s',
            'selectmp' =>'sometimes|array',
            'selectcate' =>'sometimes|array',
            'selecttags' =>'sometimes|array',
            'selectskus' =>'sometimes|array',
            'selectunions' =>'sometimes|array',
            'audience' =>'sometimes|array',//适用人群
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $coupon=MarketCoupon::find($request->id);
        $coupon->name=$request->name;
        $coupon->content=$request->input('content');
        $coupon->begin_at=strtotime($request->begin_at);
        $coupon->end_at=strtotime($request->end_at);
        $coupon->audience=implode(',',$request->audience);
        $coupon->save();
        //更新关联表
        $coupon->mpnames()->sync($request->selectmp);
        $coupon->cats()->sync($request->selectcate);
        $coupon->tags()->sync($request->selecttags);
        $coupon->skus()->sync($request->selectskus);
        $coupon->unions()->sync($request->selectunions);

        return [
            'status'=>1,
            'msg'=>'更新成功'
        ];
    }

    //团购
    //普通spu团购列表
    public function groupBuyNormalList(Request $request)
    {
        $where=[];
        $group_ids=[];
        if ($request->product_name){
            $where[]=['erp_product_list.product_name','like','%'.$request->product_name.'%'];
        }
        if ($request->product_no){
            $where[]=['erp_product_list.product_no','=',$request->product_no];
        }
        if (!empty($where)){
            $group_ids=DB::table('erp_market_group_details')
                ->leftJoin('erp_product_price','erp_market_group_details.price_id','erp_product_price.id')
                ->leftJoin('erp_product_list','erp_product_price.product_id','erp_product_list.id')
                ->where($where)
                ->pluck('erp_market_group_details.group_id');
        }

        $data=[];
        $spus=DB::table('erp_market_groups')
            ->leftJoin('erp_mp_name_spu_link','erp_market_groups.mp_spu_id','erp_mp_name_spu_link.id')
            ->leftJoin('erp_spu_list','erp_mp_name_spu_link.spu_id','erp_spu_list.id')
            ->where('erp_market_groups.spu_type',0)
            ->where('erp_market_groups.flag',0)
            ->when($request->product_no || $request->product_name, function ($query) use($group_ids){
                $query->whereIn('erp_market_groups.id', $group_ids);
            })
            ->select([
                'erp_market_groups.id',
                'erp_market_groups.mp_spu_id as link_id',
                'erp_market_groups.duration',
                'erp_spu_list.name',
                'erp_market_groups.group_people_num',
                'erp_market_groups.support_bot',
                'erp_market_groups.begin_at',
                'erp_market_groups.end_at',
            ])
            ->orderBy('erp_market_groups.id','desc')
            ->paginate(isset($request -> per_page)?$request -> per_page:3);

        $temp=[];
        foreach ($spus as $spu){
            $spu->begin_at=date('Y-m-d H:i:s',$spu->begin_at);
            $spu->end_at=date('Y-m-d H:i:s',$spu->end_at);
            $temp1[]=$spu;
            $skus=DB::table('erp_market_group_details')
                ->leftJoin('erp_product_price','erp_market_group_details.price_id','erp_product_price.id')
                ->leftJoin('erp_product_list','erp_product_price.product_id','erp_product_list.id')
                ->where('erp_market_group_details.group_id',$spu->id)
                ->select([
                    'erp_market_group_details.id',
                    'erp_product_list.product_no',
                    'erp_product_list.product_name',
                    'erp_product_price.has_stock',
                    'erp_market_group_details.group_num',
                    'erp_market_group_details.origin_price',
                    'erp_market_group_details.group_price',
                ])
                ->get();
            foreach ($skus as $sku){
                $sku->link_id=$spu->link_id;
                $temp1[]=$sku;
            }
            $temp[]=$temp1;
            unset($temp1);
        }
        $data['list']=$temp;
        $data['current_page']=$spus->currentPage();
        $data['per_page']=$spus->perPage();
        $data['total']=$spus->total();
        return $data;
    }

    //套餐团购列表
    public function groupBuyUnionList(Request $request)
    {
        $where=[];
        $group_ids=[];
        if ($request->product_name){
            $where[]=['erp_product_list.product_name','like','%'.$request->product_name.'%'];
        }
        if ($request->product_no){
            $where[]=['erp_product_list.product_no','=',$request->product_no];
        }
        if (!empty($where)){
            $group_ids=DB::table('erp_market_group_details')
                ->leftJoin('erp_product_price','erp_market_group_details.price_id','erp_product_price.id')
                ->leftJoin('erp_product_list','erp_product_price.product_id','erp_product_list.id')
                ->where($where)
                ->pluck('erp_market_group_details.group_id');
        }

        $data=[];
        $spus=DB::table('erp_market_groups')
            ->leftJoin('erp_mp_name_spu_link','erp_market_groups.mp_spu_id','erp_mp_name_spu_link.id')
            ->leftJoin('erp_spu_list','erp_mp_name_spu_link.spu_id','erp_spu_list.id')
            ->where('erp_market_groups.spu_type',1)
            ->where('erp_market_groups.flag',0)
            ->when($request->product_no || $request->product_name, function ($query) use($group_ids){
                $query->whereIn('erp_market_groups.id', $group_ids);
            })
            ->select([
                'erp_market_groups.id',
                'erp_market_groups.mp_spu_id as link_id',
                'erp_market_groups.duration',
                'erp_spu_list.name',
                'erp_market_groups.group_people_num',
                'erp_market_groups.support_bot',
                'erp_market_groups.begin_at',
                'erp_market_groups.end_at',
                'erp_market_groups.union_group_num as group_num',
            ])
            ->orderBy('erp_market_groups.id','desc')
            ->paginate(isset($request -> per_page)?$request -> per_page:3);

        $temp=[];
        foreach ($spus as $spu){
            $spu->begin_at=date('Y-m-d H:i:s',$spu->begin_at);
            $spu->end_at=date('Y-m-d H:i:s',$spu->end_at);
            $temp1[]=$spu;
            $skus=DB::table('erp_market_group_details')
                ->leftJoin('erp_product_price','erp_market_group_details.price_id','erp_product_price.id')
                ->leftJoin('erp_product_list','erp_product_price.product_id','erp_product_list.id')
                ->where('erp_market_group_details.group_id',$spu->id)
                ->select([
                    'erp_market_group_details.id',
                    'erp_product_list.id as sku_id',
                    'erp_product_list.product_no',
                    'erp_product_list.product_name',
                    'erp_product_price.has_stock',
                    'erp_product_price.union_num',
                    'erp_market_group_details.origin_price',
                    'erp_market_group_details.group_price',
                ])
                ->get();
            foreach ($skus as $sku){
                $sku->link_id=$spu->link_id;
                $temp1[]=$sku;
            }
            $temp[]=$temp1;
            unset($temp1);
        }
        $data['list']=$temp;
        $data['current_page']=$spus->currentPage();
        $data['per_page']=$spus->perPage();
        $data['total']=$spus->total();
        return $data;
    }

    //新建团购时可选择的商品spu列表
    public function groupBuyCanAddSpuList(Request $request)
    {
        if ($request->type){//1套餐
            $where[]=['erp_spu_list.type','=',1];
            $where[]=['erp_mp_name_spu_link.union_flag','=',0];
            $where[]=['sku.union_status','=',1];
            $where[]=['sku.union_num','>',0];
        }else{
            $where[]=['erp_spu_list.type','=',0];
        }
        if ($request->name){
            $where[]=['erp_spu_list.name','like',"%{$request->name}%"];
        }
        if ($request->mpid){
            $where[]=['erp_mp_name_spu_link.mp_name_id','=',$request->mpid];
        }
        //已经选择并在活动中的mp spu link id
        $selected_link_ids=DB::table('erp_market_groups')
            ->where('spu_type',$request->type)
            ->where('flag',0)
            ->where('end_at','>',time())
            ->pluck('mp_spu_id');

        // 先拿所有的正常的skus
        $erp_mp_name_spu_link_ids = DB::table('erp_product_price as sku')
            -> leftJoin('erp_spu_sku_link', 'sku.product_id', 'erp_spu_sku_link.sku_id')
            -> leftJoin('erp_spu_list', 'erp_spu_sku_link.spu_id', 'erp_spu_list.id')
            -> leftJoin('erp_mp_name_spu_link', function($q) {
                $q->on('erp_mp_name_spu_link.spu_id', '=', 'erp_spu_sku_link.spu_id')
                    ->on('erp_mp_name_spu_link.mp_name_id', '=', 'sku.mp_name_id');
            })
            -> where('sku.flag', 0)
            -> where('sku.status', 1)
            -> where('sku.is_show', 1)
            -> where('sku.has_stock', '>' , 0)
            -> where('erp_mp_name_spu_link.flag', 0)
            -> where($where)
            -> whereNotIn('erp_mp_name_spu_link.id',$selected_link_ids)
            -> select([
                'erp_mp_name_spu_link.id',
                'erp_mp_name_spu_link.mp_name_id',
                'erp_mp_name_spu_link.spu_id'
            ])
            -> groupBy('mp_name_id', 'spu_id', 'id')
            -> pluck('id');

        $spus = DB::table('erp_mp_name_spu_link as link')
            -> leftJoin('erp_spu_list', 'link.spu_id', 'erp_spu_list.id')
            -> leftJoin('erp_mp_name', 'link.mp_name_id', 'erp_mp_name.id')
            -> where('erp_spu_list.flag', 0)
            -> whereIn('link.id', $erp_mp_name_spu_link_ids)
            -> select([
                'link.id as link_id',
                'erp_mp_name.mp_name',
                'erp_spu_list.name',
                'erp_spu_list.sub_name',
            ])
            -> orderBy('erp_spu_list.id','desc')
            -> paginate(isset($request -> per_page)?$request -> per_page:20);

        return $spus;
    }

    //根据mp spu link id带出关联的sku信息
    public function groupGetSkusByLinkIds(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'link_ids'=>'required',
            'begin_at' =>'required|date_format:Y-m-d H:i:s',
            'end_at' =>'required|date_format:Y-m-d H:i:s',
            'duration' =>'required',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $mp_spus=DB::table('erp_mp_name_spu_link')
            ->leftJoin('erp_spu_list','erp_mp_name_spu_link.spu_id','erp_spu_list.id')
            ->leftJoin('erp_mp_name','erp_mp_name_spu_link.mp_name_id','erp_mp_name.id')
            ->whereIn('erp_mp_name_spu_link.id',$request->input('link_ids'))
            ->select([
                'erp_mp_name_spu_link.id as link_id',
                'erp_mp_name_spu_link.mp_name_id',
                'erp_mp_name_spu_link.spu_id',
                'erp_spu_list.type',
                'erp_spu_list.name',
                'erp_mp_name.mp_name',
            ])
            ->get();

        $data=[];
        foreach ($mp_spus as $mp_spu){
            $temp=[];
            $mp_spu->begin_at=$request->begin_at;
            $mp_spu->end_at=$request->end_at;
            $mp_spu->duration=$request->duration;
            $mp_spu->support_bot=1;//默认支持机器人
            $query = DB::table('erp_spu_sku_link')
                -> leftJoin('erp_product_list','erp_spu_sku_link.sku_id','erp_product_list.id')
                -> leftJoin('erp_product_price', function($q) use ($mp_spu) {
                    $q->on('erp_product_price.product_id', '=', 'erp_spu_sku_link.sku_id')
                        ->where('erp_product_price.mp_name_id', '=', $mp_spu->mp_name_id);
                })
                -> where('erp_spu_sku_link.spu_id', $mp_spu->spu_id)
                -> where('erp_spu_sku_link.flag', 0)
                -> where('erp_product_price.flag', 0)
                -> where('erp_product_price.status', 1)
                -> where('erp_product_price.is_show', 1)
                -> where('erp_product_price.has_stock', '>', 0)
                -> select([
                    'erp_product_price.id as price_id',
                    'erp_product_list.product_no',
                    'erp_product_list.product_name',
                    'erp_product_price.has_stock',
                    'erp_product_price.union_num',
                    'erp_product_price.union_status',
                    'erp_product_price.price_d as origin_price',
                ])
                -> orderBy('erp_spu_sku_link.sort_index','desc')
                -> orderBy('erp_spu_sku_link.id','desc');

            //如果是套餐spu,重新组装下面的价格等信息
            if ($mp_spu->type==1){
                $skus=$query->where('erp_product_price.union_status',1)->get();
                $spu_origin_price=0;
                foreach ($skus as $sku){
                    $spu_origin_price+=$sku->union_num*$sku->origin_price;
                }
                $mp_spu->origin_price=$spu_origin_price;
            }else{
                $skus=$query->get();
            }
            $temp[]=$mp_spu;
            foreach ($skus as $sku){
                $sku->link_id=$mp_spu->link_id;
                $temp[]=$sku;
            }
            $data[]=$temp;
            unset($temp);
        }

        return $data;
    }

    //添加
    public function groupBuyAdd(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' =>'required|in:1,0',
            'data' =>'required|array',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        DB::beginTransaction();
        try{
            foreach ($request->data as $v){
                if (count($v)==1)
                    continue;
                $group_data=array_shift($v);//第一个是spu信息
                $group_insert_data=[
                    'mp_spu_id'=>$group_data['link_id'],
                    'spu_type'=>$request->type,
                    'group_people_num'=>$group_data['group_people_num'],
                    'support_bot'=>$group_data['support_bot'],
                    'begin_at'=>strtotime($group_data['begin_at']),
                    'end_at'=>strtotime($group_data['end_at']),
                    'duration'=>$group_data['duration'],
                    'created_at'=>time(),
                    'union_group_num'=>$request->type?$group_data['group_num']:0,
                ];
                $group_id=DB::table('erp_market_groups')->insertGetId($group_insert_data);
                //group detail
                $detail_insert_data=[];
                $union_origin_price=0;
                $union_group_price=0;
                foreach ($v as $vv){
                    $detail_insert_data[]=[
                        'group_id'=>$group_id,
                        'price_id'=>$vv['price_id'],
                        'group_num'=>$request->type?0:$vv['group_num'],
                        'origin_price'=>$vv['origin_price'],
                        'group_price'=>$vv['group_price'],
                    ];
                    if ($request->type){
                        $union_origin_price+=$vv['union_num']*$vv['origin_price'];
                        $union_group_price+=$vv['union_num']*$vv['group_price'];
                    }
                }
                DB::table('erp_market_group_details')->insert($detail_insert_data);
                //如果是套餐,更新一下主表里的字段
                if ($request->type){
                    DB::table('erp_market_groups')->where('id',$group_id)->update([
                        'union_origin_price'=>$union_origin_price,
                        'union_group_price'=>$union_group_price
                    ]);
                }
            }
            DB::commit();

            return [
                'status'=>1,
                'msg'=>'添加成功'
            ];
        }catch (\Exception $e){
            DB::rollBack();
            return [
                'status'=>0,
                'msg'=>'添加失败'
            ];
        }
    }

    //编辑
    public function groupBuyEdit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'data' =>'required|array',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        DB::beginTransaction();
        try{
            foreach ($request->data as $v){
                $group_data=array_shift($v);//第一个是spu信息
                $group=DB::table('erp_market_groups')->where('id',$group_data['id'])->first();
                $group_update_data=[
                    'group_people_num'=>$group_data['group_people_num'],
                    'support_bot'=>$group_data['support_bot'],
                    'union_group_num'=>$group->spu_type?$group_data['group_num']:0,
                ];
                DB::table('erp_market_groups')->where('id',$group_data['id'])->update($group_update_data);
                //group detail
                $union_origin_price=0;
                $union_group_price=0;
                foreach ($v as $vv){
                    $detail_update_data=[
                        'group_num'=>$group->spu_type?0:$vv['group_num'],
                        'origin_price'=>$vv['origin_price'],
                        'group_price'=>$vv['group_price'],
                    ];
                    if ($group->spu_type){
                        $union_origin_price+=$vv['union_num']*$vv['origin_price'];
                        $union_group_price+=$vv['union_num']*$vv['group_price'];
                    }
                    DB::table('erp_market_group_details')->where('id',$vv['id'])->update($detail_update_data);
                }
                //如果是套餐,更新一下主表里的字段
                if ($group->spu_type){
                    DB::table('erp_market_groups')->where('id',$group_data['id'])->update([
                        'union_origin_price'=>$union_origin_price,
                        'union_group_price'=>$union_group_price
                    ]);
                }
            }
            DB::commit();

            return [
                'status'=>1,
                'msg'=>'编辑成功'
            ];
        }catch (\Exception $e){
            DB::rollBack();
            return [
                'status'=>0,
                'msg'=>'编辑失败'
            ];
        }
    }

    //关闭
    public function groupBuyDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids'=>'required',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        DB::beginTransaction();
        try{
            DB::table('erp_market_groups')->whereIn('id',$request->ids)->update(['flag'=>1,'end_at'=>time()]);
            //如果有正在进行中的拼团，直接用机器人补团
            $groups=DB::table('erp_market_groups')->whereIn('id',$request->ids)->get();
            foreach ($groups as $group){
                if ($group->support_bot){
                    $open_ids=DB::table('erp_market_open_groups')->where('group_id',$group->id)->where('status',0)->pluck('id');
                    foreach ($open_ids as $open_id){
                        $user_count=DB::table('erp_market_group_buyers')->where('open_id',$open_id)->count();
                        $order_ids=DB::table('erp_market_group_buyers')->where('open_id',$open_id)->pluck('order_id');
                        $bot_count=$group->group_people_num - $user_count;
                        $insert_data=[];
                        for ($i=0;$i<$bot_count;$i++){
                            $random_bot_user = DB::table('wxuser_bot')->inRandomOrder()->first();//随机取一个机器人用户
                            $insert_data[]=[
                                'open_id'=>$open_id,
                                'user_id'=>$random_bot_user->id,
                                'group_detail_id'=>0,
                                'created_at'=>time(),
                                'is_bot'=>1,
                                'order_status'=>1,
                            ];
                        }
                        DB::table('erp_market_group_buyers')->insert($insert_data);
                        DB::table('erp_market_open_groups')->where('id',$open_id)->update(['status'=>1]);
                        DB::table('erp_stock_order')->whereIn('id',$order_ids)->update(['group_status'=>4]);
                    }
                }
            }

            DB::commit();
            return [
                'status'=>1,
                'msg'=>'关闭成功'
            ];
        }catch (\Exception $e){
            DB::rollBack();
            return [
                'status'=>0,
                'msg'=>'关闭失败'
            ];
        }
    }

    //团购详情
    public function groupBuyDetail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'=>'required',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        $name=DB::table('erp_market_groups')
            ->leftJoin('erp_mp_name_spu_link', 'erp_market_groups.mp_spu_id', 'erp_mp_name_spu_link.id')
            ->leftJoin('erp_spu_list', 'erp_mp_name_spu_link.spu_id', 'erp_spu_list.id')
            ->where('erp_market_groups.id',$request->id)
            ->value('erp_spu_list.name');
        $count=DB::table('erp_market_open_groups')->where('status',1)->where('group_id',$request->id)->count();
        $open_groups=DB::table('erp_market_open_groups')->where('group_id',$request->id)->select(['id','status'])->get();
        foreach ($open_groups as $open_group){
            $buyers=DB::table('erp_market_group_buyers')
                ->leftJoin('wxuser',function($q){
                    $q->on('erp_market_group_buyers.user_id','wxuser.id')
                        ->where('erp_market_group_buyers.is_bot',0);
                })
                ->leftJoin('wxuser_bot',function($q){
                    $q->on('erp_market_group_buyers.user_id','wxuser_bot.id')
                        ->where('erp_market_group_buyers.is_bot',1);
                })
                ->where('erp_market_group_buyers.open_id',$open_group->id)
                ->select([
                    'erp_market_group_buyers.is_bot',
                    'wxuser.nickname as user_name',
                    'wxuser_bot.nickname as bot_name',
                ])
                ->get();
            foreach ($buyers as $buyer){
                $open_group->buyers[]=$buyer->is_bot?$buyer->bot_name.'(机器人)':$buyer->user_name;
                $open_group->name=$name;
                $open_group->count=$count;
            }
        }
        /*return [
            'name'=>$name,
            'count'=>$count,
            'open_groups'=>$open_groups
        ];*/
        return $open_groups;
    }

    public function skucats(Request $request)
    {
        $list=DB::table('erp_product_class')
            ->where('type_id',0)
            ->where('flag',0)
            ->select(['id','name'])
            ->get();
        return $list;
    }

    public function skumpnames(Request $request)
    {
        $list=DB::table('erp_mp_name')
            ->where('flag', 0)
            ->where('is_show',1)
            ->select([
                'id','mp_name'
            ])
            ->get();
        return $list;
    }

    public function skutags(Request $request)
    {
        $list = DB::table('erp_tags')
            -> select(['id','name'])
            -> get();
        return $list;
    }

    public function mpskus(Request $request)
    {
        $list=DB::table('erp_product_price')
            ->leftJoin('erp_product_list','erp_product_price.product_id','erp_product_list.id')
            ->leftJoin('erp_mp_name','erp_product_price.mp_name_id','erp_mp_name.id')
            ->where('erp_product_price.flag', 0)
            ->where('erp_product_price.status', 1)
            ->where('erp_product_price.is_show', 1)
            ->where('erp_product_price.has_stock', '>', 0)
            ->where('erp_product_list.flag',0)
            ->when($request->filled('product_name'), function ($query) use ($request) {
                return $query->where('erp_product_list.product_name','like','%'.$request->product_name.'%');
            })
            ->select([
                'erp_product_price.id',
                'erp_mp_name.mp_name',
                'erp_product_list.product_name',
            ])
            ->get();
        return $list;
    }

    public function unions(Request $request)
    {
        $list=DB::table('erp_mp_name_spu_link')
            ->leftJoin('erp_spu_list','erp_mp_name_spu_link.spu_id','erp_spu_list.id')
            ->leftJoin('erp_mp_name','erp_mp_name_spu_link.mp_name_id','erp_mp_name.id')
            ->where('erp_mp_name.flag',0)
            ->where('erp_mp_name.is_show',1)
            ->where('erp_spu_list.flag',0)
            ->where('erp_spu_list.type',1)//套餐
            ->where('erp_mp_name_spu_link.flag',0)
            ->when($request->filled('spu_name'), function ($query) use ($request) {
                return $query->where('erp_spu_list.name','like','%'.$request->spu_name.'%');
            })
            ->select([
                'erp_mp_name_spu_link.id',
                'erp_mp_name.mp_name',
                'erp_spu_list.name',
            ])
            ->get();
        return $list;
    }
}
