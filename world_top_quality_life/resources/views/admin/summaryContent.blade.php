<style>
    .table td,.table th{
        text-align: center;
    }
</style>
<div class="col-md-12" style="padding-left:9px;">
    <div class="nav-tabs-custom">
        <!--
        <ul class="nav nav-tabs">

            <li  ><a href="{{ admin_url('countList') }}" >日报</a></li>
            <li  ><a href="{{ admin_url('countList') }}" >日报</a></li>
            <li  ><a href="{{ admin_url('countList') }}" >日报</a></li>

            <li class="pull-right header"></li>
        </ul>
        -->
        <div class="tab-content">

            <div class="tab-pane @if(!isset($_GET['type'])) active  @endif " id="box1" >
                <div class="box-header">
                    <form method="get" action="{{ admin_url('shipmentSummary') }}" id="myForm">
                        <h3 class="box-title"></h3>

                        <div class="pull-left" style="">
                            <input   class="form-control price_point pull-left" placeholder="请选择" id="year_select"    value="{{ $layDate }}"  name="layDate"  AUTOCOMPLETE="off"  style="width:200px;"  />
                            <div class="input-group pull-left" style="margin-left:10px;">
                                <a class="btn btn-sm btn-primary grid-refresh " id="submitBtn"><i class="fa fa-search"></i>  搜索</a>
                            </div>
                        </div>

                    </form>
                </div>

                <div style="padding:2px;display:inline-block;width:100%;">
                    <table class="table">
                        <thead>
                        <tr>
                            <th>单号</th>
                            <th>件数</th>
                            <th>贴单箱数</th>
                            <th>贴单单号</th>
                            <th>备注</th>
                            <th>贴单日期</th>
                        </tr>
                        </thead>
                        <tbody>
                        @if(count($list))
                            @php $count_packages = 0;$count_num = 0 @endphp
                            @foreach($list as $k => $vo)
                                <tr>
                                    <td>{{ $vo -> numbers }}</td>
                                    <td>{{ $vo -> num }}</td>
                                    <td>{{ $vo -> count_package }}</td>
                                    <td><a href="{{ admin_url('packageSearch').'?9d658889d5baf59705c3fe6ce0f6d400='.$vo -> numbers }}" target="_blank">查看详情</a></td>
                                    <td>{{ $vo -> remark }}</td>
                                    <td>{{ $vo -> tie_date }}</td>
                                </tr>
                                @php
                                    $count_packages += intval($vo -> count_package);
                                    $count_num += intval($vo -> num);
                                @endphp
                            @endforeach
                            <tr>
                                <td>合计</td>
                                <td>{{ $count_num }}</td>
                                <td>{{ $count_packages }}</td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                        @endif
                        </tbody>
                    </table>
                </div>
            </div>



        </div>
    </div>
</div>


<style>
    @if(!isset($_GET['type']))

     #laydate_table{
        display:none;
    }

    @else

     #laydate_table{
        display:none;
    }
    #laydate_MM{
        display:none;
    }

    @endif



</style>

<script>

    $(function(){
        laydate.render({
            elem: '#year_select',
            type:'date',
            format:'yyyy-MM-dd'
            //指定元素
        });



        laydate.render({
            elem: '#years_select',
            type:'year',
            format:'yyyy'
            //指定元素
        });

    })

</script>
<script>
    $('#submitBtn').click(function(){
        $('#myForm').submit();
    })
    $('#submitBtn2').click(function(){
        $('#myForm2').submit();
    })
</script>