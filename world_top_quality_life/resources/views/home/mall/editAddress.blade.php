<!DOCTYPE html>
<html>
	<head>
		<title>编辑收货地址</title>
		@include('home.mall.public')
		<link rel="stylesheet" href="{{ asset('mall/css/LArea.css') }}">
		<script src="{{ asset('mall/js/LAreaData1.js') }}"></script>
		<script src="{{ asset('mall/js/LArea.js') }}"></script>

		<script>
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
				area1.value=[{{ $address -> address_code?$address -> address_code:'2,2288' }}];
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
						<div class="add_addr_txt font_color_9">收货人</div>
						<div class="add_addr_ipt">
							<input type="text" placeholder="输入真实姓名" value="{{ $address -> name }}" id="name_input"/>
						</div>
					</div>
					<div class="add_addr_div">
						<div class="add_addr_txt font_color_9">联系电话</div>
						<div class="add_addr_ipt">
							<input type="text" placeholder="输入手机号" value="{{ $address -> tel }}" id="tel_input" />
						</div>
					</div>
					<div class="add_addr_div">
						<div class="add_addr_txt font_color_9">所在地区</div>
						<div class="add_addr_ipt">
							<input id="triggerIpt" type="text" readonly="" placeholder="请选择"  value="{{ $address_value }}" >
							<input id="valueToIpt" type="hidden" value="{{ $address -> address_code }}">
							
						</div>
					</div>
					<div class="add_addr_div">
						<div class="add_addr_txt font_color_9">详细地址</div>
						<div class="add_addr_ipt">
							<input type="text" placeholder="填写街道门牌等详细信息" value="{{ $address -> address }}" id="address_input"/>
						</div>
					</div>
				</div>
				
				<div class="box addr_zhineng">
					<textarea placeholder="智能地址填写"  id="address_textarea"></textarea>
					<div class="addr_zhineng_btn" >
					   提交
					</div>
				</div>
				<div class="car_guonei_bg" style="background: none;">
					<div class="car_guonei car_op_weixuanzhong" style="margin: 0 0.4rem;">
						<input type="checkbox" name="is_moren" onchange="morenCheck(this)" />
						设为默认
					</div>
				</div>	
			</div>	
		</div>
		
		<div class="foot">
			<div class="pay_all_btn" style="width: 100%;" id="saveAddress">
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

                                if(!data){
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
                                }

                                //layer.msg('错误');return false;


                            },
                            error: function(xhr, type){
                                layer.closeAll('loading');
                            }
                        });
                    }

                })



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

                    var id = '{{ $address -> id  }}';


                    //ajax 保存地址
                    var url = '{{ url('mall/editAddressRes') }}';
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
                            is_moren:is_moren,
							id:id
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
