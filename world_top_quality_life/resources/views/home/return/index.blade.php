<!DOCTYPE html>
<html>
	<head>
		<title>返点订单</title>
		@include('home.return.public')
		<script>
			 $(function () {
		       $(".back_check_box").delegate(".back_guojia","click",function(){	
					if($(this).next(".back_check_ipt").is(':hidden')){
					      $(this).next(".back_check_ipt").show();
					  }else{
					      $(this).next(".back_check_ipt").hide();
					    }
					 });
		    });
		function buyCheck(obj){
				    $(".shaixuan_check").removeClass("xuanzhong");
				    $(".shaixuan_check").addClass("weixuanzhong");
				if ($(obj).is(':checked')) {
					$(obj).parents(".shaixuan_check").removeClass("weixuanzhong");
					$(obj).parents(".shaixuan_check").addClass("xuanzhong");
				}else{
					$(obj).parents(".shaixuan_check").removeClass("xuanzhong");
					$(obj).parents(".shaixuan_check").addClass("weixuanzhong");
				};			
			}
		</script>
	</head>
	<body>
		<div class="content" style="padding-bottom: 3.5rem;">
			<div class="back_header" style="margin-bottom: 0.4rem;">
				请选择购物的商铺/商品
			</div>
			<div class="tab_content">
				@if($data)

					@foreach($data as $k =>$vo)

						@if($k == '1')
							<div class="back_check_box">
								<div class="back_guojia" >
									<span class="back_guoqi"><img src="img/zhongguo.png"/></span>
									<span>中国（CHINA）</span>
									<span class="back_down">
							<img src="img/back_down.png"/>
						</span>
								</div>
								<div class="back_check_ipt" style="display:block;">
									@foreach($vo as $key => $value)
										@if($key == 0)
									<div class="back_check_ipt_div" data="{{ $value -> id }}" >
										<div class="shaixuan_check xuanzhong" >
											<input type="radio" name="seed" onclick="buyCheck(this)" value="{{ $value -> id }}"  checked class="input_check" />
										</div>
										{{ $value -> shop_name }}
									</div>
											@else
											<div class="back_check_ipt_div" data="{{ $value -> id }}" >
												<div class="shaixuan_check weixuanzhong" >
													<input type="radio" name="seed" onclick="buyCheck(this)" value="{{ $value -> id }}"  class="input_check" />
												</div>
												{{ $value -> shop_name }}
											</div>
										@endif
									@endforeach
								</div>
							</div>


							@elseif($k == '3')
							<div class="back_check_box">
								<div class="back_guojia">
									<span class="back_guoqi"><img src="img/riben.png"/></span>
									<span>日本（JAPAN）</span>
									<span class="back_down">
							<img src="img/back_down.png"/>
						</span>
								</div>
								<div class="back_check_ipt" style="display:block;">
									@foreach($vo as $value)
										<div class="back_check_ipt_div" data="{{ $value -> id }}" >
											<div class="shaixuan_check weixuanzhong" >
												<input type="radio" name="seed" onclick="buyCheck(this)" value="{{ $value -> id }}"  class="input_check" />
											</div>
											{{ $value -> shop_name }}
										</div>
									@endforeach
								</div>
							</div>

							@elseif($k == '2')
							<div class="back_check_box">
								<div class="back_guojia">
									<span class="back_guoqi"><img src="img/hanguo.png"/></span>
									<span>韩国（KOREA）</span>
									<span class="back_down">
							<img src="img/back_down.png"/>
						</span>
								</div>
								<div class="back_check_ipt" style="display:block;">
									@foreach($vo as $value)
										<div class="back_check_ipt_div" data="{{ $value -> id }}" >
											<div class="shaixuan_check weixuanzhong" >
												<input type="radio" name="seed" onclick="buyCheck(this)" value="{{ $value -> id }}"  class="input_check" />
											</div>
											{{ $value -> shop_name }}
										</div>
									@endforeach
								</div>
							</div>
						@endif

					@endforeach

				@endif









			</div>
		</div>
		<div class="foot">
			<div class="pay_all_btn" style="width: 100%;top:-1.5rem;" id="nextBtn">下一步</div>
			@include('home.return.foot')
		</div>
		<script>
			$(function(){
			    $('#nextBtn').click(function(){
			        var check_value = $('input[name=seed]:checked').val();

			        //console.log(check_value);
					location.href='{{ url('return/writePage') }}'+'?check_value='+check_value;
				})
			})
		</script>
	</body>
</html>
