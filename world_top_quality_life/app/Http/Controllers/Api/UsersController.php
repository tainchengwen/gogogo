<?php

namespace App\Http\Controllers\Api;

use App\Business;
use App\Configure;
use App\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class UsersController extends Controller
{
    //用户列表
    public function theList(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }


        if($request -> type == "salier"){
            $user = auth('api')->user();
            $model_has_roles = DB::table('model_has_roles')
                -> leftJoin('roles','model_has_roles.role_id','roles.id')
                -> where([
                    'model_has_roles.model_id' => $user -> id
                ])
                -> select([
                    'roles.name as  roles_name'
                ])
                -> get();
            //如果是客服 只展示自己
            //是不是客服角色
            $is_kefu_roles = 0;
            if($model_has_roles){
                foreach($model_has_roles as $vo){
                    if(strstr($vo -> roles_name,'客服')){
                        $is_kefu_roles = 1;
                        break;
                    }
                }
            }

            //先找这个事业部下 销售 的角色
            $roles_model = DB::table('model_has_roles')
                -> leftJoin('roles','model_has_roles.role_id','roles.id')
                -> where(function($query)use($is_kefu_roles,$user){
                    if($is_kefu_roles){
                        //如果是客服 则 只显示自己
                        $query -> where('model_has_roles.model_id',$user -> id);
                    }else{
                        $query -> where('roles.name','like','%销售%') -> orWhere('roles.name','like','%客服%');
                    }

                })
                -> where([
                    'roles.business_id' => $request -> business_id
                ])
                -> select([
                    'model_has_roles.model_id'
                ])
                -> get();
            if(count($roles_model)){
                $user_ids = [];
                foreach($roles_model as $vo){
                    $user_ids[] = $vo -> model_id;
                }
                $users = DB::table('user_has_business')
                    -> leftJoin('users','user_has_business.user_id','users.id')

                    -> where(function($query)use($request){
                        if($request -> name){
                            $query -> where('name','like','%'.trim($request -> name).'%');
                        }
                        $query -> where( 'user_has_business.business_id','=',$request -> business_id);
                    })
                    -> whereIn('users.id',$user_ids)
                    -> where('users.username','<>','admin')
                    ->orderBy('users.id','desc')
                    -> select([
                        'users.*'
                    ]) -> get();
                return $users;
            }else{
                return [

                ];
            }

        }

        //查找此事业部下的所有人
        $users = DB::table('user_has_business')
            -> leftJoin('users','user_has_business.user_id','users.id')

            -> where(function($query)use($request){
                if($request -> name){
                    $query -> where('name','like','%'.trim($request -> name).'%');
                }
                $query -> where( 'user_has_business.business_id','=',$request -> business_id);
            })
            -> where('users.username','<>','admin')
            ->orderBy('users.id','desc')
            -> select([
                'users.*'
            ]) -> get();



        /*
        if(count($users)){
            foreach($users as $k => $vo){
                $model = User::find($vo -> id);
                $model -> getRoleNames();
            }
        }
        */

        return $users;
    }

    public function getLoginInfo(){
        $user = auth('api')->user();
        //查找此用户拥有的事业部
        $business_id = DB::table('user_has_business')
            -> leftJoin('erp_business','user_has_business.business_id','erp_business.id')
            -> select([
                'erp_business.id',
                'erp_business.name'
            ])
            -> where([
                'user_has_business.user_id' => $user -> id,
                'erp_business.flag' => 0
            ]) -> get();
        //return $business_id;
        return Configure::dealCalssArray($business_id);
    }

    //禁用该用户
    public function banUser(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|numeric|exists:users,id',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        $user =auth('api')->user();
        if($request->id != 3){
            if(User::where('id',$request->id)->update(['status'=>0])){
                return [
                    'code' => 0,
                    'msg' => '禁用成功'
                ];
            }else{
                return [
                    'code' => 1,
                    'msg' => '禁用失败'
                ];

            }
        }else{
            return [
                'code' => 1,
                'msg' => '超级管理员不能禁用'
            ];
        }
    }

    //启用该用户
    public function unbanUser(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|numeric|exists:users,id',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        if(User::where('id',$request->id)->update(['status'=>1])){
            return [
                'code' => 0,
                'msg' => '启用成功'
            ];
        }else{
            return [
                'code' => 1,
                'msg' => '启用失败'
            ];

        }

    }




    public function getUserInfo(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|numeric|exists:users',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $user = User::find($request -> id);
        $user->getRoleNames();
        return $user;

    }


    //给用户 分配角色
    public function giveRoleToUser(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|numeric|exists:users,id',
            'role_ids' => 'required|json',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        $user = User::find($request -> user_id);

        //把此人的角色 全部清除 然后分配
        DB::table('model_has_roles') -> where('model_id',$user -> id) ->delete();
        $role_ids_arr = json_decode($request -> role_ids,true);
        foreach($role_ids_arr as $vo){
            $user->assignRole($vo);
        }

        return [
            'code' => 200,
            'msg' => '分配成功'
        ];

        /*
        $is_has = $user->hasRole($request -> role_id);
        if($is_has){
            return [
                'code' => 500,
                'msg' => '已经拥有此角色'
            ];
        }else{
            $user->assignRole($request -> role_id);
            return [
                'code' => 200,
                'msg' => '分配成功'
            ];
        }
        */
    }


    //移除角色 $user->removeRole('writer');
    public function removeRole(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|numeric|exists:users,id',
            'role_id' => 'required|numeric|exists:roles,id',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        $user = User::find($request -> user_id);
        $user->removeRole($request -> role_id);

        return [
            'code' => 200,
            'msg' => '移除角色成功'
        ];


    }

    public function editUser(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|numeric|exists:users',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        //要改的名字 不能是已存在的
        if($request -> name){
            $user_info = User::where([
                'name' => $request -> name,
            ]) -> where('id','<>',$request -> id)
                -> first();
            if($user_info){
                return [
                    'code' => 500,
                    'msg' => '该用户名已存在'
                ];
            }
        }



        $userinfo = User::find($request -> id);
        User::where('id',$request -> id) -> update([
            'name' => $request -> name?$request -> name:$userinfo -> name,
            'email' => $request -> email?$request -> email:$userinfo -> email,
            'password' => $request -> password?bcrypt($request -> password):$userinfo -> password,
        ]);

        return [
            'code' => 200,
            'msg' => '修改成功'
        ];


    }


    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255',
            'email' => 'required|string|max:255',
            'password' => 'required|string|min:6',
            'business_id' => 'required|exists:erp_business,id'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $userinfo = User::where([
            'username' => $request -> username
        ]) -> first();
        if($userinfo){
            return [
                'code' => 500,
                'msg' => '用户名已存在'
            ];
        }


        $userid = User::insertGetId([
            'name' => $request -> name,
            'username' => $request -> username,
            'email' => $request -> email,
            //'business_id' => $request -> business_id,
            'password' => bcrypt($request -> password),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        //给这个人 增加事业部权限
        DB::table('user_has_business') -> insert([
            'business_id' => $request -> business_id,
            'user_id' => $userid,
            'created_at' => time(),
            'updated_at' => time(),
        ]);


        return [
            'code' => 200,
            'msg' => '成功'
        ];
    }


    //人员 编辑事业部
    public function giveBusinessTo(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|numeric|exists:users,id',
            'business_ids' => 'required|json', //事业部ids [1,2,3,4]
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        //$user = User::find($request -> user_id);
        $business_arr = json_decode($request -> business_ids,true);
        if(!is_array($business_arr) || !count($business_arr)){
            return [
                'code' => 500,
                'msg' => 'json为空'
            ];
        }

        $temp_ids = [];
        foreach($business_arr as $vo){
            if(in_array($vo,$temp_ids)){
                return [
                    'code' => 500,
                    'msg' => 'business_id重复'
                ];
            }else{
                $temp_ids[] = $vo;
            }

            $business_info = Business::find($vo);
            if(!$business_info){
                return [
                    'code' => 500,
                    'msg' => 'business_id错误'
                ];
            }


        }



        //把此人的事业部全部清除 然后分配
        DB::table('user_has_business') -> where([
            'user_id' => $request -> user_id,
        ]) -> delete();

        foreach($business_arr as $vo){
            $id = DB::table('user_has_business') -> insertGetId([
                'user_id' => $request -> user_id,
                'business_id' => $vo,
                'created_at' => time(),
                'updated_at' => time(),
            ]);
        }
        return [
            'code' => 200,
            'msg' => '分配成功'
        ];
    }


    //返回用户所拥有的菜单
    public function menu(Request $request){

        $user = auth('api')->user();
        if($user -> username == 'admin'){
            return [
                'admin'
            ];
        }

        //找到此人 此事业部的角色
        $permissions = DB::table('permissions')
            -> leftJoin('role_has_permissions','role_has_permissions.permission_id','permissions.id')
            -> leftJoin('roles','roles.id','role_has_permissions.role_id')
            -> leftJoin('model_has_roles','model_has_roles.role_id','roles.id')





            -> where([
                'roles.business_id' => $request -> business_id,
                'model_has_roles.model_id' => $user -> id
            ])
            -> select([
                'permissions.*'
            ])
            -> get();
        //查此用户拥有的角色
        //$permissions = $user->getAllPermissions();


        //return $permissions;
        $sort_str = [];

        //return $permissions;
        /*
        [
    {
        "id": 1,
        "name": "列表展示",
        "guard_name": "web",
        "created_at": "2019-01-21 16:00:00",
        "updated_at": "2019-01-21 16:00:00",
        "fid": 11,
        "pivot": {
            "role_id": 2,
            "permission_id": 1
        }
    },
    {
        "id": 2,
        "name": "新增采购单",
        "guard_name": "web",
        "created_at": "2019-01-21 16:00:00",
        "updated_at": "2019-01-21 16:00:00",
        "fid": 11,
        "pivot": {
            "role_id": 2,
            "permission_id": 2
        }
    },
    {
        "id": 3,
        "name": "采购单详情",
        "guard_name": "web",
        "created_at": "2019-01-21 16:00:00",
        "updated_at": "2019-01-21 16:00:00",
        "fid": 11,
        "pivot": {
            "role_id": 2,
            "permission_id": 3
        }
    }
]

        */

        $permissions_id_arr = [];
        $f_permissions_ids = [];
        foreach($permissions as $k => $vo){
            $sort_str[] = $vo -> sort_str;
            if($vo -> fid){
                $f_permissions_ids[]= $vo -> fid;
            }
            /*
            $permissions_id_arr[$k]['id'] = $vo -> id;
            $permissions_id_arr[$k]['fid'] = $vo -> fid;
            $permissions_id_arr[$k]['name'] = $vo -> name;
            */
        };

        //return array_values(array_unique($f_permissions_ids));

        //return $permissions_id_arr;

        //此人所拥有的角色， 所拥有的父权限点 （次外层菜单）
        $f_permissionss = DB::table('erp_f_permissions as f1')
            -> leftJoin('erp_f_permissions as f2','f1.fid','f2.id')
            -> whereIn('f1.id',array_unique($f_permissions_ids))
            -> select([
                'f1.sort_str as f1_sort_str',
                'f2.sort_str as f2_sort_str' ,
            ])
            ->get();
        foreach($f_permissionss as $vo){
            if(!in_array($vo -> f1_sort_str,$sort_str,true)){
                $sort_str[] = $vo -> f1_sort_str;
            }
            if(!in_array($vo -> f2_sort_str,$sort_str,true)){
                $sort_str[] = $vo -> f2_sort_str;
            }
        }

        $super_send_order = config('admin.super_send_order');

        if(in_array($user -> username,$super_send_order)){
            //超级仓库管理员的权限
            $sort_str[] = '999';
        }

        if(!count($sort_str)){
            return ['undefined'];
        }
        return $sort_str;


    }



    //用户拥有的事业部列表
    public function businessList(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|numeric|exists:users,id',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }


        $business_info = DB::table('user_has_business')
            -> leftJoin('erp_business','user_has_business.business_id','erp_business.id')
            -> select([
                'erp_business.id',
                'erp_business.name',
            ])
            -> where('user_has_business.user_id',$request -> id) -> get();

        if($request -> return_type == 'option'){
            return Configure::dealCalssArray($business_info);
        }

        return $business_info;
    }


}
