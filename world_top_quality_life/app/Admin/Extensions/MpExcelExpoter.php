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

class MpExcelExpoter extends AbstractExporter
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

        $orders = $this -> getData();
        $order_ids = [];
        foreach($orders as $vo){
            $order_ids[] = $vo['id'];
        }
        //dd($orders);
        $info = DB::table('mp_scan_goods_info')
            -> leftJoin('mp_temp_package_number','mp_scan_goods_info.package_id','mp_temp_package_number.id')
            -> leftJoin('area_name','mp_temp_package_number.area_id','area_name.id')
            -> leftJoin('mp_users','mp_temp_package_number.user_id','mp_users.id')
            -> leftJoin('area_scan_order','mp_temp_package_number.send_order_id','area_scan_order.id')
            -> select([
                'mp_scan_goods_info.product_no',
                'mp_scan_goods_info.product_name',
                'mp_scan_goods_info.product_method',
                'mp_scan_goods_info.product_format',
                'mp_scan_goods_info.product_unit',
                'mp_scan_goods_info.declare_price',
                'mp_scan_goods_info.taobao_url',
                'mp_scan_goods_info.goods_number',
                'mp_temp_package_number.package_num',
                'mp_temp_package_number.created_at as mp_temp_package_number_created_at',
                'mp_temp_package_number.id as temp_package_number_id',
                'area_name.area_name',
                'mp_users.nickname',
                'mp_temp_package_number.province',
                'mp_temp_package_number.city',
                'mp_temp_package_number.country',
                'mp_temp_package_number.address',
                'mp_temp_package_number.name',
                'mp_temp_package_number.tel',
                'mp_temp_package_number.card',
                'mp_temp_package_number.mode_id',
                'mp_temp_package_number.remark',
                'mp_temp_package_number.weight',
                'mp_scan_goods_info.english_name',
                'mp_scan_goods_info.brand_name',
                'area_scan_order.order_num as area_scan_order_num',

            ])
            -> whereIn('mp_scan_goods_info.package_id',$order_ids)
            -> where('mp_scan_goods_info.flag',0)
            -> get();

        $cellData[] = [
            '商品编码',
            '商品名称',
            '包装方式',
            '商品规格',
            '商品单位',
            '英文名称',
            '品牌',


            '申报价值',
            '淘宝链接',
            '个数',
            '包裹ID',
            '包裹编号',
            '区域名称',
            '下单人',
            '省',
            '市',
            '区',
            '地址',
            '姓名',
            '电话',
            '身份证',
            '发货方式',
            '备注',
            '重量',
            '发货单单号',
            '创建时间',

        ];
        foreach($info as $vo){
            $mode = config('admin.mp_mode');
            $cellData[] = [
                (string)$vo -> product_no,
                (string)$vo -> product_name,
                (string)$vo -> product_method,
                (string)$vo -> product_format,
                (string)$vo -> product_unit,
                (string)$vo -> english_name,
                (string)$vo -> brand_name,
                (string)$vo -> declare_price,
                (string)$vo -> taobao_url,
                (string)$vo -> goods_number,
                (string)$vo -> temp_package_number_id,
                (string)$vo -> package_num,
                (string)$vo -> area_name,
                (string)$vo -> nickname,
                (string)$vo -> province,
                (string)$vo -> city,
                (string)$vo -> country,
                (string)$vo -> address,
                (string)$vo -> name,
                (string)$vo -> tel,
                $vo -> card."\t",
                isset($mode[$vo -> mode_id])?$mode[$vo -> mode_id]:'',
                (string)$vo -> remark,
                (string)$vo -> weight,
                (string)$vo -> area_scan_order_num,
                (string)date('Y-m-d H:i',$vo -> mp_temp_package_number_created_at),

            ];
        }





        //dd($cellData);
        Excel::create(date('Y-m-d-H-i').'导出小程序订单',function($excel) use ($cellData){
            $excel->sheet('小程序订单', function($sheet) use ($cellData){
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