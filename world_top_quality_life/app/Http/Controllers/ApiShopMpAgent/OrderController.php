<?php

namespace App\Http\Controllers\ApiShopMpAgent;

use App\Repositories\AgentOrderRepository;
use App\Repositories\AgentCartRepository;
use App\Repositories\AgentfreightRepository;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiShopMpAgent\Controller;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Repositories\AgentNewSPURepository;

class OrderController extends Controller
{
    public function __construct(
        // 代理订单
        AgentOrderRepository $agentOrderRepository,
        // 代理购物车
        AgentCartRepository $agentCartRepository,
        // 运费计算
        AgentfreightRepository $agentfreightRepository,
        AgentNewSPURepository $agentSPURepository
    ){
        $this->agentOrderRepository   = $agentOrderRepository;
        $this->agentCartRepository    = $agentCartRepository;
        $this->agentfreightRepository = $agentfreightRepository;
        $this->agentSpuRepository      = $agentSPURepository;
    }

    /**
     * 确认订单页面信息
     * 填写订单
     */
    public function fill(Request $request)
    {
        // 组装订单信息
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id',
            'cartIds'     => 'required|array|min:1'
        ]);
        if ($validator->fails()) {
            // 返回正确的格式结果
            return $this->errorResponse($request, [], '缺少必要参数');
        }
        // 检测商品状态
        $checkResult = $this->agentOrderRepository->checkByCartIdsAndBusinessIdAndUserId($request->cartIds, $request->business_id, $request->user->wxUserId);
        if (!$checkResult['success']) {
            return $this->errorResponse($request, [], $checkResult['msg']);
        }

        // 拼装预支付信息
        $abResult = $this->agentOrderRepository->assemble($request);

        return $this->successResponse($request, $abResult);
    }

    /**
     * 直接下单
     */
    public function direct(Request $request)
    {
        // 组装订单信息
        $validator = Validator::make($request->all(), [
            'business_id'   => 'required|numeric|exists:erp_business,id',
            'spuId'         => 'required|numeric',
            'skuInfo'         => 'required|json',
            'num'           => 'required|numeric|min:1',
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
        // 先加入购物车
        $cartId           = $this->agentCartRepository->addDirect($request);
        $request->cartIds = [$cartId];

        $checkResult = $this->agentOrderRepository->checkByCartIdsAndBusinessIdAndUserId($request->cartIds, $request->business_id, $request->user->wxUserId);
        if (!$checkResult['success']) {
            return $this->errorResponse($request, [], $checkResult['msg']);
        }

        // 拼装预支付信息
        $abResult = $this->agentOrderRepository->assemble($request);
        return $this->successResponse($request, $abResult);
    }

    /**
     * 提交订单
     */
    public function order(Request $request)
    {
        // 组装订单信息
        $validator = Validator::make($request->all(), [
            'business_id'   => 'required|numeric|exists:erp_business,id',
            // 购物车Ids
            'cartIds'       => 'required|array|min:1',
            // 还有地址id
            'addressId'     => 'required|numeric|exists:erp_mp_shop_address,id',
            // 身份证id
            'idCardId'      => 'numeric',
            // 买家留言
            'message'       => 'max:50',
        ]);
        if ($validator->fails()) {
            // 返回正确的格式结果
            return $this->errorResponse($request, [], '缺少或必要参数错误');
        }
        // 库存check
        $checkResult = $this->agentOrderRepository->checkByCartIdsAndBusinessIdAndUserId($request->cartIds, $request->business_id, $request->user->wxUserId);
        if (!$checkResult['success']) {
            return $this->errorResponse($request, [], $checkResult['msg']);
        }

        $orderResult = $this->agentOrderRepository->order($request);


        if ($orderResult['success']) {
            return $this->successResponse($request, $orderResult['data']);
        } else {
            return $this->errorResponse($request, [], $orderResult['msg']);
        }
    }

    // 取消订单
    public function cancelOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id'   => 'required|numeric|exists:erp_business,id',
            'orderId'       => 'required|numeric|exists:erp_stock_order,id',
        ]);
        if ($validator->fails()) {
            // 返回正确的格式结果
            return $this->errorResponse($request, [], '缺少或必要参数错误');
        }

        // 生成订单，暂减库存
        $cancelResult = $this->agentOrderRepository->cancel($request->orderId, $request->business_id);

        if ($cancelResult['success']) {
            return $this->successResponse($request, []);
        } else {
            return $this->errorResponse($request, [], $cancelResult['msg']);
        }
    }

    // 更新运费信息
    public function expressPrice(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id',
            'cartIds'     => 'required|array|min:1',
            'addressId'   => 'required|numeric|exists:erp_mp_shop_address,id'
        ]);
        if ($validator->fails()) {
            // 返回正确的格式结果
            return $this->errorResponse($request, [], '缺少或必要参数错误');
        }

        $freight = $this->freightRepository->computedFreight($request->cartIds, $request->addressId);
        return $this->successResponse($request, ['expressPrice'=>$freight]);
    }

    /**
     * 订单详情
     */
    public function detail(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'business_id'   => 'required|numeric|exists:erp_business,id'
        ]);
        if ($validator->fails()) {
            // 返回正确的格式结果
            return $this->errorResponse($request, [], '缺少或必要参数错误');
        }

        // 生成订单，暂减库存
        $orderResult = $this->agentOrderRepository->getById($request, $id);
        if ($orderResult['success']) {
            return $this->successResponse($request, $orderResult['order']);
        } else {
            return $this->errorResponse($request, [], $orderResult['msg']);
        }
    }

    /**
     * 订单列表（带状态查询）
     */
    public function list(Request $request)
    {
        // 我的订单列表
        $validator = Validator::make($request->all(), [
            'business_id'   => 'required|numeric|exists:erp_business,id'
        ]);
        if ($validator->fails()) {
            // 返回正确的格式结果
            return $this->errorResponse($request, [], '缺少或必要参数错误');
        }

        // 生成订单，暂减库存
        $listResult = $this->agentOrderRepository->search($request);

        if ($listResult['success']) {
            return $this->successResponse($request, $listResult['data']);
        } else {
            return $this->errorResponse($request, [], $listResult['msg']);
        }


    }

}
