<?php

namespace App\Http\Controllers\Api;

use App\Stock;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SKUController extends Controller
{

    public function maintainPrice(Request $request)
    {
        // 先做数据校验
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
            'id'  =>  'required|numeric',
            'price_s'     =>  'required|numeric',
            'price_a'     =>  'required|numeric',
            'price_b'     =>  'required|numeric',
            'price_c'     =>  'required|numeric',
            'price_d'     =>  'required|numeric'
        ]);

        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        // 判断是新增还是修改
        $price = DB::table('erp_product_price')
            -> where('product_id', $request->id)
            -> first();
        if ($price) {
            // 修改
            DB::table('erp_product_price')
            -> where('product_id', $request->id)
            -> update([
                'price_s'       => $request -> price_s,
                'price_a'       => $request -> price_a,
                'price_b'       => $request -> price_b,
                'price_c'       => $request -> price_c,
                'price_d'       => $request -> price_d,
                'updated_at'    =>  time()
            ]);
        } else {
            // 新增
            $priceId = DB::table('erp_product_price')
            ->insertGetId([
                'product_id' => $request -> id,
                'price_s'    => $request -> price_s,
                'price_a'    => $request -> price_a,
                'price_b'    => $request -> price_b,
                'price_c'    => $request -> price_c,
                'price_d'    => $request -> price_d,
                'flag'       => 0,
                'created_at' => time(),
                'updated_at' => time()
            ]);
        }

        return [
            'code' => 200,
            'msg' => '成功'
        ];
    }

    public function putOnSKUs(Request $request)
    {
        // 批量上架 ids
        $validator = Validator::make($request->all(), [
            'stockIds' => 'required|array|min:1',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $errors = Stock::putOnProcess($request->stockIds, $request->spu_id, $request->business_id);

        if (empty($errors)) {
            return [
                'code' => 200,
                'msg' => '编辑成功'
            ];
        } else {
            return [
                'code' => 500,
                'msg' =>  '部分失败：'.implode("；", $errors)
            ];
        }
    }

    public function putOffSKUs(Request $request)
    {
        // 批量下架 ids
        $validator = Validator::make($request->all(), [
            'stockIds' => 'required|array|min:1',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $errors = Stock::putOffProcess($request->stockIds, $request->spu_id, $request->business_id);

        if (empty($errors)) {
            return [
                'code' => 200,
                'msg' => '编辑成功'
            ];
        } else {
            return [
                'code' => 500,
                'msg' =>  '部分失败：'.implode("；", $errors)
            ];
        }

    }

    public function theSKUList(Request $request)
    {
        $where = [];
        if($request->warehouse){
            $where[] = [
                'warehouse_id','=',$request -> warehouse
            ];
        }

        // 库存
        $list = DB::table('erp_stock')
            -> leftJoin('erp_product_list','erp_stock.product_id','erp_product_list.id')
            -> where(['erp_stock.flag' => '0'])
            -> where('erp_stock.business_id',$request -> business_id)
            -> where($where)
            -> orderBy('erp_stock.id','desc')
            -> select([
                '*'
            ])
            -> paginate(10);

        return $this->listFilter($list);
    }

    public function listFilter($list)
    {
        foreach ($list as $key => $value) {
            $list[$key]->imageUrl = getImageUrl($value->image);
        }

        return $list;
    }

}