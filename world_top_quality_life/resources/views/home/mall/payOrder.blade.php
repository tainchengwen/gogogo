<!DOCTYPE html>
<html>
<head>
    <title>订单支付</title>
    @include('home.mall.public')
    <script>
        function buyCheck(obj){
            $(".shaixuan_check").removeClass("xuanzhong");
            $(".shaixuan_check").addClass("weixuanzhong");
            if ($(obj).is(':checked')) {
                $(obj).parents(".shaixuan_check").removeClass("weixuanzhong");
                $(obj).parents(".shaixuan_check").addClass("xuanzhong");
            }else{
                $(obj).parents(".shaixuan_check").removeClass("xuanzhong");
                $(obj).parents(".shaixuan_check").addClass("weixuanzhong");
            };
        }
    </script>
</head>
<body>

<div class="content">
    <div class="tab_content" style="top:0;padding-bottom: 2rem;">

        <div class="addr_kuaidi box pay_info" style="margin-top: 0.32rem;">
            <div class="dingdan_info_box">
                <div class="dingdan_info_div1">
                    订单编号:
                    <span>{{ $order_info['SaleID'] }}</span>
                </div>
                <div class="dingdan_info_div1">
                    商品金额:
                    <span>￥{{ $order_info['Payable'] }}</span>
                </div>
                <!--
                <div class="dingdan_info_div1">
                    会员优惠:
                    <span>- ￥0</span>
                </div>
                -->
                <div class="dingdan_info_div1">
                    快递运费:
                    <span>+ ￥{{ $order_info['DeliveryFee'] }}</span>
                </div>
            </div>

            <div class="dingdan_info_div" style="border-top: 1px solid #ccc;">
                实付金额:
                <span style="font-size: 0.32rem;line-height: 1.5rem;margin-left: 0.1rem;color: #777;">共{{ count($order_detail) }}件商品</span>
                <span style="font-size: 0.42rem;" class="font_color">￥{{ $order_info['Payable'] }}</span>

            </div>
        </div>




        <div class="addr_kuaidi box pay_info pay_type" >
            <div class="tab_shaixuan_box"   >
                <img src="{{ asset('mall/img/yue.png') }}"> 余额支付
                <div class="shaixuan_check xuanzhong"  >
                    <input type="radio" name="seed" onclick="buyCheck(this)" checked value="1"/>
                </div>
            </div>
            <div class="tab_shaixuan_box" >
                <img src="{{ asset('mall/img/weixin.png') }}"> 微信线下支付
                <div class="shaixuan_check weixuanzhong">
                    <input type="radio" name="seed" onclick="buyCheck(this)" value="2"/>
                </div>
            </div>
            <div class="tab_shaixuan_box" >
                <img src="{{ asset('mall/img/zhifubao.png') }}"> 支付宝线下支付
                <div class="shaixuan_check weixuanzhong">
                    <input type="radio" name="seed" onclick="buyCheck(this)" value="3"/>
                </div>
            </div>
        </div>



    </div>
</div>

<div class="foot">
    <div class="pay_all_btn" style="width: 100%;">下一步</div>
</div>
<script>
    $(function(){
        $('.pay_all_btn').click(function(){

            var value = $('input[name=seed]:checked').val();
            if(value != 1){
                location.href='{{ asset('mall/payMethod') }}'+'?Payable={{ $order_info['Payable'] }}&SaleID={{ $order_info['SaleID'] }}&number={{ count($order_detail) }}&value='+value;
            }else{
                //直接请求支付接口
                layer.load(1);
                var url = '{{ asset('mall/payApi') }}';
                var  SaleID = '{{ $order_info['SaleID'] }}';
                $.ajax({
                    type: 'POST',
                    url: url,
                    data: {
                        SaleID:SaleID
                    },
                    //dataType:"json",
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(data){
                        layer.closeAll('loading');
                        if(data == 'error'){
                            layer.alert('支付失败');return false;
                        }else if(data == 'success'){
                            location.href='{{ url('mall/center') }}';
                        }else if(data == 'noprice'){
                            layer.alert('余额不足');return false;
                        }
                        return false;

                    },
                    error: function(xhr, type){
                        layer.closeAll('loading');
                    }
                });
            }




            //alert($('input[name=seed]:checked').val());
        })
    })
</script>
</body>
</html>
