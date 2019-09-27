<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class IntegralRepository  extends BaseRepository
{
    /**
     * 减少用户积分
     * @param $wxuser_id
     * @param $number
     * @param $type  减少积分的类型 4 订单金额抵扣 5 运费抵扣 6兑换商品抵扣 7 积分清理
     * @param string $order_id
     * @return array
     */
    public function decrIntegral($wxuser_id, $number, $type, $order_id='')
    {
        $haved =$this->getUserIntegral($wxuser_id);
        if (! $haved) {
            return $this->response('用户暂无积分', 400);
        }
        if ($haved < $number) {
            return $this->response('用户积分不足', 400);
        }
        DB::beginTransaction();
        try{
            DB::table('erp_wxuser_extend')->where('wxuser_id', $wxuser_id)->decrement('integral', $number);
            # 流水
            DB::table('erp_integral_record')->insert([
                'wsuser_id'=>$wxuser_id,
                'number' => ceil($number),
                'get_type'=>$type,
                'order_id' => $order_id,
                'created_at' =>date('Y-m-d H;i:s')
            ]);
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
        }
        return $this->response('减少几分失败', 500);
    }

    /**
     * 当前订单是否送积分
     * @param $wxuser_id
     * @param $business_id
     * @param $order_money
     * @param string $order_id
     * @return array
     */
    public function givingIntegral($wxuser_id, $business_id, $order_money, $order_id='')
    {
        $setting = $this->getIntegralSetting($business_id);
        # 没有积分设置
        if (! $setting) {
            return $this->response('商家没有积分设置', 400);
        }
        if (! $setting->get_way) {
            return $this->response('商家没有设置积分获取方式', 400);
        }
        $get_way =  json_decode($setting->get_way, true);
        # 支付就送
        $give_num = $get_way[1] ?? 0;
        # 单笔支付满多少就送
        $full_give_num =  $get_way[2] ?? [];
        if ($full_give_num && $full_give_num['money'] <= $order_money){
            $give_num += $full_give_num['integral'];
        }

        if ($give_num) {
            # 是否有积分关联记录
            $integral_record = DB::table('erp_wxuser_extend')->where('wxuser_id', $wxuser_id)->first();
            DB::beginTransaction();
            try{
                # 增加积分
                if ($integral_record) {
                    DB::table('erp_wxuser_extend')->where('wxuser_id', $wxuser_id)
                        ->where('id', $integral_record->id)
                        ->increment('integral', $give_num);
                } else {
                    DB::table('erp_wxuser_extend')->insert([
                        'wxuser_id'=> $wxuser_id,
                        'integral'=>$give_num,
                        'growth_value'=>0,
                        'updated_time'=>time()
                    ]);
                }
                # 流水
                DB::table('erp_integral_record')->insert([
                    'wsuser_id'=>$wxuser_id,
                    'number' => $give_num,
                    'get_type'=>1,
                    'order_id' => $order_id,
                    'created_at' =>date('Y-m-d H;i:s')
                ]);
                DB::commit();
                return $this->response('赠送积分成功', 200);
            } catch (\Exception $exception) {
                DB::rollBack();
                return $this->response('赠送积分失败', 500);
            }
        }
        return $this->response('不符合积分赠送条件',400);


    }

    /**
     * 积分兑换商品
     * @param $wxuser_id
     * @param $business_id
     * @param array $goods_id
     * @return array   ['goods_ids'=>可兑换的商品id 索引数组, 'used_integral'=>消耗的积分]
     */
    public function exchangeGoods($wxuser_id, $business_id, array $goods_id=[])
    {
        $user_integral = $this->getUserIntegral($wxuser_id);
        if (! $user_integral) {
            return $this->response('用户没有积分', 400);
        }
        if (! $goods_id || ! is_array($goods_id)) {
            return $this->response('商品id参数错误', 400);
        }
        $setting = $this->getIntegralSetting($business_id);
        # 没有积分设置
        if (! $setting) {
            return $this->response('商家没有积分设置', 400);
        }
        # 没有抵扣设置
        if (! $setting->integral_deduction) {
            return $this->response('暂无可抵扣项', 400);
        }
        $dedu_set = json_decode($setting->integral_deduction, true);
        # 没有设置抵扣商品
        if (! $dedu_set[3]) {
            return $this->response('不可抵扣商品', 400);
        }
        # 可用抵扣商品
        $dedu_ids = [];  # 可抵扣的商品id
        $dedu_integral = 0; # 花费的积分
        foreach($goods_id as $goods) {
            if (! array_key_exists($goods, $dedu_set[3])) { # 商品属于可兑换
                continue;
            }
            if (! ($user_integral - $dedu_set[3][$goods] >=0)) {   # 用户积分不小于商品需要的积分
                continue;
            }
            array_push($dedu_ids, $goods);  # 兑换商品
            $dedu_integral += $dedu_set[3][$goods];  # 记录消耗积分
            $user_integral -= $dedu_set[3][$goods];  # 减少积分
            if ($user_integral<=0) {  # 积分不足
                break;
            }
        }
        return $this->response(null, 200, ['goods_ids'=>$dedu_ids, 'used_integral'=>$dedu_integral]);
    }

    /**
     * 可减免订单/运费的金额
     * @param $wxuser_id
     * @param $business_id
     * @param int $type   抵扣的类型 1 运费  2 运费
     * @return array   ['money'=>抵扣的金额, 'used_integral'=>消耗的积分]
     */
    public function decrMoney($wxuser_id, $business_id, $type= 1)
    {
        $setting = $this->getIntegralSetting($business_id);
        # 没有积分设置
        if (! $setting) {
            return $this->response('商家没有积分设置', 400);
        }
        # 没有抵扣设置
        if (! $setting->integral_deduction) {
            return $this->response('暂无可抵扣项', 400);
        }
        $dedu_set = json_decode($setting->integral_deduction, true);
        if (1==$type) {
            if (! $dedu_set[1]) {
                return $this->response('不可抵扣订单金额', 400);
            }
        } elseif (2==$type){
            if (! $dedu_set[2]) {
                return $this->response('不可抵扣运费', 400);
            }
        }

        $user_integral = $this->getUserIntegral($wxuser_id);
        if (! $user_integral) {
            return $this->response('用户没有积分', 400);
        }
        $base_set = $this->getBaseSetting($setting);
        if (!$base_set['deduction_per']) {
            return $this->response('商家未设置抵扣规则', 400);
        }

        if ($user_integral>= $base_set['single_use_limit'][1]) { # 用户积分大于等于最小限制
            if ($base_set['single_use_limit'][2]) { # 有最大限制
                if ($user_integral>= $base_set['single_use_limit'][2]) {  # 大于等于最大限制
                    $dedu = bcdiv($base_set['single_use_limit'][2], $base_set['deduction_per'],2);
                    $integral = $base_set['single_use_limit'][2];
                } else {
                    $dedu = bcdiv($user_integral, $base_set['deduction_per'],2);
                    $integral = $user_integral;
                }
                return $this->response('计算成功', 200, ['money'=>$dedu, 'used_integral'=>$integral, 'type'=>$type]);
            }
        }
        return $this->response('用户积分未达到最低抵扣标准', 400);
    }

    /**
     * 获取用户已有积分
     * @param $wxuser_id
     * @return mixed
     */
    public function getUserIntegral($wxuser_id)
    {
       return DB::table('erp_wxuser_extend')->where('wxuser_id', $wxuser_id)->value('integral');
    }

    /**
     * 抵扣使用的基础条件
     * @param $setting
     * @return array ['deduction_per' => 兑换比例, 'single_use_limit' => 使用限制]
     */
    public function getBaseSetting($setting)
    {
        return [
            'deduction_per' => $setting->deduction_per,
            'single_use_limit' => json_decode($setting->single_use_limit, true),
        ];
    }

    /**
     * 获取商家积分设置
     * @param $business_id
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Query\Builder|object|null
     */
    public function getIntegralSetting($business_id)
    {
        return DB::table('erp_integral_setting')->where('business_id', $business_id)->first();
    }

    /**
     * 返回
     * @param string $message
     * @param int $code  200 成功
     * @param array $data
     * @return array
     */
    public function response($message='操作成功', $code=200, $data=[])
    {
        return ['code'=>$code, 'message'=>$message , 'data'=>$data];
    }

}