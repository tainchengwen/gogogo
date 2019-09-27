<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\JsonResource;
use App\SPUList;
use App\SPUProductList;
use App\WareHouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Repositories\SPURepository;

class SPUController extends Controller
{

    public function __construct(SPURepository $spuRepository)
    {
        $this->spuRepository = $spuRepository;
    }

    public function getSPU(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|numeric|exists:erp_spu_list,id',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $info = DB::table('erp_spu_list')
            -> select([
                '*'
            ])
            -> where([
                'id'    => $request -> id,
                'flag'  => 0
            ]) -> first();

        // 还需要拼接tags数组
        $tags = DB::table('erp_spu_tag_link')
            -> where('spu_id', $info->id)
            -> get();

        $celarArray = [];
        foreach ($tags as $key => $tag) {
            array_push($celarArray, $tag->tag_id);
        }

        $info->tag_ids = $celarArray;
        $info->selectedOptions = $this->spuRepository->getBreadCategroy($info->class_id);

        //兼容处理首图
        if(!empty($info->img)){
            $info->img_url=getImageUrl($info->img);
            $info->img=substr($info->img,8);
        }

        //如果是套餐spu，取出已关联sku
        if ($info->type == 1){
            $request->offsetSet('spu_id',$request->id);
            $stock_controller=app('App\Http\Controllers\Api\StockController');
            $info->skus=app()->call([$stock_controller, "getSKUList"]);
        }

        return [
            'code' => 200,
            'data' => $info
        ];
    }

    // 旧SPUlist
    public function oldTheSPUList(Request $request)
    {
        // 根据仓库name和事业部id获取仓库id
        $info = Warehouse::getWarehouseId($request->warehousename, $request->business_id);
        $warehouse_ids = [];
        foreach ($info as $key => $value) {
            array_push($warehouse_ids, $value->id);
        }
        $where = [];
        if($request->spu_name){
            $where[] = [
                'erp_spu_list.name','like','%'.trim($request -> spu_name).'%'
            ];
        };
        $wherecategory = [];
        if($request->class_id){
            $wherecategory[] = [
                'class_id',$request->class_id
            ];
        }
        $list = SPUList::where(['flag' => '0'])
            ->leftJoin('erp_spu_category','erp_spu_list.class_id','erp_spu_category.id')
            -> where($where)
            -> where($wherecategory)
            -> whereIn('warehouse_id', $warehouse_ids)
            -> orderBy('erp_spu_list.id','desc')
            -> select([
                '*',
                'erp_spu_list.name as spu_name',
                'erp_spu_list.id as id',
                'erp_spu_category.id as category_id'
            ])
            -> paginate(10);

        foreach ($list as $key => $value) {
            // 查询单品个数
            $list[$key]['skuNum'] = DB::table('erp_spu_sku_link')->where('spu_id',$value->id)->count();

            $tags = DB::table('erp_spu_tag_link')
            -> where('erp_spu_tag_link.spu_id', $value->id)
            -> leftJoin('erp_tags','erp_spu_tag_link.tag_id','erp_tags.id')
            -> orderBy('erp_tags.id','desc')
            -> select([
                'erp_tags.name'
            ]) -> get();

            $list[$key]['tags'] = $this->switchArray2String($tags, 'name');
        }
        foreach ($list as $key => $value) {
            $list[$key]['selectedOptions'] = $this->getBreadCategroy($value->class_id);
            $categorynamelist = $this->getBreadCategroy($value->class_id);
            $categorynameall = $this->getCategoryName($categorynamelist);
            $list[$key]['categorynameall'] = $categorynameall;
        }
        return $list;
    }

