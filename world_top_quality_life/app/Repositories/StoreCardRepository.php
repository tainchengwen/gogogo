<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class StoreCardRepository  extends BaseRepository
{
    /**
     * 储值卡赠送积分
     * @param $card_id  储值卡id
     * @param $wxuser_id  用户id
     * @param $business_id
     * @return array
     */
    public function incrIntegral($card_id, $wxuser_id, $business_id)
    {
        /* 查询*/
        $result = DB::table('erp_store_card')->where('business_id', $business_id)
            ->where('id', $card_id)
            ->where('flag', 1)
            ->first();
        if (! $result) {
            $this->response('储值卡不存在',400);
        }
        if (! $result->integral) {
            $this->response('储值卡没有赠送积分',200);
        }

        # 是否有积分关联记录
        $integral_record = DB::table('erp_wxuser_extend')->where('wxuser_id', $wxuser_id)->first();
        DB::beginTransaction();
        try{
            # 增加积分
            if ($integral_record) {
                DB::table('erp_wxuser_extend')->where('wxuser_id', $wxuser_id)
                    ->where('id', $integral_record->id)
                    ->increment('integral', $result->integral);
            } else {
                DB::table('erp_wxuser_extend')->insert([
                    'wxuser_id'=> $wxuser_id,
                    'integral'=>$result->integral,
                    'growth_value'=>0,
                    'updated_time'=>time()
                ]);
            }
            # 流水
            DB::table('erp_integral_record')->insert([
                'wsuser_id'=>$wxuser_id,
                'number' => $result->integral,
                'get_type'=>3,
                'created_at' =>date('Y-m-d H;i:s')
            ]);
            DB::commit();
        } catch(\Exception $exception) {
            DB::rollBack();
            return $this->response('赠送积分失败',500);
        }
        return $this->response('赠送积分成功');
    }

    /**
     * 储值卡赠送会员时长
     * @param $card_id  储值卡id
     * @param $wxuser_id  用户id
     * @param $business_id
     * @return array
     */
    public function extendVipTime($card_id, $wxuser_id, $business_id)
    {
        /* 查询*/
        $result = DB::table('erp_store_card')->where('business_id', $business_id)
            ->where('id', $card_id)
            ->where('flag', 1)
            ->first();
        if (! $result) {
            $this->response('储值卡不存在',400);
        }
        if (! $result->vip_card) {
            $this->response('储值卡没有赠送会员',200);
        }
        # 查询赠送的会员卡是否有效
        $vip_card = DB::table('erp_vip_cards')
            ->where('id', $result->vip_card)
            ->where('business_id', $business_id)
            ->where('flag', 1)
            ->first();
        if (! $vip_card) {
            $this->response('会员卡不存在',400);
        }

        # 查询是否已有处于有效期的此会员卡
        $vip_record_msg = DB::table('erp_wxuser_vip_cards')->where('card_id',$result->vip_card)
            ->where('wxuser_id',$wxuser_id)
            ->where('invalid_at', 0)
            ->orWhere('invalid_at', '>',time())
            ->orderBy('id', 'desc')
            ->first();
        if (! $vip_record_msg) {
            # 赠送，新增记录
            DB::table('erp_wxuser_vip_cards')->insert([
                'card_id' => $result->vip_card,
                'wsuser_id' => $wxuser_id,
                'get_way' => 3,
                'created_at' => time(),
                'invalid_at'=> time() + $result->vip_time,
            ]);
        } else {
            if ($vip_record_msg->invalid_at != 0) {  # 不是永久有效才会更新过期时间
                DB::table('erp_wxuser_vip_cards')
                    ->where('card_id',$result->vip_card)
                    ->where( 'wsuser_id', $wxuser_id)
                    ->where('business_id', $business_id)
                    ->update(['invalid_at'=> $vip_record_msg->invalid_at + $result->vip_time,]);
            }
        }
        $this->response('赠送会员卡成功',200);
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