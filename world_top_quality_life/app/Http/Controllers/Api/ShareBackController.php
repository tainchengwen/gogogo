<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
class ShareBackController extends Controller
{
    //
    public function backAdd(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id',
            'image' => 'required'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        DB::table('erp_share_background')
            -> insert([
                'business_id' => $request->business_id,
                'type' => 1,
                'image' =>$request->image,
                'created_at' => time(),
                'updated_at' => time(),
            ]);
        return [
            'code' => 0,
            'msg' => '添加成功'
        ];

    }

    public function backList(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
       $list =  DB::table('erp_share_background')->where([
            'business_id' => $request->business_id,
            'is_del'      => 0
        ])->get()->toArray();
        return $list;
    }
    public function toImageUrl($info){
        foreach ($info as $key => $stock) {
            $info[$key]->imageUrl = empty($stock->image) ? '' : getImageUrl($stock -> image) ;
        }
        return $info;
    }

    public function backDel(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|numeric|exists:erp_share_background,id',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        DB::table('erp_share_background')->where('id',$request->id)->update([
            'is_del'      => 1,
            'updated_at'  => time()
        ]);
        return [
            'code' => 0,
            'msg' => '删除成功'
        ];
    }

    public function backShow(Request $request){
        $validator = Validator::make($request->all(), [
            'id'          => 'required|numeric|exists:erp_share_background,id',
            'business_id' => 'required|numeric|exists:erp_business,id',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        DB::transaction(function() use($request){

            DB::table('erp_share_background')->where('business_id',$request->business_id)->update([
                'is_show'      => 0,
                'updated_at'  => time()
            ]);
            DB::table('erp_share_background')->where('id',$request->id)->update([
                'is_show'      => 1,
                'updated_at'  => time()
            ]);

        });

        return [
            'code' => 0,
            'msg' => '操作成功'
        ];
    }

}
