<?php

namespace App\Repositories;

use Httpful\Request;
use Illuminate\Support\Facades\DB;
use App\Repositories\AgentCategoryRepository;
use App\ProductList;
use App\PublicStockOrder;
use App\SPUList;
class AgentNewSPURepository extends BaseRepository
{
    public function __construct(
        AgentCategoryRepository $agentCategoryRepository
    )
    {
        $this->agentCategoryRepository = $agentCategoryRepository;
    }

    public function searchPaginate($request, $pageSize = 7)
    {
        $spuList = DB::table('erp_spu_list')
            ->leftJoin('erp_business_spu_link_new','erp_business_spu_link_new.new_spu_id','erp_spu_list.id')
            ->where([
                ['erp_spu_list.status', 1 ],
                ['erp_spu_list.business_id' ,$request->business_id]
            ])
            ->where(function($query)use($request){
                if($request->class_id){
                    $query->where('erp_spu_list.class_id',$request->class_id);
                }
                if($request->keyword){
                    $query->where('erp_spu_list.name','like',"%{$request->keyword}%")
                    ->orWhere('erp_spu_list.sub_name','like',"%{$request->keyword}%");

                }

            })
            ->select([
                'erp_spu_list.*',
                'erp_business_spu_link_new.spu_id as bindSpuId'
            ])
            ->paginate(20);
        foreach ($spuList as $k=>$v){
            $stock = DB::table('erp_spu_sku_link')
                ->leftJoin('erp_product_list','erp_product_list.id','erp_spu_sku_link.sku_id')
                ->where('erp_spu_sku_link.spu_id',$v->id)
                ->where('erp_product_list.price','<>','0.00')
                ->select(DB::raw("sum(erp_product_list.number) as number,min(erp_product_list.price) as price"))->first();

            if($v->bindSpuId){
                //分销商品 查找关联的库存
                $stock2 = DB::table('erp_spu_sku_link')
                    ->leftJoin('erp_product_list','erp_product_list.id','erp_spu_sku_link.sku_id')
                    ->where('erp_spu_sku_link.spu_id',$v->bindSpuId)
                    ->where('erp_product_list.price','<>','0.00')
                    ->select(DB::raw("sum(erp_product_list.number) as number"))->first();
                $stock->number = $stock2->number;
            }

            $v->price = $stock->price;
            $v->img = getImageUrl($v->img);
            if($stock->number <= 0){
                unset($spuList[$k]);
                continue;
            }




        }
        return $spuList;



    }

    public function home($request,$catgories)
    {
        $recommended = [];
        foreach ($catgories as $key => $value) {
            $request->class_id = $value->id;
            $recommended[] = [
                'id'           => $value->id,
                'categoryName' => $value->title,
                'spus'         => $this->tmp_search($request, 12)
            ];
        }
        return $recommended;
    }

    private function tmp_search($request ,$limit = 5)
    {
        $spulist = DB::table('erp_spu_list')
            ->where([
                ['class_id','=',$request->class_id],
                ['status' , '=' , '1']
            ])
            ->select([
                'id as spu_id',
                'name',
                'sub_name',
                'img'
            ])
            ->limit($limit)->get();

        foreach ($spulist as $key => $value){
            $sku = $this->fetchSkuUnderSpu($value->spu_id) -> first();;
            if (!$sku) {
                unset($spulist[$key]);
                continue;
            }

            // 价格保留两位小数
            $sku->price =  $this->math_add($sku->price,0);
            $spulist[$key]->spu_image = getImageUrl($spulist[$key]->img);
            $spulist[$key]->sku = $sku;

        }



        return $spulist;
    }

    //获取单个spu详情
    public function spuInfo($spu_id){
        $spuInfo = SPUList::leftJoin('erp_business_spu_link_new','erp_business_spu_link_new.new_spu_id','erp_spu_list.id')
            ->where('erp_spu_list.id',$spu_id)
            ->select([
                'erp_spu_list.*',
                'erp_business_spu_link_new.spu_id as bindSpuId'
            ])
            ->first();
        $spuInfo->skuInfo =$this->fetchSkuUnderSpu($spu_id);


        if($spuInfo->bindSpuId>0){
            $temp = $this->fetchSkuUnderSpu($spuInfo->bindSpuId)->toArray();
            $temp = array_column($temp,'number','product_no');
            foreach ($spuInfo->skuInfo as $k=>$v){
                $v->number = $temp[$v->product_no];
            }
        }
        $spuInfo->skuChoise = $this->returnSkuInfo($spuInfo->skuInfo);

        $spuInfo->tags = DB::table('erp_spu_tag_link')
            ->where('spu_id',$spu_id)->pluck('id');


        return $spuInfo;
    }

