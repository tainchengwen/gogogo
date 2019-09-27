<?php

namespace App\Repositories;

use DB;

class VipCardRepository extends BaseRepository
{
    /*----------------------主要方法----------------
     *
    1,过期检查:checkInvalid
    2,发放会员卡
        2-1,直接发放:sendVipCardDirect
        2-2,标准方式付费发放:sendVipCardByPay
        2-3,多种方式付费发放:sendVipCardByMultiPay
        2-4,累计金额发放:sendVipCardByCumulativeAmount
        2-5,累计经验值发放:sendVipCardByCumulativeExperience
        2-6,购买指定商品发放:sendVipCardBySkus
    3,赠送会员卡(这里默认用户表wxuser有积分字段integral):handselVipCard
    4,获取用户的会员卡折扣:getUserVipCardDiscount
    5,获取用户的会员卡是否有包邮权益:getUserVipCardShipping
    6,获取用户的会员卡积分回馈倍率:getUserVipCardIntegral
    7,获取用户可赠送的会员卡列表:getUserCanHandselVipCards
    8,检查某用户是否拥有某张会员卡，并且未过期:checkUserHasUsingCard
    9,获取用户默认应用的会员卡:getUserDefaultVipCard
    */

    /**
     * 过期检查
     * @param $relation_id 用户绑定的会员卡的关联id(erp_wxuser_vip_cards的id)
     * @return bool true过期，false未过期
     */
    public function checkInvalid($relation_id)
    {
        $wxuser_card=DB::table('erp_wxuser_vip_cards')->where('id',$relation_id)->first();
        if ($wxuser_card && ($wxuser_card->invalid_at == 0 || time() < $wxuser_card->invalid_at)){
            //0代表永久有效，或者当前时间小于过期时间
            return false;
        }
        return true;
    }

    /**
     * 直接发放会员卡
     * @param $wxuser_id 用户id
     * @param $vip_card_id 会员卡id
     * @return array [code:结果状态,200成功500失败;msg:具体成功/失败原因;card_id:成功时会返回发放的会员卡id]
     */
    public function sendVipCardDirect($wxuser_id, $vip_card_id)
    {
        return $this->sendVipCard('Direct',$wxuser_id,$vip_card_id);
    }

    /**
     * 标准方式付费发放
     * @param $wxuser_id 用户id
     * @param $vip_card_id 会员卡id
     * @param $pay_price 付费金额
     * @return array [code:结果状态,200成功500失败;msg:具体成功/失败原因;card_id:成功时会返回发放的会员卡id]
     */
    public function sendVipCardByPay($wxuser_id, $vip_card_id, $pay_price)
    {
        return $this->sendVipCard('Pay',$wxuser_id,$vip_card_id,['pay_price'=>$pay_price]);
    }

    /**
     * 多种方式付费发放
     * @param $wxuser_id 用户id
     * @param $vip_card_id 会员卡id
     * @param $multi_id 付费方式id,例如用户购买了月卡,传递月卡id
     * @param $pay_price 付费金额
     * @return array [code:结果状态,200成功500失败;msg:具体成功/失败原因;card_id:成功时会返回发放的会员卡id]
     */
    public function sendVipCardByMultiPay($wxuser_id, $vip_card_id, $multi_id, $pay_price)
    {
        return $this->sendVipCard('MultiPay',$wxuser_id,$vip_card_id,['multi_id'=>$multi_id,'pay_price'=>$pay_price]);
    }

    /**
     * 累计金额发放会员卡
     * @param $wxuser_id 用户id
     * @param $business_id 当前事业部id
     * @param $cumulative_amount 累计金额
     * @return array [返回成功发放的会员卡ids]
     */
    public function sendVipCardByCumulativeAmount($wxuser_id, $business_id, $cumulative_amount)
    {
        return $this->sendVipCardPassive('CumulativeAmount',$wxuser_id,$business_id,$cumulative_amount);
    }

