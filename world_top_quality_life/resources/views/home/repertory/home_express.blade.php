<!DOCTYPE html>
<html>
	<head>
		<title>国际快递选择</title>
		@include('home.repertory.public')
	</head>
	<body>
		<div class="home_img_bg">
			<img src="{{ asset('repertory/img/anpaiwuliu.png') }}" onclick="location.href='{{ url('repertory/express_info').'?type=1' }}'"/>
			<img src="{{ asset('repertory/img/bangwoyuyue.png') }}" onclick="layer.msg('正在开发中,敬请期待')" />
		</div>
			
	</body>
</html>
