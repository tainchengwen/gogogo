<meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
<script src="{{asset('js/jquery-1.11.1.min.js')}}"></script>
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

<title>到货清单</title>
<style>
    body{
        height:100%;
    }
</style>

<script>

</script>
<div class="content-wrapper" id="pjax-container" style="min-height: 593px;margin-left:0;">

    <section class="content">


        <div class="row"><div class="col-md-12"><div class="col-md-12" style="padding-left:9px;">
                    <div class="box">
                        <div class="box-header" style="height:70px;width:100%;">
                            <div style="float: left;width:40%;text-align: center;">
                                <a class="btn btn-sm btn-facebook"  style="font-size:25px;margin-top:5px;" href="{{ url('scanGoods') }}" ><i class="fa fa-crosshairs"></i>返回首页</a>
                            </div>
                            <div  style="float: left;width:40%;text-align: center;">
                                <a class="btn btn-sm btn-success"  style="font-size:25px;margin-top:5px;"  id="mergeNumbers"  ><i class="fa fa-crosshairs"></i>合并</a>
                            </div>
                            <div  style="float: left;width:10%;text-align: center;">
                                <a href="{{ url('stopPrinter') }}" target="_blank">停止打印</a>
                            </div>

                            <div style="float: left;width:50%;text-align: left;line-height: 30px">

                            </div>
                            <div style="clear:both;"></div>
                        </div>








                        <!-- /.box-header -->
                        <div class="box-body table-responsive no-padding" style="text-align: center;">
                            <h1 style="color:green;">到货清单</h1>
                            <table class="table table-hover">
                                <tbody>
                                <tr>
                                    <th></th>
                                    <th>序号</th>
                                    <th>入仓号</th>
                                    <th>到货物流单号</th>
                                    <th>母单号</th>
                                    <th>发件日期</th>
                                    <th>预计到货日期</th>
                                    <th>件数</th>
                                    <th>包裹状态</th>
                                    <th>备注</th>
                                    <th>处理方案</th>
                                    <th>重量</th>

                                    <th>创建日期</th>
                                    <th style="width:150px;">操作</th>
                                </tr>
                                @if(isset($data) && count($data))
                                @foreach($data as $key => $vo)

                                    <tr>
                                        <td><input type="checkbox" class="repertory_ids" /></td>
                                        <td>{{ config('admin.repertory_id_prefix').sprintf('%06s',$vo -> id)  }}</td>
                                        <td>{{ $vo -> ru_number }}</td>
                                        <td>{{ $vo -> numbers }}</td>
                                        <td>{{ $vo -> f_name }}</td>
                                        <td>{{ $vo -> fajian_date }}</td>
                                        <td>{{ $vo -> yuji_date }}</td>
                                        <td>{{ $vo -> num }}</td>
                                        <td>{{ $vo -> package_status }}</td>
                                        <td>{{ $vo -> remark }}</td>
                                        <td>@if(isset($deal_methods[$vo -> deal_method])){{ $deal_methods[$vo -> deal_method] }}@endif</td>

                                        <td>{{ $vo -> weight_json }}</td>
                                        <td>@if($vo -> created_at){{ date('Y-m-d H:i:s',$vo -> created_at) }}@endif</td>
                                        <td>
                                            <a  class="updateRepertoryData"  data="{{ $vo -> id }}"  style="cursor:pointer;">维护</a>
                                            <br>
                                            <a  class="printAll"  data="{{ $vo -> id }}"  style="cursor:pointer;margin-top:3px;">打印</a>
                                            <br>
                                            <a  class="printData"  data="{{ $vo -> id }}"    cangwei="{{ $vo -> cangwei }}" danhao="{{ trim($vo -> numbers) }}"  style="cursor:pointer;margin-top:3px;">打印标识卡</a>
                                        </td>
                                    </tr>

                                @endforeach
                                @endif


                                </tbody>
                            </table>



                            <h1 style="margin-top:100px;color:rebeccapurple;">在途清单</h1>
                            <table class="table table-hover">
                                <tbody>
                                <tr>
                                    <th>编号</th>
                                    <th>入仓号</th>
                                    <th>到货物流单号</th>
                                    <th>母单号</th>
                                    <th>发件日期</th>
                                    <th>预计到货日期</th>
                                    <th>件数</th>
                                    <th>包裹状态</th>
                                    <th>备注</th>
                                    <th>重量</th>

                                    <th>创建日期</th>

                                </tr>
                                @if(isset($repertory_under) && count($repertory_under))
                                    @foreach($repertory_under as $key => $vo)

                                        <tr>
                                            <td>{{ config('admin.repertory_id_prefix').sprintf('%06s',$vo -> id) }}</td>
                                            <td>{{ $vo -> ru_number }}</td>
                                            <td>{{ $vo -> numbers }}</td>
                                            <td>{{ $vo -> f_name }}</td>
                                            <td>{{ $vo -> fajian_date }}</td>
                                            <td>{{ $vo -> yuji_date }}</td>
                                            <td>{{ $vo -> num }}</td>
                                            <td>{{ $vo -> package_status }}</td>
                                            <td>{{ $vo -> remark }}</td>
                                            <td>{{ $vo -> weight_json }}</td>
                                            <td>@if($vo -> created_at){{ $vo -> created_at }}@endif</td>

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
                        //打印标示卡
                        $('.printData').click(function(){
                            var cangwei = $(this).attr('cangwei');
                            var id = $(this).attr('data');
                            var danhao = $(this).attr('danhao');
                            if(!cangwei){
                                //没有仓位号 必须填写才能
                                cangwei = $.trim(prompt('请填写仓位号'));
                                console.log(cangwei);
                                if(cangwei){
                                    console.log(cangwei);
                                    var url = '{{ url('saveCangwei') }}';
                                    $.ajax({
                                        type: 'POST',
                                        url: url,
                                        data: {
                                            cangwei:cangwei,
                                            id:id
                                        },
                                        dateType:'json',
                                        headers: {
                                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                        },
                                        success: function (data) {
                                            if(data.code == 'success'){
                                                location.href='{{ url('makeMarkPdf') }}'+'?s_number='+id+'&cangwei='+cangwei+'&danhao='+danhao;
                                            }



                                        },
                                        error: function (xhr, type) {
                                            alert('Ajax error!')
                                        }
                                    })
                                }

                            }else{
                                location.href='{{ url('makeMarkPdf') }}'+'?s_number='+id+'&cangwei='+cangwei+'&danhao='+danhao;
                            }

                            return false;
                        });


                        //打印全部的小标签
                        $('.printAll').click(function(){
                            var id = $(this).attr('data');
                            layer.load(1);
                            $.ajax({
                                method: 'post',
                                url: '{{ url('printRepertoryList') }}',
                                data: {
                                    _token:'{{ csrf_token() }}',
                                    id:id
                                },
                                dataType:'json',
                                success:function(resdata){
                                    alert(resdata.msg);
                                }
                            });
                        });




                        $('.updateRepertoryData').click(function(){
                            var repertory_id = $(this).attr('data');
                            layer.open({
                                type: 2,
                                title: '维护重量',
                                shadeClose: true,
                                shade: 0.8,
                                area: ['50%', '70%'],
                                content: '{{ url('updateRepertoryData') }}' +  '?repertory_id=' + repertory_id,
                                end:function(){
                                    location.reload();
                                }
                            });

                            return false;




                            $.ajax({
                                method: 'post',
                                url: '{{ url('updateRepertoryData') }}',
                                data: {
                                    _token:'{{ csrf_token() }}',
                                    type:'post'
                                },
                                success:function(resdata){
                                    layer.closeAll('loading');

                                    layer.open({
                                        type: 1,
                                        area: ['80%', '80%'], //宽高
                                        content: resdata,
                                        end:function(){
                                            location.reload();
                                        }
                                    });
                                }
                            });
                        })


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

                        //提交到货条码
                        layer.load(1);
                        var url = '{{ url('subScanGoodsRes') }}';
                        $.ajax({
                            type: 'POST',
                            url: url,
                            data: {
                                scan:wuliu_num
                            },

                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            success: function (data) {
                                layer.closeAll('loading');

                                if(data == 'update'){
                                    alert('扫描成功，本订单已成功入库');
                                }
                                if(data == 'success'){
                                    alert('扫描成功，本订单已录系统，等待分配客户');
                                }
                                if(data == 're_scan'){
                                    alert('重复扫描');
                                }

                                location.reload();

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