    /**
     * 累计经验值发放
     * @param $wxuser_id 用户id
     * @param $business_id 当前事业部id
     * @param $cumulative_experience 累计经验值
     * @return array [返回成功发放的会员卡ids]
     */
    public function sendVipCardByCumulativeExperience($wxuser_id, $business_id, $cumulative_experience)
    {
        return $this->sendVipCardPassive('CumulativeExperience',$wxuser_id,$business_id,$cumulative_experience);
    }

    /**
     * 购买指定商品发放
     * @param $wxuser_id 用户id
     * @param $business_id 当前事业部id
     * @param $sku_ids 购买的sku ids
     * @return array [返回成功发放的会员卡ids]
     */
    public function sendVipCardBySkus($wxuser_id, $business_id, $sku_ids)
    {
        return $this->sendVipCardPassive('Skus',$wxuser_id,$business_id,$sku_ids);
    }

    /**
     * 赠送会员卡
     * @param $from_user_id 赠送人id
     * @param $to_user_id 被赠送人id
     * @param $card_id 会员卡id
     * @return array [code:结果状态,200成功500失败;msg:具体成功/失败原因;]
     */
    public function handselVipCard($from_user_id, $to_user_id, $card_id)
    {
        //先取出赠送人默认应用的会员卡信息
        if (!$card=$this->getUserDefaultVipCard($from_user_id)){
            return [
                'code'=>500,
                'msg'=>'赠送人当前没有可用的会员卡',
            ];
        }
        //检查该会员卡是否有赠送的权益
        if (!$this->checkVipCardHasHandselRight($card)){
            return [
                'code'=>500,
                'msg'=>'赠送人当前应用的会员卡没有赠送权益',
            ];
        }
        //检查待赠送的会员卡是否在赠送权益列表中
        if (!$this->checkIsCardInHandselList($card,$card_id)){
            return [
                'code'=>500,
                'msg'=>'此会员卡没有赠送该卡种的权益',
            ];
        }
        //检查该会员卡是否还有赠送的次数
        if (!$this->checkIsCardHasMoreHandselNumber($card,$card_id)){
            return [
                'code'=>500,
                'msg'=>'此会员卡已超出赠送次数',
            ];
        }
        //检查被赠送人是否已经有待赠送的卡
        if ($this->checkUserHasUsingCard($to_user_id,$card_id)){
            return [
                'code'=>500,
                'msg'=>'被赠送人已拥有此会员卡',
            ];
        }
        //赠送
        DB::beginTransaction();
        try{
            $insert_data=[
                'card_id'=>$card_id,
                'wxuser_id'=>$to_user_id,
                'created_at'=>time(),
                'gived_by'=>$from_user_id,
                //获取赠送的体验卡时间
                'invalid_at'=>$this->getHandselInvalidAt($card,$card_id),
            ];
            DB::table('erp_wxuser_vip_cards')->insert($insert_data);
            //更新赠送次数
            $this->updateGiveCount($card,$card_id);
            //更新赠送人积分
            $this->updateUserIntegral($from_user_id,$card);
            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
            return [
                'code'=>500,
                'msg'=>'赠送失败',
            ];
        }
        return [
            'code'=>200,
            'msg'=>'赠送成功',
        ];
    }

    /**
     * 获取用户的会员卡折扣
     * @param $wxuser_id 用户id
     * @return array [code:结果状态,200成功500失败;msg:具体成功/失败原因;discount:成功时会返回折扣率,如95代表九五折]
     */
    public function getUserVipCardDiscount($wxuser_id)
    {
        //先取出赠送人默认应用的会员卡信息
        if (!$card=$this->getUserDefaultVipCard($wxuser_id)){
            return [
                'code'=>500,
                'msg'=>'当前用户没有可用的会员卡',
            ];
        }
        $rights=json_decode($card->rights,true);
        if (!isset($rights[1])){
            return [
                'code'=>500,
                'msg'=>'当前用户默认的会员卡没有折扣权益',
            ];
        }
        return [
            'code'=>200,
            'discount'=>$rights[1],
        ];

    }

