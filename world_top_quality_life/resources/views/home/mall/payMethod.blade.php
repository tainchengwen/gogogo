<!DOCTYPE html>
<html>
	<head>
		<title>订单支付</title>
		@include('home.mall.public')
	</head>
	<body>
	
		<div class="content">
			<div class="tab_content" style="top:0;bottom: 0;">
					
				<div class="addr_kuaidi box pay_info" style="margin-top: 0.32rem;">
					<div class="dingdan_info_box">
						<div class="dingdan_info_div1">
							订单编号:
							<span>{{ $order_info['SaleID'] }}</span>
						</div>
						<div class="dingdan_info_div1">
							实付金额:
							<span style="font-size: 0.32rem;line-height: 0.6rem;margin-left: 0.1rem;color: #777;">共{{ $order_info['number'] }}件商品</span>
							<span style="font-size: 0.42rem;color: #FE4070;">￥{{ $order_info['Payable'] }}</span>
						</div>
						
					</div>

				</div>


				<div class="addr_kuaidi box pay_info pay_type erweima_box">
					<div class="tab_shaixuan_box" >
						<img src="@if($typename == '微信') {{ asset('mall/img/weixin.png') }}  @else {{ asset('mall/img/zhifubao.png') }} @endif"> {{ $typename }}线下支付
					</div>
					<div class="pay_txt">
						您可直接添加客服{{ $typename }}完成转账，并后续由客服进行人工发货。为保证您的财产安全，请核实客服{{ $typename }}。
					</div>
					<div class="pay_erweima">
						<img src="{{ $image }}"/>
						<div>寰球优品生活</div>
					</div>
				</div>



			</div>	
		</div>
	</body>
</html>
