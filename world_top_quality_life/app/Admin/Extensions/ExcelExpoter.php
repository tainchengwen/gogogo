<?php

/**
 * Created by PhpStorm.
 * User: richer
 * Date: 2018/5/20
 * Time: 15:48
 */
namespace App\Admin\Extensions;

use Encore\Admin\Grid\Exporters\AbstractExporter;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ExcelExpoter extends AbstractExporter
{

    protected $file_name;

    protected $sheet_name;

    protected $admin_user_name;


    public function __construct($admin_user_name)
    {
        $this -> admin_user_name = $admin_user_name;
    }


    protected $cellLetter = [
        'A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q',
        'R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD',
        'AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN',
        'AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ'
    ];


    public function export()
    {

        $admin_user_name = $this -> admin_user_name;

        $orders = $this -> getData();
        //分订单导出
        if($admin_user_name == 'admin'){
            $cellData = [
                ['订单日期','订单编号','包裹编号','运单号','客户','收件人','电话','地址','城市','省份','邮编','重量','区域',	'线路','成本价格',	'区域价格','客户价格']
            ];
        }else{
            $cellData = [
                ['订单日期','订单编号','包裹编号','运单号','客户','收件人','电话','地址','城市','省份','邮编','重量','区域',	'线路',	'区域价格','客户价格']
            ];
        }

        foreach($orders as $order){
            $id = $order['id'];
            $order_info = DB::table('order') -> where([
                'id' => $id,
                'flag' => 0
            ]) -> first();

            //客户
            $username = DB::table('wxuser') -> where([
                'id' => $order_info -> userid
            ]) -> first();

            //查找所属区域id
            $from_area = DB::table('area_name') -> where([
                'id' => $order_info -> from_area
            ]) -> first();

            $packages = DB::table('packages') -> where([
                'order_id' => $id,
                'flag' => 0
            ]) -> get();


            $route_config = config('admin.route_setting');




            foreach($packages as $k => $vo){
                //路线名称
                if($vo -> route_id){
                    $route_name = $route_config[$vo -> route_id];
                }else{
                    $route_name = '未知';
                }


                //查看邮编
                $zip_info = DB::table('zips')
                    -> where('city','like','%'.mb_substr(trim($vo -> province),0,2,'utf-8').'%')
                    -> where('province','like','%'.mb_substr(trim($vo -> city),0,2,'utf-8').'%')
                    -> first();

                if(!empty($zip_info)){
                    $zip = $zip_info -> zip_code;
                }else{
                    $zip = $vo -> zip;
                }

                if($admin_user_name == 'admin') {
                    $cellData[] = [
                        (string)date('Y-m-d', $order_info->created_at),
                        (string)$order_info->order_num . '',
                        (string)$vo->package_num . '',
                        (string)$vo->wuliu_num . '',
                        (string)$username->nickname . '',
                        (string)$vo->name . '',
                        (string)$vo->tel . '',
                        (string)$vo->address . '',
                        (string)$vo->city . '',
                        (string)$vo->province . '',
                        (string)$zip . '',
                        (string)$vo->weight . '',
                        (string)$from_area->area_name . '',
                        (string)$route_name . '',
                        (string)$vo->cost . '',
                        (string)$vo->area_price . '',
                        (string)$vo->price . '',

                        //////
                    ];

                }else{
                    $cellData[] = [
                        (string)date('Y-m-d', $order_info->created_at),
                        (string)$order_info->order_num . '',
                        (string)$vo->package_num . '',
                        (string)$vo->wuliu_num . '',
                        (string)$username->nickname . '',
                        (string)$vo->name . '',
                        (string)$vo->tel . '',
                        (string)$vo->address . '',
                        (string)$vo->city . '',
                        (string)$vo->province . '',
                        (string)$zip . '',
                        (string)$vo->weight . '',
                        (string)$from_area->area_name . '',
                        (string)$route_name . '',

                        (string)$vo->area_price . '',
                        (string)$vo->price . '',

                        //////
                    ];
                }

                //dd($cellData);
            }
        }




        //dd($cellData);
        Excel::create(date('Y-m-d-H-i').'导出历史订单',function($excel) use ($cellData){
            $excel->sheet('导出历史订单', function($sheet) use ($cellData){
                $sheet->rows($cellData);

                for ($i = 1; $i <= count($cellData[0]) + 1; $i++) {
                    //$sheet->setHeight($i, 20);
                    $sheet->setWidth($this->cellLetter[$i - 1], 30);

                    /*
                    $sheet->row($i - 1, function ($row) {
                        $row->setAlignment('center');
                        $row->setValignment('center');
                    });
                    */
                }

            });
        })->export('xlsx');



    }
}