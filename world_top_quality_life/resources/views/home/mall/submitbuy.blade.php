<!DOCTYPE html>
<html>
<head>
    <title>提交订单</title>
    @include('home.mall.public')
    @if($config_env != 'local')
    <script src="http://res.wx.qq.com/open/js/jweixin-1.4.0.js" type="text/javascript" charset="utf-8"></script>
    <script type="text/javascript" charset="utf-8">
        wx.config(<?php echo $app->jssdk->buildConfig(array('openAddress'), false) ?>);
    </script>
        @endif
</head>
<body>

<div class="content">
    <div class="tab_content" style="top:0;padding-bottom: 2rem;bottom:0;">
        <div class="address_box1 box addr_check"  onclick="openAddress()" >
            <div class="addr_check_icon_left"></div>
            <div class="address_box_info">
                <div class="address_box_div">
                    收货人：<a id="open_name"></a>
                    <span id="open_tel"></span>
                </div>
                <div class="address_box_div address_box_div_sm">
                    收货地址：<a id="open_address"></a>
                </div>
            </div>
            <div class="addr_check_icon_right"></div>
        </div>

        <input type="hidden" id="name_input"  />
        <input type="hidden" id="tel_input"  />
        <input type="hidden" id="province_input"  />
        <input type="hidden" id="city_input"  />
        <input type="hidden" id="country_input"  />
        <input type="hidden" id="address_input"  />

        <div class="address_box1 box addr_check" style="display:none;">
            <div class="addr_check_icon_left"></div>
            <div class="address_box_info">
                <div class="address_box_div">
                    收货人：<a>许多钱</a>
                    <span>15212347055</span>
                </div>
                <div class="address_box_div address_box_div_sm">
                    收货地址：<a>江苏省南京市江宁区  学院路100号南科技大学东区 教学楼210</a>
                </div>
            </div>
            <div class="addr_check_icon_right"></div>
        </div>




        <div class="tab_qigndan">
            <div class="tab_qigndan_title">
                <img src="{{ asset('mall/img/qingdan.png') }}"/>
                购物清单
            </div>
            <div class="tab_qigndan_content">
                @if($mall_res)
                    @foreach($mall_res as $key => $vo)
                        <div class="commodity_box">
                            <div class="commodity_img">
                                @if($vo['product_img'])
                                    <img src="{{ $vo['product_img'] }}"/>
                                @else
                                    <img src="{{ asset('mall/img/buy.png') }}"/>
                                @endif
                            </div>
                            <div class="commodity_info">
                                <div class="commodity_name1">{{ $vo['PartNumber'] }}</div>
                                <div class="commodity_bianhao">商品编码：{{ $vo['ProductNo'] }}</div>
                                <div class="commodity_pay">
                                    <span class="commodity_maney_sp1">￥{{ $vo['Price'] }}</span>
                                    <div class="commodity_num">
                                        x{{ $vo['car_number'] }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif

            </div>
        </div>


        <div class="addr_kuaidi box pay_info">
            <div class="dingdan_info_div">
                配送方式:
                <span>中通（目前仅支持中通）</span>
            </div>
            <!--
            <div class="dingdan_info_div" style="border-top: 1px solid #dfdfdf;border-bottom: 1px solid #ccc;">
                支付方式:
                <span>微信支付</span>
            </div>
            -->
            <div class="dingdan_info_div" style="border-top: 1px solid #ccc;">
                买家留言:
                <div class="dingdan_info_div_txt">
                    <input type="text" placeholder="填写留言给卖家" id="remark"/>
                </div>

            </div>
        </div>




        <div class="addr_kuaidi box pay_info">
            <div class="dingdan_info_box">
                <div class="dingdan_info_div1">
                    商品金额:
                    <span>￥{{ $price_all }}</span>
                </div>
                <!--
                <div class="dingdan_info_div1">
                    会员优惠:
                    <span>- ￥100</span>
                </div>
                -->
                <div class="dingdan_info_div1">
                    快递运费:
                    <span>+ ￥<a id="kuaidi">0</a></span>

                </div>
            </div>

            <div class="dingdan_info_div" style="border-top: 1px solid #ccc;">
                实付金额:
                <span style="font-size: 0.32rem;line-height: 1.5rem;margin-left: 0.1rem;color: #777;">共{{ count($mall_res) }}件商品</span>
                <span style="font-size: 0.42rem;" class="font_color">￥<a class="price_all_input">{{ $price_all }}</a></span>
                <input type="hidden" id="price_all"  value="{{ $price_all }}" />

            </div>
        </div>
    </div>
</div>

<div class="foot">
    <div class="pay_all">
        合计金额: <span style="color:#fe4070" class="font_color">￥<a class="price_all_input">{{ $price_all }}</a></span>
    </div>
    <div class="pay_all_btn" >提交订单</div>
</div>
<script>
    function openAddress(){


        location.href='{{ url('mall/checkAddress') }}';
        return false;
        wx.openAddress({
            success: function (res) {
                //alert(res);
                $('#open_name').text(res.userName);
                $('#name_input').val(res.userName);

                $('#open_tel').text(res.telNumber);
                $('#tel_input').val(res.telNumber);

                $('#open_address').text(res.provinceName+res.cityName+res.countryName +res.detailInfo);



                $('#address_input').val(res.provinceName+res.cityName+res.countryName +res.detailInfo);
                $('#province_input').val(res.provinceName);
                $('#city_input').val(res.cityName);
                $('#country_input').val(res.countryName);

                //选完地址之后  计算运费
                yunfei();



            },
            cancel: function () {
                // 用户取消拉出地址
            }

        });
    }


    //计算运费
    function yunfei(){
        layer.load(1);
        var price_all = parseFloat('{{ $price_all }}');
        //省
        var province = $('#province_input').val();
        var url = '{{ asset('mall/getKuaiDiPrice') }}';
        var json_data = '{{ $mall_res_json }}';
        $.ajax({
            type: 'POST',
            url: url,
            data: {
                price_all:price_all,
                province:province,
                json_data:json_data
            },
            //dataType:"json",
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            success: function(data){
                layer.closeAll('loading');
                $('#kuaidi').text(data);


                $('.price_all_input').text(parseFloat(data) + parseFloat($('#price_all').val()));
                return false;

            },
            error: function(xhr, type){
                layer.closeAll('loading');
            }
        });


    }

    $(function(){
        //提交订单
        $('.pay_all_btn').click(function(){

            //判断地址是否填写
            var name_input = $('#name_input').val();
            var tel_input = $('#tel_input').val();
            var province_input = $('#province_input').val();
            var city_input = $('#city_input').val();
            var country_input = $('#country_input').val();
            var address_input = $('#address_input').val();
            var remark = $('#remark').val();
            //快递
            var kuaidi = $('#kuaidi').text();



            var json_data = '{{ $mall_res_json }}';

            if(!name_input || !tel_input || !address_input){
                layer.alert('请选择收货地址');return false;
            }

            if(!kuaidi){
                layer.alert('数据错误，请联系客服核对');return false;
            }

            layer.load(1);
            var url = '{{ asset('mall/sendOrderRes') }}';
            $.ajax({
                type: 'POST',
                url: url,
                data: {
                    name_input:name_input,
                    tel_input:tel_input,
                    province_input:province_input,
                    city_input:city_input,
                    address_input:address_input,
                    country_input:country_input,
                    order_json: json_data,
                    remark: remark,
                    kuaidi:kuaidi
                },
                dataType:"json",
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success: function(data){
                    layer.closeAll('loading');
                    if(data.code == 'success'){
                        location.href='{{ url('mall/payOrder') }}'+'?order_id='+data.result;
                    }else{
                        layer.alert(data.result);
                        return false;
                    }


                    /*
                    if(data == 'error'){
                        layer.alert('系统错误请重新下单');return false;
                    }else{
                        location.href='{{ url('mall/payOrder') }}'+'?order_id='+data;
                    }
                    */

                    return false;

                },
                error: function(xhr, type){
                    layer.closeAll('loading');
                }
            });
        })



        //当 从智能地址 点进来
        @if($user_address)

            $('#open_name').text('{{ $user_address -> name }}');
            $('#name_input').val('{{ $user_address -> name }}');

            $('#open_tel').text('{{ $user_address -> tel }}');
            $('#tel_input').val('{{ $user_address -> tel }}');

            $('#open_address').text('{{ $user_address -> province.$user_address -> city.$user_address -> country.$user_address -> address }}');



            $('#address_input').val('{{ $user_address -> province.$user_address -> city.$user_address -> country.$user_address -> address }}');
            $('#province_input').val('{{ $user_address -> province }}');
            $('#city_input').val('{{ $user_address -> city }}');
            $('#country_input').val('{{ $user_address -> country }}');

            //选完地址之后  计算运费
            yunfei();
        @endif


    })
</script>
</body>
</html>
