@include('home.scanPublic')
<title>查找商品</title>

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


                        <div class="box-header" style="">
                            后台需要找的单号：
                        </div>

                        <div class="box-header" style="">

                            @if($info)
                                @foreach($info as $vo)
                                    {{ $vo -> code }} <br>
                            @endforeach
                        @endif


                            <!--<textarea id="findGoodsList" style="height:200px;width:400px;" disabled="" >

                            </textarea>-->
                        </div>


                        <div class="box-header" style="">
                            <form method="get"  id="myForm" onkeydown="if(event.keyCode==13) return getData()&&false;" >
                                <h3 class="box-title"></h3>

                                <div class="pull-left" style="">
                                    <div style="">
                                        <h2>请扫描包裹编码</h2>
                                    </div>

                                    <div style="margin-top:10px;"><input type="text" id="texts"  style="width:200px;height:30px;line-height: 30px" /></div>

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

                        var wuliu_num = $.trim($('#texts').val());
                        if(!wuliu_num){
                            alert('请扫码');
                            location.reload();
                            return false;
                        }


                        var numbers = $.trim($('#texts').val());
                        var findGoodsList = $('#findGoodsList').val();



                        //提交
                        layer.load(1);
                        var url = '{{ url('scanFindGoodsRes') }}';
                        $.ajax({
                            type: 'POST',
                            url: url,
                            data: {
                                numbers:numbers,
                                findGoodsList:findGoodsList
                            },
                            dateType:'json',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            success: function (data) {

                                if(data.code == 'success'){
                                    bigMsg('找到了','success');
                                    findSuccess();
                                    setTimeout(function(){
                                        location.reload();
                                    },1500);
                                }else{
                                    bigMsg('没找到','error');
                                    noFind();
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





                </script></div></div>

    </section>

</div>
<script>
</script>
