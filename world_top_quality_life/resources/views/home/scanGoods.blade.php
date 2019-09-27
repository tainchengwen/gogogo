@include('home.scanPublic')
<title>到货扫描</title>
<style>
    .b-box{
        float: left;width:16%;text-align: center;
    }
</style>
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
                            <div class="b-box">
                                <a class="btn btn-sm btn-primary grid-refresh"  style="font-size:25px;margin-top:5px;" id="" href="{{ url('scanStart') }}"><i class="fa fa-search" ></i>到货扫描</a>
                            </div>
                            <div class="b-box">
                                <a class="btn btn-sm btn-success"  style="font-size:25px;margin-top:5px;" href="{{ url('repertoryList') }}" ><i class="fa fa-save"></i>物流清单</a>
                            </div>
                            <div  class="b-box">
                                <a class="btn btn-sm btn-facebook"  style="font-size:25px;margin-top:5px;" href="{{ url('createNewBatch') }}" ><i class="fa fa-crosshairs"></i>生成新托盘</a>
                            </div>
                            <div  class="b-box">
                                <a class="btn btn-sm btn-success"  style="font-size:25px;margin-top:5px;" href="{{ url('splitPackages') }}" ><i class="fa fa-bomb"></i>拆单</a>
                            </div>
                            <div  class="b-box">
                                <a class="btn btn-sm btn-facebook"  style="font-size:25px;margin-top:5px;" href="{{ url('printScanPage') }}" ><i class="fa fa-print"></i>扫描打印</a>
                            </div>
                            <div  class="b-box">
                                <a class="btn btn-sm btn-success"  style="font-size:25px;margin-top:5px;" href="{{ url('scanCommodityCode') }}" ><i class="fa fa-print"></i>扫描商品</a>
                            </div>
                            <div style="clear:both;"></div>
                        </div>

                        <div class="box-header" style="height:100px;width:100%;">
                            <div class="b-box">
                                <a class="btn btn-sm btn-success"  style="font-size:25px;margin-top:5px;" id="" href="{{ url('scanFindGoods') }}"><i class="fa fa-search" ></i>查找包裹</a>
                            </div>

                            <div class="b-box">
                                <a class="btn btn-sm btn-facebook"  style="font-size:25px;margin-top:5px;" id="" href="{{ url('matchPackage') }}"><i class="fa fa-search" ></i>匹配包裹</a>
                            </div>

                            <div class="b-box">
                                <a class="btn btn-sm btn-success"  style="font-size:25px;margin-top:5px;" id="" href="{{ url('exPrint') }}"><i class="fa fa-search" ></i>补打印</a>
                            </div>

                            <div class="b-box">
                                <a class="btn btn-sm btn-facebook"  style="font-size:25px;margin-top:5px;" id="" href="{{ url('addTempData') }}"><i class="fa fa-search" ></i>扫描记录</a>
                            </div>

                            <div class="b-box">
                                <a class="btn btn-sm btn-success"  style="font-size:25px;margin-top:5px;" id="" href="{{ url('checkGoods') }}"><i class="fa fa-search" ></i>盘点</a>
                            </div>

                            <div class="b-box">
                                <a class="btn btn-sm btn-primary grid-refresh"  style="font-size:25px;margin-top:5px;" id="" href="{{ url('scanWarningStart') }}"><i class="fa fa-search" ></i>异常件</a>
                            </div>

                            <div style="clear:both;"></div>
                        </div>

                        <div class="box-header" style="height:100px;width:100%;">

                            <div class="b-box">
                                <a class="btn btn-sm btn-success"  style="font-size:25px;margin-top:5px;" id="" href="{{ url('makePackageLabel') }}"><i class="fa fa-search" ></i>打包出标签</a>
                            </div>



                            <div style="clear:both;"></div>
                        </div>

                        <!-- /.box-header -->
                        <div class="box-body table-responsive no-padding">
                            <table class="table table-hover">
                                <tbody>
                                <tr>
                                    <th>序号</th>
                                    <th>托盘编号</th>
                                    <th>历史托盘号</th>
                                    <th>入库物流单号</th>
                                    <th>当前数量</th>
                                    <th>最后修改时间</th>
                                    <th>操作</th>
                                </tr>
                                @if(isset($data) && count($data))
                                @foreach($data as $key => $vo)

                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <td>{{ $vo -> id }}</td>
                                        <td>{{ $vo -> batch_num }}</td>
                                        <td>
                                            @if(isset($vo -> relation) && $vo -> relation)
                                                @foreach($vo -> relation as $key => $value)
                                                    @if(isset($value -> temp_repertory -> numbers))
                                                    <a style="color:red;cursor:pointer;" href="{{ url('createNewBatch').'?batch='.$value -> id }}" >{{ $value -> temp_repertory -> numbers }}</a><br>
                                                    @endif
                                                        @endforeach
                                            @endif
                                        </td>
                                        <td>{{ $vo -> count_packages }}</td>
                                        <td>{{ date('Y-m-d H:i',$vo -> updated_at) }}</td>
                                        <td>

                                            <a class="btn btn-sm btn-primary " href="{{ url('createNewBatch').'?pici_id='.$vo -> id }}"     ><i class="fa fa-search"></i>新物流单号扫描</a>
                                                @if(!$vo -> count_packages)
                                                <a class="btn btn-sm btn-danger deleteData" data="{{ $vo -> id }}" ><i class="fa fa-trash"></i>&nbsp;&nbsp;删除</a>
                                                    @endif
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
                        $('#scanGoods').click(function(){
                            var scan;
                            scan=$.trim(prompt("请扫描到货条码"));
                            if(scan){
                                //提交到货条码
                                layer.load(1);
                                var url = '{{ url('subScanGoodsRes') }}';
                                $.ajax({
                                    type: 'POST',
                                    url: url,
                                    data: {
                                        scan:scan
                                    },

                                    headers: {
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                    },
                                    success: function (data) {
                                        layer.closeAll('loading');
                                        alert('到货扫描成功');

                                    },
                                    error: function (xhr, type) {
                                        layer.closeAll('loading');
                                        alert('数据错误');
                                    }
                                });
                            }
                        })
                    })








                    function getData(){
                        //return true;
                        var url = '{{ url('barCodeAjax') }}';
                        var wuliu_num = $.trim($('#texts').val());
                        if(!wuliu_num){
                            alert('请扫码');
                            location.reload();
                            return false;
                        }
                        var query = '';

                        @if(isset($_GET['ids']) && $_GET['ids'])
                            query = '{{ $_GET['ids'] }}' ;
                        @endif
                        layer.load(1);
                        $.ajax({
                            type: 'POST',
                            url: url,
                            data: {
                                wuliu_num:wuliu_num,
                                query:query
                            },

                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            success: function (data) {
                                layer.closeAll('loading');
                                console.log(data);

                                if(data == 'error'){
                                    alert('输入单号不存在,或者已删除');
                                    location.reload();
                                    return false;
                                }
                                if(data == 'repeat'){
                                    alert('输入单号重复');
                                    location.reload();
                                    return false;
                                }else{
                                    //成功 就跳转
                                    //找到正确的url
                                    var url_right = '{{ url('barCode') }}';
                                    if(query){
                                        url_right = url_right + '?ids='+query + ','+wuliu_num;
                                    }else{
                                        url_right = url_right + '?ids=' + wuliu_num;
                                    }
                                    console.log(url_right);
                                    location.href=url_right;
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
                        var url = '{{ url('deleteScanGoods') }}';


                        if(confirm('确定删除此条数据么？')){
                            $.ajax({
                                type: 'POST',
                                url: url,
                                data: {id:id},

                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                success: function (data) {

                                    if(data == 'error'){
                                        alert('删除失败');
                                        location.reload();
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
