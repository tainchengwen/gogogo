<?php

/**
 * Created by PhpStorm.
 * User: richer
 * Date: 2018/5/20
 * Time: 15:48
 */
namespace App\Admin\Extensions;

use App\Configure;
use Encore\Admin\Grid\Exporters\AbstractExporter;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class InvoiceExcelExpoter extends AbstractExporter
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


        $data = $this -> getData();
        //dd($repertory);

        $cellData = [
            ['invoice编号','发件人','发件手机','发件公司','发件地址','发件邮政编码'],
        ];

        foreach($data as $k =>  $vo){
            $cellData[] = [
                (string)$vo['id'].'',
                (string)$vo['send_name'].'',
                (string)$vo['send_tel'].'',
                (string)$vo['send_company'].'',
                (string)$vo['send_address'].'',
                (string)$vo['send_zipCode'].'',
            ];
        }
        $cellData[] = [
            '','','','',''
        ];

        $cellData = [
            ['invoice编号','提货人','提货人手机','提货公司','提货地址','提货邮政编码'],
        ];

        foreach($data as $k =>  $vo){
            $cellData[] = [
                (string)$vo['id'].'',
                (string)$vo['take_name'].'',
                (string)$vo['take_tel'].'',
                (string)$vo['take_company'].'',
                (string)$vo['take_address'].'',
                (string)$vo['take_zipCode'].'',
            ];
        }
        $cellData[] = [
            '','','','',''
        ];

        $cellData[] = [
            'invoice编号','收件人','收件手机','收件公司','收件地址','收件邮政编码'
        ];

        foreach($data as $k => $vo){
            $cellData[] = [
                (string)$vo['id'].'',
                (string)$vo['receive_name'].'',
                (string)$vo['receive_tel'].'',
                (string)$vo['receive_company'].'',
                (string)$vo['receive_address'].'',
                (string)$vo['receive_zipCode'].'',
            ];
        }

        $cellData[] = [
            '','','','',''
        ];


        $cellData[] = [
            'invoice编号','币种','重量','体积重','面积（平方厘米）','件数'
        ];

        $currency_config = Configure::getCurrency();
        foreach($data as $k => $vo){
            $cellData[] = [
                (string)$vo['id'].'',
                (string)$currency_config[$vo['currency_type']].'',
                (string)$vo['weight'].'',
                (string)$vo['volume_weight'].'',
                (string)$vo['area'].'',
                (string)$vo['number'].'',
            ];
        }

        $cellData[] = [
            '','','','',''
        ];

        $cellData[] = [
            'invoice编号','商品名称','商品编号','商品数量','商品价值',''
        ];
        foreach($data as $k => $vo){
            //找invoice 中的 商品明细
            $invoice_info = DB::table('invoice_info') -> where([
                'invoice_id' => $vo['id']
            ]) -> get();
            foreach($invoice_info as $value){
                
                $cellData[] = [
                    (string)$vo['id'].'',
                    (string)$value -> product_name,
                    (string)$value -> product_number,
                    (string)$value -> product_count,
                    (string)$value -> product_price,
                    ''
                ];
            }
        }








        //dd($cellData);
        Excel::create(date('Y-m-d-H-i').'导出INVOICE',function($excel) use ($cellData){
            $excel->sheet('导出INVOICE', function($sheet) use ($cellData){
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