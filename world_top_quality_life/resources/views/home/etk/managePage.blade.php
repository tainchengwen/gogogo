<!DOCTYPE html>
<html>
<head>
	<title>打印列表</title>
	@include('layouts.home_header')
	<style>
		.address_box img{position: absolute;top:0.6rem;right: 0.4rem;width: 0.5rem;}
		.address_box1{color: #111;font-size: 0.4rem;position: relative;}
		.address_box_info{padding:0.53rem 0.4rem 0;}
		.address_box1 .address_box_div{margin-bottom: 0.4rem;}
		.address_box1 .address_box_div_sm{font-size: 0.32rem;color: #666;margin-bottom: 0.4rem;}
		.address_box1 img{position: absolute;top:5.62rem;right: 0.4rem;}
		.address_box_sub{height: 1.7rem;line-height: 1.7rem; border-top:1px solid #eee;color: #111;font-size: 0.4rem;padding: 0 0.4rem;}
		.address_box_sub button{float: right;margin-top: 0.42rem;}

		.package_weight{
			width:50%;height:0.5rem;margin-left:5%;
		}
		.del_img{
			top: 0.4rem;
			height: 0.5rem;
			width: 0.5rem;
		}
		.openfont{
			font-size:15px;
		}
	</style>
</head>
<body>
<div class="dingdan_header" style="background: #E64340;">
	订单打印队列
</div>

<div class="address_content" >
	@if($info)
		@foreach($info as $vo)
			<div class="address_box box">

				<span class="temp_span">
					{{ $vo -> order_info -> order_num.' ' }} <a style="padding-left:20px;"></a>{{ date('Y-m-d H:i',$vo -> order_info -> created_at) }}
				</span>
				<img src="{{ asset('mall/img/xuanze_ouzhou.png') }}" class="del_img"  data="{{ $vo -> order_info -> id }}" />
			</div>
		@endforeach
	@endif
	<input type="hidden" id="printer_id" value="{{ $printer_id }}" />

</div>
<script>
	$(function(){
	    $('.del_img').click(function(){
            var url = '{{ url('etk/startPrint') }}';
            var order_id = $(this).attr('data');
            var printer_id = $('#printer_id').val();
            layer.load(1);
            $.ajax({
                type: 'POST',
                url: url,
                data: {
                    printer_id:printer_id,
					order_id:order_id
                },

                headers: {
                    'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                },
                success: function(data){
                    layer.closeAll('loading');
                    if(data == 'success'){
                        layer.alert('已进入打印请求队列，请稍等');
                        location.reload();
                    }
                    if(data == 'no_pdf'){
                        layer.alert('面单pdf还未生成，请等待');
					}
                    if(data == 'error'){
                        layer.alert('没有此订单');
                    }


                },
                error: function(xhr, type){
                    layer.msg('网络错误，请等待');
                    layer.closeAll('loading');
                }
            });
		})
	})
</script>




</body>
</html>
