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

class RepertoryExcelExpoter extends AbstractExporter
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


        $repertory = $this -> getData();
        //dd($repertory);

        $cellData = [
            ['运单号','发件日期','物流公司','客户','件数','重量','预报箱数','预计到港','实际到港','状态','包裹状态','打包数量','发出数量','剩余数量','货值','币种','备注','新增时间','修改时间']
        ];

        $company_arr = config('admin.repertory_company');
        $status_arr = config('admin.repertory_status');
        $package_status_arr = config('admin.package_status');
        $bizhong_arr = config('admin.currency');

        foreach($repertory as $vo){
            $user_info = DB::table('wxuser') -> where([
                'id' => $vo['user_id']
            ]) -> first();
            if($user_info){
                $username = $user_info -> nickname;
            }else{
                $username = '';
            }

            $cellData[] = [
                (string)$vo['numbers'].'',
                (string)$vo['fajian_date'].'',
                (string)isset($company_arr[$vo['company']])?$company_arr[$vo['company']].'':'',
                (string)$username.'',
                (string)$vo['num'].'',
                (string)$vo['weight'].'',
                (string)$vo['yubao_num'].'',
                (string)$vo['yuji_date'].'',
                (string)$vo['shiji_date'].'',
                (string)isset($status_arr[$vo['status']])?$status_arr[$vo['status']].'':'',
                (string)isset($package_status_arr[$vo['package_status']])?$package_status_arr[$vo['package_status']].'':'',
                (string)$vo['dabao_num'].'',
                (string)$vo['fachu_num'].'',
                (string)$vo['shengyu_num'].'',
                (string)$vo['goods_value'].'',
                (string)isset($bizhong_arr[$vo['currency']])?$bizhong_arr[$vo['currency']].'':'',
                (string)$vo['remark'],
                (string)date('Y-m-d H:i:s',$vo['created_at']).'',
                (string)date('Y-m-d H:i:s',$vo['updated_at']).'',

                //////
            ];
        }




        //dd($cellData);
        Excel::create(date('Y-m-d-H-i').'导出到货库存',function($excel) use ($cellData){
            $excel->sheet('导出到货库存', function($sheet) use ($cellData){
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