<?php

namespace App\Http\Controllers\Api;

use App\Account;
use App\Business;
use App\Configure;
use App\Port;
use App\Repositories\OssRepository;
use App\Storehouse;
use App\Supplier;
use App\User;
use App\WareHouse;
use EasyWeChat\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Repositories\AgentRepository;

class BusinessController extends Controller
{
    public function __construct( AgentRepository $agentRepository){
        $this->agentRepository = $agentRepository;
    }

    public function add(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:50',
            'describe' => 'max:250',
            'currency' => 'required|numeric',
            'attribute' => 'required|numeric',
            'master_id' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $info = DB::table('erp_business')
            -> where([
                'flag' => 0,
                'name' => $request -> name,
            ]) -> first();
        if($info){
            return [
                'code' => 500,
                'msg' => '名字重复'
            ];
        }



        DB::beginTransaction();
        try{
            $business_id = $this->create($request->all());
            if($business_id){
                //添加事业部的时候 把admin的business 权限加上
                $users = DB::table('users') -> where([
                    'username' => 'admin'
                ]) -> first();
                if($users){
                    DB::table('user_has_business') -> insertGetId([
                        'user_id' => $users -> id,
                        'business_id' => $business_id,
                        'created_at' => time(),
                        'updated_at' => time(),
                    ]);
                }


                //Log::info($request -> banben);
                //港口 仓库 库位 关系
                $port_warehouse_storehouse = [
                    '香港港' => [
                        '展鹏库' => [
                            '主库'
                        ]
                    ],
                    '深圳港' => [
                        '展鹏库' => [
                            '主库'
                        ]
                    ],
                    '虚拟1港' => [
                        '虚拟1库' => [
                            '虚拟1主库'
                        ]
                    ],
                    '虚拟2港' => [
                        '虚拟2库' => [
                            '虚拟2主库'
                        ]
                    ],
                ];
                //Log::info(print_r($port_warehouse_storehouse,true));
                foreach($port_warehouse_storehouse as $port_name => $temp){
                    Log::info(print_r($port_name,true));
                    Log::info(print_r($temp,true));
                    $warehouse_ids = [];
                    foreach($temp as $warehouse => $storehouses){
                        //生成仓库
                        $warehouse_id = WareHouse::insertGetId([
                            'name'                 => $warehouse,
                            'describe'             => '',
                            'business_id'          => $business_id,
                            'image'                => '',
                            'created_at'           => time(),
                            'updated_at'           => time()
                        ]);
                        $warehouse_ids[] = $warehouse_id;
                        //生成库位
                        foreach($storehouses  as $storehouse){
                            Storehouse::insertGetId([
                                'name' => $storehouse,
                                'describe' => '',
                                'business_id' => $business_id,
                                'warehouse_id' => $warehouse_id,
                                'created_at' => time(),
                                'updated_at' => time(),
                                'is_unusual' => 1, //1正常0异常
                            ]);
                        }

                    }

                    //Log::info(json_encode($warehouse_ids));
                    //自动生成港口、港口与仓库的绑定
                    Port::insertGetId([
                        'name' => $port_name,
                        'describe' => $port_name.'描述',
                        'business_id' => $business_id,
                        'created_at' => time(),
                        'updated_at' => time(),
                        'warehouse_ids' => json_encode($warehouse_ids), //可收货港口
                    ]);

                }

                if($request -> banben == 2){
                //if(true){
                    //增加 代理版 事业部

                    //添加供应商
                    Supplier::insert([
                        'name' => '展鹏供应商',
                        'describe' => trim($request -> name).'事业部展鹏供应商',
                        'business_id' => $business_id,
                        'created_at' => time(),
                        'updated_at' => time(),
                    ]);




                    //财务账号
                    $account_id = Account::insertGetId([
                        'account_name' => trim($request -> name).'默认账户',
                        'business_id' => $business_id,
                        'describe' => '',
                        'currency' => 1,
                        'zhifubao_account' => '',
                        'created_at' => time(),
                        'updated_at' => time(),
                    ]);



                    $business_set_id = 58;
                    //找出指定事业部的角色
                    $business_set = DB::table('erp_business') -> where([
                        'id' => $business_set_id
                    ]) -> first();
                    if($business_set){
                        //Log::info(222222);
                        //找到这个事业部的角色
                        $roles = DB::table('roles') -> where([
                            'business_id' => $business_set_id
                        ]) -> get();
                        //Log::info(222222);
                        foreach($roles as $vo){

                            //Log::info(222222);
                            //插入一个管理员的角色
                            $roles_id = DB::table('roles') -> insertGetId([
                                'name' => $vo -> name,
                                'guard_name' => $vo -> guard_name,
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s'),
                                'business_id' => $business_id
                            ]);
                            //Log::info(222222);

                            //找到这个角色 的 用户
                            $users = DB::table('model_has_roles')
                                -> leftJoin('users','model_has_roles.model_id','users.id')
                                -> where([
                                    'model_has_roles.role_id' => $vo -> id
                                ])
                                -> select([
                                    'users.id'
                                ])
                                -> get();

                            //这些用户 需要有新增代理事业部的权限，也要有新增代理事业部 的 角色
                            foreach($users as $value){
                                $user_has_business = DB::table('user_has_business') -> where([
                                    'business_id' => $business_id,
                                    'user_id' => $value -> id,
                                ]) -> first();
                                if(!$user_has_business){
                                    DB::table('user_has_business') -> insert([
                                        'business_id' => $business_id,
                                        'user_id' => $value -> id,
                                        'created_at' => time(),
                                        'updated_at' => time(),
                                    ]);
                                }


                                DB::table('model_has_roles') -> insertGetId([
                                    'role_id' => $roles_id,
                                    'model_id' => $value -> id,
                                    'model_type' => "App\User"
                                ]);
                            }




                            //插入用户
                            $userid = User::insertGetId([
                                'name' => trim($request -> name).$vo -> name.'用户',
                                'username' => str_random(10),
                                'email' => '',
                                'password' => bcrypt('abc123456'),
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s'),
                            ]);
                            //Log::info(222222);

                            //给这个人 增加事业部权限
                            DB::table('user_has_business') -> insert([
                                'business_id' => $business_id,
                                'user_id' => $userid,
                                'created_at' => time(),
                                'updated_at' => time(),
                            ]);
                            //Log::info(222222);

                            //此人增加角色
                            DB::table('model_has_roles') -> insertGetId([
                                'role_id' => $roles_id,
                                'model_id' => $userid,
                                'model_type' => "App\User"
                            ]);

                            //复制 角色 所拥有的权限
                            $role_has_permissions = DB::table('role_has_permissions')
                                -> where([
                                    'role_id' => $vo -> id
                                ]) -> get();

                            foreach($role_has_permissions as $temp){
                                DB::table('role_has_permissions') -> insertGetId([
                                    'permission_id' => $temp -> permission_id,
                                    'role_id' => $roles_id
                                ]);
                            }

                        }


                        //这个事业部的用户 也挪过去



                    }

                    // 一键代理
                    $this->agentRepository->oneKeyAgent($business_id);
                }

                DB::commit();

                return [
                    'code' => 200,
                    'msg' => '成功'
                ];
            }else{
                DB::rollBack();
                return [
                    'code' => 500,
                    'msg' => '失败'
                ];
            }
        }catch (\Exception $exception){
            Log::info($exception ->getTraceAsString());
            DB::rollBack();
            return [
                'code' => 500,
                'msg' => $exception ->getTraceAsString()
            ];
        }

    }

