<!DOCTYPE html>
<html>
	<head>
		<title>新增收货地址</title>
		@include('home.mall.public')
		<link rel="stylesheet" href="{{ asset('mall/css/LArea.css') }}">
		<script src="{{ asset('mall/js/LAreaData1.js') }}"></script>
		<script src="{{ asset('mall/js/LArea.js') }}"></script>
		<style>
		</style>
		<script>
			var area1,ss;
			$(function(){
				var area1 = new LArea();
				area1.init({
					'trigger': '#triggerIpt', //触发选择控件的文本框，同时选择完毕后name属性输出到该位置
					'valueTo': '#valueToIpt', //选择完毕后id属性输出到该位置
					'keys': {
						id: 'id',
						name: 'name'
					}, 
					'type': 1, 
					'data': LAreaData 
				});
                area1.value=[2,2288];
			});
			function morenCheck(obj){
				if ($(obj).is(':checked')) {
						$(obj).parents(".car_guonei").removeClass("car_op_weixuanzhong");
						$(obj).parents(".car_guonei").addClass("car_op_xuanzhong");
					}else{
						$(obj).parents(".car_guonei").removeClass("car_op_xuanzhong");
						$(obj).parents(".car_guonei").addClass("car_op_weixuanzhong");
					};
			}
		</script>
	</head>
	<body>
		<div class="content">
			<div class="tab_content">
				<div class="tab_qigndan_title" style="background: none;border:none">
					<img src="{{ asset('mall/img/addr.png') }}" style="margin-right: 0.1rem;color: #666;"/>
					地址信息
				</div>
				
				<div class="box">
					<div class="add_addr_div">
						<div class="add_addr_txt">收货人</div>
						<div class="add_addr_ipt">
							<input type="text" placeholder="输入真实姓名" id="name_input"/>
						</div>
					</div>
					<div class="add_addr_div">
						<div class="add_addr_txt">联系电话</div>
						<div class="add_addr_ipt">
							<input type="text" placeholder="输入手机号" id="tel_input"/>
						</div>
					</div>
					<div class="add_addr_div">
						<div class="add_addr_txt">所在地区</div>
						<div class="add_addr_ipt">
							<input id="triggerIpt" type="text" readonly="" placeholder="请选择"  value="">
							<input id="valueToIpt" type="hidden" value="">
							
						</div>
					</div>
					<div class="add_addr_div">
						<div class="add_addr_txt">详细地址</div>
						<div class="add_addr_ipt">
							<input type="text" placeholder="填写街道门牌等详细信息" id="address_input"/>
						</div>
					</div>
				</div>
				
				<div class="box addr_zhineng">
					<textarea placeholder="粘贴整段地址，自动识别姓名、电话和地址 例：张小哥，13200000000，广东省深圳市南山区某某街道某某大厦001号" id="address_textarea"></textarea>
					<div class="addr_zhineng_btn">
					   提交
					</div>
				</div>
				<div class="car_guonei_bg" style="background: none;">
					<div class="car_guonei car_op_weixuanzhong" style="margin: 0 0.4rem;">
						<input type="checkbox" name="is_moren" onchange="morenCheck(this)" value="1" />
						设为默认
					</div>
				</div>	
			</div>	
		</div>
		
		<div class="foot">
			<div class="pay_all_btn " style="width: 100%;" id="saveAddress">
				保存地址
			</div>
		</div>


		<script>
			$(function(){
			    //智能地址
				$('.addr_zhineng_btn').click(function(){
				    var address_text = $.trim($('#address_textarea').val() );
				    if(address_text){
				        //请求顺丰接口
                        var url = '{{ url('mall/getExtAddress') }}';
                        layer.load(1);
                        $.ajax({
                            type: 'POST',
                            url: url,
                            data: {
                                address:address_text,
                            },
                            dataType:"json",
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            success: function(data){
                                layer.closeAll('loading');
                                console.log(111);
                                console.log(data);
                                if(data.code == '500'){
                                    layer.msg('识别失败,用逗号分隔试试');
                                    return false;
                                }

								var obj = data.obj;
								if(obj.personalName){
									$('#name_input').val(obj.personalName);
								}

								if(obj.telephone){
                                    $('#tel_input').val(obj.telephone);
								}

                                if(obj.site){
                                    $('#address_input').val(obj.site);
                                }

								//拼接地址

								if(obj.province){
                                    var temp = obj.province;
                                    if(obj.city){
                                        temp += ','+obj.city;
                                        if(obj.area){
                                            temp += ','+obj.area;
                                        }
									}

                                    $('#triggerIpt').val(temp);

                                    ss = area1.getId(obj.province,obj.city,obj.area);
                                    area1.value=ss;


                                    //重新初始化
									/*
                                    var area2 = new LArea();
                                    area2.init({
                                        'trigger': '#triggerIpt', //触发选择控件的文本框，同时选择完毕后name属性输出到该位置
                                        'valueTo': '#valueToIpt', //选择完毕后id属性输出到该位置
                                        'keys': {
                                            id: 'id',
                                            name: 'name'
                                        },
                                        'type': 1,
                                        'data': LAreaData
                                    });
                                    var ss = area2.getId(obj.province,obj.city,obj.area);
                                    console.log(ss);
                                    area1.value=ss;
                                    */

								}

                                //layer.msg('错误');return false;


                            },
                            error: function(xhr, type){
                                layer.closeAll('loading');
                            }
                        });
					}

				});



			    $('#saveAddress').click(function(){

			        var is_moren = $('input[name=is_moren]:checked').val();
			        //判断必填
					var name = $.trim($('#name_input').val());
					var tel = $.trim($('#tel_input').val());
					//省市区的合体
					var triggerIpt = $.trim($('#triggerIpt').val());
					var valueToIpt = $.trim($('#valueToIpt').val());
					var address = $.trim($('#address_input').val());

					if(!name){
						layer.msg('请填写姓名');return false;
					}

                    if(!tel){
                        layer.msg('请填写手机号');return false;
                    }

                    var reg = /^1[3|4|5|6|7|8|9][0-9]{9}$/;
                    if(!reg.test(tel)){
                        layer.msg("手机号码有误");
                        return false;
                    }


                    if(!triggerIpt){
                        layer.msg('请选择地区');return false;
                    }

                    if(!address){
                        layer.msg('请填写地址');return false;
                    }


                    //ajax 保存地址
					var url = '{{ url('mall/addAddressRes') }}';
					layer.load(1);
                    $.ajax({
                        type: 'POST',
                        url: url,
                        data: {
                            name:name,
                            tel:tel,
                            triggerIpt:triggerIpt,
                            valueToIpt:valueToIpt,
                            address:address,
                            is_moren:is_moren
                        },
                        //dataType:"json",
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        success: function(data){
                            layer.closeAll('loading');
							if(data == 'success'){
							    location.href='{{ url('mall/checkAddress') }}';
							}
                            return false;

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
