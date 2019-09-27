<?php

namespace App\Http\Controllers\ApiShopMpAgent;

use App\Repositories\AgentCartRepository;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiShopMpAgent\Controller;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Repositories\AgentNewSPURepository;

class CartController extends Controller
{
    public function __construct(AgentCartRepository $agentCartRepository, AgentNewSPURepository $agentSPURepository)
    {
        $this->agentCartRepository = $agentCartRepository;
        $this->agentSpuRepository      = $agentSPURepository;
    }

    public function list(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id'
        ]);

        if ($validator->fails()) {
            // 返回正确的格式结果
            return $this->errorResponse($request, [], '缺少必要参数');
        }

        $list = $this->agentCartRepository->searchAll($request);
        return $this->successResponse($request, $list);
    }

    public function num(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id'
        ]);
        if ($validator->fails()) {
            // 返回正确的格式结果
            return $this->errorResponse($request, [], '缺少必要参数');
        }

        $num = $this->agentCartRepository->getNum($request);
        return $this->successResponse($request, $num);
    }

    public function add(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id',
            'spuId'       => 'required|numeric',
            'num'         => 'required|numeric',
            'skuInfo'     => 'required|json'
        ]);
        if ($validator->fails()) {
            // 返回正确的格式结果
            return $this->errorResponse($request, [], '缺少必要参数');
        }
        $skuId = $this->agentSpuRepository->fetchSkuIdBySkuInfo($request);
        if($skuId == 0){
            // 返回正确的格式结果
            return $this->errorResponse($request, [], '找不到该商品');
        }
        $request->skuId = $skuId;
        $this->agentCartRepository->add($request);

        return $this->successResponse($request, [], '加入购物车成功！');
    }

    public function deleteAll(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id',
            'ids'         => 'required|array|min:1'
        ]);
        if ($validator->fails()) {
            // 返回正确的格式结果
            return $this->errorResponse($request, [], '缺少必要参数');
        }
        $this->agentCartRepository->deleteByIds($request->ids);

        return $this->successResponse($request, [], '删除成功！');
    }

    public function delete(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id'
        ]);
        if ($validator->fails()) {
            // 返回正确的格式结果
            return $this->errorResponse($request, [], '缺少必要参数');
        }
        $this->agentCartRepository->deleteByIds([$id]);

        return $this->successResponse($request, [], '删除成功！');
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id',
            'num'         => 'required|numeric'
        ]);
        if ($validator->fails()) {
            return $this->errorResponse($request, [], '缺少必要参数');
        }

        if (!$request->num > 0) {
            return $this->errorResponse($request, [], '数量必须大于0');
        }

        $request->id = $id;
        $check = $this->agentCartRepository->checkManagePermision($request);
        if (!$check['check']) {
            return $this->errorResponse($request, [], $check['msg']);
        }

        $this->agentCartRepository->updateBuyNum($request->id, $request->num);
        return $this->successResponse($request, []);
    }
}
