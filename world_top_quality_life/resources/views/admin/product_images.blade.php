<div class="col-md-12" style="padding-left:9px;">
    @foreach($images as $vo)
        <div class="col-md-4">
            <div style="height:200px;">
                <a href="{{ $vo -> url }}" target="_blank"><img  src="{{ $vo -> url }}" style="width: 100%;height: 100%"   /></a>
            </div>
            <div style="height:50px;">
                <a class="deleteImg" data="{{ $vo -> id }}">删除</a>
            </div>

        </div>
    @endforeach
</div>

<script>
    $(function(){
        $('.deleteImg').click(function(){
            var data = $(this).attr('data');
            if(confirm('确定删除么')){
                window.open('{{ admin_url('deleteProductImage').'?id=' }}' + data);
            }
        });
    })
</script>
