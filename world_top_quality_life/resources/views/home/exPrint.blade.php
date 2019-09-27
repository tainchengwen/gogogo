@include('home.scanPublic')
<title>补打印</title>

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
                                    <div style="">
                                        <h2>输入任何标签都会打印</h2>
                                    </div>


                                    <div style="margin-top:10px;">输入：<input type="text" id="texts"  style="width:200px;height:30px;line-height: 30px" /></div>

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
                            //alert('请扫码');
                            //location.reload();
                            return false;
                        }


                        var numbers = $.trim($('#texts').val());

                        //提交
                        layer.load(1);
                        var url = '{{ url('exPrintAjax') }}';
                        $.ajax({
                            type: 'POST',
                            url: url,
                            data: {
                                numbers:numbers
                            },
                            dateType:'json',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            success: function (data) {
                                layer.closeAll('loading');
                                bigMsg('正在打印','success');
                                setTimeout(function(){
                                    location.reload();
                                },1100);
                                return false;


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
