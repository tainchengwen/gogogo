<?php

namespace App\Http\Controllers\ApiShopMp;

use App\MarketCoupon;
use App\WxUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use DB;

class ShareMessageController extends Controller
{
    public function message(Request $request, $type)
    {

        if (empty($type)) {
            return $this->successResponse($request, []);
        }

        if ($type == 'coupons') {
            return $this->successResponse($request, [
                'title' => '优惠券等你来拿！',
                'imageUrl'  => 'https://fenithcdn.oss-cn-shanghai.aliyuncs.com/banner_img/share-message-0903.png'
            ]);
        } else {
            return $this->successResponse($request, []);
        }
    }
}
