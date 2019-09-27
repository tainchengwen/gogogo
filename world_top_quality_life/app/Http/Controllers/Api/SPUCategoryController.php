<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\JsonResource;
use App\SPUList;
use App\SPUProductList;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SPUCategoryController extends Controller
{

    //上传商品图片
    public function uploadImage(Request $request){
        $validator = Validator::make($request->all(), [
            //'image' => 'required|image|max:10'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $newFileName = md5(time().rand(10000,99999)).'.jpg';
        \Illuminate\Support\Facades\Log::info(json_encode($_FILES));

        $is_move = file_put_contents(public_path().'/uploads/images/'.$newFileName,file_get_contents($_FILES['image']['tmp_name']));

        //$is_move = move_uploaded_file($request -> image,public_path().'/uploads/images/'.$newFileName);
        if(!$is_move){
            return [
                'code' => 500,
                'msg' => '上传失败'
            ];
        }else{
            return [
                'code' => 200,
                'file_name' => $newFileName,
                'url' => getImageUrl($newFileName)
            ];
        }
    }
            
    // 增
    public function addCategory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'      => 'required|max:64',
            'code'      => 'required|max:64',
            'sort_index' => 'required|numeric',
            'mp_flag'   => 'required|numeric',
            'parentId'  => 'required|numeric',
            'image'     => 'required',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        // 必须code唯一，不能重复
        $count = DB::table('erp_spu_category')
            -> where([
                'code' => $request->code
            ]) -> count();
        
        if (!empty($count)) {
            return [
                'code' => 500,
                'msg' => '该编码已存在！'
            ];
        }

        // 顶级分类
        if (!empty($request->parentId)) {
            // 父分类
            $parent = DB::table('erp_spu_category')
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

        $categoryId = DB::table('erp_spu_category') -> insertGetId([
            'name'      => $request -> name,
            'code'      => $request -> code,
            'sort_index' => $request -> sort_index,
            'mp_flag'   => $request -> mp_flag,
            'path'      => empty($request->parentId) ? '' : implode(",", $pathArr),
            'image'     => 'ali_oss:'.$request -> image
        ]);

        return [
            'code' => 200,
            'msg' => '新增成功'
        ];
    }

    // 删
    public function delCategory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'  => 'required|numeric|exists:erp_spu_category,id',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        // 当前分类
        $node = DB::table('erp_spu_category')
        -> where('id', $request->id)
        -> first();

        // 如果下面有分类，则不允许删除
        $chindrenCount = DB::table('erp_spu_category')
            -> where('path','like', $node->path.','.$node->id.'%')
            -> count();

        if (!empty($chindrenCount)) {
            return [
                'code' => 500,
                'msg' => '请先删除所有子分类！'
            ];
        }

        DB::table('erp_spu_category')
            -> where('id',$node->id)
            -> delete();

        return [
            'code' => 200,
            'msg' => '删除成功'
        ];
    }

    // 改
    public function editCategory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'      => 'required|max:64',
            'code'      => 'required|max:64',
            'sort_index' => 'required|numeric',
            'mp_flag'   => 'required|numeric',
            'id'        => 'required|numeric|exists:erp_spu_category,id',
            'image'     => 'required'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        // 必须code唯一，不能重复
        $count = DB::table('erp_spu_category')
            -> where('code', '=' , $request->code)
            -> where('id', '!=' , $request->id)
            -> count();

        if (!empty($count)) {
            return [
                'code' => 500,
                'msg' => '该编码已存在！'
            ];
        }

        DB::table('erp_spu_category')
            ->where('id',$request -> id)
            -> update([
            'name' => $request->name,
            'code' => $request->code,
            'sort_index' => $request->sort_index,
            'mp_flag' => $request->mp_flag,
            'image' => 'ali_oss:'.$request->image,
        ]);

        return [
            'code' => 200,
            'msg' => '编辑成功'
        ];
    }

    //查分类详情
    public function categoryInfo(Request $request){
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|numeric|exists:erp_spu_category,id'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $info = DB::table('erp_spu_category')->where('id','=',$request->category_id)-> first();
        if($info -> image){
            $info -> image_url = getImageUrl($info -> image);
            $info -> image=substr($info -> image,8);
        }
        return [
            'code' => 200,
            'data' => $info
        ];
    }

    // 查
    public function getCategoryTree(Request $request)
    {
        // 生成树，并且桉树渲染
        // 所有一级目录
        $categories = DB::table('erp_spu_category')
        -> where('path','')
        -> orderBy('sort_index', 'desc')
        -> orderBy('id', 'desc')
        -> select([
            'id',
            'name',
            'code',
            'sort_index',
            'mp_flag',
            'path',
            'image'
        ])
        -> get();
        $this->generateTree($categories);
        // 根目录
        return [
            'code' => 200,
            'data' => $categories
        ];
    }

    private function generateTree($abc)
    {
        foreach ($abc as $key => $category) {
            $children = DB::table('erp_spu_category')
            -> where('path','like','%,'.$category->id)
            -> orderBy('sort_index', 'desc')
            -> orderBy('id', 'desc')
            -> select([
                'id',
                'name',
                'code',
                'sort_index',
                'mp_flag',
                'path',
                'image'
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
            $abc[$key]->name = $category->sort_index . '.' . $category->name;
        }
    }

}