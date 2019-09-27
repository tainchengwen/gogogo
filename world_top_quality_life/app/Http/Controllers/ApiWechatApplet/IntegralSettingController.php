<?php


namespace App\Http\Controllers\ApiWechatApplet;


use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Validator;
use DB;

class IntegralSettingController extends BaseController
{
    protected static $table = 'erp_integral_setting';


    /**
     * 新增积分设置 接口
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|exists:erp_business,id',
            'deduction_per'=>'required|integer',
            'clear' => 'sometimes|min:0|max:2',
        ]);

        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        # 处理数据
        $insert_data = [];
        $insert_data['business_id'] = $request->business_id;
        $insert_data['deduction_per'] = $request->deduction_per;
        $insert_data['single_use_limit'] = json_encode($request->single_use_limit, true);
        $insert_data['integral_deduction'] = json_encode($request->integral_deduction,true);
        $insert_data['get_way'] = json_encode($request->get_way);
        $insert_data['clear'] = $request->clear;
        $insert_data['created_at'] = time();
        $new = DB::table(self::$table)->where('business_id', $request->business_id)->first();

        if (! $new) {
            # 新增
            DB::table(self::$table)->insert($insert_data);
        } else {
            # 更新
            unset($insert_data['business_id']);
            DB::table(self::$table)->where('business_id', $request->business_id)->update($insert_data);

        }
        return $this->success('积分设置成功');
    }

    /**
     * 获取积分设置  接口
     * @param Request $request
     * @return JsonResponse
     */
    public function info(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|exists:erp_business,id',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $result = DB::table(self::$table)->where('business_id', $request->business_id)->first();

        return $this->success(null, $result);
    }

    /**
     * 获取积分设置       模块调用
     * @param $business_id
     * @return JsonResponse
     */
    public function integral_msg($business_id)
    {
        $result = DB::table(self::$table)->where('business_id', $business_id)->first();
        return $result;
    }
}
