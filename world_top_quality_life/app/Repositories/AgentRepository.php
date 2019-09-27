<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class AgentRepository extends BaseRepository
{

  /**
   * 一键代理
   */
  public function oneKeyAgent($business_id)
  {
        // 1、copy分类
        $categories = DB::table('erp_agent_spu_category')
                        -> where('business_id', config('admin.zhanpeng_business_id'))
                        -> select([
                            'name',
                            'image',
                            'mp_flag',
                            'sort_index'
                        ])
                        -> get();
        $insertData = [];
        foreach ($categories as $key => $value) {
            $insertData[] = [
                'name'        => $value->name,
                'image'       => $value->image,
                'mp_flag'     => $value->mp_flag,
                'sort_index'  => $value->sort_index,
                'business_id' => $business_id
            ];
        }
        DB::table('erp_agent_spu_category')
        -> insert($insertData);

        // 2、copy 代理spu
        $zhanpengSPUs = DB::table('erp_business_spu_link as link')
        -> leftJoin('erp_agent_spu_category as category', 'link.class_id', 'category.id')
        -> where('link.business_id', config('admin.zhanpeng_business_id'))
        -> where('link.flag', 0)
        -> select([
            'link.spu_id',
            'link.status',
            'link.mp_name_id',
            'category.name'
        ])
        -> get();

        $agentSPUs = [];
        $catgoryMap = [];

        foreach ($zhanpengSPUs as $key => $value) {
            if (!empty($value->name) && empty($catgoryMap[$value->name])) {
                // 查一下
                $category = DB::table('erp_agent_spu_category')
                -> where('business_id', $business_id)
                -> where('name', $value->name)
                -> first();

                if ($category) {
                    $catgoryMap[$value->name] = $category->id;
                }
            }

            $agentSPUs[] = [
                'business_id' => $business_id,
                'spu_id'      => $value->spu_id,
                'status'      => $value->status,
                'mp_name_id'  => $value->mp_name_id,
                'class_id'    => !empty($catgoryMap[$value->name]) ? $catgoryMap[$value->name] : 0
            ];
            
        }
        DB::table('erp_business_spu_link')
        -> insert($agentSPUs);

        // 3、copy 售价
        $zhanpengPrice = DB::table('erp_agent_price')
        -> where('flag', 0)
        -> where('business_id', config('admin.zhanpeng_business_id'))
        -> select([
            'sku_id',
            'mp_name_id',
            'price',
            'original_price',
            'status'
        ])
        -> get();

        $insterPrice = [];

        foreach ($zhanpengPrice as $key => $value) {
            $insterPrice[] = [
                'sku_id'         => $value->sku_id,
                'business_id'    => $business_id,
                'mp_name_id'     => $value->mp_name_id,
                'price'          => $value->price,
                'original_price' => $value->original_price,
                'status'         => $value->status,
            ];
        }
        DB::table('erp_agent_price')
        -> insert($insterPrice);

        return true;
  }

  /**
   * 获取代理商的已关联SPU列表
   */
  public function getLinkedSpu($request)
  {
    $wherecategory = [];
    if($request->class_id){
        $wherecategory[] = [
          'erp_business_spu_link.class_id',$request->class_id
        ];
    }
    $list = DB::table('erp_business_spu_link')
                -> leftJoin('erp_spu_list', 'erp_spu_list.id', 'erp_business_spu_link.spu_id')
                -> leftJoin('erp_agent_spu_category','erp_business_spu_link.class_id','erp_agent_spu_category.id')
                -> where([
                    'erp_business_spu_link.mp_name_id'  => $request->id,
                    'erp_business_spu_link.flag'        => 0,
                    'erp_business_spu_link.business_id' => $request->business_id
                ])
                -> where(function($query)use($request){
                    if($request->spuname){
                        $query -> where('erp_spu_list.name', 'like', '%'."{$request->spuname}".'%');
                    }
                })
                -> where($wherecategory)
                -> select([
                    'erp_spu_list.name',
                    'erp_spu_list.sub_name',
                    'erp_business_spu_link.spu_id as id',
                    'erp_business_spu_link.id as business_spu_id',
                    'erp_business_spu_link.status',
                    'erp_agent_spu_category.name as class_name',
                    'erp_agent_spu_category.id as class_id'
                ])
                -> paginate(isset($request->per_page)?$request->per_page:20);
    return $list;
  }

  /**
   * 更新某个SPU的分类信息
   */
  public function updateSpuCategory($request) {
    $info = DB::table('erp_business_spu_link')
                -> whereIn('erp_business_spu_link.id', $request->id)
                -> where('flag', 0)
                -> update([
                  'class_id' => $request->class_id
                ]);
    return [
      'code' => 200,
      'msg'  => '修改成功'
    ];
  }

  /**
   * 获取某个SPU的分类信息
   */
  public function getLinkedSpuCategory($request) {
    $info = DB::table('erp_business_spu_link')
                -> leftJoin('erp_agent_spu_category', 'erp_business_spu_link.class_id', 'erp_agent_spu_category.id')
                -> where('erp_business_spu_link.id', $request->id)
                -> select([
                  'erp_business_spu_link.id as business_spu_id',
                  'erp_agent_spu_category.name as name',
                  'erp_agent_spu_category.id as id'
                ])
                -> first();
    $info->selectedOptions = $this->getBreadCategroy($info->id);
    return [
      'code' => 200,
      'data' => $info
    ];
  }

  /**
   * 获取分类数组
   */
  public function getBreadCategroy($id) {
      // 获取分类数组
      $category = DB::table('erp_agent_spu_category')
          -> where('id', $id)
          -> first();
      if(!empty($category)){
          $selectedOptions = explode(",", $category->path.','.$category->id);
          array_shift($selectedOptions);
          foreach ($selectedOptions as $key => $selectedOption) {
              $selectedOptions[$key] = (int)$selectedOption;
          }
          return $selectedOptions;
      }else{
          return [];
      }
  }
  
  /**
  * 获取代理商未关联的SPU列表
  */
  public function getUnLinkedSpu($request) {
    $spuids = DB::table('erp_business_spu_link')
                  -> where([
                      'erp_business_spu_link.mp_name_id'  => $request->id,
                      'erp_business_spu_link.flag'        => 0,
                      'erp_business_spu_link.business_id' => $request->business_id
                  ])
                  -> pluck('spu_id');
    $list = DB::table('erp_mp_name_spu_link')
                -> leftJoin('erp_spu_list', 'erp_mp_name_spu_link.spu_id', 'erp_spu_list.id')
                -> where('erp_mp_name_spu_link.flag', 0)
                -> where('erp_mp_name_spu_link.mp_name_id', $request->id)
                -> whereNotIn('erp_mp_name_spu_link.spu_id', $spuids)
                -> where(function($query)use($request){
                  if($request->spuname){
                      $query -> where('erp_spu_list.name', 'like', '%'."{$request->spuname}".'%');
                  }
                })
                -> select([
                    'erp_spu_list.id as id',
                    'erp_spu_list.name',
                    'erp_spu_list.sub_name',
                ])
                -> paginate(isset($request->per_page)?$request->per_page:20);
    // $list = DB::table('erp_spu_list')
    //             -> where('erp_spu_list.flag', 0)
    //             -> whereNotIn('erp_spu_list.id', $spuids)
    //             -> where(function($query)use($request){
    //                 if($request->spuname){
    //                     $query -> where('erp_spu_list.name', 'like', '%'."{$request->spuname}".'%');
    //                 }
    //             })
    //             -> select([
    //                 'erp_spu_list.id as id',
    //                 'erp_spu_list.name',
    //                 'erp_spu_list.sub_name',
    //             ])
    //             -> paginate(isset($request->per_page)?$request->per_page:20);
    return $list;
  }

  /**
  * 代理商关联SPU
  */
  public function addLinkSpus($request) {
    DB::beginTransaction();
    try {
      $insertData = [];
      foreach ($request->spuIds as $spuId) {
        $count = DB::table('erp_business_spu_link')
                    -> where('mp_name_id', $request->mpnameid)
                    -> where('spu_id', $spuId)
                    -> where('business_id', $request->business_id)
                    -> where('flag', 0)
                    -> count();
        if (empty($count)) {
          array_push($insertData, ['business_id' => $request->business_id, 'spu_id' => $spuId, 'mp_name_id' => $request->mpnameid, 'create_at' => time()]);
        }
      }
      DB::table('erp_business_spu_link')->insert($insertData);
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

  /**
   * 代理商取消关联SPU
   */
  public function removeLinkSpus($request) {
      DB::table('erp_business_spu_link')
          -> where('mp_name_id', $request->mpnameid)
          -> whereIn('spu_id', $request->spuIds)
          -> where('flag', 0)
          -> update([
              'flag' => 1
          ]);
      return [
          'code' => 200,
          'msg' => '取消关联成功'
      ];
  }

  /**
   * 获取代理商已关联SKU
   */
  public function getLinkedSku($request) {
    $mpNameId = $request->id;
    $businessId = $request->business_id;

    //找等级
    $level = DB::table('erp_business')
                 -> leftJoin('wxuser', 'erp_business.master_id', 'wxuser.id')
                 -> where('erp_business.id', $businessId)
                 -> where('wxuser.flag', 0)
                 -> where('erp_business.flag', 0)
                 -> value('market_class');

    $spuids = DB::table('erp_business_spu_link')
                  -> where([
                      'erp_business_spu_link.mp_name_id'  => $request->id,
                      'erp_business_spu_link.flag'        => 0,
                      'erp_business_spu_link.business_id' => $request->business_id
                    ])
                  -> pluck('spu_id');
    $inskuids = DB::table('erp_spu_sku_link')
                  -> where('erp_spu_sku_link.flag', 0)
                  -> whereIn('erp_spu_sku_link.spu_id', $spuids)
                  -> distinct('sku_id')
                  -> pluck('sku_id');
    $list = DB::table('erp_product_list')
                // -> leftJoin('erp_agent_price', 'erp_agent_price.sku_id', 'erp_product_list.id')
                -> leftJoin('erp_agent_price', function ($join) use ($mpNameId, $businessId) {
                    $join-> on('erp_product_list.id', '=', 'erp_agent_price.sku_id')
                          -> where('erp_agent_price.mp_name_id', '=', "$mpNameId")
                          -> where('erp_agent_price.business_id', '=', "$businessId")
                          -> where('erp_agent_price.flag', '=', 0);
                })
                -> leftJoin('erp_product_price', 'erp_product_list.id', 'erp_product_price.product_id')
                // -> leftJoin('erp_business', 'erp_business.id', 'erp_agent_price.business_id')
                // -> leftJoin('erp_business', function ($join) use ($businessId) {
                //   $join-> on('erp_agent_price.business_id', '=', 'erp_business.id')
                //         -> where('erp_business.id', '=', "$businessId")
                //         -> where('erp_business.flag', '=', 0);
                // })
                // -> leftJoin('wxuser', 'erp_business.master_id', 'wxuser.id')
                -> leftJoin('erp_product_class as product_class', 'erp_product_list.class_id', 'product_class.id')
                -> leftJoin('erp_product_class as product_brand', 'erp_product_list.brand_id', 'product_brand.id')
                -> leftJoin('erp_product_class as product_series', 'erp_product_list.series_id', 'product_series.id')
                // -> where('erp_agent_price.mp_name_id', $request->id)
                // -> where('erp_agent_price.business_id', $request->business_id)
                -> where('erp_product_price.mp_name_id', $request->id)
                -> where('erp_product_price.flag', 0)
                -> where('erp_product_price.status', 1)
                // -> where('erp_business.id', $businessId)
                // -> where('erp_agent_price.flag', 0)
                -> whereIn('erp_product_list.id', $inskuids)
                -> where(function($query)use($request){
                    //产品类别
                    if($request -> class_id){
                        $query -> where('erp_product_list.class_id',$request -> class_id);
                    }
                    //产品品牌
                    if($request -> brand_id){
                        $query -> where('erp_product_list.brand_id',$request -> brand_id);
                    }
    
                    //产品系列
                    if($request -> series_id){
                        $query -> where('erp_product_list.series_id',$request -> series_id);
                    }
    
                    //商品编号
                    if($request -> product_no){
                        $query -> where('erp_product_list.product_no',$request -> product_no);
                    }
                    //商品名称
                    if($request -> product_name){
                        $query -> where('erp_product_list.product_name','like','%'.trim($request -> product_name).'%');
                    }
                })
                -> select([
                  // 'wxuser.market_class',
                  'erp_agent_price.price as price',
                  'erp_agent_price.status as status',
                  'erp_agent_price.mp_name_id as mpnameid',
                  'erp_product_list.id as id',
                  'erp_product_list.product_no as product_no',
                  'erp_product_list.product_name as product_name',
                  'erp_product_list.model',
                  'erp_product_list.number',
                  'product_class.name as product_class_name',
                  'product_brand.name as product_brand_name',
                  'product_series.name as product_series_name',
                  'erp_product_price.price_a',
                  'erp_product_price.price_b',
                  'erp_product_price.price_c',
                  'erp_product_price.price_d',
                  'erp_product_price.price_s',
                  'erp_agent_price.original_price',
                ])
                -> orderBy('id')
                -> paginate(isset($request->per_page)?$request->per_page:20);
                // -> get();
    foreach ($list as $k => $v) {
      if ($level === 0) {
        $list[$k]->cost_price = $v->price_d;
      } else if($level === 1) {
        $list[$k]->cost_price = $v->price_c;
      } else if($level === 2) {
        $list[$k]->cost_price = $v->price_b;
      } else if($level === 3) {
        $list[$k]->cost_price = $v->price_a;
      } else if($level === 4) {
        $list[$k]->cost_price = $v->price_s;
      }
        //$list[$k]->price = (float)$v->price;
        // $list[$k]->price_a = (float)$v->price_a;
        // $list[$k]->price_b = (float)$v->price_b;
        // $list[$k]->price_c = (float)$v->price_c;
        // $list[$k]->price_d = (float)$v->price_d;

        //兼容旧版程序处理原价
        if($v->original_price=='0'){
            $list[$k]->original_price='';
        }
    }
    return $list;
  }

  /**
   * 设置代理价格
   */
  public function updateAgentPrice($request) {
    $info = DB::table('erp_agent_price')
                -> where([
                  'sku_id'      => $request->id,
                  'mp_name_id'  => $request->mpnameid,
                  'business_id' => $request->business_id,
                  'flag'        => 0
                ])
                -> first();
    if ($info) {
      DB::table('erp_agent_price')
          -> where([
            'id' => $info->id,
          ])
          -> update([
            'price' => $request->price,
            'original_price' => $request->has('original_price')&&!empty($request->original_price)?$request->original_price:'0'
          ]);
    } else {
      DB::table('erp_agent_price')
          -> insert([
            'sku_id' => $request->id,
            'price' => $request->price,
            'mp_name_id' => $request->mpnameid,
            'business_id' => $request->business_id,
            'flag' => 0,
            'status' => 0,
              'original_price' => $request->has('original_price')&&!empty($request->original_price)?$request->original_price:'0'
          ]);
    }

    return [
      'code' => 200,
      'msg'  => '成功'
    ];
  }

  /**
   * 代理商上架
   */
  public function agentSkuPutOn($request) {
    $info = DB::table('erp_agent_price')
                -> whereIn('sku_id', $request->skuIds)
                -> where([
                  'mp_name_id'  => $request->mpnameid,
                  'business_id' => $request->business_id,
                  'flag'        => 0
                ])
                -> first();
    if ($info) {
      DB::table('erp_agent_price')
          -> where([
            'id' => $info->id
          ])
          -> update([
            'status' => 1
          ]);
      return [
        'code' => 200,
        'msg'  => '成功'
      ];
    } else {
      return [
        'code' => 500,
        'msg' => '售价未维护'
      ];
    }
  }

  /**
   * 代理商下架
   */
  public function agentSkuPutOff($request) {
    $info = DB::table('erp_agent_price')
                -> whereIn('sku_id', $request->skuIds)
                -> where([
                  'mp_name_id'  => $request->mpnameid,
                  'business_id' => $request->business_id,
                  'flag'        => 0
                ])
                -> update([
                  'status' => 0
                ]);
    return [
      'code' => 200,
      'msg'  => '下架成功'
    ];
  }

  public function getBanners($request)
  {
    
    $banners = DB::table('erp_setting_banners')
    -> where('business_id', $request->business_id)
    -> where('is_show', 1)
    -> where('is_del', 0)
    -> select([
        'id',
        //'spu_id',
        'image'
    ])
    -> get();

    foreach ($banners as $key => $banner) {
        $banner->image = getImageUrl($banner->image);
    }

    return $banners;
  }

  /**
   * 获取代理基础配置
   */
  public function getAgentBasicSetting($request) {
    $newInfo = [];
    $info = DB::table('erp_setting')
                -> where('business_id', $request->business_id)
                -> select([
                  'id',
                  'color',
                  'name',
                  'notice',
                  'logo',
                  'image'       
                ])
                -> first();
   if ($info) {
        if($info->logo){
            $info->logoUrl = getImageUrl($info->logo);
            $info->logo=substr($info->logo,8);
        }
        if($info->image){
            $info->imageUrl = getImageUrl($info->image);
            $info->image=substr($info->image,8);
        }
        return [
            'code' => 200,
            'data' => $info
        ];
   } else {
        $newInfo['name'] = '';
        $newInfo['notice'] = '';
        $newInfo['logo'] = '';
        $newInfo['image'] = '';
        return [
            'code' => 200,
            'data' => $newInfo
        ];
   }
  }

  /**
   * 更新代理基础配置
   */
  public function updateAgentBasicSetting($request) {
    $info = DB::table('erp_setting')
                -> where('business_id', $request->business_id)
                -> first();
    if ($info) {
      DB::table('erp_setting')
          -> where('business_id', $request->business_id)
          -> update([
            'name'       => $request->name,
            'notice'     => $request->notice,
            'logo'       => 'ali_oss:'.$request->logo,
            'image'      => 'ali_oss:'.$request->image,
            'updated_at' => time()
          ]);
    } else {
      DB::table('erp_setting')
          -> insert([
            'business_id' => $request->business_id,
            'name'        => $request->name,
            'notice'      => $request->notice,
            'logo'        => 'ali_oss:'.$request->logo,
            'image'       => 'ali_oss:'.$request->image,
            'created_at'  => time(),
            'updated_at'  => time()
          ]);
    }
    return [
      'code' => 200,
      'mag' => '更新成功'
    ];
  }

}