<?php

namespace App\Http\Controllers\ApiWechatApplet;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ApiWechatApplet\Traits\ApiResponse;
use App\Http\Controllers\ApiWechatApplet\Traits\SendSms;

class BaseController extends Controller
{
    use ApiResponse, SendSms;

}