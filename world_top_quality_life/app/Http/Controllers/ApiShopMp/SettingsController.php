<?php

namespace App\Http\Controllers\ApiShopMp;

use Illuminate\Http\Request;
use App\Http\Controllers\ApiShopMp\Controller;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    public function config(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id'
        ]);
        if ($validator->fails()) {
            // 返回正确的格式结果
            return $this->errorResponse($request, [], '缺少必要参数');
        }
        // 通过   business_id  获取该代理的相关配置
        return $this->successResponse($request, 
            [
                'color' =>  '#fff'
            ]
        );
    }

}