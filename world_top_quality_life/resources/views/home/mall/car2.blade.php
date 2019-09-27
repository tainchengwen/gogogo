<!DOCTYPE html>
<html>
<head>
    <title>购物车</title>
    @include('home.mall.public')
    <style>
        .wuliu_info{display: none;}
    </style>
    <script>


        var spIds=[];
        $(function(){
            @if(session('back_res'))
                layer.alert('{{ session('back_res') }}');
            @endif
        });
        function jian(obj){
            var num=parseInt($(obj).parent(".car_num").find(".car_ipt").text());
            if(num>1){
                $(obj).parent(".car_num").find(".car_ipt").text(num-1);
            }
            hejiShow();
        }
        function jia(obj){
            var num=parseInt($(obj).parent(".car_num").find(".car_ipt").text());
            if(num<999){
                $(obj).parent(".car_num").find(".car_ipt").text(num+1);
            }
            hejiShow();
        }
        // 全选
        function allCheck(){
            var a = document.getElementById("all");
            var d = document.getElementsByName("seed");
            if(a.checked){
                $(".car_op_check").removeClass("car_op_weixuanzhong");
                $(".car_op_check").addClass("car_op_xuanzhong");
                for(var i=0;i<d.length;i++){
                    d[i].checked="checked";
                    $(".car_check").removeClass("car_weixuanzhong");
                    $(".car_check").addClass("car_xuanzhong");
                }
            }else{
                $(".car_op_check").removeClass("car_op_xuanzhong");
                $(".car_op_check").addClass("car_op_weixuanzhong");
                for(var i=0;i<d.length;i++){
                    d[i].checked=false;
                    $(".car_check").removeClass("car_xuanzhong");
                    $(".car_check").addClass("car_weixuanzhong");
                }
            }
            hejiShow();
        }


        function buyCheck(obj){
            var d = document.getElementsByName("seed");
            var a = document.getElementById("all");
            var c = true;
            if ($(obj).is(':checked')) {
                $(obj).parents(".car_check").removeClass("car_weixuanzhong");
                $(obj).parents(".car_check").addClass("car_xuanzhong");
            }else{
                $(obj).parents(".car_check").removeClass("car_xuanzhong");
                $(obj).parents(".car_check").addClass("car_weixuanzhong");
            };
            if(a.checked){
                for(var i=0;i<d.length;i++){
                    if(d[i].checked==false){
                        a.checked=false;
                        $(".car_op_check").removeClass("car_op_xuanzhong");
                        $(".car_op_check").addClass("car_op_weixuanzhong");
                        break;

                    }
                }
            }else{
                for(var i=0;i<d.length;i++){
                    if(d[i].checked==false){
                        c=false;

                        break;
                    }
                }
                if(c){
                    a.checked="checked";
                    $(".car_op_check").removeClass("car_op_weixuanzhong");
                    $(".car_op_check").addClass("car_op_xuanzhong");
                }
            }
            hejiShow();
        }

        function hejiShow(){
            spIds=[];
            var  cars=$(".car_box");
            var maney1=0;
            alert(cars.length);
            for(var i=0;i<cars.length;i++){
                var num=parseInt($(".car_num .car_ipt").eq(i).text());
                var maney1Txt=$(".car_box .car_maney_sp1").eq(i).text();
                if($(".car_box .car_check input").eq(i).is(':checked')){
                    //获取商品的id和数量
                    spIds.push([$(".car_box").eq(i).attr("key"),num]);
                    //获取商品总价
                    maney1+=parseFloat(maney1Txt.substr(1)*num);
                }
            }
            $(".car_op_maney span").text("￥"+maney1);
            $('#price_all').val(maney1);
        }


        function guoneiCheck(obj){
            if ($(obj).is(':checked')) {
                $(obj).parents(".car_guonei").removeClass("car_op_weixuanzhong");
                $(obj).parents(".car_guonei").addClass("car_op_xuanzhong");
                var check_str = 'check_'+ $(obj).attr('data');
                //其他的全部 check_input 都未选中

                for(var i=0;i<$('.check_input').length;i++){
                    $('.check_input').eq(i).removeClass("car_xuanzhong");
                    $('.check_input').eq(i).addClass("car_weixuanzhong");
                    //alert($('.check_input').eq(i).checked);
                    $('.check_input').eq(i).checked=false;
                }

                //alert($('.'+check_str).length);
                for(var j = 0;j<$('.'+check_str).length;j++){
                    //都选中
                    $('.'+check_str).eq(j).parent('.car_check').removeClass("car_weixuanzhong");
                    $('.'+check_str).eq(j).parent('.car_check').addClass("car_xuanzhong");
                    $('.'+check_str).eq(j).checked=true;
                }
            }else{
                //未选中
                $(obj).parents(".car_guonei").removeClass("car_op_xuanzhong");
                $(obj).parents(".car_guonei").addClass("car_op_weixuanzhong");

                for(var i=0;i<$('.check_input').length;i++){
                    alert($('.check_input').eq(i).checked);
                }




                var check_str = 'check_'+ $(obj).attr('data');
                for(var j = 0;j<$('.'+check_str).length;j++){

                    $('.'+check_str).eq(j).parent('.car_check').removeClass("car_xuanzhong");
                    $('.'+check_str).eq(j).parent('.car_check').addClass("car_weixuanzhong");
                    $('.'+check_str).eq(j).checked=false;
                }

            };
            hejiShow();
        }

    </script>
