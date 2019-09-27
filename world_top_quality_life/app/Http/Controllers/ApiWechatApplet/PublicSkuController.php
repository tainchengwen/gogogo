<?php

namespace App\Http\Controllers\ApiWechatApplet;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\JsonResource;
use App\SPUList;
use App\ProductList;
use App\PublicStockOrder;
use App\SPUProductList;
use App\WareHouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Repositories\SPURepository;
use App\Repositories\AgentNewSPURepository;
use App\BusinessSpuLink;


class PublicSkuController extends BaseController
{

    public function __construct(SPURepository $spuRepository,AgentNewSPURepository $agentSPURepository)
    {
        $this->spuRepository = $spuRepository;
        $this->agentSpuRepository = $agentSPURepository;
    }

    /**
     * 代理发布spu
     * @param MobileCodeRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function privateSpuAdd(Request $request){
        $validator = Validator::make($request->all(), [
            'spu_name'     => 'required|min:1|max:128',
            'class_id'     => 'required',
            'details'      => 'required',
            'tags'         => 'sometimes|array',
            'type'         => 'sometimes|in:0,1',
            'business_id'  => 'required|numeric',
            'sku_info'     => 'required|json',
            'price'        => 'sometimes',
            'origin_price' => 'sometimes',
            'number'       => 'required',
            'status'       => 'sometimes|in:0,1',
            'marked_price' => 'required', //划线价
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        //0普通1套餐

        DB::beginTransaction();
        try{
            $res = $this->agentSpuRepository->addSpuAndSku($request);
            //自动生成采购单
            $skuData = collect();
            $skuData->business_id = $request->business_id;
            $skuData->skuInfo  = ProductList::whereIn('id',$res['sku_arr'])->get();
            $res =  PublicStockOrder::privateSkuPurchaseStock($skuData);
            if($res['code'] == '200'){
                DB::commit();
            }else{
                return $this->error($res['msg']);
            }
            return $this->success('success');
        }catch(\Exception $exception){
            DB::rollBack();
            return $this->error($exception);
        }



    }


    /**
     * 代理编辑spu
     * @param MobileCodeRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function privateSpuEdit(Request $request){
        $validator = Validator::make($request->all(), [
            'id'           => 'required|exists:erp_spu_list,id',
            'spu_name'     => 'required|min:1|max:128',
            'class_id'     => 'required',
            'details'      => 'required',
            'tags'         => 'sometimes|array',
            'type'         => 'sometimes|in:0,1',
            'business_id'  => 'required|numeric',
            'sku_info'     => 'required|json',
            'price'        => 'sometimes',
            'origin_price' => 'sometimes',
            'number'       => 'sometimes',
            'status'       => 'sometimes|in:0,1',
            'sku_id'       => 'sometimes'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        DB::beginTransaction();
        try{
            $res = $this->agentSpuRepository->editSpuAndSku($request);
            //判断商品是自营还是分销
            $isSelf = BusinessSpuLink::where(['new_spu_id'=>$request->id,'business_id'=>$request->business_id])->first();
            if($isSelf){
                DB::commit();
                return $this->success('success');
            }
            //库存转移
            if(count($res['sku_number_change'])){
                $res = PublicStockOrder::multiSkuStockChange($res['sku_number_change']);
                if($res['code'] != '200'){
                    return $this->error($res['msg']);
                }
            }
            //自动生成采购单
            if(count($res['sku_auto_purchase'])){
                $skuData = collect();
                $skuData->business_id = $request->business_id;
                $skuData->skuInfo  = ProductList::whereIn('id',$res['sku_arr'])->get();
                $res =  PublicStockOrder::privateSkuPurchaseStock($skuData);
                if($res['code'] != '200'){
                    return $this->error($res['msg']);
                }
            }
            DB::commit();
            return $this->success('success');
        }catch(\Exception $exception){
            DB::rollBack();
            return $this->error($exception);
        }

    }


    /**
     * 代理spu列表
     * @param MobileCodeRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function privateSpuList(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id'  => 'required',
            'status'       => 'required|in:0,1,2'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        $spuList = DB::table('erp_spu_list')
            ->where('erp_spu_list.business_id',$request->business_id)
            ->where(function($query)use($request){
                switch ($request->status){
                    case 0:
                        $query->where('erp_spu_list.status',0);
                        break;
                    case 1:
                        $query->where([
                            ['erp_spu_list.status' => 1],
                            ['erp_spu_list.number','>',0]
                        ]);
                        break;
                    case 2:
                        $query->where([
                            ['erp_spu_list.status' => 1],
                            ['erp_spu_list.number','=',0]
                        ]);
                        break;
                }
                if($request->name){
                    $query->where('name','like',"%{$request->name}%");
                }
            })
            ->paginate(20);
        return $this->success('success',$spuList);
    }


    /**
     * 代理spu详情
     * @param MobileCodeRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function privateSpuInfo(Request $request){
        $spuId  = $request->input('id');

        $spuInfo =   DB::table('erp_spu_list')->where('id',$spuId)->first();

        $spuInfo->sku = DB::table('erp_spu_sku_link')
            ->leftJoin('erp_spu_sku_link','erp_product_list.id','erp_spu_sku_link.sku_id')
            ->where('erp_spu_sku_link.spu_id',$spuId)
            ->select('erp_spu_sku_link.*')
            ->get();

        return $this->success('success',$spuInfo);
    }

    /**
     * 代理spu上下架
     * @param MobileCodeRequest $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function privateSpuPutOnOff(Request $request){
        $validator = Validator::make($request->all(), [
            'ids'      => 'required|array',
            'status'  => 'required|in:0,1'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        $spuArr = $request->ids;
        if(empty($spuArr))return $this->error('参数有误');
        try{
            foreach ($spuArr as $v){
                //检测上架条件
                $this->agentSpuRepository->checkSpuStatus();
            }
            $res = DB::table('erp_spu_list')->whereIn('id',$spuArr)->update(['status'=>$request->status]);
            if($res){
                return $this->success('操作成功');
            }else{
                return $this->error('操作失败');
            }
        }catch (\Exception $exception){
            return $this->error($exception);
        }

    }


}
