<?php

namespace App\Http\Controllers\Api;

use App\Account;
use App\Configure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AccountController extends Controller
{
    //列表
    public function theList(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
            // 'currency' => 根据币种筛选
        ]);

        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        //账户名称
        if($request -> account_name){
            $where[] = [
                'account_name','like','%'.trim($request -> account_name).'%'
            ];
        }
        //币种
        if($request -> currency){
            $where[] = [
                'currency','=',$request -> currency
            ];
        }
        if($request -> account_name){
            $where[] = [
                'account_name','like','%'.$request -> account_name.'%'
            ];
        }

        $where[] = [
            'business_id','=',$request -> business_id
        ];
        $where[] = [
            'flag','=',0
        ];

        $list = Account::where($where) -> get();

        $currency = Configure::getCurrency();
        foreach($list as $k => $vo){
            //翻译币种
            $list[$k] -> currency_name = $currency[$vo -> currency];
        }
        return $list;
    }

    public function add(Request $request){
        $validator = Validator::make($request->all(), [
            'account_name' => 'required|max:50', //账户名称
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
            'describe' => 'max:200', //描述
            'currency' => 'required|numeric', //币种
            'zhifubao_account' => 'max:128',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        //检验名称重复
        $info = Account::where([
            'business_id' => $request -> business_id,
            'flag' => 0,
            'account_name' => trim($request -> account_name)
        ]) -> first();
        if($info){
            return [
                'code' => 500,
                'msg' => '账户名称重复'
            ];
        }


        Account::insert([
            'account_name' => trim($request -> account_name),
            'business_id' => $request -> business_id,
            'describe' => $request -> describe,
            'currency' => $request -> currency,
            'zhifubao_account' => $request -> zhifubao_account,
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        return [
            'code' => 200,
            'msg' => '添加成功'
        ];





    }

    public function info(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
            'id' => 'required|numeric|exists:erp_account,id',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }


        $info = Account::where([
            'business_id' => $request -> business_id,
            'flag' => 0,
            'id' => trim($request -> id)
        ]) -> first();

        if(!$info){
            return [
                'code' => 500,
                'msg' => '无数据'
            ];
        }

        $currency = Configure::getCurrency();
        $info -> currency_name = $currency[$info -> currency];




        return $info;

    }

    public function edit(Request $request){
        $validator = Validator::make($request->all(), [
            'account_name' => 'required|max:50', //账户名称
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
            'describe' => 'max:200', //描述
            //'currency' => 'required|numeric', //币种
            'zhifubao_account' => 'max:128',
            'id' => 'required|numeric|exists:erp_account,id',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        //检验名称重复
        $info = Account::where([
            'business_id' => $request -> business_id,
            'flag' => 0,
            'account_name' => trim($request -> account_name)
        ]) -> where('id','<>',$request -> id) ->  first();
        if($info){
            return [
                'code' => 500,
                'msg' => '账户名称重复'
            ];
        }

        Account::where([
            'id' => $request -> id
        ]) -> update([
            'account_name' => trim($request -> account_name),
            'describe' => trim($request -> describe),
            //'currency' => trim($request -> currency),
            'zhifubao_account' => trim($request -> zhifubao_account),
            'updated_at' => time()
        ]);

        return [
            'code' => 200,
            'msg' => '修改成功'
        ];



    }

    public function delete(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
            'id' => 'required|numeric|exists:erp_account,id',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }




        //看下此账户 有没有付款记录
        $pay_info = DB::table('erp_purchase_order_pay')
            -> where([
                'account_id' => $request -> id,
                'flag' => 0
            ])-> first();
        if($pay_info){
            return [
                'code' => 500,
                'msg' => '已存在付款记录，不可删除'
            ];
        }




        DB::beginTransaction();
        try{
            Account::where([
                'id' => $request -> id
            ]) -> update([
                'flag' => 1,
                'updated_at' => time()
            ]);

            DB::table('erp_account_recharge_record')
                -> where([
                    'account_id' => $request -> id
                ]) -> update([
                    'flag' => 1
                ]);
            DB::commit();
            return [
                'code' => 200,
                'msg' => '删除成功'
            ];

        }catch (\Exception $exception){
            DB::rollBack();
            return [
                'code' => 500,
                'msg' => $exception ->getTraceAsString()
            ];
        }





    }

    //账户充值记录
    public function rechargeRecord(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
            'id' => 'required|numeric|exists:erp_account,id', //账户id account_id
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $record = DB::table('erp_account_recharge_record')
            -> leftJoin('users','erp_account_recharge_record.create_user_id','users.id')
            -> where([
                'erp_account_recharge_record.flag' => 0,
                'erp_account_recharge_record.account_id' => $request -> id
            ])
            -> select([
                'erp_account_recharge_record.*',
                'users.name as recharge_user_name' //充值人员
            ])
            -> get();
        return $record;

    }


    //账户充值
    public function recharge(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
            'id' => 'required|numeric|exists:erp_account,id', //账户id account_id
            'recharge_num' => 'required|numeric', //充值金额
            'service_charge' => 'numeric', //手续费
            'rate' => 'required|numeric',
            'remark' => 'max:200',

        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }



        //判断账户存在否
        $info = Account::where([
            'business_id' => $request -> business_id,
            'flag' => 0,
            'id' => trim($request -> id)
        ]) -> first();
        if(!$info){
            return [
                'code' => 500,
                'msg' => '无数据'
            ];
        }

        DB::beginTransaction();
        try{
            $rate = $request -> rate?$request -> rate:1;
            $base_currency = (floatval($request -> recharge_num) - floatval($request -> service_charge));

            $user = auth('api')->user();
            $recharge_record_id = DB::table('erp_account_recharge_record') -> insertGetId([
                'account_id' => $request -> id,
                'recharge_num' => $request -> recharge_num,
                'service_charge' => $request -> service_charge,
                'rate' => $rate,
                'base_currency' => $base_currency,
                'price' => $base_currency,
                'remark' => $request -> remark,
                'created_at' => time(),
                'updated_at' => time(),
                'create_user_id' => $user -> id
            ]);

            Account::addLog([
                'business_id' => $request -> business_id,
                'account_id' => $request -> id,
                'user_id' => $user -> id,
                'log_type' => 1,
                'price' => $base_currency,
                'parameter_id' => $recharge_record_id
            ]);



            //账户余额 + base_currency
            DB::table('erp_account') -> where([
                'id' => $request -> id,
            ]) -> increment('balance',$base_currency);

            DB::commit();
            return [
                'code' => 200,
                'msg' => '充值成功'
            ];
        }catch (\Exception $exception){
            DB::rollBack();
            return [
                'code' => 500,
                'msg' => '充值失败'
            ];
        }

    }


    //撤销充值
    public function revokeRecharge(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
            'account_id' => 'required|numeric|exists:erp_account,id', //账户id account_id
            'record_id' => 'required|numeric|exists:erp_account_recharge_record,id'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $record = DB::table('erp_account_recharge_record')
            -> where([
                'flag' => 0,
                'id' => $request -> record_id,
                'account_id' => $request -> account_id
            ]) -> first();
        if(!$record){
            return [
                'code' => 500,
                'msg' => '没有这些数据'
            ];
        }

        if($record -> base_currency != $record -> price){
            return [
                'code' => 500,
                'msg' => '已经用这个账户支付过，不允许撤销'
            ];
        }

        DB::table('erp_account_recharge_record')
            -> where([
                'id' => $record -> id
            ]) -> update([
                'flag' => 1
            ]);

        //把账户的剩余余额 减去
        DB::table('erp_account') -> where([
            'id' =>$request -> account_id
        ]) -> decrement('balance',$record -> price);

        //记录log
        $user = auth('api')->user();

        Account::addLog([
            'business_id' => $request -> business_id,
            'account_id' => $request -> account_id,
            'user_id' => $user -> id,
            'log_type' => 9,
            'price' => $record -> price,
            'parameter_id' => $request -> record_id
        ]);

        return [
            'code' => 200,
            'msg' => '撤销成功'
        ];




    }

    //返回用户 拥有事业部的所有账户
    public function getAccount(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        $currency_config = Configure::getCurrency();
        //获取本位币账户
        $currency_this = 0;
        if($request -> is_base){
            $business_info = DB::table('erp_business')
                -> where([
                    'id' => $request -> business_id
                ]) -> first();
            $currency_this = $business_info -> currency;
        }




        $account = DB::table('erp_account')
            -> where([
                'erp_account.business_id' => $request -> business_id,
                'erp_account.flag' => 0
            ])
            -> where(function($query)use($currency_this,$request){
                if($currency_this){
                    $query -> where('currency',$currency_this);
                }

            })
            -> get();
        if($currency_this){
            foreach($account as $k => $vo){
                $account[$k] -> currency_str = $currency_config[$vo -> currency];
            }
            return $account;
        }
        $currencys = [];    // 数组定义
        if($request -> all_account){
            foreach($account as $k => $vo){
                $account[$k] -> currency_str = $currency_config[$vo -> currency];
            }

            foreach($account as $k => $vo){
                $currencys[$k]['name'] = $vo -> account_name;
                $currencys[$k]['value'] = $vo -> id;
            }

            return $currencys;
        }


        $currencys_temp = [];
        foreach($account as $k => $vo){
            if(!in_array($vo -> currency,$currencys)){
                $currencys_temp[$k]['name'] = $currency_config[$vo -> currency];
                $currencys_temp[$k]['value'] = $vo -> currency;
                $currencys[] = $vo -> currency;
            }
        }
        return $currencys_temp;
    }









}
