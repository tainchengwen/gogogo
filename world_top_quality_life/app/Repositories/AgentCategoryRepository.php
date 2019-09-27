<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class AgentCategoryRepository extends BaseRepository {
  /**
   * 获取相应代理商的分类
   */
  public function getAgentCategoryTree($request) {
    // 生成树，并且桉树渲染
    // 所有一级目录
    $categories = DB::table('erp_agent_spu_category')
                      -> where('path','')
                      -> where('business_id', $request->business_id)
                      -> where(function($query)use($request){
                        if($request->ignoreHide){
                          $query -> where('mp_flag', 1);
                        }
                      })
                      -> orderBy('sort_index', 'desc')
                      -> orderBy('id', 'desc')
                      -> select([
                        'id',
                        'name',
                        'sort_index',
                        'mp_flag',
                        'path',
                        'image',
                        'business_id'
                      ])
                      -> get();
    $this->generateTree($request, $categories);
    // 根目录
    return [
        'code' => 200,
        'data' => $categories
    ];
  }

  public function generateTree($request, $abc) {
    foreach ($abc as $key => $category) {
      $children = DB::table('erp_agent_spu_category')
                      -> where('path','like','%,'.$category->id)
                      -> where(function($query)use($request){
                        if($request->ignoreHide){
                          $query -> where('mp_flag', 1);
                        }
                      })
                      -> orderBy('sort_index', 'desc')
                      -> orderBy('id', 'desc')
                      -> select([
                        'id',
                        'name',
                        'sort_index',
                        'mp_flag',
                        'path',
                        'image',
                        'business_id'
                      ])
                      -> get();
    $abc[$key]->deep = count(explode(",", $category->path));
    $abc[$key]->haveChildren = count($children) > 0;
    if (count($children)) {
        $abc[$key]->children = $children;
    }
    if(count($children)){
        $this->generateTree($children);
    }
    $abc[$key]->title = $category->name;
    $abc[$key]->name = $category->sort_index . '.' . $category->name;
    }
  }

  /**
   * 代理商增加分类
   */
  public function addAgentCategory($request) {
    // 必须code唯一，不能重复
    $count = DB::table('erp_agent_spu_category')
                -> where([
                  'code' => $request->code
                ])
                -> count();
    if (!empty($count)) {
      return [
        'code' => 500,
        'msg' => '该编码已存在！'
      ];
    }
    // 顶级分类
    if (!empty($request->parentId)) {
      // 父分类
      $parent = DB::table('erp_agent_spu_category')
                    -> where('id', $request->parentId)
                    -> first();
      // 当前路径为父分类路径+父分类id
      $pathArr = explode(",", $parent->path);
      // 检测深度 只允许三级分类
      if (count($pathArr) > 2) {
        return [
          'code' => 500,
          'msg' => '最多允许三级分类！'
        ];
      }
      array_push($pathArr, $parent->id);
    }
    $categoryId = DB::table('erp_agent_spu_category')
                      -> insertGetId([
                        'name'        => $request -> name,
                        'sort_index'  => $request -> sort_index,
                        'mp_flag'     => $request -> mp_flag,
                        'path'        => empty($request->parentId) ? '' : implode(",", $pathArr),
                        'image'       => 'ali_oss:'.$request -> image,
                        'business_id' => $request -> business_id
                      ]);
    return [
      'code' => 200,
      'msg' => '新增成功'
    ];
  }

  /**
   * 删除代理分类
   */
  public function delAgentCategory($request) {
    // 当前分类
    $node = DB::table('erp_agent_spu_category')
                -> where('id', $request->id)
                -> first();
    // 如果下面有分类，则不允许删除
    $chindrenCount = DB::table('erp_agent_spu_category')
                        -> where('path','like', $node->path.','.$node->id.'%')
                        -> where('business_id', $request->business_id)
                        -> count();
    if (!empty($chindrenCount)) {
      return [
        'code' => 500,
        'msg' => '请先删除所有子分类！'
      ];
    }
    DB::table('erp_agent_spu_category')
        -> where('id',$node->id)
        -> delete();
    return [
        'code' => 200,
        'msg' => '删除成功'
    ];
  }

  /**
   * 修改代理分类
   */
  public function editAgentCategory($request) {
    // 必须code唯一，不能重复
    $count = DB::table('erp_agent_spu_category')
                -> where('id', '!=' , $request->id)
                -> where('business_id', $request->business_id)
                -> where('name', $request->name)
                -> count();
    if (!empty($count)) {
      return [
        'code' => 500,
        'msg' => '该名称已存在！'
      ];
    }
    DB::table('erp_agent_spu_category')
        ->where('id',$request -> id)
        -> update([
          'name'       => $request->name,
          'sort_index' => $request->sort_index,
          'mp_flag'    => $request->mp_flag,
          'image'      => 'ali_oss:'.$request->image,
        ]);
    return [
      'code' => 200,
      'msg' => '编辑成功'
    ];
  }

  //查代理分类详情
  public function agentCategoryInfo($request){
    $info = DB::table('erp_agent_spu_category')->where('id','=',$request->category_id)-> first();
    if ($info -> image) {
      $info -> image_url = getImageUrl($info -> image);
      $info -> image=substr($info -> image,8);
    }

    return [
      'code' => 200,
      'data' => $info
    ];
  }
}