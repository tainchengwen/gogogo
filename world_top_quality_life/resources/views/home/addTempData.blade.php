<meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
<script src="{{asset('js/jquery-1.11.1.min.js')}}"></script>
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

<!-- REQUIRED JS SCRIPTS -->
<script src="{{ admin_asset ("/vendor/laravel-admin/AdminLTE/plugins/jQuery/jQuery-2.1.4.min.js") }}"></script>
<script src="{{ admin_asset ("/vendor/laravel-admin/AdminLTE/bootstrap/js/bootstrap.min.js") }}"></script>
<script src="{{ admin_asset ("/vendor/laravel-admin/AdminLTE/plugins/slimScroll/jquery.slimscroll.min.js") }}"></script>
<script src="{{ admin_asset ("/vendor/laravel-admin/AdminLTE/dist/js/app.min.js") }}"></script>
<script src="{{ admin_asset ("/vendor/laravel-admin/jquery-pjax/jquery.pjax.js") }}"></script>
<script src="{{ admin_asset ("/vendor/laravel-admin/nprogress/nprogress.js") }}"></script>
<script src="{{ asset('js/layer/2.2/layer.js') }}"></script>
@include('home.voice')
<title>扫描保存数据</title>

<script>
    $(function(){
        $("#texts").focus();
    })
</script>
<div class="content-wrapper" id="pjax-container" style="min-height: 593px;margin-left:0;">

    <section class="content">


        <div class="row"><div class="col-md-12"><div class="col-md-12" style="padding-left:9px;">
                    <div class="box">
                        <div class="box-header" style="height:100px;width:100%;">
                            <div style="float: left;width:50%;text-align: center;">
                                <a class="btn btn-sm btn-facebook"  style="font-size:25px;margin-top:5px;" href="{{ url('scanGoods') }}" ><i class="fa fa-crosshairs"></i>返回首页</a>
                            </div>

                            <div style="float: left;width:50%;text-align: center;">
                                <h2>已扫描{{ $number }}箱</h2>
                            </div>


                            <div style="clear:both;"></div>

                        </div>



                        <div class="box-header" style="height:200px;">
                            <form method="get"  id="myForm" onkeydown="if(event.keyCode==13) return getData()&&false;" >
                                <h3 class="box-title"></h3>

                                <div class="pull-left" style="">
                                    <div style="">
                                        <h2>扫描保存数据</h2>
                                    </div>


                                    <div style="margin-top:10px;">请扫描单号：<input type="text" id="texts" name="wuliu_num" style="width:200px;height:30px;line-height: 30px" /></div>

                                </div>

                            </form>
                        </div>







                        <div class="box-footer clearfix">
                        </div>
                        <!-- /.box-body -->

                    </div>
                </div>
                <script>

                    function getData(){
                        //return true;

                        var numbers = $.trim($('#texts').val());
                        if(!numbers){
                            //alert('请扫码');
                            //location.reload();
                            return false;
                        }


                        //提交
                        layer.load(1);
                        var url = '{{ url('addTempDataRes') }}';
                        $.ajax({
                            type: 'POST',
                            url: url,
                            data: {
                                numbers:numbers,
                            },
                            dateType:'json',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            success: function (data) {
                                layer.closeAll('loading');
                                if(data.code == 'success'){
                                    playMusic();
                                    bigMsg('扫描成功','success');
                                    setTimeout(function(){
                                        location.reload();
                                    },1200);
                                }
                                if(data.code == 'error'){
                                    voice_error();
                                    bigMsg(data.msg,'error')
                                    setTimeout(function(){
                                        location.reload();
                                    },1200);
                                }


                            },
                            error: function (xhr, type) {
                                layer.closeAll('loading');
                                alert('数据错误');
                            }
                        });



                        return false;
                    }





                </script></div></div>

    </section>

</div>
