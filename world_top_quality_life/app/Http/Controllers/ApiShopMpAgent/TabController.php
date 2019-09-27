<?php

namespace App\Http\Controllers\ApiShopMpAgent;

use App\Repositories\SPURepository;
use App\Repositories\AgentRepository;
use App\Repositories\AgentCategoryRepository;
use App\Repositories\AgentNewSPURepository;
use App\Repositories\CategoryRepository;
use App\Repositories\WarehouseRepository;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiShopMpAgent\Controller;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TabController extends Controller
{
    public function __construct(
        SPURepository $spuRepository,
        AgentRepository $agentRepository,
        AgentNewSPURepository $agentSPURepository,
        CategoryRepository $categoryRepository,
        WarehouseRepository $warehouseRepository,
        AgentCategoryRepository $agentCategoryRepository
    )
    {
        $this->spuRepository           = $spuRepository;
        $this->agentRepository         = $agentRepository;
        $this->categoryRepository      = $categoryRepository;
        $this->agentCategoryRepository = $agentCategoryRepository;
        $this->warehouseRepository     = $warehouseRepository;
        $this->agentSPURepository      = $agentSPURepository;
    }

    public function home(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id'
        ]);
        // 进入控制器的时候要清楚几个事情，进来的是用户信息
        // 进来的是哪个事业部
        // 这个事业部是不是代理事业部
        // 理论上所有非自营事业部全部到转到这边来

        if ($validator->fails()) {
            return $this->errorResponse($request, [], '缺少必要参数');
        }

        // 配置
        $setting = $this->agentRepository->getAgentBasicSetting($request)['data'];
        // 轮播图
        $banners = $this->agentRepository->getBanners($request);

        // 获取所有分类
        $request->ignoreHide = true;
        $categories  = $this->agentCategoryRepository->getAgentCategoryTree($request)['data'];
        $recommended = $this->agentSPURepository->home($request, $categories);


        return $this->successResponse($request,
            [
                'setting'     => $setting,
                'banners'     => $banners,
                'recommended' => $recommended
            ]
        );

    }

    public function categories(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($request, [], '缺少必要参数');
        }

        // 变为馆区
        $request->ignoreHide = true;
        $categories = $this->agentCategoryRepository->getAgentCategoryTree($request)['data'];

        return $this->successResponse($request,
            [
                'categories'     => $categories,
            ]
        );
    }

}
