<?php

namespace App\Http\Controllers\ApiWechatApplet;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Repositories\AgentNewSPURepository;
use App\Repositories\SPURepository;
use App\SPUList;
use App\BusinessSpuLink;

class SupplyCenterController extends BaseController
{

    public function __construct(
        SPURepository $spuRepository,
        AgentNewSPURepository $agentSPURepository
    )
    {
        $this->spuRepository = $spuRepository;
        $this->agentSpuRepository      = $agentSPURepository;
    }
    /**
     * 供货中心
     * @param Request $request
     * @return JsonResponse
     */
    public function theList(Request $request){
        //获取展鹏事业部下的所有未删除spu
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

        $list = SPUList::where(['erp_spu_list.flag' => '0'])
            ->leftJoin('erp_spu_sku_link','erp_spu_list.id','erp_spu_sku_link.spu_id')
            ->leftJoin('erp_product_list','erp_product_list.id','erp_spu_sku_link.sku_id')
            ->leftJoin('erp_spu_category','erp_spu_list.class_id','erp_spu_category.id')
            -> where($where)
            -> where($wherecategory)
            ->where(function($query)use($request){
                $query->where('erp_spu_list.business_id',49)
                    ->orWhere('erp_spu_list.is_public',1);
            })
            ->where([
                ['erp_product_list.price','>',0 ],
                ['erp_product_list.origin_price','>',0 ],
                ['erp_product_list.number','>',0 ]
            ])
            -> where('erp_spu_list.type',$type)
            -> where('erp_spu_sku_link.id','>',0)
            -> orderBy('erp_spu_list.id','desc')
            ->groupBy('erp_spu_sku_link.spu_id')
            -> select([
                'erp_spu_list.*',
                'erp_spu_category.*',
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

    /**
     * 在供应中心挑选商品上架
     * @param Request $request
     * @return mixed
     */
    public function pickSupplyGoods(Request $request){
        $validator = Validator::make($request->all(), [
            'spu_id'      => 'required|exists:erp_spu_list,id',
            'business_id' => 'required|exists:erp_business,id'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($request, [], '缺少必要参数');
        }

        $link = BusinessSpuLink::where(['business_id'=>$request->business_id,'spu_id'=>$request->spu_id,'flag'=>0])->first();
        if($link)return $this->error('已关联该spu');
        $spuInfo = $this->agentSpuRepository->spuInfo($request->spu_id);
        if(DB::table('erp_spu_list')->where([
            'name' => $spuInfo->name,
            'business_id' => $request->business_id
        ])->first()){
            return $this->error('已存在该商品');
        }
        $skuInfo = [];
        foreach ($spuInfo->skuInfo as $v){
            $skuInfo[] = [
                'product_no' => $v->product_no,
                'price' => $v->price,
                'origin_price' => $v->origin_price,
                'number' => $v->number,
                'sku' => json_decode($v->sku_info,1),
            ];
            $img = getImageUrl($v->image);
        }

        DB::beginTransaction();
        try{

            //新增spu sku
            $data = collect();
            $data->spu_name     = $spuInfo->name;
            $data->sub_name     = $spuInfo->sub_name;
            $data->details      = $spuInfo->details;
            $data->img          = $img;
            $data->business_id  = $request->business_id;
            $data->status       = 0;
            $data->class_id       = 0;
            $data->tags         = $spuInfo->tags;
            $data->type         = $spuInfo->type;
            $data->sku_info     = json_encode($skuInfo);
            $res = $this->agentSpuRepository->addSpuAndSku($data);
            //代理与sku的关联关系
            BusinessSpuLink::insert(['business_id'=>$request->business_id,'spu_id'=>$request->spu_id,'create_at'=>time(),'flag'=>0,'new_spu_id'=>$res['spu_id']]);
            if($res){
                DB::commit();
            }else{
                DB::rollBack();
            }
            return $this->success('挑选成功');
        }catch (\Exception $e){
            DB::rollBack();
            return $this->error($e);
        }



    }

    /**
     * 恢复旧的展鹏数据
     */
    public function syncSpuSkuInfo(){
        $spuList = DB::table('erp_product_list')
            ->leftJoin('erp_product_price','erp_product_list.id','erp_product_price.product_id')
            ->leftJoin('erp_purchase_order_goods','erp_product_list.id','erp_purchase_order_goods.product_id')
            ->where('erp_product_list.model','<>',0)
            ->where('erp_product_list.business_id','<>',96)
            ->select([
                'erp_product_list.id as aid',
                'erp_product_list.model',
                'erp_purchase_order_goods.id as bid',
                'erp_purchase_order_goods.price as purchase_price',
                'erp_product_price.id as cid',
                'erp_product_price.has_stock',
                'erp_product_price.price_d as price'
            ])
            ->groupBy('erp_product_list.id')
            ->get();
        foreach ($spuList as $k=>$v){
            DB::table('erp_product_list')->where('id',$v->aid)->update([
                'sku_info' => json_encode([[
                    "q"=>'规格',
                     'a'=>$v->model
                ]]),
                'price' => $v->price?:0,
                'origin_price' => $v->purchase_price? : 0 ,
                'number' => $v->has_stock? : 0 ,
            ]);

        }


        dd(1);

    }
}
