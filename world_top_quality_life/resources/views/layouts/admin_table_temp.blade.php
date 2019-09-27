<style type="text/css">
    .tab_box{border: 1px solid #DFDFDF;color: #393939;font-size: 14px;border-radius: 3px;}
    .tab_box .tab_header{height: 40px;line-height: 40px;padding: 0 15px;background:#EFEFEF; }
    .tab_box .tab_header img{vertical-align: middle;}
    .tab_box .tab_header_click{height: 10px;}
    .tab_box .tab_header_img img{height: 36px;}
    .tab_box .tab_content{padding: 0 15px 15px;}
    .tab_box .tab_info{height: 40px;line-height: 40px;}
    .tab_box .tab_info_status{color: #2B7E26}
    .tab_box .tab_info_num{color: #4D38FA}
    .tab_box table{width: 100%;border-collapse:collapse}
    .tab_box table tr:nth-child(1),table tr:nth-child(2){background:#EFEFEF;}
    .tab_box table td{border: 1px solid #DFDFDF;padding: 5px 15px;}
</style>
<script src="{{asset('js/layer/2.2/layer.js')}}"></script>

<div class="tab_box" style="@if($compact['orderinfo'] -> pay_type == 9) color:red;border-color: red;  @endif" >
    <div class="tab_header" >
        <!--
        <img class="tab_header_click" src="{{ asset('img/ic-arrow-down.png') }}">
        -->
        <span class="tab_header_txt" style="color:red;cursor:pointer;"  >{{ $compact['orderinfo'] -> order_num }}</span>
        <!--
        <span class="tab_header_img"><img src="{{ asset('img/ic-express-car.png') }}"></span>
        -->
        <span class="tab_header_txt" >
				&nbsp;&nbsp;{{ $compact['userinfo'] -> nickname }}
				&nbsp;&nbsp;{{$compact['orderinfo'] -> count_package}}个包裹
				&nbsp;&nbsp;记录创建时间：{{date('Y-m-d H:i',$compact['orderinfo'] -> created_at -> timestamp)}}
<!--
                &nbsp;&nbsp;导出时间：2018-03-23 14:51:16
                -->
            @if($compact['orderinfo'] -> from_area)
                {{ $compact['areas_arr'][$compact['orderinfo'] -> from_area] }}
            @endif
            {{ $compact['route_name'] }}


			</span>
    </div>
    <div class="tab_content">
        <div class="tab_info">
            发货状态：<span class="tab_info_status">{{ $compact['status_configs'][$compact['orderinfo'] -> status] }}</span>
            支付状态：<span class="tab_info_status">{{ $compact['pay_status_configs'][$compact['orderinfo'] -> pay_status] }}</span>

            @if($compact['orderinfo'] -> pay_time)&nbsp;&nbsp;支付时间：{{ date('Y-m-d H:i',$compact['orderinfo'] -> pay_time)}} 支付方式：@if($compact['orderinfo'] -> pay_type==0)余额支付@else线下支付@endif @endif

            @if( !in_array($compact['orderinfo']->status ,[2,3,4]) )



                <a style="margin-left:10px;cursor:pointer;" onclick="deleteOrder({{$compact['orderinfo']->id}})"><i class="fa fa-trash"></i>删除订单</a>
            @endif

            @if($compact['orderinfo']->pay_status == 0 || $compact['orderinfo'] -> pay_type == 9)
                <a style="margin-left:10px;cursor:pointer;" onclick="confirmPay({{$compact['orderinfo']->id}})"><i class="fa fa-diamond"></i>支付确认</a>
            @endif

            <a style="margin-left:10px;cursor:pointer;" onclick="location.href='{{ admin_base_path('order').'/'.intval($compact['orderinfo'] -> id).'/'.'edit' }}'"><i class="fa fa-edit"></i>编辑地址</a>

            <!-- 导出pdf -->

            <a style="margin-left:10px;cursor:pointer;"    href="{{ url('exportPdf').'/'.intval($compact['orderinfo'] -> id) }}" target="_blank" ><i class="fa fa-caret-square-o-right"></i>导出pdf</a>

            <!-- 导出订单 -->
            <a style="margin-left:10px;cursor:pointer;"    href="{{ admin_base_path('exportOrder').'/'.intval($compact['orderinfo'] -> id) }}" target="_blank" ><i class="fa fa-caret-square-o-right"></i>导出历史订单</a>

            @if(isset($compact['orderinfo'] -> mp_package_id) && $compact['orderinfo'] -> mp_package_id)
                <a  href="{{ admin_base_path('MpPackageNumber').'/'.$compact['orderinfo'] -> mp_package_id.'/edit' }}" target="_blank" ><i class="fa fa-caret-square-o-right"></i>商品明细</a>
            @endif

            <!--
                &nbsp;&nbsp;发货时间：2018-03-23 07:00:03
                &nbsp;&nbsp;UPS单号：<span class="tab_info_num">1234567899</span>
                -->
        </div>
        <table  >
            <tr>
                <td width="12px"></td>
                <td width="150px">编号</td>
                <td width="80px">收货人</td>
                <td width="110px">电话</td>
                <td >地址</td>
                <td width="80px">重量</td>
                <td >运单号</td>

                @if($compact['username'] == 'admin')
                    <td width="80px">金额</td>
                    <td width="90px">区域成本</td>
                    <td width="120px">总成本</td>
                    @else
                    <td width="120px">金额</td>
                @endif

                    <td style="width:74px;">操作</td>

            </tr>
            @if(!empty($compact['packages']))
                @foreach($compact['packages'] as $vo)
            <tr>
                <td>
                    <input type="checkbox" value="{{ $vo -> id }}" class="package_ids" data="{{ $vo -> id }}" package_num = "{{ $vo -> package_num }}" />
                </td>
                <td>{{ $vo -> package_num }}</td>
                <td>{{ $vo -> name }}</td>
                <td>{{ $vo -> tel }}</td>
                <td>{{ $vo -> address }}</td>
                <td>{{ $vo -> weight }}</td>
                <td>{{ $vo -> wuliu_num }}</td>
                <td>{{ $vo -> price }}</td>
                @if($compact['username'] == 'admin')
                <td>{{ $vo -> area_price }}</td>
                <td>{{ $vo -> cost }}</td>
                @endif

                    <td>
                        @if(!$vo -> wuliu_num)
                        <a data="{{ $vo -> id }}" onclick="deletePackage({{ $vo -> id }})"  class="deleteData">删除</a>

                            <a  href="{{ admin_base_path('editPacketAddressPage').'/'.$vo -> id }}" >地址</a>
                            <a  class="shenbao" data="{{ $vo -> id }}"  onclick="editGoodsPar({{ $vo -> id }})"  style="cursor:pointer;" >申报</a>
                        @endif
                        <a  href="{{ admin_base_path('editPacketPage').'/'.$vo -> id }}" >改重</a>






                    </td>



            </tr>
                @endforeach
                    <tr>
                        <td colspan="
                        @if($compact['username'] == 'admin')
                                                        9
                        @else
                                                        7
                        @endif
                        ">合计</td>
                        <td>
                            订单：{{$compact['orderinfo']->price}}<br>
                            优惠：{{$compact['orderinfo']->minus_price}}<br>
                            实付：{{$compact['orderinfo']->pay_price}}<br>
                            税金：0

                        </td>

                            <td></td>

                    </tr>
            @endif

            <tr>
                <td>备注</td>
                <td colspan="
                @if($compact['username'] == 'admin')
                        10
                        @else
                        8
                @endif
                ">
                    {{ $compact['orderinfo'] -> remark }}
                </td>





            </tr>



        </table>
    </div>
</div>
<script>
    function editGoodsPar(package_id){

        layer.open({
            type: 2,
            title: '申报物品修改',
            shadeClose: true,
            shade: 0.8,
            area: ['50%', '50%'],
            content: '{{ admin_base_path('editGoodsParatemer').'/' }}' + package_id
        });


        return false;
        var url = '{{ admin_base_path('editGoodsParatemer') }}';
        $.ajax({
            type: 'POST',
            url: url,
            data: {package_id:package_id},

            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            success: function(data){
                console.log(data);
                layer.open({
                    type: 2,
                    title: '线下支付确认',
                    shadeClose: true,
                    shade: 0.8,
                    area: ['50%', '70%'],
                    content: '{{ admin_url('confirmPayPage').'/' }}' + order_id
                });
            },
            error: function(xhr, type){
                //alert('Ajax error!')
            }
        });
        return false;
    }
    function deleteOrder(order_id){
        if(confirm('确认删除么？')){
            var url = '{{ admin_base_path('cancelOrder') }}';
            var token = $('meta[name="_token"]').attr('content');
            $.ajax({
                type: 'POST',
                url: url,
                data: {id:order_id},

                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success: function(data){
                    if(data == 'success'){
                        alert('已删除')
                        location.reload();
                    }
                    if(data == 'nodel'){
                        alert('订单下有已发货的包裹，请先执行取消单号操作');
                    }
                },
                error: function(xhr, type){
                    //alert('Ajax error!')
                }
            });
        }
    }


    //支付确认
    function confirmPay(order_id){
        layer.open({
            type: 2,
            title: '线下支付确认',
            shadeClose: true,
            shade: 0.8,
            area: ['50%', '70%'],
            content: '{{ admin_url('confirmPayPage').'/' }}' + order_id
        });
    }


    //删除包裹
    function deletePackage(id){
        if(confirm('确定要删除么')){
            var url = '{{ admin_base_path('deletePackage') }}';
            var token = $('meta[name="_token"]').attr('content');
            $.ajax({
                type: 'POST',
                url: url,
                data: {package_id:id},

                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success: function(data){
                    if(data == 'success'){
                        alert('已删除')
                        location.reload();
                    }
                    if(data == 'notdel'){
                        alert('已存在单号，不允许删除，请执行取消单号操作');
                    }
                    if(data == 'delOrder'){
                        alert('请执行删除订单操作');
                    }
                },
                error: function(xhr, type){
                    //alert('Ajax error!')
                }
            });
        }
    }
</script>