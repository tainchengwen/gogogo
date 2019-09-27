<?php

namespace App\Http\Controllers\Home;

use App\Jobs\sendLabelByMail;
use App\Package;
use App\ReceiveRecord;
use App\StockOrder;
use EasyWeChat\Factory;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

define('ReqURL','http://api.kdniao.com/api/EOrderService');
class TestController extends Controller
{
    public function  test1(){

        $stock_order = new StockOrder();
        $res = $stock_order -> addAgentStockOrder([
            'order_info_json' => json_encode([
                [
                    'goods_id' => 3373,
                    'price' => 124,
                    'number' => 1
                ]
            ]),
            'business_id' => 79
        ]);
        dd($res);
    }



}
