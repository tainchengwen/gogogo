<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\OssRepository;
use App\Repositories\SPURepository;
use Validator;
use DB;
use Carbon\Carbon;
class LongPicController extends Controller
{
    public function __construct(SPURepository $spuRepository)
    {
        $this->spuRepository = $spuRepository;
    }

    //后台上传提交
    public function makeLongPost(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id',
            'goods_json'  => 'required|json',
            'title'       => 'required',
            'sub_title'   => 'required',
            'date'        => 'required',
            'type'        => 'required|numeric',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        DB::beginTransaction();
        try{
            set_time_limit(0);
            $function = "makeLongPicModel".$request->type;
            $img = $this->$function($request);
            DB::table("erp_long_pic")->insert([
                'title'     => $request->title,
                'sub_title' => $request->sub_title,
                'goods_json'=> $request->goods_json,
                'type'      => $request->type,
                'date'      => $request->date,
                'created_at'=> time(),
                'updated_at'=> time(),
                'business_id'=> $request->business_id,
                'image'     => $img,
            ]);
            DB::commit();
            return [
                'code' => 0,
                'msg' => '生成成功',
                'img' => $img
            ];
        }catch (\Exception $e){
            DB::rollBack();
            return [
                'code' => 1,
                'msg' => $e ->getMessage()
            ];
        }

    }

