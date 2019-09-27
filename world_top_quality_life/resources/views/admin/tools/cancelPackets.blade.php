<div class="btn-group ">
    <a class="btn btn-sm  btn-info"><i class="fa fa-anchor"></i> A线路</a>
    <button type="button" class="btn btn-sm btn-info dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
        <span class="caret"></span>
        <span class="sr-only">Toggle Dropdown</span>
    </button>
    <ul class="dropdown-menu" role="menu">
        @if(strstr($compact['admin_name'],'admin'))
            <li><a  class="makeOrderPage" data="4" >生成面单HK(NN100)</a></li>
            <li><a  class="makeOrderPage" data="6" >生成面单MO(NN100)</a></li>


            <li><a  class="makeOrderPage" data="5" >生成面单HK(XS001)</a></li>
            <li><a  class="makeOrderPage" data="7" >生成面单MO(XS001)</a></li>
            @elseif($compact['from_area_admin_name'] == 'admin')
            <li><a  class="makeOrderPage" data="4" >生成面单HK(NN100)</a></li>
            <li><a  class="makeOrderPage" data="6" >生成面单MO(NN100)</a></li>
            @elseif($compact['from_area_admin_name'] == 'xx')
            <li><a  class="makeOrderPage" data="5" >生成面单HK(XS001)</a></li>
            <li><a  class="makeOrderPage" data="7" >生成面单MO(XS001)</a></li>
        @endif


        <!--
        <li><a href="" target="_blank">生成pdf(勾选订单)</a></li>
        <li><a href="" target="_blank">生成pdf(勾选包裹)</a></li>
        -->
    </ul>
</div>




<div class="btn-group ">
    <a class="btn btn-sm  btn-info"><i class="fa fa-anchor"></i> B线路</a>
    <button type="button" class="btn btn-sm btn-info dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
        <span class="caret"></span>
        <span class="sr-only">Toggle Dropdown</span>
    </button>
    <ul class="dropdown-menu" role="menu">
        <li><a class="makeOrderPage"  data="8" >生成面单</a></li>

    </ul>
</div>



<a class="btn btn-sm btn-primary " id="cancelPackages"><i class="fa fa-remove"></i> 取消单号</a>
<script>
    //生成单号
    $('.makeOrderPage').click(function(){
        var data = $(this).attr('data');
        var length = $('.grid-row-checkbox').length;
        var check_arr =  [];



        for(var i = 0 ;i < length; i++){
            if($('.grid-row-checkbox').eq(i).is(':checked')){
                check_arr.push($('.grid-row-checkbox').eq(i).attr('data-id'));
            }
        }
        console.log(check_arr);
        if(!check_arr.length){
            alert('请选择');return false;
        }

        layer.load(1);
        $.ajax({
            method: 'post',
            url: '/admin/exportApi',
            data: {
                _token:LA.token,
                ids: check_arr,
                action: data
            },
            success: function (data) {

                if(data == 'error'){
                    layer.closeAll('loading');
                    $.pjax.reload('#pjax-container');
                    toastr.success('操作失败');
                    return false;
                }

                console.log($.parseJSON(data).length);
                if(!$.parseJSON(data).length){
                    layer.closeAll('loading');
                    alert('未付款状态不可以生成单号');return false;
                }

                $.ajax({
                    method: 'post',
                    url: '{{ admin_url('apiAlertPage') }}',
                    data: {
                        _token:LA.token,
                        data:data,
                        type:'post'
                    },
                    success:function(resdata){
                        layer.closeAll('loading');

                        layer.open({
                            type: 1,
                            area: ['80%', '80%'], //宽高
                            content: resdata,
                            end:function(){
                                location.reload();
                            }
                        });
                    }
                });





                //layer.closeAll('loading');
                //$.pjax.reload('#pjax-container');
                //toastr.success('操作成功');
            }
        });





    });


    //取消单号
    $('#cancelPackages').click(function(){
        var length = $('.package_ids').length;
        var check_arr =  [];
        var package_num_arr = [];


        for(var i = 0 ;i < length; i++){
            if($('.package_ids').eq(i).is(':checked')){
                check_arr.push($('.package_ids').eq(i).attr('data'));
                package_num_arr.push($('.package_ids').eq(i).attr('package_num'));
            }
        }
        console.log(check_arr);
        if(!check_arr.length){
            alert('请选择');return false;
        }

        var package_nums = package_num_arr.join("\r");



        if(confirm('确认取消单号么,共'+check_arr.length+'个单号'+'\r'+package_nums)){
            layer.load(1);
            var url = '{{ admin_base_path('cancelPackageWuliuNum') }}';
            $.ajax({
                type: 'POST',
                url: url,
                data:{'check_arr':check_arr},

                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success: function(data){
                    if(data == 'nodata'){
                        layer.closeAll('loading');
                        toastr.error('没有要操作的数据');
                        return false;
                    }
                    if(data == 'route_error'){
                        layer.closeAll('loading');
                        toastr.error('不同线路的单号不能批量取消');
                        return false;
                    }

                    $.ajax({
                        type: 'post',
                        url: '{{ admin_base_path('cancelpackageAlert') }}',
                        data: {
                            data:data
                        },
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        success:function(resdata){
                            layer.closeAll('loading');
                            layer.open({
                                type: 1,
                                area: ['80%', '80%'], //宽高
                                content: resdata ,
                                end:function(){
                                    location.reload();
                                }
                            });
                        }
                    });

                },
                error: function(xhr, type){
                    alert('Ajax error!')
                }
            });
        }


    })


    //全选
    function selectAll(){

        if($('#checkAll').is(':checked')){
            var length = $('.package_ids').length;
            for(var i = 0 ;i < length; i++){
                $('.package_ids').eq(i).prop("checked", true);
            }

        }else{
            var length = $('.package_ids').length;
            for(var i = 0 ;i < length; i++){
                $('.package_ids').eq(i).prop("checked", false);
            }
        }
    }



</script>