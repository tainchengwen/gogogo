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

class CheckGoodsExcelExpoter extends AbstractExporter
{



    protected $cellLetter = [
        'A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q',
        'R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD',
        'AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN',
        'AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ'
    ];


    public function export()
    {

        $orders = $this -> getData();
        $cellData[] = [
            '盘点编号',
            '包裹编号',
            '扫描日期'
        ];
        foreach($orders as $vo){
            $check_goods_detail = DB::table('check_goods_detail')
                -> where([
                    'check_goods_id' => $vo['id'],
                    'flag' => 0
                ]) -> get();
            if($check_goods_detail){
                foreach($check_goods_detail as $value){

                    $cellData[] = [
                        (string)$vo['goods_number'],
                        (string)$value -> scan_goods_number,
                        (string)date('Y-m-d H:i',$value -> created_at),
                    ];
                }
            }
        }

        //dd($cellData);
        Excel::create(date('Y-m-d-H-i').'导出盘点数据',function($excel) use ($cellData){
            $excel->sheet('盘点数据', function($sheet) use ($cellData){
                $sheet->rows($cellData);
            });
        })->export('xlsx');



    }
}