    /**
     * 获取用户的会员卡是否有包邮权益
     * @param $wxuser_id 用户id
     * @return array [code:结果状态,200成功500失败;msg:具体成功/失败原因;shipping:成功时会返回true,代表包邮]
     */
    public function getUserVipCardShipping($wxuser_id)
    {
        //先取出赠送人默认应用的会员卡信息
        if (!$card=$this->getUserDefaultVipCard($wxuser_id)){
            return [
                'code'=>500,
                'msg'=>'当前用户没有可用的会员卡',
            ];
        }
        $rights=json_decode($card->rights,true);
        if (!isset($rights[2]) || !$rights[2]){
            return [
                'code'=>500,
                'msg'=>'当前用户默认的会员卡没有包邮权益',
            ];
        }
        return [
            'code'=>200,
            'shipping'=>true,
        ];

    }

    /**
     * 获取用户的会员卡积分回馈倍率
     * @param $wxuser_id 用户id
     * @return array [code:结果状态,200成功500失败;msg:具体成功/失败原因;integral:成功时会返回倍率]
     */
    public function getUserVipCardIntegral($wxuser_id)
    {
        //先取出赠送人默认应用的会员卡信息
        if (!$card=$this->getUserDefaultVipCard($wxuser_id)){
            return [
                'code'=>500,
                'msg'=>'当前用户没有可用的会员卡',
            ];
        }
        $rights=json_decode($card->rights,true);
        if (!isset($rights[3])){
            return [
                'code'=>500,
                'msg'=>'当前用户默认的会员卡没有返积分权益',
            ];
        }
        return [
            'code'=>200,
            'integral'=>$rights[3],
        ];

    }

    /**
     * 获取用户可赠送的会员卡列表
     * @param $wxuser_id 用户id
     * @return array [code:结果状态,200成功500失败;msg:失败原因;list:成功时会返回列表]
     */
    public function getUserCanHandselVipCards($wxuser_id)
    {
        //先取出赠送人默认应用的会员卡信息
        if (!$card=$this->getUserDefaultVipCard($wxuser_id)){
            return [
                'code'=>500,
                'msg'=>'当前用户没有可用的会员卡',
            ];
        }
        $rights=json_decode($card->rights,true);
        if (!isset($rights[4])){
            return [
                'code'=>500,
                'msg'=>'当前用户默认的会员卡没有赠送权益',
            ];
        }

        $give_count=json_decode($card->give_count,true);

        $can_handsel_ids=[];
        foreach ($rights[4] as $v){
            if (isset($give_count[$v['card_id']]) && $give_count[$v['card_id']] >= $v['number']){
                continue;
            }
            $can_handsel_ids[]=$v['card_id'];
        }

        $list=DB::table('erp_vip_cards')->whereIn('id',$can_handsel_ids)->get();

        return [
            'code'=>200,
            'list'=>$list,
        ];

    }

    /**
     * 检查某用户是否拥有某张会员卡，并且未过期
     * @param $wxuser_id 用户id
     * @param $vip_card_id 会员卡id
     * @return mixed true存在,false不存在
     */
    public function checkUserHasUsingCard($wxuser_id, $vip_card_id)
    {
        return DB::table('erp_wxuser_vip_cards')
            ->where('wxuser_id',$wxuser_id)
            ->where('card_id',$vip_card_id)
            ->where(function($q){
                $q->where('invalid_at',0)
                    ->orWhere('invalid_at','>',time());
            })
            ->exists();
    }

    /**
     * 获取用户默认应用的会员卡
     * @param $wxuser_id 用户id
     * @return mixed
     */
    public function getUserDefaultVipCard($wxuser_id)
    {
        return DB::table('erp_wxuser_vip_cards')
            ->leftJoin('erp_vip_cards','erp_wxuser_vip_cards.card_id','erp_vip_cards.id')
            ->where('wxuser_id',$wxuser_id)
            ->where(function($q){
                $q->where('invalid_at',0)
                    ->orWhere('invalid_at','>',time());
            })
            ->select([
                'erp_wxuser_vip_cards.id as relation_id',
                'erp_wxuser_vip_cards.give_count',
                'erp_vip_cards.*',
            ])
            ->orderByDesc('erp_vip_cards.level')//默认应用等级最高的会员卡
            ->first();
    }

