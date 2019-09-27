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
                                <a class="btn btn-sm btn-facebook"  style="font-size:25px;margin-top:5px;" href="{{ url('checkGoods') }}" ><i class="fa fa-crosshairs"></i>盘点</a>
                            </div>
                            <div style="float: left;width:50%;text-align: left;">
                                <h1>{{ $info -> goods_number }}</h1>
                            </div>
                            <div style="clear:both;">

                            </div>
                        </div>



                        <div class="box-header" style="height:138px;">
                            <form method="get"  id="myForm" onkeydown="if(event.keyCode==13) return getData()&&false;" >
                                <h3 class="box-title"></h3>
                                <div class="pull-left" style="">
                                    <div style="margin-top:10px;">请扫描包裹编号或商品编码：<input type="text" id="texts" name="scan_goods_number" style="width:200px;height:30px;line-height: 30px" /></div>

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
                                    <th>包裹编号</th>
                                    <th>创建日期</th>
                                    <th>操作</th>
                                </tr>
                                @if(isset($check_goods) && count($check_goods))
                                @foreach($check_goods as $key => $vo)

                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <td>{{ $vo -> scan_goods_number }}</td>
                                        <td>{{ date('Y-m-d H:i:s',$vo -> created_at) }}</td>
                                        <td>
                                            <a class="btn btn-sm btn-primary deleteData" data="{{ $vo -> id }}"    ><i class="fa fa-"></i>删除</a>
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

                        var scan_goods_number = $.trim($('#texts').val());
                        if(!scan_goods_number){
                            alert('请输入包裹编号');
                            location.reload();
                            return false;
                        }


                        layer.load(1);
                        var url = '{{ url('addCheckGoodsDetailRes') }}';
                        $.ajax({
                            type: 'POST',
                            url: url,
                            data: {
                                scan_goods_number:scan_goods_number,
                                check_goods_id:{{ $_GET['id'] }}
                            },

                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            success: function (data) {
                                layer.closeAll('loading');
                                location.reload();

                            },
                            error: function (xhr, type) {
                                layer.closeAll('loading');
                                alert('数据错误');
                            }
                        });



                        return false;
                    }






                    $('.deleteData').click(function(){
                        var id = $(this).attr('data');
                        var url = '{{ url('deleteCheckGoods') }}';



                        if(confirm('确定删除此条数据么？')){
                            $.ajax({
                                type: 'POST',
                                url: url,
                                data: {id:id},

                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                success: function (data) {

                                    location.reload();


                                },
                                error: function (xhr, type) {
                                    alert('Ajax error!')
                                }
                            })
                        }


                            return false;


                    })


                </script></div></div>

    </section>

</div>
