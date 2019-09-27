<!DOCTYPE html>
<html>
	<head>
		<title>订单详情</title>
		@include('layouts.home_header')
		<style>
			.font-lay{
				font-size:15px;
			}
		</style>
	</head>
	<body>
		<div class="dingdan_header" >
			订单状态：{{ $order_info -> status_name }}
		</div>

		<div class="dingdan_info box">
			<div class="dingdan_info_div">
				订单编号
				<span>{{ $order_info -> order_num }}</span>
			</div>
			<div class="dingdan_info_div">
				支付时间
				<span>{{ date('Y-m-d H:i',$order_info -> pay_time) }}</span>
			</div>
			<div class="dingdan_info_div">
				订单内容
				<span style="color: #E64340;">共{{ $order_info -> count_package }}个包裹</span>
			</div>
			<div class="dingdan_info_div">
				支付金额
				<span>￥{{ $order_info -> pay_price }} @if($order_info -> minus_price)(已优惠￥{{ $order_info -> minus_price }})@endif</span>
			</div>
			<div class="dingdan_info_div">
				订单备注
				<span>{{ $order_info -> remark }} </span>
			</div>

		</div>


		<div class="dingdan_btns">
			<div class="wuliu_info_btn">
				<span class="btn_blue" id="copyBT" >复制完整信息</span>
				<span class="btn_blue" id="copyBT2" >仅复制单号</span>
			</div>
		</div>


		@if($uuid_res)
            <?php $num = 0; ?>
			@foreach($uuid_res as $k => $vo)
                <?php $num ++; ?>
				<div class="address_content">
					<div class="address_name">
						地址<?php echo $num; ?>
					</div>
					<div class="address_box1 box">
						<div class="address_box_info">
							<div class="address_box_div">
								{{ $uuid_res[$k][0] -> name.'   '.$uuid_res[$k][0] -> tel  }}
							</div>
							<div class="address_box_div_sm">
								{{ $uuid_res[$k][0] -> address }}
							</div>
						</div>
						@foreach($vo as $vol)
							@if($vol -> wuliu_num)
								<div class="address_box_sub">
									{{ $vol -> wuliu_num }}
									&nbsp; <a style="font-size:0.25rem;">{{ mb_substr($vol -> remark,0,20,'utf-8') }}</a>
									&nbsp; <a style="font-size:0.25rem;" data="{{ $vol -> trackingStatus }}" class="wuliu" >物流状态</a>
									&nbsp; <a style="font-size:0.25rem;" data="{{ $vol -> clear_status }}" class="qingguan">清关状态</a>
									&nbsp; <a style="font-size:0.25rem;" data="{{ $vol -> trackingList }}" class="xiangxi">详细轨迹</a>


								</div>
							@endif
						@endforeach
					</div>

				</div>
			@endforeach
		@endif



		<div id="content"  style="display:none;font-size:12px;">
			@if($uuid_res)
				@php $key = 1; @endphp
				@foreach($uuid_res as $k => $vo)

					@foreach($vo as $vol)
						@if($vol -> wuliu_num)
							<a>{{ $key.'、'.$uuid_res[$k][0] -> name.','.$vol -> wuliu_num}}</a><br>
							@php $key ++ ; @endphp
						@endif
					@endforeach
					@php $key ++ ; @endphp
				@endforeach
			@endif
		</div>

		<div id="content2"  style="display:none;font-size:12px;">
			@if($uuid_res)
				@foreach($uuid_res as $k => $vo)
					@foreach($vo as $vol)
						@if($vol -> wuliu_num)
							<a>{{$vol -> wuliu_num}}</a><br>
						@endif
					@endforeach
				@endforeach
			@endif
		</div>



		<script type="text/javascript">
            $(function(){
                $('#copyBT').click(function(){
                    layer.open({
                        type: 1,
                        skin: 'layui-layer-rim font-lay', //加上边框
                        area: ['80%', '80%'], //宽高
                        content: $('#content').html(),
						title:'复制完整信息'
                    });
				})

                $('#copyBT2').click(function(){
                    layer.open({
                        type: 1,
                        skin: 'layui-layer-rim font-lay', //加上边框
                        area: ['80%', '80%'], //宽高
                        content: $('#content2').html(),
                        title:'仅复制单号'
                    });
                })
				//物流状态
				$('.wuliu').click(function(){
					var data = $(this).attr('data');
					if(data){
						layer.alert(data);
					}else{
					    layer.msg('暂时没有获取到最新信息');
					}
				});
                $('.qingguan').click(function(){
                    var data = $(this).attr('data');
                    if(data){
                        layer.alert(data);
                    }else{
                        layer.msg('暂时没有获取到最新信息');
                    }
                });
                $('.xiangxi').click(function(){
                    var data = $(this).attr('data');
                    console.log(data);
                    if(data){
                        var json = JSON.parse(data);
                        console.log(json);
                        console.log(json.length);
                        var html = '<div style="line-height:25px;font-size:18px;">';
						for(var item in json){
                            html += json[item].Time;
                            html += '<br>';
                            html += json[item].Context;
                            html += '<br>';
                            html += '<br>';
                            //console.log(json[item].Context);
						}
						html += '</div>';

                        layer.open({
                            type: 1,
                            skin: 'layui-layer-rim font-lay', //加上边框
                            area: ['90%', '90%'], //宽高
                            content: html,
                            title:'详细轨迹'
                        });

					}else{
                        layer.msg('暂时没有获取到最新信息');
					}


                });
			})
		</script>

	</body>
</html>
