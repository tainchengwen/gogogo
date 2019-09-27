<?php

namespace App\Http\Controllers\ApiShopMp;

use App\Repositories\CartRepository;
use App\Repositories\SPURepository;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiShopMp\Controller;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    public function __construct(CartRepository $cartRepository)
    {
        $this->cartRepository = $cartRepository;
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

        $list = $this->cartRepository->searchAll($request);
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

        $num = $this->cartRepository->getNum($request);
        return $this->successResponse($request, $num);
    }

    public function add(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id',
            'spuId'       => 'required|numeric',
            'skuId'       => 'required|numeric',
            'num'         => 'required|numeric'
        ]);
        if ($validator->fails()) {
            // 返回正确的格式结果
            return $this->errorResponse($request, [], '缺少必要参数');
        }

        $result=$this->cartRepository->add($request);

        if (!$result['status']) {
            return $this->errorResponse($request, [], $result['msg']);
        }

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
        $this->cartRepository->deleteByIds($request->ids);

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
        $this->cartRepository->deleteByIds([$id]);

        return $this->successResponse($request, [], '删除成功！');
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id',
            'id'          => 'required|numeric',
            'num'         => 'required|numeric'
        ]);
        if ($validator->fails()) {
            // var_dump($validator->errors());exit;
            // 返回正确的格式结果
            return $this->errorResponse($request, [], '缺少必要参数');
        }

        $check = $this->cartRepository->checkManagePermision($request);
        if (!$check['check']) {
            return $this->errorResponse($request, [], $check['msg']);
        }

        //检测限购
        $limit_buy=$this->cartRepository->checkLimitBuyNum($request);
        if (!$limit_buy['success']) {
            return $this->errorResponse($request, [], $limit_buy['msg']);
        }

        $this->cartRepository->updateBuyNum($request->id, $request->num);
        return $this->successResponse($request, []);
    }
}