<?php

namespace App\Http\Controllers\ApiShopMp;

use Illuminate\Http\Request;
use App\Repositories\OssRepository;
use Illuminate\Support\Facades\Validator;
use EasyWeChat\Factory;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\ApiShopMp\Controller;

class SharePicController extends Controller
{
    public function __construct()
    {
    }

    //限时特价分享长图
    public function makeLongShareSpecial(Request $request){
        $validator = Validator::make($request->all(), [
            'goods_json'  => 'required|json',
            'need_qrcode' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->errorResponse($request, [], '缺少必要参数');
        }

        $function = "makeSpecialModel1";
        $img = $this->$function($request);
        return $this->successResponse($request, $img);
    }




    //普通商品分享图片
    public function makeLongShareNormal(Request $request){
        $validator = Validator::make($request->all(), [
            'pic'  => 'required',
            'title'       => 'required',
            'price'        =>    '',
            'origin_price' =>    '',
            'need_qrcode' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->errorResponse($request, [], '缺少必要参数');
        }
        $request->type =1 ;
        $function = "makeNormalModel".$request->type;
        $img = $this->$function($request);

       return $this->successResponse($request, $img);
    }


    //商品分享长图模版1
    public function makeNormalModel1($request){
        $config = $this->getPicConfigOne();
        //计算画布高度
        $count = count($request->pic);
        $picHeightTotal= $now =$count==4? 1160: 944;
        $need_qrcode = $request->need_qrcode;
        if($need_qrcode)$picHeightTotal += 170;
        //字体加载
        $fontpath  = $config['fontpath'];  // 普通
        $fontpathB = $config['fontpathB']; //加粗
        $fontpathM = $config['fontpathM']; //中等
        //字体颜色
        $im = imagecreatetruecolor(400, 300);
        $wordColor = imagecolorallocate($im, 25, 25, 25);
        $wordWhite = imagecolorallocate($im, 255, 255, 255);
        $wordRed = imagecolorallocate($im, 255, 77, 98);
        $wordGray = imagecolorallocate($im, 102, 102, 102);


        $new_img = ImageCreateTrueColor(750, $picHeightTotal); // 创建一个画布，作为拼接后的图片
        imagefill($new_img,0,0,$wordWhite);
        $x =30;
        if(mb_strlen($request->title) > 20)$request->title = mb_substr($request->title,0,20).'...';
        if(mb_strlen($request->sub_title) > 33)$request->sub_title = mb_substr($request->sub_title,0,32).'...';
        imagettftext($new_img,24, 0, $x, 70, $wordColor, $fontpath, $request->title);//标题
        imagettftext($new_img,16, 0, $x, 120, $wordGray, $fontpath, $request->sub_title);//副标题

        $yuan = imagettfbbox(18, 0, $fontpathM, '¥');
        $price = imagettfbbox(30, 0, $fontpathM, $request->price);
        imagettftext($new_img,18, 0, $x, 180, $wordRed, $fontpathM, '¥');//¥
        imagettftext($new_img,30, 0, $x+$yuan[2]-$yuan[0], 180, $wordRed, $fontpathM, $request->price);//价格
        $opriceX = $yuan[2]-$yuan[0] + $price[2] -$price[0] +$x + 24;
        $yuanjia = imagettfbbox(16, 0, $fontpath, '原价：');
        imagettftext($new_img,16, 0, $opriceX, 180, $wordGray, $fontpath, '原价：');//原价
        imagettftext($new_img,16, 0, $opriceX+$yuanjia[2]-$yuanjia[0], 180, $wordGray, $fontpath, '¥'.$request->origin_price);//原价
        $origin = imagettfbbox(16, 0, $fontpath, '¥'.$request->origin_price);
        imageline($new_img,$opriceX+$yuanjia[2]-$yuanjia[0],172,$opriceX+$yuanjia[2]-$yuanjia[0] +$origin[2]-$origin[0],172,$wordColor);//中横线

        $border1 =  "http://cdn.fenith.com/longpicture/border1.jpg";
        $borders1 =  getimagesize($border1);
        $borderInfo1 = $this->getImageInfo($borders1,$border1);


        $picArr = $request->pic;
        //首图
        foreach ($picArr as $k=>$v){
            $picArr[$k] = str_replace('https://fenithcdn.oss-cn-shanghai.aliyuncs','http://cdn.fenith',$v);
        }

        $one['info'] = getimagesize($picArr[0]);
        $one['img'] = $this->getImageInfo($one['info'],$picArr[0]);

        imagecopyresampled($new_img, $borderInfo1, $x, 210, 0, 0,
            690, 690,$borders1[0], $borders1[1]);
        imagecopyresampled($new_img, $one['img'], $x+3, 213, 0, 0,684, 684,$one['info'][0], $one['info'][1]);

        if($count == 4){
            $border2 =  "http://cdn.fenith.com/longpicture/border2.jpg";
            $borders2 =  getimagesize($border2);
            $borderInfo2 = $this->getImageInfo($borders2,$border2);
            //二图
            $two['info'] = getimagesize($picArr[1]);
            $two['img'] = $this->getImageInfo($two['info'],$picArr[1]);
            imagecopyresampled($new_img, $borderInfo2, $x, 930, 0, 0,216, 216,$borders2[0], $borders2[1]);
            imagecopyresampled($new_img, $two['img'], $x+3, 933, 0, 0,210, 210,$two['info'][0], $two['info'][1]);
            //三图
            $three['info'] = getimagesize($picArr[2]);
            $three['img'] = $this->getImageInfo($three['info'],$picArr[2]);
            imagecopyresampled($new_img, $borderInfo2, $x+237, 930, 0, 0,216, 216,$borders2[0], $borders2[1]);
            imagecopyresampled($new_img, $three['img'], $x+240, 933, 0, 0,210, 210,$three['info'][0], $three['info'][1]);
            //四图
            $four['info'] = getimagesize($picArr[3]);
            $four['img'] = $this->getImageInfo($four['info'],$picArr[3]);
            imagecopyresampled($new_img, $borderInfo2, $x+237*2, 930, 0, 0,216, 216,$borders2[0], $borders2[1]);
            imagecopyresampled($new_img, $four['img'], $x+237*2+3, 933, 0, 0,210, 210,$four['info'][0], $four['info'][1]);
        }


        //二维码
        if($need_qrcode){
            //获取小程序码
            $res = $this->mpQRCode($request->scene,[
                'page'  => $request->path,
                'width' => 110
            ]);
            $qrcode = "https://fenithlong.oss-cn-hongkong.aliyuncs.com/".$res;
            $qrcodeInfo  = getimagesize($qrcode);
            $qrcodeimg = $this->getImageInfo($qrcodeInfo,$qrcode);

            imagecopyresampled($new_img, $qrcodeimg, 126, $now+30, 0, 0,
                110,110, $qrcodeInfo[0], $qrcodeInfo[1]);
            $text1 = "使用微信扫一扫";
            $text2 = "或者在微信长按识别";
            imagettftext($new_img,20, 0, 340, $now+75, $wordGray, $fontpath, $text1);
            imagettftext($new_img,20, 0, 312, $now+105, $wordGray, $fontpath, $text2);

        }
        $name = strval(microtime());
        $img_path =   public_path('uploads/images/').$name . ".jpg";
        //存放拼接后的图片到本地
//        header("Content-type: image/png");
        imagejpeg($new_img,$img_path);
        $oss=new OssRepository();
        $result=$oss->uploadFile($img_path,'hk');
        unlink($img_path);
        return "https://fenithlong.oss-cn-hongkong.aliyuncs.com/".$result;
    }





    //小程序分享长图限时特价
    public function makeSpecialModel1($request){

        $border =  "http://cdn.fenith.com/longpicture/border.jpg";
        $need_qrcode = $request->need_qrcode ;
        $goods  = array_chunk(json_decode($request->goods_json,1),2);
        $config = $this->getPicConfigOne();
        $goodsLength = count($goods);
        //计算画布高度
        $picHeightTotal = 574 * $goodsLength ;
        if($need_qrcode)$picHeightTotal += 170;
        $picWidth  = 750;
        $picHeight = 574;
        //字体加载
        $fontpath  = $config['fontpath'];  // 普通
        $fontpathB = $config['fontpathB']; //加粗
        $fontpathM = $config['fontpathM']; //中等
        //字体颜色
        $im = imagecreatetruecolor(400, 300);
        $wordColor = imagecolorallocate($im, 25, 25, 25);
        $wordWhite = imagecolorallocate($im, 255, 255, 255);
        $wordRed = imagecolorallocate($im, 225, 77, 98);
        $wordGray = imagecolorallocate($im, 102, 102, 102);
        $wordtitle = imagecolorallocate($im, 68, 68, 68);


        $new_img = ImageCreateTrueColor($picWidth, $picHeightTotal); // 创建一个画布，作为拼接后的图片
        imagefill($new_img,0,0,$wordWhite);
        $goodWidth  = 342;
        $goodHeight = 342;
        $borderWidth  = 346;
        $borderHeight = 346;
        for($i =0 ; $i< $goodsLength ; $i++){
            $img_info[$i]['x'] = 0;
            $img_info[$i]['y'] = $i* $picHeight;
        }
        $borders =  getimagesize($border);
        $borderInfo = $this->getImageInfo($borders,$border);
        foreach ($goods as $k=>$v){
            $dst_r = ImageCreateTrueColor($picWidth,$picHeight); // 获取新图
            imagefill($dst_r,0,0,$wordWhite);

            foreach ($v as $ko=>$vo){
                $v[$ko] = (array)$vo;
            }
            $v = array_values($v);
            foreach ($v as $ko=>$vo){
                $vo['pic'] = str_replace('https://fenithcdn.oss-cn-shanghai.aliyuncs','http://cdn.fenith',$vo['pic']);
                $goodInfo[$ko]['info']  = getimagesize($vo['pic']);
                $goodInfo[$ko]['pic']   = $this->getImageInfo($goodInfo[$ko]['info'],$vo['pic']);
                $goodInfo[$ko]['price'] = $vo['price'];
                $goodInfo[$ko]['price_d'] = $vo['origin_price'];
                $goodInfo[$ko]['name'] = $vo['goods_name'];
            }
            //第一张图
            $borderSize[0]['x'] = 20;
            $borderSize[0]['y'] = 30;
            $goodInfo[0]['x'] = 12;
            $goodInfo[0]['y'] = 42;
            imagecopyresampled($dst_r, $borderInfo, $borderSize[0]['x'], $borderSize[0]['y'], 0, 0,
                $borderWidth, $borderHeight,$borders[0], $borders[1]);

            imagecopyresampled($dst_r, $goodInfo[0]['pic'], $goodInfo[0]['x']+10, $goodInfo[0]['y']-10, 0, 0,
                $goodWidth, $goodHeight,$goodInfo[0]['info'][0], $goodInfo[0]['info'][1]);
            //第一张图文字
            $goodInfo[0]['title'] = $this->getTitle($goodInfo[0]['name']);
            imagettftext($dst_r,22, 0, $goodInfo[0]['x'], 422, $wordtitle, $fontpath, $goodInfo[0]['title']['t1']);
            if($goodInfo[0]['title']['t2']) imagettftext($dst_r,22, 0, $goodInfo[0]['x'], 460, $wordtitle, $fontpath, $goodInfo[0]['title']['t2']);
            //第一张图价格
            $goodInfo[0]['priceP'] = imagettfbbox(16, 0, $fontpathM, '¥');
            $goodInfo[0]['priceYuan'] = $goodInfo[0]['x'] +  $goodInfo[0]['priceP'][2]-$goodInfo[0]['priceP'][0];
            imagettftext($dst_r,33, 0, $goodInfo[0]['priceYuan'] , 530, $wordRed, $fontpathB, $goodInfo[0]['price']);
            imagettftext($dst_r,16, 0, $goodInfo[0]['x'], 528, $wordRed, $fontpathM, '¥');
            //原价
            $goodInfo[0]['price_d'] =  "原价：¥{$goodInfo[0]['price_d']}";
            $goodInfo[0]['pricePd'] = imagettfbbox(16, 0, $fontpath, $goodInfo[0]['price_d']);
            imageline($dst_r,$goodWidth+$goodInfo[0]['x']-($goodInfo[0]['pricePd'][2]-$goodInfo[0]['pricePd'][0]),522,$goodWidth+$goodInfo[0]['x'],522,$wordColor);
            imagettftext($dst_r,16, 0, $goodWidth+$goodInfo[0]['x']-($goodInfo[0]['pricePd'][2]-$goodInfo[0]['pricePd'][0]), 529, $wordColor, $fontpath, $goodInfo[0]['price_d']);
            if(count($v) > 1){
                //第二张图
                $goodInfo[1]['x'] = 386;
                $goodInfo[1]['y'] = 32;
                $borderSize[1]['x'] = 384;
                $borderSize[1]['y'] = 30;
                imagecopyresampled($dst_r, $borderInfo, $borderSize[1]['x'], $borderSize[1]['y'], 0, 0,
                    $borderWidth, $borderHeight,$borders[0], $borders[1]);
                imagecopyresampled($dst_r, $goodInfo[1]['pic'], $goodInfo[1]['x'], $goodInfo[1]['y'], 0, 0,
                    $goodWidth, $goodHeight,$goodInfo[1]['info'][0], $goodInfo[1]['info'][1]);
                //第二张图文字
                $goodInfo[1]['title'] = $this->getTitle($goodInfo[1]['name']);
                imagettftext($dst_r,22, 0, $goodInfo[1]['x'], 422, $wordtitle, $fontpath, $goodInfo[1]['title']['t1']);
                if($goodInfo[1]['title']['t2']) imagettftext($dst_r,22, 0, $goodInfo[1]['x'], 460, $wordtitle, $fontpath, $goodInfo[1]['title']['t2']);
                //第二张图价格

                $goodInfo[1]['priceP'] = imagettfbbox(16, 0, $fontpathM, '¥');
                $goodInfo[1]['priceYuan'] = $goodInfo[1]['x'] +  $goodInfo[1]['priceP'][2]-$goodInfo[1]['priceP'][0];
                imagettftext($dst_r,33, 0, $goodInfo[1]['priceYuan'] , 530, $wordRed, $fontpathB, $goodInfo[1]['price']);
                imagettftext($dst_r,16, 0, $goodInfo[1]['x'], 528, $wordRed, $fontpathM, '¥');
                //原价
                $goodInfo[1]['price_d'] =  "原价：¥{$goodInfo[1]['price_d']}";
                $goodInfo[1]['pricePd'] = imagettfbbox(16, 0, $fontpath, $goodInfo[1]['price_d']);
                imageline($dst_r,$picWidth -20-($goodInfo[1]['pricePd'][2]-$goodInfo[1]['pricePd'][0]),522,$picWidth -20,522,$wordColor);
                imagettftext($dst_r,16, 0, $picWidth -20-($goodInfo[1]['pricePd'][2]-$goodInfo[1]['pricePd'][0]), 529, $wordColor, $fontpath, $goodInfo[1]['price_d']);
            }

            imagecopyresampled($new_img, $dst_r, $img_info[$k]['x'], $img_info[$k]['y'], 0, 0,
                $picWidth, $picHeight, $picWidth, $picHeight);
        }
        /********                                 尾部                           **********/
        if($need_qrcode){
            //获取小程序码
            $res = $this->mpQRCode($request->scene,[
                'page'  => $request->path,
                'width' => 110
            ]);
            $qrcode = "https://fenithlong.oss-cn-hongkong.aliyuncs.com/".$res;
            $qrcodeInfo  = getimagesize($qrcode);
            $qrcodeimg = $this->getImageInfo($qrcodeInfo,$qrcode);
            imagecopyresampled($new_img, $qrcodeimg, 126, 574 * $goodsLength + 30, 0, 0,
                110,110, $qrcodeInfo[0], $qrcodeInfo[1]);
            $text1 = "使用微信扫一扫";
            $text2 = "或者在微信长按识别";
            imagettftext($new_img,20, 0, 340, 574 * $goodsLength + 80, $wordGray, $fontpath, $text1);
            imagettftext($new_img,20, 0, 312, 574 * $goodsLength + 120, $wordGray, $fontpath, $text2);

        }

        //获取时间戳，以时间戳的名字存放
        $name = strval(microtime());
        $img_path =   public_path('uploads/images/').$name . ".jpg";
        //存放拼接后的图片到本地

//        header("Content-type: image/png");
        imagejpeg($new_img,$img_path);
        $oss=new OssRepository();
        $result=$oss->uploadFile($img_path,'hk');
        unlink($img_path);
        return "https://fenithlong.oss-cn-hongkong.aliyuncs.com/".$result;
    }


    private function getImageInfo($image,$pic){
        switch($image[2]) {
            case 1:
                $img_r= imagecreatefromgif($pic);
                break;
            case 2:
                $img_r= imagecreatefromjpeg($pic);
                break;
            case 3:
                $img_r= imagecreatefrompng($pic);
                break;
        }
        return $img_r;
    }

    private function getPicConfigOne(){
        return [
            'fontpath' => public_path('/font/syb.ttf'),  // 普通
            'fontpathB'=> public_path('/font/sybb.otf'), //加粗
            'fontpathM'=> public_path('/font/sym.otf'), //中等
        ];
    }

    //文字标题处理
    private function getTitle($goods_name){
        $spuName1 = $spuName = $goods_name;
        $spuName2 = '';
        if(mb_strlen($spuName) >10) {
            $spuName1 = mb_substr($spuName, 0, 10);
            $spuName2 = mb_substr($spuName,10,10).'';
            if(mb_strlen($spuName)>20){
                $spuName2 = mb_substr($spuName,10,10).'...';
            }
        }
        return [
            't1' =>$spuName1,
            't2' =>$spuName2,
        ];
    }


    /**
     * 生成小程序二维码
     */
    public function mpQRCode($scene,$path){
        //不存在则生成新的码
        $wxConfig = [
            'app_id' => env('MINI_TMP_SHOP_APPID'),
            'secret' => env('MINI_TMP_SHOP_SECRET'),
            'response_type' => 'array'
        ];
        $info=DB::table('erp_setting')->where('business_id',49)->first();

        $ossLogoName = explode(":",$info->logo)[1]; // logo的oss路径名称
        $app = Factory::miniProgram($wxConfig);
        $response = $app->app_code->getUnlimit($scene,$path);
        if ($response instanceof \EasyWeChat\Kernel\Http\StreamResponse) {
            $filename = $response->save(public_path('uploads/images'));
            //上传oss
            $oss=new OssRepository();
            $result=$oss->uploadFile(public_path('uploads/images').'/'.$filename,'hk');
            //删除本地文件
            unlink(public_path('uploads/images').'/'.$filename);

            return $result;
        }
    }


}