    public function addSpuAndSku($request){
        $type=0;
        if ($request->type){
            $type=$request->type;
        }
        $spuId = DB::table('erp_spu_list') -> insertGetId([
            'name'         => $request -> spu_name,
            'sub_name'     => empty($request -> sub_name) ? "" :  $request -> sub_name,
            'details'      => $request -> details,
            'class_id'     => empty($request -> class_id) ? 0 :  $request -> class_id,
            'created_at'   => time(),
            'updated_at'   => time(),
            'img'          => $request->has('img')&&!empty($request->img)?'ali_oss:'.$request->img:'',
            'type'         => $type,
            'business_id'  => $request->business_id,
            'status'       => $request->status,
            'marked_price' => empty($request -> marked_price) ? 0 :  $request -> marked_price,
        ]);
        // 存tags
        if (!empty($request->tags)) {
            $links = [];
            foreach ($request -> tags as $tag) {
                array_push($links, ['tag_id' => $tag, 'spu_id' => $spuId]);
            }
            DB::table('erp_spu_tag_link')->insert($links);
        }
        $sku_info  = json_decode($request->sku_info,1);
        $sku_arr = [];
        if(empty($sku_info)){
            //默认生成一个sku
            $sku_id = ProductList::insertGetId([
                'product_no'  => 'Random'.mt_rand(1000,9999),
                'image'       => $request->has('img')&&!empty($request->img)?'ali_oss:'.$request->img:'',
                'class_id'    => empty($request -> class_id) ? 0 :  $request -> class_id,
                'product_name'=> $request -> spu_name,
                'price'       => $request ->price,
                'origin_price'=> $request ->origin_price,
                'business_id'      => $request->business_id,
                'created_at'       => time(),
                'updated_at'       => time(),
                'number'           => $request->number,
            ]);
            $sku_arr[] = $sku_id;
        }else{
            foreach($sku_info as $v){
                $sku_id = ProductList::insertGetId([
                    'product_no'       => $v['product_no'],
                    'image'            => $request->has('img')&&!empty($request->img)?'ali_oss:'.$request->img:'',
                    'class_id'         => empty($request -> class_id) ? 0 :  $request -> class_id,
                    'product_name'     => $request -> spu_name,
//                        'weight'           => $v['weight'],
//                        'model'            => $v['model'],
                    'number'           => $v['number'],
//                        'product_long'     => floatval($v['product_long']),
//                        'product_wide'     => floatval($v['product_wide']),
//                        'product_height'   => floatval($v['product_height']),
//                        'volume'           => floatval($v['product_long']) * floatval($v['product_wide']) * floatval($v['product_height']),
                    'price'            => $v['price'],
                    'origin_price'     => $v['origin_price'],
                    'sku_info'         => json_encode($v['sku']),
//                        'japan_name'       => $v['japan_name'],
//                        'element_zh'       => $v['element_zh'],
//                        'element_ja'       => $v['element_ja'],
//                        'product_country'  => $v['product_country'],
//                        'volume_weight'    => $v['volume_weight'],
//                        'physics_weight'   => $v['physics_weight'],
                    'business_id'      => $request->business_id,
                    'created_at'       => time(),
                    'updated_at'       => time(),
                ]);
                $sku_arr[] = $sku_id;
//                    if(isset($v['image_details']) && $v['image_details']){
//                        DB::table('mpshop_product_images') -> where([
//                            'product_no' => $v['product_no'],
//                        ]) -> update([
//                            'flag' => 1
//                        ]);
//                        $json = json_decode($v['image_details'],true);
//                        foreach($json as $vo){
//                            DB::table('mpshop_product_images') -> insertGetId([
//                                'product_no' => $v['product_no'],
//                                'image' => 'ali_oss:'.$vo,
//                                'created_at' => time(),
//                                'updated_at' => time(),
//                            ]);
//                        }
//                    }
            }
        }
        $insertData = [];
        foreach ($sku_arr as $skuId) {
            $count = DB::table('erp_spu_sku_link')
                -> where('spu_id', $spuId)
                -> where('sku_id', $skuId)
                -> where('flag', 0)
                -> count();
            if (empty($count)) {
                array_push($insertData, ['sku_id' => $skuId, 'spu_id' => $spuId]);
            }
        }
        DB::table('erp_spu_sku_link')->insert($insertData);
        return [
            'sku_arr' =>$sku_arr,
            'spu_id'  =>$spuId
        ];
    }

