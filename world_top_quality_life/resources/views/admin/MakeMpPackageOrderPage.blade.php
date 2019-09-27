
<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="box">

                <!-- /.box-header -->
                <div class="box-body" style="display: block;">
                    <form method="GET" action="" class="form-horizontal" accept-charset="UTF-8" >
                        <div class="box-body fields-group">

                            <table class="table" id="myTable">
                                <thead>
                                <tr>
                                    <th>序号</th>
                                    <th>包裹编号</th>
                                    <th>备注</th>
                                    <th>重量<input type="number" id="temp_weight" /></th>

                                </tr>
                                </thead>
                                <tbody>
                                @if(isset($data))
                                    @foreach($data as $k => $vo)
                                        <tr>
                                            <td>{{ $k + 1}}</td>
                                            <td>{{ $vo ->  package_num}}</td>
                                            <td>{{ $vo ->  remark}}</td>
                                            <td><input type="number" data="{{ $vo -> id }}"  class="weight_input" value="{{ $vo -> weight }}" /></td>
                                        </tr>
                                    @endforeach
                                @endif

                                </tbody>
                            </table>

                        </div>

                        <!-- /.box-body -->
                        <div class="box-footer">
                            <div class="col-md-2"></div>

                            <div class="col-md-8">

                                <div class="btn-group pull-right">
                                    <button type="button" class="btn btn-info pull-right tijiao_nopay" data="1">提交不支付</button>
                                </div>

                                <div class="btn-group pull-left">
                                    <button type="button" class="btn btn-info pull-left tijiao_pay" data="pay" >提交并支付</button>
                                </div>

                            </div>

                        </div>
                    </form>

                </div>
                <!-- /.box-body -->
            </div>
        </div>
    </div>

</section>
<script>
    function randomNum(minNum,maxNum){
        switch(arguments.length){
            case 1:
                return parseInt(Math.random()*minNum+1);
                break;
            case 2:
                return parseInt(Math.random()*(maxNum-minNum+1)+minNum);
                break;
            default:
                return 0;
                break;
        }
    }
    $(function(){
        $('.tijiao_nopay').click(function(){
            if($(this).attr('data') == 'pay'){
                var pay = 1;
            }else{
                var pay = 0;
            }
            var weight_num = parseInt($('.weight_input').length);
            if(!weight_num){
                alert('重量必填');return false;
            }

            var names = [];
            var weights = [];
            for(var i=0;i<weight_num;i++){
                names.push($('.weight_input').eq(i).attr('data'));
                weights.push($('.weight_input').eq(i).val());
                if(!$('.weight_input').eq(i).val()){
                    alert('重量必填');return false;
                }
            }

            var state = true;
            if(confirm('确认提交么？')){
                if(state){
                    state = false;
                }else{
                    return false;
                }
                var url = '{{ admin_base_path('submitMpOrder') }}';
                layer.load(1);
                $.ajax({
                    type: 'POST',
                    url: url,
                    data: {weights:weights.join(),names:names.join(),pay:pay},

                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(data){
                        alert('下单成功');
                        parent.location.reload();
                    },
                    error: function(xhr, type){
                        state = true;
                        alert('Ajax error!')
                    }
                });
            }



        });


        $('.tijiao_pay').click(function(){
            if($(this).attr('data') == 'pay'){
                var pay = 1;
            }else{
                var pay = 0;
            }
            var weight_num = parseInt($('.weight_input').length);
            if(!weight_num){
                alert('重量必填');return false;
            }

            var names = [];
            var weights = [];
            for(var i=0;i<weight_num;i++){
                names.push($('.weight_input').eq(i).attr('data'));
                weights.push($('.weight_input').eq(i).val());
                if(!$('.weight_input').eq(i).val()){
                    alert('重量必填');return false;
                }
            }

            var state = true;
            if(confirm('确认提交么？')){
                if(state){
                    state = false;
                }else{
                    return false;
                }
                var url = '{{ admin_base_path('submitMpOrder') }}';
                layer.load(1);
                $.ajax({
                    type: 'POST',
                    url: url,
                    data: {weights:weights.join(),names:names.join(),pay:pay},

                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(data){
                        alert('下单成功');
                        parent.location.reload();
                    },
                    error: function(xhr, type){
                        state = true;
                        alert('Ajax error!')
                    }
                });
            }



        });


        $('#temp_weight').on('input propertychange',function(){
            var length = $('.weight_input').length;
            var sum_price = 0;
            var weight;
            var weight_input = $.trim($('#temp_weight').val());
            if(weight_input){
                for(var i=0;i<length;i++){
                    var tr = $('tbody').eq(i).find('tr');
                    weight_input = parseFloat(weight_input) - 0.02;
                    var rand = parseInt(randomNum(0,15));
                    weight =parseFloat(weight_input) - rand/100;



                    $('.weight_input').eq(i).val(weight.toFixed(2));
                }
            }
        });
    });



</script>