    //后台上传提交
    public function makeLongPostEdit(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id',
            'goods_json'  => 'required|json',
            'title'       => 'required',
            'sub_title'   => 'required',
            'date'        => 'required',
            'type'        => 'required|numeric',
            'id'          => 'required|numeric|exists:erp_long_pic,id',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        DB::beginTransaction();
        try{
            set_time_limit(0);
            $function = "makeLongPicModel".$request->type;
            $img = $this->$function($request);
            DB::table("erp_long_pic")
                ->where('id',$request->id)
                ->update([
                'title'     => $request->title,
                'sub_title' => $request->sub_title,
                'goods_json'=> $request->goods_json,
                'type'      => $request->type,
                'date'      => $request->date,
                'updated_at'=> time(),
                'image'     => $img,
            ]);
            DB::commit();
            return [
                'code' => 0,
                'msg' => '修改成功',
                'img' => $img
            ];
        }catch (\Exception $e){
            DB::rollBack();
            return [
                'code' => 1,
                'msg' => $e ->getMessage()
            ];
        }

    }
    //后台上传提交
    public function makeLongPostDel(Request $request){
        $validator = Validator::make($request->all(), [
            'id'          => 'required|numeric|exists:erp_long_pic,id',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        DB::table("erp_long_pic")
            ->where('id',$request->id)
            ->delete();
        return [
            'code' => 0,
            'msg' => '删除成功'
        ];

    }

    //后台上传提交
    public function makeLongPostList(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $list = DB::table("erp_long_pic")
            ->where('business_id',$request->business_id)
            ->orderBy('id','desc')
            ->paginate(20);
        return $list;

    }




    public function makeLongPicModel1($request){
        $config = $this->getPicConfigOne();
        $top = $config['top'];
        $mid = $config['mid'];
        $wei = $config['wei'];
        $date = date("Y",time()).'-'.$request->date;
        $goodsGet = $this->spuRepository->fetchSpecialInfo($date);


        $goods = $goodsGet->chunk(2)->toArray();
        $toptext =[
            $request->date.'日拼单',
            $request->title,
            $request->sub_title,
        ];
        $topSize = getimagesize($top);
        $midSize = getimagesize($mid);
        $weiSize = getimagesize($wei);
        $goodsLength = count($goods);
        //计算画布高度
        $picHeight = $topSize[1] + $weiSize[1] + $goodsLength * $midSize[1];

        //字体加载
        $fontpath  = $config['fontpath'];  // 普通
        $fontpathB = $config['fontpathB']; //加粗
        $fontpathM = $config['fontpathM']; //中等
        //字体颜色
        $im = imagecreatetruecolor(400, 300);
        $wordColor = imagecolorallocate($im, 25, 25, 25);
        $wordWhite = imagecolorallocate($im, 255, 255, 255);
        $wordRed = imagecolorallocate($im, 225, 27, 27);
        $wordGray = imagecolorallocate($im, 153, 153, 153);


        $new_img = ImageCreateTrueColor($topSize[0], $picHeight); // 创建一个画布，作为拼接后的图片


        /********                                 头部                        *************/
        $imgTop = $this->getImageInfo($topSize,$top);
        imagecopyresampled($new_img, $imgTop, 0, 0, 0, 0,
            $topSize[0], $topSize[1], $topSize[0], $topSize[1]);
        //获取文字虚拟方框
        $top1 = imagettfbbox(25, 0, $fontpath, $toptext[0]);   //得到字符串虚拟方框四个点的坐标
        $x1= ($topSize[0] - ($top1[2] - $top1[0]))/2;
        $top2 = imagettfbbox(45, 0, $fontpath, $toptext[1]);   //得到字符串虚拟方框四个点的坐标
        $x2= ($topSize[0] - ($top2[2] - $top2[0]))/2;
        $top3 = imagettfbbox(23, 0, $fontpath, $toptext[2]);   //得到字符串虚拟方框四个点的坐标
        $x3= ($topSize[0] - ($top3[2] - $top3[0]))/2;

        imagettftext($new_img,25, 0, $x1, 63, $wordColor, $fontpath, $toptext[0]);
        imagettftext($new_img,45, 0, $x2, 143, $wordColor, $fontpath, $toptext[1]);
        imagettftext($new_img,23, 0, $x3, 206, $wordWhite, $fontpath, $toptext[2]);


        /********                                 中部                           **********/
        $imgMid = $this->getImageInfo($midSize,$mid);
        for($i =0 ; $i< $goodsLength ; $i++){
            $img_info[$i]['height'] = $midSize[1];
            $img_info[$i]['width'] = $midSize[0];
            $img_info[$i]['x'] = 0;
            $img_info[$i]['y'] = $topSize[1] +$i* $midSize[1];
        }
        $goodWidth  = 242;
        $goodHeight = 242;
        foreach ($goods as $k=>$v){
            $dst_r = ImageCreateTrueColor($midSize[0],$midSize[1]); // 获取新图
            imagecopyresampled($dst_r, $imgMid, 0, 0, 0, 0,
                $img_info[$k]['width'], $img_info[$k]['height'], $img_info[$k]['width'], $img_info[$k]['height']);
            foreach ($v as $ko=>$vo){
                $v[$ko] = (array)$vo;
            }
            $v = array_values($v);
            foreach ($v as $ko=>$vo){
                $goodInfo[$ko]['info']  = getimagesize($vo['image']);
                $goodInfo[$ko]['pic']   = $this->getImageInfo($goodInfo[$ko]['info'],$vo['image']);
                $goodInfo[$ko]['price'] = $vo['price'];
                $goodInfo[$ko]['price_d'] = $vo['price_d'];
                $goodInfo[$ko]['name'] = $vo['showname'];
            }
            //第一张图
            $goodInfo[0]['x'] = 60;
            $goodInfo[0]['y'] = 20;
            imagecopyresampled($dst_r, $goodInfo[0]['pic'], $goodInfo[0]['x'], $goodInfo[0]['y'], 0, 0,
                $goodWidth, $goodHeight,$goodInfo[0]['info'][0], $goodInfo[0]['info'][1]);
            //第一张图文字
            $goodInfo[0]['title'] = $this->getTitle($goodInfo[0]['name']);
            imagettftext($dst_r,16, 0, $goodInfo[0]['x'], 290, $wordColor, $fontpathM, $goodInfo[0]['title']['t1']);
            if($goodInfo[0]['title']['t2']) imagettftext($dst_r,16, 0, $goodInfo[0]['x'], 320, $wordColor, $fontpathM, $goodInfo[0]['title']['t2']);
            //第一张图价格
            $goodInfo[0]['priceP'] = imagettfbbox(33, 0, $fontpathB, $goodInfo[0]['price']);
            $goodInfo[0]['priceX'] = ($goodWidth-($goodInfo[0]['priceP'][2]-$goodInfo[0]['priceP'][0])) /2 +50;
            $goodInfo[0]['priceYuan'] = $goodInfo[0]['x'] +  $goodInfo[0]['priceP'][2]-$goodInfo[0]['priceP'][0];
            imagettftext($dst_r,33, 0, $goodInfo[0]['x'], 380, $wordRed, $fontpathB, $goodInfo[0]['price']);
            imagettftext($dst_r,16, 0, $goodInfo[0]['priceYuan'], 380, $wordColor, $fontpathM, '元');

            //原价
            $goodInfo[0]['price_d'] =  "原价:{$goodInfo[0]['price_d']}元";
            $goodInfo[0]['pricePd'] = imagettfbbox(16, 0, $fontpath, $goodInfo[0]['price_d']);
            imageline($dst_r,$goodInfo[0]['x']+242-($goodInfo[0]['pricePd'][2]-$goodInfo[0]['pricePd'][0]),373,$goodInfo[0]['x']+242-($goodInfo[0]['pricePd'][2]-$goodInfo[0]['pricePd'][0])+($goodInfo[0]['pricePd'][2]-$goodInfo[0]['pricePd'][0]),373,$wordColor);
            imagettftext($dst_r,16, 0, $goodInfo[0]['x']+242-($goodInfo[0]['pricePd'][2]-$goodInfo[0]['pricePd'][0]), 380, $wordColor, $fontpath, $goodInfo[0]['price_d']);
            if(count($v) > 1){
                //第二张图
                $goodInfo[1]['x'] = 435;
                $goodInfo[1]['y'] = 20;
                imagecopyresampled($dst_r, $goodInfo[1]['pic'], $goodInfo[1]['x'], $goodInfo[1]['y'], 0, 0,
                    $goodWidth, $goodHeight,$goodInfo[1]['info'][0], $goodInfo[1]['info'][1]);
                //第二张图文字
                $goodInfo[1]['title'] = $this->getTitle($goodInfo[1]['name']);
                imagettftext($dst_r,16, 0, $goodInfo[1]['x'], 290, $wordColor, $fontpathM, $goodInfo[1]['title']['t1']);
                if($goodInfo[1]['title']['t2']) imagettftext($dst_r,16, 0, $goodInfo[1]['x'], 320, $wordColor, $fontpathM, $goodInfo[1]['title']['t2']);
                //第二张图价格

                $goodInfo[1]['priceP'] = imagettfbbox(33, 0, $fontpathB, $goodInfo[1]['price']);
                $goodInfo[1]['priceX'] = ($goodWidth-($goodInfo[1]['priceP'][2]-$goodInfo[1]['priceP'][0])) /2 +$goodInfo[1]['x']-10;
                $goodInfo[1]['priceYuan'] = $goodInfo[1]['x'] +  $goodInfo[1]['priceP'][2]-$goodInfo[1]['priceP'][0];
                imagettftext($dst_r,33, 0, $goodInfo[1]['x'], 380, $wordRed, $fontpathB, $goodInfo[1]['price']);
                imagettftext($dst_r,16, 0, $goodInfo[1]['priceYuan'], 380, $wordColor, $fontpathM, '元');


                //原价
                $goodInfo[1]['price_d'] =  "原价:{$goodInfo[1]['price_d']}元";
                $goodInfo[1]['pricePd'] = imagettfbbox(16, 0, $fontpath, $goodInfo[1]['price_d']);
                imageline($dst_r,$goodInfo[1]['x']+242-($goodInfo[1]['pricePd'][2]-$goodInfo[1]['pricePd'][0]),373,$goodInfo[1]['x']+242,373,$wordColor);
                imagettftext($dst_r,16, 0, $goodInfo[1]['x']+242-($goodInfo[1]['pricePd'][2]-$goodInfo[1]['pricePd'][0]), 380, $wordColor, $fontpath, $goodInfo[1]['price_d']);
            }

            imagecopyresampled($new_img, $dst_r, $img_info[$k]['x'], $img_info[$k]['y'], 0, 0,
                $img_info[$k]['width'], $img_info[$k]['height'], $img_info[$k]['width'], $img_info[$k]['height']);
        }

        /********                                 尾部                           **********/
        $imgWei = $this->getImageInfo($weiSize,$wei);
        $weiHeight = $topSize[1] +$goodsLength * $midSize[1];
        imagecopyresampled($new_img, $imgWei, 0, $weiHeight, 0, 0,
            $weiSize[0], $weiSize[1], $weiSize[0], $weiSize[1]);

        //获取时间戳，以时间戳的名字存放
        $name = strval(time());
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
            'top'      => "http://cdn.fenith.com/longpicture/top.jpg",
            'mid'      => "http://cdn.fenith.com/longpicture/mid.jpg",
            'wei'      => "http://cdn.fenith.com/longpicture/wei.png",
            'fontpath' => public_path('/font/syb.ttf'),  // 普通
            'fontpathB'=> public_path('/font/sybb.otf'), //加粗
            'fontpathM'=> public_path('/font/sym.otf'), //中等
        ];
    }

    //文字标题处理
    private function getTitle($goods_name){
        $spuName1 = $spuName = $goods_name;
        $spuName2 = '';
        if(mb_strlen($spuName) >12) {
            $spuName1 = mb_substr($spuName, 0, 12);
            $spuName2 = mb_substr($spuName,12,12).'';
            if(mb_strlen($spuName)>24){
                $spuName2 = mb_substr($spuName,12,11).'...';
            }
        }
        return [
            't1' =>$spuName1,
            't2' =>$spuName2,
        ];
    }


    //限时特价生成长图
    public function makeSpecialLongPic(Request $request){
        $validator = Validator::make($request->all(), [
            'date' => 'required',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $request->title = '宠爱自己，点滴做起';
        $request->sub_title = '寰球优品生活';
        $request->date = date('m-d',strtotime($request->date));
        return [
            'pic' => $this->makeLongPicModel1($request),
            'code' => 0
        ];
    }

    private function randomModel(){

        $arr = [
            '宠爱自己，点滴做起',
            '精致女孩的生活“仪式感”',
            '敏感脆弱的你，需要呵护',
            '仙女的美丽“外衣”需要各种加持',
            '实用好物大合集，教你玩转时尚圈'
        ];
        $random = mt_rand(0,count($arr)-1);
        return $arr[$random];

    }
}