    public function editSpuAndSku($request){
        //0普通1套餐
        $type=0;
        if ($request->type){
            $type=$request->type;
        }
        DB::table('erp_spu_list')->where('id',$request->id)-> update([
            'name'         => $request -> spu_name,
            'sub_name'     => empty($request -> sub_name) ? "" :  $request -> sub_name,
            'details'      => $request -> details,
            'class_id'     => empty($request -> class_id) ? 0 :  $request -> class_id,
            'created_at'   => time(),
            'updated_at'   => time(),
            'img'          => $request->has('img')&&!empty($request->img)?'ali_oss:'.$request->img:'',
            'type'         => $type,
            'business_id'  => $request->business_id,
            'status'       => $request->status,
            'marked_price' => empty($request -> marked_price) ? 0 :  $request -> marked_price,
        ]);
        // 存tags
        DB::table('erp_spu_tag_link')->where('spu_id',"=",$request->id)->delete();
        if (!empty($request->tags)) {
            $links = [];
            foreach ($request -> tags as $tag) {
                array_push($links, ['tag_id' => $tag, 'spu_id' => $request->id]);
            }
            DB::table('erp_spu_tag_link')->insert($links);
        }
        $deleteSkuid = DB::table('erp_spu_sku_link')->where('spu_id',"=",$request->id)->pluck('sku_id');
        DB::table('erp_spu_sku_link')->where('spu_id',"=",$request->id)->delete();

        $sku_info  = json_decode($request->sku_info,1);
        $sku_arr = [];
        //需要库存数量调整的数据
        $sku_number_change = [];
        //需要下自动采购单的数据
        $sku_auto_purchase = [];

        if(empty($sku_info)){
            //默认生成一个sku
            if($request->sku_id){
                $sku_id =$request->sku_id;
                ProductList::where('id',$request->sku_id)->update([
                    'image'       => $request->has('img')&&!empty($request->img)?'ali_oss:'.$request->img:'',
                    'class_id'    => empty($request -> class_id) ? 0 :  $request -> class_id,
                    'product_name'=> $request -> spu_name,
                    'price'       => $request ->price,
                    'origin_price'=> $request ->origin_price,
                    'business_id'      => $request->business_id,
                    'updated_at'       => time(),
                    'number'           => $request->number,
                ]);
            }else{
                $sku_id = ProductList::insertGetId([
                    'product_no'  => 'Random'.mt_rand(1000,9999),
                    'image'       => $request->has('img')&&!empty($request->img)?'ali_oss:'.$request->img:'',
                    'class_id'    => empty($request -> class_id) ? 0 :  $request -> class_id,
                    'product_name'=> $request -> spu_name,
                    'price'       => $request ->price,
                    'origin_price'=> $request ->origin_price,
                    'business_id'      => $request->business_id,
                    'created_at'       => time(),
                    'updated_at'       => time(),
                    'number'           => $request->number,
                ]);
            }

            $sku_arr[] = $sku_id;
        }else{
            $postSkuId = [];
            foreach($sku_info as $v){
                if(isset($v['id']) && $v['id'] ){
                    $postSkuId[] = $v['id'];
                }
            }
            if($postSkuId)ProductList::whereNotIn('id',$postSkuId)->whereIn('id',$deleteSkuid)->delete();
            foreach($sku_info as $v){
                if(isset($v['id']) && $v['id'] ){
                    $postSkuId[] = $v['id'];
                    $sku_id = $v['id'];
                    $originSku = ProductList::where([
                        'id'=>$sku_id])->first();
                    if(!$originSku) return false;

                    if($originSku->number == $v['number']){
                        //数量未发生改变

                    }elseif($v['number'] > $originSku->number){
                        $sku_number_change[] = [
                            'sku_id'  => $sku_id,
                            'number'  => intval($v['number'] - $originSku->number),
                            'type'    => 'plus'
                        ];

                    }else{
                        $sku_number_change[] = [
                            'sku_id'  => $sku_id,
                            'number'  => intval($originSku->number-$v['number']),
                            'type'    => 'reduce'
                        ];


                    }

                    ProductList::where('id',$sku_id)->update([
                        'product_no'       => $v['product_no'],
                        'number'           => $v['number'],
                        'price'            => $v['price'],
                        'origin_price'     => $v['origin_price'],
                        'sku_info'         => json_encode($v['sku']),
                        'updated_at'       => time(),
                    ]);

                }else{
                    $sku_id = ProductList::insertGetId([
                        'product_no'       => $v['product_no'],
                        'image'            => $request->has('img')&&!empty($request->img)?'ali_oss:'.$request->img:'',
                        'class_id'         => empty($request -> class_id) ? 0 :  $request -> class_id,
                        'product_name'     => $request -> spu_name,
                        'number'           => $v['number'],
                        'price'            => $v['price'],
                        'origin_price'     => $v['origin_price'],
                        'sku_info'         => json_encode($v['sku']),
                        'business_id'      => $request->business_id,
                        'created_at'       => time(),
                        'updated_at'       => time(),
                    ]);
                    $sku_auto_purchase[] = $sku_id;
                }
                $sku_arr[] = $sku_id;
            }
        }

        $insertData = [];
        foreach ($sku_arr as $skuId) {
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

        return [
            'sku_arr' => $sku_arr,
            'sku_auto_purchase' => $sku_auto_purchase,
            'sku_number_change' => $sku_number_change
        ];


    }

    //检测商品是否符合上架条件
    public function checkSpuStatus(){
        $spuParam = [


        ];
    }

    //返回所有的规格
    public function returnSkuInfo($sku){
        if(!count($sku)) return [];
        $showSku = [];
        foreach ($sku as $k=>$v){
            if($v->sku_info){
                $v->sku_info = json_decode($v->sku_info,1);
                foreach ($v->sku_info as $ko =>$vo){
                        $showSku[$vo['q']][] = $vo['a'];
                }

            }
        }
        //整理返回数据
        $returnSku = [];
        if($showSku){
            foreach ($showSku as $k=>$v){
                $returnSku[] = [
                    'name'  => $k,
                    'choose'=> array_unique($v)
                ];
            }

        }
        return $returnSku;

    }

    //通过选择的规格来获取对应的skuId
    public function fetchSkuIdBySkuInfo($request){
        $skuInfo = json_decode($request->skuInfo,1);
        $returnSkuId = 0;
        $realSkuInfo = $this->fetchSkuUnderSpu($request->spuId);
        if(!empty($skuInfo)){
            //不为空  去匹配现有的sku
            $realSkuInfo->each(function($item,$key){
                $item->sku_info = json_decode($item->sku_info,1);
            });
            $skuInfo = array_column($skuInfo,'choose','name');
            foreach ($realSkuInfo as $k=>$v){
                $num = 0;
                $count = count($v->sku_info);
                foreach($v->sku_info as $k1=> $v1){
                     if($skuInfo[$v1['q']] == $v1['a']){
                         $num++;
                     }
                }
                if($num == $count){
                    //匹配
                    $returnSkuId = $v->id;
                    break;
                }
            }
            return $returnSkuId;

        }else{

            return $realSkuInfo->first()->id;

        }

    }

    //获取spu下面的sku
    private function fetchSkuUnderSpu($spuId){
        $sku  =  DB::table('erp_spu_sku_link')
            ->leftJoin('erp_product_list','erp_product_list.id','erp_spu_sku_link.sku_id')
            ->where([
                ['erp_spu_sku_link.spu_id' ,'=' ,$spuId],
                ['erp_product_list.status' ,'=', 0],
                ['erp_product_list.price','>',0],
                ['erp_product_list.origin_price','>',0]
            ])
            ->select([
                'erp_product_list.id',
                'erp_product_list.product_no',
                'erp_product_list.number',
                'erp_product_list.status',
                'erp_product_list.price',
                'erp_product_list.origin_price',
                'erp_product_list.sku_info',
            ])
            ->get();

        return $sku;

    }


}
