<?php

namespace App\Http\Controllers\Api;

use App\Account;
use App\Configure;
use App\StockOrder;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Repositories\MathRepository;

class PayOrderController extends Controller
{
    public function __construct(MathRepository $mathRepository)
    {
        $this->mathRepository = $mathRepository;
    }
    //销售订单收款列表
    public function theList(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id

        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }

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

        $orders = DB::table('erp_stock_order as order')
            -> leftJoin('wxuser','order.user_id','wxuser.id')
            -> leftJoin('erp_warehouse','order.warehouse_id','erp_warehouse.id')
            -> leftJoin('users','order.sale_user_id','users.id')
            -> select([
                'order.*',
                'erp_warehouse.name as erp_warehouse_name',
                'users.name as sale_user_name',
                'wxuser.nickname'
            ])
            -> where([
                'order.business_id' => $request -> business_id,
                'order.flag' => 0,
            ])
            -> where(function($query)use($is_kefu_roles,$user){
                if($is_kefu_roles){
                    $query -> where('order.operator_user_id',$user -> id);
                }
            })
            -> where(function($query)use($request){
                //付款状态 1已付款 0未付款
                if($request -> pay_status != null){
                    $query -> where('order.pay_status',$request -> pay_status);
                }


                if($request -> order_num){
                    $query -> where('order.order_num','like','%'.trim($request -> order_num).'%');
                }

                if($request -> sale_date_left){
                    $query -> where('order.sale_date','>=',strtotime($request -> sale_date_left));
                }
                if($request -> sale_date_right){
                    $query -> where('order.sale_date','<=',strtotime($request -> sale_date_right));
                }

                if($request -> user_id){
                    $query -> where('order.user_id',$request -> user_id);
                }

                if(isset($request -> order_status)){
                    if($request -> order_status == 1){
                        //发货状态 0未发货 1已发货 2发货中
                        //前端字段 0未发货 1发货中 2已发货
                        $query -> where('order.send_status',2);
                    }elseif($request -> order_status == 2){
                        $query -> where('order.send_status',1);
                    }else{
                        $query -> where('order.send_status',0);
                    }
                }


                //销售员
                if($request -> sale_user_id){
                    $query -> where('order.sale_user_id',$request -> sale_user_id);
                }






            })
            -> orderBy('order.id','desc')
            -> paginate(isset($request -> per_page)?$request -> per_page:20);

        //发货状态 0未发货 1已发货 2发货中
        $send_status_config = [
            0 => '未发货',
            1 => '已发货',
            2 => '发货中'
        ];
        //付款状态
        $pay_status_config = [
            0 => '未付款',
            1 => '已付款'
        ];

        foreach($orders as $k => $vo){
            $orders[$k] -> sale_date = date('Y-m-d',$vo -> sale_date);
            $orders[$k] -> user_name = $vo -> nickname;
            $orders[$k] -> instead_price = $vo -> substitute;
            $orders[$k] -> goods_price = $vo -> price;
            $orders[$k] -> order_status_str = $send_status_config[$vo -> send_status];
            $orders[$k] -> pay_status_str = $pay_status_config[$vo -> pay_status];
            //总金额 = 运费 + 代发费 + 销售总价

            $orders[$k] -> totalprice = $this->mathRepository->math_add($this->mathRepository->math_add($vo -> freight, $vo -> substitute), $vo -> price);
        }


