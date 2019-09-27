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

class PackageExcelExpoter extends AbstractExporter
{

    protected $file_name;

    protected $sheet_name;

    protected $admin_user_name;


    public function __construct()
    {

    }


    protected $cellLetter = [
        'A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q',
        'R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD',
        'AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN',
        'AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ'
    ];


    public function export()
    {



        $packages = $this -> getData();
        //dd($packages);
        $cellData = [
            ['序号','日期','区域','包裹编号','运单号','HK仓库发出','HK邮局状态','海关状态',  '姓名','电话','申报重量','过机重量']
        ];

        foreach($packages as $key =>  $vo){
            $from_area = DB::table('area_name') -> where([
                'id' => $vo['from_area']
            ]) -> first();
            //dd($from_area);

            $value = $vo['id'];
            //根据package_id 查交货单号
            $batch_info = DB::table('batch_packages') -> where(function($query)use($value){
                $query -> where('package_ids','like','%'.$value.',%');
                $query -> orWhere('package_ids','like','%,'.$value.',%');
                $query -> orWhere('package_ids','like','%,'.$value.'%');
            }) -> first();
            if($batch_info){
                //发货单
                $send_order_info = DB::table('send_order_list') -> where([
                    'id' => $batch_info -> send_order_id
                ]) -> first();
                $send_order_id = $send_order_info -> order_num;
            }else{
                $send_order_id = '';
            }

            $cellData[] = [
                $key+1,
                date('Y-m-d H:i'),
                isset($from_area -> area_name)?$from_area -> area_name:'',
                $vo['package_num'],
                $vo['wuliu_num'],
                $send_order_id,
                '',
                '',
                $vo['name'],
                $vo['tel'] ,
                $vo['weight'],
                ''
            ];
        }





        //dd($cellData);
        Excel::create(date('Y-m-d-H-i').'导出包裹',function($excel) use ($cellData){
            $excel->sheet('导出包裹', function($sheet) use ($cellData){
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