@include('home.scanPublic')
<title>扫描标示卡打印标签</title>

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
                            <div style="float: left;width:50%;text-align: left;line-height: 30px">

                            </div>
                            <div style="clear:both;"></div>
                        </div>



                        <div class="box-header" style="height:138px;">
                            <form method="get"  id="myForm" onkeydown="if(event.keyCode==13) return getData()&&false;" >
                                <h3 class="box-title"></h3>
                                <div class="pull-left" style="">
                                    <div style="margin-top:10px;">请扫描标示卡：<input type="text" id="texts" name="wuliu_num" style="width:200px;height:30px;line-height: 30px" /></div>
                                    <div style="margin-top:10px;">请填写重量，不填默认2.7-2.95：<input type="text" id="weight_input" style="width:200px;height:30px;line-height: 30px" /></div>

                                </div>

                            </form>
                        </div>







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

                        var op_user_id = $.trim(prompt('请扫描员工条码'));
                        if(!op_user_id){
                            alert('您没有扫描员工条码');
                            location.reload();
                            return false;
                        }

                        var weight_input = $.trim($('#weight_input').val());


                        //提交到货条码
                        layer.load(1);
                        var url = '{{ url('makePackageLabelRes') }}';
                        $.ajax({
                            type: 'POST',
                            url: url,
                            data: {
                                scan:wuliu_num,
                                op_user_id:op_user_id,
                                weight_input:weight_input
                            },
                            dataType:'json',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            success: function (data) {
                                layer.closeAll('loading');
                                if(data.code == '500'){
                                    bigMsg(data.msg,'error');
                                    setTimeout(function(){
                                        location.reload();
                                    },1500);
                                    return false;
                                }else{
                                    bigMsg('正在打印','success');
                                    playMusic();
                                    setTimeout(function(){
                                        location.reload();
                                    },1000);
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
