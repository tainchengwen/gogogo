<!DOCTYPE html>
<html>
<head>
	<title>首页</title>
	@include('home.mall.public')
	<style>
		.tab_search span{
			z-index: 999;
		}
	</style>
	<script>


        var spIds=[],shaiIds=[-1];
        var index_page = 1;
        var is_ajax = true;

        //获取套餐商品 只获取一次
		var merge_goods = true;

        //有无关键词
		var keyword = '';

        $(function(){
            onLoad([-1],1,'','');
			$('#search-btn').click(function(){
                keyword = $.trim($('.tab_search input').val());
                if(keyword){
                    shaiIds = [-1];
                    //带关键词搜索
                    onLoad(shaiIds,1,'clear',keyword);
                }else{
                    onLoad(shaiIds,1,'clear',keyword);
                }
			})
            //搜索

			$('.tab_search input').blur(function(){
			    keyword = $.trim($('.tab_search input').val());
			    if(keyword){
                    shaiIds = [-1];
                    //带关键词搜索
                    onLoad(shaiIds,1,'clear',keyword);
				}else{
                    onLoad(shaiIds,1,'clear',keyword);
				}
			})





            // 滚动条到底时进行刷新
			/*
            $(".tab_content").scroll(function(){
                //console.log(1);
                var height = $(this)[0].scrollHeight;
                var top = $(this)[0].scrollTop;
                var divheight = $(".tab_content").height()+10;
                if(top+divheight >= height){
                    //console.log(222);
                    //获取当前页数
                    var page_index = parseInt($('#page_index').val());
                    if(page_index+1 > index_page){
                        console.log(222);
                        onLoad(shaiIds,page_index,'',keyword);
					}


                }
            });
            */

            $(window).scroll(function() {
                var scrollTop = $(this).scrollTop();
                var scrollHeight = $(document).height();
                var windowHeight = $(this).height();
                if(scrollTop + windowHeight >= scrollHeight - 10){
                    //获取当前页数
                    var page_index = parseInt($('#page_index').val());
                    if(page_index+1 > index_page){
                        console.log(222);
                        onLoad(shaiIds,page_index,'',keyword);
                    }
                }
            });


            $(".tab_header img").click(function(){
                $(".tab_shaixuan_bg").show();
            });
            $(".tab_shaixuan_title span").click(function(){
                $(".tab_shaixuan_bg").hide();
            });


            $("#shaiAll").click(function(){
                $(".shaixuan_check").removeClass("xuanzhong");
                $(".shaixuan_check").addClass("weixuanzhong");
                $("#shaiAll").removeClass("weixuanzhong");
                $("#shaiAll").addClass("xuanzhong");
            });

        });
        function jian(obj){
            var num=parseInt($(obj).parent(".commodity_num").find(".commodity_ipt").text());
            if(num>0){
                $(obj).parent(".commodity_num").find(".commodity_ipt").text(num-1);
            }
            hejiShow();
        }
        function jia(obj){
            //alert(1);
            var num=parseInt($(obj).parent(".commodity_num").find(".commodity_ipt").text());
            if(num<999){
                $(obj).parent(".commodity_num").find(".commodity_ipt").text(num+1);
            }
            hejiShow();
        }


        //加载套餐商品
		function onLoadMergeGoods(){

            var url = '{{ url('mall/ajaxGetMergeGoods') }}';
            $.ajax({
                type: 'POST',
                url: url,
                data: {},
                dataType:"json",
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                },
                success: function(data){
                    if(data.length){
                        var content="";
                        for(var i=0;i<data.length;i++){
                            content+='<div class="commodity_box" key="'+data[i].RowNo+'">';
                            content+='<div class="commodity_img">';
                            if(data[i].product_img){
                                content+='<img src="'+data[i].product_img+'"/>';
                            }else{
                                content+='<img src="{{ asset('mall/img/noimg2.jpg') }}"/>';
                            }

                            content+='</div>';
                            content+='<div class="commodity_info">';
                            content+='<div class="commodity_name">'+data[i].PartNumber+'</div>';
                            content+='<div class="commodity_bianhao">商品编码：'+data[i].ProductNo+'</div>';
                            content+='<div class="commodity_maney">';




                            content+='</div>	';
                            content+='<div class="commodity_pay">';
                            content+='<div class="commodity_kucun">库存充足</div>	';
                            content+='<div class="commodity_num">';

                            content+='</div></div></div></div>';
                        }

                        console.log(content);
                        $(".tab_content").append(content);
                    }
                    layer.closeAll('loading');
                    return false;

                },
                error: function(xhr, type){
                    layer.closeAll('loading');
                    return false;
                }
            });

		}

        //加载列表
        function onLoad(arr,index,is_clear,keyword){
		    if(merge_goods){
                layer.load(1);

		        merge_goods = false;
			}


            if(is_clear){
                index_page = 1;
            }
            $(".tab_shaixuan_bg").hide();
            if(!is_ajax && !is_clear){
                return false;
			}
            is_ajax = false;
            layer.load(1);
            var url = '{{ url('mall/ajaxGetInfo') }}';
            $.ajax({
                type: 'POST',
                url: url,
                data: {page:index,type:arr,keyword:keyword},
                dataType:"json",
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                },
                success: function(data){
					data = data.data;
                    is_ajax = true;
                    if(is_clear){
                        $('.tab_content').empty();
					}
					//console.log(is_clear);
					//console.log(data);

                        if(data.length){
                            //请求到 页数确实加1
                            var page_index = parseInt($('#page_index').val());
                            $('#page_index').val(page_index+1);
                            index_page ++;
                            var content="";
                            for(var i=0;i<data.length;i++){
                                content+='<div class="commodity_box" key="'+data[i].RowNo+'">';
                                content+='<div class="commodity_img">';
                                if(data[i].product_img){
                                    content+='<img src="'+data[i].product_img+'"/>';
                                }else{
                                    content+='<img src="{{ asset('mall/img/noimg2.jpg') }}"/>';
                                }

                                content+='</div>';
                                content+='<div class="commodity_info">';
                                content+='<div class="commodity_name">'+data[i].PartNumber+'</div>';
                                content+='<div class="commodity_bianhao">商品编码：'+data[i].ProductNo+'</div>';
                                content+='<div class="commodity_maney">';




                                content+='</div>	';
                                content+='<div class="commodity_pay">';
                                content+='<div class="commodity_kucun">'+data[i].erp_warehouse_name+' 数量:'+data[i].can_buy_num+'</div>	';
                                content+='<div class="commodity_num">';

                                content+='</div></div></div></div>';
                            }

							console.log(content);
                            $(".tab_content").append(content);
                        }else{
                            is_ajax = false;
                        }

                    if(is_clear){
                        is_ajax = true;
                    }

                    layer.closeAll('loading');
					return false;

                },
                error: function(xhr, type){
                    //alert('Ajax error!')
                    is_ajax = true;
                    layer.closeAll('loading');
                    return false;
                }
            });


            //arr为筛选类型的id数组
            console.log(arr)

        }

        //判断是否显示合计
        function hejiShow(){
            spIds=[];
            var  commoditys=$(".commodity_box");
            var maney1=0,maney2=0,nums=0;
            var hejiCheck=false;
            for(var i=0;i<commoditys.length;i++){
                //var num=parseInt($(".commodity_box input").eq(i).val());
                var num=parseInt($(".commodity_box .commodity_ipt").eq(i).text());
                var maney1Txt=$(".commodity_box .commodity_maney_sp1").eq(i).text();
                var maney2Txt=$(".commodity_box .commodity_maney_sp2").eq(i).text();
                if(num>0){
                    //获取商品的id和数量
                    spIds.push([$(".commodity_box").eq(i).attr("key"),num]);
                    //获取商品总价
                    maney1+=parseFloat(maney1Txt.substr(1)*num);
                    //获取商品VIP价格
                    maney2+=parseFloat(maney2Txt.substr(1)*num);
                    //获取商品总数
                    nums+=num;
                    hejiCheck=true;
                }
            }
            $(".tab_heji .commodity_maney_sp1").text("￥"+maney1);
            $(".tab_heji .commodity_maney_sp2").text("￥"+maney2);
            $(".tab_heji .commodity_maney_sp3").text("（"+nums+"件）");
            //alert(hejiCheck);
            if(hejiCheck){
                $(".tab_heji").show();
                $(".tab_content").css("bottom","2.88rem");
            }else{
                $(".tab_heji").hide();
                $(".tab_content").css("bottom","1.44rem");
            }
        }
        function buyCheck(obj){
            shaiIds=[];
            var hejiCheck=false;
            if ($(obj).is(':checked')) {
                $(obj).parents(".shaixuan_check").removeClass("weixuanzhong");
                $(obj).parents(".shaixuan_check").addClass("xuanzhong");
            }else{
                $(obj).parents(".shaixuan_check").removeClass("xuanzhong");
                $(obj).parents(".shaixuan_check").addClass("weixuanzhong");
            };

            /*
            $(".shaixuan_check").css("background","url()no-repeat center center");
            $(".shaixuan_check").css("background-size","0.75rem 0.75rem");
            $(obj).parents(".shaixuan_check").css("background","url({{ asset('mall/img/xuanze.png') }})no-repeat center center");
            $(obj).parents(".shaixuan_check").css("background-size","0.75rem 0.75rem");


            /*
            if ($(obj).is(':checked')) {
                $(obj).parents(".shaixuan_check").css("background","url({{ asset('mall/img/xuanze.png') }})no-repeat center center");
                $(obj).parents(".shaixuan_check").css("background-size","0.75rem 0.75rem");
            }else{
                $(obj).parents(".shaixuan_check").css("background","url({{ asset('mall/img/weixuanze.png') }})no-repeat center center");
                $(obj).parents(".shaixuan_check").css("background-size","0.75rem 0.75rem");
            };
            */
            var commoditys=$(".shaixuan_check");
            shaiIds.push($(obj).parents(".shaixuan_check").attr("key"));
            hejiCheck=true;
            /*
            for(var i=1;i<commoditys.length;i++){
                if($(".shaixuan_check input").eq(i).is(':checked')){
                    //获取选择的id
                    shaiIds.push($(".shaixuan_check").eq(i).attr("key"));
                    hejiCheck=true;
                }
            }
            */
            /*
            if(!hejiCheck){
                $("#shaiAll").css("background","url({{ asset('mall/img/xuanze.png') }})no-repeat center center");
                $("#shaiAll").css("background-size","0.75rem 0.75rem");
                shaiIds=[-1];
            }else{
                $("#shaiAll").css("background","url({{ asset('mall/img/weixuanze.png') }})no-repeat center center");
                $("#shaiAll").css("background-size","0.75rem 0.75rem");
            };
            */
            //onLoad(shaiIds);
        }

        function shaiUpdate(){
            keyword = '';
            onLoad(shaiIds,1,'clear',keyword);
        }

        $(function(){
            $('.tab_heji_btn').click(function(){
                var length = $('.goods_input').length;
                var product_no = [];
                var number = [];
                for(var i=0;i<length;i++){
                    if(parseInt($('.goods_input').eq(i).text()) > 0 ){
                        product_no.push($('.goods_input').eq(i).attr('product_no'));
                        number.push($('.goods_input').eq(i).text());
					}

                }
                var product_no_str = product_no.join(',');
				var number_str = number.join(',');
				location.href='{{ url('mall/car') }}'+'?product_no_str='+product_no_str+'&number_str='+number_str;
            });
		})
	</script>
