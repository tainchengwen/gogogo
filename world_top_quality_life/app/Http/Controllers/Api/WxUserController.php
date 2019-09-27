<?php

namespace App\Http\Controllers\Api;

use App\WxUser;
use EasyWeChat\Factory;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Validator;
use DB;
use Illuminate\Support\Facades\Log;

class WxUserController extends Controller
{
    //
    public function theList(Request $request){
        if(trim($request -> nickname)){
            $name = $request -> nickname;
        }else{
            $name = '';
        }
        if($request->type == 1){
            $users = DB::table('wxuser')
                ->leftJoin('users','users.id','wxuser.relate_admin_user')
                ->leftJoin('erp_stock_order',"erp_stock_order.user_id","wxuser.id")
                ->where(function($query)use($request,$name){
                    $query-> where('wxuser.nickname','like','%'.$name .'%')
                        -> orWhere('wxuser.erp_id','like','%'.$name .'%')
                        -> orWhere('wxuser.id',$name);

                })->where(function($query)use($request){

                    if($request->admin_id){
                        $query-> where('wxuser.relate_admin_user',$request->admin_id);
                    }
                    if($request->is_bind === 0){
                        $query->where("wxuser.relate_admin_user",0);
                    }elseif($request->is_bind === 1){
                        $query->where("wxuser.relate_admin_user",'>',0);
                    }
                    if($request->number_left){
                        $query->where('erp_stock_order.pay_status',1);
                    }
                    if($request->price_left){
                        $query->where('wxuser.price','>=',$request->price_left);
                    }
                    if($request->price_right){
                        $query->where('wxuser.price','<=',$request->price_right);
                    }
                })
                ->select([
                    'wxuser.id',
                    'wxuser.nickname',
                    'wxuser.erp_id',
                    'wxuser.price',
                    'users.name as adminname',
                    'wxuser.relate_admin_user',
                    DB::raw('count(erp_stock_order.id) as total')
                ])
                ->groupBy('wxuser.id')
                ->when($request->number_Left != '' ,function($query)use($request){
                    $query->havingRaw("IF({$request->number_left}>=0,count(erp_stock_order.id) >= '{$request->number_left}','1=1')");
                },function($query){

                })
                ->when($request->number_right !='',function($query)use($request){
                    $query ->havingRaw("IF({$request->number_right}>=0,count(erp_stock_order.id) <= '{$request->number_right}','1=1')");
                },function($query){

                })
                ->paginate(isset($request -> per_page)?$request -> per_page:20);
        }else{
            $users = DB::table('wxuser') ->where(function($query)use($request,$name){
                $query-> where('nickname','like','%'.$name .'%')
                    -> orWhere('erp_id','like','%'.$name .'%')
                    -> orWhere('id',$name);
            })->select([
                'id',
                'nickname',
                'erp_id',
                'price'
            ])->paginate(20);
        }

        foreach($users as $k => $vo){
            $users[$k] -> nickname = $vo -> id.'-'.$vo -> nickname.'-'.$vo -> erp_id;
        }
        return $users;
    }

    //客户绑定后台用户接口


