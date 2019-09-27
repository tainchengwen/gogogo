@include('home.scanPublic')
<title>扫描商品编码</title>

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
                                <a class="btn btn-sm btn-success"  style="font-size:25px;margin-top:5px;" href="{{ url('scanCommodityCode') }}" ><i class="fa fa-crosshairs"></i>扫描下个包裹</a>
                            </div>
                            <div style="float: left;width:50%;text-align: center;">
                                <a class="btn btn-sm btn-facebook"  style="font-size:25px;margin-top:5px;" href="{{ url('scanGoods') }}" ><i class="fa fa-crosshairs"></i>返回首页</a>
                            </div>

                            <div style="clear:both;"></div>
                        </div>



                        <div class="box-header" style="height:200px;">
                            <form method="get"  id="myForm" onkeydown="if(event.keyCode==13) return getData()&&false;" >
                                <h3 class="box-title"></h3>

                                <div class="pull-left" style="">
                                    <div style="">
                                        <h1>包裹编号：{{ $package_info -> number }}</h1>
                                        <input type="hidden" id="fid" value="{{ $package_info -> id }}" />
                                        <h2>请先扫描包裹内商品编码</h2>
                                    </div>


                                    <div style="margin-top:10px;"><input type="text" id="texts"  style="width:200px;height:30px;line-height: 30px" /></div>

                                </div>

                            </form>
                        </div>



                        <div class="box-body table-responsive no-padding">
                            <table class="table table-hover">
                                <tbody>
                                <tr>
                                    <th>序号</th>
                                    <th>商品编码</th>
                                    <th>扫描时间</th>
                                    <th>操作</th>
                                </tr>
                                @if(isset($code_info) && count($code_info))
                                    @foreach($code_info as $key => $vo)

                                        <tr>
                                            <td>{{ $key + 1 }}</td>
                                            <td>{{ $vo -> code }}</td>
                                            <td>{{ $vo -> created_at }}</td>
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

                    $('.deleteData').click(function(){
                        var id = $(this).attr('data');


                        if(confirm('确定删除此条数据么？')){
                            var url = '{{ url('deleteScanCommodityCode') }}';
                            var batch_id = $('#batch_id').val();
                            $.ajax({
                                type: 'POST',
                                url: url,
                                data: {id:id},
                                dataType:'json',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                success: function (data) {


                                    if(data.code == 'success'){
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

                    function getData(){
                        //return true;

                        var wuliu_num = $.trim($('#texts').val());
                        if(!wuliu_num){
                            //alert('请扫码');
                            //location.reload();
                            return false;
                        }


                        var numbers = $.trim($('#texts').val());
                        var fid = $.trim($('#fid').val());

                        //提交
                        layer.load(1);
                        var url = '{{ url('scanCommodityCodeGoodsRes') }}';
                        $.ajax({
                            type: 'POST',
                            url: url,
                            data: {
                                numbers:numbers,
                                fid:fid
                            },
                            dateType:'json',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            success: function (data) {
                                if(data.code == 'success'){
                                    $('#texts').val('');
                                    playMusic();
                                    $('#texts').attr('readonly','readonly');
                                    location.reload();
                                    /*
                                    bigMsg('扫描成功','success');
                                    setTimeout(function(){
                                        location.reload();
                                    },1100);
                                    */

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





                </script></div></div>

    </section>

</div>
<script>
    $(function(){
        //playerGoods();
    })
</script>
