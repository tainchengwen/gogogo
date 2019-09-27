<!DOCTYPE html>
<html>
<head>
	<title>店员发货</title>
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
	</style>
</head>
<body>
<div class="dingdan_header" style="background: #E64340;">
	请认真填写以下内容
</div>
<div class="address_content" >

	<input type="hidden"  id="mark"/>

</div>


<div class="btn_big_bg">
	<button class="btn_big" id="addTr" style="background: #E64340;border: none;">添加</button>
	<button class="btn_big" id="subOrder">提交</button>
	<input type="hidden" id="userid" value="{{ $userid }}" />
	<input type="hidden" id="from_area" value="{{ $from_area }}" />
	<input type="hidden" id="printer_userid" value="{{ $printer_userid }}" />


</div>

<script>
    $(function(){
		$('#addTr').click(function(){
		    var html = '<div class="address_box box">';
		    html += '<span class="temp_span">';
		    html += '包裹重量：<input  type="number" class="package_weight" name="weight" />';
		    html += '</span>';
		    html += '<img src="{{ asset('img/del.png') }}" class="del_img"  />';
		    html += '</div>';
		   	 $('#mark').before(html);
		});

        $('.address_content').on('click','.del_img',function(){
            var self = $(this);
            self.parents('.address_box').remove();
        });

        $('#subOrder').click(function(){
            //看下每个重量是否填写完毕
			var length = $('.package_weight').length;
			var weight_arr = [];
			for(var i = 0;i<length;i++){
			    if(!$.trim($('.package_weight').eq(i).val())){
			        alert('重量必填');return false;
				}
                weight_arr.push($.trim($('.package_weight').eq(i).val()));
			}
			var userid = $('#userid').val();
			var from_area = $('#from_area').val();
			var printer_userid = $('#printer_userid').val();

            var url = '{{ url('etk/subOrderRes') }}';
            $.ajax({
                type: 'POST',
                url: url,
                data: {
                    userid:userid,
                    from_area:from_area,
                    printer_userid:printer_userid,
                    weight_arr:weight_arr
				},

                headers: {
                    'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                },
                success: function(data){
                    if(data == 'success'){
                        layer.alert('下单成功，已向此用户发送通知');
                    }

                },
                error: function(xhr, type){
                    //alert('Ajax error!')
                }
            });


        })
    })
</script>
</body>
</html>
