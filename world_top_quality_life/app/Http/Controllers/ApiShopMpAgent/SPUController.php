<?php

namespace App\Http\Controllers\ApiShopMpAgent;

use App\Repositories\AgentNewSPURepository;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiShopMpAgent\Controller;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SPUController extends Controller
{
    public function __construct(AgentNewSPURepository $agentSPURepository)
    {
        $this->agentSPURepository = $agentSPURepository;
    }

    /**
     * 商品列表
     */
    public function spus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id'    => 'required|numeric',
            'class_id'       => 'sometimes',
            'keyword'        => 'sometimes'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($request, [], '缺少必要参数');
        }

        // 解码
        if ($request->keyword) {
            $request->keyword = urldecode($request->keyword);
        }

        $list = $this->agentSPURepository->searchPaginate($request);
        // 通过   business_id  获取该代理的相关配置
        return $this->successResponse($request, $list);
    }

    /**
     * 商品详情
     */
    public function spu(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'business_id'   => 'required|numeric'
        ]);
        if ($validator->fails()) {
            return $this->errorResponse($request, [], '缺少必要参数');
        }

        // 组装数据  所有的skus
        $assembledSPU = $this->agentSPURepository->spuInfo($id);

        return $this->successResponse($request, $assembledSPU);
    }

    /**
     * 商品搜索
     */
    public function spusSearch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id'    => 'required|numeric',
            'class_id'       => 'sometimes',
            'keyword'        => 'sometimes'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($request, [], '缺少必要参数');
        }

        // 解码
        if ($request->keyword) {
            $request->keyword = urldecode($request->keyword);
        }

        $list = $this->agentSPURepository->searchByKeyWord($request);
        // 通过   business_id  获取该代理的相关配置
        return $this->successResponse($request, $list);
    }
}
