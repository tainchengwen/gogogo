<?php

namespace App\Repositories;

use Log;
use Illuminate\Support\Str;
use DB;

class PayByCrossRepository extends BaseRepository
{
	protected $inputCharset;
    protected $pageUrl;
	protected $bgUrl;
	protected $version;
	protected $language;
	protected $signType;
	protected $merchantAcctId;
	protected $terminalId;
	protected $payerName;
	protected $payerContactType;
	protected $payerContact;
	protected $payerIdentityCard;
	protected $mobileNumber;
	protected $cardNumber;
	protected $customerId;
	protected $orderId;
	protected $inquireTrxNo;
	protected $orderCurrency;
	protected $settlementCurrency;
	protected $orderAmount;
	protected $orderTime;
	protected $productName;
	protected $productNum;
	protected $productId;
	protected $productDesc;
	protected $ext1;
	protected $ext2;
	protected $openId;
	protected $deviceType;
	protected $payType;
	protected $customerIp;
	protected $bankId;
	protected $redoFlag;
    protected $signMsg;
    protected $url;
    protected $bankDealId;
    protected $dealId;
    protected $dealTime;
    protected $errCode;
    protected $payResult;

    //发送预下单请求
    public function send($order,$request)
    {
        $this->paramRequestInit($order,$request);
        //参数组装
        $kq_all_para = $this->param_join([
            'pageUrl',
            'bgUrl',
            'merchantAcctId',
            'terminalId',
            'customerId',
            'orderId',
            'orderAmount',
            'orderTime',
            'productDesc',
            'ext1',
            'ext2',
            'deviceType',
            'payType'
        ]);
        //RSA 签名计算
        $this->signMsg = $this->signMsgByRsa($kq_all_para);
        //设置post数据
        $post_data = array (
            "inputCharset" => $this->inputCharset,
            "pageUrl" => $this->pageUrl,
            "bgUrl" => $this->bgUrl,
            "version" => $this->version,
            "language" => $this->language,
            "signType" => $this->signType,
            "merchantAcctId" => $this->merchantAcctId,
            "terminalId" => $this->terminalId,
            "payerName" => $this->payerName,
            "payerContactType" => $this->payerContactType,
            "payerContact" => $this->payerContact,
            "payerIdentityCard" => $this->payerIdentityCard,
            "mobileNumber" => $this->mobileNumber,
            "cardNumber" => $this->cardNumber,
            "customerId" => $this->customerId,
            "orderId" => $this->orderId,
            "settlementCurrency" => $this->settlementCurrency,
            "orderCurrency" => $this->orderCurrency,
            "orderAmount" => $this->orderAmount,
            "orderTime" => $this->orderTime,
            "inquireTrxNo" => $this->inquireTrxNo,
            "productName" => $this->productName,
            "productNum" => $this->productNum,
            "productId" => $this->productId,
            "productDesc" => $this->productDesc,
            "ext1" => $this->ext1,
            "ext2" => $this->ext2,
            "openId" => $this->openId,
            "deviceType" => $this->deviceType,
            "payType" => $this->payType,
            "bankId" => $this->bankId,
            "customerIp" => $this->customerIp,
            "redoFlag" => $this->redoFlag,
            "signMsg" => $this->signMsg
        );

        //post data做记录
        $insert_data=$post_data;
        $insert_data['stockOrderIds']=implode(',',$order->stock_order_ids);
        unset($insert_data['merchantAcctId']);
        DB::table('erp_online_pay_post_data')->insert($insert_data);

        //curl
        $respData = $this->curl_func($this->url,$post_data);

        //防止系统升级
        if(!Str::contains($respData,'respCode')){
            throw new \Exception('支付系统正在升级，请稍后再试或联系客服');
        }

        parse_str($respData, $output);
        if ($output['respCode'] != '000000') {
            Log::error('在线支付-预下单-订单号:'.$order->order_num.',时间:'.date("Y-m-d H:i:s").',respCode:'.$output['respCode'].',respMsg:'.$output['respMsg']);
            throw new \Exception($output['respCode'] == '100092' ? '经公安系统校验，支付人身份证姓名与号码不匹配，请正确填写' : '支付失败');
        }else{
            return $output['payInfo'];
        }

    }

