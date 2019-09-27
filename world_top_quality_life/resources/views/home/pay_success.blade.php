<!DOCTYPE html>
<html>
	<head>
		@include('layouts.home_header')
		<title>订单支付</title>
		<style>
			body{background: #F5F5F6;}
		</style>
		<script>
			$(function(){
			    layer.msg('支付成功');
			})
		</script>
	</head>
	<body>
		<div class="dingdan_success">
			<div class="dingdan_success_img">
				<img src="{{ asset('img/ic-success.png') }}"/>
			</div>
			<div class="dingdan_success_txt">支付成功</div>
			<div class="dingdan_success_info">
				订单号：{{ $order_info -> order_num }}
				<span>@if($order_info -> from_area == 1)大阪@else东京@endif</span>
			</div>
			<div class="dingdan_success_maney">
				支付方式：@if($order_info -> pay_type == 1)
					微信支付
					@if($order_info -> pay_type == 2)
						支付宝支付
					@else余额支付
						@endif
					@endif
			</div>
			<div class="dingdan_success_maney">
				支付金额：￥{{ $order_info -> pay_price }}
			</div>
		</div>
		<div class="btn_big_bg" style="margin-top: 3.75rem;">
			<button class="btn_big" onclick="location.href='{{ url('orderInfo').'/'.$order_info -> id }}'">订单详情</button>
			<button class="btn_big btn_big_black" onclick="location.href='{{ url('order') }}'" >查看全部订单</button>
		</div>
		
	</body>
</html>
