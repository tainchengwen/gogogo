<?php

namespace App\Http\Controllers\ApiShopMpAgent;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as BaseController;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class Controller extends BaseController
{
    public function successResponse($request, $data, $msg='success')
    {
        return [
            'status'    => 1,
            'msg'       => $msg,
            'token'     => empty($request->refreshToken) ? '' : $request->refreshToken,
            'data'      => $data
        ];
    }

    public function errorResponse($request, $data, $msg='error')
    {
        return [
            'status'    => 0,
            'msg'       => $msg,
            'token'     => empty($request->refreshToken) ? '' : $request->refreshToken,
            'data'      => $data
        ];
    }

    public function formatFloat($num)
    {
        return bcadd($num, 0, '2');
    }
}