    //充值发送预下单请求
    public function sendRecharge($order,$request)
    {
        $this->paramRequestInit($order,$request);
        //参数组装
        $kq_all_para = $this->param_join([
            'pageUrl',
            'bgUrl',
            'merchantAcctId',
            'terminalId',
            'customerId',
            'orderId',
            'orderAmount',
            'orderTime',
            'productDesc',
            'ext1',
            'ext2',
            'deviceType',
            'payType'
        ]);
        //RSA 签名计算
        $this->signMsg = $this->signMsgByRsa($kq_all_para);
        //设置post数据
        $post_data = array (
            "inputCharset" => $this->inputCharset,
            "pageUrl" => $this->pageUrl,
            "bgUrl" => $this->bgUrl,
            "version" => $this->version,
            "language" => $this->language,
            "signType" => $this->signType,
            "merchantAcctId" => $this->merchantAcctId,
            "terminalId" => $this->terminalId,
            "payerName" => $this->payerName,
            "payerContactType" => $this->payerContactType,
            "payerContact" => $this->payerContact,
            "payerIdentityCard" => $this->payerIdentityCard,
            "mobileNumber" => $this->mobileNumber,
            "cardNumber" => $this->cardNumber,
            "customerId" => $this->customerId,
            "orderId" => $this->orderId,
            "settlementCurrency" => $this->settlementCurrency,
            "orderCurrency" => $this->orderCurrency,
            "orderAmount" => $this->orderAmount,
            "orderTime" => $this->orderTime,
            "inquireTrxNo" => $this->inquireTrxNo,
            "productName" => $this->productName,
            "productNum" => $this->productNum,
            "productId" => $this->productId,
            "productDesc" => $this->productDesc,
            "ext1" => $this->ext1,
            "ext2" => $this->ext2,
            "openId" => $this->openId,
            "deviceType" => $this->deviceType,
            "payType" => $this->payType,
            "bankId" => $this->bankId,
            "customerIp" => $this->customerIp,
            "redoFlag" => $this->redoFlag,
            "signMsg" => $this->signMsg
        );

        //post data做记录
        $insert_data=$post_data;
        $insert_data['rechargeId']=$order->order_id;
        unset($insert_data['merchantAcctId']);
        DB::table('erp_online_pay_recharge_post_data')->insert($insert_data);

        //curl
        $respData = $this->curl_func($this->url,$post_data);
        //防止系统升级
        if(!Str::contains($respData,'respCode')){
            throw new \Exception('支付系统正在升级，请稍后再试或联系客服');
        }

        parse_str($respData, $output);
        if ($output['respCode'] != '000000') {
            Log::error('在线支付-预下单-订单号:'.$order->order_num.',时间:'.date("Y-m-d H:i:s").',respCode:'.$output['respCode'].',respMsg:'.$output['respMsg']);
            throw new \Exception('支付失败');
        }else{
            return $output['payInfo'];
        }
    }

    //回调验签
    public function verifyReceive($request)
    {
        $this->paramReceiveInit($request);
        //参数组装
        $trans_body = $this->param_join([
            'bankDealId',
            'bankId',
            'dealId',
            'dealTime',
            'errCode',
            'ext1',
            'ext2',
            'merchantAcctId',
            'orderAmount',
            'orderCurrency',
            'orderId',
            'orderTime',
            'payResult',
            'payType',
            'terminalId',
            'version'
        ]);

        $result = $this->verifyMsgByRsa($trans_body);

        return $result;
    }

    //交易查询
    public function orderQuery($order)
    {
        $this->paramQueryInit($order);
        //参数组装
        $kq_all_para = $this->param_join([
            'inputCharset',
            'signType',
            'merchantAcctId',
            'terminalId',
            'orderId',
            'dealId'
        ]);
        //RSA 签名计算
        $this->signMsg = $this->signMsgByRsa($kq_all_para);
        //设置post数据
        $post_data = array (
            "inputCharset" => $this->inputCharset,
            "signType" => $this->signType,
            "merchantAcctId" => $this->merchantAcctId,
            "terminalId" => $this->terminalId,
            "orderId" => $this->orderId,
            "dealId" => $this->dealId,
            "signMsg" => $this->signMsg
        );

        //curl
        $respData = $this->curl_func($this->url,$post_data);

        parse_str($respData, $output);
        if ($output['queryRespCode'] == 'S' && $output['payResult'] == '10') {
            return true;
        }else{
            Log::error('在线支付-查询-订单号:'.$order->online_order_num.',时间:'.date("Y-m-d H:i:s").',queryRespCode:'.$output['queryRespCode'].',payResult:'.$output['payResult']);
            throw new \Exception('支付失败');
        }
    }

