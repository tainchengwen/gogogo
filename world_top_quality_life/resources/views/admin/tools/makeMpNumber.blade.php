<a class="btn btn-sm btn-primary " id="makePrint"><i class="fa fa-print"></i>打印A4纸</a>
<a class="btn btn-sm btn-primary " id="makePrintPdf"><i class="fa fa-print"></i>直接打印</a>
<script>
    $(function(){
        $('#makePrint').click(function(){
            var number = prompt('您想打印多少张');
            if(parseInt(number) > 0){
                number = parseInt(number);
                $.ajax({
                    method: 'post',
                    url: '/admin/makeTempNumberPdf',
                    dataType:'json',
                    data: {
                        number:number
                    },
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function (data) {
                        if(data.code == 'error'){
                            $.pjax.reload('#pjax-container');
                            toastr.error('操作失败');

                        }else if(data.code == 'success'){
                            window.open('/admin/makePdf?type=1&ids='+ data.msg);return false;
                        }

                    }
                });


            }
        });

        $('#makePrintPdf').click(function(){
            var number = prompt('您想打印多少张');
            if(parseInt(number) > 0){
                number = parseInt(number);
                $.ajax({
                    method: 'post',
                    url: '/admin/makeTempNumberPdf',
                    dataType:'json',
                    data: {
                        number:number
                    },
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function (data) {
                        if(data.code == 'error'){
                            $.pjax.reload('#pjax-container');
                            toastr.error('操作失败');

                        }else if(data.code == 'success'){
                            window.open('/admin/makePdf?type=2&ids='+ data.msg);return false;
                        }

                    }
                });


            }
        })
    })
</script>