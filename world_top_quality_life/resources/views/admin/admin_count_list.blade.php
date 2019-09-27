<style>
    .table td{
        text-align: center;
    }
</style>
<div class="col-md-12" style="padding-left:9px;">
    <div class="nav-tabs-custom">
        <ul class="nav nav-tabs">

            <li  class="@if(!isset($_GET['type'])) active  @endif " ><a href="{{ admin_url('countList') }}" >日报</a></li>
            <li class="@if(isset($_GET['type']) && $_GET['type'] =='week') active  @endif "><a href="{{ admin_url('countList').'?type=week' }}" >周报</a></li>
            <li class="@if(isset($_GET['type']) && $_GET['type'] =='month') active  @endif "><a href="{{ admin_url('countList').'?type=month' }}" >月报</a></li>


            <li class="pull-right header"></li>
        </ul>
        <div class="tab-content">

            <div class="tab-pane @if(!isset($_GET['type'])) active  @endif " id="box1" >
                <div class="box-header">
                    <form method="get" action="{{ admin_url('countList') }}" id="myForm">
                        <h3 class="box-title"></h3>

                        <div class="pull-left" style="">
                            <input   class="form-control price_point pull-left" placeholder="请选择" id="year_select"  @if(isset($_GET['layDate']))  value="{{ $_GET['layDate'] }}" @endif  name="layDate"  AUTOCOMPLETE="off"  style="width:200px;"  />
                            <div class="input-group pull-left" style="margin-left:10px;">
                                <a class="btn btn-sm btn-primary grid-refresh " id="submitBtn"><i class="fa fa-search"></i>  搜索</a>
                            </div>
                        </div>

                    </form>
                </div>

                <div style="padding:2px;display:inline-block;">
                    @foreach($cache_arr as $key => $vo)
                        <table class="table" border="1" bordercolor="#a0c6e5" style="border-collapse:collapse;
                        @if($key%4==1)
                                width:27%;
                        @else
                                width:24%;
                        @endif
                                height:10%;
                                float:left;" >
                            <thead>
                            <tr>
                                @if($key%4==1)
                                    <td rowspan="2">区<br>域</td>
                                @endif
                                <td colspan="3">{{ $key }}</td>
                            </tr>
                            <tr>

                                <td>销售金额</td>
                                <td>成本金额</td>
                                <td>利润</td>
                            </tr>
                            </thead>
                            <tbody>

                            <?php $day_price = 0;?>
                            <?php $day_cost = 0;?>
                            <?php $day_profits = 0;?>
                            @foreach($vo as $k => $vol)
                                <tr>
                                    @if($key%4==1)
                                        <td>{{ $cache_name[$k] }}</td>
                                    @endif
                                    <td>{{ $vol['price'] }}</td>
                                    <td>{{ $vol['cost'] }}</td>
                                    <td>{{ $vol['profits'] }}</td>
                                </tr>
                                <?php $day_price += $vol['price'];?>
                                <?php $day_cost += $vol['cost'];?>
                                <?php $day_profits += $vol['profits'];?>
                            @endforeach

                            <tr>
                                @if($key%4==1)
                                    <td>合计</td>
                                @endif
                                <td><?php echo $day_price;  ?></td>
                                <td><?php echo $day_cost;  ?></td>
                                <td><?php echo $day_profits;  ?></td>
                            </tr>

                            </tbody>
                        </table>

                    @endforeach
                </div>


            </div>



                    <div class="tab-pane @if(isset($_GET['type']) && $_GET['type'] == 'week') active  @endif ">
                        <div class="box-header" style="">
                            <form method="get" action="{{ admin_url('countList') }}" id="myForm2">
                                <h3 class="box-title"></h3>

                                <div class="pull-left" style="">
                                    <input   class="form-control price_point pull-left" placeholder="请选择年份" id="years_select"  @if(isset($year))  value="{{$year}}" @endif  name="year"  AUTOCOMPLETE="off"  style="width:200px;"  />
                                    <div class="input-group pull-left" style="margin-left:10px;">
                                    <select name="week">
                                        @for($i=1;$i<=51;$i++)
                                            <option @if(isset($week) && $week == $i) selected @endif>{{ $i }}</option>
                                        @endfor
                                    </select>
                                    </div>
                                    <input name="type" value="@if(isset($_GET['type'])){{ $_GET['type'] }}@endif" type="hidden" />
                                    <div class="input-group pull-left" style="margin-left:10px;">
                                        <a class="btn btn-sm btn-primary grid-refresh " id="submitBtn2"><i class="fa fa-search"></i>  搜索</a>
                                    </div>
                                </div>

                            </form>
                        </div>

                        <div style="padding:2px;display:inline-block;">
                            @foreach($cache_arr as $key => $vo)
                                <table class="table" border="1" bordercolor="#a0c6e5" style="border-collapse:collapse;
                                @if($key%4==1)
                                        width:27%;
                                @else
                                        width:24%;
                                @endif
                                        height:10%;
                                        float:left;" >
                                    <thead>
                                    <tr>
                                        @if(($key-$start_date)/86400%4==0)
                                            <td rowspan="2">区<br>域</td>
                                        @endif
                                        <td colspan="3">{{ date('Y-m-d',$key) }}</td>
                                    </tr>
                                    <tr>

                                        <td>销售金额</td>
                                        <td>成本金额</td>
                                        <td>利润</td>
                                    </tr>
                                    </thead>
                                    <tbody>

                                    <?php $week_price = 0;?>
                                    <?php $week_cost = 0;?>
                                    <?php $week_profits = 0;?>
                                    @foreach($vo as $k => $vol)
                                        <tr>
                                            @if(($key-$start_date)/86400%4==0)
                                                <td>{{ $cache_name[$k] }}</td>
                                            @endif
                                            <td>{{ $vol['price'] }}</td>
                                            <td>{{ $vol['cost'] }}</td>
                                            <td>{{ $vol['profits'] }}</td>
                                                <?php $week_price += $vol['price'];?>
                                                <?php $week_cost += $vol['cost'];?>
                                                <?php $week_profits += $vol['profits'];?>
                                        </tr>
                                    @endforeach

                                    <tr>
                                        @if(($key-$start_date)/86400%4==0)
                                            <td>合计</td>
                                        @endif
                                        <td><?php echo $week_price;  ?></td>
                                        <td><?php echo $week_cost;  ?></td>
                                        <td><?php echo $week_profits;  ?></td>
                                    </tr>

                                    </tbody>
                                </table>

                            @endforeach
                        </div>


                    </div>





            <div class="tab-pane @if(isset($_GET['type']) && $_GET['type'] == 'month') active  @endif " id="box2">
                <div class="box-header" style="display:none;">
                    <form method="get" action="{{ admin_url('countList') }}" id="myForm">
                        <h3 class="box-title"></h3>

                        <div class="pull-left" style="">
                            <input name="type" value="@if(isset($_GET['type'])) {{ $_GET['type'] }} @endif" type="hidden" />
                            <div class="input-group pull-left" style="margin-left:10px;">
                                <a class="btn btn-sm btn-primary grid-refresh " id="submitBtn"><i class="fa fa-search"></i>  搜索</a>
                            </div>
                        </div>

                    </form>
                </div>

                <div style="padding:2px;display:inline-block;">
                @foreach($cache_arr as $key => $vo)
                <table class="table" border="1" bordercolor="#a0c6e5" style="border-collapse:collapse;
                @if($key%4==1)
                        width:27%;
                        @else
                        width:24%;
                @endif
                height:10%;
                float:left;" >
                    <thead>
                    <tr>
                        @if($key%4==1)
                        <td rowspan="2">区<br>域</td>
                        @endif
                        <td colspan="3">{{ $key }}</td>
                    </tr>
                    <tr>

                        <td>销售金额</td>
                        <td>成本金额</td>
                        <td>利润</td>
                    </tr>
                    </thead>
                    <tbody>

                    <?php $month_price = 0;?>
                    <?php $month_cost = 0;?>
                    <?php $month_profits = 0;?>

                    @foreach($vo as $k => $vol)
                        <tr>
                            @if($key%4==1)
                            <td>{{ $cache_name[$k] }}</td>
                            @endif
                            <td>{{ $vol['price'] }}</td>
                            <td>{{ $vol['cost'] }}</td>
                            <td>{{ $vol['profits'] }}</td>
                        </tr>
                        <?php $month_price += $vol['price'];?>
                        <?php $month_cost += $vol['cost'];?>
                        <?php $month_profits += $vol['profits'];?>
                    @endforeach

                    <tr>
                        @if($key%4==1)
                            <td>合计</td>
                        @endif
                        <td><?php echo $month_price;  ?></td>
                        <td><?php echo $month_cost;  ?></td>
                        <td><?php echo $month_profits;  ?></td>
                    </tr>

                    </tbody>
                </table>

                @endforeach
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
            type:'month',
            format:'yyyyMM'
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