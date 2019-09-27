<?php

namespace App\Http\Controllers\ApiShopMp;

use App\Repositories\OrderRepository;
use App\Repositories\CartRepository;
use App\Repositories\FreightRepository;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiShopMp\Controller;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ExpressController extends Controller
{
    /*public function __construct(OrderRepository $orderRepository, CartRepository $cartRepository, FreightRepository $freightRepository)
    {
        $this->orderRepository   = $orderRepository;
        $this->cartRepository    = $cartRepository;
        $this->freightRepository = $freightRepository;
    }*/

    /**
     * 物流详情
     */
    public function show(Request $request, $id)
    {
        $validator = Validator::make($request->order_id, [
            'order_id'   => 'required|numeric|exists:erp_stock_order,id'
        ]);
        if ($validator->fails()) {
            // 返回正确的格式结果
            return $this->errorResponse($request, [], '缺少或必要参数错误');
        }

        //todo
    }
}