    /**
     * 新SPUlist
     */
    public function theSPUList(Request $request)
    {
        $where = [];
        if($request->spu_name){
            $where[] = [
                'erp_spu_list.name','like','%'.trim($request -> spu_name).'%'
            ];
        };
        $wherecategory = [];
        if($request->class_id){
            $wherecategory[] = [
                'class_id',$request->class_id
            ];
        }

        //0普通1套餐
        $type=0;
        if($request->type){
            $type=$request->type;
        }

        $list = SPUList::where(['flag' => '0'])
            ->leftJoin('erp_spu_category','erp_spu_list.class_id','erp_spu_category.id')
            -> where($where)
            -> where($wherecategory)
            ->where(function($query)use($request){
                if(isset($request->business_id)){
                    $query->where('erp_spu_list.business_id',$request->business_id)
                    ->orWhere('erp_spu_list.is_public',1);
                }
            })
            -> where('type',$type)
            -> orderBy('erp_spu_list.id','desc')
            -> select([
                '*',
                'erp_spu_list.name as spu_name',
                'erp_spu_list.id as id',
                'erp_spu_category.id as category_id'
            ])
            -> paginate(isset($request -> per_page)?$request -> per_page:20);
        //处理首图
        $list->each(function ($item, $key) {
            if(!empty($item->img)){
                $item->img=getImageUrl($item->img);
            }
        });
        $list = $this->spuRepository->getSkuNum($list);
        $list = $this->spuRepository->getCategoryNameAll($list);
        return $list;
    }

    // 寻找仓库id
    public function findWarehouseId(Request $request) {
        // 根据仓库name和事业部id获取仓库id
        $warehouse_ids = Warehouse::getWarehouseId($request->warehousename, $request->business_id);
        $info = $warehouse_ids[0]->id;
        return $info;
    }

