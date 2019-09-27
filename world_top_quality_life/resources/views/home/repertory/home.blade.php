<!DOCTYPE html>
<html>
	<head>
		<title>送货单预约</title>
		@include('home.repertory.public')
	</head>
	<body>
		<div class="home_img_bg">
			<img src="{{ asset('repertory/img/guojiwuliu.png') }}"  onclick="location.href='{{ url('repertory/home_express') }}' " />
			<img src="{{ asset('repertory/img/songhuoshangmen.png') }}"  onclick="location.href='{{ url('repertory/info?type=1') }}' " />
			<img src="{{ asset('repertory/img/anpaitihuo.png') }}"  onclick="location.href='{{ url('repertory/info?type=2') }}' " />
		</div>
			
	</body>
</html>