    /**
     * 获取指定id的会员卡详细信息
     * @param $vip_card_id
     * @return array
     */
    public function getVipCardInfo($vip_card_id)
    {
        $vip_card=DB::table('erp_vip_cards')->where('id',$vip_card_id)->first();
        if (!$vip_card){
            return [
                'code'=>500,
                'msg'=>'不存在该会员卡'
            ];
        }
        if ($vip_card->flag){
            return [
                'code'=>500,
                'msg'=>'该会员卡已禁用'
            ];
        }
        if ($vip_card->validity_type == 3 && $vip_card->end_at <= time()){
            return [
                'code'=>500,
                'msg'=>'该会员卡已过期'
            ];
        }
        //计算当前时刻发放该会员卡给用户时，用户这张卡的过期时间
        $vip_card->invalid_at=$this->computerInvalidAt($vip_card);

        return [
            'code'=>200,
            'data'=>$vip_card
        ];
    }

    /**
     * 获取会员卡拥有的权益字符串
     * @param $rights
     * @return string
     */
    public function getRightsStr($rights)
    {
        $rights=json_decode($rights,true);

        $rights_str='';
        $rights_str.=array_key_exists('1',$rights)?'折扣,':'';
        $rights_str.=array_key_exists('2',$rights) && $rights[2]?'包邮,':'';
        $rights_str.=array_key_exists('3',$rights)?'积分,':'';
        $rights_str.=array_key_exists('4',$rights)?'赠送体验,':'';

        return rtrim($rights_str,',');
    }

    /**
     * 获取会员卡有效期字符串
     * @param $type
     * @param $begin
     * @param $end
     * @return string
     */
    public function getValidityStr($type, $begin, $end)
    {
        switch ($type)
        {
            case 1:
                $validity_str='永久有效';
                break;
            case 2:
                $validity_str='领卡后'.$end.'天有效';
                break;
            case 3:
                $validity_str=date('Y-m-d H:i:s',$begin).'至'.date('Y-m-d H:i:s',$end);
                break;
            default:
                break;
        }
        return $validity_str;
    }

    /**
     * 计算会员卡的失效时间
     * @param $card
     * @return false|int
     */
    private function computerInvalidAt($card)
    {
        //1永久有效2领卡后多少天有效3时间段内有效
        switch ($card->validity_type)
        {
            case 1:
                $invalid_at=0;
                break;
            case 2:
                $invalid_at=strtotime("+ {$card->end_at}days");
                break;
            case 3:
                $invalid_at=$card->end_at;
                break;
            default:
                break;
        }
        return $invalid_at;
    }

    private function sendVipCardPassive($type,$wxuser_id,$business_id,$cumulative)
    {
        //取出该事业部下所有可用的会员卡
        $card_ids=DB::table('erp_vip_cards')
            ->where('business_id',$business_id)
            ->where('flag',0)
            ->where(function($q){
                $q->where('validity_type','!=',3)
                    ->orWhere([
                        ['validity_type','=',3],
                        ['end_at','>',time()],
                    ]);
            })
            ->orderByDesc('id')
            ->pluck('id');

        $send_success_ids=[];
        foreach ($card_ids as $card_id){
            $result=$this->sendVipCard($type,$wxuser_id,$card_id,[snake_case($type)=>$cumulative]);
            if ($result['code']==200){
                $send_success_ids[]=$result['card_id'];
            }
        }
        return $send_success_ids;
    }

