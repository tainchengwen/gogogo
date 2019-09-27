<style>
    .li_style {
        list-style-type:none;
        height:40px;
        line-height:40px;
        font-size:18px;
    }
</style>


<h3>支付信息</h3>
<ul>
    <li class="li_style">下单时间：{{ date('Y-m-d H:i',$compact['order_info'] ->created_at -> timestamp) }}</li>
    <li class="li_style">支付时间：@if(!empty($compact['order_info'] ->pay_time)){{ date('Y-m-d H:i',$compact['order_info'] ->pay_time) }}@endif</li>
    <li class="li_style">支付方式：
        @if($compact['order_info'] ->pay_type == 1)
            线下支付 支付渠道：微信支付 交易流水号:{{ $compact['order_info'] ->pay_number }}
        @elseif($compact['order_info'] ->pay_type == 2)
            线下支付 支付渠道：支付宝支付 交易流水号:{{ $compact['order_info'] ->pay_number }}
        @elseif($compact['order_info'] ->pay_type == 9)
            虚拟支付
        @else余额支付@endif</li>
    <li class="li_style">商品价格：
        @if(strstr($compact['admin_user_name'],'admin'))
            <input type="text" value="{{ $compact['order_info'] ->price }}" name="price" />
            <input type="text" value="{{ $compact['order_info'] ->price }}" name="price_pre" />
        @else
            {{ $compact['order_info'] ->price }}
            <input type="hidden"  value="{{ $compact['order_info'] ->price }}" name="price" />
            <input type="hidden"  value="{{ $compact['order_info'] ->price }}" name="price_pre" />
        @endif
    </li>
    <li class="li_style">会员优惠：{{ $compact['order_info'] ->minus_price }}</li>
</ul>
<hr>
<h3>会员信息</h3>
<ul>
    <li class="li_style">会员昵称：{{ $compact['userinfo'] ->nickname }}<a style="margin-left:100px;color:#000000;">会员ID：{{ $compact['userinfo'] ->id }}</a></li>
    <li class="li_style">会员等级：{{ $compact['userinfo'] ->class_name }}</li>
    <li class="li_style">发展人：@if(!empty($compact['userinfo'] ->from_userinfo)) {{ $compact['userinfo'] ->from_userinfo -> nickname }} @endif<a style="margin-left:100px;color:#000000;">会员ID：@if($compact['userinfo'] -> from_userinfo) {{ $compact['userinfo'] -> from_userinfo -> id }} @endif</a></li>
    <li class="li_style">会员等级：@if(!empty($compact['userinfo'] ->from_userinfo)) {{ $compact['userinfo'] ->from_userinfo -> class_name }} @endif</li>
    <li class="li_style">返点优惠：</li>
</ul>
<h3>备注信息</h3>
<textarea name="remark" >{{ $compact['order_info'] -> remark }}</textarea>