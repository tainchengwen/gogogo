<!DOCTYPE html>
<html>
	<head>
		<title>自约物流</title>
		@include('home.return.public')
		<style>
			.add_addr_div{background:#fff;}
			.add_addr_ipt{left: 3.5rem;}
			html,body{background: #fff;height: 100%;}
			.form_ps{
				text-align: center;
			}
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
			<div class="form_ps">
				请上传物流单号照片
			</div>
			<div class="tab_content">

				<form id="myForm">
				<div class="form_img">
					<div class="form_img_up" style="left:0.5rem;">
						<img src="img/add_img.png" onclick="$('input[id=upload-file]').click();"/>
						<input type="file" id="upload-file" name="upload-file"/>
						<input type="hidden" id="file_name" />
					</div>
				</div>
				</form>

				<div class="form_img_add"   >
					<img src="">
				</div>

			</div>
		</div>
		<div class="foot" style="">
			<div class="pay_all_btn" style="width: 100%;" id="subData" >提交</div>
		</div>

		<div class="content" id="content2" style="text-align: center;display:none;">
			<img src="img/back_success.png" style="width: 30%;margin-top: 20%;"/>
			<div style="font-size: 0.45rem;">提交成功</div>
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


                    var file_name = $('#file_name').val();
                    if(!file_name){
                        layer.msg('请上传凭证照片');
                        return false;
                    }

                    //提交数据
                    var url = '{{ url('repertory/subRepertoryData') }}';
                    layer.load(1);
                    $.ajax({
                        type: 'POST',
                        url: url,
                        data: {
                            file_name:file_name,
                            sub_type:1,
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
                                    location.href='{{ url('repertory/home') }}';
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