    // 旧SPU添加
    public function oldAddSPU(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'         => 'required|min:1|max:128',
            'class_id'     => 'required',
            'details'      => 'required',
            'business_id'  => 'required',
            'warehouse_id' => 'required|numeric',
            'skuIds'       => 'array',
            'tags'         => 'array'
        ]);

        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $spuId = DB::table('erp_spu_list') -> insertGetId([
            'name'         => $request -> name,
            'sub_name'     => empty($request -> sub_name) ? "" :  $request -> sub_name,
            'details'      => $request -> details,
            'business_id'  => $request -> business_id,
            'class_id'     => empty($request -> class_id) ? 0 :  $request -> class_id,
            'warehouse_id' => $request -> warehouse_id,
            'created_at'   => time(),
            'updated_at'   => time()
        ]);

        // 存tags
        if (!empty($request->tags)) {
            $this->addTagsLink($request -> tags, $spuId);
        }

        return [
            'code' => 200,
            'msg' => '添加成功'
        ];
    }

    // 新SPU添加
    public function addSPU(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'         => 'required|min:1|max:128',
            'class_id'     => 'required',
            'details'      => 'required',
            'skuIds'       => 'array',
            'skus'       => 'array',
            'tags'         => 'array',
            'type'         => 'sometimes|in:0,1',
            //'img'          => 'required'
            'business_id'  => 'required|numeric', //事业部id
            'limit_buy_type'      => 'required|in:0,1,2',
            'limit_buy_number'      => 'nullable|integer|min:1',
            'vip0'      => 'nullable|integer|min:1',
            'vip1'      => 'nullable|integer|min:1',
            'vip2'      => 'nullable|integer|min:1',
            'vip3'      => 'nullable|integer|min:1',
            'vip4'      => 'nullable|integer|min:1',
            'cycle'      => 'required_if:limit_buy_type,1,2',
        ]);

        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        //0普通1套餐
        $type=0;
        if ($request->type){
            $type=$request->type;
        }

        $spuId = DB::table('erp_spu_list') -> insertGetId([
            'name'         => $request -> name,
            'sub_name'     => empty($request -> sub_name) ? "" :  $request -> sub_name,
            'details'      => $request -> details,
            'class_id'     => empty($request -> class_id) ? 0 :  $request -> class_id,
            'created_at'   => time(),
            'updated_at'   => time(),
            'img'   => $request->has('img')&&!empty($request->img)?'ali_oss:'.$request->img:'',
            'is_public' => isset($request->is_public) && $request->is_public == 1 ?1 : 0,
            'business_id' => $request->business_id,
            'type'=>$type,
            'limit_buy_type'=>$request->limit_buy_type,
            'limit_buy_number'=>$request->filled('limit_buy_number')?$request->limit_buy_number:'',
            'vip0'=>$request->filled('vip0')?$request->vip0:'',
            'vip1'=>$request->filled('vip1')?$request->vip1:'',
            'vip2'=>$request->filled('vip2')?$request->vip2:'',
            'vip3'=>$request->filled('vip3')?$request->vip3:'',
            'vip4'=>$request->filled('vip4')?$request->vip4:'',
            'cycle'=>$request->filled('cycle')?$request->cycle:0,
        ]);

        // 存tags
        if (!empty($request->tags)) {
            $this->addTagsLink($request -> tags, $spuId);
        }

        //如果是添加套餐spu，关联sku
        if ($type=1 && !empty($request->skus)){
            $skuIds=[];
            foreach ($request->skus as $sku){
                $skuIds[]=$sku['id'];
            }
            $request->offsetSet('id',$spuId);
            $request->offsetSet('skuIds',$skuIds);
            $this->addLinkSKUs($request);
        }

        return [
            'code' => 200,
            'msg' => '添加成功'
        ];
    }

    //旧SPU修改
    public function oldEditSPU(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'        => 'required|numeric|exists:erp_spu_list,id',
            'name'      => 'required|min:1|max:128',
            'details'   => 'required',
            'class_id'  => 'required|numeric',
            'skuIds'    => 'array',
            'tags'      => 'array'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $spuInfo = DB::table('erp_spu_list')->find($request->id);

        DB::table('erp_spu_list')
        -> where('id',$request->id)
        -> update([
            'name'          => $request -> name,
            'sub_name'      => empty($request -> sub_name) ? $spuInfo->sub_name : $request -> sub_name,
            'details'       => $request -> details,
            'class_id'      => empty($request -> class_id) ? $spuInfo->class_id : $request -> class_id,
            'updated_at'    => time()
        ]);

        // 删除tags关联
        $this->removeTagsLinkBySPUId($request->id);

        // 新增tags
        if (!empty($request->tags)) {
            $this->addTagsLink($request->tags, $request->id);
        }

        return [
            'code' => 200,
            'msg' => '编辑成功'
        ];
    }

    //新SPU修改
    public function editSPU(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'        => 'required|numeric|exists:erp_spu_list,id',
            'name'      => 'required|min:1|max:128',
            'details'   => 'required',
            'class_id'  => 'required|numeric',
            'skuIds'    => 'array',
            'skus'    => 'array',
            'tags'      => 'array',
            //'img'      => 'required',
            'limit_buy_type'      => 'required|in:0,1,2',
            'limit_buy_number'      => 'nullable|integer|min:1',
            'vip0'      => 'nullable|integer|min:1',
            'vip1'      => 'nullable|integer|min:1',
            'vip2'      => 'nullable|integer|min:1',
            'vip3'      => 'nullable|integer|min:1',
            'vip4'      => 'nullable|integer|min:1',
            'cycle'      => 'required_if:limit_buy_type,1,2',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $spuInfo = DB::table('erp_spu_list')->find($request->id);

        DB::table('erp_spu_list')
        -> where('id',$request->id)
        -> update([
            'name'          => $request -> name,
            'sub_name'      => empty($request -> sub_name) ? $spuInfo->sub_name : $request -> sub_name,
            'details'       => $request -> details,
            'class_id'      => empty($request -> class_id) ? $spuInfo->class_id : $request -> class_id,
            'updated_at'    => time(),
            'img'   => $request->has('img')&&!empty($request->img)?'ali_oss:'.$request->img:'',
            'limit_buy_type'=>$request->limit_buy_type,
            'limit_buy_number'=>$request->filled('limit_buy_number')?$request->limit_buy_number:'',
            'vip0'=>$request->filled('vip0')?$request->vip0:'',
            'vip1'=>$request->filled('vip1')?$request->vip1:'',
            'vip2'=>$request->filled('vip2')?$request->vip2:'',
            'vip3'=>$request->filled('vip3')?$request->vip3:'',
            'vip4'=>$request->filled('vip4')?$request->vip4:'',
            'cycle'=>$request->filled('cycle')?$request->cycle:0,
        ]);

        // 删除tags关联
        $this->removeTagsLinkBySPUId($request->id);

        // 新增tags
        if (!empty($request->tags)) {
            $this->addTagsLink($request->tags, $request->id);
        }

        //如果是套餐spu，修改关联sku
        if ($spuInfo->type=1 && !empty($request->skus)){
            $new_sku_ids=[];
            foreach ($request->skus as $sku){
                $new_sku_ids[]=$sku['id'];
            }
            $old_sku_ids=DB::table('erp_spu_sku_link')
                ->where('spu_id',$request->spuId)
                ->where('flag',0)
                ->pluck('sku_id')->toArray();

            $add_sku_ids=array_diff($new_sku_ids,$old_sku_ids);
            $delete_sku_ids=array_diff($old_sku_ids,$new_sku_ids);

            if (!empty($add_sku_ids)){
                $request->offsetSet('id',$request->id);
                $request->offsetSet('skuIds',$add_sku_ids);
                $this->addLinkSKUs($request);
            }

            if (!empty($delete_sku_ids)){
                $request->offsetSet('spuId',$request->id);
                $request->offsetSet('skuIds',$delete_sku_ids);
                $this->removeLinkSKUs($request);
            }
        }

        return [
            'code' => 200,
            'msg' => '编辑成功'
        ];
    }

    /**
     *删除spu
     */
    public function deleteSPU(Request $request)
    {
        $ids=is_array($request->id)?$request->id:[$request->id];
        DB::table('erp_spu_list')->whereIn('id',$ids)->update(['flag'=>1]);
        DB::table('erp_mp_name_spu_link')->whereIn('spu_id',$ids)->update(['flag'=>1]);
        DB::table('erp_spu_sku_link')->whereIn('spu_id',$ids)->update(['flag'=>1]);

        return [
            'code' => 200,
            'msg' => '删除成功'
        ];
    }

    // 旧关联操作
    public function oldAddLinkSKUs(Request $request)
    {
        // 批量添加记录
        $validator = Validator::make($request->all(), [
            'id'        => 'required|numeric|exists:erp_spu_list,id',
            'skuIds'    => 'required|array|min:1',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        DB::beginTransaction();
        try {
            $insertData = [];
            foreach ($request->skuIds as $skuId) {
                $warehouse_id = DB::table('erp_spu_list')
                -> where('id',$request->id)
                -> value('warehouse_id');
                $count = DB::table('erp_spu_sku_link')
                -> where('spu_id', $request->id)
                -> where('sku_id', $skuId)
                -> where('flag', 0)
                -> count();
                if (empty($count)) {
                    array_push($insertData, ['sku_id' => $skuId, 'spu_id' => $request->id, 'warehouse_id' => $warehouse_id, 'business_id' => (int)$request->business_id]);
                }
            }
            DB::table('erp_spu_sku_link')->insert($insertData);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'code' => 500,
                'msg' => '关联失败'
            ];
        }

        return [
            'code' => 200,
            'msg' => '关联成功'
        ];
    }

    // 新关联操作
    public function addLinkSKUs(Request $request)
    {
        // 批量添加记录
        $validator = Validator::make($request->all(), [
            'id'        => 'required|numeric|exists:erp_spu_list,id',
            'skuIds'    => 'required|array|min:1',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        DB::beginTransaction();
        try {
            $insertData = [];
            foreach ($request->skuIds as $skuId) {
                $count = DB::table('erp_spu_sku_link')
                            -> where('spu_id', $request->id)
                            -> where('sku_id', $skuId)
                            -> where('flag', 0)
                            -> count();
                if (empty($count)) {
                    array_push($insertData, ['sku_id' => $skuId, 'spu_id' => $request->id]);
                }
            }
            DB::table('erp_spu_sku_link')->insert($insertData);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'code' => 500,
                'msg' => '关联失败'
            ];
        }

        return [
            'code' => 200,
            'msg' => '关联成功'
        ];
    }

    //旧取消关联操作
    public function oldRemoveLinkSKUs(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'spuId'     => 'required|numeric',
            'skuIds'    => 'required|array|min:1',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        $status = DB::table('erp_spu_sku_link')
                      -> where('spu_id',$request->spuId)
                      -> whereIn('sku_id',$request->skuIds)
                      -> get();
        foreach ($status as $key => $value) {
            if ( $value->status === 1 ) {
                return [
                    'code' => 500,
                    'msg' => '存在已上架产品，取消关联失败'
                ];
            }
        }
        DB::beginTransaction();
        try {
            // 除了删除以外，剩余正常单品数量
            $remainLinkedPutOnNum = DB::table('erp_spu_sku_link')
            -> where('erp_spu_sku_link.spu_id', $request->spuId)
            -> whereNotIn('erp_spu_sku_link.sku_id', $request->skuIds)
            -> where('erp_spu_sku_link.status','=', 1)
            -> count();

            // spuId = spuId   id in ids的这种
            DB::table('erp_spu_sku_link')->where('spu_id',"=",$request->spuId)
            ->whereIn('sku_id',$request->skuIds)
            ->delete();

            // 如果剩下的没有上架单品了，则不显示了
            if (empty($remainLinkedPutOnNum)) {
                DB::table('erp_spu_list')
                -> where('id', $request->spuId)
                -> update([
                    'status' => 0
                ]);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $errors[] = '取消关联失败，数据库错误';
        }
        return [
            'code' => 200,
            'msg' => '取消关联成功'
        ];
    }

    /**
     * 新取消关联操作
     */
    public function removeLinkSKUs(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'spuId'     => 'required|numeric',
            'skuIds'    => 'required|array|min:1',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        // $status = DB::table('erp_spu_sku_link')
        //               -> where('spu_id',$request->spuId)
        //               -> whereIn('sku_id',$request->skuIds)
        //               -> get();
        // foreach ($status as $key => $value) {
        //     if ( $value->status === 1 ) {
        //         return [
        //             'code' => 500,
        //             'msg' => '存在已上架产品，取消关联失败'
        //         ];
        //     }
        // }
        DB::beginTransaction();
        try {
            // 除了删除以外，剩余正常单品数量
            $remainLinkedPutOnNum = DB::table('erp_spu_sku_link')
            -> where('erp_spu_sku_link.spu_id', $request->spuId)
            -> whereNotIn('erp_spu_sku_link.sku_id', $request->skuIds)
            -> where('erp_spu_sku_link.status','=', 1)
            -> count();

            // spuId = spuId   id in ids的这种
            DB::table('erp_spu_sku_link')->where('spu_id',"=",$request->spuId)
            ->whereIn('sku_id',$request->skuIds)
            ->update([
                'flag' => 1
            ]);

            // 如果剩下的没有上架单品了，则不显示了
            // if (empty($remainLinkedPutOnNum)) {
            //     DB::table('erp_spu_list')
            //     -> where('id', $request->spuId)
            //     -> update([
            //         'status' => 0
            //     ]);
            // }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $errors[] = '取消关联失败，数据库错误';
        }
        return [
            'code' => 200,
            'msg' => '取消关联成功'
        ];
    }

    //批量设置限购
    public function batchLimitBuyEdit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids'         => 'required|array',
            'ids.*'         => 'integer',
            'limit_buy_type'      => 'required|in:0,1,2',
            'limit_buy_number'      => 'nullable|integer|min:1',
            'vip0'      => 'nullable|integer|min:1',
            'vip1'      => 'nullable|integer|min:1',
            'vip2'      => 'nullable|integer|min:1',
            'vip3'      => 'nullable|integer|min:1',
            'vip4'      => 'nullable|integer|min:1',
            'cycle'      => 'required_if:limit_buy_type,1,2',
        ]);

        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        DB::table('erp_spu_list')
            -> whereIn('id',$request->ids)
            -> update([
                'updated_at'    => time(),
                'limit_buy_type'=>$request->limit_buy_type,
                'limit_buy_number'=>$request->filled('limit_buy_number')?$request->limit_buy_number:'',
                'vip0'=>$request->filled('vip0')?$request->vip0:'',
                'vip1'=>$request->filled('vip1')?$request->vip1:'',
                'vip2'=>$request->filled('vip2')?$request->vip2:'',
                'vip3'=>$request->filled('vip3')?$request->vip3:'',
                'vip4'=>$request->filled('vip4')?$request->vip4:'',
                'cycle'=>$request->filled('cycle')?$request->cycle:0,
            ]);

        return [
            'code' => 200,
            'msg' => '编辑成功'
        ];
    }

    private function addTagsLink($tags, $spuId)
    {
        $insertData = [];
        foreach ($tags as $tag) {
            array_push($insertData, ['tag_id' => $tag, 'spu_id' => $spuId]);
        }
        DB::table('erp_spu_tag_link')->insert($insertData);
        return true;
    }

    private function removeTagsLinkBySPUId($spuId)
    {
        DB::table('erp_spu_tag_link')->where('spu_id',"=",$spuId)->delete();
        return true;
    }

}
