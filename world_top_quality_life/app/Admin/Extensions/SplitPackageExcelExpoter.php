<?php

/**
 * Created by PhpStorm.
 * User: richer
 * Date: 2018/5/20
 * Time: 15:48
 */
namespace App\Admin\Extensions;

use App\PrintSequeue;
use Encore\Admin\Grid\Exporters\AbstractExporter;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class SplitPackageExcelExpoter extends AbstractExporter
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


        $sp_packages = $this -> getData();
        //dd($repertory);

        $cellData = [
            ['拆分后单号','重量','拆单前单号','是否贴单','贴单日期']
        ];




        foreach($sp_packages as $vo){
            $print_info = PrintSequeue::where('number',$vo['sp_numbers']) -> where('type',2) -> first();



            $cellData[] = [
                (string)$vo['sp_numbers'],
                (string)$vo['weight'],
                (string)$vo['package_wuliu_num'],
                (string)$print_info?'已贴单':'未贴单',
                (string)$print_info?$print_info -> created_at:''
            ];
        }




        //dd($cellData);
        Excel::create(date('Y-m-d-H-i').'导出拆单单号',function($excel) use ($cellData){
            $excel->sheet('导出拆单单号', function($sheet) use ($cellData){
                $sheet->rows($cellData);

                for ($i = 1; $i <= count($cellData[0]) + 1; $i++) {
                }

            });
        })->export('xlsx');



    }
}