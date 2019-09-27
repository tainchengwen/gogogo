<html>
<head>
    @extends('layouts.admin_header')
    <style>
        .col-lg-6{
            width:50%;
            float:left;
        }
        .col-sm-8{
            width:66%;
            float:left;
        }
        .col-sm-2{
            width:16.6%;
            float: left;
            padding-top: 7px;
        }
        .col-sm-1{
            float:left;
            width:11%;
            padding-top: 7px;
        }
        .col-sm-4{
            float:left;
            width:31%;
        }
    </style>
    <meta name="_token" content="{{ csrf_token() }}"/>
    <script src="{{asset('vendor/laravel-admin/AdminLTE/plugins/jQuery/jQuery-2.1.4.min.js')}}"></script>
    <script src="{{ asset('js/laydate/laydate.js') }}"></script>
    <script src="{{ asset('js/layer/2.2/layer.js') }}"></script>
</head>
<body>
<div class="content-wrapper" id="pjax-container" style="margin-left:0;" >
    <section class="content"><div class="row"><div class="col-md-12"><div class="box">

                    <!-- /.box-header -->
                    <div class="box-body" style="display: block;">
                        <form method="GET" action="" class="form-horizontal" accept-charset="UTF-8" >
                            <div class="box-body fields-group">

                                <div class="form-group  ">

                                    <label for="date" class="col-sm-1 control-label">订单号</label>

                                    <div class="col-sm-4">


                                        <div class="box box-solid box-default no-margin">
                                            <!-- /.box-header -->
                                            <div class="box-body">
                                                {{ $orderinfo -> order_num }}
                                            </div>
                                            <!-- /.box-body -->
                                        </div>


                                    </div>
                                    <label for="date" class="col-sm-1 control-label" style="width:13%;">收款金额</label>

                                    <div class="col-sm-4">

                                        <!--
                                        <div class="input-group">

                                            <span class="input-group-addon"><i class="fa fa-calendar fa-fw"></i></span>

                                            <input style="width: 110px" type="text" id="date" name="date" value="" class="form-control date" placeholder="输入 Date">
                                        </div>
                                        -->
                                        <div class="box box-solid box-default no-margin">
                                            <!-- /.box-header -->
                                            <div class="box-body">
                                                {{ $orderinfo -> pay_price }}
                                            </div>
                                            <!-- /.box-body -->
                                        </div>


                                    </div>
                                </div>
                                请选择支付方式：
                                <div class="form-group  ">

                                    <label  class="col-sm-1 control-label">微信</label>

                                    <div class="col-sm-1">
                                        <div class="input-group" style="padding-top:7px;">
                                            <input type="radio" class="zhifufangshi" name="zhifufangshi" checked value="1" />
                                        </div>
                                    </div>

                                    <label   class="col-sm-1 control-label">支付宝</label>

                                    <div class="col-sm-1">
                                        <div class="input-group" style="padding-top:7px;">
                                            <input type="radio" class="zhifufangshi" name="zhifufangshi" value="2" />
                                        </div>
                                    </div>

                                    <label   class="col-sm-2 control-label">虚拟支付</label>

                                    <div class="col-sm-2">
                                        <div class="input-group" style="padding-top:7px;">
                                            <input type="radio" class="zhifufangshi" name="zhifufangshi" value="9" />
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group  ">

                                    <label for="datetime" class="col-sm-2 control-label">支付流水号</label>

                                    <div class="col-sm-8">


                                        <div class="input-group" style="width: 100%" >
                                            <input style="width: 100%" type="text" id="datetime" name="liushuihao" value="" class="form-control " placeholder="输入 支付流水号">
                                        </div>


                                    </div>
                                </div>
                                <div class="form-group  ">

                                    <label for="datetime" class="col-sm-2 control-label">来自用户</label>

                                    <div class="col-sm-8">


                                        <div class="input-group" style="width: 100%" >
                                            <input style="width: 100%" type="text" id="datetime" disabled value="{{ $userinfo -> nickname }}" class="form-control ">
                                        </div>


                                    </div>
                                </div>

                                <hr>


                                <div class="form-group  ">

                                    <label for="time_start" class="col-sm-2 control-label">支付时间</label>

                                    <div class="col-sm-8">


                                        <div class="row" style="width: 370px">
                                            <div class="col-lg-6">
                                                <div class="input-group">
                                                    <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                                                    <input type="text"  id="payTime" name="paytime" value=""  autocomplete="off" class="form-control time_start" style="width: 150px">
                                                </div>
                                            </div>


                                        </div>


                                    </div>
                                </div>




                            </div>

                            <!-- /.box-body -->
                            <div class="box-footer">
                                <div class="col-md-2"></div>

                                <div class="col-md-8">
                                    <div class="btn-group pull-left">
                                        <button type="reset" class="btn btn-warning pull-right">撤销</button>
                                    </div>
                                    <div class="btn-group pull-right">
                                        <button type="button" class="btn btn-info pull-right" id="tijiao">提交</button>
                                    </div>

                                </div>

                            </div>
                        </form>

                    </div>
                    <!-- /.box-body -->
                </div></div></div>

    </section></div>

<script>
    $(function(){
        $('#tijiao').click(function(){
            var zhifufangshi = $('input[name=zhifufangshi]:checked').val();
            var liushuihao = $.trim($('input[name=liushuihao]').val());
            var paytime = $('input[name=paytime]').val();
            var orderid = '{{ $orderinfo -> id }}';
            if(!paytime){
                layer.msg('请填写支付时间');return false;
            }
            if(!liushuihao){
                layer.msg('请填写支付流水号');return false;
            }

            if(confirm('确认提交么？')){
                var url = '{{ admin_base_path('submitPayOrder') }}';

                $.ajax({
                    type: 'POST',
                    url: url,
                    data: {orderid:orderid,pay_number:liushuihao,paytime:paytime,paytype:zhifufangshi},

                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(data){
                        if(data == 'success'){
                            alert('已提交')
                            parent.location.reload();
                        }
                        if(data == 'error'){
                            alert('出现错误')
                            return false;
                            //location.reload();
                        }
                    },
                    error: function(xhr, type){
                        alert('Ajax error!')
                    }
                });
            }



        })
    });


    $(function(){
        laydate.render({
            elem: '#payTime',
            type:'datetime',
            //指定元素
        });


    })


</script>
</body>
</html>