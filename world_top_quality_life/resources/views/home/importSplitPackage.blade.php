<meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
<script src="{{asset('js/jquery-1.11.1.min.js')}}"></script>
<link rel="stylesheet" href="{{ admin_asset("/vendor/laravel-admin/AdminLTE/bootstrap/css/bootstrap.min.css") }}">
<!-- Font Awesome -->
<link rel="stylesheet" href="{{ admin_asset("/vendor/laravel-admin/font-awesome/css/font-awesome.min.css") }}">
<link rel="stylesheet" href="{{ admin_asset("/vendor/laravel-admin/laravel-admin/laravel-admin.css") }}">
<link rel="stylesheet" href="{{ admin_asset("/vendor/laravel-admin/nprogress/nprogress.css") }}">
<link rel="stylesheet" href="{{ admin_asset("/vendor/laravel-admin/sweetalert/dist/sweetalert.css") }}">
<link rel="stylesheet" href="{{ admin_asset("/vendor/laravel-admin/nestable/nestable.css") }}">
<link rel="stylesheet" href="{{ admin_asset("/vendor/laravel-admin/toastr/build/toastr.min.css") }}">
<link rel="stylesheet" href="{{ admin_asset("/vendor/laravel-admin/bootstrap3-editable/css/bootstrap-editable.css") }}">
<link rel="stylesheet" href="{{ admin_asset("/vendor/laravel-admin/google-fonts/fonts.css") }}">
<link rel="stylesheet" href="{{ admin_asset("/vendor/laravel-admin/AdminLTE/dist/css/AdminLTE.min.css") }}">

<!-- REQUIRED JS SCRIPTS -->
<script src="{{ admin_asset ("/vendor/laravel-admin/AdminLTE/plugins/jQuery/jQuery-2.1.4.min.js") }}"></script>
<script src="{{ admin_asset ("/vendor/laravel-admin/AdminLTE/bootstrap/js/bootstrap.min.js") }}"></script>
<script src="{{ admin_asset ("/vendor/laravel-admin/AdminLTE/plugins/slimScroll/jquery.slimscroll.min.js") }}"></script>
<script src="{{ admin_asset ("/vendor/laravel-admin/AdminLTE/dist/js/app.min.js") }}"></script>
<script src="{{ admin_asset ("/vendor/laravel-admin/jquery-pjax/jquery.pjax.js") }}"></script>
<script src="{{ admin_asset ("/vendor/laravel-admin/nprogress/nprogress.js") }}"></script>
<script src="{{ admin_asset ("/vendor/laravel-admin/bootstrap-fileinput/js/fileinput.min.js") }}"></script>
<script src="{{ asset('js/layer/2.2/layer.js') }}"></script>
<title>导入拆分包裹重量</title>

<script>
    $(function(){
        @if(session('msg'))
            alert('{{  session('msg') }}');
        @endif
    })
</script>
<div class="content-wrapper" id="pjax-container" style="min-height: 1834px;margin-left:0;">
    <section class="content-header"><h1>
            导入拆分包裹重量
        </h1>

        <!-- breadcrumb start -->
        <!-- breadcrumb end -->

    </section><section class="content"><div class="row"><div class="col-md-12">
                <div class="box box-info">

                    <!-- form start -->
                    <form action="{{ url('importSplitPackageRes') }}" method="post" accept-charset="UTF-8" class="form-horizontal" enctype="multipart/form-data" >
                        {{ csrf_field() }}
                        <div class="box-body">

                            <div class="fields-group">

                                <div class="form-group  ">



                                    <div class="col-sm-12">
                                        <div class="col-sm-12">
                                            <input type="file" class="file_column" name="file_name" id="1544416837439"></div>
                                        </div>


                                    </div>
                                </div>


                            </div>

                        </div>
                        <!-- /.box-body -->

                        <div class="box-footer">

                            <div class="col-md-2">
                            </div>

                            <div class="col-md-8">

                                <div class="btn-group pull-right">
                                    <button type="submit" class="btn btn-primary">提交</button>
                                </div>

                                <!--
                    <label class="pull-right" style="margin: 5px 10px 0 0;">
                        <input type="checkbox" class="after-submit" name="after-save" value="1"> admin.continue_editing
                    </label>
                    -->

                                <!--
                    <label class="pull-right" style="margin: 5px 10px 0 0;">
                        <input type="checkbox" class="after-submit" name="after-save" value="2"> 查看
                    </label>
                    -->


                                <div class="btn-group pull-left">
                                    <button type="reset" class="btn btn-warning" onclick="history.go(-1)">返回</button>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" name="_method" value="PUT" class="_method"><input type="hidden" name="_previous_" value="http://package.com/admin/users" class="_previous_"><!-- /.box-footer -->
                    </form>
                </div>

            </div></div>

    </section><script data-exec-on-popstate="">

        $(function () {

            $('.5c0dee4555281-delete').unbind('click').click(function() {

                swal({
                    title: "确认删除?",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#DD6B55",
                    confirmButtonText: "确认",
                    showLoaderOnConfirm: true,
                    cancelButtonText: "取消",
                    preConfirm: function() {
                        return new Promise(function(resolve) {
                            $.ajax({
                                method: 'post',
                                url: 'http://package.com/admin/680',
                                data: {
                                    _method:'delete',
                                    _token:LA.token,
                                },
                                success: function (data) {
                                    $.pjax({container:'#pjax-container', url: 'http://package.com/admin' });

                                    resolve(data);
                                }
                            });
                        });
                    }
                }).then(function(result) {
                    var data = result.value;
                    if (typeof data === 'object') {
                        if (data.status) {
                            swal(data.message, '', 'success');
                        } else {
                            swal(data.message, '', 'error');
                        }
                    }
                });
            });

            /*
            $("input.file_column").fileinput({"overwriteInitial":true,"initialPreviewAsData":true,"browseLabel":"\u6d4f\u89c8","showRemove":false,"showUpload":false,"deleteExtraData":{"file_column":"_file_del_","_file_del_":"","_token":"8AEG1lg6pmvzA1WDbSVuNaXxWhUI9PWAPtiVKcES","_method":"PUT"},"deleteUrl":"http:\/\/package.com\/admin\/680"});
*/
        });
    </script></div>
