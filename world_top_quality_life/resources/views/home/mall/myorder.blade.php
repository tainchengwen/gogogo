<!DOCTYPE html>
<html>
<head>
    <title>我的订单</title>
    @include('home.mall.public')
    <script src="{{ asset('mall/js/pinchzoom.js') }}"></script>
    <script>
        $(function () {
            $('.dingdan_kuaidi_img').each(function () {
                new RTP.PinchZoom($(this), {});
            });
        })
        function closeDingdan(){
            $(".dingdan_close_bg").show();
        }
        function closeDingdanSub(){
            $(".dingdan_close_bg").hide();
        }

        //查看物流
        function openKuaidi(){
            $(".dingdan_kuaidi_bg").show();
        }
        function closeKuaidi(){
            $(".dingdan_kuaidi_bg").hide();
        }
    </script>
</head>
<body>
<div class="content">
    <div class="tab_header_dingdan">
        <div class="tab_sp5 @if(!isset($_GET['type']) || $_GET['type'] == '0') tab_sp_active @endif " onclick="location.href='{{ url('mall/myorder').'?type=0' }}' ">
            <span>全部</span>
        </div>
        <div class="tab_sp5 @if(isset($_GET['type']) && $_GET['type'] == '1') tab_sp_active @endif " onclick="location.href='{{ url('mall/myorder').'?type=1' }}'" >
            <span>待付款</span>
        </div>
        <div class="tab_sp5 @if(isset($_GET['type']) && $_GET['type'] == '2') tab_sp_active @endif " onclick="location.href='{{ url('mall/myorder').'?type=2' }}'" >
            <span>待发货</span>
        </div>
        <div class="tab_sp5 @if(isset($_GET['type']) && $_GET['type'] == '3') tab_sp_active @endif " onclick="location.href='{{ url('mall/myorder').'?type=3' }}'" >
            <span>待收货</span>
        </div>
        <div class="tab_sp5 @if(isset($_GET['type']) && $_GET['type'] == '4') tab_sp_active @endif " onclick="location.href='{{ url('mall/myorder').'?type=4' }}'" >
            <span>已完成</span>
        </div>
    </div>

    <!--
    <div class="tab_content" style="bottom: 0;top:1.12rem; padding-top: 0.26rem;background: #fff;">

        <div class="dingdan_kong">
            <img src="{{ asset('mall/img/kong.png') }}"/>
        </div>


    </div>
       -->

    <div class="tab_content" style="padding-top: 1.12rem">

        @if($data)
            @foreach($data as $vo)
                <div class="dingdan_box box">
                    <div class="dingdan_time">
                        下单时间: {{ substr($vo['CreateDate'],0,10) }}
                        @if($vo['PaidStatus'] == '付款未完成')
                        <span>待付款</span>
                            @elseif($vo['SaleStatus'] == '发货完成')
                            <span>已完成</span>
                            @elseif($vo['SaleStatus'] == '未发货')
                            <span>待发货</span>
                            @else
                            <span>待收货</span>
                        @endif
                    </div>
                    <div class="tab_qigndan_content" onclick="location.href='{{ url('mall/orderdetail').'?order_id='.$vo['SaleID'] }}'">
                        @if(count($vo['order_detail']))
                            @foreach($vo['order_detail'] as $value)

                                <div class="commodity_box">
                                    <div class="commodity_img">

                                        @if($value['product_img'])
                                            <img src="{{ $value['product_img'] }}"/>
                                        @else
                                            <img src="{{ asset('mall/img/noimg2.jpg') }}" />
                                        @endif

                                    </div>
                                    <div class="commodity_info">
                                        <div class="commodity_name1">{{ $value['PartNumber'] }}</div>
                                        <div class="commodity_bianhao">商品编码：{{ $value['ProductNo'] }}</div>
                                        <div class="commodity_pay">
                                            <span class="commodity_maney_sp1">￥{{ $value['Amount'] }}</span>
                                            <div class="commodity_num">
                                                x{{ $value['Qty'] }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @endif


                    </div>

                    <div class="ding_btn">
                        实付:
                        <span class="ding_btn_maney">￥{{ $vo['Payable'] }}</span>
                        <span class="ding_btn_num">共{{ count($vo['order_detail']) }}件商品</span>

                        <!-- 未付款 -->
                        @if($vo['PaidStatus'] == '付款未完成')
                            <span class="ding_btn_btn ding_btn_btn2 ding_btn_btn_color"  onclick="location.href='{{ url('mall/payOrder').'?order_id='.$vo['SaleID'] }}'" >付款</span>
                            <!--
                            <span class="ding_btn_btn ding_btn_btn4" onclick="closeDingdan()">取消订单</span>
                            -->
                        @endif


                        <!-- 已发货 -->
                        @if($vo['SaleStatus'] == '发货完成')
                        <span class="ding_btn_btn ding_btn_btn2 delete"  data="{{ $vo['SaleID'] }}">删除</span>
                        <span class="ding_btn_btn ding_btn_btn4" onclick="openKuaidi()">查看物流</span>
                        @endif
                    </div>
                </div>
            @endforeach
        @endif


        <!--
        <div class="dingdan_box box">
            <div class="dingdan_time">
                下单时间: 2018-01-01
                <span class="dingdan_fense">待付款</span>
            </div>
            <div class="tab_qigndan_content">
                <div class="commodity_box">
                    <div class="commodity_img">
                        <img src="img/buy.png"/>
                    </div>
                    <div class="commodity_info">
                        <div class="commodity_name1">日本资生堂洗颜卸妆清洁专科泡洗面奶120</div>
                        <div class="commodity_bianhao">商品编码：12345678</div>
                        <div class="commodity_pay">
                            <span class="commodity_maney_sp1">￥1000</span>
                            <div class="commodity_num">
                                x1
                            </div>
                        </div>
                    </div>
                </div>
                <div class="commodity_box">
                    <div class="commodity_img">
                        <img src="img/buy.png"/>
                    </div>
                    <div class="commodity_info">
                        <div class="commodity_name1">日本资生堂洗颜卸妆清洁专科泡洗面奶120</div>
                        <div class="commodity_bianhao">商品编码：12345678</div>
                        <div class="commodity_pay">
                            <span class="commodity_maney_sp1">￥1000</span>
                            <div class="commodity_num">
                                x1
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="ding_btn">
                实付:
                <span class="ding_btn_maney">￥4000.0</span>
                <span class="ding_btn_num">共3件商品</span>
                <span class="ding_btn_btn ding_btn_btn2 ding_btn_btn_color">付款</span>
                <span class="ding_btn_btn ding_btn_btn4" onclick="closeDingdan()">取消订单</span>
            </div>
        </div>
           -->

    </div>


    <div class="dingdan_close_bg">
        <div class="dingdan_close">
            <div class="dingdan_close_title">您已取消订单</div>
            <div class="dingdan_close_number">订单号: 1234567</div>
            <div class="dingdan_close_ps">已支付金额将在1个工作日内退回您的微信账户，请注意查看 。 </div>
            <div class="dingdan_close_btn" onclick="closeDingdanSub()">确 定</div>
        </div>
    </div>
    <div class="dingdan_kuaidi_bg">
        <div class="dingdan_kuaidi">
            <div class="dingdan_kuaidi_title">顺丰快递</div>
            <div class="dingdan_kuaidi_number">快递单号: 1234567</div>
            <div class="dingdan_kuaidi_img">
                <img src="{{ asset('mall/img/kuaidi.png') }}"/>
            </div>
            <div class="dingdan_kuaidi_btn" onclick="closeKuaidi()"></div>
        </div>
    </div>

</div>
</body>
</html>
