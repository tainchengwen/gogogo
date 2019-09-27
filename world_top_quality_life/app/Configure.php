<?php

namespace App;


use Illuminate\Support\Facades\DB;

class Configure
{

    //处理数组
    static function dealArray($arr){
        $temp = [];
        foreach($arr as $k => $vo){
            $temp[] = [
                'name' => $vo,
                'value' => $k
            ];
        }
        return $temp;
    }

    //处理数组 对象
    static function dealCalssArray($obj){
        $temp = [];
        foreach($obj as $vo){
            $temp[] = [
                'name' => $vo -> name,
                'value' => $vo -> id
            ];
        }
        return $temp;
    }


    static function getCurrency(){
        return [
            '1' => '人民币',
            '2' => '港币',
            '3' => '日元',
            '4' => '美元',
            '5' => '欧元',
            '6' => '英镑',
        ];
    }

    static function getAttribute(){
        return [
            '1' => '公有',
            '2' => '私有'
        ];
    }


    //采购类别
    static function purchaseClass(){
        return [
            '1' => '进货采购',
            '2' => '退货采购'
        ];
    }

    static  function getProductClass($fid){
        if(!$fid){
            $fid = 0;
        }
        $class = DB::table('erp_product_class') -> where('fid',$fid) -> get();
        return $class;
    }


    //采购类型
    static function purchaseType(){
        return [
            '1' => '国内',
            '2' => '国外',
        ];
    }

    //运费类型
    static function freightType(){
        return [
            '1' => '物理重',
            '2' => '体积重',
            '3' => '个数'
        ];
    }


    //采购订单状态 purchase_order status
    static function getOrderStatus(){
        //已保存未发货、发货中、发货完成未入库
        return [
            '0' => '未保存',
            '1' => '已保存未发货',
            '2' => '发货中',
            '3' => '发货完成未入库',
            '4' => '发货完成已入库',
        ];

    }

    //采购订单支付状态 purchase_order pay_status 运费订单也用这个
    static function getOrderPayStatus(){
        return [
            '0' => '未支付',
            '1' => '部分支付',
            '2' => '已支付'
        ];
    }





    //物流单号状态 logistics status
    static function getLogisticsStatus(){
        return [
            '0' => '未收货',
            '1' => '港口收货，仓库未收货',
            '2' => '仓库已收货',
        ];
    }



    //小程序 下单状态
    static function getMpPackageOrderStatus(){
        return [
            0 => '待完善',
            1 => '待付款',
            2 => '待发货',
            3 => '待收货',
            4 => '已签收'
        ];
    }

    //快递类型
    static function getExpressNumbers(){
        return [
            1 => '中通',
            2 => '韵达',
            3 => '圆通'
        ];
    }


    //账户日志
    static function accountType(){
        return [
            1 => [
                'name' => '账户充值',
                'plus_minus' => 1, //正
                'table' => 'erp_account_recharge_record'
            ],
            2 => [
                'name' => '订单付款',
                'plus_minus' => 2, //负
                'table' => 'erp_purchase_order_pay',
                'pay_type' => 0
            ],
            3 => [
                'name' => '运费付款',
                'plus_minus' => 2,
                'table' => 'erp_purchase_order_pay',
                'pay_type' => 1
            ],
            4 => [
                'name' => '账户转出',
                'plus_minus' => 2,
                'table' => ''
            ],
            5 => [
                'name' => '账户转入',
                'plus_minus' => 1,
                'table' => ''
            ],
            6 => [
                'name' => '账户调账',
                'plus_minus' => 1,
                'table' => ''
            ],
            7 => [
                'name' => '订单收款',
                'plus_minus' => 1,
                'table' => 'erp_stock_order',
                'pay_type' => 2
            ],
            8 => [
                'name' => '杂项付款',
                'plus_minus' => 1,
                'table' => ''
            ],
            9 => [
                'name' => '撤销充值',
                'plus_minus' => 2,
                'table' => ''
            ],
            10 => [
                'name' => '小程序订单收款',
                'plus_minus' => 1,
                'table' => 'erp_stock_order',
                'pay_type' => 2
            ],
            99 => [
                'name' => '退款',
                'plus_minus' => 1,
                'table' => 'erp_stock_order',
                'pay_type' => 2
            ],
        ];
    }



    static function create_uuid($prefix = ""){    //可以指定前缀
        $str = md5(uniqid(mt_rand(), true));
        $uuid  = substr($str,0,8) . '-';
        $uuid .= substr($str,8,4) . '-';
        $uuid .= substr($str,12,4) . '-';
        $uuid .= substr($str,16,4) . '-';
        $uuid .= substr($str,20,12);
        return $prefix . $uuid;
    }


}
