<!DOCTYPE html>
<html>
	<head>
		<title>我的账户</title>
		@include('layouts.home_header')
	</head>
	<body>

		<div class="content">
			<div class="my_header">
				<div class="my_img">
					<img src="{{ $userinfo -> headimg }}"/>
				</div>
				<div class="my_info">
					<div class="my_name">
						{{ $userinfo -> nickname }}
					</div>
					<!--vip用户-->
					<div class="my_level">
						<img src="img/my_vip.png"/>
						{{ $userinfo -> class_name }}会员
					</div>
				</div>

			</div>

			<div class="my_box box my_box_nav" onclick="location.href='{{ url('order') }}'" >
				<img src="img/my_etk.png" class="my_box_img" />
				ETK发货订单
				<span>
						<img src="img/ic-arrow-right.png"/>
					</span>
			</div>
			<div class="my_box box my_box_nav loading" >
				<img src="img/my_guoji.png" class="my_box_img" />
				国际物流订单
				<span>
						<img src="img/ic-arrow-right.png"/>
					</span>
			</div>
			<div class="my_box box my_box_nav" onclick="location.href='{{ url('checkAddress') }}' "   >
				<img src="img/my_addr.png" class="my_box_img"/>
				我的地址
				<span>
						<img src="img/ic-arrow-right.png"/>
					</span>
			</div>
			<div class="my_box box my_box_nav" onclick="location.href='{{ url('priceTable') }}'">
				<img src="img/my_yue.png" class="my_box_img"/>
				余额明细
				<span>
						<img src="img/ic-arrow-right.png"/>
					</span>
			</div>

			<div class="my_box box my_box_nav" onclick="location.href='{{ url('myfriend') }}'">
				<img src="img/my_frined.png" class="my_box_img"/>
				邀请好友
				<span>
						<img src="img/ic-arrow-right.png"/>
					</span>
			</div>
		</div>

		@include('layouts.home_foot')
		<script>
			$('.loading').click(function(){
			    layer.msg('敬请期待');
			})
		</script>
	</body>
</html>
