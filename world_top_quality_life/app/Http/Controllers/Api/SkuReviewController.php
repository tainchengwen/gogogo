<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SkuReviewController extends Controller
{
    //提交审核
    public function reviewAdd(Request $request){
        // 先做数据校验
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
            'sku_id'  =>  'required|array',
//            'sku_num' =>  'required|numeric',
//            'pic'     =>  'required',
            'to_store_house_id' =>  'required|numeric',
            'store_type'  => 'required',   //1公有库位 0 私有
            'public_no' => 'sometimes',
        ]);

        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        if(DB::table('erp_sku_review')->where([
            'business_id' => $request->business_id,
            'sku_id'      => $request->sku_id
        ])->first()){
            return [
                'code' => 1,
                'msg' => '该sku已申请过'
            ];
        }

        if($request->store_type ==1){
            $storeInfo = DB::table('erp_storehouse')
                        ->leftJoin('erp_warehouse','erp_warehouse.id','erp_storehouse.warehouse_id')
                        ->where([
                            'erp_storehouse.business_id' => $request->business_id,
                            'erp_warehouse.business_id'  => 0,
                        ])
                        ->select('erp_storehouse.*')->first();

            if($storeInfo){
                $request->to_store_house_id = $storeInfo->id;
            }else{
                $ware = DB::table('erp_warehouse')->where('business_id',0)->first();
                $request->to_store_house_id = DB::table('erp_storehouse')->insertGetId([
                    'name'         => '公共库',
                    'warehouse_id' => $ware->id,
                    'created_at'   => time(),
                    'updated_at'   => time(),
                    'business_id'  => $request->business_id
                ]);
            }
        }
        $array  = $request->sku_id;
        $insertArr = [];
        foreach($array as $v){
            $skuInfo = DB::table('erp_product_list')->where([
                'id' => $v
            ])->first();
            $insertArr[] = [
                'sku_id'      => $v,
                'business_id' => $request->business_id,
                'price'       => $skuInfo->price,
                'private_no'  => $skuInfo->product_no,
                'public_no'   => isset($request->public_no)?$request->public_no:0,
                'status'      => 0,
                'pic'         => $request->pic,
                'to_store_house_id'=> $request->to_store_house_id,
                'created_at'  => time(),
                'updated_at'  => time(),
            ];
        }

        if(DB::table('erp_sku_review')->insert($insertArr)){
            return [
                'code' => 0,
                'msg' => '提交成功'
            ];
        }else{
            return [
                'code' => 1,
                'msg' => '提交失败'
            ];
        }
    }

    //编辑
    public function reviewEdit(Request $request){
        // 先做数据校验
        $validator = Validator::make($request->all(), [
            'id'      => 'required|numeric|exists:erp_sku_review,id',
            'sku_num' =>  'required|numeric',
            'price'   =>  'required',
            'pic'     =>  'required',
            'to_store_house_id' =>  'required|numeric',
            'private_no' => 'required'
        ]);

        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        if(DB::table('erp_sku_review')->where('id',$request->id)->update([
            'sku_num'     => $request->sku_num,
            'can_buy_num' => $request->sku_num,
            'price'       => $request->price,
            'private_no'  => $request->private_no,
            'pic'         => $request->pic,
            'to_store_house_id'=> $request->to_store_house_id,
            'updated_at'  => time(),
        ])){
            return [
                'code' => 0,
                'msg' => '提交成功'
            ];
        }else{
            return [
                'code' => 1,
                'msg' => '提交失败'
            ];
        }
    }
    //被拒绝重新提交审核
    public function reviewRepeat(Request $request){
        // 先做数据校验
        $validator = Validator::make($request->all(), [
            'id'      => 'required|numeric|exists:erp_sku_review,id',
        ]);

        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        if(DB::table('erp_sku_review')->where('id',$request->id)->update([
            'status'      => 0,
            'updated_at'  => time(),
        ])){
            return [
                'code' => 0,
                'msg' => '提交成功'
            ];
        }else{
            return [
                'code' => 1,
                'msg' => '提交失败'
            ];
        }
    }

    //详情
    public function reviewInfo(Request $request){
        // 先做数据校验
        $validator = Validator::make($request->all(), [
            'id'      => 'required|numeric|exists:erp_sku_review,id',
        ]);

        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        return DB::table('erp_sku_review')->where('id',$request->id)->first();
    }



    public function reviewPass(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|numeric|exists:erp_sku_review,id',
        ]);

        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        DB::beginTransaction();
        try{
            //审核成功
            $user = auth('api')->user();
            DB::table('erp_sku_review')->where('id',$request->id)->update([
                'status'      => 1,
                'updated_at'  => time(),
                'reviewd_at'  => time(),
                'act_user'   => $user->id
            ]);
            $review = DB::table('erp_sku_review')->where('id',$request->id)->first();

            DB::table('erp_product_list')->where('id',$review->sku_id)->update(['is_public'=>1]);


            DB::table('erp_sku_public_log')->insert([
                'sku_id'      => $review->sku_id,
                'business_id' => $review->business_id,
                'new_num'     => $review->sku_num,
                'old_num'     => 0,
                'type'        => 'apply',
                'created_at'  => date("Y-m-d H:i:s",time())
            ]);
            DB::commit();
            return [
                'code' => 0,
                'msg' => '审核成功'
            ];
        }catch (\Exception $e){
            DB::rollBack();
            return [
                'code' => 1,
                'msg' => '审核失败'
            ];
        }
    }

    public function reviewRefuse(Request $request){
        $validator = Validator::make($request->all(), [
            'id'     => 'required|numeric|exists:erp_sku_review,id',
            'reason' => 'required',
        ]);

        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        $user = auth('api')->user();
        if(DB::table('erp_sku_review')->where('id',$request->id)->update([
            'status'     => 2,
            'updated_at' => time(),
            'reviewd_at'=> time(),
            'act_user'   => $user->id,
            'reason'     => $request->reason,
        ])){
            return [
                'code' => 0,
                'msg' => '已拒绝'
            ];
        }else{
            return [
                'code' => 1,
                'msg' => '拒绝失败'
            ];
        }
    }

    public function reviewList(Request $request){
        // 先做数据校验
        $validator = Validator::make($request->all(), [
            'status' => 'required'
        ]);

        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        $list = DB::table('erp_sku_review')
            ->leftJoin('erp_product_list','erp_sku_review.sku_id','erp_product_list.id')
            ->leftJoin('erp_business','erp_business.id','erp_sku_review.business_id')
            -> leftJoin('erp_agent_spu_category as product_class','erp_product_list.class_id','product_class.id')
            ->where(function($query)use($request){
                if($request->business_id){
                    $query->where('erp_sku_review.business_id',$request->business_id);
                }
                if(isset($request->status)){
                    $query->where('erp_sku_review.status',$request->status);
                }
                if($request->product_name){
                    $query->where('erp_product_list.product_name','like','%'.trim($request -> product_name).'%');
                }
                if($request->product_no){
                    $query->where('erp_product_list.product_no','like','%'.trim($request -> product_no).'%');
                }
            })
            -> select([
                'erp_sku_review.*',
                'erp_business.name as business_name',
                'erp_sku_review.status as review_status',
                'erp_sku_review.id as review_id',
                'erp_product_list.*',
                'product_class.name as product_class_name',
            ])
            ->paginate(20);

        return  $list;
    }


    //修改数量

    public function skuNumChange(Request $request){
        $validator = Validator::make($request->all(), [
            'id'     => 'required|numeric|exists:erp_sku_review,id',
            'num'    => 'required|numeric'
        ]);

        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        DB::beginTransaction();
        try{
            $review = DB::table('erp_sku_review')->where('id',$request->id)->first();

            DB::table('erp_sku_review')->where('id',$request->id)->update([
                'sku_num'     => $request->num,
                'can_buy_num' => $request->num,
                'updated_at'  => time(),
            ]);
            DB::table('erp_sku_public_log')->insert([
                'sku_id'      => $review->sku_id,
                'business_id' => $review->business_id,
                'new_num'     => $request->num,
                'old_num'     => $review->can_buy_num,
                'type'        => 'change',
                'created_at'  => date("Y-m-d H:i:s",time())
            ]);
            DB::commit();
            return [
                'code' => 0,
                'msg' => '修改成功'
            ];
        }catch (\Exception $e){
            DB::rollBack();
            return [
                'code' => 1,
                'msg' => '修改失败'
            ];
        }


    }

}