</head>
<body>
<div class="content">
    <div class="tab_content">
        <div class="car_guonei_bg">
            <div class="car_guonei car_op_weixuanzhong">
                <input type="checkbox" name="guonei" onchange="guoneiCheck(this)" data="1" /><img src="img/dianpu.png" />
                国内现货
            </div>
        </div>
        <div class="car_box" key="1">
            <div class="car_check car_weixuanzhong">
                <input type="checkbox" name="seed" onchange="buyCheck(this)" class="check_input check_1"/>
            </div>
            <div class="car_img">
                <img src="img/buy.png"/>
            </div>
            <div class="car_info">
                <div class="car_name">日本资生堂洗颜专科泡洗面奶120g...</div>
                <div class="car_bianhao">商品编码：12345678</div>
                <div class="car_maney">
                    <span class="car_maney_sp1">￥1500</span><img src="img/vip1_blue.png"/><span class="car_maney_sp2">￥1200</span>
                </div>
                <div class="car_pay">
                    <div class="car_kucun">库存充足</div>
                    <div class="car_num">
                        <span class="car_jian" onClick="jian(this)"></span><span class="car_ipt" >1</span><span class="car_jia" onClick="jia(this)"></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="car_box" key="2">
            <div class="car_check car_weixuanzhong">
                <input type="checkbox" name="seed" onchange="buyCheck(this)" class="check_input check_1" />
            </div>
            <div class="car_img">
                <img src="img/buy.png"/>
            </div>
            <div class="car_info">
                <div class="car_name">日本资生堂洗颜专科泡洗面奶120g...</div>
                <div class="car_bianhao">商品编码：12345678</div>
                <div class="car_maney">
                    <span class="car_maney_sp1">￥1500</span><img src="img/vip1_blue.png"/><span class="car_maney_sp2">￥1200</span>
                </div>
                <div class="car_pay">
                    <div class="car_kucun">库存充足</div>
                    <div class="car_num">
                        <span class="car_jian" onClick="jian(this)"></span><span class="car_ipt" >1</span><span class="car_jia" onClick="jia(this)"></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="car_box" key="3">
            <div class="car_check car_weixuanzhong">
                <input type="checkbox" name="seed" onchange="buyCheck(this)" class="check_input check_1"/>
            </div>
            <div class="car_img">
                <img src="img/buy.png"/>
            </div>
            <div class="car_info">
                <div class="car_name">日本资生堂洗颜专科泡洗面奶120g...</div>
                <div class="car_bianhao">商品编码：12345678</div>
                <div class="car_maney">
                    <span class="car_maney_sp1">￥1500</span><img src="img/vip1_blue.png"/><span class="car_maney_sp2">￥1200</span>
                </div>
                <div class="car_pay">
                    <div class="car_kucun">库存充足</div>
                    <div class="car_num">
                        <span class="car_jian" onClick="jian(this)"></span><span class="car_ipt" >1</span><span class="car_jia" onClick="jia(this)"></span>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="car_op">
        <div class="car_op_check car_op_weixuanzhong">
            <input type="checkbox" name="need" id="all" onchange="allCheck()"/>
            全选
        </div>
        <div class="car_op_del">删除</div>
        <div class="car_op_maney">
            总计:<span>￥0</span>
        </div>
        <div class="car_op_btn">
            <span class="buyBtn">确认订单</span>
        </div>
    </div>
</div>
@include('home.mall.foot')
<script>
    $(function(){
        $('.buyBtn').click(function(){
            alert(spIds);
        })
    })
</script>
</body>
</html>
