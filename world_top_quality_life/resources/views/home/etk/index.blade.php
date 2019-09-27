<!DOCTYPE html>
<html>
<head>
	<title>etk发货</title>
	@include('home.etk.public')

</head>
<body>
<div class="home_img_bg">
	<img src="{{ asset('etk/img/home3.png') }}" onclick="location.href='{{ url('etk/sendOrderQrCode') }}' " />
	<!--
	<img src="{{ asset('etk/img/home4.png') }}" class="loading"/>
	-->
</div>
@include('home.etk.foot')
<script>
    $('.loading').click(function(){
        layer.msg('敬请期待');
    })
</script>
</body>
</html>
