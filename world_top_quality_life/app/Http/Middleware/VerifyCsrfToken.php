<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        //
        'service',
        'api/makeOrderData',
        'api/apitest',
        'api/getOrderInfo',
        'api/payOrder',
        'api/makePostNumber',
        'api/cancelPostNumber',
        'api/makePdfFile',
        'api/getLastPrice',
        'api/deletePackage',
        'api/deleteOrder',
        'api/makePackagePdfFile',
        'api/updatePackageWeight',
        'api/trackingMoreHook',
        'api/getPrintFiles',
        'api/printEnd',
        'asy/*',
        'eLoginRes',
        'saveCookieFile',
        //跨境支付回调
        'api/shop_mp/agent/pay/notify',
        'api/shop_mp/pay/notify',
    ];
}
