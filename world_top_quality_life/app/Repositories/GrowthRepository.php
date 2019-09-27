<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use function Sodium\increment;

class GrowthRepository  extends BaseRepository
{
    /**
     * 单次交易金额满多少送成长值
     * @param $wxuser_id
     * @param $business_id
     * @param $order_money
     * @return array
     */
    public function incrGrowthByPay($wxuser_id, $business_id, $order_money)
    {
        $setting = $this->getGrowthSetting($business_id);
        # 没有成长值设置
        if (! $setting || ! $setting->setting) {
            return $this->response('商家没有成长值设置', 400);
        }
        $config = json_decode($setting->setting, true);
        if (! array_key_exists('every_pay', $config) || ! $config['every_pay']) {
            return $this->response('商家没有设置成长值的对应增长条件', 400);
        }

        $money = $config['every_pay']['money'];
        $value = $config['every_pay']['growth'];
        if ($order_money < $money) {
            return $this->response('订单接不满足条件', 400);
        }

        # 是否有成长值关联记录
        $growth_record = DB::table('erp_wxuser_extend')->where('wxuser_id', $wxuser_id)->first();
        DB::beginTransaction();
        try{
            # 增加
            if ($growth_record) {
                DB::table('erp_wxuser_extend')->where('wxuser_id', $wxuser_id)
                    ->where('id', $growth_record->id)
                    ->increment('growth_value', $value);
            } else {
                DB::table('erp_wxuser_extend')->insert([
                    'wxuser_id'=> $wxuser_id,
                    'integral'=>0,
                    'growth_value'=>$value,
                    'updated_time'=>time()
                ]);
            }
            # 流水
            DB::table('erp_growth_record')->insert([
                'wxuser_id' => $wxuser_id,
                'number' => $value,
                'get_type' => 4,
                'created_at' =>date('Y-m-d H;i:s')
            ]);
            DB::commit();
            return $this->response('成长值增加成功', 200);
        }catch(\Exception $exception) {
            DB::rollBack();
            return $this->response('成长值增加失败', 500);
        }


    }

    /**
     * 增加成长值
     * @param $wxuser_id
     * @param $business_id
     * @param int $type    1 每日首次登录 2 签到 3 每笔成功订单
     * @param string $order_id
     * @return array
     */
    public function incrGrowth($wxuser_id, $business_id, $type=1, $order_id='')
    {
        $setting = $this->getGrowthSetting($business_id);
        # 没有成长值设置
        if (! $setting || ! $setting->setting) {
            return $this->response('商家没有成长值设置', 400);
        }
        $config = json_decode($setting->setting, true);
        $flag = false;
        if (1==$type) {
            $flag = array_key_exists('day', $config) && $config['day'] >0;
            $type = 'day';
        } elseif(2==$type) {
            $flag = array_key_exists('sign', $config) && $config['sign'] >0;
            $type = 'sign';
        } elseif (3==$type) {
            $flag = array_key_exists('every_order', $config) && $config['every_order'] >0;
            $type = 'every_order';
        }

        if ($flag) {
            $value = $config[$type];
            if ('day' == $type) {
                $record = DB::table('erp_growth_record')->whereDate('created_at', date('Y-m-d'))->first();
                if ($record) {
                    # 已有记录
                    return $this->response();
                }
            }

            # 是否有成长值关联记录
            $growth_record = DB::table('erp_wxuser_extend')->where('wxuser_id', $wxuser_id)->first();
            DB::beginTransaction();
            try{
                # 增加
                if ($growth_record) {
                    DB::table('erp_wxuser_extend')->where('wxuser_id', $wxuser_id)
                        ->where('id', $growth_record->id)
                        ->increment('growth_value', $value);
                } else {
                    DB::table('erp_wxuser_extend')->insert([
                        'wxuser_id'=> $wxuser_id,
                        'integral'=>0,
                        'growth_value'=>$value,
                        'updated_time'=>time()
                    ]);
                }
                # 流水
                DB::table('erp_growth_record')->insert([
                    'wxuser_id' => $wxuser_id,
                    'number' => $value,
                    'get_type' => 1,
                    'created_at' =>date('Y-m-d H;i:s')
                ]);
                DB::commit();
                return $this->response('成长值增加成功', 200);
            }catch(\Exception $exception) {
                DB::rollBack();
                return $this->response('成长值增加失败', 500);
            }
        }

        return $this->response('商家没有设置每日首次登录增长', 400);
    }

    /**
     * 获取商家成长值设置
     * @param $business_id
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Query\Builder|object|null
     */
    public function getGrowthSetting($business_id)
    {
        return DB::table('erp_growth_setting')->where('business_id', $business_id)->first();
    }

    /**
     * 返回
     * @param string $message
     * @param int $code
     * @return array
     */
    public function response($message='操作成功', $code=200)
    {
        return ['code'=>$code, 'message'=>$message];
    }

}