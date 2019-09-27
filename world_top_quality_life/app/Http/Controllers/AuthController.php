<?php

namespace App\Http\Controllers;

use App\Configure;
use App\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use mysql_xdevapi\Exception;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Mail;
use iscms\Alisms\SendsmsPusher as Sms;

class AuthController extends Controller
{
    //
    /**
     * Create a new AuthController instance.
     * 要求附带email和password（数据来源users表）
     *
     * @return void
     */
    public function __construct(Sms $sms)
    {
        // 这里额外注意了：官方文档样例中只除外了『login』
        // 这样的结果是，token 只能在有效期以内进行刷新，过期无法刷新
        // 如果把 refresh 也放进去，token 即使过期但仍在刷新期以内也可刷新
        // 不过刷新一次作废
//        $this->middleware('auth:api', ['except' => ['login']]);
        // 另外关于上面的中间件，官方文档写的是『auth:api』
        // 但是我推荐用 『jwt.auth』，效果是一样的，但是有更加丰富的报错信息返回
        $this->sms=$sms;
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required',
            'password' => 'required',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->getMessageBag());
        }

        $credentials = request(['username', 'password']);

        if (! $token = auth('api')->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(auth('api')->user());
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth('api')->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     * 刷新token，如果开启黑名单，以前的token便会失效。
     * 值得注意的是用上面的getToken再获取一次Token并不算做刷新，两次获得的Token是并行的，即两个都可用。
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh(Request $request)
    {

        return $this->respondWithToken(auth('api')->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     * @param  int $type   1正常情况 2 第一次登陆 3 3个月未修改密码
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {

        $user = auth('api')->user();
        $type = 1;
        if($user->created_at == $user->updated_at){
            //第一次登陆
            $type = 2;
        }else{
            if($user->last_change_time){
                $type = time() - strtotime($user->last_change_time) > 86400*30*3 ? 3: 1;
            }else{
                $type = 3;
            }
        }
        if($user->status == 0)return response()->json(['error' => 'Unauthorized'], 401);
        return response()->json([
            'type'         => $type,
            'access_token' => $token,
            'token_type' => 'bearer',
            'username'   =>  $user->username,
            'expires_in' => auth('api')->factory()->getTTL() * 60
        ]);
    }

    public function register(Request $request)
    {

        $validator = $this->validator($request->all());

        if($validator->fails()){
            return ['code' => $validator -> errors()];
        }
        $user = $this->create($request->all());
        //$token = JWTAuth::fromUser($user);
        //return ["token" => $token];

        return [
            'code' => '200',
            'msg' => '成功'
        ];
    }


    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'email' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:6', //|confirmed'
            'mobile'   => 'regex:/^1[345789][0-9]{9}$/'
        ]);
    }


    protected function create(array $data)
    {
        return User::create([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'mobile'   => $data['mobile'],
        ]);
    }


    public function reset(Request $request){
        $user = auth('api')->user();
        $validator = Validator::make($request->all(), [
            'password'    => 'required|string|min:6',
            'newpassword' => 'required|string|min:6'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->getMessageBag());
        }
        $credentials = ['username'=>$user->username,'password'=>$request->password];
        if (!auth('api')->validate($credentials)) {
            return response()->json(['error' => '当前密码不正确'], 401);
        }
        if(User::where(['id'=> $user->id])->update([
            'password' => bcrypt($request->newpassword),
            'last_change_time' => date('Y-m-d H:i:s',time())
        ])){
            return [
                'code' => '0',
                'msg' => '成功'
            ];
        }else{
            return [
                'code' => '1',
                'msg' => '失败'
            ];
        }

    }
    //第一次设置密码
    public function firstSetPassword(Request $request){
        $user = auth('api')->user();
        $validator = Validator::make($request->all(), [
            'password'    => 'required|string|min:6',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->getMessageBag());
        }
        if(User::where(['id'=> $user->id])->update([
            'password' => bcrypt($request->password),
            'last_change_time' => date('Y-m-d H:i:s',time())
        ])){
            return [
                'code' => '0',
                'msg' => '成功'
            ];
        }else{
            return [
                'code' => '1',
                'msg' => '失败'
            ];
        }

    }

    //发邮件
    public function checkEmail(Request $request){
        $validator = Validator::make($request->all(), [
            'email' => 'required',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->getMessageBag());
        }
        if(!User::where(['email'=>$request->email])->first()){
            return [
                'code' => '1',
                'msg' => '该邮箱不存在'
            ];
        }else{
            try{
                $to = $request->email;
                $subject = '验证';
                $code = $this->yzmCode();
                Mail::send(
                    'email',
                    ['content' => $code],
                    function ($code) use($to, $subject) {
                        $code->to($to)->subject($subject);
                    }
                );
                if(count(Mail::failures()) > 0){
                    return [
                        'code' => '1',
                        'msg' => '邮件发送失败1'
                    ];
                }else{
                    //存储发送记录 用于校验
                    if(DB::table('verify_log')->insert([
                        'code'        => $code,
                        'yz_account'  => $request->email,
                        'created_at'  => time()
                    ])){
                        return [
                            'code' => '0',
                            'msg' => '邮件发送成功'
                        ];
                    }else{
                        return [
                            'code' => '1',
                            'msg' => '邮件发送失败3'
                        ];
                    }
                }
            }catch (\Exception $exception){
                \Illuminate\Support\Facades\Log::info($exception ->getTraceAsString());
                return [
                    'code' => '1',
                    'msg' => '邮件发送失败2'
                ];
            }
        }

    }


    //校验验证码
    public function formCode(Request $request){
        $validator = Validator::make($request->all(), [
            'type'     => 'required',    //验证方式  1 邮箱 2手机
            'account'  => 'required',    //验证邮箱或手机哈
            'code'     => 'required',    //验证码
            'password' => 'required|string|min:6',    //新密码
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->getMessageBag());
        }

        //检查时间内是否有记录
        $res = DB::table('verify_log')
            ->where([
                'code'        => $request->code,
                'yz_account'  => $request->account
            ])
            ->where('created_at','>',time()-600)
            ->first();
        if($res){
            $param = $request->type ==1? 'email' : 'mobile';
            if(User::where([$param => $request->account])->update([
                'password' => bcrypt($request->password)
            ])){
                return [
                    'code' => '0',
                    'msg' => '修改成功'
                ];
            }else{
                return [
                    'code' => '1',
                    'msg' => '密码与上一次一致'
                ];
            }


        }else{
            return [
                'code' => '1',
                'msg' => '验证码错误'
            ];

        }



    }






    //返回验证码
    private function yzmCode(){
        return rand(100000,999999);
    }


}
