<!DOCTYPE html>
<html>
	<head>
		<title>@if($type==1)送货上门@else安排提货@endif</title>
		@include('home.repertory.public')
		<style>
			.car_service_bg{background: #fff;}
			.car_service{display: inline-block;height: 1.2rem;line-height: 1.2rem;font-size: 0.4rem;color: #444;margin:0.32rem 0 0.32rem 0.1rem;background: #fff;}
			.car_service input{width: 0.6rem!important;height: 100%;opacity: 0;vertical-align: middle;text-align: left!important;}
			.car_service img{width: 0.6rem;vertical-align: text-top;}
			.car_op_weixuanzhong{background: url('{{ asset('repertory/img/weixuanze.png') }}')no-repeat center right;background-size:0.5rem 0.5rem;}
			.car_op_xuanzhong{background: url('{{ asset('repertory/img/xuanze_etk.png') }}')no-repeat center right;background-size:0.5rem 0.5rem;}
			.car_service span{float: left;margin-right: 0.2rem;}
		</style>
		<script>


            function morenCheck(obj,type){
                $(".car_guonei").removeClass("car_op_xuanzhong");
                $(".car_guonei").addClass("car_op_weixuanzhong");
                $(obj).parents(".car_guonei").addClass("car_op_xuanzhong");
                if(type == 3){
                    $('.qita').show();
                }else{
                    $('.qita').hide();
                }
            }


            function serviceCheck(obj,type){
                $(".car_service").removeClass("car_op_xuanzhong");
                $(".car_service").addClass("car_op_weixuanzhong");
                $(obj).parents(".car_service").addClass("car_op_xuanzhong");
                if(type == 3){
                    $('.qita_service').show();
                }else{
                    $('.qita_service').hide();
                }
            }



		</script>

	</head>
	<body>
	
		<div class="content">
			<div class="tab_content">



				@if($type == 1)

					<!-- 送货上门 -->
					<div class="box kuaidi_box">
						<div class="add_addr_div">
							<div class="add_addr_txt">送货日期:</div>
							<div class="add_addr_ipt">
								<input type="text" placeholder="请选择" id="song_date" />
							</div>
						</div>
					</div>


					<div class="box kuaidi_box">
						<!--
						<div class="add_addr_div">
							<div class="add_addr_txt">入仓号:</div>
							<div class="add_addr_ipt">
								<input type="text" placeholder="输入入仓号" id="canghao"/>
							</div>
						</div>
						-->
						<div class="add_addr_div">
							<div class="add_addr_txt">电话号码:</div>
							<div class="add_addr_ipt">
								<input type="number" placeholder="输入电话号码" id="tel"/>
							</div>
						</div>

						<div class="add_addr_div">
							<div class="add_addr_txt">身份证号码:</div>
							<div class="add_addr_ipt">
								<input type="text" placeholder="输入身份证号码" id="card"/>
							</div>
						</div>

						<div class="add_addr_div">
							<div class="add_addr_txt">接收送货单邮箱:</div>
							<div class="add_addr_ipt">
								<input type="text" placeholder="输入接收送货单邮箱" id="mail"/>
							</div>
						</div>
					</div>

					<div class="box kuaidi_box">

						<div class="add_addr_div">
							<div class="add_addr_txt">件/板数:</div>
							<div class="add_addr_ipt">
								<input type="number" placeholder="输入件/板数" id="num"/>
							</div>
						</div>
						<div class="add_addr_div">
							<div class="add_addr_txt">重量:</div>
							<div class="add_addr_ipt">
								<input type="number" placeholder="输入重量" id="weight"/>
							</div>
						</div>
					</div>


					<div class="box kuaidi_box">
						<div class="add_addr_div" style="border: none;">
							<div class="add_addr_txt">货物状态:</div>
							<div class="add_addr_ipt car_guonei_bg">
								<div class="car_guonei car_op_xuanzhong" style="margin:0;">
									<span>需要打包</span>
									<input type="checkbox" name="package_status" onchange="morenCheck(this,1)" value="0"/>
								</div>
								<div class="car_guonei car_op_weixuanzhong" style="margin: 0;">
									<span>已打包</span>
									<input type="checkbox" name="package_status" onchange="morenCheck(this,2)" value="1"/>
								</div>
								<div class="car_guonei car_op_weixuanzhong" style="margin: 0;">
									<span>其他</span>
									<input type="checkbox" name="package_status" onchange="morenCheck(this,3)" value="999"/>
								</div>
							</div>
						</div>
						<div class="add_addr_div qita" style="border: none;margin-bottom: 0.2rem;display:none;">
							<div class="add_addr_txt">其他:</div>
							<div class="add_addr_ipt other_box">
								<input type="text" class="remark"  />
							</div>
						</div>
					</div>




					@else
					<!-- 安排提货 -->

					<div class="box kuaidi_box">
						<div class="add_addr_div">
							<div class="add_addr_txt">预约提货日期:</div>
							<div class="add_addr_ipt">
								<input type="text" placeholder="请选择" id="ti_date" />
							</div>
						</div>
					</div>


					<div class="box kuaidi_box">
						<!--
						<div class="add_addr_div">
							<div class="add_addr_txt">入仓号:</div>
							<div class="add_addr_ipt">
								<input type="text" placeholder="输入入仓号" id="canghao"/>
							</div>
						</div>
						-->
						<div class="add_addr_div">
							<div class="add_addr_txt">联系人:</div>
							<div class="add_addr_ipt">
								<input type="text" placeholder="输入联系人" id="name"/>
							</div>
						</div>

						<div class="add_addr_div">
							<div class="add_addr_txt">电话号码:</div>
							<div class="add_addr_ipt">
								<input type="number" placeholder="输入电话号码" id="tel"/>
							</div>
						</div>



						<div class="add_addr_div">
							<div class="add_addr_txt">提货地址:</div>
							<div class="add_addr_ipt">
								<input type="text" placeholder="输入提货地址" id="address"/>
							</div>
						</div>
					</div>



						<div class="box kuaidi_box">

							<div class="add_addr_div">
								<div class="add_addr_txt">件/板数:</div>
								<div class="add_addr_ipt">
									<input type="text" placeholder="输入件/板数" id="num"/>
								</div>
							</div>
							<div class="add_addr_div">
								<div class="add_addr_txt">重量:</div>
								<div class="add_addr_ipt">
									<input type="number" placeholder="输入重量" id="weight"/>
								</div>
							</div>
						</div>

						<div class="box kuaidi_box">
							<div class="add_addr_div" style="border: none;">
								<div class="add_addr_txt">服务选择:</div>
								<div class="add_addr_ipt car_service_bg">
									<div class="car_service car_op_xuanzhong" style="margin:0;">
										<span>打包发货</span>
										<input type="checkbox" name="service_type" onchange="serviceCheck(this,1)" value="1"/>
									</div>
									<div class="car_service car_op_weixuanzhong" style="margin: 0;">
										<span>直接发货</span>
										<input type="checkbox" name="service_type" onchange="serviceCheck(this,2)" value="2"/>
									</div>
									<div class="car_service car_op_weixuanzhong" style="margin: 0;">
										<span>其他</span>
										<input type="checkbox" name="service_type" onchange="serviceCheck(this,3)" value="999"/>
									</div>
								</div>
							</div>
							<div class="add_addr_div qita_service" style="border: none;margin-bottom: 0.2rem;display:none;">
								<div class="add_addr_txt">其他:</div>
								<div class="add_addr_ipt other_box">
									<input type="text" class="remark"  />
								</div>
							</div>
						</div>



				@endif

					<div class="box kuaidi_box">

						<textarea style="width:100%;height:100px;border:none;" placeholder="文字暂存区域"></textarea>
					</div>


				
			</div>
		</div>
		
		<div class="foot">
			@if($type == 1)
			<div class="pay_all_btn" style="width: 100%;" id="submit">
				提 交
			</div>
				@else
			<div class="pay_all_btn" style="width: 100%;" id="submit_type2">
				提 交
			</div>
			@endif

		</div>
		<script>
            //执行一个laydate实例
            laydate.render({
                elem: '#song_date', //指定元素
                type:'date',
                format:'yyyy-MM-dd'
            });

            laydate.render({
                elem: '#ti_date', //指定元素
                type:'date',
                format:'yyyy-MM-dd'
            });


            $('#submit').click(function(){

                var song_date = $.trim($('#song_date').val());
                var tel = $.trim($('#tel').val());
                var card = $.trim($('#card').val());
                var mail = $('#mail').val();
                var num = $.trim($('#num').val());
                var weight = $.trim($('#weight').val());
                var package_status = $('input[name=package_status]:checked').val();
				var remark = $('.remark').val();

                if(!song_date){
                    layer.msg('请选择送货日期');return false;
                }
                if(!tel){
                    layer.msg('请填写发件人手机号');return false;
                }
                if(!card){
                    layer.msg('请填写发件人身份证');return false;
                }
                if(!mail){
                    layer.msg('请填写接收送货单邮箱');return false;
                }
                if(!num){
                    layer.msg('请填写件/板数');return false;
                }
                if(!weight){
                    layer.msg('请填写重量');return false;
                }






                var url = '{{ url('repertory/orderRepertoryRes') }}';
                $.ajax({
                    type: 'POST',
                    url: url,
                    data: {
                        song_date:song_date,
                        num:num,
                        weight:weight,
                        name:name,
                        card:card,
                        tel:tel,
                        package_status:package_status,
                        mail:mail,
						sub_type:2,
                        remark:remark,
                    },
                    dateType:'json',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function (data) {
						if(data.code == 'success'){
						    layer.msg('提交成功');
						    setTimeout(function(){
						        location.href='{{ url('repertory/home') }}';
							},1200);
						}
                    },
                    error: function (xhr, type) {
                        layer.msg('数据错误');
                    }
                });
            });




            $('#submit_type2').click(function(){

                var ti_date = $.trim($('#ti_date').val());
                var name = $.trim($('#name').val());
                var tel = $.trim($('#tel').val());
                var address = $.trim($('#address').val());
                var num = $.trim($('#num').val());
                var weight = $.trim($('#weight').val());
                var service_type = $('input[name=service_type]:checked').val();

                var remark = $('.remark').val();

                if(!ti_date){
                    layer.msg('请选择提货日期');return false;
                }
                if(!name){
                    layer.msg('请填写发件人');return false;
                }
                if(!tel){
                    layer.msg('请填写发件人手机号');return false;
                }
                if(!address){
                    layer.msg('请填写提货地址');return false;
                }

                if(!num){
                    layer.msg('请填写件/板数');return false;
                }
                if(!weight){
                    layer.msg('请填写重量');return false;
                }






                var url = '{{ url('repertory/orderRepertoryRes') }}';
                $.ajax({
                    type: 'POST',
                    url: url,
                    data: {
                        ti_date:ti_date,
                        name:name,
                        tel:tel,
                        address:address,
                        num:num,
                        weight:weight,
                        service_type:service_type,
                        remark:remark,
                        sub_type:3
                    },
                    dateType:'json',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function (data) {
                        if(data.code == 'success'){
                            layer.msg('提交成功');
                            setTimeout(function(){
                                location.href='{{ url('repertory/home') }}';
                            },1200);
                        }
                    },
                    error: function (xhr, type) {
                        layer.msg('数据错误');
                    }
                });
            });

		</script>



	</body>
</html>
