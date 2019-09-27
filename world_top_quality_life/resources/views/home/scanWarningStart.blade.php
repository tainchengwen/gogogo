@include('home.scanPublic')
<title>异常件到货扫描</title>

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
                                    <div style="margin-top:10px;">请扫描异常件包裹单号：<input type="text" id="texts" name="wuliu_num" style="width:200px;height:30px;line-height: 30px" /></div>

                                </div>

                            </form>
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
                        var url = '{{ url('scanWarningStartRes') }}';
                        $.ajax({
                            type: 'POST',
                            url: url,
                            data: {
                                scan:wuliu_num
                            },
                            dataType:'json',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            success: function (data) {
                                layer.closeAll('loading');

                                if(data.code == 200){
                                    alert('扫描成功');
                                    location.reload();
                                }else{
                                    alert(data.msg);
                                    location.reload();
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
