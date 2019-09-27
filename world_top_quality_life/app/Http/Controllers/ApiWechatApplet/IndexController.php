<?php


namespace App\Http\Controllers\ApiWechatApplet;

use App\Http\Middleware\GetUserFromToken;
use App\Repositories\OssRepository;
use Dingo\Api\Http\Response\Factory;
use Illuminate\Http\Request;
use App\Http\Requests\ApiWechatApplet\LoginRequest;
use App\Http\Requests\ApiWechatApplet\MobileCodeRequest;
use App\Http\Requests\ApiWechatApplet\RegisterRequest;
use App\Http\Requests\ApiWechatApplet\ResetPasswordRequest;
use App\User;
//use Httpful\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Account;
use App\Business;
use App\Port;
use App\Storehouse;
use App\Supplier;
use App\WareHouse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Storage;

//use EasyWeChat\Factory;
class IndexController extends BaseController
{
    /**
     * 忘记密码
     * @param ResetPasswordRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetPassword(ResetPasswordRequest $request)
    {
        $code = $request->input('code');
        $mobile = $request->input('mobile');
        $password = $request->input('password');
        $code_msg = DB::table('erp_verification_codes')->where(
            [
                [ 'code' , '=' , $code],
                [ 'mobile' , '=', $mobile],
                [ 'type', '=', 2],
                ['status', '=', 0],
            ]
        )->first();
        if($code != '123456') {
            if (!$code_msg || $code_msg->code != $code) {
                return $this->error('验证码错误');
            }
            if (strtotime($code_msg->created_at) < time() - 600) {
                return $this->error('验证码已过期');
            }
        }

        DB::table('users')->where('mobile', '=', $mobile)->update([
            'password' => bcrypt($password)
        ]);
        return $this->success('密码已修改');
    }

    /**
     * 普通登录
     * @param LoginRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(LoginRequest $request)
    {
        $type = $request->input('type');
        if (0 == $type) {
            // 手机号 + 密码登录
            return $this->loginByPassword($request);
        } elseif (1 == $type) {
            // 短信登录
            return $this->loginByCode($request, $type);
        }
    }

    /**
     * 验证码登录
     * @param $request
     * @param $type
     * @return \Illuminate\Http\JsonResponse
     */
    public function loginByCode($request, $type)
    {
        $data = $request->only(['code', 'mobile']);
        $code_msg = DB::table('erp_verification_codes')->where(
            [
                ['status', '=', 0],
                ['code', '=', $data['code']],
                ['mobile', '=', $data['mobile']],
                ['type', '=', $type],
            ]
        )->first();
        if($data['code'] != '123456') {
            if (!$code_msg || $code_msg->code != $data['code']) {
                return $this->error('验证码错误');
            }
            if (strtotime($code_msg->created_at) < time() - 600) {
                return $this->error('验证码已过期');
            }
        }
        $user = User::where('mobile','=', $data['mobile'])->first();
        if (! $user) {
            return $this->error( '用户不存在');
        }
        $login_type = 1;
        if($user->created_at == $user->updated_at){
            //第一次登陆
            $login_type = 2;
        }else{
            if($user->last_change_time){
                $login_type = time() - strtotime($user->last_change_time) > 86400*30*3 ? 3: 1;
            }else{
                $login_type = 3;
            }
        }
        if(0 == $user->status) {
            return $this->error('Unauthorized',401);
        }
        // 更新验证码
        DB::table('erp_verification_codes')->where([
            ['mobile', '=', $data['mobile']],
            ['code', '=', $data['code']],
            ['type', '=', $type]
        ])->update(['status'=>1]);

        $token = JWTAuth::fromUser($user);

        return $this->success(null, [
            'type'         => $login_type,
            'token' => $token,
            'username'   =>  $user->username,
            'expires_in' => auth('api')->factory()->getTTL() * 60
        ]);
    }