    private function sendVipCard($send_type,$wxuser_id,$vip_card_id,$param=[])
    {
        //检查该用户是否已经有这张卡，并且未过期正在使用中
        if ($this->checkUserHasUsingCard($wxuser_id,$vip_card_id)){
            return [
                'code'=>500,
                'msg'=>'此用户已存在该会员卡'
            ];
        }
        //取出会员卡的信息
        $vip_card=$this->getVipCardInfo($vip_card_id);

        if ($vip_card['code'] != 200){
            return [
                'code'=>500,
                'msg'=>$vip_card['msg']
            ];
        }

        //检查是否满足发放条件
        $check_func="checkSendType{$send_type}";
        $check_type_result=$this->$check_func($vip_card['data'],$param);
        if ($check_type_result['code'] != 200){
            return $check_type_result;
        }

        $insert_data=[
            'card_id'=>$vip_card_id,
            'wxuser_id'=>$wxuser_id,
            'created_at'=>time(),
            //选择特殊时间会替代原本通用时间
            'invalid_at'=>$send_type=='MultiPay'?$check_type_result['invalid_at']:$vip_card['data']->invalid_at,
        ];

        DB::table('erp_wxuser_vip_cards')->insert($insert_data);

        return [
            'code'=>200,
            'msg'=>'发放成功',
            'card_id'=>$vip_card_id//成功发放的会员卡id
        ];
    }

    private function checkSendTypeDirect($card,$param=[])
    {
        if ($card->receive_type != 1){
            return [
                'code'=>500,
                'msg'=>'该会员卡不支持直接领取'
            ];
        }
        return [
            'code'=>200,
            'msg'=>'符合发放条件'
        ];
    }

    private function checkSendTypePay($card,$param=[])
    {
        if (!array_key_exists('pay_price',$param)){
            return [
                'code'=>500,
                'msg'=>'请传递已付费金额'
            ];
        }
        if ($card->receive_type != 2){
            return [
                'code'=>500,
                'msg'=>'该会员卡不支持付费购买'
            ];
        }
        $condition=json_decode($card->condition_data,true);
        if (!array_key_exists('1',$condition)){
            return [
                'code'=>500,
                'msg'=>'该会员卡不支持付费购买'
            ];
        }
        if ($condition[1] > $param['pay_price']){
            return [
                'code'=>500,
                'msg'=>'付费不足,未达到购买条件'
            ];
        }
        return [
            'code'=>200,
            'msg'=>'符合发放条件'
        ];
    }

    private function checkSendTypeMultiPay($card,$param=[])
    {
        if (!array_key_exists('multi_id',$param) || !array_key_exists('pay_price',$param)){
            return [
                'code'=>500,
                'msg'=>'请传递正确的参数'
            ];
        }
        if ($card->receive_type != 2){
            return [
                'code'=>500,
                'msg'=>'该会员卡不支持付费购买'
            ];
        }
        $condition=json_decode($card->condition_data,true);
        if (!array_key_exists('2',$condition)){
            return [
                'code'=>500,
                'msg'=>'该会员卡不支持多种付费方式购买'
            ];
        }

        $code=500;
        foreach ($condition[2] as $v){
            if ($v['id'] == $param['multi_id'] && $v['price'] <= $param['pay_price']){
                $code=200;
                //选择特殊时间会替代原本通用时间
                $invalid_at=isset($v['days'])?strtotime("+ {$v['days']}days"):strtotime("+ {$v['months']}months");
            }
        }
        if ($code != 200){
            return [
                'code'=>500,
                'msg'=>'不存在该付费方式或付费不足'
            ];
        }
        return [
            'code'=>200,
            'msg'=>'符合发放条件',
            'invalid_at'=>$invalid_at
        ];
    }

    private function checkSendTypeCumulativeAmount($card,$param=[])
    {
        if (!array_key_exists('cumulative_amount',$param)){
            return [
                'code'=>500,
                'msg'=>'请传递正确的参数'
            ];
        }
        if ($card->receive_type != 2){
            return [
                'code'=>500,
                'msg'=>'该会员卡不支持满条件发放'
            ];
        }
        $condition=json_decode($card->condition_data,true);
        if (!array_key_exists('3',$condition)){
            return [
                'code'=>500,
                'msg'=>'该会员卡不支持累计金额发放'
            ];
        }

        if ($condition[3] > $param['cumulative_amount']){
            return [
                'code'=>500,
                'msg'=>'未达到累计金额'
            ];
        }

        return [
            'code'=>200,
            'msg'=>'符合发放条件',
        ];
    }

