<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mockery\Exception;

class MallApi extends Model
{
    //查询库存和客户价格（分页）
    public function getGoodsList($index,$pagesize,$query=[]){
        $url = 'http://api.fenith.com/QueryInterface.svc/InventoryByPage?';
        $url .= 'Token=15088E5F-79E8-4E1F-B438-998834469F63';
        $url .= '&CustomerCategory=C';


        //事业部
        if(isset($query['Organization']) && $query['Organization']){
            $url .= '&Organization='.$query['Organization'];
        }else{
            //默认
            $url .= '&Organization='.env('MALL_PRE');
        }


        //商品类别
        if(isset($query['type']) && $query['type']){
            $url .= '&CategoryName='.$query['type'];
        }
        //商品编号
        if(isset($query['ProductNo']) && $query['ProductNo']){
            $url .= '&ProductNo='.$query['ProductNo'];
        }

        //客户名称
        if(isset($query['CustomerName']) && $query['CustomerName']){
            $url .= '&CustomerName='.$query['CustomerName'];
        }

        //仓库名称
        if(isset($query['WarehouseName']) && $query['WarehouseName'] ){
            $url .= '&WarehouseName='.$query['WarehouseName'];
        }

        //库位
        if(isset($query['LocationName']) && $query['LocationName'] ){
            $url .= '&LocationName='.$query['LocationName'];
        }



        $url .= '&pageindex='.$index.'&pagesize='.$pagesize;
        //dd($url);
        //Log::info($url);
        //echo $url;exit;

        //设置超时参数
        $opts=array(
            "http"=>array(
                "method"=>"GET",
                "timeout"=>3
            ),
        );
        //创建数据流上下文
        $context = stream_context_create($opts);
        try
        {
            $result = file_get_contents($url,false, $context);
            if($result){
                return json_decode(trim(stripslashes($result),'\"'),true);
            }
        }

        //捕获异常
        catch(Exception $e)
        {
            return [];
        }

    }


    //根据关键词查库存
    public function getGoodsListByWord($index,$pagesize,$query=[]){
        $url = 'http://api.fenith.com/QueryInterface.svc/InventoryWechat?';
        $url .= 'Token=15088E5F-79E8-4E1F-B438-998834469F63';
        $url .= '&CustomerCategory=C';

        //关键词
        if(isset($query['Keyword']) && $query['Keyword']){
            $url .= '&Keyword='.$query['Keyword'];
        }

        //事业部
        if(isset($query['Organization']) && $query['Organization']){
            $url .= '&Organization='.$query['Organization'];
        }else{
            //默认
            $url .= '&Organization='.env('MALL_PRE');
        }


        $url .= '&pageindex='.$index.'&pagesize='.$pagesize;

        //设置超时参数
        $opts=array(
            "http"=>array(
                "method"=>"GET",
                "timeout"=>3
            ),
        );
        //创建数据流上下文
        $context = stream_context_create($opts);
        try
        {
            $result = file_get_contents($url,false, $context);
            if($result){
                return json_decode(trim(stripslashes($result),'\"'),true);
            }
        }

            //捕获异常
        catch(Exception $e)
        {
            return [];
        }

    }



    //查询发货信息
    public function getSendOrderInfo($SaleID){
        $url = 'http://api.fenith.com/QueryInterface.svc/Issue?';
        $url .= 'Token=15088E5F-79E8-4E1F-B438-998834469F63';
        $url .= '&SaleID='.$SaleID;
        $result = file_get_contents($url);
        if($result){
            return json_decode(trim(stripslashes($result),'\"'),true);
        }
    }



