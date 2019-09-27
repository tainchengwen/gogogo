<!DOCTYPE html>
<html>
	<head>
		<title>返点订单</title>
		@include('home.return.public')
		<style>
			.add_addr_div{background:#fff;}
			.add_addr_ipt{left: 3.5rem;}
		</style>
		<script src="js/jquery.min.js"></script>
		<script>
			 $(function () {
		       $(".back_check_box").delegate(".back_guojia","click",function(){	
					if($(this).next(".back_check_ipt").is(':hidden')){
					      $(this).next(".back_check_ipt").show();
					  }else{
					      $(this).next(".back_check_ipt").hide();
					    }
					 })
		    })
		</script>
	</head>
	<body>
		<div class="content" style="padding-bottom: 6.5rem;">
			<div class="back_header">
				我的返点申请
			</div>
			<div class="form_look">
				@if($info -> status)  已返款 @else 未返款 @endif
			</div>
			<div class="tab_content">
				<div class="add_addr_div">
					<div class="add_addr_txt">购物商场/品牌</div>
					<div class="add_addr_ipt">
						{{ $return_shop -> shop_name }}
					</div>
				</div>
				<div class="add_addr_div">
					<div class="add_addr_txt">凭证<span class="form_txt_blue">(啥是凭证?)</span></div>
					<div class="add_addr_ipt">
						{{ $info -> numbers }}
					</div>
				</div>
				<div class="add_addr_div">
					<div class="add_addr_txt">实付款<span class="form_txt">(人民币)</span></div>
					<div class="add_addr_ipt">
						{{ $info -> price }}元
					</div>
				</div>
				<div class="add_addr_div">
					<div class="add_addr_txt">应返款<span class="form_txt">(人民币)</span></div>
					<div class="add_addr_ipt">
						{{ $info -> price }}元
					</div>
				</div>
				<div class="add_addr_div" style="margin-top: 0.4rem;">
					<div class="add_addr_txt">汇率</div>
					<div class="add_addr_ipt">
						1人民币=1人民币
					</div>
				</div>
				<div class="add_addr_div">
					<div class="add_addr_txt">返点率</div>
					<div class="add_addr_ipt">
						{{ $return_shop -> bili }}%
					</div>
				</div>
				<div class="form_img_look">
					<div class="form_img_look_text">图片信息</div>
					<div class="form_img_info">
						<img src="{{ $info -> image }}"/>
					</div>
				</div>
				
			</div>
		</div>
		<div class="foot">
			<div class="pay_all_btn" style="width: 100%;"  onclick="history.go(-1)">确定</div>
		</div>
	</body>
</html>
