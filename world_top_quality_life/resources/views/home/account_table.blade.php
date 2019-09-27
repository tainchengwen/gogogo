<!DOCTYPE html>
<html>
	<head>
		<title>余额明细</title>
		@include('layouts.home_header')
		<style>
			.tab_sp4{
				width:33%;
			}
		</style>
	</head>
	<body>
		<div class="content">
			<div class="tab_header">
				<div class="tab_sp4 @if(!isset($_GET['type'])) tab_sp_active @endif " onclick="location.href='{{ url('priceTable') }}'" >
					<span >全部</span>
				</div>
				<div class="tab_sp4 @if(isset($_GET['type']) && $_GET['type'] == 1) tab_sp_active @endif "  onclick="location.href='{{ url('priceTable').'?type=1' }}'" >
					<span>只看支出</span>
				</div>
				<div class="tab_sp4  @if(isset($_GET['type']) && $_GET['type'] == 2) tab_sp_active @endif  " onclick="location.href='{{ url('priceTable').'?type=2' }}'" >
					<span>只看收入</span>
				</div>
				<!--
				<div class="tab_sp4 @if(isset($_GET['type']) && $_GET['type'] == 3) tab_sp_active @endif  "  >
					<span>只看提现</span>
				</div>
				-->
			</div>
			<div class="tab_content" style="bottom: 0;padding-top: 0.26rem;">
				@if($log)
					@foreach($log as $vo)
				<div class="yue_box">
					<div class="yue_info">
						@if($vo -> order_id)
						<div class="yue_name">
							订单编号：{{ $vo -> order_id }}
						</div>
						@endif

						<div class="yue_time">
							{{ date('Y-m-d H:i',$vo -> created_at) }}
						</div>
						<div class="yue_num">
							余额：{{ $vo -> end_price }}
						</div>
					</div>
					<div class="yue_zj">
						<div class="yue_zj_type">{{ $vo ->type_name }}</div>
						<div class="yue_zj_num" @if($vo -> in_out == 0) style="color:#1AAD19" @endif >{{ $vo -> price }}</div>
					</div>
				</div>
					@endforeach
				@endif
			</div>
			
		</div>
	</body>
</html>
