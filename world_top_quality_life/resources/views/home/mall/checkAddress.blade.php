<!DOCTYPE html>
<html>
	<head>
		<title>收货地址选择</title>
		@include('home.mall.public')
		<script>
			function buyCheck(obj){
				    $(".address_box2_check").removeClass("xuanzhong");
				    $(".address_box2_check").addClass("weixuanzhong");
				if ($(obj).is(':checked')) {
					$(obj).parents(".address_box2_check").removeClass("weixuanzhong");
					$(obj).parents(".address_box2_check").addClass("xuanzhong");
				}else{
					$(obj).parents(".address_box2_check").removeClass("xuanzhong");
					$(obj).parents(".address_box2_check").addClass("weixuanzhong");
				};			
			}


			function changeCheck(obj){
			    if($(obj).hasClass('address_box2_right_bianji_hui')){
			        //开始编辑
                    $(obj).removeClass('address_box2_right_bianji_hui');
                    $(obj).addClass('address_box2_right_bianji');

                    $(obj).parent().children('.address_box2_check').hide();
                    $(obj).parent().children('.address_box2_del').show();

				}else{
			        //结束编辑 再点一下 进入地址编辑页面
					var data = $(obj).attr('data');
					//alert(data);
					//location.href='{{ url('mall/editAddress') }}'+'?id='+data;


                    $(obj).addClass('address_box2_right_bianji_hui');
                    $(obj).removeClass('address_box2_right_bianji');

                    $(obj).parent().children('.address_box2_check').show();
                    $(obj).parent().children('.address_box2_del').hide();

				}
			}


			$(function(){
                $('.address_box_info').click(function(){
                    var data = $(this).attr('data');
                    //alert(data);
                    location.href='{{ url('mall/editAddress') }}'+'?id='+data;
                });


                $('.address_box2_del').click(function(){
                    var data = $(this).attr('data');
                    //删除
                    //询问框

                    layer.confirm('确定删除么？', {
                        btn: ['确定','取消'] //按钮
                    }, function(){
                        //删除
						location.href='{{ url('mall/delAddress') }}' +'?id='+data;
                    }, function(){
                        layer.msg('已取消', {
                            time: 800,
                        });
                    });
				})
			})
		</script>
	</head>
	<body>
	
		<div class="content">
			<div class="tab_content" style="padding-top: 0.21rem;">
				@if(count($address))
					@foreach($address as $vo)
						<div class="address_box2">
							<div class="address_box_info" data="{{ $vo -> id}}" >
								<div class="address_box_div">
									收货人：{{ $vo -> name }}  {{ $vo -> tel }}
								</div>
								<div class="address_box_div address_box_div_sm">
									收货地址：{{ $vo -> province.$vo->city.$vo->country.$vo->address }}
								</div>
							</div>
							<div class="address_box2_right">
								<div class="address_box2_right_bianji_hui"  onclick="changeCheck(this)" data="{{ $vo -> id}}">编辑</div>
								<div class="address_box2_del" style="display:none;" data="{{ $vo -> id}}" ></div>
								@if(!$return)
								<div class="address_box2_check weixuanzhong" onclick="location.href='{{ url('mall/submitbuy').'?spIds='.session('mall_spIds').'&address_id='.$vo -> id }}' " >
									<input type="radio" name="seed" onclick="buyCheck(this)"/>
								</div>
								@endif
							</div>
						</div>
					@endforeach
				@endif


				<!--
				<div class="address_box2">
					<div class="address_box_info">
						<div class="address_box_div">
							收货人：许多钱  15212347055 
						</div>
						<div class="address_box_div address_box_div_sm">
							收货地址：江苏省南京市江宁区  学院路100号南科技大学东区 教学楼210
						</div>
					</div>
					<div class="address_box2_right">
						<div class="address_box2_right_bianji_hui">编辑</div>
						<div class="address_box2_check weixuanzhong">
							<input type="radio" name="seed" onclick="buyCheck(this)"/>
						</div>
					</div>
				</div>
				
				<div class="address_box2">
					<div class="address_box_info">
						<div class="address_box_div">
							收货人：许多钱  15212347055 
						</div>
						<div class="address_box_div address_box_div_sm">
							收货地址：江苏省南京市江宁区  学院路100号南科技大学东区 教学楼210
						</div>
					</div>
					<div class="address_box2_right">
						<div class="address_box2_right_bianji_hui">编辑</div>
						<div class="address_box2_check weixuanzhong">
							<input type="radio" name="seed" onclick="buyCheck(this)"/>
						</div>
					</div>
				</div>
			  -->
				
				
				
				
				
				
				
			</div>	
		</div>
		
		<div class="foot">
			<div class="pay_all_btn" style="width: 100%;" onclick="location.href='{{ url('mall/addAddress') }}' ">
				<img src="{{ asset('mall/img/add_addr.png') }}"/>
				添加新地址
			</div>
		</div>
	</body>
</html>