    public function bindAdminUser(Request $request){
        $validator = Validator::make($request->all(), [
            'userId'      => 'required', // 用户id
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        $user =auth('api')->user();
        if(empty($request->userId))
            return [
                'code' => 1,
                'msg' => '参数错误'
            ];
        if(WxUser::whereIn('id',$request->userId)->update([
            'relate_admin_user' => $user->id
        ])){

            Log::info('绑定日志？绑定客户'.join(',',$request->userId).'管理员'.$user->id);
            return [
                'code' => 0,
                'msg' => '绑定成功'
            ];
        }else{
            return [
                'code' => 1,
                'msg' => '绑定失败'
            ];
        }

    }

    public function removeAdminUser(Request $request){
        $validator = Validator::make($request->all(), [
            'userId'      => 'required|numeric|exists:wxuser,id', // 用户id
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        $user =auth('api')->user();
        if(empty($request->userId))
            return [
                'code' => 1,
                'msg' => '参数错误'
            ];
        if(WxUser::where('id',$request->userId)->update([
            'relate_admin_user' => '-1'
        ])){

            Log::info('绑定日志？无效客户'.$request->userId.'管理员'.$user->id);
            return [
                'code' => 0,
                'msg' => '绑定成功'
            ];
        }else{
            return [
                'code' => 1,
                'msg' => '绑定失败'
            ];
        }

    }

    public function unbindAdminUser(Request $request){
        $validator = Validator::make($request->all(), [
            'userId'      => 'required|numeric|exists:wxuser,id', // 用户id
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        $user =auth('api')->user();
        $userInfo = WxUser::where('id' ,$request->userId)->first();
        if($userInfo->relate_admin_user && $userInfo->relate_admin_user  != -1 && ($userInfo->relate_admin_user == $user->id || $user->id==3)){
            if(WxUser::where([
                'id' => $request->userId,
            ])->update([
                'relate_admin_user' => 0
            ])){

                Log::info('绑定日志？绑定客户'.$request->userId.'管理员'.$user->id);
                return [
                    'code' => 0,
                    'msg' => '解绑成功'
                ];
            }else{
                return [
                    'code' => 1,
                    'msg' => '解绑失败'
                ];
            }
        }else{
            return [
                'code' => 1,
                'msg' => '解绑失败'
            ];
        }


    }

    public function nickname(Request $request)
    {
        if(!$request->master_id){
            return ['nickname'=>''];
        }
        $user=WxUser::find($request->master_id);
        return ['nickname'=>$user->nickname];
    }

    // 修改用户 vip 等级
    public function alterVipLevel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id', // 事业部id
            'userId'      => 'required|numeric|exists:wxuser,id', // 用户id
            'level'       => 'required|numeric|min:0|max:4', // vip等级
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        // 更新 vip 等级
        $updateResult = DB::table('wxuser')
        -> where('id', $request->userId)
        -> update([
            'market_class' => $request->level
        ]);

        if ($updateResult) { // $updateResult > 0 : 更新成功
            return [
                'code' => 0,
                'msg' => '修改成功'
            ];
        } else { // $updateResult > 0 : 更新失败
            return [
                'code' => 1,
                'msg' => '修改失败'
            ];
        }
    }

    //充值接口
    public  function recharge(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id'   => 'required|numeric|exists:wxuser,id', // 用户id
            'price'    => 'required|numeric', // 充值金额
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        DB::beginTransaction();
        $user = auth('api')->user();
        try{
            $userinfo =  WxUser::where([
                'id' => $request->user_id
            ]) -> first();
            WxUser::where([
                'id' => $request->user_id
            ]) ->update([
                'price' => bcadd($userinfo->price,$request->price,2)
            ]);
            //添加日志
            DB::table('price_log') -> insert([
                'userid' => $request->user_id,
                'price'  => $request->price,
                'type' => 0,
                'created_at' => time(),
                'updated_at' => time(),
                'from_user_id' =>$user->id,
                'in_out' => 0 ,// 0收入1支出
                'end_price' => bcadd($userinfo->price,$request->price,2)
            ]);
            //累计充值
            $price_sum = DB::table('price_log') -> where([
                'userid' =>$request->user_id,
                'type' => 0,
            ]) -> sum('price');

            if (!empty($userinfo -> openid)){
                $config = [
                    'app_id' => env('WECHAT_OFFICIAL_ACCOUNT_APPID'),
                    'secret' => env('WECHAT_OFFICIAL_ACCOUNT_SECRET'),
                ];
                $app = Factory::officialAccount($config);
                $res= $app->template_message->send([
                    'touser' => $userinfo -> openid,
                    'template_id' => 'wYyGvnA8UueXkkw6FogTD_Cf-M3AQ_XmIWnEfYTNNS0',
                    'url' => url('priceTable'),
                    'data' => [
                        'first' => '尊敬的会员，您已充值成功！',
                        'keyword1' => $request->price, //充值金额
                        'keyword2' => $request->price,
                        'keyword3' => date('Y-m-d H:i'),
                        'keyword4' =>$price_sum, // 累计充值
                        'remark' => '感谢您的使用'
                    ]
                ]);
            }

            DB::commit();
            return [
                'code' => 0,
                'msg' => '充值成功'
            ];
        } catch (\Exception $e){
            DB::rollback();
            return [
                'code' => 1,
                'msg' => '充值失败'
            ];
        }
    }


    //扣款接口
    public function consume(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id'   => 'required|numeric|exists:wxuser,id', // 用户id
            'price'    => 'required|numeric', // 扣款金额
        ]);
        $user = auth('api')->user();
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        DB::beginTransaction();
        try{
            $userinfo =  WxUser::where([
                'id' => $request->user_id
            ]) -> first();
            if($userinfo->price < $request->price){
                return [
                    'code' => 1,
                    'msg' => '余额不足'
                ];
            }
            WxUser::where([
                'id' => $request->user_id
            ]) ->update([
                'price' => bcsub($userinfo->price,$request->price,2)
            ]);

            //添加日志
            DB::table('price_log') -> insert([
                'userid' => $request->user_id,
                'price' => $request->price,
                'type' => 2,
                'created_at' => time(),
                'updated_at' => time(),
                'from_user_id' => $user -> id,
                'in_out' => 1 ,// 0收入1支出
                'end_price' => bcsub($userinfo->price,$request->price,2)
            ]);
            DB::commit();
            return [
                'code' => 0,
                'msg' => '扣款成功'
            ];
        } catch (\Exception $e){
            DB::rollback();
            return [
                'code' => 1,
                'msg' => '扣款失败'
            ];
        }
    }



    //用户充值消费记录
    public function priceLog(Request $request){

        $log = DB::table('price_log')
            ->leftJoin('wxuser','wxuser.id','price_log.userid')
            ->leftJoin('users','users.id','price_log.from_user_id')
            ->where(function($query) use($request){
                if($request->name){
                    $query->where('wxuser.nickname','like',$request->name);
                }
                if($request->type){
                    $query->where('price_log.type',$request->name);
                }
                if($request->user_id){
                    $query->where('wxuser.id',$request->user_id);
                }
                if($request->created_at){
                    $query->where(DB::raw('from_unixtime(price_log.created_at,"%Y-%m-%d")'),$request->created_at);
                }
            })
            ->select([
                'price_log.id',
                'price_log.type',
                'price_log.in_out',
                'price_log.order_id',
                'price_log.end_price',
                'price_log.price',
                'price_log.created_at',
                'wxuser.nickname',
                'users.name'
            ])
            ->orderBy('price_log.created_at','desc')
            ->paginate(50);
        $config = config('admin.price_log_type');
        foreach ($log->all() as $v){
            $v->typename   = $config[$v->type];
            $v->created_at = date('Y-m-d H:i',$v->created_at);
        }
        return $log;
    }

    // 将用户加入黑名单，不列入报表的统计
    public function addBlackList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id',
            'userId' => 'required|numeric|exists:wxuser,id'
        ]);

        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $update = DB::table('wxuser')
                    ->where('id', $request->userId)
                    ->update([ 'in_black_list' => 1 ]);
        return [
            'status' => 1,
            'msg' => '添加成功'
        ];
    }

