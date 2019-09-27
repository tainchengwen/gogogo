<?php

namespace App\Http\Controllers\ApiShopMp;

use App\Repositories\MarketRepository;
use App\Repositories\SpecialRepository;
use App\Repositories\SPURepository;
use App\Repositories\CategoryRepository;
use App\Repositories\WarehouseRepository;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiShopMp\Controller;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TabController extends Controller
{
    public function __construct(
        SPURepository $spuRepository, 
        CategoryRepository $categoryRepository,
        WarehouseRepository $warehouseRepository,
        SpecialRepository $specialRepository,
        MarketRepository $marketRepository
    )
    {
        $this->spuRepository        = $spuRepository;
        $this->categoryRepository   = $categoryRepository;
        $this->warehouseRepository   = $warehouseRepository;
        $this->special   = $specialRepository;
        $this->market   = $marketRepository;
    }

    public function home(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($request, [], '缺少必要参数');
        }
        //  新版本 banners
        $bannerList = DB::table('erp_setting_banners')
                        ->where([
                            'business_id' => $request->business_id,
                            'is_show' => 1,
                            'is_del' => 0
                        ])
                        ->select(['id', 'image', 'type', 'keyword', 'sort_index'])
                        ->orderBy('sort_index', 'asc')
                        ->get();
        $banners_v1 = $bannerList->toArray();
        foreach($banners_v1 as $key => $value) {
            $value->image = getImageUrl($value->image);
        }

        $banners = [
            [
                'imageUrl' => 'https://fenithcdn.oss-cn-shanghai.aliyuncs.com/shopmp/banners/7-15-1.jpeg',
                'spuId'    => 90
            ],
            [
                'imageUrl' => 'https://fenithcdn.oss-cn-shanghai.aliyuncs.com/shopmp/banners/7-15-2.jpeg',
                'spuId'    => 85
            ]
            // [
            //     'imageUrl' => 'https://fenithcdn.oss-cn-shanghai.aliyuncs.com/shopmp/banners/6-8-1.jpeg',
            //     'spuId'    => 702
            // ]
        ];

        // 自营的一级分类
        // 这边是所有的一级分类列表
        $categroies = $this->categoryRepository->getCategoryTreeByDeep(1);

        // 热门精选
        $hotSelection = [
            
            [
                'imageUrl' => 'https://fenithcdn.oss-cn-shanghai.aliyuncs.com/banner_img/xinpin_09_11.jpeg',
                'tagName'  => '新品'
            ],
            [
                'imageUrl' => 'https://fenithcdn.oss-cn-shanghai.aliyuncs.com/banner_img/kaixue_0827.jpeg',
                'tagName'  => '开学季'
            ]
        ];

        //  限时特价 specials
        $specials = $this->special->getIndexSpecialList();

        //  推荐列表 recommended
        $deepOne = $this->categoryRepository->getCategoryTreeByDeep();
        $recommended = $this->spuRepository->home($request,$deepOne);
        //弹窗
        $popup=[];
        $popup['coupons']=$this->market->getCouponPopup($request);//获取弹窗优惠券

        //团购
        $group=$this->market->groupIndex();

        return $this->successResponse($request, 
            [
                'banners'      => $banners,
                'banners_v1'   => $banners_v1,
                'categroy'     => $categroies,
                'specials'     => $specials,
                'group'  => $group,
                'hotSelection' => $hotSelection,
                'recommended'  => $recommended,
                'popup'  => $popup,//弹窗
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
        $warehouses = $this->warehouseRepository->getMPWarehouses($request);
        
        $categroies = $this->categoryRepository->getCategoryTreeByDeep(2);

        // 热门精选
        $hotSelection = [
            [
                'imageUrl' => 'https://fenithcdn.oss-cn-shanghai.aliyuncs.com/shopmp/hotSelection/big1.png',
                'tagName'  => 'vip专享'
            ],
            [
                'imageUrl' => 'https://fenithcdn.oss-cn-shanghai.aliyuncs.com/shopmp/hotSelection/big2.png',
                'tagName'  => '限时特价'
            ],
        ];

        return $this->successResponse($request, 
            [
                'warehouses'   => $warehouses,
                'categroy'     => $categroies,
                'hotSelection' => $hotSelection,
            ]
        );
    }

    public function vip(Request $request)
    {
        return $this->successResponse(
            $request, 
            ['imageUrl'=>'https://fenithcdn.oss-cn-shanghai.aliyuncs.com/shopmp/vip/vip_0731.jpeg']
        );
    }

}