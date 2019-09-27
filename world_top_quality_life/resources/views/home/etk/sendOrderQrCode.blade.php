<!DOCTYPE html>
<html>
	<head>
		<title>发货二维码</title>
		@include('home.etk.public')
	</head>
	<body>
	<div class="content">
		<div class="tab_content" style="top:0;bottom: 0;">
			<div class="pay_erweima" style="margin-top: 20%;">
				{!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(200)->margin(0)->generate(url('etk/qrCodeManage').'?sign='.$sign) !!}

				<div>(向店员展示此二维码)</div>
			</div>
		</div>
	</div>


	</body>
</html>
