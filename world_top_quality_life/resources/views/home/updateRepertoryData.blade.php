<meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
<script src="{{asset('js/jquery-1.11.1.min.js')}}"></script>
<script src="{{asset('js/spinner/jquery.spinner.js')}}"></script>

<script src="{{ asset('js/layer/2.2/layer.js') }}"></script>
<link rel="stylesheet" href="{{ admin_asset("/vendor/laravel-admin/AdminLTE/bootstrap/css/bootstrap.min.css") }}">
<!-- Font Awesome -->
<link rel="stylesheet" href="{{ admin_asset("/vendor/laravel-admin/font-awesome/css/font-awesome.min.css") }}">
<link rel="stylesheet" href="{{ admin_asset("/vendor/laravel-admin/laravel-admin/laravel-admin.css") }}">
<link rel="stylesheet" href="{{ admin_asset("/vendor/laravel-admin/nprogress/nprogress.css") }}">
<link rel="stylesheet" href="{{ admin_asset("/vendor/laravel-admin/sweetalert/dist/sweetalert.css") }}">
<link rel="stylesheet" href="{{ admin_asset("/vendor/laravel-admin/nestable/nestable.css") }}">
<link rel="stylesheet" href="{{ admin_asset("/vendor/laravel-admin/toastr/build/toastr.min.css") }}">
<link rel="stylesheet" href="{{ admin_asset("/vendor/laravel-admin/bootstrap3-editable/css/bootstrap-editable.css") }}">
<link rel="stylesheet" href="{{ admin_asset("/vendor/laravel-admin/google-fonts/fonts.css") }}">
<link rel="stylesheet" href="{{ admin_asset("/vendor/laravel-admin/AdminLTE/dist/css/AdminLTE.min.css") }}">
<title>到货清单维护</title>
<style>
    .num_div{
        margin-top:10px;
    }
    .num_div input {
        text-align: center;
        font-weight:bold;
    }
    .num_div span{
        float: left;

        width:55px;
    }
    .num_div input{
        float: left;
    }
    .clear_div{
        clear: both;
    }
</style>
<script>

</script>
<div class="content-wrapper" id="pjax-container" style="min-height: 593px;margin-left:0;">

    <section class="content">


        <div class="row"><div class="col-md-12"><div class="col-md-12" style="padding-left:9px;">
                    <div class="box">

                        <div class="box-header" style="width: 100%;height:380px;">
                            <div style="width:100%;text-align: center;height:220px;">
                            <div style="width:50%;float: left" >
                                <div class="num_div"> <span>3.0-3.5</span> <input type="text" data="3.4" class="package_num" style="width:50px;" /><div class="clear_div"></div></div>
                                <div class="num_div"> <span>3.5-4.0</span> <input type="text" data="3.9" class="package_num" style="width:50px;" /><div class="clear_div"></div></div>
                                <div class="num_div"> <span>4.0-4.5</span> <input type="text" data="4.4" class="package_num" style="width:50px;" /><div class="clear_div"></div></div>
                                <div class="num_div"> <span>4.5-5</span> <input type="text" data="4.9" class="package_num" style="width:50px;" /><div class="clear_div"></div></div>
                            </div>
                            <div style="width:50%;float: right" >
                                <div class="num_div"> <span><1.0</span> <input type="text" data="1.0" class="package_num" style="width:50px;" /><div class="clear_div"></div></div>
                                <div class="num_div"> <span>1.0-1.5</span> <input type="text" data="1.4" class="package_num" style="width:50px;" /><div class="clear_div"></div></div>
                                <div class="num_div"> <span>1.5-2.0</span> <input type="text" data="1.9" class="package_num" style="width:50px;" /><div class="clear_div"></div></div>
                                <div class="num_div"> <span>2.0-2.5</span> <input type="text" data="2.4" class="package_num" style="width:50px;" /><div class="clear_div"></div></div>
                                <div class="num_div"> <span>2.5-3.0</span> <input type="text" data="2.9" class="package_num" style="width:50px;" /><div class="clear_div"></div></div>
                            </div>
                            </div>
                            <div style="width:100%;text-align: center;height:20px;line-height: 20px">
                                <h4 id="jisuan">{{ $zong }}</h4>
                            </div>

                            <div style="width:100%;text-align: center;margin-top:10px;">
                                <a class="btn btn-sm btn-facebook"  style="font-size:18px;" id="tijiao"  ><i class="fa fa-crosshairs"></i>提交</a>
                            </div>
                            <input type="hidden" id="repertory_id" value="{{ $_GET['repertory_id'] }}" />

                            <div style="clear: both;"></div>






                        </div>
                    </div>
                </div>
                <script>
                    function jisuanval(){

                        var length = $('.package_num').length;
                        var zong = 0;
                        for(var i = 0;i<length;i++){
                            console.log($('.package_num').eq(i).val());
                            zong += parseInt($('.package_num').eq(i).val());
                        }
                        $('#jisuan').text(zong);
                    }


                    $(function(){

                        $('#tijiao').click(function(){
                            var length = $('.package_num').length;
                            var temp,data;
                            var temp_arr = [];
                            var data_arr = [];
                            var repertory_id = $('#repertory_id').val();
                            for(var i = 0;i<length;i++){
                                temp = $('.package_num').eq(i).val();
                                data = $('.package_num').eq(i).attr('data');

                                temp_arr.push(temp);
                                data_arr.push(data);
                                //console.log(temp);
                            }
                            layer.load(1);

                            //保存重量数组
                            var url = '{{ url('saveUpdateRepertoryData') }}';
                            $.ajax({
                                type: 'POST',
                                url: url,
                                data: {
                                    temp_arr:temp_arr,
                                    data_arr:data_arr,
                                    repertory_id:repertory_id
                                },

                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                success: function(data){
                                    if(data == 'success'){
                                        alert('提交成功');
                                        parent.location.reload();
                                    }else if(data == 'no_decrease'){
                                        alert('不允许减少，请通知后台操作人员');
                                    }else{
                                        alert('提交错误');
                                    }

                                },
                                error: function(xhr, type){
                                    //alert('Ajax error!')
                                }
                            });



                        })



                        $('.package_num').spinner({
                            max:5,
                            min:0,
                            step:0
                        });
                        $('.package_num').val(0);


                        @if($weight_json)

                         var length = $('.package_num').length;
                         @foreach($weight_json as $key => $vo)

                            for(var i=0;i<length;i++){
                                if($('.package_num').eq(i).attr('data') == '{{ $key }}'){
                                    $('.package_num').eq(i).val('{{ $vo }}');
                                }
                            }
                         @endforeach



                        @endif



                    })


                </script></div></div>

    </section>

</div>
