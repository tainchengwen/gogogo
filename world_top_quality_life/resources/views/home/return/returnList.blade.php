<!DOCTYPE html>
<html>
	<head>
		<title>返点订单</title>
		@include('home.return.public')
	</head>
	<body>
		<div class="content">
			<div class="tab_header_dingdan">
				<div class="tab_sp3 @if(!isset($_GET['type']) || $_GET['type'] == 1) tab_sp_active @endif" onclick="location.href='{{ url('return/returnList').'?type=1' }}' ">
					<span>全部</span>
				</div>
				<div class="tab_sp3 @if(isset($_GET['type']) && $_GET['type'] == 2) tab_sp_active @endif" onclick="location.href='{{ url('return/returnList').'?type=2' }}' ">
					<span>待返</span>
				</div>
				<div class="tab_sp3 @if(isset($_GET['type']) && $_GET['type'] == 3) tab_sp_active @endif" onclick="location.href='{{ url('return/returnList').'?type=3' }}' ">
					<span>已返</span>
				</div>
			</div>
			<div class="tab_content" style="padding-top: 1.12rem">
				@if($data)
					@foreach($data as $vo)
				<div class="back_list_box"
					 @if($vo -> status == 1 || $vo -> status == 0)
					 	 onclick="location.href='{{ url('return/look').'?id='.$vo -> id }}' "
					 @else
						 onclick="location.href='{{ url('return/writePage').'?check_value='.$vo -> shop_id.'&return_id='.$vo -> id }}' "
						@endif
				>
					<div class="back_list_title">
						{{ $vo -> shop_name }}
						<span>{{ date('m-d H:i',$vo -> created_at) }}</span>
					</div>
					<div class="back_list_num">
						待返金额: {{ $vo -> fan_price }}元
						<span>
							@if($vo -> status == 1)
								已返
							@elseif($vo -> status == 2)
								驳回
							@else
								待返
							@endif
						</span>
					</div>
				</div>
					@endforeach
				@endif
			</div>
		</div>
		<div class="foot">
			@include('home.return.foot')
		</div>
	</body>
</html>
