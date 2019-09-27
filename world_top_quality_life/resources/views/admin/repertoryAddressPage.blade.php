<style>
    .table td input{
        float:left;
    }
</style>
<input type="hidden" name="_token" value="{{ csrf_token() }}" />
<section class="content" style="background-color: #ffffff">

    <div class="row">
        <div class="col-md-12">
            <div class="btn-group pull-right" >
                <button type="submit" class="btn btn-info btn-sm" >提交</button>
            </div>
            <div class="btn-group pull-left" id="deleteData"  onclick="deleteData()">
                <button type="button" class="btn btn-warning" >删除</button>
            </div>
            <form>

            <table class="table" id="myTable">
                <thead>
                <tr>
                    @if(isset($compact['type']))
                        <th><input type="checkbox" id="checkAll" onclick="selectAll()" /></th>
                    @endif

                    <th>收件人</th>
                    <th>电话</th>
                    <th>地址</th>
                    <th>省</th>
                    <th>市</th>


                    @if(!isset($compact['type']))
                    <th>重量</th>
                       @endif
                </tr>
                </thead>
                <input type="hidden" name="repertory_id" value="{{ $compact['repertory_id'] }}" />

                @if(isset($compact['from_area']))
                    <input type="hidden" name="from_area" value="{{ $compact['from_area'] }}" />
                @endif

                <tbody>
                    @foreach($compact['address_use'] as $vo)
                        <tr>
                            @if(isset($compact['type']))
                                <td><input type="checkbox"  class="ids" data="{{ $vo -> id }}" /></td>
                            @endif

                            <td>{{ $vo -> name }}</td>
                            <td>{{ $vo -> tel }}</td>
                            <td>{{ $vo -> address }}</td>
                            <td>{{ $vo -> province }}</td>
                            <td>{{ $vo -> city }}</td>


                            @if(!isset($compact['type']))
                            <td><input type="number" value="@php
                                    $rand_f = rand(0,1);
                                     $rand = rand(0,15);
                                     if($rand_f){
                                        echo $compact['order_weight'] + $rand/100;
                                     }else{
                                        echo $compact['order_weight'] - $rand/100;
                                     }
                                @endphp"  class="weight" name="weights[]"/></td>
@endif

                            <input type="hidden" name="name[]" value="{{ $vo -> name }}" />
                            <input type="hidden" name="tel[]" value="{{ $vo -> tel }}" />
                            <input type="hidden" name="address[]" value="{{ $vo -> address }}" />
                            <input type="hidden" name="province[]" value="{{ $vo -> province }}" />
                            <input type="hidden" name="city[]" value="{{ $vo -> city }}" />

                        </tr>
                    @endforeach
                    @if(!isset($compact['type']))
                    <tr>
                        <td colspan="5">
                            合计
                        </td>
                        <td>
                            <a id="sum_weight"></a>
                        </td>
                    </tr>

                    <tr>
                        <td>
                            备注
                        </td>
                        <td  colspan="5" >
                            <input type="text" id="remark" name="remark" value="" class="form-control remark" placeholder="输入 备注">
                        </td>
                    </tr>

                    <tr>
                        <td colspan="6">
                            <div class="btn-group pull-right">
                                <button type="button" id="underOrder" class="btn btn-info pull-right" data-loading-text="<i class='fa fa-spinner fa-spin '></i> 保存">下单</button>
                            </div>
                        </td>
                    </tr>
                        @endif
                </tbody>
            </table>
            </form>
        </div>
    </div>



</section>
<script>
    $('#myTable').on('input','.weight',function(){
        var self = $(this);
        //重量
        var weight = self.val();

        var tr = self.closest("tr");

        sumPriceNumber();


    });
    sumPriceNumber();

    function sumPriceNumber(){
        //将所有的重量加起来
        var length = $('.weight').length;
        var sum_weight = 0;
        for(var i=0;i<length;i++){
            sum_weight += parseFloat($('.weight').eq(i).val());
        }
        $('#sum_weight').text(sum_weight.toFixed(2));


    }

    $('#underOrder').click(function () {
        //先检查下 所有的重量是否填了
        var length = $('.weight').length;
        for(var i=0;i<length;i++){
            if(!$('.weight').eq(i).val() || parseFloat($('.weight').eq(i).val()) <= 0  ){
                alert('重量为必填');return false;
            }
        }


        var url = '{{ admin_base_path('submitUnderOrderRes') }}';

        $.ajax({
            type: 'POST',
            url: url,
            data: $("form").serialize(),

            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            success: function(data){
                if(data == 'success'){
                    alert('下单成功');
                    location.href='{{ admin_base_path('order') }}';
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


    });


    $('.delete_data').click(function(){
        var address_id = $(this).attr('data');

    })



    function selectAll(){

        console.log($('#checkAll').is(':checked'));
        if($('#checkAll').is(':checked')){
            var length = $('.ids').length;
            for(var i = 0 ;i < length; i++){
                $('.ids').eq(i).prop("checked", true);
            }

        }else{
            var length = $('.ids').length;
            for(var i = 0 ;i < length; i++){
                $('.ids').eq(i).prop("checked", false);
            }
        }
    }


    function deleteData(){
        var length = $('.ids').length;
//分配的人

        var check_arr = [];
        for(var i = 0 ;i < length; i++){
            if($('.ids').eq(i).is(':checked')){
                check_arr.push($('.ids').eq(i).attr('data'));
            }
        }
        if(!check_arr.length){
            alert('请选择');return false;
        }
        if(confirm('确定删除么？')){
            var url = '{{ admin_base_path('deleteRepertoryAddress') }}';

            $.ajax({
                type: 'POST',
                url: url,
                data: {check_arr:check_arr},

                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success: function(data){
                    if(data == 'success'){
                        alert('删除成功');
                        location.reload();
                    }
                    if(data == 'error'){
                        alert('出现错误')
                        location.reload();
                    }
                },
                error: function(xhr, type){
                    alert('Ajax error!')
                }
            });

        }





    }

</script>
