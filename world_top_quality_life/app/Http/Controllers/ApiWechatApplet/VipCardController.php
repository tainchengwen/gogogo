<?php

namespace App\Http\Controllers\ApiWechatApplet;

use App\Repositories\VipCardRepository;
use Illuminate\Http\Request;
use Validator;
use DB;
use Illuminate\Http\JsonResponse;

class VipCardController extends BaseController
{
    /**
     * 添加会员卡
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'=>'required',
            'back_color'=>'required',
            'back_img'=>'required',
            'validity_type'=>'required|in:1,2,3',
            'begin_at'=>'required_if:validity_type,3',
            'end_at'=>'required_if:validity_type,2,3',
            'receive_type'=>'required|in:1,2',
            'condition_data'=>'sometimes|array',//条件数据
            'business_id'=>'required|exists:erp_business,id',
            'rights_data'=>'sometimes|array',//权益数据
            'rights_data.4.*.card_id'=>'sometimes|exists:erp_vip_cards,id',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        //组装数据

        $insert_data=[
            'name'=>$request->name,
            'back_color'=>$request->back_color,
            'back_img'=>$request->back_img,
            'validity_type'=>$request->validity_type,
            'begin_at'=>$request->has('begin_at')?$request->begin_at:0,
            'end_at'=>$request->has('end_at')?$request->end_at:0,
            'receive_type'=>$request->receive_type,
            'condition_data'=>json_encode($request->input('condition_data',[])),
            'rights'=>json_encode($request->input('rights_data',[])),
            'business_id'=>$request->business_id,
            'created_at'=>time(),
            'updated_at'=>time(),
        ];

        DB::table('erp_vip_cards')->insert($insert_data);

        return $this->success('新增成功');
    }

    /**
     * 会员卡列表
     * @param Request $request
     * @param VipCardRepository $vipCardRepository
     * @return JsonResponse
     */
    public function index(Request $request, VipCardRepository $vipCardRepository)
    {
        $validator = Validator::make($request->all(), [
            'business_id'=>'required|exists:erp_business,id',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        //取出所以数据
        $cards=DB::table('erp_vip_cards')
            ->where('business_id',$request->business_id)
            ->orderBy('id', 'desc')
            ->get();

        foreach ($cards as $card){
            $card->receive_str=$card->receive_type==1?'无门槛':'付费或条件领取';//领取条件
            $card->validity_str=$vipCardRepository->getValidityStr($card->validity_type,$card->begin_at,$card->end_at);//有效期
            $card->rights_str=$vipCardRepository->getRightsStr($card->rights);//权益
        }

        //已禁用
        $forbid=$cards->filter(function ($value, $key) {
            return $value->flag == 1;
        });
        //已过期
        $expired=$cards->filter(function ($value, $key) {
            return $value->validity_type == 3 && time() >= $value->end_at;
        });
        //使用中
        $using=$cards->filter(function ($value, $key) {
            if ($value->flag == 1 || ($value->validity_type == 3 && time() >= $value->end_at)){
                return false;
            }
            return true;
        });

        return $this->success('',[
            'using'=>$using,
            'forbid'=>$forbid,
            'expired'=>$expired,
        ]);
    }

    /**
     * 设置会员卡等级
     * @param Request $request
     * @return JsonResponse
     */
    public function setLevel(Request $request, VipCardRepository $vipCardRepository)
    {
        $validator = Validator::make($request->all(), [
            'business_id'=>'required|exists:erp_business,id',
            'level_data'=>['required','array',function($attribute, $value, $fail)use($request) {
                    if (count($value) != count(array_unique($value))){
                        return $fail("不能设置相同的等级");
                    }
                    $has_levels=DB::table('erp_vip_cards')
                        ->where('business_id',$request->input('business_id'))
                        ->whereNotIn('id',array_keys($value))
                        ->pluck('level')->toArray();
                    $check_result=array_intersect($has_levels,$value);
                    if (!empty($check_result)){
                        return $fail("不能设置和已存在会员卡相同的等级");
                    }
                }
            ],//['id'=>'level','会员卡id'=>'对应等级']
            'level_data.*'=>[
                'integer',
                'min:1',
                function($attribute, $value, $fail) {
                    $card_id=explode('.',$attribute)[1];
                    if (DB::table('erp_vip_cards')->where('id',$card_id)->doesntExist()){
                        return $fail("id为{$card_id}的会员卡不存在");
                    }
                }
            ],
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        DB::beginTransaction();
        try{
            foreach ($request->level_data as $card_id=>$level){
                DB::table('erp_vip_cards')->where([
                    'id'=>$card_id,
                    'business_id'=>$request->business_id,
                ])->update(['level'=>$level,'updated_at'=>time()]);
            }
            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
            return $this->error('设置失败');
        }

        return $this->success('设置等级成功');
    }

    /**
     * 禁用会员卡
     * @param Request $request
     * @return JsonResponse
     */
    public function forbid(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'=>'required|exists:erp_vip_cards,id',
            'business_id'=>'required|exists:erp_business,id',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        //todo 检测当前会员卡是否有未到期的用户，有则不能禁用

        DB::table('erp_vip_cards')->where([
            'id'=>$request->id,
            'business_id'=>$request->business_id,
        ])->update(['flag'=>1]);

        return $this->success('禁用成功');
    }

    /**
     * 编辑会员卡
     * @param Request $request
     * @return JsonResponse
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'=>'required|exists:erp_vip_cards,id',
            'name'=>'required',
            'back_color'=>'required',
            'back_img'=>'required',
            'receive_type'=>'required|in:1,2',
            'condition_data'=>'sometimes|array',//条件数据
            'business_id'=>'required|exists:erp_business,id',
            'rights_data'=>'sometimes|array',//权益数据
            'rights_data.4.*.card_id'=>'sometimes|exists:erp_vip_cards,id',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $update_data=[
            'name'=>$request->name,
            'back_color'=>$request->back_color,
            'back_img'=>$request->back_img,
            'receive_type'=>$request->receive_type,
            'condition_data'=>json_encode($request->input('condition_data',[])),
            'rights'=>json_encode($request->input('rights_data',[])),
            'updated_at'=>time(),
        ];

        DB::table('erp_vip_cards')->where('id',$request->id)->update($update_data);

        return $this->success('编辑成功');
    }

    //todo 新建会员卡时,购买指定商品的列表
    //todo 查看成员
    //todo 领卡记录
}