    private function checkSendTypeCumulativeExperience($card,$param=[])
    {
        if (!array_key_exists('cumulative_experience',$param)){
            return [
                'code'=>500,
                'msg'=>'请传递正确的参数'
            ];
        }
        if ($card->receive_type != 2){
            return [
                'code'=>500,
                'msg'=>'该会员卡不支持满条件发放'
            ];
        }
        $condition=json_decode($card->condition_data,true);
        if (!array_key_exists('4',$condition)){
            return [
                'code'=>500,
                'msg'=>'该会员卡不支持累计经验值发放'
            ];
        }

        if ($condition[4] > $param['cumulative_experience']){
            return [
                'code'=>500,
                'msg'=>'未达到累计经验值'
            ];
        }

        return [
            'code'=>200,
            'msg'=>'符合发放条件',
        ];
    }

    private function checkSendTypeSkus($card,$param=[])
    {
        if (!array_key_exists('skus',$param)){
            return [
                'code'=>500,
                'msg'=>'请传递正确的参数'
            ];
        }
        if ($card->receive_type != 2){
            return [
                'code'=>500,
                'msg'=>'该会员卡不支持满条件发放'
            ];
        }
        $condition=json_decode($card->condition_data,true);
        if (!array_key_exists('5',$condition)){
            return [
                'code'=>500,
                'msg'=>'该会员卡不支持购买指定商品发放'
            ];
        }

        if (empty(array_intersect($condition[5],$param['skus']))){
            return [
                'code'=>500,
                'msg'=>'未购买指定商品'
            ];
        }

        return [
            'code'=>200,
            'msg'=>'符合发放条件',
        ];
    }

    private function checkVipCardHasHandselRight($card)
    {
        $rights=json_decode($card->rights,true);

        return array_key_exists('4',$rights) && !empty($rights[4]);
    }

    private function checkIsCardInHandselList($card,$to_handsel_card_id)
    {
        $rights=json_decode($card->rights,true);

        $in=false;
        foreach ($rights[4] as $v){
            if ($v['card_id']==$to_handsel_card_id){
                $in=true;
                break;
            }
        }
        return $in;
    }

    private function checkIsCardHasMoreHandselNumber($card,$to_handsel_card_id)
    {
        $rights=json_decode($card->rights,true);

        $number=0;
        foreach ($rights[4] as $v){
            if ($v['card_id']==$to_handsel_card_id){
                $number=$v['number'];
                break;
            }
        }
        if ($number == 0){
            return false;
        }
        if (! $card->give_count) {
            return true;
        }
        $give_count=json_decode($card->give_count,true);
        if (isset($give_count[$to_handsel_card_id]) && $give_count[$to_handsel_card_id] >= $number){
            return false;
        }
        return true;
    }

    private function getHandselInvalidAt($card,$to_handsel_card_id)
    {
        $rights=json_decode($card->rights,true);

        $invalid_at=0;
        foreach ($rights[4] as $v){
            if ($v['card_id']==$to_handsel_card_id){
                $invalid_at=isset($v['days'])?strtotime("+ {$v['days']}days"):strtotime("+ {$v['months']}months");
                break;
            }
        }
        return $invalid_at;
    }

    private function updateGiveCount($card,$to_handsel_card_id)
    {
        $give_count=json_decode($card->give_count,true);

        if (empty($give_count) || !isset($give_count[$to_handsel_card_id])){
            $give_count[$to_handsel_card_id]=1;
        }else{
            $give_count[$to_handsel_card_id]++;
        }
        DB::table('erp_wxuser_vip_cards')
            ->where('id',$card->relation_id)
            ->update(['give_count'=>json_encode($give_count)]);
    }

    private function updateUserIntegral($wxuser_id,$card)
    {
        $rights=json_decode($card->rights,true);

        if (isset($rights['integral'])){
            # 新增
            DB::table('erp_wxuser_extend')
                ->where('wxuser_id',$wxuser_id)
                ->increment('integral',$rights['integral']);
            # 流水
            DB::table('erp_integral_record')->insert([
                'wxuser_id'=>$wxuser_id,
                'number'=> ceil($rights['integral']),
                'get_type'=>2,
                'created_at'=> date('Y-m-d H:i:s')
            ]);
        }
    }
}