    //库存销售下单
    public function makeOrder($query){
        $url = 'http://api.fenith.com/SaleInterface.svc/Add?';
        $url .= 'Token=15088E5F-79E8-4E1F-B438-998834469F63';

        $customer_name = $this -> getCustomerName(trim($query['CustomerName']));
        Log::info(print_r($query,true));
        $post_data = [
            //客户名称
            'CustomerName' => $customer_name,
            //销售员账户
            'SaleUser' => 'shop',
            'Remark' => $query['Remark']?$query['Remark']:'',
            //发货费用
            //'DeliveryFee' => 0,
            'DeliveryFee' => $query['DeliveryFee'],
            //待发费
            'DaiFaFee' => 0,
            //仓库名称
            'WarehouseName' => $query['WarehouseName']?$query['WarehouseName']:'苏州库',
            //事业部名称
            'OrganizationName' => $query['OrganizationName']?$query['OrganizationName']:env('MALL_PRE'),
            //1发货，2不发货，3预定
            'IsShip' => 3,
            //发货人
            'ShipConsignee' => '李先生',
            //发货省份
            'ShipProvince' => '江苏省',
            //发货市
            'ShipCity' => '苏州市',
            //发货区
            'ShipDistrict' => '吴中区',
            //发货详细地址
            'ShipAddress' => '宏业路',
            //发货手机
            'ShipMobile' => '17195134748',
            //收货人
            'ReceiveConsignee' => $query['ReceiveConsignee']?$query['ReceiveConsignee']:'张杨',
            //收货省份
            'ReceiveProvince' => $query['ReceiveProvince']?$query['ReceiveProvince']:'江苏省',
            //收货市
            'ReceiveCity' => $query['ReceiveCity']?$query['ReceiveCity']:'苏州市',
            //收货区
            'ReceiveDistrict' => $query['ReceiveDistrict']?$query['ReceiveDistrict']:'吴中区',
            //收货详细地址
            'ReceiveAddress' => $query['ReceiveAddress']?$query['ReceiveAddress']:'虚拟地址',
            //收货手机
            'ReceiveMobile' => $query['ReceiveMobile']?$query['ReceiveMobile']:'18734194202',
            'SaleDetailList' => count($query['SaleDetailList'])?$query['SaleDetailList']:[
                [
                    //产品编号
                    'ProductNo' => '4562344352901',
                    //下单数量
                    'Qty' => 1,
                    //价格
                    'Price' => 100
                ]
            ],
            /*
            'SaleDetailList' => [
                [
                    //产品编号
                    'ProductNo' => '4562344352901',
                    //下单数量
                    'Qty' => 1,
                    //价格
                    'Price' => 100
                ]
            ]
            */
        ];
        //echo json_encode($post_data);
        $res = $this -> post($url,$post_data);
        Log::info('send_order_post_data:'.print_r($post_data,true));
        Log::info('send_order_res:'.$res);
        if(strpos($res,'S-')){
            //返回的是订单号 下单成功
            return [
                'code' => 'success',
                'result' => $res
            ];
            //return $res;
        }else{
            return [
                'code' => 'error',
                'result' => $res
            ];
            //return false;
        }

        //dump($res);




    }


    //获取订单列表
    public function getOrderList($CustomerName){
        $CustomerName = $this -> getCustomerName(trim($CustomerName));

        $url = 'http://api.fenith.com/SaleInterface.svc/GetList?';
        $url .= 'Token=15088E5F-79E8-4E1F-B438-998834469F63';
        $url .= '&OrganizationName='.env('MALL_PRE');
        $url .= '&CustomerName='.$CustomerName;
        $url .= '&pageindex=1&pagesize=15';
        //echo $url;
        Log::info($url);
        $result = file_get_contents($url);

        if($result){
            return json_decode(trim(stripslashes($result),'\"'),true);
        }

    }

    //通过d订单号获取订单明细
    public function getOrderDetail($SaleID){
        $url = 'http://api.fenith.com/SaleInterface.svc/GetDetail?';
        $url .= 'Token=15088E5F-79E8-4E1F-B438-998834469F63';
        $url .= '&SaleID='.$SaleID;
        //dd($url);
        $result = file_get_contents($url);
        if($result){
            return json_decode(trim(stripslashes($result),'\"'),true);
        }
    }


