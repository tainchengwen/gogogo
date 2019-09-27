<?php


namespace App\Http\Controllers\ApiWechatApplet;


use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Validator;
use DB;

class GrowthValueController extends BaseController
{
    protected static $table = 'erp_growth_setting';

    /**
     * 新增成长值设置 接口
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|exists:erp_business,id',
            'setting' => 'required',
        ]);

        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        # 处理数据
        $insert_data = [];
        $insert_data['business_id'] = $request->business_id;
        $insert_data['setting'] = json_encode($request->setting);
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
        return $this->success('成长值设置成功');
    }

    /**
     * 获取成长值设置  接口
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
     * 获取成长值设置       模块调用
     * @param $business_id
     * @return JsonResponse
     */
    public function growth_msg($business_id)
    {
        $result = DB::table(self::$table)->where('business_id', $business_id)->first();
        return $result;
    }
}
