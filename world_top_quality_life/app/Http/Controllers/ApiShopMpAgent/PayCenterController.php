<?php

namespace App\Http\Controllers\ApiShopMpAgent;

use Illuminate\Http\Request;
use App\Repositories\PayByCrossRepository;
use App\StockOrder;
use DB;
use Validator;
use App\Repositories\IdentityCardRepository;

class PayCenterController extends Controller
{
    protected $payByCross;

    public function __construct(PayByCrossRepository $payByCrossRepository,IdentityCardRepository $identityCardRepository)
    {
        $this->payByCross = $payByCrossRepository;
        $this->identityCardRepository = $identityCardRepository;
    }

    /**
     *显示支付页面的数据
     */
    public function showPay(Request $request)
    {
        $validator = Validator::make(['orders'=>$request->orders], [
            'orders' => 'required|regex:/^[0-9]+(_[0-9]+)*$/'
        ]);

        if ($validator->fails()) {
            // 返回正确的格式结果
            return $this->errorResponse($request, [], '缺少必要参数');
        }

        $order_ids=explode('_',$request->orders);

        $order_models=StockOrder::whereIn('id',$order_ids)->orderBy('id','asc')->get();

        //获取身份证信息
        $ident_ids=$order_models->pluck('ident_id');
        $idCard = $this->identityCardRepository->get($ident_ids);

        $products_data=[];
        $count=0;

        $order_models->each(function ($item, $key) use(&$products_data,&$count) {
            //
            $item->shopCarts->each(function ($i, $k) use(&$products_data,&$count) {
                $products_data[]=[
                    'spuName'=>$i->spuName,
                    'num'=>$i->num,
                    'unitPrice'=>$i->unitPrice
                ];
                $count+=$i->num;
            });
        });

        $data=[
            'order_num_list'=>$order_models->pluck('order_num')->all(),
            'total_price'=>$order_models->sum('price'),
            'total_freight'=>$order_models->sum('freight'),
            'products_list'=>$products_data,
            'products_count'=>$count,
            'real_pay_price'=>bcadd($order_models->sum('price'),$order_models->sum('freight'),2),
            'pay_method'=>3,
            'idCard'=>$idCard
        ];

        return $this->successResponse($request,$data);
    }

    /**
     * 支付（发起汇付跨境支付）
     */
    public function pay(Request $request)
    {
        $order_ids=explode('_',$request->orders);

        $stock_order = new StockOrder();
        //检查订单状态
        $checkResult = $stock_order->checkOrdersByIds($request->user->business_id, $request->user->wxUserId, $order_ids);
        // 订单不正常
        if ($checkResult['code'] !== 200) {
            return $this->errorResponse($request, [], $checkResult['msg']);
        }
        //其他检查todo

        //检查身份证
        if(!$request->filled('idCardId')){
            return $this->errorResponse($request, [], '未选择身份证');
        }
        //更新订单表里的身份证信息
        $id_card=$this->identityCardRepository->get([$request->idCardId]);
        if (!$id_card){
            return $this->errorResponse($request, [], '该身份证不存在，请重新添加');
        }
        DB::table('erp_stock_order')->whereIn('id',$order_ids)->update([
            'idNumber'=>$id_card->idNumber,
            'imageFront'=>$id_card->imageFront,
            'imageBack'=>$id_card->imageBack,
            'ident_id'=>$id_card->id,
        ]);

        $first_order=DB::table('erp_stock_order')->whereIn('id', $order_ids)->first();
        $total_prices=DB::table('erp_stock_order')->whereIn('id', $order_ids)->sum('price');
        $total_freight=DB::table('erp_stock_order')->whereIn('id', $order_ids)->sum('freight');

        if (empty($first_order->idNumber)){
            return $this->errorResponse($request, [], '该订单的身份证号不存在，请重新添加');
        }

        $stock_order->price=$total_prices;
        $stock_order->freight=$total_freight;
        $stock_order->order_num=$stock_order->createOnlineOrder($order_ids,$request);
        $stock_order->name=$id_card->name;
        $stock_order->phone=$first_order->phone;
        $stock_order->idNumber=$first_order->idNumber;
        $stock_order->created_at=$first_order->created_at;
        $stock_order->stock_order_ids=$order_ids;

        //发起请求
        try{
            $data = $this->payByCross->send($stock_order,$request);
        }catch (\Exception $exception){
            return $this->errorResponse($request, [], $exception->getMessage());
        }
        return $this->successResponse($request,$data);
    }

    /**
     * 支付异步回调
     */
    public function asynNotify(Request $request)
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
     * 订单支付结果查询
     */
    public function payResultQuery(Request $request)
    {
        $order_ids=explode('_',$request->orders);

        $first_order=DB::table('erp_stock_order')->whereIn('id', $order_ids)->first();

        if($first_order->pay_status){
            return $this->successResponse($request,['pay_status'=>1],'支付成功');
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

        return $this->successResponse($request,['pay_status'=>1],'支付成功');
    }
}