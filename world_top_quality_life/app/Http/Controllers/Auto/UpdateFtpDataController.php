<?php

namespace App\Http\Controllers\Auto;

use App\Order;
use App\SendOrder;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Anchu\Ftp\Facades\Ftp;
use Illuminate\Support\Facades\Log;


//抓取ftp 获取
class UpdateFtpDataController extends Controller
{

    //更新包裹
    function updateTrackingList(){
        //找没有Delivered 的标记

        $model_order = new Order();

        $send_order = new SendOrder();
        //Log::info('updateTrackingList start');
        for($i=0;$i<=99999999;$i+=10){
            sleep(1);
            $packages = DB::table('packages') -> where([
                'is_end_update' => 0,
                'flag' => 0
            ])
                -> where('wuliu_num','<>','')
                -> where('created_at','>=',strtotime('2018-11-08'))
                -> offset($i)
                -> limit(10)
                -> orderBy('id','desc') ->get();
            if(!count($packages)){
                //Log::info('updateTrackingList end');
                echo 'updateTrackingList end';
                exit;
            }
            //查找融通接口  获取跟踪轨迹
            foreach($packages as $vo){

                $trackingList = $model_order -> getTrackingList($vo -> wuliu_num);
                if($trackingList){
                    $json_decode_data = json_decode($trackingList,true);

                    if(isset($json_decode_data['Code']) && $json_decode_data['Code'] == 200){
                        //直接更新Data
                        DB::table('packages') -> where([
                            'id' => $vo -> id
                        ]) -> update([
                            'updated_at' => time(),
                            'trackingList' => json_encode($json_decode_data['Data'])
                        ]);

                        //判断下Data 里 是否有存在Delivered 如果存在 则停止更新
                        foreach($json_decode_data['Data'] as $value){
                            //如果含有 inscanned 就把此包裹的 pass_mark 标记为1 ，然后 计算此包裹的交货单的过机通过率

                            if(strstr(strtolower($value['Context']),'inscanned')){
                                $send_order -> updatePassNumber($vo -> id);
                            }

                            if($value['Context'] == 'Delivered'){
                                //停止更新
                                DB::table('packages') -> where([
                                    'id' => $vo -> id
                                ]) -> update([
                                    'is_end_update' => 1
                                ]);
                                break;
                            }
                        }


                        //识别最后一条内容， 取最新状态
                        $end = end($json_decode_data['Data']);
                        $trackingStatus = '';
                        if(strstr(strtolower($end['Context']),'created')){
                            $trackingStatus = 'created';
                        }elseif(strstr(strtolower($end['Context']),'exported')){
                            $trackingStatus = 'exported';
                        }elseif(strstr(strtolower($end['Context']),'arrived') && strstr(strtolower($end['Context']),'destination') ){
                            $trackingStatus = 'arrived at destination';
                        }elseif(strstr(strtolower($end['Context']),'arrived') && strstr(strtolower($end['Context']),'processing') ){
                            $trackingStatus = 'arrived at processing centre';
                        }elseif(strstr(strtolower($end['Context']),'arrived') && strstr(strtolower($end['Context']),'warehouse') ){
                            $trackingStatus = 'arrived at warehouse';
                        }elseif(strstr(strtolower($end['Context']),'inscanned')){
                            $trackingStatus = 'inscanned';
                        }elseif(strstr(strtolower($end['Context']),'clearance')){
                            $trackingStatus = 'clearance';
                        }elseif(strstr(strtolower($end['Context']),'submitted')){
                            $trackingStatus = 'submitted';
                        }elseif(strstr(strtolower($end['Context']),'accepted')){
                            $trackingStatus = 'accepted';
                        }elseif(strstr(strtolower($end['Context']),'despatch')){
                            $trackingStatus = 'despatched';
                        }elseif(strstr(strtolower($end['Context']),'completed')){
                            $trackingStatus = 'completed';
                        }elseif(strstr(strtolower($end['Context']),'delivery')){
                            $trackingStatus = 'delivery';
                        }elseif(strstr(strtolower($end['Context']),'delivered')){
                            $trackingStatus = 'delivered';
                        }elseif(strstr(strtolower($end['Context']),'handed')){
                            $trackingStatus = 'handed';
                        }


                        //update by 2019-03-25
                        elseif(strstr(strtolower($end['Context']),'held by customs')){
                            $trackingStatus = 'held by customs';
                        }elseif(strstr(strtolower($end['Context']),'shipment rejected')){
                            $trackingStatus = 'shipment rejected';
                        }elseif(strstr(strtolower($end['Context']),'shipment is ready to returned to sender')){
                            $trackingStatus = 'shipment is ready to returned to sender';
                        }elseif(strstr(strtolower($end['Context']),'shipment returned')){
                            $trackingStatus = 'shipment returned';
                        }elseif(strstr(strtolower($end['Context']),'inward office of exchange')){
                            $trackingStatus = 'inward office of exchange';
                        }

                        if($trackingStatus){
                            DB::table('packages') -> where([
                                'id' => $vo -> id
                            ]) -> update([
                                'trackingStatus' => $trackingStatus
                            ]);
                        }



                    }
                }


                /*
                //先判断下 时间过去多久了
                //Log::info('updateTrackingList_'.$vo -> wuliu_num);
                $apiRes = $model_order -> eExpress_GetTax($vo -> wuliu_num);
                $json_decode_data = json_decode($apiRes,true);
                //Log::info($apiRes);
                if($json_decode_data['Code'] == '200'){
                    //处理数据 更新
                    //存入数据库得换下格式
                    $saveData = [];
                    foreach($json_decode_data['status'] as $key => $value){
                        $saveData[$key]['Time'] = date('Y-m-d H:i:s',strtotime($json_decode_data['date'][$key]));
                        $saveData[$key]['Context'] = $json_decode_data['detail'][$key];
                        $saveData[$key]['Code'] = $value;
                    }
                    //dd($saveData);
                    //dump($saveData);
                    //dump($saveData);
                    krsort($saveData);
                    $key = key($saveData);
                    //第一个值给了状态
                    $status = $saveData[$key];
                    unset($saveData[$key]);

                    //跟新数据
                    DB::table('packages') -> where([
                        'id' => $vo -> id
                    ]) -> update([
                        'updated_at' => time(),
                        'trackingStatus' => $status['Code'],
                        'trackingList' => json_encode($saveData),
                    ]);

                    if(time() - $vo -> created_at >= 84600 * 20){
                        //更新状态为 不可更新
                        //停止更新
                        DB::table('packages') -> where([
                            'id' => $vo -> id
                        ]) -> update([
                            'is_end_update' => 1
                        ]);
                    }

                }
                */




                //dd($saveData);
                //dd($saveData);
                //dd($apiRes);



                /*
                $trackingList = $model_order -> getTrackingList($vo -> wuliu_num);
                if($trackingList){
                    $json_decode_data = json_decode($trackingList,true);

                    if($json_decode_data['Code'] == 200){
                        //直接更新Data
                        DB::table('packages') -> where([
                            'id' => $vo -> id
                        ]) -> update([
                            'updated_at' => time(),
                            'trackingList' => json_encode($json_decode_data['Data'])
                        ]);

                        //判断下Data 里 是否有存在Delivered 如果存在 则停止更新
                        foreach($json_decode_data['Data'] as $value){
                            if($value['Context'] == 'Delivered'){
                                //停止更新
                                DB::table('packages') -> where([
                                    'id' => $vo -> id
                                ]) -> update([
                                    'is_end_update' => 1
                                ]);
                                break;
                            }
                        }

                    }
                }
                */



            }

            //dd(1111);

        }











        //ftp 获取的方法 -- 暂时先不用
        exit;
        $files  =  FTP :: connection() -> getDirListing('./Tracking');
        if(!count($files)){
            echo 'nofile';
            exit;
        }
        $data = FTP::connection() -> readFile($files[0]);

        //json 解密
        $json_decode_data = json_decode($data,true);


        //更新我们的每个包裹
        foreach($json_decode_data as $vo){
            dd($vo);
            $package = DB::table('packages') -> where([
                'wuliu_num' => $vo['trackingNo'],
                'flag' => 0
            ]) -> first();
            if(!$package){
                continue;
            }
            //更新json
            if(!$package -> trackingList){
                //没有就新增
                DB::table('packages') -> where([
                    'id' => $package -> id
                ]) -> update([
                    'trackingList' => json_encode($vo['trackingList'])
                ]);
            }else{
                //取出来 更新
                $json_data_temp = json_decode($package -> trackingList,true);

                $json_data_temp[] = $vo['trackingList'];

                DB::table('packages') -> where([
                    'id' => $package -> id
                ]) -> update([
                    'trackingList' => json_encode($json_data_temp)
                ]);
            }


        }


        //dd($json_decode_data[0]);
    }


    //更新清关状态 放行啥的
    function updateClearStatus(){
        $model_order = new Order();
        for($i=0;$i<=99999999;$i+=100){
            //Log::info($i.'  :clearStatus_auto');
            $packages = DB::table('packages') -> where([
                'is_end_updateClearStatus' => 0,
                'flag' => 0
            ]) -> where('wuliu_num','<>','')  -> offset($i) -> limit(100) -> orderBy('id','desc') ->get();

            if(!count($packages)){
                exit;
            }

            foreach($packages as $vo){
                if($vo -> route_id == 8){
                    $res = $model_order -> sendXmlGetTax($vo -> wuliu_num,2);
                }else{
                    $res = $model_order -> sendXmlGetTax($vo -> wuliu_num,1);
                }


                if($res){
                    //dd($res);
                    DB::table('packages') -> where([
                        'id' => $vo -> id
                    ]) -> update([
                        'clear_status' => $res,
                        'updated_at' => time()
                    ]);

                    if($res == '放行' || $res == '退运'){
                        //结束更新
                        DB::table('packages') -> where([
                            'id' => $vo -> id
                        ]) -> update([
                            'is_end_updateClearStatus' => 1,
                            'updated_at' => time()
                        ]);
                    }
                }
            }



        }
    }


}