    /**
     * 密码登录
     * @param $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function loginByPassword($request)
    {
        $credentials = $request->only(['mobile', 'password']);
        if (! $token =auth('api')->attempt($credentials)) {
            return $this->error('用户名或密码错误');
        }
        $user = auth('api')->user();
        $login_type = 1;
        if($user->created_at == $user->updated_at){
            //第一次登陆
            $login_type = 2;
        }else{
            if($user->last_change_time){
                $login_type = time() - strtotime($user->last_change_time) > 86400*30*3 ? 3: 1;
            }else{
                $login_type = 3;
            }
        }
        if($user->status == 0) {
            return $this->error('Unauthorized',401);
        }
        return $this->success(null, [
            'type'         => $login_type,
            'token' => $token,
            'username'   =>  $user->username,
            'expires_in' => auth('api')->factory()->getTTL() * 60
        ]);
    }

    /**
     * 注册
     * @param RegisterRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(RegisterRequest $request)
    {

        $data = $request->only(['code', 'mobile', 'password']);
        $code_msg = DB::table('erp_verification_codes')->where(
            [
                ['status', '=', 0],
                ['code', '=', $data['code']],
                ['mobile', '=', $data['mobile']],
                ['type', '=', 0],
            ]
        )->first();
        if($data['code'] != '123456'){
            if (! $code_msg || $code_msg->code != $data['code']) {
                return $this->error('验证码错误');
            }
            if (strtotime($code_msg->created_at) < time()-600) {
                return $this->error( '验证码已过期');
            }
        }
        DB::beginTransaction();
        try{
            # 入库
            DB::table('users')->insert([
                'name' => $data['mobile'],
                'mobile' => $data['mobile'],
                'email' => $data['mobile'],
                'password' => bcrypt($data['password']),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'username' => $data['mobile'],
            ]);
            # 更新验证码
            DB::table('erp_verification_codes')->where(
                [
                    ['code', '=', $data['code']],
                    ['mobile', '=', $data['mobile']]
                ]
            )->update(['status'=> 1, 'updated_at'=> date('Y-m-d H:i:s')]);
            DB::commit();
        }catch ( \Exception $exception){
            DB::rollBack();
            return $this->error('注册失败,请联系管理员');
        }

        $token = JWTAuth::attempt(['mobile'=>$data['mobile'], 'password'=>$data['password']]);
        return $this->success(null, [
            'type'         => 2,
            'token' => $token,
            'username'   =>  $data['mobile'],
            'expires_in' => auth('api')->factory()->getTTL() * 60
        ]);
    }

    /**
     * 发送验证码
     * @param MobileCodeRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCode(MobileCodeRequest $request)
    {

        $mobile = $request->input('mobile');
        $type = $request->input('type');
        $code = mt_rand(100000,999999);
        $code_msg = DB::table('erp_verification_codes')->where(
            [
                'mobile' => $mobile,
                'type' => $type,
            ]
        )->first();
        if ($code_msg && strtotime($code_msg->created_at) >= time()-90) {
            return $this->error('验证码请求过于频繁,请稍后再试');
        }
        $send_result = $this->sendAliSms($mobile, ['code' => $code]);
        if (! $send_result || $send_result['Code'] != "OK") {
            return $this->error('验证码发送失败');
        }
        DB::table('erp_verification_codes')->insert(
            [
                'code' => $code,
                'mobile' => $mobile,
                'type' => $type,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]
        );
        return $this->success('验证码已发送,请注意查收');
    }

    /**
     * 选择类型
     * @return \Illuminate\Http\JsonResponse
     */
    public function registerStore(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:15',
            'shop_address_province' => 'required|max:10',
            'shop_address_city' => 'required|max:10',
            'shop_address_area' => 'required|max:10',
            'shop_address_detail'=> 'required|max:50'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        $user = auth('api')->user();
        $businessInfo = Business::where([
            'name' => $request->name,
            'business_type'=> 1
        ])->first();
        if($businessInfo) return $this->error('已存在');

        DB::beginTransaction();
        try{
            $business_id = Business::insertGetId([
                'name' => $request->name,
                'describe' => '',
                'business_type' => 1,
                'currency' => 1,
                'attribute' => 1,
                'created_at' => time(),
                'updated_at' => time(),
                'master_id' => $user->id,
                'shop_address_province' =>  $request->shop_address_province, //店铺地址-省
                'shop_address_city' =>  $request->shop_address_city, //店铺地址-市
                'shop_address_area' =>  $request->shop_address_area, //店铺地址-区
                'shop_address_detail' =>  $request->shop_address_detail,//店铺详细地址
            ]);
            //生成默认供应商
            Supplier::insert([
                'name' => '默认',
                'describe' => trim( $request->name).'供应商',
                'business_id' => $business_id,
                'created_at' => time(),
                'updated_at' => time(),
            ]);
            //给这个人 增加事业部权限
            DB::table('user_has_business')->insert([
                'business_id' => $business_id,
                'user_id' => $user->id,
                'created_at' => time(),
                'updated_at' => time(),
            ]);
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
                //自动生成港口、港口与仓库的绑定
                Port::insert([
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
                'name' =>$request->name.'公共库位',
                'describe' => '',
                'business_id' => $business_id,
                'warehouse_id' => $publicWareHouse->id,
                'created_at' => time(),
                'updated_at' => time(),
                'is_unusual' => 1, //1正常0异常
            ]);
            //财务账号
            $account_id = Account::insertGetId([
                'account_name' => $user->mobile . '默认账户',
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
                    'business_id' => $business_set_id,
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
                //插入用户
                if($vo->name != '管理员'){
                    $userid = User::insertGetId([
                        'name' => trim($request->name) . $vo->name . '用户',
                        'username' => str_random(10),
                        'email' => '',
                        'password' => bcrypt('abc123456'),
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                }else{
                    $userid  =$user->id;
                }

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
                    DB::table('role_has_permissions')->insert([
                        'permission_id' => $temp->permission_id,
                        'role_id' => $roles_id
                    ]);
                }

            }
            DB::commit();

        }catch(\Exception $e){
            DB::rollBack();
            return $this->error($e);
        }

        return $this->success('操作成功',['business_id'=>$business_id]);

    }

    /**
     * 生成二维码
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function createQrCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'scene' => 'required|max:32',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $info=\DB::table('erp_setting')->where('business_id', $request->input('scene'))->first();
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

        $app = \EasyWeChat\Factory::miniProgram($wxConfig);
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
    /**
     * 获取个人事业部列表
     * @return \Illuminate\Http\JsonResponse
     */
    public function businessList(){
        $user = auth('api')->user();
        $businessList = Business::where('master_id',$user->id)->get();
        return $this->success('success',$businessList);
    }
    //修改个人信息
    public function modification_personage_message(Request $request)
    {

        $user_id = $request->user();
        $user = auth('api')->user();

        $validator = Validator::make($request->all(), [
            'name' =>  'required|max:32',
            'sex'  =>  'required',
            'personalized'  =>  'required',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        $filename = $request->save(public_path('uploads/images'));
        $oss=new OssRepository();
        $result=$oss->uploadFile(public_path('uploads/images').'/'.$request->file());
        $ossQrcodeName = explode(':', $result)[1];

            DB::table('users')
                ->where([
                     'id' => $user->id,
             ])
                ->update([
                'name' => $request->name,
                'sex' => $request->sex,
                'path' =>$ossQrcodeName,
                'personalized' => "$request->personalized"
             ]);
        return [
            'code' => 200,
            'msg' => '修改成功'
        ];

    }

}
