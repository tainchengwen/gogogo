@include('home.scanPublic')
<title>盘点</title>

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
                                <a class="btn btn-sm btn-facebook"  style="font-size:25px;margin-top:5px;" href="{{ url('scanGoods') }}" ><i class="fa fa-crosshairs"></i>返回首页</a>
                            </div>

                            <div style="clear:both;"></div>
                        </div>



                        <div class="box-header" style="height:138px;">
                            <form method="get"  id="myForm" onkeydown="if(event.keyCode==13) return getData()&&false;" >
                                <h3 class="box-title"></h3>
                                <div class="pull-left" style="">
                                    <div style="margin-top:10px;">请输入新的盘点编号：<input type="text" id="texts" name="goods_number" style="width:200px;height:30px;line-height: 30px" /></div>

                                </div>

                            </form>
                        </div>




                        @if(isset($_GET['id']) && $_GET['id'])

                        <!-- /.box-header -->
                        <div class="box-body table-responsive no-padding">
                            <table class="table table-hover">
                                <tbody>
                                <tr>
                                    <th>序号</th>
                                    <th>盘点编号</th>
                                    <th>盘点个数</th>

                                    <th>创建日期</th>
                                    <th>操作</th>
                                </tr>
                                @if(isset($check_goods) && count($check_goods))
                                @foreach($check_goods as $key => $vo)

                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <td>{{ $vo -> goods_number }}</td>
                                        <td></td>

                                        <td>{{ date('Y-m-d H:i:s',$vo -> created_at) }}</td>
                                        <td>
                                            <a class="btn btn-sm btn-primary printData" data="{{ $vo -> id }}"    ><i class="fa fa-print"></i>打印标识卡</a>
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

                            @endif


                    </div>
                </div>
                <script>



                    function getData(){
                        //return true;

                        var goods_number = $.trim($('#texts').val());
                        if(!goods_number){
                            alert('请输入盘点编号');
                            location.reload();
                            return false;
                        }


                        layer.load(1);
                        var url = '{{ url('addCheckGoodsRes') }}';
                        $.ajax({
                            type: 'POST',
                            url: url,
                            data: {
                                goods_number:goods_number
                            },
                            dataType: "json",
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            success: function (data) {
                                layer.closeAll('loading');
                                if(data.code == 200){
                                    location.href='{{ url('addCheckGoodsDetail') }}'+'?id='+data.msg;
                                }

                                //location.reload();

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
