<!DOCTYPE html>
<html>
	<head>
		<title>我的账户</title>
		@include('layouts.home_header')
		<script>
            $(function(){
                $(".diaog_bg").click(function(){
                    $(".diaog_bg").hide();
                });
                $(".diaog_close").click(function(){
                    $(".diaog_bg").hide();
                });
                $('.pay_all_btn').click(function(){
                    $(".diaog_bg").show();
				})
            });
		</script>
	</head>
	<body>
	<div class="content">
		@if(!empty($users))
		<div class="my_f_num">
			共有<span>{{ count($users) }}</span>个好友
		</div>

			@foreach($users as $vo)
				@if($vo -> headimg && $vo -> nickname)

		<div class="my_f_div">
			<div class="my_f_header">
				<img src="{{ $vo -> headimg }}"/>
			</div>
			<div class="my_f_name">{{ $vo -> nickname }}</div>
			<!--
			<div class="my_f_time"><span>删除</span></div>
			-->
		</div>
				@endif
			@endforeach
		@endif
	</div>
	<div class="foot">
		<div class="pay_all_btn" style="width: 100%;">获取我的专属邀请码</div>
	</div>

	<div class="diaog_bg">
		<div class="diaog">
			<img class="diaog_close" src="{{ asset('img/close.png') }}"/>
			<div class="diaog_title">邀请好友加入我们</div>
			<div class="diaog_txt">请好友扫一扫或分享此图给您的好友</div>
			<img src="{{ $url }}"/>

		</div>
	</div>

	</body>
</html>
