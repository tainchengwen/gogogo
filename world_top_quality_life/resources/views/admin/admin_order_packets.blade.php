<div class="col-md-12" style="padding-left:9px;">
    <div class="box">
        <div class="box-header">
            <form method="get" action="{{ admin_url('orderPacket') }}" id="myForm">
            <h3 class="box-title"></h3>

            <div class="pull-left" style="width:300px;height:200px;">
                <textarea style="width:100%;height:100%;resize:none" name="texts" ></textarea>
            </div>

            <div class="pull-left" style="padding-left:10px;">

                    <a class="btn btn-sm btn-primary grid-refresh" id="submitBtn"><i class="fa fa-search"></i>  搜索</a>
                <br>
                    <a class="btn btn-sm btn-facebook" style="margin-top:10px;" href="{{ admin_url('orderPacket') }}" ><i class="fa fa-backward"></i>  重置</a>
                <br>
                    <!--
                    <a class="btn btn-sm btn-danger" style="margin-top:10px;" id="exportBtn" ><i class="fa fa-download"></i>  导出</a>
                    -->
                <div class="btn-group " style="margin-top:10px;">
                    <a class="btn btn-sm btn-twitter"><i class="fa fa-download"></i> 导出</a>
                    <button type="button" class="btn btn-sm btn-twitter dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                        <span class="caret"></span>
                        <span class="sr-only">Toggle Dropdown</span>
                    </button>
                    <ul class="dropdown-menu" role="menu">
                        <li><a  id="exportBtn1">通用模板</a></li>
                        <li><a  id="exportBtn2" >NN100</a></li>
                    </ul>
                </div>

                <br>
                <a class="btn btn-sm btn-danger" style="margin-top:10px;" id="saveBtn" ><i class="fa fa-save"></i>  保存</a>
                <br>
                <div class="input-group" style="width:100px;margin-top:10px;">
                    <input type="text" id="pici" name="pici"  class="form-control price_point" placeholder="批次号"  @if(isset($batch_info -> batch_num)) value="{{ $batch_info -> batch_num }}" readonly=""  @endif  style="width:200px;">
                </div>

                @if(isset($batch_info -> batch_num))
                <input type="hidden" name="batch_id" value="{{ $batch_info -> id }}" />
                    @else
                <input type="hidden" name="batch_id" value="0" />
                @endif

            </div>


            </form>
        </div>
        <!-- /.box-header -->
        <div class="box-body table-responsive no-padding">
            <table class="table table-hover">
                <tbody>
                <tr>
                    <th><input type="checkbox" id="checkAll" onclick="selectAll()" /></th>
                    <th>包裹编号</th>
                    <th>运单号</th>
                    <th>收货人</th>
                    <th>电话</th>
                    <th>地址</th>
                    <th>重量</th>
                    <th>操作</th>
                </tr>

                @if($packages)
                    @foreach($packages as $k => $vo)
                <tr>
                    <td><input type="checkbox" class="ids" data="{{ $vo -> id }}" /> {{ $k + 1 }}</td>
                    <td>{{ $vo -> package_num }}</td>
                    <td>{{ $vo -> wuliu_num }}</td>
                    <td>{{ $vo -> name }}</td>
                    <td>{{ $vo -> tel }}</td>
                    <td>{{ $vo -> address }}</td>
                    <td>{{ $vo -> weight }}</td>
                    <td>
                        <a class="btn btn-sm btn-danger" onclick="superDel(1,{{ $vo -> id }})" data="{{ $vo -> id }}" >DEL(返还)</a>
                        <a class="btn btn-sm btn-danger" onclick="superDel(2,{{ $vo -> id }})" data="{{ $vo -> id }}" >DEL(不返还)</a>
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
    $('#submitBtn').click(function(){
        $('#myForm').submit();
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