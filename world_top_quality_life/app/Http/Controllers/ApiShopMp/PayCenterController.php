<?php

namespace App\Http\Controllers\ApiShopMp;

use App\Repositories\OrderRepository;
use App\Repositories\PayByCrossRepository;
use App\Repositories\PayRepository;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiShopMp\Controller;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\StockOrder;

class PayCenterController extends Controller
{
    public function __construct(OrderRepository $orderRepository, PayRepository $payRepository, PayByCrossRepository $payByCrossRepository)
    {
        $this->orderRepository = $orderRepository;
        $this->payRepository   = $payRepository;
        $this->payByCross = $payByCrossRepository;
    }

    /**
     * 支付页面
     */
    public function showPay(Request $request)
    {
        // 支付方式选择
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id',
            'orderIds'    => 'required|array|min:1',
        ]);

        if ($validator->fails()) {
            // 返回正确的格式结果
            return $this->errorResponse($request, [], '缺少或必要参数错误');
        }

        $showResult = $this->payRepository->show($request->business_id, $request->user->wxUserId, $request->orderIds);

        if ($showResult['success']) {
            return $this->successResponse($request, $showResult['data']);
        } else {
            return $this->errorResponse($request, [], $showResult['msg']);
        }
    }

    /**
     * 支付
     */
    public function pay(Request $request)
    {
        // 支付订单，修改状态，确认减少库存
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id',
            'orderIds'    => 'required|array|min:1',
            'payType'     => 'required'
        ]);

        if ($validator->fails()) {
            // 返回正确的格式结果
            return $this->errorResponse($request, [], '缺少或必要参数错误');
        }

        $payResult = $this->payRepository->pay(
            $request->business_id,
            $request->orderIds,
            $request->user->wxUserId,
            $request->payType,
            $request
        );

        if ($payResult['success']) {
            if($request->payType==3){
                return $this->successResponse($request, $payResult['data']);
            }
            $data=[];
            $open_group=DB::table('erp_market_group_buyers')->whereIn('order_id',$request->orderIds)->first();
            if ($open_group){
                $data=['open_group_id'=>$open_group->open_id];
            }
            return $this->successResponse($request, $data);
        } else {
            return $this->errorResponse($request, [], $payResult['msg']);
        }
    }

    // 提供给第三方的支付回调
    public function payNotify(Request $request)
    {
        //验签
        $result = $this->payByCross->verifyReceive($request);
        if ($result == 1) {
            switch($request->input('payResult')){
                case '10':
                    //此处做商户逻辑处理
                    DB::beginTransaction();

                    try{
                        StockOrder::asynOprate($request->input('orderId'));

                        DB::commit();
                    }catch(\Exception $exception){
                        \Log::error('在线支付回调异常-订单:'.$request->input('orderId').',时间:'.date('Y-m-d H:i:s').',信息:'.$exception->getMessage());
                        DB::rollBack();
                    }

                    $msg="deal Success, check sign success";
                    \Log::info('在线支付回调成功-订单:'.$request->input('orderId').',时间:'.date('Y-m-d H:i:s'));
                    break;
                default:
                    $msg="deal failed, check sign success";
                    \Log::error('在线支付回调失败-订单:'.$request->input('orderId').',时间:'.date('Y-m-d H:i:s'));
                    break;
            }
        }else{
            $msg="check sign failed";
            \Log::error('在线支付回调验签失败-时间:'.date('Y-m-d H:i:s'));
            abort(500);
        }

        echo "<result>{$msg}</result>";
    }

    /**
     * 支付结果查询
     * @param Request $request
     * @return array
     */
    public function payResultQuery(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'orderIds'    => 'required|array|min:1'
        ]);

        if ($validator->fails()) {
            // 返回正确的格式结果
            return $this->errorResponse($request, [], '缺少或必要参数错误');
        }

        $order_ids=$request->orderIds;

        $data=[];
        $open_group=DB::table('erp_market_group_buyers')->whereIn('order_id',$order_ids)->first();
        if ($open_group){
            $data=['open_group_id'=>$open_group->open_id];
        }

        $first_order=DB::table('erp_stock_order')->whereIn('id', $order_ids)->first();

        if($first_order->pay_status){
            return $this->successResponse($request,array_merge(['pay_status'=>1],$data),'支付成功');
        }
        DB::beginTransaction();
        //请求查询接口
        try{
            $result = $this->payByCross->orderQuery($first_order);
            if($result){
                StockOrder::asynOprate($first_order->online_order_num);
                DB::commit();
            }
        }catch (\Exception $exception){
            DB::rollBack();
            return $this->successResponse($request, ['pay_status'=>0], $exception->getMessage());
        }

        return $this->successResponse($request,array_merge(['pay_status'=>1],$data),'支付成功');
    }



    /**
     *  账户充值
     */
    public function rechargePay(Request $request)
    {
        // 支付订单，修改状态，确认减少库存
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id',
            'payMoney'    => 'required'
        ]);

        if ($validator->fails()) {
            // 返回正确的格式结果
            return $this->errorResponse($request, [], '缺少或必要参数错误');
        }
        $payResult = $this->payRepository->rechargePay($request);
        if ($payResult['success']) {
            return $this->successResponse($request, $payResult['data']);
        } else {
            return $this->errorResponse($request, [], $payResult['msg']);
        }
    }


    // 提供给第三方的支付回调
    public function rechargeNotify(Request $request)
    {
        //验签
        $result = $this->payByCross->verifyReceive($request);
        if ($result == 1) {
            switch($request->input('payResult')){
                case '10':
                    //此处做商户逻辑处理
                    DB::beginTransaction();

                    try{
                        StockOrder::asynOprateRecharge($request->input('orderId'));

                        DB::commit();
                    }catch(\Exception $exception){
                        \Log::error('在线支付回调异常-订单:'.$request->input('orderId').',时间:'.date('Y-m-d H:i:s').',信息:'.$exception->getMessage());
                        DB::rollBack();
                    }

                    $msg="deal Success, check sign success";
                    \Log::info('在线支付回调成功-订单:'.$request->input('orderId').',时间:'.date('Y-m-d H:i:s'));
                    break;
                default:
                    $msg="deal failed, check sign success";
                    \Log::error('在线支付回调失败-订单:'.$request->input('orderId').',时间:'.date('Y-m-d H:i:s'));
                    break;
            }
        }else{
            $msg="check sign failed";
            \Log::error('在线支付回调验签失败-时间:'.date('Y-m-d H:i:s'));
            abort(500);
        }

        echo "<result>{$msg}</result>";
    }
}
