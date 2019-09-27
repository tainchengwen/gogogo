<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TagsController extends Controller
{
    public function theTagsList(Request $request)
    {
        if($request -> name){
            $where[] = [
                'name','like','%'.trim($request -> name).'%'
            ];
        } else {
            $where = [];
        }
        $list = DB::table('erp_tags')
            -> where($where)
            -> orderBy('id','desc')
            -> select([
                '*'
                // 'id as value', 
                // 'name as label'
            ]) -> get();
        return $list;
    }

    public function add(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        //查看这个 是否重复
        $info = DB::table('erp_tags')
            -> where([
                'name' => trim($request -> name)
            ]) 
            -> first();

        if($info){
            return [
                'code' => 500,
                'msg' => '名字重复'
            ];
        }

        DB::table('erp_tags')
            ->insert([
                'name' => trim($request -> name)
            ]);

        return [
            'code' => 200,
            'msg' => '添加成功'
        ];
    }

    public function edit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|numeric|exists:erp_tags,id',
            'name' => 'required'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $tag = DB::table('erp_tags')
            -> where([
                'id' => $request->id
            ]) 
            -> first();

        //查看这个 是否重复
        $count = DB::table('erp_tags')
            -> where([
                'name' => trim($request -> name),
            ])
            // 允许无修改保存
            -> where('id', '!=', $request -> id)
            -> count();

        if($count){
            return [
                'code' => 500,
                'msg' => '名称重复'
            ];
        }

        DB::table('erp_tags')
        -> where([
            'id' => $request -> id
        ]) -> update([
            'name' => $request->name,
        ]);

        return [
            'code' => 200,
            'msg' => '修改成功'
        ];
    }

    public function del(Request $request)
    {
        // 被关联的的都不允许删除
        $validator = Validator::make($request->all(), [
            'tagIds'    => 'required|array|min:1',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        // 部分删除成功，那几个存在关联，请先解除关联
        $errorIds = [];

        foreach ($request->tagIds as $key => $tagId) {
            //查看这个 是否重复
            $count = DB::table('erp_spu_tag_link')
                -> where([
                    'tag_id' => $tagId,
                ])
                -> count();

            if (!empty($count)) {
                array_push($errorIds, $tagId);
                continue;
            }

            // 删除
            DB::table('erp_tags')
                -> where([
                    'id' => $tagId,
                ])
                -> delete();
        }

        if(count($errorIds)){
            $errorMsg = '部分删除失败：';
            $errorTags = DB::table('erp_tags')
            -> whereIn('id', $errorIds)
            -> get();

            foreach ($errorTags as $key => $errorTag) {
                $errorMsg .= $errorTag->name.'、';
            }
            $errorMsg .= '存在关联，不能删除。';

            return [
                'code' => 500,
                'msg'  => $errorMsg
            ];
        } else {
            return [
                'code' => 200,
                'msg' => '删除成功'
            ];
        }
    }

}