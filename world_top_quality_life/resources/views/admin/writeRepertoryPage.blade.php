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
    @if($error == 1)
        <script>
            alert('不在同一个区域不能生成');
        </script>
        @else
    <section class="content"><div class="row"><div class="col-md-12">
                <div class="box">

                    <!-- /.box-header -->
                    <div class="box-body" style="display: block;">
                        <form method="GET" action="" class="form-horizontal" accept-charset="UTF-8" >
                            <input type="hidden" id="ids_str" value="{{ $_GET['ids_str'] }}" />

                                <div class="form-group  ">

                                    <label for="datetime" class="col-sm-2 control-label">物流公司</label>

                                    <div class="col-sm-8">


                                        <div class="input-group" style="width: 100%" >
                                            <select id="company" style="width:200px;">
                                                @foreach($company as $k => $vo)
                                                    <option value="{{ $k }}">{{ $vo }}</option>
                                                @endforeach
                                            </select>
                                        </div>


                                    </div>
                                </div>

                            @if(count($repertorys))
                            <div class="form-group  ">

                                <label for="datetime" class="col-sm-2 control-label">已存在物流单号</label>

                                <div class="col-sm-8">


                                    <div class="input-group" style="width: 100%" >
                                        <select id="repertorys" style="width:200px;">
                                            <option value="0">请选择</option>
                                            @foreach($repertorys as $vo)
                                                <option value="{{ $vo -> id }}">{{ $vo -> id.'---'.$vo -> numbers }}</option>
                                            @endforeach
                                        </select>
                                    </div>


                                </div>
                            </div>
                                @else
                                <input type="hidden" id="repertorys" value="0" />
                            @endif



                                <div class="form-group  ">

                                    <label for="datetime" class="col-sm-2 control-label">物流单号</label>

                                    <div class="col-sm-8">


                                        <div class="input-group" style="width: 100%" >
                                            <input style="width: 300px" type="text"  id="company_number"  class="form-control ">
                                        </div>


                                    </div>
                                </div>

                                <hr>


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

                    </div>
                    <!-- /.box-body -->
                </div></div>

    </section>
    @endif

</div>

<script>
    $(function(){
        $('#tijiao').click(function(){

            var ids_str = $('#ids_str').val();
            var company = $.trim($('#company').val());
            var company_number = $.trim($('#company_number').val());
            var repertorys = $('#repertorys').val();
            if(!company || !company_number){
                alert('所有必填');return false;
            }
            if(confirm('确认提交么？')){
                var url = '{{ admin_base_path('writeRepertoryRes') }}';

                $.ajax({
                    type: 'POST',
                    url: url,
                    data: {
                        ids_str:ids_str,
                        company:company,
                        company_number:company_number,
                        repertorys:repertorys
                    },
                    dataType:"json",
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(data){
                        if(data.code == 500){
                            alert(data.msg);return false;
                        }else{
                            alert('提交成功');
                            parent.location.reload();
                        }
                    },
                    error: function(xhr, type){
                        alert('Ajax error!')
                    }
                });
            }



        })
    });




</script>
</body>
</html>