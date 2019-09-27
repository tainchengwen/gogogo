<div class="col-md-12" style="padding-left:9px;">
    <div class="box">
        <div class="box-header">
            <form method="get" action="{{ admin_url('scanMpPackageGoods') }}" id="myForm">
            <h3 class="box-title"></h3>

            <div class="pull-left" style="width:800px;height:80px;line-height:80px;font-size:50px;">
                <input style="width:100%;height:100%;resize:none" name="texts" id="texts" />
            </div>

            <div class="pull-left" style="padding-left:10px;">

                    <a class="btn btn-sm btn-primary grid-refresh" id="submitBtn"><i class="fa fa-search"></i>  确定</a>
                <br>
                    <a class="btn btn-sm btn-facebook" style="margin-top:10px;" href="{{ admin_url('scanMpPackage') }}" ><i class="fa fa-backward"></i>  返回</a>

            </div>

                <input type="hidden" name="hide_input" value="{{{ $hide_input }}}" />
                <input type="hidden" name="id" value="{{{ $_GET['id'] }}}" />

            </form>
        </div>


        <!-- /.box-header -->
        <div class="box-body table-responsive no-padding">
            <table class="table table-hover">
                <tbody>
                <tr>

                    <th>商品编号</th>
                    <th>数量</th>
                    <!--
                    <th>操作</th>
                    -->
                </tr>

                @if($packages)
                    @foreach($packages as $k => $vo)
                        <tr>

                            <td>{{ $vo -> product_no }}</td>
                            <td>{{ $vo -> goods_number }}</td>

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
    $(function () {
        $('#texts').focus();
    });


    $('#submitBtn').click(function(){
        $('#myForm').submit();
    });

    $('#makeAreaOrder').click(function () {
        var url = '{{ admin_url('makeMpAreaOrder') }}';
        var length = $('.ids').length;
        var check_arr = [];
        for(var i = 0 ;i < length; i++){
            if($('.ids').eq(i).is(':checked')){
                check_arr.push($('.ids').eq(i).attr('data'))
            }
        }

        if(!check_arr.length){
            alert('请选择');return false;
        }
        if(confirm('确定生成交货单么')){
            $.ajax({
                type: 'POST',
                url: url,
                data: {
                    ids:check_arr,
                    order_num:1
                },
                dataType:'json',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success: function(data){
                    if(data.code == 'error'){
                        alert(data.msg);return false;
                    }else{
                        location.href = '{{ admin_url('AreaScanOrder') }}';
                    }

                },
                error: function(xhr, type){
                    alert('Ajax error!')
                }
            });
        }


    });

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

    //导出
    $('#exportBtn1').click(function() {
        exportFile(1);
    });
    $('#exportBtn2').click(function() {
        exportFile(2);
    });


        //保存
        $('#saveBtn').click(function(){
            var pici = $.trim($('#pici').val());
            if(!pici){
                alert('请填写批次号');return false;
            }

            var batch_id = $('input[name=batch_id]').val();
            //alert(batch_id);


            //填写批次号后 检验 批次号是否重复
            var url_pici = '{{ admin_url('isBatchRepeat') }}';
            $.ajax({
                type: 'POST',
                url: url_pici,
                data: {pici:pici},

                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success: function(data){
                    if(data == 'repeat' && !batch_id){
                        alert('批次号重复');
                    }else{
                        var length = $('.ids').length;
                        var check_arr = '?';
                        var mark= false;
                        for(var i = 0 ;i < length; i++){
                            if($('.ids').eq(i).is(':checked')){
                                if(check_arr == '?'){
                                    check_arr += 'check_arr[]='+$('.ids').eq(i).attr('data');
                                }else{
                                    check_arr += '&check_arr[]='+$('.ids').eq(i).attr('data');
                                }

                                mark = true;
                            }
                        }
                        if(!mark){
                            alert('请选择');return false;
                        }

                        check_arr += '&pici='+pici;
                        check_arr += '&is_save=yes';



                        if(confirm('确定保存么？')){
                            layer.load(1);
                            check_arr += '&type=save';
                            var url = '{{ admin_base_path('orderPacketExport') }}'+'/1';
                            $.ajax({
                                type: 'POST',
                                url: url + check_arr,
                                data: {type:'save',batch_id:batch_id},

                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                success: function(data){
                                    layer.closeAll('loading');
                                    if(data == 'save_success'){
                                        alert('保存成功');
                                        layer.closeAll('loading');
                                    }

                                },
                                error: function(xhr, type){
                                    alert('Ajax error!');
                                    layer.closeAll('loading');
                                }
                            });
                        }
                    }
                },
                error: function(xhr, type){
                    alert('Ajax error!')
                }
            });








    })



    //导出方法
     function exportFile(type){
        var pici = '导出包裹';

        //填写批次号后 检验 批次号是否重复
        var url_pici = '{{ admin_url('isBatchRepeat') }}';
        $.ajax({
            type: 'POST',
            url: url_pici,
            data: {pici: pici},

            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            success: function (data) {
                if (data == 'repeat') {
                    alert('批次号重复');
                } else {
                    var length = $('.ids').length;
                    var check_arr = '?';
                    var mark = false;
                    for (var i = 0; i < length; i++) {
                        if ($('.ids').eq(i).is(':checked')) {
                            if (check_arr == '?') {
                                check_arr += 'check_arr[]=' + $('.ids').eq(i).attr('data');
                            } else {
                                check_arr += '&check_arr[]=' + $('.ids').eq(i).attr('data');
                            }

                            mark = true;
                        }
                    }
                    if (!mark) {
                        alert('请选择');
                        return false;
                    }

                    check_arr += '&pici=' + pici;
                    check_arr += '&is_save=no';


                    if (confirm('确定导出么？')) {
                        var url = '{{ admin_base_path('orderPacketExport') }}'+'/'+type;
                        window.open(url + check_arr);
                    }
                }
            },
            error: function (xhr, type) {
                alert('Ajax error!')
            }
        })
    }


    //强制删除
    function superDel(type,id){
         // 1 返还 2 不返还
        var url = '{{ admin_url('superDel') }}';
        if(confirm('确定删除订单么？')){
            $.ajax({
                type: 'POST',
                url: url,
                data: {id: id,type:type},

                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success: function (data) {
                    if(data == 'error'){
                        alert('删除失败');return false;
                    }
                    if(data == 'success'){
                        alert('成功');location.reload();
                    }


                },
                error: function (xhr, type) {
                    alert('Ajax error!')
                }
            })
        }



    }


</script>