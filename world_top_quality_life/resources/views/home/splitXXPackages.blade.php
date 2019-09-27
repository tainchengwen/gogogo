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
<title>拆分包裹熊谢</title>

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
                                <h1>当前打印机编号：{{ $num }}</h1>
                                <input type="hidden" id="num" value="{{ trim($num) }}" />
                            </div>

                            <div style="clear:both;"></div>
                            <div style="font-size:20px;">
                                打印机列表：
                                @foreach($config as $vo)

                                    <a href="{{ $url.$vo }}" style="margin-left:10px;">{{ $vo }}</a>

                                @endforeach
                            </div>
                        </div>



                        <div class="box-header" style="height:220px;">
                            <form method="get"  id="myForm" onkeydown="if(event.keyCode==13) return getData()&&false;" >
                                <h3 class="box-title"></h3>

                                <div class="pull-left" style="">
                                    <div style="">
                                        <h2>拆分数量默认为2，如需修改，请在扫描前修改</h2>
                                        <h2>xx拆分包裹！！！</h2>
                                    </div>

                                    <div style="">
                                        拆分数量：
                                        <select id="numbers">
                                            <option selected>2</option>
                                            @for($i = 3;$i<=50;$i++)
                                            <option >{{ $i }}</option>
                                            @endfor
                                        </select>
                                    </div>

                                    <div style="margin-top:10px;">请扫描需要拆分的单号：<input type="text" id="texts" name="wuliu_num" style="width:200px;height:30px;line-height: 30px" /></div>

                                </div>

                            </form>
                        </div>







                        <div class="box-footer clearfix">
                        </div>
                        <!-- /.box-body -->

                    </div>
                </div>
                <script>
                    $(function(){
                        $('#makeBatchPackages').click(function(){
                            var batch = $.trim($('#batch_num').val());
                            if(batch){
                                location.href='{{ url('saveBatchPackages') }}'+'?batch_num='+batch;
                            }

                        });

                        $('.deleteData').click(function(){
                            var id = $(this).attr('data');


                            if(confirm('确定删除此条数据么？')){
                                var url = '{{ url('deleteNewBatch') }}';
                                var batch_id = $('#batch_id').val();
                                $.ajax({
                                    type: 'POST',
                                    url: url,
                                    data: {id:id,batch_id:batch_id},

                                    headers: {
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                    },
                                    success: function (data) {

                                        if(data == 'error'){
                                            alert('删除失败');
                                            return false;
                                        }
                                        if(data == 'success'){
                                            alert('成功');
                                            location.reload();
                                        }


                                    },
                                    error: function (xhr, type) {
                                        alert('Ajax error!')
                                    }
                                })
                            }


                        });


                    })


                    function getData(){
                        //return true;

                        var wuliu_num = $.trim($('#texts').val());
                        if(!wuliu_num){
                            alert('请扫码');
                            location.reload();
                            return false;
                        }


                        var numbers = $.trim($('#numbers').val());
                        var num = $('#num').val();

                        //提交
                        layer.load(1);
                        var url = '{{ url('splitXXPackagesRes') }}';
                        $.ajax({
                            type: 'POST',
                            url: url,
                            data: {
                                scan:wuliu_num,
                                numbers:numbers,
                                num:num
                            },
                            dateType:'json',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            success: function (data) {
                                layer.closeAll('loading');
                                if(data.code == 'error'){
                                    voice_error();
                                    bigMsg(data.msg,'error')
                                    setTimeout(function(){
                                        location.reload();
                                    },1200);

                                    return false;
                                }else{
                                    playMusic();
                                    bigMsg('扫描成功','success');
                                    setTimeout(function(){
                                        location.reload();
                                    },1500);

                                    return false;
                                }


                            },
                            error: function (xhr, type) {
                                layer.closeAll('loading');
                                alert('数据错误');
                            }
                        });



                        return false;
                    }


                    $('#submitBtn').click(function(){

                        if(!confirm('确定生成批次么？')){
                            return false;
                        }

                        //
                        var query = '';
                        @if(isset($_GET['ids']) && $_GET['ids'])
                            query = '{{ $ids }}' ;
                        @endif
                        if(query){
                            var url = '{{ url('saveBarCode') }}';
                            $.ajax({
                                type: 'POST',
                                url: url,
                                data: {query:query},
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                success: function (data) {
                                    alert('生成批次成功');
                                    location.href='{{ url('barCode') }}';
                                },
                                error: function (xhr, type) {
                                    alert('Ajax error!')
                                }
                            })

                        }else{
                            alert('请扫码');
                        }

                    })

                    function selectAll(){

                        console.log($('#checkAll').is(':checked'));
                        if($('#checkAll').is(':checked')){
                            var length = $('.ids').length;
                            for(var i = 0 ;i < length; i++){
                                $('.ids').eq(i).prop("checked", true);
                            }

                        }else{
                            var length = $('.ids').length;
                            for(var i = 0 ;i < length; i++){
                                $('.ids').eq(i).prop("checked", false);
                            }
                        }
                    }





                    $('.deleteData').click(function(){
                        var id = $(this).attr('data');
                        var url = '{{ url('deleteBarCode') }}';
                        var query = '';
                        var query_arr = [];

                        @if(isset($_GET['ids']) && $_GET['ids'])
                            query = '{{ $_GET['ids'] }}' ;
                            query_arr = query.split(",");
                            console.log(query_arr);

                            var index = -1;
                            for(var i=0;i<query_arr.length;i++){
                                if(query_arr[i] == id){
                                    index = i;
                                }
                            }
                        console.log(index);
                        if(index > -1){
                            query_arr.splice(index, 1);
                            console.log(query_arr);
                            var url_query = query_arr.join(',');
                            var url_right = '{{ url('barCode') }}'+ '?ids=' + url_query;

                            console.log(url_right);
                            location.href=url_right;
                        }


                        @endif


                            return false;

                        if(confirm('确定删除此条数据么？')){
                            $.ajax({
                                type: 'POST',
                                url: url,
                                data: {wuliu_num:id,query:query},

                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                success: function (data) {

                                    if(data == 'error'){
                                        alert('删除失败');
                                        return false;
                                    }
                                    if(data == 'success'){
                                        alert('成功');
                                        location.reload();
                                    }


                                },
                                error: function (xhr, type) {
                                    alert('Ajax error!')
                                }
                            })
                        }
                    })


                </script></div></div>

    </section>

</div>
