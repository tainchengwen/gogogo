<a class="btn btn-sm btn-primary " id="superGoods" > 添加限时特价</a>
<script>
    $('#superGoods').click(function(){
        var length = $('.grid-row-checkbox').length;

        var check_arr =  [];


        /*
        for(var i = 0 ;i < length; i++){
            if($('.grid-row-checkbox').eq(i).is(':checked')){
                check_arr.push($('.grid-row-checkbox').eq(i).attr('data-id'));
            }
        }
        console.log(check_arr);
        if(!check_arr.length){
            alert('请选择');return false;
        }
        */


        location.href='{{ admin_url('superGoods/create') }}'+'?url_type='+'{{ $compact['url_type'] }}'








    })
</script>