
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
                                    <th>不需要付费请勾选</th>
                                    <th>重量<input type="number" id="temp_weight" /></th>

                                </tr>
                                </thead>
                                <tbody>
                                @if(isset($data))
                                    @foreach($data as $k => $vo)
                                        <tr>
                                            <td>{{ $k + 1}}</td>
                                            <td>{{ $vo ->  number}}</td>
                                            <td>{{ $vo ->  remark}}</td>
                                            <td class="check_input"><input type="checkbox" class="is_cut" value="{{ $vo -> id }}" /></td>
                                            <td><input type="number" data="{{ $vo -> id }}"  class="weight_input" /></td>
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


                                <div class="btn-group pull-left">
                                    <button type="button" class="btn btn-info pull-left tijiao" data="pay" >提交</button>
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
        $('.tijiao').click(function(){
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
            var check_ids = [];


            $.each($('.check_input input:checkbox:checked'),function(){
                check_ids.push($(this).val());
            });

            for(var i=0;i<weight_num;i++){
                names.push($('.weight_input').eq(i).attr('data'));
                weights.push($('.weight_input').eq(i).val());

                if(!$('.weight_input').eq(i).val()){
                    alert('重量必填');return false;
                }
            }

            if(confirm('确认提交么？')){
                var url = '{{ admin_base_path('submitWarningOrder') }}';

                $.ajax({
                    type: 'POST',
                    url: url,
                    data: {weights:weights,names:names,check_ids:check_ids},

                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(data){

                        alert('下单成功');
                        parent.location.reload();
                    },
                    error: function(xhr, type){
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