    private function paramRequestInit($order,$request)
    {
        //编码方式，1代表 UTF-8; 2 代表 GBK; 3代表 GB2312 默认为1,该参数必填。
        $this->inputCharset=1;
        if(app()->environment()=='development'){
            //接收支付结果的页面地址，该参数一般置为空即可。
            $this->pageUrl = 'https://www.baidu.com/';
            //服务器接收支付结果的后台地址，该参数务必填写，不能为空。
            $this->bgUrl = 'https://wind.cross.echosite.cn';
        }else{
            if (isset($request->mp) && $request->mp == 'shop'){
                if(isset($request->isRecharge) && $request->isRecharge == 1){
                    $this->bgUrl = route('shopRecharge.asyn');//自营的
                }else{
                    $this->bgUrl = route('shopPay.asyn');//自营的
                }
            }else{
                $this->bgUrl = route('pay.asyn');//代理的
            }
            $this->pageUrl = route('pay.syn');
        }

        //终端号，由我司提供。
        if (isset($request->mp) && $request->mp == 'shop'){
            $this->terminalId = config('pay.terminalId_zi_ying');
        }else{
            $this->terminalId = config('pay.terminalId');
        }
        //网关版本，固定值：3.0,该参数必填。
        $this->version =  "3.0";
        //语言种类，1代表中文显示，2代表英文显示。默认为1,该参数必填。
        $this->language =  "1";
        //签名类型,固定值：4。RSA加签。
        $this->signType =  "4";
        //商户会员号，该账号为11位商户号+01,该参数必填。
        $this->merchantAcctId = config('pay.merchantAcctId');
        //支付人姓名,根据产品开通时的配置确定是否必填。
        $this->payerName = is_null($order->name)? "默认" : $order->name;
        //支付人联系类型，1 代表电子邮件方式；2 代表手机联系方式。可以为空。
        $this->payerContactType = "2";
        //支付人联系方式，与payerContactType设置对应，payerContactType为1，则填写邮箱地址；payerContactType为2，则填写手机号码。可以为空。
        $this->payerContact = is_null($order->phone)? " " : $order->phone;
        //支付人身份证号码，根据产品开通时的配置确定是否必填。
        $this->payerIdentityCard = is_null($order->idNumber)? " " : $order->idNumber;
        //支付人手机号，可为空。
        $this->mobileNumber = " ";
        //支持人所持卡号，可为空。
        $this->cardNumber = " ";
        //支付人在商户系统的客户编号，可为空。
        $this->customerId = "";
        //商户订单号，建议加入时间来定义订单号，商户可以根据自己订单号的定义规则来定义该值，不能为空。
        $this->orderId = is_null($order->order_num)? date("YmdHis") : $order->order_num;
        //询盘流水号
        $this->inquireTrxNo = " ";
        //订单币种
        $this->orderCurrency = "CNY";
	    //结算币种
        $this->settlementCurrency = "CNY";
        //订单金额，金额以“分”为单位,该参数必填。
        $this->orderAmount = $this->math_add($order->price,$order->freight)*100;
        //订单提交时间，格式：yyyyMMddHHmmss，如：20071117020101，不能为空。
	    $this->orderTime = is_null($order->created_at)? date("YmdHis") : date("YmdHis",$order->created_at->timestamp);
        //商品名称，可以为空。
        $this->productName= "默认";
        //商品数量，可以为空。
        $this->productNum = "1";
        //商品代码，可以为空。
        $this->productId = "100018";
        //商品描述，可以为空。
        $this->productDesc = "T";
        //扩展字段1，商户可以传递自己需要的参数，支付完后会原值返回，可以为空。
        $this->ext1 = "ext1";
        //扩展自段2，商户可以传递自己需要的参数，支付完后会原值返回，可以为空。
        $this->ext2 = "ext2";
        //openID
        $this->openId = $request->user->openid;
        //1：pc端支付2：移动端支付
        $this->deviceType = "2";
        //1：微信扫码（返回二维码）2：支付宝扫码 8:小程序
        $this->payType = "8";
        //支付人Ip，可为空。
        $this->customerIp = " ";
        //银行代码，如果payType为00，该值可以为空；如果payType为10，该值必须填写，具体请参考银行列表。
        $this->bankId = " ";
        //同一订单禁止重复提交标志，实物购物车填1，虚拟产品用0。1代表只能提交一次，0代表在支付不成功情况下可以再提交。可为空。
        $this->redoFlag = "1";
        //请求接口
        $this->url = config('pay.url');
    }
    private function paramReceiveInit($request)
    {
        //银行交易号 ，ChinaPnr交易在银行支付时对应的交易号，如果不是通过银行卡支付，则为空
        $this->bankDealId = $request->input('bankDealId');
        //银行代码，如果payType为00，该值为空；如果payType为10,该值与提交时相同。
        $this->bankId = $request->input('bankId');
        // ChinaPnr交易号，商户每一笔交易都会在ChinaPnr生成一个交易号。
        $this->dealId = $request->input('dealId');
        //ChinaPnr交易时间，ChinaPnr对交易进行处理的时间,格式：yyyyMMddHHmmss，如：20071117020101
        $this->dealTime = $request->input('dealTime');
        //错误代码 ，请参照《人民币网关接口文档》最后部分的详细解释。
        $this->errCode = $request->input('errCode');
        //扩展字段1，该值与提交时相同
        $this->ext1 = $request->input('ext1');
        //扩展字段2，该值与提交时相同。
        $this->ext2 = $request->input('ext2');
        //人民币网关账号，该账号为11位人民币网关商户编号+01,该值与提交时相同。
        $this->merchantAcctId = $request->input('merchantAcctId');
        //订单金额，金额以“分”为单位，商户测试以1分测试即可，切勿以大金额测试,该值与支付时相同。
        $this->orderAmount = $request->input('orderAmount');
        $this->orderCurrency = $request->input('orderCurrency');
        //商户订单号，,该值与提交时相同。
        $this->orderId = $request->input('orderId');
        //订单提交时间，格式：yyyyMMddHHmmss，如：20071117020101,该值与提交时相同。
        $this->orderTime = $request->input('orderTime');
        //处理结果， 10支付成功，11 支付失败，00订单申请成功，01 订单申请失败
        $this->payResult = $request->input('payResult');
        //支付方式，一般为00，代表所有的支付方式。如果是银行直连商户，该值为10,该值与提交时相同。
        $this->payType = $request->input('payType');
        //商户终端号，该值与提交时相同。
        $this->terminalId = $request->input('terminalId');
        //网关版本，该值与提交时相同。
        $this->version = $request->input('version');

        $this->signMsg = $request->input('signMsg');
    }

