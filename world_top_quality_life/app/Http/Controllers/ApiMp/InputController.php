<?php

namespace App\Http\Controllers\ApiMp;

use App\Repertory;
use App\WxUser;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class InputController extends Controller
{
    //国际物流 上传物流单号图片
    public function uploadRepertoryPhoto(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:mp_users,id',
            'photo' => 'required',
        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }

        $image = $request -> photo;
        if (strstr($image,",")){
            $image = explode(',',$image);
            $image = $image[1];
        }



        $destinationPath = public_path('uploads/return');
        $extension = 'png';
        $fileName = str_random(10).time().'.'.$extension;

        $res = file_put_contents($destinationPath.'/'.$fileName,base64_decode($image));
        if($res){

            $model = new WxUser();
            $wxuser_id = $model -> getWxUserId($request -> user_id);

            Repertory::insert([
                'user_id' => $wxuser_id,
                'photo' => url('uploads/return').'/'.$fileName,
                'created_at' => time(),
                'updated_at' => time(),
                'admin_user_name' => 'admin',
                'is_check' => 1,
                'sub_type' => 1,
                'status' => 6
            ]);

            return [
                'code' => 200,
                'msg' => '上传成功'
            ];

        }else{
            return [
                'code' => 500,
                'msg' => '上传失败'
            ];
        }









    }


    //提交送货上门
    public function submitDoor(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:mp_users,id',
            'send_date' => 'required|date',
            'tel' => 'required|numeric',
            'card' => 'required',
            'mail' => 'required',
            'num' => 'required', //件数/板数
            'weight' => 'required|numeric',
            'package_status' => 'required|numeric',

        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }
        $model = new WxUser();
        $userid = $model -> getWxUserId($request -> user_id);

        $id = Repertory::insertGetId([
            'user_id' => $userid,
            'weight' => $request -> weight,
            'card' => $request -> card,
            'tel' => $request -> tel,
            'mail' => $request -> mail,
            'song_date' => $request -> send_date,
            'package_status' => $request -> package_status,
            'num' => $request -> num,
            'remark' => $request -> remark,
            'created_at' => time(),
            'updated_at' => time(),
            'is_check' => 1,
            'sub_type' => 2,
            'admin_user_name' => 'admin',
            'status' => 6
        ]);


        //更新入仓号
        Repertory::where('id',$id) -> update([
            'canghao' => 'HQ'.$userid.'-'.date('ymd').'-'.sprintf('%06s',$id)
        ]);

        return [
            'code' => 200,
            'msg' => '提交成功'
        ];



    }


    //安排提货
    public function takeGoods(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:mp_users,id',
            'ti_date' => 'required|date',
            'name' => 'required',
            'tel' => 'required|numeric',
            'address' => 'required',
            'num' => 'required',
            'weight' => 'required|numeric',
            'service_type' => 'required|numeric', //1 2 999

        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }
        $model = new WxUser();
        $userid = $model -> getWxUserId($request -> user_id);

        $id = Repertory::insertGetId([
            'user_id' => $userid,
            'ti_date' => $request -> ti_date,
            'name' => $request -> name,
            'tel' => $request -> tel,
            'address' => $request -> address,
            'num' => $request -> num,
            'weight' => $request -> weight,
            'service_type' => $request -> service_type,
            'remark' => $request -> remark,
            'created_at' => time(),
            'updated_at' => time(),
            'is_check' => 1,
            'sub_type' => 3,
            'admin_user_name' => 'admin',
            'status' => 6
        ]);


        //更新入仓号
        Repertory::where('id',$id) -> update([
            'canghao' => 'HQ'.$userid.'-'.date('ymd').'-'.sprintf('%06s',$id)
        ]);

        return [
            'code' => 200,
            'msg' => '提交成功'
        ];

    }


    //我的预报
    public function myInput(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }

        //通过mp_user 查找 wxuser
        $mp_user = DB::table('mp_users')
            -> where([
                'id' => $request -> user_id
            ]) -> first();

        if(!$mp_user){
            return [
                'code' => 500,
                'msg' => '没有此用户'
            ];
        }

        $wxuser = DB::table('wxuser')
            -> where([
                'unionid' => $mp_user -> unionid
            ]) -> first();
        if(!$wxuser){
            return [
                'code' => 500,
                'msg' => '数据有误'
            ];
        }



        //查找他自己的预报
        $data = DB::table('repertory')
            -> where([
                'user_id' => $wxuser -> id,
                'flag' => 0
            ])
            -> select([
                'id',
                'numbers',
                'created_at',
                'company',
                'status',
                'sub_type',
                'is_check',
            ])
            -> orderBy('id','desc')
            -> paginate(20);

        $company = config('admin.repertory_company');
        $status = config('admin.repertory_status');
        $subtype = config('admin.repertory_sub_type');
        foreach($data as $k => $vo){
            $data[$k] -> zp_number = config('admin.repertory_id_prefix').sprintf('%06s',$vo -> id);
            $data[$k] -> company_str = isset($company[$vo -> company])?$company[$vo -> company]:'其他';
            if(!$vo -> is_check){
                //已经审核通过
                $data[$k] -> status_str = isset($status[$vo -> status])?$status[$vo -> status]:'未知';
            }else{
                $data[$k] -> status_str = '待确认';
            }
            if(!$vo -> numbers){
                $data[$k] -> numbers = '暂无';
            }

            $data[$k] -> created_at_str = date('Y-m-d H:i',$vo -> created_at);
            $data[$k] -> sub_type_str = $subtype[$vo -> sub_type];
        }

        return $data;

    }

}
