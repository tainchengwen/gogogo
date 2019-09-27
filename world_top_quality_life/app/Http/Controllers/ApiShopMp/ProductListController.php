<?php

namespace App\Http\Controllers\ApiShopMp;

use App\ProductList;
use Redis;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductListController extends Controller
{
    //商品列表
    public function theList(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            //'photo_ids' => 'required|json'
        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }

        $list = DB::table('goods_list')
            -> paginate(20);

        foreach($list as $k => $vo){
            $list[$k] -> imgurl = url('uploads').'/'.$vo -> image;
        }
        return $list;
    }

    public function test(Request $request)
    {
        // $order = DB::table('erp_stock_order as order')
        //     -> leftJoin('wxuser','order.user_id','wxuser.id')
        //     -> leftJoin('erp_warehouse','order.warehouse_id','erp_warehouse.id')
        //     -> leftJoin('users','order.sale_user_id','users.id')
        //     -> select([
        //         'order.*',
        //         'erp_warehouse.name as erp_warehouse_name',
        //         'users.name as sale_user_name',
        //         'wxuser.nickname'
        //     ])
        //     -> where([
        //         'order.flag' => 0,
        //     ]) 
        //     -> get();
        //     ;

        exit;

    }

    //商品详情
    public function detail(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'product_id' => 'required'
            //'photo_ids' => 'required|json'
        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }

        $info = DB::table('goods_list')
            -> where([
                'id' => $request -> product_id
            ])
            -> select([
                'goods_list.product_id as product_no',
                'goods_list.image',
                'goods_list.product_name',
                'goods_list.price',
            ])
            -> first();

        $info -> vip_price = 183.5;
        $info -> imgurl = url('uploads').'/'.$info -> image;

        //查找多图
        $image_details = DB::table('mpshop_product_images')
            -> where([
                'product_no' => $info -> product_no,
                'flag' => 0
            ]) -> get();
        if(count($image_details)){
            $temp = [];
            foreach($image_details as $k => $vo){
                $temp[]  = getImageUrl($vo -> image);
            }
            $info -> info_image = $temp;

        }else{
            $info -> info_image = [
                url('uploads').'/'.$info -> image,
                url('uploads').'/'.$info -> image,
                url('uploads').'/'.$info -> image,
            ];
        }
        return [
            'code' => 200,
            'info' => $info
        ];

    }

}
