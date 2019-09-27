<?php

namespace App\Http\Controllers\ApiShopMp;

use App\Repositories\MarketRepository;
use App\Repositories\OrderRepository;
use App\Repositories\CartRepository;
use App\Repositories\FreightRepository;
use App\Repositories\SpecialRepository;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiShopMp\Controller;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Refund;

class OrderController extends Controller
{
    public function __construct(
        OrderRepository $orderRepository,
        CartRepository $cartRepository,
        FreightRepository $freightRepository,
        SpecialRepository $specialRepository,
        MarketRepository $marketRepository
    ){
        $this->orderRepository   = $orderRepository;
        $this->cartRepository    = $cartRepository;
        $this->freightRepository = $freightRepository;
        $this->special = $specialRepository;
        $this->market = $marketRepository;
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
        $checkResult = $this->orderRepository->checkByCartIdsAndBusinessIdAndUserId($request->cartIds, $request->business_id, $request->user->wxUserId);
        if (!$checkResult['success']) {
            return $this->errorResponse($request, [], $checkResult['msg']);
        }
        //检测限购
        $limit_buy=$this->orderRepository->checkLimitByCartIds($request->cartIds,$request->business_id,$request->user->wxUserId,$request->user->market_class);
        if (!$limit_buy['success']) {
            return $this->errorResponse($request, [], $limit_buy['msg']);
        }

        // 拼装预支付信息
        if (empty($request->version)) {
            $abResult = $this->orderRepository->assemble($request);
        } else {
            $abResult = $this->orderRepository->assemble_1_1($request);
        }

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
            'skuId'         => 'required|numeric',
            'num'           => 'required|numeric|min:1',
            'group_id'           => 'sometimes',//团购id
            'price_id'           => 'sometimes',
            'group_buy_type'           => 'sometimes',//团购页面的购买方式,1单独购买2团购价购买，开团必须
            'open_group_id'           => 'sometimes',//参团必须
        ]);
        if ($validator->fails()) {
            // 返回正确的格式结果
            return $this->errorResponse($request, [], '缺少必要参数');
        }
        // 先加入购物车
        $add_result           = $this->cartRepository->addDirect($request);
        if (!$add_result['status']) {
            return $this->errorResponse($request, [], $add_result['msg']);
        }
        $request->cartIds = [$add_result['cartid']];

        $checkResult = $this->orderRepository->checkByCartIdsAndBusinessIdAndUserId($request->cartIds, $request->business_id, $request->user->wxUserId);
        if (!$checkResult['success']) {
            return $this->errorResponse($request, [], $checkResult['msg']);
        }

        //检测限时特价
        if($request->has('special_id') && !empty($request->special_id)){
            $special_ids=explode('_',$request->special_id);
            if(count($special_ids)>1){
                $special=$this->special->getUnionSpecialSku($special_ids);
            }else{
                $special=$this->special->getSpecialSku($special_ids);
            }
            if ($special->special_code != 0 || $special->can_buy_num < $request->num){
                return $this->errorResponse($request, [], '部分商品已下架或库存不足！');
            }
        }

        //检测团购
        if ($request->filled('group_id')){
            $group=DB::table('erp_market_groups')->where('id',$request->group_id)->first();
            if (!$group || $group->flag==1 || time()>$group->end_at || time()<$group->begin_at || ($group->spu_type==0 && !$request->filled('price_id'))){
                return $this->errorResponse($request, [], '该团购已失效');
            }
            $request->offsetSet('group',$group);
        }

        // 拼装预支付信息
        if (empty($request->version)) {
            $abResult = $this->orderRepository->assemble($request);
        } else {
            $abResult = $this->orderRepository->assemble_1_1($request);
        }
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
            // 购物车Ids  继续传给我
            'cartIds'       => 'required|array|min:1',
            // 还有地址id
            'addressId'     => 'required|numeric|exists:erp_mp_shop_address,id',
            // 买家留言
            'message'       => 'max:50',
        ]);
        if ($validator->fails()) {
            // 返回正确的格式结果
            return $this->errorResponse($request, [], $validator->errors());
        }
        //新增type   0的时候 首次进入 如果有拆单其情况 弹出弹框  1 不拆 2 拆
        $request->type = 1;
        // 库存check
        $checkResult = $this->orderRepository->checkByCartIdsAndBusinessIdAndUserId($request->cartIds, $request->business_id, $request->user->wxUserId,$request->type);
        if (!$checkResult['success']) {
            if(isset($checkResult['retry']) && $checkResult['retry'] == 1){
                return [
                    'status'    => 403,
                    'msg'       => $checkResult['msg'],
                    'token'     => empty($request->refreshToken) ? '' : $request->refreshToken,
                    'data'      => []
                ];
            }else{
                return $this->errorResponse($request, [], $checkResult['msg']);
            }

        }

        //检测限购
        $limit_buy=$this->orderRepository->checkLimitByCartIds($request->cartIds,$request->business_id,$request->user->wxUserId,$request->user->market_class);
        if (!$limit_buy['success']) {
            return $this->errorResponse($request, [], $limit_buy['msg']);
        }

        //检测团购
        if ($request->filled('groups')){
            $group=DB::table('erp_market_groups')->where('id',$request->groups['group_id'])->first();
            if (!$group || $group->flag==1 || time()>$group->end_at || time()<$group->begin_at || ($group->spu_type==0 && !$request->groups['price_id'])){
                return $this->errorResponse($request, [], '该团购已失效');
            }
            if ($request->groups['open_group_id']){
                $open_group=DB::table('erp_market_open_groups')->find($request->groups['open_group_id']);
                if (!$open_group || $open_group->status!=0){
                    return $this->errorResponse($request, [], '该团购已失效');
                }
            }
            $avaliable_group_ids=$group->spu_type==1?$this->market->getUnionGroupIds():$this->market->getNormalGroupIds();
            if (!$avaliable_group_ids->contains($group->id)){
                return $this->errorResponse($request, [], '该团购已失效');
            }

            $request->offsetSet('group',$group);
        }

        if (empty($request->version)) {
            $orderResult = $this->orderRepository->order($request);
        } else {
            $orderResult = $this->orderRepository->order_1_1($request);
        }

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
        $cancelResult = $this->orderRepository->cancel($request->orderId, $request->business_id);

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

        $expressPriceResult = $this->freightRepository->computedFreight($request->cartIds, $request->addressId);
        $freight = $expressPriceResult['total_freight'];
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
        $orderResult = $this->orderRepository->getById($request, $id);
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
        $listResult = $this->orderRepository->search($request);

        if ($listResult['success']) {
            return $this->successResponse($request, $listResult['data']);
        } else {
            return $this->errorResponse($request, [], $listResult['msg']);
        }


    }

    public function refund(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id'   => 'required|numeric|exists:erp_business,id',
            'id'   => 'required|numeric|exists:erp_stock_order,id',//订单id
            'reason'   => 'required',
        ]);
        if ($validator->fails()) {
            // 返回正确的格式结果
            return $this->errorResponse($request, [], '缺少或必要参数错误');
        }
        //检测是否合规
        $order=DB::table('erp_stock_order')->find($request->id);
        if ($order->flag == 1 || $order->user_id != $request->user->wxUserId || $order->pay_status == 0){
            return $this->errorResponse($request, [], '非法操作');
        }
        if ($order->print_express == 1 || $order->print_distribution == 1){
            return $this->errorResponse($request, [], '已发货，不可申请退款');
        }
        $refund=Refund::where('order_id',$order->id)->first();
        if ($refund){
            return $this->errorResponse($request, [], '已申请过退款');
        }

        //新增退款申请记录
        Refund::create([
            'order_id'=>$order->id,
            'request_reason'=>$request->reason,
            'created_at'=>time(),
        ]);

        return $this->successResponse($request, [], '申请成功，请等待审核');
    }

}