    private function paramQueryInit($order)
    {
        //账号，该账号为11位商户编号+01,该参数必填。
        $this->merchantAcctId = config('pay.merchantAcctId');
        $this->terminalId = config('pay.terminalId');
        //编码方式，1代表 UTF-8; 2 代表 GBK; 3代表 GB2312 默认为1,该参数必填。
        $this->inputCharset=1;
        //签名类型,该值为4，代表PKI加密方式,该参数必填。
        $this->signType =  "4";
        //商户订单号，以下采用时间来定义订单号，商户可以根据自己订单号的定义规则来定义该值，不能为空。
        $this->orderId = $order->online_order_num;
        //收单系统交易号，可为空。
        $this->dealId = "";
        //请求接口
        $this->url = config('pay.queryUrl');
    }

    private function signMsgByRsa($kq_all_para)
    {
        $private_pem_path = resource_path('pem/dc-rsa.pem');
        $fp = fopen($private_pem_path, "r");
        $priv_key = fread($fp, filesize($private_pem_path));
        fclose($fp);
        $pkeyid = openssl_get_privatekey($priv_key);

        // compute signature
        openssl_sign($kq_all_para, $signMsg, $pkeyid,OPENSSL_ALGO_SHA1);

        // free the key from memory
        openssl_free_key($pkeyid);

        $signMsg = base64_encode($signMsg);

        return $signMsg;
    }

    private function verifyMsgByRsa($trans_body)
    {
        $public_pem_path = resource_path('pem/ChinaPnR.rsa.pem');
        $signMsgDe=	urldecode($this->signMsg);
        $MAC=base64_decode($signMsgDe);
        $trans_body_de=urldecode($trans_body);

        $fp = fopen($public_pem_path, "r");
        $cert = fread($fp, filesize($public_pem_path));
        fclose($fp);
        $pubkeyid = openssl_get_publickey($cert);
        $result = openssl_verify($trans_body_de, $MAC, $pubkeyid);

        return $result;
    }

    private function param_join($params)
    {
        $kq_all_para='';
        foreach($params as $param){
            $kq_all_para.=$this->param_ck_null($this->$param,$param);
        }

        $kq_all_para=substr($kq_all_para,0,strlen($kq_all_para)-1);

        return $kq_all_para;
    }

    private function param_ck_null($kq_va,$kq_na)
    {
		if($kq_va == ""){
			$kq_va="";
		}else{
			return $kq_va=$kq_na.'='.$kq_va.'&';
		}
	}
}