    public function register(Request $request){
        $validator = Validator::make($request->all(), [
            'business_name' => 'required|max:50',
            'describe' => 'max:250',
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255',
            'email' => 'required|string|max:255',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $info = DB::table('erp_business')
            -> where([
                'flag' => 0,
                'name' => $request -> name,
            ]) -> first();
        if($info){
            return [
                'code' => 500,
                'msg' => '名字重复'
            ];
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
        DB::beginTransaction();
        try {
            $userid = User::insertGetId([
                'name' => $request->name,
                'username' => $request->username,
                'email' => $request->email,
                'password' => bcrypt($request->password),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $business_id = Business::insertGetId([
                'name' => $request->name,
                'describe' => $request->describe,
                'business_type' => 1,
                'currency' => 1,
                'attribute' => 1,
                'created_at' => time(),
                'updated_at' => time(),
                'master_id' => $userid
            ]);
            //生成默认供应商
            Supplier::insert([
                'name' => '默认',
                'describe' => trim($request -> name).'供应商',
                'business_id' => $business_id,
                'created_at' => time(),
                'updated_at' => time(),
            ]);
            //给这个人 增加事业部权限
            DB::table('user_has_business')->insert([
                'business_id' => $business_id,
                'user_id' => $userid,
                'created_at' => time(),
                'updated_at' => time(),
            ]);
            if ($business_id) {
                //添加事业部的时候 把admin的business 权限加上
                $users = DB::table('users')->where([
                    'username' => 'admin'
                ])->first();
                if ($users) {
                    DB::table('user_has_business')->insertGetId([
                        'user_id' => $users->id,
                        'business_id' => $business_id,
                        'created_at' => time(),
                        'updated_at' => time(),
                    ]);
                }

                $port_warehouse_storehouse = [
                    '香港港' => [
                        '展鹏库' => [
                            '主库'
                        ]
                    ],
                    '深圳港' => [
                        '展鹏库' => [
                            '主库'
                        ]
                    ],
                    '虚拟1港' => [
                        '虚拟1库' => [
                            '虚拟1主库'
                        ]
                    ],
                    '虚拟2港' => [
                        '虚拟2库' => [
                            '虚拟2主库'
                        ]
                    ],
                ];
                foreach ($port_warehouse_storehouse as $port_name => $temp) {
                    Log::info(print_r($port_name, true));
                    Log::info(print_r($temp, true));
                    $warehouse_ids = [];
                    foreach ($temp as $warehouse => $storehouses) {
                        //生成仓库
                        $warehouse_id = WareHouse::insertGetId([
                            'name' => $warehouse,
                            'describe' => '',
                            'business_id' => $business_id,
                            'image' => '',
                            'created_at' => time(),
                            'updated_at' => time()
                        ]);
                        $warehouse_ids[] = $warehouse_id;
                        //生成库位
                        foreach ($storehouses as $storehouse) {
                            Storehouse::insertGetId([
                                'name' => $storehouse,
                                'describe' => '',
                                'business_id' => $business_id,
                                'warehouse_id' => $warehouse_id,
                                'created_at' => time(),
                                'updated_at' => time(),
                                'is_unusual' => 1, //1正常0异常
                            ]);
                        }

                    }
                    //Log::info(json_encode($warehouse_ids));
                    //自动生成港口、港口与仓库的绑定
                    Port::insertGetId([
                        'name' => $port_name,
                        'describe' => $port_name . '描述',
                        'business_id' => $business_id,
                        'created_at' => time(),
                        'updated_at' => time(),
                        'warehouse_ids' => json_encode($warehouse_ids), //可收货港口
                    ]);
                }
                $publicWareHouse = DB::table('erp_warehouse')->where('business_id',0)->first();
                //生成公共库位
                Storehouse::insertGetId([
                    'name' => $request->name.'公共库位',
                    'describe' => '',
                    'business_id' => $business_id,
                    'warehouse_id' => $publicWareHouse->id,
                    'created_at' => time(),
                    'updated_at' => time(),
                    'is_unusual' => 1, //1正常0异常
                ]);
                //财务账号
                $account_id = Account::insertGetId([
                    'account_name' => trim($request->name) . '默认账户',
                    'business_id' => $business_id,
                    'describe' => '',
                    'currency' => 1,
                    'zhifubao_account' => '',
                    'created_at' => time(),
                    'updated_at' => time(),
                ]);
                $business_set_id = 58;
                //找出指定事业部的角色
                $business_set = DB::table('erp_business')->where([
                    'id' => $business_set_id
                ])->first();
                if ($business_set)
                    //找到这个事业部的角色
                    $roles = DB::table('roles')->where([
                        'business_id' => $business_set_id
                    ])->get();
                foreach ($roles as $vo) {
                    //插入一个管理员的角色
                    $roles_id = DB::table('roles')->insertGetId([
                        'name' => $vo->name,
                        'guard_name' => $vo->guard_name,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                        'business_id' => $business_id
                    ]);
                    //找到这个角色 的 用户
                    $users = DB::table('model_has_roles')
                        ->leftJoin('users', 'model_has_roles.model_id', 'users.id')
                        ->where([
                            'model_has_roles.role_id' => $vo->id
                        ])
                        ->select([
                            'users.id'
                        ])
                        ->get();

                    //这些用户 需要有新增代理事业部的权限，也要有新增代理事业部 的 角色
                    foreach ($users as $value) {
                        $user_has_business = DB::table('user_has_business')->where([
                            'business_id' => $business_id,
                            'user_id' => $value->id,
                        ])->first();
                        if (!$user_has_business) {
                            DB::table('user_has_business')->insert([
                                'business_id' => $business_id,
                                'user_id' => $value->id,
                                'created_at' => time(),
                                'updated_at' => time(),
                            ]);
                        }
                        DB::table('model_has_roles')->insertGetId([
                            'role_id' => $roles_id,
                            'model_id' => $value->id,
                            'model_type' => "App\User"
                        ]);
                    }


                    //插入用户
                    $userid = User::insertGetId([
                        'name' => trim($request->name) . $vo->name . '用户',
                        'username' => str_random(10),
                        'email' => '',
                        'password' => bcrypt('abc123456'),
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);

                    //给这个人 增加事业部权限
                    DB::table('user_has_business')->insert([
                        'business_id' => $business_id,
                        'user_id' => $userid,
                        'created_at' => time(),
                        'updated_at' => time(),
                    ]);

                    //此人增加角色
                    DB::table('model_has_roles')->insertGetId([
                        'role_id' => $roles_id,
                        'model_id' => $userid,
                        'model_type' => "App\User"
                    ]);

                    //复制 角色 所拥有的权限
                    $role_has_permissions = DB::table('role_has_permissions')
                        ->where([
                            'role_id' => $vo->id
                        ])->get();

                    foreach ($role_has_permissions as $temp) {
                        DB::table('role_has_permissions')->insertGetId([
                            'permission_id' => $temp->permission_id,
                            'role_id' => $roles_id
                        ]);
                    }

                }
                DB::commit();

                return [
                    'code' => 200,
                    'msg' => '成功'
                ];

            }
            else{
                DB::rollBack();
                return [
                    'code' => 500,
                    'msg' => '失败'
                ];
            }
        }catch (\Exception $exception){
            Log::info($exception ->getTraceAsString());
            DB::rollBack();
            return [
                'code' => 500,
                'msg' => $exception ->getTraceAsString()
            ];
        }

    }

    protected function create(array $data)
    {
        return Business::insertGetId([
            'name' => $data['name'],
            'describe' => isset($data['describe'])?$data['describe']:'',
            'user_id' => isset($data['user_id'])?$data['user_id']:'',
            'business_type' => $data['banben'] == 1?0:1,
            'currency' => $data['currency'],
            'attribute' => $data['attribute'],
            'created_at' => time(),
            'updated_at' => time(),
            'master_id' => $data['master_id']
        ]);
    }


    public function info(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|numeric|exists:erp_business',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $info = Business::find($request -> id);
        return $info;
    }


    public function edit(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|numeric|exists:erp_business',
            'describe' => 'max:250',
            //'currency' => 'numeric',
            'attribute' => 'numeric',
            'master_id' => 'integer'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        $info = Business::find($request -> id);

        $res = Business::where('id',$request -> id) -> update([
            'name' => isset($request -> name)?$request -> name:$info -> name,
            'describe' => isset($request -> describe)?$request -> describe:$info -> describe,
            //'currency' => isset($request -> currency)?$request -> currency:$info -> currency,
            'attribute' => isset($request -> attribute)?$request -> attribute:$info -> attribute,
            'user_id' => isset($request -> user_id)?$request -> user_id:$info -> user_id,
            'master_id' => isset($request -> master_id)?$request -> master_id:$info -> master_id,
            'updated_at' => time()
        ]);

        if($res){
            return [
                'code' => 200,
                'msg' => '更新成功',
            ];
        }else{
            return [
                'code' => 500,
                'msg' => '更新失败',
            ];
        }

    }

    //删除
    public function delete(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $info = Business::find($request -> id);
        if(!$info){
            return [
                'code' => 500,
                'msg' => 'id有误'
            ];
        }



        //看下有没有采购单
        $order_info = DB::table('erp_purchase_order')
            -> where([
                'flag' => 0,
                'business_id' => $request -> id
            ]) -> first();
        if($order_info){
            return [
                'code' => 500,
                'msg' => '事业部下存在采购订单 不允许删除'
            ];
        }



        $res = Business::where('id',$request -> id) -> update([
            'flag' => 1,
            'updated_at' => time()
        ]);

        if($res){


            //删除了 把事业部的人也去掉
            DB::table('user_has_business')
                -> where([
                    'business_id' => $request -> id
                ]) -> delete();


            return [
                'code' => 200,
                'msg' => '删除成功',
            ];
        }else{
            return [
                'code' => 500,
                'msg' => '删除失败',
            ];
        }
    }

    public function theList(Request $request){
        //事业部名称
        $name = $request -> name;
        if($name){
            $list = Business::where('flag',0) -> where('name','like','%'.trim($name).'%') -> get();
        }else{
            $list = Business::where('flag',0) -> get();
        }

        $config_currency = Configure::getCurrency();
        $config_attribute = Configure::getAttribute();
        foreach($list as $k => $vo){
            $list[$k] -> currency_str = isset($config_currency[$vo -> currency])?$config_currency[$vo -> currency]:'未知';
            $list[$k] -> attribute_str = isset($config_attribute[$vo -> attribute])?$config_attribute[$vo -> attribute]:'未知';
            $list[$k] -> banben_str = $vo -> business_type?'代理版':'标准版';
        }
        if($request -> return_type == 'option'){
            return Configure::dealCalssArray($list);
        }
        return $list;
    }


    /**
     *获取小程序码
     */
    public function mpQRCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'scene' => 'required|max:32',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $info=DB::table('erp_setting')->where('business_id', $request->input('scene'))->first();
        if(!$info){
            return [
                'code' => 404,
                'msg' => '不存在该条数据'
            ];
        }

        $ossLogoName = explode(":",$info->logo)[1]; // logo的oss路径名称
        $ossQrcodeName = ''; // 小程序太阳码的oss路径名称

        if($info->qrcode_image){
            $ossQrcodeName = explode(':', $info->qrcode_image)[1];

            return [
                'code' => 200,
                'data' => [
                    'qrcode_image' => $this -> ossWatermarkQRCode('fenithcdn', $ossQrcodeName, $ossLogoName)
                ]
            ];
        }

        //不存在则生成新的码
        $wxConfig = [
            'app_id' => env('MINI_SHOP_APPID'),
            'secret' => env('MINI_SHOP_SECRET'),
            'response_type' => 'array'
        ];

        $app = Factory::miniProgram($wxConfig);
        $response = $app->app_code->getUnlimit($request->input('scene'));
        //$response = $app->app_code->get($request->input('scene'));

        if ($response instanceof \EasyWeChat\Kernel\Http\StreamResponse) {
            $filename = $response->save(public_path('uploads/images'));
            //上传oss
            $oss=new OssRepository();
            $result=$oss->uploadFile(public_path('uploads/images').'/'.$filename);
            $ossQrcodeName = explode(':', $result)[1];
            DB::table('erp_setting')->where('business_id', $request->input('scene'))->update(['qrcode_image'=>$result]);

            //删除本地文件
            unlink(public_path('uploads/images').'/'.$filename);

            return [
                'code' => 200,
                'data' => [
                    'qrcode_image' => $this -> ossWatermarkQRCode('fenithcdn', $ossQrcodeName, $ossLogoName)
                ]
            ];
        }
    }

    /**
     * @description 将小程序太阳码中间的logo更换成用户的头像
     * @param $bucket 存储空间名称
     * @param $endpoint 存储空间所在地域的访问域名
     * @param $objectName 图片文件名称（在oss上的路径）
     * @param $logo 小程序太阳码中间的logo图片（在oss上的路径）
     * @return 返回一个带店铺logo图片的小程序太阳码图片的 绝对路径
     */
    public function ossWatermarkQRCode(string $bucket, string $objectName, string $logo, string $endpoint = 'oss-cn-shanghai.aliyuncs.com') :string
    {
        // 白底图片base64编码，防止logo是png图片
        $blankBase64 = base64_encode('upload/blank.png?x-oss-process=image/resize,m_fixed,w_140,h_140,limit_0/circle,r_70');
        $urlSafeBase64Blank = str_replace(array('+','/','='), array('-','_',''), $blankBase64);
        // logo base64编码
        $base64Image = base64_encode($logo . '?x-oss-process=image/' . 'resize,w_140,h_140/circle,r_70');
        $urlSafeBase64Image = str_replace(array('+','/','='), array('-','_',''), $base64Image);

        return "https://{$bucket}.{$endpoint}/{$objectName}?x-oss-process=image/resize,w_300/watermark,image_{$urlSafeBase64Blank},g_center/watermark,image_{$urlSafeBase64Image},g_center";
    }
}
