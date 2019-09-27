@include('home.scanPublic')
<title>匹配包裹</title>
<style>
    .table tr{
        height:50px;
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
                        <div class="box-header" style="height:70px;width:100%;">
                            <div style="float: left;width:50%;text-align: center;">
                                <a class="btn btn-sm btn-facebook"  style="font-size:25px;margin-top:5px;" href="{{ url('scanGoods') }}" ><i class="fa fa-crosshairs"></i>返回首页</a>
                            </div>
                            <div style="float: left;width:50%;text-align: center;">
                                @if(!isset($_GET['type']))
                                <a class="btn btn-sm btn-success"  style="font-size:25px;margin-top:5px;" href="{{ url('matchPackage').'?type=1' }}" ><i class="fa fa-crosshairs"></i>根据列表查找匹配包裹</a>
                                @else
                                    <a class="btn btn-sm btn-success"  style="font-size:25px;margin-top:5px;" href="{{ url('matchPackage') }}" ><i class="fa fa-crosshairs"></i>添加需要匹配的包裹</a>
                                @endif

                            </div>

                            <div style="clear:both;"></div>
                        </div>



                        <div class="box-header" style="height:138px;">
                            <form method="get"  id="myForm" onkeydown="if(event.keyCode==13) return getData()&&false;" >
                                <h3 class="box-title"></h3>

                                <div class="pull-left" style="">
                                    <div style="">
                                        @if(!isset($_GET['type']))
                                        <h2>添加需要匹配的包裹</h2>
                                            <input type="hidden" class="match_type" value="1" />
                                        @else
                                            <h2>根据列表查找匹配包裹</h2>
                                            <input type="hidden" class="match_type" value="2" />
                                        @endif
                                    </div>



                                    <div style="margin-top:10px;">请扫描标签/面单：<input type="text" id="texts"  style="width:200px;height:30px;line-height: 30px" /> <a id="qingkong">清空</a></div>

                                </div>

                            </form>
                        </div>




                        <div class="box-body table-responsive no-padding">
                            <table class="table table-hover">
                                <tbody>
                                <tr>
                                    <th>序号</th>
                                    <th>标签</th>
                                    <th>编号</th>
                                    <th>剩余</th>
                                    <th>操作</th>

                                    <th>序号</th>
                                    <th>标签</th>
                                    <th>编号</th>
                                    <th>剩余</th>
                                    <th>操作</th>

                                    <th>序号</th>
                                    <th>标签</th>
                                    <th>编号</th>
                                    <th>剩余</th>
                                    <th>操作</th>
                                </tr>
                                @if(isset($data) && count($data))
                                    @php $number = 0; @endphp
                                    @foreach($data as $key => $vo)

                                        <tr>
                                            @if(isset($vo[0]))
                                            <td style="color:red;font-weight: 800">{{ $number + 1 }}</td>
                                            <td>{{ $vo[0] -> scan_number }}</td>
                                            <td>{{ $vo[0] -> package_num }}</td>
                                            <td>{{ $vo[0] -> number }}</td>
                                            <td>
                                                <a class="btn btn-sm btn-primary deleteData" data="{{ $vo[0] -> id }}"     ><i class="fa fa-trash"></i></a>
                                            </td>
                                            @endif

                                            @if(isset($vo[1]))
                                                    <td style="color:red;font-weight: 800">{{ $number + 2 }}</td>
                                                    <td>{{ $vo[1] -> scan_number }}</td>
                                            <td>{{ $vo[1] -> package_num }}</td>
                                            <td>{{ $vo[1] -> number }}</td>
                                            <td>
                                                <a class="btn btn-sm btn-primary deleteData" data="{{ $vo[1] -> id }}"     ><i class="fa fa-trash"></i></a>
                                            </td>
                                            @endif


                                            @if(isset($vo[2]))
                                                    <td style="color:red;font-weight: 800">{{ $number + 3 }}</td>
                                            <td>{{ $vo[2] -> scan_number }}</td>
                                            <td>{{ $vo[2] -> package_num }}</td>
                                            <td>{{ $vo[2] -> number }}</td>
                                            <td>
                                                <a class="btn btn-sm btn-primary deleteData" data="{{ $vo[2] -> id }}"     ><i class="fa fa-trash"></i></a>
                                            </td>
                                            @endif

                                                @php $number = $number + 3; @endphp
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
                            var url = '{{ url('deleteMatchData') }}';
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

                    $('#qingkong').click(function(){
                        if(confirm('确定删除所有的数据么？')){
                            var url = '{{ url('deleteAllMatchData') }}';
                            $.ajax({
                                type: 'POST',
                                url: url,
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
                        var numbers = $.trim($('#texts').val());
                        if(!numbers){
                            //alert('请扫码');
                            //location.reload();
                            return false;
                        }
                        var match_type = $('.match_type').val();

                        //提交
                        layer.load(1);


                        var url = '{{ url('matchPackageAjax') }}';
                        $.ajax({
                            type: 'POST',
                            url: url,
                            data: {
                                numbers:numbers,
                                match_type:match_type
                            },
                            dateType:'json',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            success: function (data) {
                                layer.closeAll('loading');
                                if(data.code == '2'){
                                    playMusic();
                                    bigMsg('添加成功','success');
                                    setTimeout(function(){
                                        location.reload();
                                    },1200);
                                }

                                if(data.code == '3'){
                                    voice_error();
                                    bigMsg(data.msg,'error')
                                    setTimeout(function(){
                                        location.reload();
                                    },1200);
                                }
                                if(data.code == '5'){
                                    voice_error();
                                    bigMsg(data.msg,'error')
                                    setTimeout(function(){
                                        location.reload();
                                    },1200);
                                }

                                //找到了
                                if(data.code == '1'){
                                    findSuccess();
                                    bigMsg(data.msg);
                                    return false;
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
