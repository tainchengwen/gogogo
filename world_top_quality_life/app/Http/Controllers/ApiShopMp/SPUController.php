<?php

namespace App\Http\Controllers\ApiShopMp;

use App\Repositories\SKURepository;
use App\Repositories\SPURepository;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiShopMp\Controller;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SPUController extends Controller
{
    public function __construct(SPURepository $spuRepository,SKURepository $SKURepository)
    {
        $this->spuRepository = $spuRepository;
        $this->skuRepository = $SKURepository;
    }

    /**
     * 商品列表
     */
    public function spus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id'    => 'required|numeric|exists:erp_business,id',
            'warehouse_id'   => 'sometimes',
            'warehouse_name' => 'sometimes',
            'class_id'       => 'sometimes',
            'keyword'        => 'sometimes',
            'tag_name'       => 'sometimes'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($request, [], '缺少必要参数');
        }

        // 解码
        if ($request->warehouse_name) {
            $request->warehouse_name = urldecode($request->warehouse_name);
        }
        if ($request->keyword) {
            $request->keyword = urldecode($request->keyword);
        }
        if ($request->tag_name) {
            $request->tag_name = urldecode($request->tag_name);
        }

        $list = $this->spuRepository->searchPaginateNew($request);
        // 通过   business_id  获取该代理的相关配置
        return $this->successResponse($request, $list);
    }

    /**
     * 商品详情
     */
    public function spu(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'business_id'   => 'required|numeric|exists:erp_business,id'
        ]);
        if ($validator->fails()) {
            return $this->errorResponse($request, [], '缺少必要参数');
        }
        // 校验商品是否存在，
        $spu = $this->spuRepository->getSPUById($id);

        if (empty($spu)) {
            return $this->errorResponse($request, [], '商品不存在');
        }

        // 组装数据  所有的skus
        $assembledSPU = $this->spuRepository->getAssembledSPUByRequestAndId($request, $id);

        return $this->successResponse($request, $assembledSPU);
    }

    /**商品搜索
     * @param Request $request
     * @return array
     */
    public function spusSearch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id'    => 'required|numeric|exists:erp_business,id',
            'warehouse_id'   => 'sometimes',
            'warehouse_name' => 'sometimes',
            'class_id'       => 'sometimes',
            'keyword'        => 'sometimes',
            'tag_name'       => 'sometimes'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($request, [], '缺少必要参数');
        }

        // 解码
        if ($request->warehouse_name) {
            $request->warehouse_name = urldecode($request->warehouse_name);
        }
        if ($request->keyword) {
            $request->keyword = urldecode($request->keyword);
        }
        if ($request->tag_name) {
            $request->tag_name = urldecode($request->tag_name);
        }

        $list = $this->spuRepository->searchByKeyWord($request);

        return $this->successResponse($request, $list);
    }

    // 商品详情分享的带价格水印的图片
    public function shareMessageImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id',
            'mpNameId' => 'required|numeric',
            'skuId' => 'required|numeric'
        ]);
        if ($validator->fails()) {
            return $this->errorResponse($request, [], '请求参数不正确');
        }

        $sku = DB::table('erp_product_price as price')
                        ->leftJoin('erp_product_list as list', 'price.product_id', 'list.id')
                        ->where([
                            'price.id' => $request->skuId,
                            'price.mp_name_id' => $request->mpNameId
                        ])
                        ->select([
                            'list.image',
                            'price.price_b as v2',
                            'price.price_a as v3'
                        ])
                        ->first();
        if (!$sku) {
            return [
                'status' => 2,
                'data' => (object)[],
                'message' => '商品不存在'
            ];
        }
        // dd($sku);
        // 商品图片的 oos 路径
        // $imagePath = substr(parse_url($image)['path'], 1);
        $imagePath = substr(parse_url(getImageUrl($sku->image))['path'], 1);
        // dd($imagePath);

        // 将需图片base64编码
        $imageBase64 = base64_encode("{$imagePath}?x-oss-process=image/resize,l_300,limit_0");
        $urlSafeBase64Image = str_replace(array('+','/','='), array('-','_',''), $imageBase64);

        // 水印文字 base64 编码
        $vip2Price = $sku->v2;
        $vip3Price = $sku->v3;
        $v2Base64 = str_replace(array('+','/','='), array('-','_',''), base64_encode("V2价格:"));
        $v3Base64 = str_replace(array('+','/','='), array('-','_',''), base64_encode("V3价格:"));
        $v2PriceBase64= str_replace(array('+','/','='), array('-','_',''), base64_encode("￥{$vip2Price}"));
        $v3PriceBase64= str_replace(array('+','/','='), array('-','_',''), base64_encode("￥{$vip3Price}"));

        // 分享图片上的按钮 base64 编码
        $button = str_replace(array('+','/','='), array('-','_',''), base64_encode('upload/shareMessageButton.jpg?x-oss-process=image/resize,w_150,limit_0'));

        // 拼接分享需要的图片的路径
        $waterImagePath =
            "https://fenithcdn.oss-cn-shanghai.aliyuncs.com/".
            "upload/blank.png?x-oss-process=image/resize,m_fixed,w_500,h_400,limit_0/".
            "watermark,image_{$urlSafeBase64Image},g_center,voffset_48/".
            "watermark,text_{$v2Base64},color_666666,g_sw,x_40,y_40,size_24/".
            "watermark,text_{$v2PriceBase64},color_FF558F,g_sw,x_124,y_40,size_30/".
            "watermark,text_{$v3Base64},color_666666,g_sw,x_274,y_40,size_24/".
            "watermark,text_{$v3PriceBase64},color_FF558F,g_sw,x_358,y_40,size_30";
            // "watermark,image_{$button},g_se,x_46,y_30";
        // dd($waterImagePath);
        return [
            'errno' => 0,
            'status' => 1,
            'data' => [
                'image' => $waterImagePath
            ]
        ];
    }



    //获取商品详情海报
    public function shareGoodsImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'spuId' => 'required',
            'type' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->errorResponse($request, [], '请求参数不正确');
        }

        $need = $this->spuRepository->fetchGoodsInfo($request);
        $imagePath = substr(parse_url($need['image'])['path'], 1);
        // 将需图片base64编码
        $imageBase64 = base64_encode("{$imagePath}?x-oss-process=image/resize,l_580,limit_0");
        $urlSafeBase64Image = str_replace(array('+','/','='), array('-','_',''), $imageBase64);


        //获取小程序码


        // 水印文字 base64 编码
        $vip2Price = $need['vip2price'];
        $vip3Price = $need['vip3price'];
        $spuName1 = $spuName = $need['name'];
        $spuName2 = '';
        if(mb_strlen($spuName) >12) {
            $spuName1 = mb_substr($spuName, 0, 11);
            $spuName2 = mb_substr($spuName,11,11).'';
            if(mb_strlen($spuName)>24){
                $spuName2 = mb_substr($spuName,11,11).'...';
            }
        }
        $spuNameBase641 = str_replace(array('+','/','='), array('-','_',''), base64_encode("{$spuName1}"));
        $spuNameBase642 = $spuName2 ? str_replace(array('+','/','='), array('-','_',''), base64_encode("{$spuName2}")):'';
        $tr = $spuNameBase642?"watermark,text_{$spuNameBase642},color_444444,g_sw,x_62,y_290,size_32/":'';
        if($request->type == 'special'){
            $res = $this->spuRepository->mpQRCode("{$request->spuId}",[
                'page'  => $request->type == 'common' ? 'pages/details/details' : 'pages/limitdetails/limitdetails',
                'width' => 160
            ]);
            $qrcode =  str_replace(array('+','/','='), array('-','_',''), base64_encode($res));
            $v2Base64 = str_replace(array('+','/','='), array('-','_',''), base64_encode("限时特价:"));
            $v3Base64 = str_replace(array('+','/','='), array('-','_',''), base64_encode("原价:"));
            $v2PriceBase64= str_replace(array('+','/','='), array('-','_',''), base64_encode("￥{$vip2Price}元"));
            $v3PriceBase64= str_replace(array('+','/','='), array('-','_',''), base64_encode("￥{$vip3Price}元"));
            $by = str_replace(array('+','/','='), array('-','_',''), base64_encode("longpicture/by.png"));
            // 拼接分享需要的图片的路径
            $waterImagePath =
                "https://fenithcdn.oss-cn-shanghai.aliyuncs.com/".
                "upload/back.jpg?x-oss-process=image/resize,m_fixed,w_750,h_1148,limit_0/".
                "watermark,image_{$urlSafeBase64Image},g_center,voffset_152,w_580,h_580/".
                "watermark,image_{$by},g_nw,x_22,y_184,w_180,h_60/".
                "watermark,image_{$qrcode},g_se,x_62,y_224,w_160,h_160/".
                "watermark,text_{$spuNameBase641},color_444444,g_sw,x_132,y_336,size_32/".
                $tr.
                "watermark,text_{$v2Base64},color_444444,g_sw,x_62,y_200,size_32/".
                "watermark,text_{$v2PriceBase64},color_FD4577,g_sw,x_200,y_200,size_32/".
                "watermark,text_{$v3Base64},color_A4A4A4,g_sw,x_62,y_144,size_24/".
                "watermark,text_{$v3PriceBase64},color_A4A4A4,g_sw,x_130,y_144,size_24";

        }else{
            $res = $this->spuRepository->mpQRCode("spuId={$request->spuId}&skuId={$request->skuId}",[
                'page'  => $request->type == 'common' ? 'pages/details/details' : 'pages/limitdetails/limitdetails',
                'width' => 160
            ]);
            $qrcode =  str_replace(array('+','/','='), array('-','_',''), base64_encode($res));
            $v2Base64 = str_replace(array('+','/','='), array('-','_',''), base64_encode("V2价格:"));
            $v3Base64 = str_replace(array('+','/','='), array('-','_',''), base64_encode("V3价格:"));


            $v2PriceBase64= str_replace(array('+','/','='), array('-','_',''), base64_encode("￥{$vip2Price}元"));
            $v3PriceBase64= str_replace(array('+','/','='), array('-','_',''), base64_encode("￥{$vip3Price}元"));

            // 拼接分享需要的图片的路径
            $waterImagePath =
                "https://fenithcdn.oss-cn-shanghai.aliyuncs.com/".
                "upload/back.jpg?x-oss-process=image/resize,m_fixed,w_750,h_1148,limit_0/".
                "watermark,image_{$urlSafeBase64Image},g_center,voffset_152,w_580,h_580/".
                "watermark,image_{$qrcode},g_se,x_62,y_224,w_160,h_160/".
                "watermark,text_{$spuNameBase641},color_444444,g_sw,x_132,y_336,size_32/".
                $tr.
                "watermark,text_{$v2Base64},color_444444,g_sw,x_62,y_200,size_32/".
                "watermark,text_{$v2PriceBase64},color_FD4577,g_sw,x_180,y_200,size_32/".
                "watermark,text_{$v3Base64},color_444444,g_sw,x_62,y_144,size_32/".
                "watermark,text_{$v3PriceBase64},color_FD4577,g_sw,x_180,y_144,size_32";
        }
        return [
            'errno' => 0,
            'status' => 1,
            'data' => [
                'image' => $waterImagePath
            ]
        ];
    }
}
