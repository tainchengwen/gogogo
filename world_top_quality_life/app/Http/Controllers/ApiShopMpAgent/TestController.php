<?php

namespace App\Http\Controllers\ApiShopMpAgent;

use App\Repositories\OrderRepository;
use App\Repositories\CartRepository;
use App\Repositories\SKURepository;
use App\Repositories\AddressRepository;
use App\Repositories\FreightRepository;
use App\Repositories\CategoryRepository;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiShopMpAgent\Controller;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TestController extends Controller
{

    public function __construct(
        OrderRepository $orderRepository,
        CartRepository $cartRepository,
        FreightRepository $freightRepository,
        AddressRepository $addressRepository,
        SKURepository $skuRepository,
        CategoryRepository $categoryRepository
    ){
        $this->orderRepository    = $orderRepository;
        $this->cartRepository     = $cartRepository;
        $this->skuRepository      = $skuRepository;
        $this->freightRepository  = $freightRepository;
        $this->addressRepository  = $addressRepository;
        $this->categoryRepository = $categoryRepository;
    }

    public function test(Request $request)
    {
        var_dump(2);exit;
    }

    public function debug(Request $request)
    {
        return $this->successResponse($request, []);
    }

}