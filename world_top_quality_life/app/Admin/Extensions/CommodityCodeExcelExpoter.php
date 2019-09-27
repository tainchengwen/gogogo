<?php

/**
 * Created by PhpStorm.
 * User: richer
 * Date: 2018/5/20
 * Time: 15:48
 */
namespace App\Admin\Extensions;

use App\CommodityCode;
use App\GoodsList;
use Encore\Admin\Grid;
use Encore\Admin\Grid\Exporters\AbstractExporter;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class CommodityCodeExcelExpoter extends AbstractExporter
{

    public function __construct(Grid $grid = null)
    {
        parent::__construct($grid);
    }

    public function export()
    {

        $temp = $this -> getData();
        //dd($temp);
        //dd($repertory);

        $cellData = [
            ['商品编码','商品名称','包裹编号','扫描时间']
        ];


        foreach($temp as $vo){
            //查此包裹下的所有商品
            $code_info = CommodityCode::where('fid',$vo['id']) -> get();
            if($code_info){
                foreach($code_info as $value){
                    $goods_info = GoodsList::where('product_id',$value -> code) -> first();
                    $goods_name = isset($goods_info -> product_name)?$goods_info -> product_name:'';


                    $cellData[] = [
                        (string)$value -> code,
                        (string)$goods_name,

                        (string)$vo['number'],
                        (string)$value -> created_at
                    ];
                }
            }
        }


        //dd($cellData);





        //dd($cellData);
        Excel::create(date('Y-m-d-H-i').'商品扫码',function($excel) use ($cellData){
            $excel->sheet('商品扫码', function($sheet) use ($cellData){
                $sheet->rows($cellData);

                for ($i = 1; $i <= count($cellData[0]) + 1; $i++) {
                    //$sheet->setHeight($i, 20);
                    //$sheet->setWidth($this->cellLetter[$i - 1], 30);

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