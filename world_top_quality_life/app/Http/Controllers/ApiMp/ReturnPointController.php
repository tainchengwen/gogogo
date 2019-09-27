<?php

namespace App\Http\Controllers\ApiMp;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class ReturnPointController extends Controller
{
    //返点配置
    public function setting(){

    }

    //返点提交
    public function subReturnPoint(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:mp_users,id',
            'code' => 'required'
        ]);
        if ($validator->fails()) {
            return new JsonResource($validator->errors());
        }
    }
}
