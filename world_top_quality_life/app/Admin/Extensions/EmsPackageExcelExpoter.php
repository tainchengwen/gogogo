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

class EmsPackageExcelExpoter extends AbstractExporter
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

        $cellData = [
            [
                '序号',
                '清关状态',
                '放行时间',
                '查验时间',
                '申报税金',
                '实际税金',
                '送货时间',
                '香港仓到货时间',
                '客户单号',
                '面单号',
                '路径',
                '收货人',
                '收货人电话',
                '收货人地址',
                '缴纳税金时间',
            ]
        ];

        $ems_status = config('admin.ems_status');
        foreach($packages as $key =>  $vo){
            //查找这个包裹当时的申报税金
            $goods_paratemer = DB::table('packages_goods_paratemer')
                -> where([
                    'id' => $vo['goods_id']
                ]) -> first();
            //通过税率 查找当时税金
            $goods_tax = DB::table('goods_tax')
                -> whereIn('code',[$goods_paratemer->Tax_code1,$goods_paratemer->Tax_code2,$goods_paratemer->Tax_code3])
                -> get();
            $goods_tax_temp = [];
            foreach($goods_tax as $value){
                $goods_tax_temp[$value -> code] = $value -> tax;
            }

            //计算总税金
            if($goods_paratemer -> s_price1 && $goods_paratemer -> s_price2 && $goods_paratemer -> s_price3){
                $tax_all = round(floatval($goods_paratemer -> s_price1) * floatval($goods_paratemer -> s_pieces1) * floatval($goods_tax_temp[$goods_paratemer -> Tax_code1]) +
                    floatval($goods_paratemer -> s_price2) * floatval($goods_paratemer -> s_pieces2) * floatval($goods_tax_temp[$goods_paratemer -> Tax_code2]) +
                    floatval($goods_paratemer -> s_price3) * floatval($goods_paratemer -> s_pieces3) * floatval($goods_tax_temp[$goods_paratemer -> Tax_code3]),2);
            }elseif($goods_paratemer -> s_price1 && $goods_paratemer -> s_price2){
                $tax_all = round(floatval($goods_paratemer -> s_price1) * floatval($goods_paratemer -> s_pieces1) * floatval($goods_tax_temp[$goods_paratemer -> Tax_code1]) +
                    floatval($goods_paratemer -> s_price2) * floatval($goods_paratemer -> s_pieces2) * floatval($goods_tax_temp[$goods_paratemer -> Tax_code2]),2);
            }else{
                $tax_all = round(floatval($goods_paratemer -> s_price1) * floatval($goods_paratemer -> s_pieces1) * floatval($goods_tax_temp[$goods_paratemer -> Tax_code1]),2);
            }
            



            //dump($vo);
            $ems_status_temp = '';
            $release_time = '';
            $inspection_time = '';
            $taxes = '';
            $send_time = '';
            $into_time = '';
            $pay_time = '';
            if(isset($vo['package_ems']) && $vo['package_ems']){
                //状态
                $ems_status_temp = isset($ems_status[$vo['package_ems']['ems_status']])?$ems_status[$vo['package_ems']['ems_status']]:'无';
                //放行时间
                $release_time = $vo['package_ems']['release_time']?date('Y-m-d',$vo['package_ems']['release_time']):'';
                //查验时间
                $inspection_time = $vo['package_ems']['inspection_time']?date('Y-m-d',$vo['package_ems']['inspection_time']):'';
                //实际税金
                $taxes = $vo['package_ems']['taxes'];
                //送货时间
                $send_time = $vo['package_ems']['send_time']?date('Y-m-d',$vo['package_ems']['send_time']):'';
                //香港仓到货时间
                $into_time = $vo['package_ems']['into_time']?date('Y-m-d',$vo['package_ems']['into_time']):'';
                //缴纳税金时间
                $pay_time = $vo['package_ems']['mail_pay_time']?$vo['package_ems']['mail_pay_time']:'未支付';

            }





            $cellData[] = [
                $key+1,
                $ems_status_temp,
                $release_time,
                $inspection_time,
                $tax_all,
                $taxes,
                $send_time,
                $into_time,
                $vo['package_num'],
                $vo['wuliu_num'],
                $vo['areas']['area_name'],
                $vo['name'],
                $vo['tel'],
                $vo['address'],
                $pay_time
            ];
        }





        //dd($cellData);
        Excel::create(date('Y-m-d-H-i').'包裹清关状态',function($excel) use ($cellData){
            $excel->sheet('包裹清关状态', function($sheet) use ($cellData){
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