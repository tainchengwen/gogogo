<!DOCTYPE html>
<html>
<head>
    <title>我的</title>
    @include('home.mall.public')
    <script>

    </script>
    <style>
        .bottom-text{
            height:1.2rem;
            line-height:1.2rem;
            width:100%;
            position:absolute;
            bottom:0;left:0;
            background-color:#000000;
            filter:alpha(opacity:50);
            opacity:0.5;
            -moz-opacity:0.5;
            -khtml-opacity: 0.5;
            padding-top:0.2rem;
            padding-bottom:0.2rem;
        }
        .bottom-text-box-l{
            text-align: center;
            color:#ffffff;
            height:0.8rem;
            line-height:0.8rem;
            width:49%;
            float:left;
            border-right:1px solid #ffffff;
            font-size:0.35rem;
        }
        .bottom-text-box-r{
            text-align: center;
            color:#ffffff;
            height:0.8rem;
            line-height:0.8rem;
            width:51%;
            float:right;
            font-size:0.35rem;
        }
    </style>
</head>
<body>
<div class="content">
    <div class="my_header" style="height:4.6rem;">
        <div class="my_img">
            <img src="{{ $userinfo -> headimg }}"/>
        </div>
        <div class="my_info">
            <div class="my_name">
                {{ $userinfo -> nickname }}
            </div>
            <!--vip用户-->
            <div class="my_level">
                @if(!$userinfo -> market_class)
                    <img src="img/putong_img.png"/>
                    普通用户
                    @else
                    <img src="img/vip_img.png"/>
                    VIP{{ $userinfo -> market_class }}会员
                @endif
            </div>



            <!--普通用户-->
            {{--<div class="my_level">--}}
                {{--<img src="img/putong_img.png"/>--}}
                {{--普通用户--}}
            {{--</div>--}}
        </div>

        <div class="bottom-text">
            <div class="bottom-text-box-l">
                账户余额:{{ $userinfo -> price }}
            </div>
            <div class="bottom-text-box-r">
                返点余额:{{ $userinfo -> fandian }}
            </div>
        </div>

    </div>

    <div class="my_box box my_box_op" style="height: 3.9rem;padding: 0;margin-bottom: 0.21rem;">
        <div class="my_all">
            我的订单
            <span onclick="location.href='{{ url('mall/myorder') }}' ">
						查看全部
						<img src="{{ asset('mall/img/ic-arrow-right.png') }}"/>
					</span>
        </div>
        <div class="my_nav">
            <div class="nav_box" onclick="location.href='{{ url('mall/myorder').'?type=1' }}'" >
                <div class="nav_img nav_img_daifu">

                </div>
                <div class="nav_txt">待付款</div>
            </div>
            <div class="nav_box" onclick="location.href='{{ url('mall/myorder').'?type=2' }}'" >
                <div class="nav_img nav_img_daifa">

                </div>
                <div class="nav_txt">待发货</div>
            </div>
            <div class="nav_box" onclick="location.href='{{ url('mall/myorder').'?type=3' }}'" >
                <div class="nav_img nav_img_daishou">

                </div>
                <div class="nav_txt">待收货</div>
            </div>
            <div class="nav_box" onclick="location.href='{{ url('mall/myorder').'?type=4' }}'" >
                <div class="nav_img nav_img_wanc">
                </div>
                <div class="nav_txt">已完成</div>
            </div>
        </div>
    </div>
    <div class="my_box box my_box_nav" onclick="location.href= '{{ url('mall/checkAddress?return=center') }}' ">
        <img src="{{ asset('mall/img/addr.png') }}" class="my_box_img"/>
        收货地址
        <span>
					<img src="{{ asset('mall/img/ic-arrow-right.png') }}"/>
				</span>
    </div>
    <div class="my_box box my_box_nav user">
        <img src="{{ asset('mall/img/huiyuan.png') }}" class="my_box_img"/>
        成为会员
        <span>
					<img src="{{ asset('mall/img/ic-arrow-right.png') }}"/>
				</span>
    </div>
</div>
@include('home.mall.foot')
<script>
    $(function(){
        $('.user').click(function(){
            location.href='{{ url('mall/howtouser') }}';
        })
    })
</script>
</body>
</html>