    // 查找黑名单用户
    public function searchBlackListUsers(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $list = DB::table('wxuser')
                    ->where(function ($query) use ($request) {
                        if ($request->has('userName')) {
                            $query->where('nickname', 'like', '%'.$request->userName.'%');
                        }
                    })
                    ->where('in_black_list', '=', 1)
                    ->orderby('id')
                    ->paginate($request->has('per_page') ? $request->per_page : 20);

        return [
            'status' => 1,
            'data' => $list
        ];
    }

    public function removeBlackList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id',
            'userId' => 'required|numeric|exists:wxuser,id'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $remove = DB::table('wxuser')
                    ->where('id', '=', $request->userId)
                    ->update([ 'in_black_list' => 0 ]);

        return [
            'status' => 1,
            'msg' => '移除成功'
        ];
    }

    // 将用户加入白名单，不列入报表的统计
    public function addWhiteList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id',
            'userId' => 'required|numeric|exists:wxuser,id'
        ]);

        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $update = DB::table('wxuser')
            ->where('id', $request->userId)
            ->update([ 'in_white_list' => 1 ]);
        return [
            'status' => 1,
            'msg' => '添加成功'
        ];
    }

    // 查找黑名单用户
    public function searchWhiteListUsers(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $list = DB::table('wxuser')
            ->where(function ($query) use ($request) {
                if ($request->has('userName')) {
                    $query->where('nickname', 'like', '%'.$request->userName.'%');
                }
            })
            ->where('in_white_list', '=', 1)
            ->orderby('id')
            ->paginate($request->has('per_page') ? $request->per_page : 20);

        return [
            'status' => 1,
            'data' => $list
        ];
    }

    public function removeWhiteList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id',
            'userId' => 'required|numeric|exists:wxuser,id'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $remove = DB::table('wxuser')
            ->where('id', '=', $request->userId)
            ->update([ 'in_white_list' => 0 ]);

        return [
            'status' => 1,
            'msg' => '移除成功'
        ];
    }


    //获取后台用户关联下客户的订单数据   月份
    public function adminUserData(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        $adminUserList = DB::table('wxuser')
            ->leftJoin('user_has_business','user_has_business.user_id','wxuser.relate_admin_user')
            ->leftJoin('users','wxuser.relate_admin_user','users.id')
            ->where('wxuser.relate_admin_user','>',0)
            ->where('user_has_business.business_id',$request->business_id)
            ->where('wxuser.relate_admin_user','<>','3')
            ->groupBy('wxuser.relate_admin_user')
            ->select([
                'users.username',
                'users.name',
                'wxuser.relate_admin_user'
            ])
            ->get();
        $newdata = [];
        if (!empty($adminUserList)) {
            foreach($adminUserList as $k => $v) {
                $data = DB::table('erp_stock_order')
                    ->leftJoin('wxuser','wxuser.id','erp_stock_order.user_id')
                    ->where('wxuser.relate_admin_user',$v->relate_admin_user)
                    ->where('erp_stock_order.pay_status',1)
                    ->where(function($query)use($request){
                        if ($request->year) {
                            $query->where(DB::raw("from_unixtime(erp_stock_order.created_at,'%Y') "), $request->year);
                        }

                    })
                    ->select([
                        DB::raw('count(1) as num'),
                        DB::raw('sum(erp_stock_order.pay_price) as total'),
                        'erp_stock_order.user_id',
                        DB::raw("from_unixtime(erp_stock_order.created_at,'%m') as date"),
                        DB::raw("from_unixtime(erp_stock_order.created_at,'%U') as week")
                    ])
                    ->groupBy(DB::raw("from_unixtime(erp_stock_order.created_at,'%Y-%m')"))
                    ->get()->toArray();
                $data = array_column($data, null, 'date');

                $newdata[$k]['num_total'] = 0;
                $newdata[$k]['price_total'] = '0.00';
                for ($i=1; $i<=12; $i++) {
                    if ($i < 10) {
                        $newdata[$k]['num_'.$i] = isset($data['0'.$i]->num) && $data['0'.$i]->num ? $data['0'.$i]->num : 0;
                        $newdata[$k]['price_'.$i] = isset($data['0'.$i]->total) && $data['0'.$i]->total ? $data['0'.$i]->total : '0.00';
                    } else {
                        $newdata[$k]['num_'.$i] = isset($data[$i]->num) && $data[$i]->num ? $data[$i]->num : 0;
                        $newdata[$k]['price_'.$i] = isset($data[$i]->total) && $data[$i]->total ? $data[$i]->total : '0.00';
                    }
                    $newdata[$k]['num_total'] = isset($data['0'.$i]->num)
                        ? bcadd($data['0'.$i]->num, $newdata[$k]['num_total'], 0)
                        : $newdata[$k]['num_total'];
                    $newdata[$k]['price_total'] = isset($data['0'.$i]->total)
                        ? bcadd(floatval($newdata[$k]['price_total']), floatval($data['0'.$i]->total), 2)
                        : $newdata[$k]['price_total'];
                }
                $newdata[$k]['name'] = $v->name;
            }
        }
        return $newdata;

    }

    //获取后台用户关联下客户的订单数据   周份

    public function adminUserWeekData(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id',
            'dateRange' => 'required|array'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        $startTime = strtotime($request->dateRange[0]);
        $endTime = strtotime($request->dateRange[1]) + 84399;


        $adminUserList = DB::table('wxuser')
            ->leftJoin('user_has_business','user_has_business.user_id','wxuser.relate_admin_user')
            ->leftJoin('users','wxuser.relate_admin_user','users.id')
            ->where('wxuser.relate_admin_user','>',0)
            ->where('user_has_business.business_id',$request->business_id)
            ->where('wxuser.relate_admin_user','<>','3')
            ->groupBy('wxuser.relate_admin_user')
            ->select([
                'users.username',
                'users.name',
                'wxuser.relate_admin_user'
            ])
            ->get();
        $newdata = [];
        if(!empty($adminUserList)){
            foreach ($adminUserList as $k=>$v){
                $date1 = date('Ymd',$startTime);
                $data = DB::table('erp_stock_order')
                    ->leftJoin('wxuser','wxuser.id','erp_stock_order.user_id')
                    ->where('wxuser.relate_admin_user',$v->relate_admin_user)
                    ->where('erp_stock_order.pay_status',1)
                    ->where(function($query)use($request,$startTime,$endTime){
                        if($startTime && $endTime){
                            $query->whereBetween('erp_stock_order.created_at',[$startTime,$endTime]);
                        }

                    })
                    ->select([
                        DB::raw('count(1) as num'),
                        DB::raw('sum(erp_stock_order.pay_price) as total'),
                        'erp_stock_order.user_id',
                        DB::raw("from_unixtime(erp_stock_order.created_at,'%Y%m%d') as date"),
                        DB::raw("from_unixtime(erp_stock_order.created_at,'%U') as week")
                    ])
                    ->groupBy(DB::raw("from_unixtime(erp_stock_order.created_at,'%Y-%m-%d')"))
                    ->get()->toArray();
                $data = array_column($data,null,'date');

                $newdata[$k]['num_total'] = 0;
                $newdata[$k]['price_total'] = '0.00';
                for($i=1;$i<=7;$i++){
                    $newdata[$k]['num_'.$i] = isset($data[$date1]->num) &&$data[$date1]->num ?$data[$date1]->num : 0;
                    $newdata[$k]['price_'.$i] = isset($data[$date1]->total) &&$data[$date1]->total ?$data[$date1]->total : '0.00';
                    $newdata[$k]['num_total'] = isset($data[$date1]->num) ? $data[$date1]->num + $newdata[$k]['num_total'] : $newdata[$k]['num_total'];
                    $newdata[$k]['price_total'] = isset($data[$date1]->total)
                        ? bcadd(floatval($data[$date1]->total), floatval($newdata[$k]['price_total']), 2)
                        : $newdata[$k]['price_total'];
                    $date1++;
                }
                $newdata[$k]['name'] = $v->name;
            }
        }
        return $newdata;

    }
}
