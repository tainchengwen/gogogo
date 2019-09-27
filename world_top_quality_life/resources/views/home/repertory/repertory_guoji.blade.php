<!DOCTYPE html>
<html>
	<head>
		<title>国际快递</title>
		@include('home.repertory.public')

	</head>
	<body>
	
		<div class="content">
			<div class="tab_content">
				
				<div class="box kuaidi_box">
					<div class="add_addr_div">
						<div class="add_addr_txt">快递单号:</div>
						<div class="add_addr_ipt">
							<input type="text" placeholder="请输入单号" id="numbers"/>
						</div>
					</div>
					<div class="add_addr_div">
						<div class="add_addr_txt">快递公司:</div>
						<div class="add_addr_ipt" style="text-align: right">
							<select id="company">
								@if($company)
									@foreach($company as $vo)
										<option>{{ $vo }}</option>
									@endforeach
								@endif
							</select>
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
							<input type="text" placeholder="输入重量" id="weight" />
						</div>
					</div>
				</div>
				
				<div class="box kuaidi_box">
					<div class="add_addr_div">
						<div class="add_addr_txt">发件日期:</div>
						<div class="add_addr_ipt">
							<input id="fajian_date" />
						</div>
					</div>
					<div class="add_addr_div">
						<div class="add_addr_txt">申报货值:</div>
						<div class="add_addr_ipt">
							<input type="number" placeholder="请输入申报货值" id="goods_value"/>
						</div>
					</div>
					<div class="add_addr_div">
						<div class="add_addr_txt">币种:</div>
						<div class="add_addr_ipt" style="text-align: right">
							<select id="currency">
								@if($currency)
									@foreach($currency as $vo)
										<option>{{ $vo }}</option>
									@endforeach
								@endif
							</select>
						</div>
					</div>
				</div>

				<div class="box kuaidi_box">
					<div class="add_addr_div" style="border: none;">
						<div class="add_addr_txt">货物状态:</div>
						<div class="add_addr_ipt car_guonei_bg">
							<div class="car_guonei car_op_xuanzhong" style="margin:0;">
								<span>需要打包</span>
								<input type="checkbox" name="status" onchange="morenCheck(this,1)" value="1"/>
							</div>
							<div class="car_guonei car_op_weixuanzhong" style="margin: 0;">
								<span>已打包</span>
								<input type="checkbox" name="status" onchange="morenCheck(this,2)" value="2"/>
							</div>
							<div class="car_guonei car_op_weixuanzhong" style="margin: 0;">
								<span>其他</span>
								<input type="checkbox" name="status" onchange="morenCheck(this,3)" value="3"/>
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

				<div class="box kuaidi_box">
					<div class="add_addr_div">
						<div class="add_addr_txt">接收送货单邮箱:</div>
						<div class="add_addr_ipt" style="left: 3.5rem;">
							<input type="text" placeholder="输入邮箱" id="mail" />
						</div>
					</div>
				</div>


			</div>	
		</div>
		
		<div class="foot">
			<div class="pay_all_btn" style="width: 100%;" id="submit">
				提 交
			</div>
		</div>

		<script>
            //执行一个laydate实例
            laydate.render({
                elem: '#fajian_date', //指定元素
                type:'date',
                format:'yyyy-MM-dd'
            });


            $('#submit').click(function(){
                var numbers = $.trim($('#numbers').val());
                var company = $.trim($('#company').val());
                var num = $.trim($('#num').val());
                var weight = $.trim($('#weight').val());
                var fajian_date = $.trim($('#fajian_date').val());
                var goods_value = $.trim($('#goods_value').val());
                var currency = $.trim($('#currency').val());
                var status = $('input[name=status]:checked').val();
                var mail = $('#mail').val();

                if(!numbers){
                    layer.msg('请填写单号');return false;
				}
				if(!num){
                    layer.msg('请填写件/板数');return false;
				}
				if(!weight){
				    layer.msg('请填写重量');return false;
				}
				if(!fajian_date){
				    layer.msg('请填写发件日期');return false;
				}
				if(!goods_value){
				    layer.msg('请填写申报货值');return false;
				}
				if(!mail){
				    layer.msg('请填写接收送货单邮箱');return false;
				}


                var url = '{{ url('repertory/orderRepertoryRes') }}';
                $.ajax({
                    type: 'POST',
                    url: url,
                    data: {
                        numbers:numbers,
                        company:company,
                        num:num,
						weight:weight,
						fajian_date:fajian_date,
						goods_value:goods_value,
						currency:currency,
						status:status,
						mail:mail
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
