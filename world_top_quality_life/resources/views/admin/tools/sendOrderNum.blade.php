<a class="btn btn-sm btn-primary " id="makeOrderNum"> 创建交货单</a>
<script>
    $('#makeOrderNum').click(function(){
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
        var order_num=prompt("请输入发货单号"); /*在页面上再一次弹出提示对话框，用户输入的信息赋给变量age*/
        if(!order_num){
            return false;
        }
        if(confirm('确认创建发货么')){
            layer.load(1);
            var url = '{{ admin_base_path('makeSendOrderRes') }}';
            $.ajax({
                type: 'POST',
                url: url,
                data:{'check_arr':check_arr,order_num:order_num},

                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success: function(data){
                    if(data == 'isset'){
                        alert('发货单号重复');
                        location.reload();
                    }else{
                        alert('成功');
                        location.reload();
                    }

                },
                error: function(xhr, type){
                    //alert('Ajax error!')
                }
            });
        }


    })
</script>