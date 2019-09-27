<?php


namespace App\Http\Controllers\ApiWechatApplet;


use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Validator;
use DB;

class StoreCardController extends BaseController
{
    protected static $table = 'erp_store_card';

    /**
     *  禁用储值卡
     * @param Request $request
     * @return JsonResponse
     */
    public function changeStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'card_id' => 'required|integer',
            'business_id' => 'required|exists:erp_business,id',
            'status'=> 'required|integer|max:1|min:0',
        ]);

        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        DB::table(self::$table)->where('business_id', $request->business_id)
            ->where('id', $request->card_id)
            ->update(['flag'=>$request->status]);
        return $this->success();
    }
    /**
     * 新增储值卡 接口
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'=>'required',
            'price'=>'required|min:0.1',
            'business_id' => 'required|exists:erp_business,id',
            'vip_card'=>'sometimes|integer',
            'vip_time'=> 'required_with:vip_card',
            'integral' => 'sometimes|integer'
        ]);

        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        # 处理数据
        $insert_data = [];
        $insert_data['name'] = $request->name;
        $insert_data['price'] = $request->price;
        $insert_data['business_id'] = $request->business_id;
        $insert_data['created_at'] = time();
        if ($request->has('vip_card')) {
            $insert_data['vip_card'] = $request->vip_card;
            $insert_data['vip_time'] = $request->vip_time;
        }
        if ($request->has('integral')) {
            $insert_data['integral'] = $request->integral;
        }

        DB::table(self::$table)->insert($insert_data);
        return $this->success('新增储值卡成功');
    }

    /**
     * 储值卡列表 接口
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|exists:erp_business,id',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        //取出所有数据
        $cards = DB::table(self::$table)
            ->where('business_id', $request->business_id)
            ->where('flag', $request->status ?: 1)
            ->orderBy('id', 'desc')
            ->paginate($request->limit ?: 20);

        foreach ($cards as &$v) {
            if ($v->vip_card) {
                $v->give_vip ='会员卡，期限：' . $v->vip_time / 86400 .'天';
            }
            if ($v->integral) {
                $v->give_integral = '积分：' . $v->integral;
            }
        }
        return $this->success(null, $cards);
    }

    /**
     * 根据id获取储值卡详情 接口
     * @param Request $request
     * @return JsonResponse
     */
    public function info(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|exists:erp_business,id',
            'card_id'=> 'required'
        ]);

        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $result = DB::table(self::$table)->where('business_id', $request->business_id)->find($request->card_id);

        return $this->success(null, $result);
    }

    /**
     * 查询储值卡信息      模块调用
     * @param $card_id
     * @param $business_id
     * @param int $status
     * @return JsonResponse
     */
    public function card_msg($card_id, $business_id, $status=1)
    {
        $result = DB::table(self::$table)
            ->where('business_id', $business_id)
            ->where('flag', $status)
            ->find($card_id);

        return $result;
    }
}