    //查询订单信息
    public function getOrderInfo($SaleID){
        $url = 'http://api.fenith.com/SaleInterface.svc/Get?';
        $url .= 'Token=15088E5F-79E8-4E1F-B438-998834469F63';
        $url .= '&SaleID='.$SaleID;
        $result = file_get_contents($url);
        //echo $url;
        if($result){
            //dd($result);
            return json_decode(trim(stripslashes($result),'\"'),true);
            /*
        array:29 [▼
          "SaleID" => "S-20180915-0025"
          "SaleUser" => "test"
          "SaleDate" => "2018-09-15T16:41:55"
          "Remark" => ""
          "ShipStatus" => 1
          "PaidStatus" => 0
          "Payable" => 200.0
          "Paid" => 0.0
          "Discount" => 0.0
          "DeliveryFee" => 100.0
          "DaiFaFee" => 0.0
          "SaleRemark" => ""
          "AddressRemark" => ""
          "CustomerRemark" => ""
          "ShipRemark" => 1
          "ShipConsignee" => "ShipConsignee"
          "ShipProvince" => "ShipProvince"
          "ShipCity" => "ShipCity"
          "ShipDistrict" => "ShipDistrict"
          "ShipAddress" => "ShipAddress"
          "ShipPhone" => ""
          "ShipMobile" => "ShipMobile"
          "ReceiveConsignee" => "ReceiveConsignee"
          "ReceiveProvince" => "ReceiveProvince"
          "ReceiveCity" => "ReceiveCity"
          "ReceiveDistrict" => "ReceiveDistrict"
          "ReceiveAddress" => "ReceiveAddress"
          "ReceivePhone" => ""
          "ReceiveMobile" => "ReceiveMobile"
        ]
            */
        }
    }

    //订单付款
    public function payOrder($SaleID,$payable){
        //header("Content-type:text/html;charset=utf-8");
        $url = 'http://api.fenith.com/SaleInterface.svc/Pay?';
        $url .= 'Token=15088E5F-79E8-4E1F-B438-998834469F63';
        $post_data = [
            'ReceiveAccountName' => '展鹏',
            'ReceiveUser' => 'shop',
            'ReceiveAmount' => (string)$payable,
            'ReceiveFee' => '0',
            'SaleID' => $SaleID,
            'Voucher' => '0',
            'IsCustomerAccount' => '0',
        ];
        $result = $this -> post($url,$post_data);

        Log::info('payOrder:'.$SaleID.' '.print_r($result,true));
        return $result;
        //return trim(stripslashes($result),'\"');
    }



    //查询发货信息
    public function getWuliu($SaleID){
        $url = 'http://api.fenith.com/QueryInterface.svc/Issue?';
        $url .= 'Token=15088E5F-79E8-4E1F-B438-998834469F63';
        $url .= '&SaleID='.$SaleID;
        $result = file_get_contents($url);

        if($result){
            return json_decode(trim(stripslashes($result),'\"'),true);
            /*
            [{
                "IssueID": "I201702171625584218",
                "SaleID": "S-20170217-0084",
                "IssueUser": "mingzhen.chen",
                "IssueDate": "2017-02-17T16:25:58",
                "LogisticsCompany": "安能物流",
                "LogisticsNo": "300146344457",
                "Remark": "到付",
                "LogisticsFare": 0.00,
                "Weight": 0.0
            }]

            */
        }
    }


   public function getImg($SaleID){
        $url = 'http://api.fenith.com/QueryInterface.svc/GetPicURL?';
        $url .= 'Token=15088E5F-79E8-4E1F-B438-998834469F63';
        $url .= '&SaleID='.$SaleID;
        $result = file_get_contents($url);
        //echo $url;
        Log::info($url);
        if($result){
            //dd($result);
            return trim(stripslashes($result),'\"');
            //return json_decode(trim(stripslashes($result),'\"'),true);
        }
    }


