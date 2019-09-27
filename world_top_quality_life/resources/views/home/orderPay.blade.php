<!DOCTYPE html>
<html>
	<head>
		<title>订单支付</title>
		@include('layouts.home_header')
	</head>
	<body>
		<div class="dingdan_order box">
			<div class="dingdan_order_num">
				订单编号
				<span>{{ $order_info -> order_num }}</span>
			</div>
			<div class="dingdan_order_maney">
				订单金额
				<span>￥{{ $order_info -> pay_price  }}</span>
			</div>
			@if($order_info -> minus_price)
			<div class="dingdan_order_num">
				优惠金额
				<span>-￥{{ $order_info -> minus_price  }}</span>
			</div>
			@endif

		</div>
		<div class="dingdan_order_pay box">
			<div class="dingdan_order_pay_img1">
				<img src="{{ asset('img/ic-remainder.png') }}"/>
			</div>
			<div class="dingdan_order_pay_txt">
				余额支付
			</div>
			<div class="dingdan_order_pay_img2">
				<img src="{{ asset('img/ic-promote.png') }}"/>
			</div>
			<div class="dingdan_order_pay_img3" data="1" >
				<img src="{{ asset('img/ic-select-active.png') }}"/>
			</div>
		</div>

		<div class="dingdan_order_pay box">
			<div class="dingdan_order_pay_img1">
				<img src="{{ asset('img/weixin.jpg') }}"/>
			</div>
			<div class="dingdan_order_pay_txt">
				微信线下支付
			</div>

			<div class="dingdan_order_pay_img3" data="2">
				<img src="{{ asset('img/ic-select-default.png') }}"/>
			</div>
		</div>
		<div class="dingdan_order_pay box">
			<div class="dingdan_order_pay_img1">
				<img src="{{ asset('img/zhifubao.jpg') }}"/>
			</div>
			<div class="dingdan_order_pay_txt">
				支付宝线下支付
			</div>

			<div class="dingdan_order_pay_img3" data="3">
				<img src="{{ asset('img/ic-select-default.png') }}"/>
			</div>
		</div>


		<input type="hidden" id="markval" value="1" />

		<div class="btn_big_bg">
			<button class="btn_big" style="color: #ffffff;" >下一步</button>
		</div>
		<script>
			$(function(){
			    $('.dingdan_order_pay_img3').click(function(){
			        $('#markval').val($(this).attr('data'));
					$('.dingdan_order_pay_img3 img').attr('src','{{ asset('img/ic-select-default.png') }}');
			        $(this).children('img').attr('src','{{ asset('img/ic-select-active.png') }}');

				});
			    $('.btn_big').click(function(){
					//查看值
					var markval = $('#markval').val();
					//余额支付
					if(markval == 1){
                        location.href='{{ url('payEnd').'/'.$order_info -> id }}';
					}
					//微信支付
					if(markval == 2){
                        location.href='{{ url('payEndType').'/'.$order_info -> id.'/1' }}';
					}
					//支付宝支付
                    if(markval == 3){
                        location.href='{{ url('payEndType').'/'.$order_info -> id.'/0' }}';
                    }



				})
			})
		</script>
	</body>
</html>
