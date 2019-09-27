<!DOCTYPE html>
<html>
	<head>
		<title>返点订单</title>
		@include('home.return.public')
		<style>
			.add_addr_div{background:#fff;}
			.add_addr_ipt{left: 3.5rem;}
			html,body{background: #fff;height: 100%;}
		</style>

		<script>
			 $(function () {
		       $(".back_check_box").delegate(".back_guojia","click",function(){	
					if($(this).next(".back_check_ipt").is(':hidden')){
					      $(this).next(".back_check_ipt").show();
					  }else{
					      $(this).next(".back_check_ipt").hide();
					    }
					 });

		       $('#pintiao').click(function(){
		           $(this).hide();
			   });

		       $('.form_txt_blue').click(function(){
				   $('#pintiao').show();
			   })

		    })
		
		</script>
	</head>
	<body>
		<div class="content" id="content1" style="padding-bottom: 2rem;">
			<div class="back_header">
				请填写小票内容
			</div>
			<div class="form_ps">
				请注意：每次只能提交一张小票信息。多张小票请多次提交。小票提交后保留最少24小时，请勿交给任何人。
			</div>
			<div class="tab_content">
				<div class="add_addr_div">
					<div class="add_addr_txt">购物商场/品牌</div>
					<div class="add_addr_ipt">
						{{ $shop_info -> shop_name }}
						<input type="hidden" id="shop_id" value="{{ $shop_info -> id }}" />
					</div>
				</div>
				<div class="add_addr_div">
					<div class="add_addr_txt">凭证<span class="form_txt_blue">(啥是凭证?)</span></div>
					<div class="add_addr_ipt">
						<input type="text" placeholder="请输入凭证号" id="pinzheng"  @if($return_info) value="{{ $return_info -> numbers }}" @endif />
					</div>
				</div>
				<div class="add_addr_div">
					<div class="add_addr_txt">个人编号</div>
					<div class="add_addr_ipt">
						{{ $userid }}
					</div>
				</div>
				<div class="add_addr_div">
					<div class="add_addr_txt">实付款<span class="form_txt">({{ $money_config[$shop_info -> money_type] }})</span></div>
					<div class="add_addr_ipt">
						<input type="text" placeholder="请输入实付款" id="fukuan" @if($return_info) value="{{ $return_info -> price }}" @endif />
					</div>
				</div>
				<div class="add_addr_div">
					<div class="add_addr_txt">应返款<span class="form_txt">({{ $money_config[$shop_info -> money_type] }})</span></div>
					<div class="add_addr_ipt" id="yingfu">
						@if($return_info) {{ $return_info -> fan_price }} @endif
					</div>
				</div>
				<div class="add_addr_div" style="margin-top: 0.4rem;">
					<div class="add_addr_txt">汇率</div>
					<div class="add_addr_ipt">
						1人民币={{ $shop_info -> rate.$money_config[$shop_info -> money_type] }}
					</div>
				</div>
				<div class="add_addr_div">
					<div class="add_addr_txt">返点率</div>
					<div class="add_addr_ipt">
						{{ $shop_info -> bili }}%
					</div>
				</div>
				<input type="hidden" id="bili" value="{{ $shop_info -> bili }}" />
				<form id="myForm">
				<div class="form_img">
					<div class="form_img_text">图片上传</div>
					<div class="foot_img_eg">
						<img src="img/eg_img.png"/>
						<div>(查看图例)</div>
					</div>
					<div class="form_img_up">
						<img src="img/add_img.png" onclick="$('input[id=upload-file]').click();"/>
						<input type="file" id="upload-file" name="upload-file"/>
						<input type="hidden" id="file_name" />
					</div>
				</div>
				</form>
				<div class="form_img_add" @if(isset($return_info -> image)) style="display:block;"  @endif  >
					@if(isset($return_info -> image)) <img src="{{ $return_info -> image }}"/> @else <img src="">  @endif
				</div>

				@if($return_info)
					<input type="hidden" id="return_id" value="{{ $return_info -> id }}" />
					@else
					<input type="hidden" id="return_id" value="0" />
				@endif
			</div>
		</div>
		<div class="foot" style="">
			<div class="pay_all_btn" style="width: 100%;" id="subData" >确定</div>
		</div>

		<div class="content" id="content2" style="text-align: center;display:none;">
			<img src="img/back_success.png" style="width: 30%;margin-top: 20%;"/>
			<div style="font-size: 0.45rem;">返点申请成功</div>
		</div>

		<div style="width:100%;height:100%;position:fixed;top:0;left:0;z-index: 999;display:none;" id="pintiao" >
			<img src="{{ asset('return/img/pintiao.jpg') }}" style="width:100%;height:100%;" />
		</div>


		<script>
            $(function(){

                $('#fukuan').on('input propertychange',function(){
                    console.log($('#fukuan').val());
                    if($.trim($('#fukuan').val())){
                        $('#yingfu').text( (parseFloat($('#bili').val()) * parseFloat($('#fukuan').val()) * 0.01 ).toFixed(2) );
					}

				});

                $("#upload-file").change(function(){
                    var formData = new FormData(document.getElementById("myForm"));
                    var url = '{{url('saveImg')}}';
                    layer.load(1);
                    $.ajax({
                        type: 'POST',
                        url: url,
                        data: formData,
                        dataType:'json',
                        // 告诉jQuery不要去处理发送的数据
                        processData : false,
                        // 告诉jQuery不要去设置Content-Type请求头
                        contentType : false,
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                        },
                        success: function(data){
                            layer.closeAll('loading');
                            if(data.code == 'success'){
                                $('#file_name').val(data.msg);
                                $('.form_img_add img').attr('src',data.msg);
                                $('.form_img_add').show();
                            }else{
                                layer.msg(data.msg);
                            }
                        },
                        error: function(xhr, type){
                            layer.msg('网络出错');
                        }
                    });
                });

                $('#subData').click(function(){

                    var pinzheng = $.trim($('#pinzheng').val());
                    var fukuan = $.trim($('#fukuan').val());
                    var shop_id = $('#shop_id').val();
                    var return_id = $('#return_id').val();
                    if(!pinzheng || !fukuan){
                        layer.msg('请填写凭证号和实付款');
                        return false;
                    }
                    var file_name = $('#file_name').val();
                    if(!file_name){
                        layer.msg('请上传凭证照片');
                        return false;
                    }

                    //提交数据
                    var url = '{{ url('return/subData') }}';
                    layer.load(1);
                    $.ajax({
                        type: 'POST',
                        url: url,
                        data: {
                            file_name:file_name,
                            shop_id:shop_id,
                            price:parseFloat(fukuan),
                            numbers:pinzheng,
                            return_id:return_id
                        },
                        dataType:"json",
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        success: function(data){
                            layer.closeAll('loading');
                            if(data.code == 'success'){
                                $('#content1').hide();
                                $('.foot').hide();
                                $('#content2').show();
                                setTimeout(function(){
                                    location.href='{{ url('return/returnList') }}';
                                },1200);

                            }
                        },
                        error: function(xhr, type){
                            layer.closeAll('loading');
                        }
                    });


                })

			})
		</script>


	</body>
</html>