</head>
<body>
<input type="hidden" id="page_index" value="1" />
<div class="tab_header" style="top:0;">
	<div class="tab_search">
		<input type="text" placeholder="输入商品名称或关键词" />
		<span id="search-btn"></span>
	</div>
</div>
<div class="content home_content" style="padding-top: 1.2rem;">

	<div class="tab_content">

	</div>
	<div class="tab_heji">
		<div class="commodity_maney">
			<span style="font-size: 0.35rem;">合计:</span>
			<span class="commodity_maney_sp1"></span>
			<!--
			<img src="{{ asset('mall/img/vip.png') }}"/><span class="commodity_maney_sp2"></span>
			-->
			<span class="commodity_maney_sp3"></span>
		</div>
		<span class="tab_heji_btn">确定</span>
	</div>
</div>
<div class="tab_shaixuan_bg">
	<div class="tab_shaixuan">
		<div class="tab_shaixuan_title">分类筛选<span>取消</span></div>
		<!--
		<div class="tab_shaixuan_box" >
			全部商品
			<div class="shaixuan_check" key="-1" id="shaiAll">
				<input type="checkbox" name="seed"/>
			</div>
		</div>
		-->
		@foreach($class_name as $vo)
			<div class="tab_shaixuan_box">
				{{ $vo -> class_name }}
				<div class="shaixuan_check weixuanzhong" key="{{ $vo -> id }}">
					<input type="checkbox" name="seed" onchange="buyCheck(this)"/>
				</div>
			</div>
		@endforeach
		<div class="tab_shaixuan_btn" onclick="shaiUpdate()">确定</div>
	</div>
</div>


<script>
	$(function(){
	    $('#howtouser').click(function(){
			location.href='{{ url('mall/howtouser') }}';
		});
	})
</script>

</body>
</html>
