<!DOCTYPE html>
<html>
	<head>
		<title>收货地址选择</title>
		@include('home.etk.public')
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



                    $(obj).addClass('address_box2_right_bianji_hui');
                    $(obj).removeClass('address_box2_right_bianji');

                    $(obj).parent().children('.address_box2_check').show();
                    $(obj).parent().children('.address_box2_del').hide();

				}
			}


			$(function(){
                $('.address_box_info').click(function(){

                });


                $('.address_box2_del').click(function(){
                    var data = $(this).attr('data');
                    //删除
                    //询问框

                    layer.confirm('确定删除么？', {
                        btn: ['确定','取消'] //按钮
                    }, function(){
                        //删除
						var url = '{{ url('delAddress') }}' +'?id='+data;
                        $.ajax({
                            type: 'POST',
                            url: url,
                            data: {},

                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                            },
                            success: function(data){
                                location.reload();
                            },
                            error: function(xhr, type){
                                //alert('Ajax error!')
                            }
                        });

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
				<div class="address_box_info" data="{{ $vo -> id}}"  @if(isset($_GET['address_index'])) onclick="location.href='{{ url('editAddress').'?address_index='.$_GET['address_index'].'&address_ids='.$_GET['address_ids'].'&order_id='.$_GET['order_id'].'&id='.$vo -> id }}' " @else onclick="location.href='{{ url('editAddress').'?id='.$vo -> id }}'" @endif  >
					<div class="address_box_div">
						<div class="address_box_div_shoujianren">收货人：</div>
						<div class="address_box_div_name">{{ $vo -> name }}</div>
						<div class="address_box_div_phone">{{ $vo -> tel }}</div>
					</div>
					<div class="address_box_div address_box_div_sm">
						收货地址：{{ $vo -> province.$vo->city.$vo->country.$vo->address }}
					</div>
				</div>
				<div class="address_box2_right">
					<div class="address_box2_right_bianji_hui" onclick="changeCheck(this)" data="{{ $vo -> id}}" >编辑</div>
					<div class="address_box2_del" style="display:none;" data="{{ $vo -> id}}" ></div>
					@if(isset($_GET['order_id']))
					<div class="address_box2_check weixuanzhong" onclick="location.href='{{ url('writeAdd/'.$_GET['order_id']).'?address_this='.$vo -> id.'&address_index='.$_GET['address_index'].'&address_ids='.$_GET['address_ids'] }}' "  >
						<input type="radio" name="seed" onclick="buyCheck(this)" />
					</div>
					@endif
				</div>
			</div>
				@endforeach
			@endif

		</div>
	</div>

	<div class="foot">
		<div class="pay_all_btn" style="width: 100%;" @if(isset($_GET['address_index'])) onclick="location.href='{{ url('addAddress').'?address_index='.$_GET['address_index'].'&address_ids='.$_GET['address_ids'].'&order_id='.$_GET['order_id'] }}' "  @else onclick="location.href='{{ url('addAddress') }}'" @endif >
			<span class="add_addr_bg"></span>
			添加新地址
		</div>
	</div>
	

	</body>
</html>
