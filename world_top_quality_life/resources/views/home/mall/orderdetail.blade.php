<!DOCTYPE html>
<html>
	<head>
		<title>订单详情</title>
		@include('home.mall.public')
	</head>
	<body>
	
		<div class="content">
			<div class="tab_content" style="top:0;padding-bottom: 2rem;">
				<div class="dingdan_over">
					@if($order_info['PaidStatus'] == '付款未完成')
						待付款
					@elseif($order_info['ShipStatus'] == '发货完成')
						已完成
					@elseif($order_info['ShipStatus'] == '未发货')
						待发货
					@else
						待收货
					@endif
				</div>
				<div class="address_box1 box addr_check">
					<div class="addr_check_icon_left"></div>
					<div class="address_box_info">
						<div class="address_box_div">
							收货人：{{ $order_info['ReceiveConsignee'] }}
							<span>{{ $order_info['ReceiveMobile'] }}</span>
						</div>
						<div class="address_box_div address_box_div_sm">
							收货地址：{{ $order_info['ReceiveAddress'] }}
						</div>
					</div>
					
				</div>
				
			   <div class="tab_qigndan">
					<div class="tab_qigndan_title">
						<img src="{{ asset('mall/img/qingdan.png') }}"/>
						购物清单
					</div>
					<div class="tab_qigndan_content">

						@foreach($order_detail as $vo)
						<div class="commodity_box">
							<div class="commodity_img">

								@if($vo['product_img'])
									<img src="{{ $vo['product_img'] }}"/>
								@else
									<img src="{{ asset('mall/img/noimg2.jpg') }}" />
								@endif

							</div>
							<div class="commodity_info">
								<div class="commodity_name1">{{ $vo['PartNumber'] }}</div>
								<div class="commodity_bianhao">商品编码：{{ $vo['ProductNo'] }}</div>
								<div class="commodity_pay">
									<span class="commodity_maney_sp1">￥{{ $vo['Amount'] }}</span>
									<div class="commodity_num">
										x{{ $vo['Qty'] }}
									</div>
								</div>
							</div>
						</div>
						@endforeach

					</div>
				</div>
				
				
				<div class="addr_kuaidi box pay_info">
					<div class="dingdan_info_div">
						配送方式:
						<span>快递</span>
					</div>
					<div class="dingdan_info_div" style="border-top: 1px solid #dfdfdf;border-bottom: 1px solid #ccc;">
						支付方式:
						<span>微信支付</span>
					</div>
				</div>

				<div class="addr_kuaidi box pay_info">
					<div class="dingdan_info_box">
						<div class="dingdan_info_div1">
							商品金额:
							<span>￥{{ $order_info['Payable'] }}</span>
						</div>
						<div class="dingdan_info_div1">
							会员优惠:
							<span>- ￥0</span>
						</div>
						<div class="dingdan_info_div1">
							快递运费:
							<span>+ ￥0</span>
						</div>
					</div>
					
					<div class="dingdan_info_div" style="border-top: 1px solid #ccc;">
						实付金额:
						<span style="font-size: 0.32rem;line-height: 1.5rem;margin-left: 0.1rem;color: #777;">共{{ count($order_detail) }}件商品</span>
						<span style="font-size: 0.42rem;color: #FE4070;">￥{{ $order_info['Payable'] }}</span>
						
					</div>
				</div>
				<div class="addr_kuaidi box pay_info">
					<div class="dingdan_info_box">
						<div class="dingdan_info_div1">
							下单时间:
							<span>{{ $order_info['SaleDate'] }}</span>
						</div>
						<div class="dingdan_info_div1">
							订单编号:
							<span>{{ $order_info['SaleID'] }}</span>
						</div>
						<div class="dingdan_info_liuyan">
							买家留言:
							<span>{{ $order_info['CustomerRemark'] }}</span>
						</div>
					</div>

				</div>


				@if($wuliu_info)
				<div class="addr_kuaidi box pay_info">
					<div class="dingdan_info_box">
						<div class="dingdan_info_div1">
							快递公司:
							<span>{{ $wuliu_company }}</span>
						</div>
						<div class="dingdan_info_div1">
							快递单号:
							<span>{{ $wuliu_no }}</span>
						</div>
						<div class="dingdan_info_liuyan">
							<img src="{{ $wuliu_image }}"/>
						</div>
					</div>

				</div>

					@endif

			</div>	
		</div>
		
		<div class="foot">
			<div class="pay_all_btn" style="width: 100%;">删除订单</div>
		</div>
	</body>
</html>