        return $orders;




    }


    //销售订单收款详情
    public function orderInfo(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
            'id' => 'required'

        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }

        $order = DB::table('erp_stock_order')
            -> where([
                'business_id' => $request -> business_id,
                'id' => $request -> id,
                'flag' => 0
            ]) -> first();
        if(!$order){
            return [
                'code' => 500,
                'msg' => '没有此数据'
            ];
        }


        $order = DB::table('erp_stock_order as order')
            -> leftJoin('wxuser','order.user_id','wxuser.id')
            -> leftJoin('erp_warehouse','order.warehouse_id','erp_warehouse.id')
            -> leftJoin('users','order.sale_user_id','users.id')
            -> select([
                'order.*',
                'erp_warehouse.name as erp_warehouse_name',
                'users.name as sale_user_name',
                'wxuser.nickname'
            ])
            -> where([
                'order.id' => $request -> id,
                'order.flag' => 0,
            ]) -> first();

        //总金额
        $order -> total_price = floatval($order -> price) + floatval($order -> freight) + floatval($order -> substitute);



        //销售订单详情
        $order_info = DB::table('erp_stock_order_info')
            -> leftJoin('erp_product_list','erp_stock_order_info.product_id','erp_product_list.id')
            //商品去找类别、品牌、系列
            //类别
            -> leftJoin('erp_product_class as product_class','erp_product_list.class_id','product_class.id')
            //品牌
            -> leftJoin('erp_product_class as product_brand','erp_product_list.brand_id','product_brand.id')
            //系列
            -> leftJoin('erp_product_class as product_series','erp_product_list.series_id','product_series.id')

            -> select([
                'erp_stock_order_info.*',
                'erp_product_list.product_name',
                'erp_product_list.model',
                'erp_product_list.product_no',
                'erp_product_list.number as product_num',
                'product_class.name as product_class_name',
                'product_brand.name as product_brand_name',
                'product_series.name as product_series_name',
            ])

            -> where([
                'erp_stock_order_info.stock_order_id' => $request -> id,
                'erp_stock_order_info.flag' => 0
            ]) -> get();





        //收款账户
        //查找此用户所在事业部的本位币账户
        $user = auth('api')->user();
        $business_infos = DB::table('user_has_business')
            -> where([
                'user_id' => $user -> id
            ]) -> get();
        $business_ids = [];
        foreach($business_infos as $vo){
            $business_ids[] = $vo -> business_id;
        }

        $accounts = DB::table('erp_account')
            -> whereIn('business_id',$business_ids)
            -> where([
                'flag' => 0
            ]) -> get();
        $currency_config = Configure::getCurrency();
        foreach($accounts as $k => $vo){
            $accounts[$k] -> currency_str = $currency_config[$vo -> currency];
        }

        $order_temp = [
            'order' => $order,
            'order_info' => $order_info,
            'accounts' => $accounts
        ];
        return $order_temp;

    }


    //库存销售订单 付款
    public function payOrder(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
            'id' => 'required|numeric',
            'pay_price' => 'required|numeric', //收款金额
            'service_charge' => 'required|numeric', //入账手续费
            'cert_num' => 'required', //交易凭证号
            'account_id' => 'required|numeric',

        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }


        $order = DB::table('erp_stock_order')
            -> where([
                'business_id' => $request -> business_id,
                'id' => $request -> id,
                'flag' => 0
            ]) -> first();
        if(!$order){
            return [
                'code' => 500,
                'msg' => '没有此数据'
            ];
        }

        //比较收款金额  和  订单的未付金额
        if( (floatval($request -> pay_price) - floatval($request -> service_charge)) > (floatval($order -> price) + floatval($order -> freight) + floatval($order -> substitute) - floatval($order -> pay_price)) ){
            return [
                'code' => 500,
                'msg' => '付款金额怎么能大于剩余未付金额呢'
            ];
        }

        $account = DB::table('erp_account') -> where([
            'id' => $request -> account_id,
            'flag' => 0
        ]) -> first();

        if(!$account){
            return [
                'code' => 500,
                'msg' => '没有此收款账户'
            ];
        }

        $user = auth('api')->user();



        //如果是余额支付 则判断下余额够不够
        if($request -> pay_method){
            //判断这个人的余额
            $user_id = $order ->user_id;
            $user_info = DB::table('wxuser') -> where([
                'id' => $user_id
            ]) -> first();
            if($user_info){
                //比较余额
                if(!$user_info -> price){
                    return [
                        'code' => 500,
                        'msg' => '此人没有余额'
                    ];
                }

                //如果余额比要支付的金额小 则支付金额变成余额
                if(floatval($request -> pay_price) > floatval($user_info -> price)){
                    $request -> pay_price = $user_info -> price;
                }


            }else{
                return [
                    'code' => 500,
                    'msg' => '此人没有余额'
                ];
            }
            $pay_method = 2;
        }else{
            $pay_method = 0;
        }

        Log::info('sssss');
        Log::info($pay_method);
        $arr = [
            [
                'id' => $request -> id,
                'pay_price' => $request -> pay_price,
                'service_charge' => $request -> service_charge?$request -> service_charge:0,
                'cert_num' => $request -> cert_num,
                'remark' => $request -> remark
            ]
        ];
        $model = new StockOrder();
        $res =  $model -> payOrder($arr,$user->id,
            $request->account_id,
            $request->business_id,
            '7',
            $pay_method);
        return $res;


    }

    //批量收款弹出框信息
    public function batchPayInfo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
            'ids' => 'required|array'
        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }
        //校验
        $order_count=DB::table('erp_stock_order')
            ->where([
                'business_id' => $request -> business_id,
                'flag' => 0
            ])
            ->whereIn('id',$request->ids)
            ->count();
        if (count($request->ids) != $order_count){
            return [
                'code' => 500,
                'msg' => '订单数据缺失'
            ];
        }

        $pay_orders=DB::table('erp_stock_order')
            ->where([
                'business_id' => $request -> business_id,
                'flag' => 0
            ])
            ->whereIn('id',$request->ids)
            ->whereNotNull('pay_price')
            ->where('pay_price','>',0)
            ->pluck('order_num')->toArray();
        if (!empty($pay_orders)){
            return [
                'code' => 500,
                'msg' => '订单'.implode(',',$pay_orders).'已经收过款'
            ];
        }
        //订单总费用
        $total=DB::table('erp_stock_order')
            ->where([
                'business_id' => $request -> business_id,
                'flag' => 0
            ])
            ->whereIn('id',$request->ids)
            ->selectRaw('sum(freight) as total_freight,sum(substitute) as total_substitute,sum(price) as total_price')
            ->get();

        return [
            'code' => 200,
            'total_price' => $total->sum('total_freight') + $total->sum('total_substitute') + $total->sum('total_price')
        ];
    }

    //批量收款
    public function batchPayOrder(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
            'ids' => 'required|array',
            'account_id' => 'required|numeric',
            'pay_method' => 'required|numeric',

        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }

        //校验
        $order_count=DB::table('erp_stock_order')
            ->where([
                'business_id' => $request -> business_id,
                'flag' => 0
            ])
            ->whereIn('id',$request->ids)
            ->count();
        if (count($request->ids) != $order_count){
            return [
                'code' => 500,
                'msg' => '订单数据缺失'
            ];
        }

        $pay_orders=DB::table('erp_stock_order')
            ->where([
                'business_id' => $request -> business_id,
                'flag' => 0
            ])
            ->whereIn('id',$request->ids)
            ->whereNotNull('pay_price')
            ->where('pay_price','>',0)
            ->pluck('order_num')->toArray();
        if (!empty($pay_orders)){
            return [
                'code' => 500,
                'msg' => '订单'.implode(',',$pay_orders).'已经收过款'
            ];
        }

        $account = DB::table('erp_account') -> where([
            'id' => $request -> account_id,
            'flag' => 0
        ]) -> first();

        if(!$account){
            return [
                'code' => 500,
                'msg' => '没有此收款账户'
            ];
        }

        $user = auth('api')->user();



        //如果是余额支付 则判断下余额够不够
        if($request -> pay_method){
            $order_users=DB::table('erp_stock_order')
                ->where([
                    'business_id' => $request -> business_id,
                    'flag' => 0
                ])
                ->whereIn('id',$request->ids)
                ->groupBy('user_id')
                ->selectRaw('user_id,sum(freight)+sum(substitute)+sum(price) as total')
                ->pluck('total','user_id');

            //判断每个人的余额
            foreach ($order_users as $user_id=>$price){
                $user_info = DB::table('wxuser') -> where([
                    'id' => $user_id
                ]) -> first();
                if($user_info){
                    //比较余额
                    if(!$user_info -> price){
                        return [
                            'code' => 500,
                            'msg' => '客户:'.$user_info->nickname.'没有余额'
                        ];
                    }

                    //如果余额比要支付的金额小
                    if(floatval($price) > floatval($user_info -> price)){
                        return [
                            'code' => 500,
                            'msg' => '客户:'.$user_info->nickname.'余额不足'
                        ];
                    }
                }else{
                    return [
                        'code' => 500,
                        'msg' => '客户不存在'
                    ];
                }
            }

            $pay_method = 2;
        }else{
            $pay_method = 0;
        }

        $orders=DB::table('erp_stock_order')
            ->where([
                'business_id' => $request -> business_id,
                'flag' => 0
            ])
            ->whereIn('id',$request->ids)
            ->select([
                'id','freight','substitute','price'
            ])
            ->get();

        $arr=[];
        foreach ($orders as $order){
            $arr[]=[
                'id' => $order->id,
                'pay_price'=>$order->freight + $order->substitute + $order->price,
                'service_charge' => 0,
                'cert_num' => '',
                'remark' => ''
            ];
        }

        $model = new StockOrder();
        $res =  $model -> payOrder($arr,$user->id,
            $request->account_id,
            $request->business_id,
            '7',
            $pay_method);
        return $res;


    }


    //库存销售订单收款历史
    public function payHistory(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
            'id' => 'required|numeric', //库存销售订单id

        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }

        $list = DB::table('erp_stock_order_pay')
            -> leftJoin('users','users.id','erp_stock_order_pay.pay_user_id')
            -> leftJoin('erp_account','erp_account.id','erp_stock_order_pay.account_id')
            -> where([
                'erp_stock_order_pay.order_id' => $request -> id,
                'erp_stock_order_pay.flag' => 0
            ])
            -> select([
                'erp_account.account_name',
                'users.name',
                'erp_stock_order_pay.*',
                'erp_stock_order_pay.price as pay_price',
            ])
            -> get();
        return $list;

    }


    //库存销售 撤销付款
    public function cancelPayOrder(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
            'stock_order_id' => 'required'

        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }





        $stock_order = DB::table('erp_stock_order') -> where([
            'business_id' => $request -> business_id,
            'id' => $request -> stock_order_id,
            'flag' => 0
        ]) -> first();
        if(!$stock_order){
            return [
                'code' => 500,
                'msg' => '没有此订单'
            ];
        }


        //看付过款没有
        if(!$stock_order -> pay_price){
            return [
                'code' => 500,
                'msg' => '此订单没有付款'
            ];
        }




        //原路撤销付款

        $user = auth('api')->user();

        $model = new StockOrder();
        //撤销付款
        return $model -> cancelPayOrder($request -> stock_order_id,$user -> id,$request -> business_id,$request -> erp_stock_order_pay_id);


    }



}
