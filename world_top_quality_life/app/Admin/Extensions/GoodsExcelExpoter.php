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

class GoodsExcelExpoter extends AbstractExporter
{

    protected $file_name;

    protected $sheet_name;

    protected $admin_user_name;


    public function __construct()
    {
        //$this -> admin_user_name = $admin_user_name;
    }


    protected $cellLetter = [
        'A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q',
        'R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD',
        'AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN',
        'AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ'
    ];


    public function export()
    {

        $goods = $this -> getData();


        $cellData[] = [
            '商品编码',
            '商品名称',
            '商品类别',
            '重量',
            '淘宝链接',

            '包装方式',
            '规格',
            '单位',
            '英文名',
            '品牌',


        ];
        foreach($goods as $vo){

            $cellData[] = [
                (string)$vo['product_id']."\t",
                (string)$vo['product_name'],
                (string)$vo['class_name'] ,
                (string)$vo['weight'] ,
                (string)$vo['taobao_url'],
                (string)$vo['product_method'] ,
                (string)$vo['product_format']  ,
                (string)$vo['product_unit']  ,
                (string)$vo['english_name']  ,
                (string)$vo['brand_name'],
            ];
        }





        //dd($cellData);
        Excel::create(date('Y-m-d-H-i').'商品库',function($excel) use ($cellData){
            $excel->sheet('商品库', function($sheet) use ($cellData){
                $sheet->rows($cellData);

                /*
                for ($i = 1; $i <= count($cellData[0]) + 1; $i++) {
                    //$sheet->setHeight($i, 20);
                    $sheet->setWidth($this->cellLetter[$i - 1], 30);

                    /*
                    $sheet->row($i - 1, function ($row) {
                        $row->setAlignment('center');
                        $row->setValignment('center');
                    });
                    */
                //}


            });
        })->export('xlsx');



    }
}