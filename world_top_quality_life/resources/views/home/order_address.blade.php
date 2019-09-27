<!DOCTYPE html>
<html>
	<head>
		<title>填写地址</title>
		@include('layouts.home_header')
		<!--
		<script src="http://res.wx.qq.com/open/js/jweixin-1.1.0.js" type="text/javascript" charset="utf-8"></script>
		<script type="text/javascript" charset="utf-8">
            wx.config(php echo $app->jssdk->buildConfig(array('onMenuShareQQ', 'onMenuShareWeibo','openAddress','editAddress','checkJsApi'), false) ?>);
            wx.ready(function(){

                wx.checkJsApi({
                    jsApiList: ['openAddress'], // 需要检测的JS接口列表，所有JS接口列表见附录2,
                    success: function(res) {
                        // 以键值对的形式返回，可用的api值true，不可用为false
                        // 如：{"checkResult":{"chooseImage":true},"errMsg":"checkJsApi:ok"}
                    }
                });



            });
		</script>
		-->
		<style>
			.openfont{
				font-size:15px;
			}
		</style>
	</head>
	<body>
		<div class="dingdan_header" >
			订单状态：等待填写地址
		</div>
		<div class="dingdan_info box">
			<div class="dingdan_info_div">
				订单编号
				<span>{{ $order_info -> order_num }}</span>
				<input type="hidden" id="order_id" value="{{ $order_info -> id }}" />
			</div>
			<!--
			<div class="dingdan_info_div">
				支付时间
				<span>{{ date('Y-m-d H:i:s',$order_info -> pay_time) }}</span>
			</div>
			<div class="dingdan_info_div">
				支付方式
				<span>余额支付</span>
			</div>
			-->
			<div class="dingdan_info_div">
				订单内容
				<span style="color: #E64340;">共{{ $order_info -> count_package }}个包裹</span>
			</div>
			<div class="dingdan_info_div">
				订单备注
				<span style="color: #E64340;">{{ $order_info -> remark }}</span>
			</div>
		</div>


		@for($i=1;$i<= $order_info -> count_package;$i++)
		<div class="address_content" onclick="location.href='{{ url('checkAddress').'?address_ids='.$address_ids.'&address_index='.$i.'&order_id='.$order_info -> id }}'" >
			<div class="address_name">
				地址{{ $i }}
			</div>
			<div class="address_box box">
				@if($address_infos && isset($address_infos[$i] ))
					<input type="hidden" class="address_num" value="1" />
					<input type="hidden" class="form_username" value="{{ $address_infos[$i] -> name }}"  />
					<input type="hidden" class="form_tel"  value="{{ $address_infos[$i] -> tel }}" />
					<input type="hidden" class="form_address"  value="{{ $address_infos[$i] -> address }}" />
					<input type="hidden" class="form_code" value=""  />
					<input type="hidden" class="form_province"  value="{{ $address_infos[$i] -> province }}"  />
					<input type="hidden" class="form_city" value="{{ $address_infos[$i] -> city }}" />
					<div class="address_box_div">
						{{ $address_infos[$i] -> name.'   '.$address_infos[$i] -> tel }}
					</div>
					<div class="address_box_div_sm" >
						{{ $address_infos[$i] -> province.$address_infos[$i] -> city.$address_infos[$i] -> country.$address_infos[$i] -> address }}
					</div>

					@else
					<span class="temp_span">
					<div class="address_box_div">
						请选择地址
					</div>
					<img src="{{ asset('img/ic-arrow-right.png') }}"/>
					</span>
					<input type="hidden" class="address_num" value="0" />
				@endif

			</div>


		</div>
		@endfor



		<div class="btn_big_bg">
			<button class="btn_big" id="subOrderAddress">提交</button>
			<input type="hidden" id="countAddress" value="{{ $order_info -> count_package }}" />
			<input type="hidden" id="countPackage" value="{{ $order_info -> count_package }}" />
			<input type="hidden" id="userid" value="{{ $order_info -> userid }}" />
		</div>

		<script>
			$(function(){
			    /*
			    $('.address_content').click(function(){
			        var self = $(this);
                    wx.openAddress({
                        success: function (res) {
                            //alert(res.userName + '  ' +res.telNumber);

                            self.find('.address_box_div').text(res.userName + '  ' +res.telNumber );
                            self.find('.form_username').val(res.userName);
                            self.find('.form_tel').val(res.telNumber);

                            self.find('.form_code').val(res.postalCode);



                            self.find('.address_box_div_sm').text(res.provinceName+res.cityName+res.countryName +res.detailInfo);

                            self.find('.form_province').val(res.provinceName);
                            self.find('.form_city').val(res.cityName);


                            self.find('.form_address').val(res.provinceName+res.cityName+res.countryName +res.detailInfo);

                            self.find('.temp_span').hide();
                            self.find('.address_box_div_sm').show();
                            self.find('.address_num').val(1);

                        },
                        cancel: function () {
                            // 用户取消拉出地址
                        }

                    });
				});
				*/


			    $('#subOrderAddress').click(function(){
			        //看下所有的地址 是否都填写完毕
					var address_num = parseInt($('#countAddress').val());
					var countPackage = parseInt($('#countPackage').val());
					var userid = parseInt($('#userid').val());
					for(var i=0;i<address_num;i++){
					    if($('.address_num').eq(i).val() == 0){
					        layer.msg('请填写地址');
					        return false;
						}
					}
					//提交所有的电话 所有的地址
					var names = [];
					var tels = [];
					var address = [];
					var codes = [];
					var provinces = [];
					var citys = [];
					var order_id = $('#order_id').val();

                    for(var j=0;j<address_num;j++){
                        names.push($.trim($('.form_username').eq(j).val()));
                        tels.push($.trim($('.form_tel').eq(j).val()));
                        address.push($.trim($('.form_address').eq(j).val()));
                        codes.push($.trim($('.form_code').eq(j).val()));
                        provinces.push($.trim($('.form_province').eq(j).val()));
                        citys.push($.trim($('.form_city').eq(j).val()));
                    }




                    var url = '{{ url('subOrderAddress') }}';
                    layer.load(1);
                    $.ajax({
                        type: 'POST',
                        url: url,
                        data: {provinces:provinces,citys:citys,codes:codes,names:names,tels:tels,address:address,order_id:order_id,address_num:address_num,countPackage:countPackage,userid:userid},
                        dataType:"json",
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                        },
                        success: function(data){
                            layer.closeAll('loading');
							//var data = eval("(" + data + ")");
							if(data.code == 'success'){
                                location.href='{{ url('payOrder') }}'+'/'+order_id;
                                return false;
							}
							if(data.code == 'error'){
                                layer.msg(data.msg);
                                return false;
							}
							if(data.code == 'api_error'){
							    data = data.msg;
                                var length = data.length;
                                var html = '<div style="line-height:20px;">';
                                for(var i=0;i<length;i++){
                                    html += '<a class="openfont">单号：'+data[i].package_num+'</a>';
                                    html += '<br>';
                                    html += '<a class="openfont">处理结果：'+data[i].data + '</a>';
                                    html += '<br>';
                                }
                                html += '</div >';

                                layer.open({
                                    type: 1,
                                    area: ['90%', '90%'], //宽高
                                    content: html,
                                    end:function(){
                                        location.reload();
                                    }
                                });
                                return false;
							}

                        },
                        error: function(xhr, type){
                            layer.msg('网络错误');
                        }
                    });


				})
			})
		</script>
	</body>
</html>
