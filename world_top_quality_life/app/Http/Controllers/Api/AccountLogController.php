<?php

namespace App\Http\Controllers\Api;

use App\Configure;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AccountLogController extends Controller
{
    //
    public function theList(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }

        //显示全部的记录
        $logs = DB::table('erp_account_log')
            -> leftJoin('erp_account','erp_account_log.account_id','erp_account.id')
            -> leftJoin('users','erp_account_log.user_id','users.id')
            -> where([
                'erp_account_log.business_id' => $request -> business_id
            ])
            -> where(function($query)use($request){
                if($request -> account_name){
                    $query -> where('erp_account_log.account_id',$request -> account_name);
                }
                if($request -> deal_date_left){
                    $query -> where('erp_account_log.created_at','>=',strtotime($request -> deal_date_left));
                }
                if($request -> deal_date_right){
                    $query -> where('erp_account_log.created_at','<=',strtotime($request -> deal_date_right));
                }
                if($request -> dealtype){
                    $query -> where('erp_account_log.log_type',$request -> dealtype);
                }


            })
            -> orderBy('erp_account_log.id','desc')
            -> select([
                'erp_account_log.*',
                'erp_account.currency',
                'erp_account.account_name',
                'users.name as dealmen',
                'erp_account_log.price as dealnum',
            ])

            -> paginate(isset($request -> per_page)?$request -> per_page:20);


        $currency_config = Configure::getCurrency();
        $log_type_config = Configure::accountType();
        foreach($logs as $k => $vo){
            $logs[$k] -> currency_str = $currency_config[$vo -> currency];
            $log_type = $log_type_config[$vo -> log_type];
            if($log_type['plus_minus'] == 1){
                $logs[$k] -> is_add = 1;
            }else{
                $logs[$k] -> is_add = 0;
            }

            $logs[$k] -> dealtime = date('Y-m-d H:i',$vo -> created_at);
            $logs[$k] -> dealtype = $log_type['name'];




        }

        return $logs;

    }



    public function payType(Request $request){
        $log_type_config = Configure::accountType();
        $temp = [];
        foreach($log_type_config as $k => $vo){
            $temp[$k]['name'] = $vo['name'];
            $temp[$k]['value'] = $k;
        }



        return array_values($temp);
    }



    public function payDetail(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
            'payLog_id' => 'required|numeric'
        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }


        $log_info = DB::table('erp_account_log')
            -> where([
                'id' => $request -> payLog_id
            ])
            -> first();

        switch ($log_info -> log_type){
            case '1': //账户充值
                $log = DB::table('erp_account_log')
                    -> leftJoin('erp_account','erp_account_log.account_id','erp_account.id')
                    -> leftJoin('users','erp_account_log.user_id','users.id')
                    -> leftJoin('erp_account_recharge_record','erp_account_recharge_record.id','erp_account_log.parameter_id')



                    -> where([
                        'erp_account_log.id' => $request -> payLog_id,
                        'erp_account_log.flag' => 0
                    ])
                    -> select([
                        'erp_account_log.*',
                        'erp_account.currency',
                        'erp_account.account_name',
                        'users.name as user_name',
                        'erp_account_recharge_record.service_charge',
                        'erp_account_recharge_record.rate',
                        'erp_account_recharge_record.remark',
                    ])
                    -> first();

                $temp = [
                    '充值账户' => $log -> account_name,
                    '充值人员' => $log -> user_name,
                    '充值时间' => date('Y-m-d H:i',$log -> created_at),
                    '充值金额' => $log -> price,
                    '充值手续费' => $log -> service_charge,
                    '充值汇率' => $log -> rate,
                    '充值金额折本位币' => round(floatval($log -> price) * floatval($log -> rate),2),
                    '充值备注' => $log -> remark
                ];


                break;
            case '2'://订单付款
                $log = DB::table('erp_account_log')
                    -> leftJoin('erp_account','erp_account_log.account_id','erp_account.id')
                    -> leftJoin('users','erp_account_log.user_id','users.id')
                    -> leftJoin('erp_purchase_order_pay','erp_purchase_order_pay.id','erp_account_log.parameter_id')
                    -> leftJoin('erp_purchase_order','erp_purchase_order_pay.order_id','erp_purchase_order.id')
                    -> where([
                        'erp_account_log.id' => $request -> payLog_id,
                        'erp_purchase_order_pay.pay_type' => 0,
                        'erp_account_log.flag' => 0
                    ])
                    -> select([
                        'erp_account_log.*',
                        'erp_account.currency',
                        'erp_account.account_name',
                        'users.name as user_name',
                        'erp_purchase_order.order_num as erp_purchase_order_order_num',
                        'erp_purchase_order_pay.service_charge',
                        'erp_purchase_order_pay.remark'
                    ])
                    -> first();
                $temp = [
                    '付款账户' => $log -> account_name,
                    '订单编号' => $log -> erp_purchase_order_order_num,
                    '付款人员' => $log -> user_name,
                    '付款时间' => date('Y-m-d H:i',$log -> created_at),
                    '付款金额' => $log -> price,
                    '充值手续费' => $log -> service_charge,
                    '付款备注' => $log -> remark
                ];
                break;
            case '3'://运费付款
                $log = DB::table('erp_account_log')
                    -> leftJoin('erp_account','erp_account_log.account_id','erp_account.id')
                    -> leftJoin('users','erp_account_log.user_id','users.id')
                    -> leftJoin('erp_purchase_order_pay','erp_purchase_order_pay.id','erp_account_log.parameter_id')
                    -> leftJoin('erp_logistics','erp_purchase_order_pay.order_id','erp_logistics.id')
                    -> where([
                        'erp_account_log.id' => $request -> payLog_id,
                        'erp_purchase_order_pay.pay_type' => 1,
                        'erp_account_log.flag' => 0
                    ])
                    -> select([
                        'erp_account_log.*',
                        'erp_account.currency',
                        'erp_account.account_name',
                        'users.name as user_name',
                        'erp_logistics.logistics_num as logistics_num',
                        'erp_purchase_order_pay.service_charge',
                        'erp_purchase_order_pay.remark'
                    ])
                    -> first();
                $temp = [
                    '付款账户' => $log -> account_name,
                    '运单编号' => $log -> logistics_num,
                    '付款人员' => $log -> user_name,
                    '付款时间' => date('Y-m-d H:i',$log -> created_at),
                    '付款金额' => $log -> price,
                    '充值手续费' => $log -> service_charge,
                    '付款备注' => $log -> remark
                ];
                break;



        }

        $return = [];
        $i = 0;
        foreach($temp as $k => $vo){
            $return[$i]['name'] = $k;
            $return[$i]['value'] = $vo;
            $i++;
        }

        return $return;




    }


}
