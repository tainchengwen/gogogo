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
<title>托盘信息</title>

<script>

    $(function(){
        $("#texts").focus();
    })
</script>


<div class="content-wrapper" id="pjax-container" style="min-height: 593px;margin-left:0;">

    <section class="content">


        <div class="row"><div class="col-md-12"><div class="col-md-12" style="padding-left:9px;">
                    <div class="box">
                        <div class="box-header" style="height:70px;width:100%;">
                            <div style="float: left;width:50%;text-align: center;">
                                <a class="btn btn-sm btn-facebook"  style="font-size:25px;margin-top:5px;" href="{{ url('scanGoods') }}" ><i class="fa fa-crosshairs"></i>返回托盘列表</a>
                            </div>
                            <div style="float: left;width:50%;text-align: left;line-height: 30px">

                            </div>
                            <div style="clear:both;"></div>
                        </div>

                        @if(isset($_GET['batch']))
                            <input type="hidden" id="batch_id" value="{{ $_GET['batch'] }}" />

                        @endif
                        <input type="hidden" id="pici_id"  @if(isset($_GET['pici_id'])) value="{{ $_GET['pici_id'] }}" @else value="0"  @endif />
                        <div class="box-header" style="height:180px;">
                            <form method="get"  id="myForm" onkeydown="if(event.keyCode==13) return getData()&&false;" >
                                <h3 class="box-title"></h3>

                                <div class="pull-left" style="" >

                                    @if(!isset($_GET['batch']))
                                        <input type="hidden" class="ajax_url" value="scanNumbersAjax" />
                                        请扫描物流单号或者选择：<input type="text" style="width:300px;height:30px;border:1px solid #000000;margin-top:10px;" placeholder="请扫描物流单号" id="wuliu_batch_num"   /><!--<a class="btn btn-sm btn-primary grid-refresh" style="margin-left:10px;" id="makeBatchPackages" ><i class="fa fa-search"></i>  生成新托盘</a>-->
                                        @if($repertory_nums)

                                            <select id="repertory_select">
                                                <option value="">请选择</option>
                                                @foreach($repertory_nums as $vo)
                                                    <option value="{{ $vo -> numbers }}">{{ $vo -> numbers.'(创建时间：'.date('Y-m-d H:i',$vo -> created_at).')' }}</option>
                                                @endforeach
                                            </select>
                                            @endif
                                        <a class="btn btn-sm btn-primary "   onclick="getData()"   ><i class="fa fa-search"></i>确认提交</a>
                                        <br>
                                    @endif


                                    @if(isset($_GET['batch']))
                                            <input type="hidden" class="ajax_url" value="createNewBatchAjax" />
                                            <h3>{{ '批次号：'.$batch_info -> batch_num }} <br>{{ '到货物流编号：'. $repertory_info -> numbers}}</h3>
                                    @endif

                                        @if(isset($_GET['batch']))
                                    <div style="margin-top:10px;">扫描单号：<input type="text" id="texts" name="wuliu_num" style="width:200px;height:30px;line-height: 30px" /></div>
<br>
<b>当前数量：{{ count($data) }}</b>
                                            @endif
                                </div>



                            </form>
                        </div>






                        <!-- /.box-header -->
                        <div class="box-body table-responsive no-padding">
                            <table class="table table-hover">
                                <tbody>
                                <tr>
                                    <th>序号</th>
                                    <th>包裹编号</th>
                                    <th>运单号</th>

                                    <th>操作</th>
                                </tr>
                                @if(isset($data) && count($data))
                                @foreach($data as $key => $vo)

                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <td>{{ $vo -> package_num }}</td>
                                        <td>{{ $vo -> wuliu_num }}</td>
                                        <td>
                                            <a class="btn btn-sm btn-primary deleteData" data="{{ $vo -> id }}"     ><i class="fa fa-trash"></i>删除</a>
                                        </td>
                                    </tr>

                                @endforeach
                                @endif


                                </tbody>
                            </table>
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
                        //扫描物流单号 或者 包裹编号
                        var scanType = $('.ajax_url').val();
                        //扫描到货物流单号
                        if(scanType == 'scanNumbersAjax'){
                            //return true;
                            var url = '{{ url('scanNumbersAjax') }}';
                            var wuliu_num = $.trim($('#wuliu_batch_num').val());
                            if(!wuliu_num){
                                //再判断下 是否选择了
                                var wuliu_num_select = $('#repertory_select').val();
                                if(!wuliu_num_select){
                                    alert('请扫描或者选择物流单号');
                                    location.reload();
                                    return false;
                                }
                                wuliu_num = wuliu_num_select;



                            }


                            //存在的话 就是托盘id 托盘新增到货物流单号
                            var pici_id = $('#pici_id').val();

                            layer.load(1);
                            $.ajax({
                                type: 'POST',
                                url: url,
                                data: {
                                    wuliu_num:wuliu_num,
                                    pici_id:pici_id
                                },
                                dataType:'json',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                success: function (data) {
                                    layer.closeAll('loading');
                                    console.log(data);
                                    if(data.code == 'error'){
                                        alert(data.msg);
                                        return false;
                                    }else{
                                        //返回的是 批次-物流单号 绑定关系ID
                                        location.href='{{ url('createNewBatch') }}'+'?batch='+data.msg;
                                        return false;
                                    }

                                },
                                error: function (xhr, type) {
                                    layer.closeAll('loading');
                                    alert('数据错误');
                                }
                            });



                            return false;
                        }else{
                            //扫描包裹
                            //return true;
                            var url = '{{ url('createNewBatchAjax') }}';
                            var wuliu_num = $.trim($('#texts').val());
                            if(!wuliu_num){
                                alert('请扫码');
                                voice_error();
                                location.reload();
                                return false;
                            }

                            //此id 为 批次-到货物流单号 关系id
                            var batch_id = $('#batch_id').val();


                            layer.load(1);
                            $.ajax({
                                type: 'POST',
                                url: url,
                                data: {
                                    wuliu_num:wuliu_num,
                                    batch_id:batch_id,
                                    pici_id:pici_id
                                },
                                dataType:'json',

                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                success: function (data) {
                                    layer.closeAll('loading');
                                    console.log(data);

                                    if(data.code == 'success'){
                                        playMusic();
                                        bigMsg('扫描成功','success');
                                        setTimeout(function(){
                                            location.reload();
                                        },800);

                                        return false;
                                    }else{
                                        voice_error();
                                        bigMsg(data.msg,'error');
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
