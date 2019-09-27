<?php

namespace App\Http\Controllers\Api;

use App\Business;
use App\SPUList;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BusinessSPUController extends Controller
{
    public function theList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        // 原生sql写法
        $list = DB::table('erp_spu_list')
        ->leftJoin(DB::raw('(SELECT * FROM `erp_business_spu_link` where business_id='. $request->business_id .') b'), 
        'erp_spu_list.id', 'b.spu_id'
        )
        ->whereNull('b.business_id')
        ->get();

        var_dump($list);
    }

    public function addBusinessLinkSPUs(Request $request)
    {
        // 批量上架 ids
        $validator = Validator::make($request->all(), [
            'business_id'   => 'required|numeric|exists:erp_business,id',
            'spuIds'        => 'required|array|min:1'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $this->addSPULinks($request->spuIds, $request->business_id);

        return [
            'code' => 200,
            'msg' => '添加成功'
        ];
    }
    public function delBusinessLinkSPUs()
    {

    }

    private function addSPULinks($spuIds, $business_id)
    {
        $insertData = [];
        foreach ($spuIds as $spuId) {
            array_push($insertData, [
                'spu_id'        => $spuId,
                'business_id'   => $business_id,
                'create_at'     => time()
            ]);
        }
        DB::table('erp_business_spu_link')->insert($insertData);
        return true;
    }

}