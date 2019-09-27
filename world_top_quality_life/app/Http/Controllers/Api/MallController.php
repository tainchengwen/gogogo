<?php

namespace App\Http\Controllers\Api;

use App\Repositories\SKURepository;
use App\Repositories\SPURepository;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use App\Repositories\WarehouseRepository;
use App\Repositories\AgentRepository;
use App\Repositories\AgentCategoryRepository;
use App\MpNameSpuList;

class MallController extends Controller
{

    public function __construct(
        WarehouseRepository $warehouseRepository,
        AgentRepository $agentRepositoy,
        AgentCategoryRepository $agentCategoryRepository,
        SKURepository $SKURepository
    ){
        $this->warehouseRepository     = $warehouseRepository;
        $this->agentRepository         = $agentRepositoy;
        $this->agentCategoryRepository = $agentCategoryRepository;
        $this->skuRepository = $SKURepository;
    }

    //价格维护
    public function priceManage(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }
    }

    // 增加配置信息
    public function mallsettingAdd(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|numeric|exists:erp_setting,id',
            'business_id' => 'required|numeric|exists:erp_setting,business_id',
            'color' => 'required'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        
        DB::table('erp_setting')
            ->insert([
                'business_id' => $request -> business_id,
                'color' => $request -> color,
                'is_all' => $request -> checkednum,
                'created_at' => time()
            ]);

        return [
            'code' => 200,
            'msg' => '添加成功'
        ];
    }

    // 修改配置信息
    public function mallsettingUpdate(Request $request){
        $validator = Validator::make($request->all(), [
            'color' => 'required',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        $info = DB::table('erp_setting')
                    ->where('business_id', '=', $request -> business_id)
                    ->first();
        if(!$info){
            return [
                'code' => 500,
                'msg' => '修改失败'
            ];
        }
        DB::table('erp_setting')
            ->where([
                'business_id' => $request -> business_id
            ]) -> update([
                'color' => $request -> color,
                'updated_at' => time(),
            ]);
        
        return [
            'code' => 200,
            'msg' => '修改成功'
        ];
    }

    //全部代理
    public function agentAllUpdate(Request $request){
        $validator = Validator::make($request->all(), [
            'checkednum' => 'required',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        $info = DB::table('erp_setting')
                    ->where('business_id', '=', $request -> business_id)
                    ->first();
        
        if (!$info) {
            return [
                'code' => 500,
                'msg' => '修改失败'
            ];
        }

        DB::table('erp_setting')
            ->where([
                'business_id' => $request -> business_id
            ]) -> update([
                'updated_at' => time(),
                'is_all' => $request -> checkednum
            ]);

        return [
            'code' => 200,
            'msg' => '修改成功'
        ]; 
    }

    // 查询配置信息
    public function mallsettingInfo(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $info = DB::table('erp_setting')
                    -> where([
                        'business_id' => $request -> business_id
                    ]) -> first();
        
        if (!$info) {
            DB::table('erp_setting')
            ->insert([
                'business_id' => $request -> business_id,
                'color' => '#000000',
                'is_all' => '0',
                'created_at' => time()
            ]);
            $info = DB::table('erp_setting')
                        -> where([
                            'business_id' => $request -> business_id
                        ]) -> first();
        }

        return [
            'code' => 200,
            'data' => $info
        ];
    }

    // 删除
    public function mallsettingDelete(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_setting,business_id'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        DB::table('erp_setting')
            -> where([
                'business_id' => $request -> business_id
            ])
            -> delete();

        return [
            'code' => 200,
            'msg' => '删除成功'
        ];
    }


    // banners
    // 查询列表
    public function bannersList(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        {
            // $info = DB::table('erp_setting_banners')
            //             -> leftJoin('erp_business', 'erp_setting_banners.business_id', 'erp_business.id')
            //             // -> leftJoin('erp_spu_list', 'erp_setting_banners.spu_id', 'erp_spu_list.id')
            //             -> where ([
            //                 'erp_setting_banners.business_id' => $request -> business_id,
            //                 'is_del' => 0
            //             ])
            //             // -> where(function($query)use($request){
            //             //     if($request->spuname){
            //             //         $query -> where('erp_spu_list.name', 'like', '%'."{$request->spuname}".'%');
            //             //     }
            //             // })
            //             -> select([
            //                 'erp_setting_banners.*',
            //                 'erp_business.name as business_name',
            //                 // 'erp_spu_list.name as spu_name',
            //             ])
            //             -> get();
    
            // $first = DB::table('erp_setting_banners')
            //             ->leftJoin('erp_spu_list', 'erp_setting_banners.keyword', 'erp_spu_list.id')
            //             ->where([
            //                 'erp_setting_banners.business_id' => $request->business_id,
            //                 'erp_setting_banners.is_del' => 0,
            //                 'erp_setting_banners.type' => 1
            //             ])
            //             ->select([
            //                 'erp_setting_banners.*',
            //                 'erp_spu_list.name as keyword',
            //                 'erp_spu_list.id as selectTags'
            //             ]);
    
            // $second = DB::table('erp_setting_banners')
            //             ->leftJoin('erp_spu_category', 'erp_setting_banners.keyword', 'erp_spu_category.id')
            //             ->where([
            //                 'erp_setting_banners.business_id' => $request->business_id,
            //                 'erp_setting_banners.is_del' => 0,
            //                 'erp_setting_banners.type' => 2
            //             ])
            //             ->select([
            //                 'erp_setting_banners.*',
            //                 'erp_spu_category.name as keyword',
            //                 'erp_spu_category.id as selectTags'
            //             ]);
    
            // $info = DB::table('erp_setting_banners')
            //             ->where([
            //                 'erp_setting_banners.business_id' => $request->business_id,
            //                 'type' => 3
            //             ])
            //             ->select([
            //                 'erp_setting_banners.*'
            //             ])
            //             ->union($first)
            //             ->union($second)
            //             ->get();
        }

        $list = DB::table('erp_setting_banners')
                    ->where([
                        'business_id' => $request->business_id,
                        'is_del' => 0
                    ])
                    ->orderBy('sort_index', 'asc')
                    ->orderBy('created_at', 'desc')
                    ->get()
                    ->toArray();
        // dd($list->toArray());
        $list = $this->selectSql($list);

        return $this->toImageUrl($list);
    }

    // 工具函数： banners信息相关的列表 sql 查询
    public function selectSql(array $data) :array {
        foreach($data as $key => $value) {
            if ($value->type === 1) {
                $info = DB::table('erp_mp_name_spu_link as link')
                            ->leftJoin('erp_spu_list as list', 'list.id', 'link.spu_id')
                            ->where([
                                'link.id' => $data[$key]->keyword
                            ])
                            ->select(['list.name as name', 'link.id as id'])
                            ->first();
                if ($info) {
                    $data[$key]->keyword = $info->name;
                    $data[$key]->selectTags = $info->id;
                } else {
                    $data[$key]->selectTags = '';
                }
            } elseif ($value->type === 2) {
                $data[$key]->selectTags = $data[$key]->keyword;
            } else {
                $data[$key]->selectTags = '';
            }
        }
        return $data;
    }

    public function toImageUrl($info){
        foreach ($info as $key => $stock) {
            $info[$key]->imageUrl = empty($stock->image) ? '' : getImageUrl($stock -> image) ;
        }
        return $info;
    }

    // banners 配置中搜索商品的接口
    public function searchProduct(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $productList = DB::table('erp_mp_name_spu_link as link')
                        ->leftJoin('erp_spu_list as list', 'list.id', 'link.spu_id')
                        ->leftJoin('erp_mp_name as mp', 'link.mp_name_id', 'mp.id')
                        ->where('list.name', 'like', "%{$request->spu_name}%")
                        ->select([
                            'link.id as id',
                            'list.name as name',
                            'list.img as image',
                            'mp.mp_name as mpName'
                        ])
                        ->get();
        foreach($productList as $key => $value) {
            $value->image = $value->image ? getImageUrl($value->image) : '';
        }
        return [
            'status' => 1,
            'data' => $productList
        ];
    }

    // 增加banners
    public function bannersAdd(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric',
            'image' => 'required',
            'title' => 'required|max:128',
            'type' => 'required|numeric',
            'keyword' => 'required|max:128',
            'isShow' => 'required'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        // 查询banner数量
        $count = DB::table('erp_setting_banners')
                    ->where([
                        'business_id' => $request -> business_id,
                        'is_del' => 0
                    ])
                    ->count();
        // 记录新的 banner 的 sort_index
        $count += 1;
        DB::table('erp_setting_banners')
            -> insert([
                'business_id' => $request->business_id,
                'title' => $request->title,
                'keyword' => $request->keyword,
                'type' => $request->type,
                'image' => 'ali_oss:'.$request->image,
                'is_show' => $request->isShow ? 1 : 0,
                'created_at' => time(),
                'updated_at' => time(),
                'sort_index' => $count
            ]);
        return [
            'code' => 200,
            'msg' => '添加成功'
        ];
    }

    // 修改banners
    public function bannersUpdate(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric',
            'title' => 'required',
            'image' => 'required',
            'isShow' => 'required',
            'type' => 'required',
            'keyword' => 'required',
            'id' => 'required|numeric|exists:erp_setting_banners,id'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $info = DB::table('erp_setting_banners')
                    -> where([
                        'id' => $request->id
                    ])
                    -> first();
        if (!$info) {
            return [
                'code' => 500,
                'msg' => '没有此条数据'
            ];
        }

        DB::table('erp_setting_banners')
            -> where([
                'erp_setting_banners.id' => $request -> id
            ])
            -> update([
                'business_id' => $request->business_id,
                'image' => 'ali_oss:'.$request->image,
                'updated_at' => time(),
                'title' => $request->title,
                'type' => $request->type,
                'keyword' => $request->keyword,
                'is_show' => $request -> isShow ? 1 : 0
            ]);
        
        return [
            'code' => 200,
            'msg' => '编辑成功'
        ];
    }

    // 修改 banner 的排序接口
    public function changeBannerSort(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric',
            'ids' => 'required'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $this -> updateBannerSort($request -> ids);
        return [
            'messge' => '更新成功',
            'errno' => 0
        ];
    }

    // 工具函数：banner 排序更新
    public function updateBannerSort(array $data)
    {
        foreach($data as $key => $id) {
            DB::table('erp_setting_banners')
                ->where([ 'id' => $id ])
                ->update([ 'sort_index' => $key + 1 ]);
        }
    }

    // 删除banners
    public function bannersDel(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|numeric|exists:erp_setting_banners,id'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        DB::table('erp_setting_banners')
            -> where('id', $request->id)
            -> update([
                'is_del' => 1,
                'sort_index' => null
            ]);

        // 更新 banner 的排序
        $list = DB::table('erp_setting_banners')
                    ->where([
                        'business_id' => $request -> business_id,
                        'is_del' => 0
                    ])
                    ->select([ 'id' ])
                    ->get()
                    ->toArray();

        $ids = [];
        foreach($list as $key => $id) {
            $ids[] = $id -> id;
        }

        $this -> updateBannerSort($ids);

        return [
            'code' => 200,
            'msg' => '已删除'
        ];
    }

    // 查询详细信息
    public function bannersInfo(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|numeric|exists:erp_setting_banners,id',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $info = DB::table('erp_setting_banners')
                    ->where([
                        'erp_setting_banners.id' => $request->id
                    ])
                    ->get();

        $info = $this->selectSql($info->toArray())[0];

        // 获取图片的完整路径
        $info->imageUrl = getImageUrl($info->image);
        return [
            'code' => 200,
            'data' => $info
        ];
    }
    
    //查询spu作为标签显示
    public function theSpuOfTagsList(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        if($request->spu_name){
            $where[] = [
                'erp_spu_list.name','like','%'.trim($request->spu_name).'%'
            ];
        } else {
            $where = [];
        }
        $list = DB::table('erp_business_spu_link')
                    -> leftJoin('erp_spu_list', 'erp_business_spu_link.spu_id', 'erp_spu_list.id')
                    -> where([
                        'erp_business_spu_link.business_id' => $request -> business_id,
                        'erp_business_spu_link.flag' => 0
                    ])
                    -> where($where)
                    -> select([
                        'erp_spu_list.id as id',
                        'erp_spu_list.name as name'
                    ])
                    -> get();
        return $list;
    }

    // 已代理
    // 已代理列表
    public function agentAlreadyList(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $is_all = DB::table('erp_setting')
                      -> where('erp_setting.business_id', $request->business_id)
                      -> value('is_all');
        if ($is_all === 1) {
            $info = DB::table('erp_spu_list')
                        -> where(function($query)use($request){
                            if($request -> spu_name){
                                $query -> where('erp_spu_list.name', 'like', '%'."{$request->spu_name}".'%');
                            }
                        })
                        -> select([
                            'erp_spu_list.id as spu_id',
                            'erp_spu_list.name as spu_name'
                        ])
                        -> paginate(10);
            return [
                'list' => $info,
                'isAll' => 1
            ];
        } else {
            $info = DB::table('erp_business_spu_link')
                        -> leftJoin('erp_spu_list', 'erp_business_spu_link.spu_id', 'erp_spu_list.id')
                        ->where([
                            'erp_business_spu_link.business_id' => $request -> business_id,
                            'erp_business_spu_link.flag' => 0
                        ])
                        -> where(function($query)use($request){
                            if($request -> spu_name){
                                $query -> where('erp_spu_list.name', 'like', '%'."{$request->spu_name}".'%');
                            }
                        })
                        -> select([
                            'erp_spu_list.id as spu_id',
                            'erp_spu_list.name as spu_name'
                        ])
                        -> paginate(10);
            
            return [
                'list' => $info 
            ];
        }
        
    }

    
    // 未代理
    // 未代理列表
    public function agentNoList(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        $is_all = DB::table('erp_setting')
                      -> where('erp_setting.business_id', $request->business_id)
                      -> value('is_all');

        if ($is_all === 1) {
            return [
                'isAll' => 1
            ];
        } else {
            // 原生sql写法
            $info = DB::table('erp_spu_list')
                        -> leftJoin(DB::raw('(SELECT * FROM `erp_business_spu_link` where business_id='. $request->business_id .' and flag='. 0 .') b'), 'erp_spu_list.id', 'b.spu_id')
                        -> whereNull('b.business_id')
                        -> where(function($query)use($request){
                            if($request -> spu_name){
                                $query -> where('erp_spu_list.name', 'like', '%'."{$request->spu_name}".'%');
                            }
                        })
                        -> select('erp_spu_list.name as spu_name', 'erp_spu_list.id as spu_id')
                        -> paginate(10);
                        // -> get();
            return $info;
        }
    }
    

    //增加代理
    // public function agentSingleAdd(Request $request){
    //     $validator = Validator::make($request->all(), [
    //         'business_id' => 'required|numeric',
    //         'spu_id' => 'required|numeric'
    //     ]);
    //     if ($validator->fails()) {
    //         return new JsonResponse($validator->errors());
    //     }

    //     $info = DB::table('erp_business_spu_link')
    //                 -> where([
    //                     'erp_business_spu_link.business_id' => $request -> business_id,
    //                     'erp_business_spu_link.spu_id' => $request -> spu_id
    //                 ])
    //                 -> first();
    //     if ($info.flag === 1) {
    //         DB::table(erp_business_spu_link)
    //             -> where([
    //                 'erp_business_spu_link.business_id' => $request -> business_id,
    //                 'erp_business_spu_link.spu_id' => $request -> spu_id
    //             ])
    //             -> update([
    //                 'flag' => 0,
    //             ]);
    //         return [
    //             'code' => 200,
    //             'msg' => '代理成功'
    //         ];
    //     } else {
    //         DB::table('erp_business_spu_link')
    //         -> insert([
    //             'business_id' => $request -> business_id,
    //             'spu_id' => $request -> spu_id,
    //             'flag' => 0,
    //             'create_at' => time()
    //         ]);
    //         return [
    //             'code' => 200,
    //             'msg' => '代理成功'
    //         ];
    //     }
    // }

    // 取消代理
    // public function agentSingleDel(Request $request){
    //     $validator = Validator::make($request->all(), [
    //         'business_id' => 'required|numeric',
    //         'spu_id' => 'required|numeric'
    //     ]);
    //     if ($validator->fails()) {
    //         return new JsonResponse($validator->errors());
    //     }

    //     DB::table('erp_business_spu_link')
    //             -> where([
    //                 'erp_business_spu_link.business_id' => $request -> business_id,
    //                 'erp_business_spu_link.spu_id' => $request -> spu_id
    //             ])
    //             -> update([
    //                 'flag' => 1,
    //             ]);

    //     return [
    //         'code' => 200,
    //         'msg' => '取消成功'
    //     ];
    // }

    // 批量增加代理
    public function agentBatchAdd(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric',
            'agentIds' => 'required|array|min:1'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $agentIds = $request->agentIds;
        $count = count($request->agentIds);

        for ($i = 0; $i < $count; $i++) {
            $arr[]=array(
                        'business_id' => $request->business_id,
                        'spu_id' => $agentIds[$i],
                        'create_at' => time(),
                        'flag' => 0);
        };

        DB::table('erp_business_spu_link')
            -> insert($arr);

        return [
            'code' => 200,
            'msg' => '操作成功'
        ];
    }

    // 批量取消代理
    public function agentBatchDel(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric',
            'agentIds' => 'required|array|min:1'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $info = DB::table('erp_setting_banners')
                    -> whereIn('spu_id', $request->agentIds)
                    -> where('business_id', $request->business_id)
                    -> get();

        if ($info) {
            return [
                'code' => 500,
                'msg' => '有banners'
            ];
        };

        DB::table('erp_business_spu_link')
            -> whereIn('spu_id', $request->agentIds)
            -> where('business_id', $request->business_id)
            -> update([
                'flag' => 1
            ]);

        return [
            'code' => 200,
            'msg' => '操作成功'
        ];
    }

    /**
     * 全局商城配置列表展示
     */
    public function theGlobalMallSettingList(Request $request) {
        $list = $this->warehouseRepository->getAllWarehouseList($request);
        return $list;
    }

    /**
     * 小程序列表显示
     */
    public function getMpNameList(Request $request) {
        $list = $this->warehouseRepository->getAllMpNameList($request);
        return $list;
    }

        /**
     * 事业部列表显示
     */
    public function getBusinessNameList(Request $request) {
        $list = $this->warehouseRepository->getAllBusinessNameList($request);
        return $list;
    }

    /**
     * 给仓库配置小程序名称
     */
    public function addLinkMpName(Request $request) {
        $validator = Validator::make($request->all(), [
            'mpnameid'     => 'required|numeric',
            'warehouseids' => 'required|array|min:1'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $this->warehouseRepository->addLinkMpName($request);
        return [
            'code' => 200,
            'msg'  => '关联成功'
        ];
    }

    /**
     * 仓库显示给代理
     */
    public function updateMpNameOn(Request $request) {
        $validator = Validator::make($request->all(), [
            'warehouseids' => 'required|array|min:1'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        DB::table('erp_warehouse')
            -> whereIn('id',$request->warehouseids)
            -> update([
                'agent_status' => 1
            ]);
        return [
            'code' => 200,
            'msg'  => '修改成功'
        ];
    }

    /**
     * 仓库不显示给代理
     */
    public function updateMpNameOff(Request $request) {
        $validator = Validator::make($request->all(), [
            'warehouseids' => 'required|array|min:1'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        DB::table('erp_warehouse')
            -> whereIn('id',$request->warehouseids)
            -> update([
                'agent_status' => 0
            ]);
        return [
            'code' => 200,
            'msg'  => '修改成功'
        ];
    }

    /**
     * 获取和馆区名称已关联的SPU列表
     */
    public function getMpNameLinkedSpuList(Request $request) {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'type' => 'sometimes|in:0,1',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        return $this->warehouseRepository->getLinkedSpu($request);

    }

    /**
     * 获取和馆区名称未关联的SPU列表
     */
    public function getMpNameUnLinkedSpuList(Request $request) {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'type' => 'sometimes|in:0,1',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        $list = $this->warehouseRepository->getUnLinkedSpu($request);
        return $list;
    }

    /**
     * 获取和代理商已关联的SPU列表
     */
    public function getAgentLinkedSpuList(Request $request) {
        $validator = Validator::make($request->all(), [
            'id'          => 'required',
            'business_id' => 'required'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        $list = $this->agentRepository->getLinkedSpu($request);
        return $list;
    }

    /**
     * 获取某个SPU的分类信息
     */
    public function getAgentSpuCategoryInfo(Request $request) {
        $validator = Validator::make($request->all(), [
            'id'          => 'required'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        return $this->agentRepository->getLinkedSpuCategory($request);
    }

    /**
     * 更新某个SPU的分类信息
     */
    public function updateAgentSpuCategoryInfo(Request $request) {
        $validator = Validator::make($request->all(), [
            'id'       => 'required',
            'class_id' => 'required'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        return $this->agentRepository->updateSpuCategory($request);
    }

    /**
     * 获取和代理商未关联的SPU列表
     */
    public function getAgentUnLinkedSpuList(Request $request) {
        $validator = Validator::make($request->all(), [
            'id'          => 'required',
            'business_id' => 'required'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        $list = $this->agentRepository->getUnLinkedSpu($request);
        return $list;
    }

    /**
     * 馆区关联SPU
     */
    public function addLinkSPUs(Request $request) {
        $validator = Validator::make($request->all(), [
            'mpnameid' => 'required',
            'spuIds'   => 'required|array|min:1'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        return $this->warehouseRepository->addLinkSpus($request);
    }

    /**
     * 馆区取消关联SPU
     */
    public function removeLinkSPUs(Request $request) {
        $validator = Validator::make($request->all(), [
            'mpnameid'    => 'required',
            'spuIds'      => 'required|array|min:1',
            'business_id' => 'required'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        return $this->warehouseRepository->removeLinkSpus($request);
    }

    /**
     * 代理商关联SPU
     */
    public function addAgentLinkSPUs(Request $request) {
        $validator = Validator::make($request->all(), [
            'mpnameid'    => 'required',
            'spuIds'      => 'required|array|min:1',
            'business_id' => 'required'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        return $this->agentRepository->addLinkSpus($request);
    }

    /**
     * 代理商取消关联SPU
     */
    public function removeAgentLinkSPUs(Request $request) {
        $validator = Validator::make($request->all(), [
            'mpnameid' => 'required',
            'spuIds'    => 'required|array|min:1'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        return $this->agentRepository->removeLinkSpus($request);
    }

    /**
     * 获取和馆区名称已关联的SKU列表
     */
    public function getMpNameLinkedSkuList(Request $request) {
        $validator = Validator::make($request->all(), [
            'id' => 'required'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        $list = $this->warehouseRepository->getLinkedSku($request);
        return $list;
    }

    /**
     * 获取和代理商已关联的SKU列表
     */
    public function getAgentLinkedSkuList(Request $request) {
        $validator = Validator::make($request->all(), [
            'id'          => 'required',
            'business_id' => 'required'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        $list = $this->agentRepository->getLinkedSku($request);
        return $list;
    }

    /**
     * 代理内SKU价格维护
     */
    public function updateAgentSkuPrice(Request $request) {
        $validator = Validator::make($request->all(), [
            'id'          => 'required|numeric',
            'mpnameid'    => 'required|numeric',
            'business_id' => 'required|numeric',
            'price'       => 'required|numeric',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        return $this->agentRepository->updateAgentPrice($request);
    }

    /**
     * 馆区内SKU价格维护
     */
    public function updateSkuPrice(Request $request) {
        $validator = Validator::make($request->all(), [
            'id'       => 'required|numeric',
            'mpnameid' => 'required',
            'price_s'  => 'required|numeric',
            'price_a'  => 'required|numeric',
            'price_b'  => 'required|numeric',
            'price_c'  => 'required|numeric',
            'price_d'  => 'required|numeric',
            'market_price'  => 'sometimes|numeric',
            'min_unit'  => 'sometimes|integer|min:1',
            'safe_stock'  => 'sometimes|integer|min:0'
        ]);

        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        return $this->warehouseRepository->mainTainPrice($request);
    }

    /**
     * 馆区上架SKU
     */
    // public function putOnSKUs(Request $request)
    // {
    //     // 批量上架 ids
    //     $validator = Validator::make($request->all(), [
    //         'skuIds' => 'required|array|min:1',
    //     ]);
    //     if ($validator->fails()) {
    //         return new JsonResponse($validator->errors());
    //     }
        
    //     $errors = $this->warehouseRepository->putOnProcess($request);

    //     if (empty($errors)) {
    //         return [
    //             'code' => 200,
    //             'msg' => '编辑成功'
    //         ];
    //     } else {
    //         return [
    //             'code' => 500,
    //             'msg' =>  '部分失败：'.implode("；", $errors)
    //         ];
    //     }
    // }

    /**
     * 馆区下架SKU
     */
    // public function putOffSKUs(Request $request)
    // {
    //     // 批量下架 ids
    //     $validator = Validator::make($request->all(), [
    //         'skuIds' => 'required|array|min:1',
    //     ]);
    //     if ($validator->fails()) {
    //         return new JsonResponse($validator->errors());
    //     }

    //     $errors = $this->warehouseRepository->putOffProcess($request);

    //     if (empty($errors)) {
    //         return [
    //             'code' => 200,
    //             'msg' => '编辑成功'
    //         ];
    //     } else {
    //         return [
    //             'code' => 500,
    //             'msg' =>  '部分失败：'.implode("；", $errors)
    //         ];
    //     }
    // }

    /**
     * 代理内SKU上架
     */
    public function updateAgentSkuPutOn(Request $request) {
        $validator = Validator::make($request->all(), [
            'skuIds'      => 'required',
            'mpnameid'    => 'required|numeric',
            'business_id' => 'required|numeric'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        return $this->agentRepository->agentSkuPutOn($request);
    }

    /**
     * 代理内SKU下架
     */
    public function updateAgentSkuPutOff(Request $request) {
        $validator = Validator::make($request->all(), [
            'skuIds'      => 'required',
            'mpnameid'    => 'required|numeric',
            'business_id' => 'required|numeric'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        return $this->agentRepository->agentSkuPutOff($request);
    }


    /**
     * 馆区SKU显示开启
     */
    public function skuShowPutOn(Request $request) {
        $validator = Validator::make($request->all(), [
            'id'       => 'required',
            'mpnameid' => 'required',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        return $this->warehouseRepository->skuShowPutOn($request);
    }

    /**
     * 馆区SKU显示关闭
     */
    public function skuShowPutOff(Request $request) {
        $validator = Validator::make($request->all(), [
            'id'       => 'required',
            'mpnameid' => 'required',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        return $this->warehouseRepository->skuShowPutOff($request);
    }

    /**
     * 代理分类管理，分类列表
     */
    public function getAgentCategoryList(Request $request) {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        return $this->agentCategoryRepository->getAgentCategoryTree($request);
    }

    /**
     * 代理分类管理，分类添加
     */
    public function addAgentCategory(Request $request) {
        $validator = Validator::make($request->all(), [
            'name'        => 'required|max:64',
            'sort_index'  => 'required|numeric',
            'mp_flag'     => 'required|numeric',
            'parentId'    => 'required|numeric',
            'image'       => 'required',
            'business_id' => 'required'
          ]);
          if ($validator->fails()) {
            return new JsonResponse($validator->errors());
          }
        return $this->agentCategoryRepository->addAgentCategory($request);
    }

    /**
     * 代理分类管理，分类修改
     */
    public function editAgentCategory(Request $request) {
        $validator = Validator::make($request->all(), [
            'name'        => 'required|max:64',
            'sort_index'  => 'required|numeric',
            'mp_flag'     => 'required|numeric',
            'image'       => 'required',
            'business_id' => 'required'
          ]);
          if ($validator->fails()) {
            return new JsonResponse($validator->errors());
          }
        return $this->agentCategoryRepository->editAgentCategory($request);
    }

    /**
     * 代理分类管理，分类删除
     */
    public function delAgentCategory(Request $request) {
        $validator = Validator::make($request->all(), [
            'id'  => 'required|numeric',
          ]);
          if ($validator->fails()) {
            return new JsonResponse($validator->errors());
          }
        return $this->agentCategoryRepository->delAgentCategory($request);
    }

    /**
     * 代理分类管理，分类详情
     */
    public function agentCategoryInfo(Request $request) {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|numeric',
          ]);
          if ($validator->fails()) {
            return new JsonResponse($validator->errors());
          }
        return $this->agentCategoryRepository->agentCategoryInfo($request);
    }

    /**
     * 获取代理的基础配置
     */
    public function getAgentBasicSetting(Request $request) {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric',
          ]);
          if ($validator->fails()) {
            return new JsonResponse($validator->errors());
          }
        return $this->agentRepository->getAgentBasicSetting($request);       
    }

    /**
     * 代理的基础配置
     */
    public function updateAgentBasicSetting(Request $request) {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric',
            'name'        => 'required',
            'notice'      => 'required',
            'logo'        => 'required',
            'image'       => 'required'
          ]);
          if ($validator->fails()) {
            return new JsonResponse($validator->errors());
          }
        return $this->agentRepository->updateAgentBasicSetting($request);     
    }

    /**
     * 套餐管理页面
     */
    public function skusUnion(Request $request) {
        $validator = Validator::make($request->all(), [
            'spu_id' => 'required|numeric',
            'mp_id' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        return $this->skuRepository->getSkusUnion($request);
    }

    /**
     * 套餐管理保存
     */
    public function skusUnionStore(Request $request) {
        $validator = Validator::make($request->all(), [
            'list' => 'required|array',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        $result=$this->skuRepository->storeSkusUnion($request);
        if ($result['code'] != 200){
            return new JsonResponse($result['msg']);
        }
        return $result;
    }

    /**
     * 套餐状态设置
     */
    public function skusUnionFlag(Request $request) {
        $validator = Validator::make($request->all(), [
            'spu_id' => 'required|numeric',
            'mp_id' => 'required|numeric',
            'flag' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        return $this->skuRepository->flagSkusUnion($request);
    }

    /**
     * 设置排序,权重
     * @param Request $request
     * @return JsonResponse
     */
    public function setSortIndex(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'link_id' => 'required|numeric',
            'sort_index' => 'sometimes|in:0,1,2,3,4',
            'weight_index' => 'sometimes|integer|min:0',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $mp_spu_link=MpNameSpuList::where('id',$request->link_id)->first();

        if (!$mp_spu_link){
            return ['code'=>500,'msg'=>'更新失败'];
        }

        $request->has('sort_index') && $mp_spu_link->sort_index=$request->sort_index;
        $request->has('weight_index') && $mp_spu_link->weight_index=$request->weight_index;
        $mp_spu_link->save();

        return ['code'=>200,'msg'=>'更新成功'];
    }
}

