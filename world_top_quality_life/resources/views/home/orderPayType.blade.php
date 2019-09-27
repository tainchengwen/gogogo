<!DOCTYPE html>
<html>
<head>
	<title>订单支付</title>
	@include('layouts.home_header')
</head>
	<body>
		<div class="dingdan_order box" style="padding-bottom: 0;">
			<div class="dingdan_order_num">
				订单编号
				<span>{{ $orderinfo -> order_num }}</span>
			</div>
			<div class="dingdan_order_maney">
				订单金额
				<span>￥{{ $orderinfo -> pay_price }}</span>
			</div>
		</div>
		<div class="box" style="padding:0;margin-top: 0.26rem;">
			<div style="padding:0 0.4rem;height: 1.17rem;line-height: 1.17rem; font-size: 0.4rem;color: #111111;border-bottom: 1px solid #eee;">
				{{ $typename }}线下支付
			</div>
			<div style="padding:0.4rem;font-size: 0.32rem;color:#666666;border-bottom: 1px solid #eee;">
				<p>1.您可以直接添加客服{{ $typename }}，完成转账后，由客服进行后续人工发货。</p>
				<p style="margin-top: 0.2rem;">2.为了保障您的财产安全，请务必核实唯一客服{{ $typename }}。</p>
			</div>
			<div style="text-align: center;">
				<img src="{{ $image }}" style="width: 5.6rem;height: 5.6rem;margin: 0.2rem 0;"/>
			</div>
		</div>
		<!--
		<div class="btn_big_bg" style="margin-top: 0.8rem;">
			<button class="btn_big">已支付</button>
		</div>
		-->
	</body>
</html>
