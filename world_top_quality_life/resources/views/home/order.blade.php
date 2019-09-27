<!DOCTYPE html>
<html>
	<head>
		<title>我的物流</title>
		@include('layouts.home_header')
		<style>
			.wuliu_info{display: none;}
		</style>
		<script>
			$(function(){
				$(".wuliu_box").delegate(".wuliu_time","click",function(){
					if($(this).next(".wuliu_info").is(':hidden')){
						$(this).parent().find(".wuliu_info").show();
						$(this).find("img").attr("src","img/ic-arrow-up.png");
					}else{
						$(this).parent().find(".wuliu_info").hide();
						$(this).find("img").attr("src","img/ic-arrow-down.png");
					}
				})
			});
		</script>
	</head>
	<body>
		<div class="content">
			@include('layouts.home_tab_header')
			<div class="tab_content">
				@if(!empty($order))
					@foreach($order as $k =>  $vo)
						<div class="wuliu_box">
							<div class="wuliu_time box">
								{{ $k }}
								<img src="img/ic-arrow-down.png"/>
							</div>
							@if(!empty($vo))
								@foreach($vo as $vol)
									<div class="wuliu_info box">
										<div class="wuliu_info_content"
											 @if($vol -> status == 0)
											 onclick="location.href='{{ url('payOrder').'/'.$vol->id }}'"
											 @endif

											 @if($vol -> status == 1)
											 onclick="location.href='{{ url('writeAdd').'/'.$vol -> id }}'"
											 @endif


											 @if($vol -> status == 2 || $vol -> status == 3 || $vol -> status == 4 || $vol -> status == 5)
											 onclick="location.href='{{ url('orderInfo').'/'.$vol->id }}'"
												@endif
										>
											<div class="wuliu_info_img">
												<img src="img/newbag.png"/>
											</div>
											<div class="wuliu_info_txt">
												<div class="wuliu_info_txt_num">
													{{ $vol -> order_num }}
												</div>
												<div class="wuliu_info_txt_tongji">
													共计{{ $vol -> count_package }}个包裹
												</div>
											</div>
											<div class="wuliu_info_time">

												<div>{{ date('m-d H:i',$vol -> created_at) }}</div>
												<div class="wuliu_info_txt_status">
													{{ $vol -> status_name }}
												</div>
											</div>
										</div>


										<div class="wuliu_info_btn">
											<!-- 待填地址 待付款 -->
											@if($vol -> status == 0 && $vol -> pay_status == 0 )
												<span class="btn_white" onclick="location.href='{{ url('writeAdd').'/'.$vol -> id }}'" >填写</span>
												<span class="btn_blue" onclick="location.href='{{ url('payOrder').'/'.$vol->id }}'" >付款</span>
											@endif


										<!-- 已填地址 待付款 -->
											@if($vol -> status == 5 && $vol -> pay_status == 0 )
												<span class="btn_blue" onclick="location.href='{{ url('payOrder').'/'.$vol->id }}'" >付款</span>
											@endif

										<!-- 已付款 待填地址 -->
											@if($vol -> status == 0 && $vol -> pay_status == 1 )
												<span class="btn_white" onclick="location.href='{{ url('writeAdd').'/'.$vol -> id }}'" >填写</span>
											@endif


										<!-- 待发货状态 -->
											@if($vol -> status == 1)
												<span class="btn_white" onclick="location.href='{{ url('orderInfo').'/'.$vol -> id }}'" >查看</span>
											@endif

										<!-- 已发货状态  部分发货状态 -->
											@if($vol -> status == 2 || $vol -> status == 3 )
												<span class="btn_blue end_order" data="{{ $vol -> id }}"  >确认收货</span>
											@endif


										<!-- 显示查看的按钮 -->
											@if($vol -> status == 2 || $vol -> status == 3 )
												<span class="btn_white" onclick="location.href='{{ url('orderInfo').'/'.$vol -> id }}'" >查看</span>
											@endif
										</div>
									</div>
									@endforeach
								@endif

						</div>
					@endforeach
				@endif




		</div>
			@include('layouts.home_foot')
		<script>
			$(function(){
                $('.cancel_order').click(function(){
                    var order_id = $(this).attr('data');
                    layer.confirm('确认要取消订单么', {
                        btn: ['确定','取消'] //按钮
                    }, function(){
                        var url = '{{ url('cancelOrder') }}';
                        $.ajax({
                            type: 'POST',
                            url: url,
                            data: {id:order_id,type:1},

                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                            },
                            success: function(data){
                                if(data == 'success'){
                                    layer.msg('已取消');
                                    location.reload();
                                }
                            },
                            error: function(xhr, type){
                                //alert('Ajax error!')
                            }
                        });


                    }, function(){

                        layer.msg('已取消', {time:200});


                    });

                });

                $('.end_order').click(function(){
                    var order_id = $(this).attr('data');
                    layer.confirm('确认要确认收货么', {
                        btn: ['确定','取消'] //按钮
                    }, function(){
                        var url = '{{ url('cancelOrder') }}';
                        $.ajax({
                            type: 'POST',
                            url: url,
                            data: {id:order_id,type:2},

                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                            },
                            success: function(data){
                                if(data == 'success'){
                                    layer.msg('已确认收货');
                                    location.reload();
                                }
                            },
                            error: function(xhr, type){
                                //alert('Ajax error!')
                            }
                        });


                    }, function(){

                        layer.msg('已取消', {time:200});


                    });

                });



			})
		</script>
	</body>
</html>
