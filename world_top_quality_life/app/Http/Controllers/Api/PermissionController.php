<?php

namespace App\Http\Controllers\Api;

use App\Configure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionController extends Controller
{
    //权限列表
    public function PermissionList(){

        $permissions_f1 = DB::table('erp_f_permissions') -> where('fid',0) -> get();
        $temp = [];
        foreach($permissions_f1 as $k => $vo){
            $temp[$k]['id'] = $vo -> id;
            $temp[$k]['event'] = $vo -> name;
            $temp[$k]['comment'] = '';
            $temp_f2 = [];
            //f1的子元素
            $children_arr = DB::table('erp_f_permissions') -> where('fid',$vo -> id) -> get();
            foreach($children_arr as $key => $value){
                $temp_f2[$key]['id'] = $value -> id;
                $temp_f2[$key]['event'] = $value -> name;

                $temp_permissions = [];
                $children_arr_permissions = DB::table('permissions') -> where('fid',$value -> id) -> get();

                foreach($children_arr_permissions as $key_permission => $value_permission){
                    $temp_permissions[$key_permission]['value'] = $value_permission -> id;
                    $temp_permissions[$key_permission]['name'] = $value_permission -> name;
                }
                //return $temp_permissions;


                $temp_f2[$key]['comment'] = $temp_permissions;
            }



            $temp[$k]['children'] = $temp_f2;



        }



        return $temp;



    }

    //角色列表
    public function roleList(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
            //筛选条件
            /*
             * 角色名称 name
             */

        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $name = $request -> name;
        if($name){
            $roles = Role::where('business_id',$request -> business_id) -> where('name','like','%'.trim($name).'%') -> get();
        }else{
            $roles = Role::where('business_id',$request -> business_id) -> get();
        }
        if($request -> return_type == 'option'){
            return Configure::dealCalssArray($roles);
        }
        return $roles;
    }


    //增加角色
    public function addRole(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:50',
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $info = DB::table('roles') -> where([
            'business_id' => $request -> business_id,
            'name' => trim($request -> name)
        ]) -> first();
        if($info){
            return [
                'code' => 500,
                'msg' => '该名称已存在'
            ];
        }


        $res = Role::create([
            'name' => trim($request -> name),
            'business_id' => $request -> business_id
        ]);
        if($res){
            return [
                'code' => 200,
                'msg' => '保存成功'
            ];
        }else{
            return [
                'code' => 500,
                'msg' => '保存失败'
            ];
        }
    }


    //编辑角色
    public function editRole(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|numeric|exists:roles',
            'name' => 'required|max:50',
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $info = DB::table('roles') -> where([
            'business_id' => $request -> business_id,
            'name' => trim($request -> name)
        ]) -> first();
        if($info){
            return [
                'code' => 500,
                'msg' => '已存在此名称'
            ];
        }


        Role::where([
            'id' => $request -> id,
            'business_id' => $request -> business_id
        ]) -> update([
            'updated_at' => date('Y-m-d H:i:s'),
            'name' => $request -> name
        ]);

        return [
            'code' => 200,
            'msg' => '更新成功'
        ];
    }

    public function roleInfo(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|numeric|exists:roles',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        $info = Role::findById($request -> id);

        $permissions = DB::table('role_has_permissions') -> where([
            'role_id' => $request -> id
        ]) ->  get();
        $temp_arr = [];
        foreach($permissions as $vo){
            $temp_arr[] = $vo -> permission_id;
        }

        $info -> permissions = $temp_arr;
        /*
        foreach($permissions as $k =>  $vo){
            $temp = Permission::findById($vo -> permission_id);
        }
        */

        return $info;
    }


    //把权限分配给角色
    public function givePermissionToRole(Request $request){
        $validator = Validator::make($request->all(), [
            'permission_ids' => 'required|json',
            'role_id' => 'required|numeric|exists:roles,id',

        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        DB::beginTransaction();
        try {
            //清除此角色 所有的权限
            DB::table('role_has_permissions')->where([
                'role_id' => $request->role_id
            ])->delete();

            //角色 是否拥有权限
            $permission_arr = json_decode($request->permission_ids, true);

            $permissions = Permission::whereIn('id', $permission_arr)->get();
            //return $permissions;
            $role = Role::find($request->role_id);
            $role->syncPermissions($permissions);
            DB::commit();
            return [
                'code' => 200,
                'msg' => '授权成功'
            ];
        }catch (\Exception $exception){
            DB::rollBack();
            return [
                'code' => 500,
                'msg' => '授权失败'
            ];
        }
    }


    //角色 删除权限
    public function revokePermissionToRole(Request $request){
        $validator = Validator::make($request->all(), [
            'permission_id' => 'required|numeric|exists:permissions,id',
            'role_id' => 'required|numeric|exists:roles,id',

        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        $permission = Permission::findById($request -> permission_id);
        //return $permission;
        $role = Role::find($request -> role_id);

        $role->revokePermissionTo($permission);
        return [
            'code' => 200,
            'msg' => '取消授权成功'
        ];
    }





}
