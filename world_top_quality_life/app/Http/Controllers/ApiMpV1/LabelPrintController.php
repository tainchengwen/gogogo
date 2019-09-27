<?php

namespace App\Http\Controllers\ApiMpV1;

use App\Jobs\sendLabelByMail;
use App\MpPackageNumber;
use App\PrintSequeue;
use App\WxUser;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class LabelPrintController extends Controller
{
    //扫描打印机编号 直接打印标签
    public function scanPrintLabel(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:mp_users,id',
        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }

        if(!$request -> printNum){
            return [
                'code' => 500,
                'msg' => '请扫描打印机二维码'
            ];
        }
        if($this -> checkPrintNum($request -> printNum)){
            return $this -> checkPrintNum($request -> printNum);
        }



        return [
            'code' => 200,
            'msg' => '扫描成功',
            'printNum' => trim($request -> printNum)
        ];
    }

    //扫描邮件发送二维码
    public function scanMailPrintLabel(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:mp_users,id',
        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }

        if(!$request -> printNum){
            return [
                'code' => 500,
                'msg' => '请扫描打印机二维码'
            ];
        }

        if($this -> checkPrintNum($request -> printNum)){
            return $this -> checkPrintNum($request -> printNum);
        }

        $print_num_arr = explode('-',trim($request -> printNum));


        return [
            'code' => 200,
            'msg' => '扫描成功',
            'printNum' => trim($print_num_arr[2])
        ];
    }



    //打印
    public function printLabel(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:mp_users,id',
        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }

        if(!$request -> printNum){
            return [
                'code' => 500,
                'msg' => '请扫描打印机二维码'
            ];
        }

        if(!$request -> numbers){
            return [
                'code' => 500,
                'msg' => '请输入打印数量'
            ];
        }


        if($this -> checkPrintNum($request -> printNum)){
            return $this -> checkPrintNum($request -> printNum);
        }

        $print_num_arr = explode('-',trim($request -> printNum));

        //制作pdf 打印
        $ids = MpPackageNumber::makeMpNumber($print_num_arr[2],$request -> numbers);

        foreach($ids as $vo){
            $url = url('mpLabelOnePage').'?ids='.$vo;
            $pdfurl = PrintSequeue::printHtml($url,0,2);
            //print
            PrintSequeue::addQueue(5,$vo,$pdfurl,$print_num_arr[1]);
        }



        //开始打印
        return [
            'code' => 200,
            'msg' => '正在打印'
        ];


    }


    //邮件发送
    public function printLabelByMail(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:mp_users,id',
        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }

        if(!$request -> mail){
            return [
                'code' => 500,
                'msg' => '请输入邮箱'
            ];
        }

        if(!$request -> numbers){
            return [
                'code' => 500,
                'msg' => '请输入打印数量'
            ];
        }


        $print_num_arr = explode('-',trim($request -> printNum));
        if(isset($print_num_arr[2])){
            $area_id = $print_num_arr[2];
        }else{
            $area_id = 5;
        }




        $ids = MpPackageNumber::makeMpNumber($area_id,$request -> numbers);

        dispatch(new sendLabelByMail($request -> mail,$ids));



        return [
            'code' => 200,
            'msg' => '发送成功,请稍后'
        ];
    }


    //从我的预约打印
    public function printFromMySubscribe(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:mp_users,id',
            'subscribe_id' => 'required', //预约id
            'numbers' => 'required', //打印数量

        ]);
        if ($validator->fails()) {
            return [
                'code' => 500,
                'msg' => '缺少参数'
            ];
        }

        $model = new WxUser();
        $user_id = $model ->getWxUserId($request -> user_id);
        if(!$user_id){
            return [
                'code' => 500,
                'msg' => '没有此用户'
            ];
        }

        //先校验 这个预约单是否存在
        $data = DB::table('repertory') -> where([
            'user_id' => $user_id,
            'sub_type' => '5',
            'id' => $request -> subscribe_id,
            'flag' => 0
        ]) -> first();
        if(!$data){
            return [
                'code' => 500,
                'msg' => '没有此数据'
            ];
        }

        //变成已上门

        DB::table('repertory') -> where([
            'id' => $request -> subscribe_id,
        ]) -> update([
            'is_door' => 1
        ]);

        //制作pdf 直接打印
        $ids = MpPackageNumber::makeMpNumber(5,$request -> numbers);

        foreach($ids as $vo){

            DB::table('mp_temp_package_number') -> where([
                'id' => $vo
            ]) -> update([
                'temp_repertory_id' => $data -> id,
                //'user_id' => $request -> user_id
            ]);

            //测试 先暂时去掉打印这个功能
            if(true){
                $url = url('mpLabelOnePage').'?ids='.$vo;
                $pdfurl = PrintSequeue::printHtml($url,0,2);
                //print
                PrintSequeue::addQueue(5,$vo,$pdfurl,'009');
            }
        }

        //开始打印
        return [
            'code' => 200,
            'msg' => '正在打印'
        ];





    }



    //校验printNum
    public function checkPrintNum($printNum){
        //$printNum = base64_decode(trim($printNum));
        //二维码中 应该含有打印机编号 区域编号
        //hqyp-009-13
        $print_num_arr = explode('-',trim($printNum));
        if(count($print_num_arr) <> 3){
            return [
                'code' => 500,
                'msg' => '二维码不正确'
            ];
        }

        if($print_num_arr[0] != 'hqyp'){
            return [
                'code' => 500,
                'msg' => '二维码不正确'
            ];
        }
        $printer_config = config('admin.printer_set');
        if(!in_array($print_num_arr[1],$printer_config)){
            return [
                'code' => 500,
                'msg' => '二维码不正确'
            ];
        }
        $area_info = DB::table('area_name') -> where([
            'id' => $print_num_arr[2]
        ]) -> first();
        if(!$area_info){
            return [
                'code' => 500,
                'msg' => '二维码不正确'
            ];
        }
    }



}
