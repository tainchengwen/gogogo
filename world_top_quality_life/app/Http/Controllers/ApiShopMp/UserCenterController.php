<?php

namespace App\Http\Controllers\ApiShopMp;

use App\WxUser;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiShopMp\Controller;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Configure;
use App\Repositories\OrderRepository;

class UserCenterController extends Controller
{
    public function __construct( OrderRepository $orderRepository)
    {

         $this->orderRepository   = $orderRepository;
    }

    public function get(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id'   => 'required|numeric|exists:erp_business,id'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($request, [], '缺少或必要参数错误');
        }

        // 会员等级
        // $market_class_map = config('admin.market_class');
        // $current = $market_class_map[$request->user->market_class];

        //查询拥有的能正常使用的优惠券数量
        //更新优惠券过期状态
        event('coupons.checkInvalid',['user_id'=>$request->user->wxUserId]);
        $coupons_count=DB::table('erp_user_coupon_link')
            ->where('status',0)
            ->where('user_id','=',$request->user->wxUserId)
            ->count();

        return $this->successResponse($request, [
            'nickname' => $request->user->nickname,
            'headimg'  => $request->user->headimg,
            'balance'  => $this->formatFloat($request->user->price),
            'fandian'  => $this->formatFloat($request->user->fandian),
            'vipLevel' => $request->user->market_class,
            'coupons' => $coupons_count,
        ]);
    }


    //获取用户消费记录接口
    public function userPayLog(Request $request){
        $list = DB::table('price_log')
            ->where('userid',$request->user->wxUserId)
            ->where(function($query)use($request){
                if($request->month){
                    $query->where(DB::raw('from_unixtime(created_at,"%Y-%m")'),$request->month);
                }
            })
            ->orderBy('created_at','desc')
            ->paginate(20);
        $accountType = Configure::accountType();
        foreach($list as $k=>$v){
            $v->action = isset($accountType[$v->type])? $accountType[$v->type]['name'] : '账户充值';
            $v->time   = date('Y-m-d H:i:s',$v->created_at);
            if($v->order_id){
                $v->orderInfo = $this->orderRepository->getPayById($request, $v->order_id);
            }
        }
        //查询消费金额
        $output = DB::table('price_log')
            ->where([
                'userid'=>$request->user->wxUserId,
                'in_out'=>1
            ])
            ->where(function($query)use($request){
                if($request->month){
                    $query->where(DB::raw('from_unixtime(created_at,"%Y-%m")'),$request->month);
                }
            })->sum('price');
        //查询充值金额
        $input = DB::table('price_log')
            ->where([
                'userid'=>$request->user->wxUserId,
                'in_out'=>0
            ])
            ->where(function($query)use($request){
                if($request->month){
                    $query->where(DB::raw('from_unixtime(created_at,"%Y-%m")'),$request->month);
                }
            })->sum('price');

        return $this->successResponse($request, [
            'list' => $list,
            'output'=>$output,
            'input' =>$input
        ]);
    }


}