    //添加客户
    public function addCustomer($query){
        $url = 'http://api.fenith.com/CustomerInterface.svc/Add?';
        $url .= 'Token=15088E5F-79E8-4E1F-B438-998834469F63';
        $url .= '&OrganizationName='.env('MALL_PRE');

        $post_data = [
            'CustomerName' => isset($query['CustomerName'])?$query['CustomerName']:'',  //客户名称
            'CustomerDesc' => isset($query['CustomerDesc'])?$query['CustomerDesc']:'',  //客户描述
            'CustomerNo' => isset($query['CustomerNo'])?$query['CustomerNo']:'',  //备注
            'Phone' => isset($query['CustomerNo'])?$query['CustomerNo']:'',  //客户手机
            'Mobile' => isset($query['Mobile'])?$query['Mobile']:'',  //客户电话
            'Wangwang' => isset($query['Wangwang'])?$query['Wangwang']:'',  //客户电话
            'Remark' => isset($query['Remark'])?$query['Remark']:'',  //客户备注
            'Wechat' => isset($query['Wechat'])?$query['Wechat']:'',  //微信
            'QQ' => isset($query['QQ'])?$query['QQ']:'',  //QQ
            'CustomerAccount' => isset($query['CustomerAccount'])?$query['CustomerAccount']:'',  //商城登录名
            'CustomerPassword' => isset($query['CustomerPassword'])?$query['CustomerPassword']:'',  //商城登录密码
            'IsInReport' => 1,  //是否纳入销售报表，1，是，0，否
            'Email' => isset($query['Email'])?$query['Email']:'',  //Email
            'AddUser' => 'admin',  //AddUser
        ];
        //dump($post_data);
        $result = $this -> post($url,$post_data);
        return $result;
    }

    public function getCustomer($query){
        //$query['CustomerName'] = 443;
        $url = 'http://api.fenith.com/CustomerInterface.svc/GetList?';
        $url .= 'Token=15088E5F-79E8-4E1F-B438-998834469F63';
        $url .= '&OrganizationName='.env('MALL_PRE');
        $url .= '&CustomerName='.  str_replace(' ','', $query['CustomerName'] );
//echo $url;
       $result = file_get_contents($url);
        //Log::info($url);
        if($result){
            //dd($result);
            //return trim(stripslashes($result),'\"');
            return json_decode(trim(stripslashes($result),'\"'),true);
        }
 
        return $result;
    }






    //获取客户名称
    public function getCustomerName($customer){
        if($customer){
            //传来的是wxuser 的id
            $userinfo = DB::table('wxuser') -> where([
                'id' => trim($customer)
            ]) -> first();
            if($userinfo && $userinfo -> erp_id){
                $customer_name = trim($userinfo -> erp_id);
            }else{
                $customer_name = $userinfo -> id;
            }
        }else{
            $customer_name = '27';
        }

        return $customer_name;

    }








    public  function post($url, $post_data = '',$headers = []){
        //header("Content-type:text/html;charset=utf-8");
        $ch = curl_init();
        curl_setopt ($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS,json_encode($post_data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
        //curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json; charset=utf-8',
                'Content-Length: ' . strlen(json_encode($post_data))
            )
        );
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            return  curl_error($ch);
        }
        curl_close($ch);
        return $result;
    }


    //顺丰api 分割地址
    public function getAddressBySF($address){
        
        //$url = 'http://ucmp.sf-express.com/cx-wechat-order/order/address/intelAddressResolution?address='.$address;
        $url = 'https://ucmp.sf-express.com/cx-wechat-order/order/address/intelAddressResolution?address='.$address;

        $ch = curl_init();
        curl_setopt ($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        //curl_setopt($ch, CURLOPT_POSTFIELDS,json_encode($post_data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
        //curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            return  curl_error($ch);
        }
        curl_close($ch);
        return $result;
    }
}
