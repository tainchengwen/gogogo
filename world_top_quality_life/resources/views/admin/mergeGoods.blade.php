<!--
<div class="form-group ">
    <label class="col-sm-2 control-label">重量</label>
    <div class="col-sm-8">
        <input type="text" id="weightNum" />


    </div>
</div>
-->

<style>
    .product_no{
        width:150px;
    }
    .price_a,.price_b,.price_c,.price_d,.numbers{
        width:80px;
    }
</style>
<table class="table" id="myTable">
    <thead>
    <tr>
        <th>操作</th>
        <th>序号</th>
        <th>商品编号</th>
        <th>S</th>
        <th>A</th>
        <th>B</th>
        <th>C</th>
        <th>D</th>
        <th>数量</th>
    </tr>
    </thead>
    <tbody>
    <input type="hidden" name="url_type"  id="url_type"  value="{{ $compact['url_type'] }}" />
    @if($compact['merge_goods_detail'])

        @foreach($compact['merge_goods_detail'] as $k => $vo)
            <tr>
                <td ><button class="btn btn-info" type="button">删除</button></td>
                <td >{{ $k + 1 }}</td>
                <td ><input type="text" class="product_no" name="product_no[]"  required value="{{ $vo -> product_no }}" /></td>
                <td ><input type="text" class="price_s" name="price_s[]"  required value="{{ $vo -> price_s }}" /></td>
                <td ><input type="text" class="price_a" name="price_a[]"  required value="{{ $vo -> price_a }}" /></td>
                <td ><input type="text" class="price_b" name="price_b[]"  required value="{{ $vo -> price_b }}" /></td>
                <td ><input type="text" class="price_c" name="price_c[]"  required value="{{ $vo -> price_c }}" /></td>
                <td ><input type="text" class="price_d" name="price_d[]"  required value="{{ $vo -> price_d }}" /></td>
                <td ><input type="text" class="numbers" name="numbers[]"  required value="{{ $vo -> number }}" /></td>
            </tr>
            @endforeach

        @else
        <tr>
            <td ></td>
            <td >1</td>
            <td ><input type="text" class="product_no" name="product_no[]"  required /></td>
            <td ><input type="text" class="price_s" name="price_s[]"  required /></td>
            <td ><input type="text" class="price_a" name="price_a[]"  required /></td>
            <td ><input type="text" class="price_b" name="price_b[]"  required /></td>
            <td ><input type="text" class="price_c" name="price_c[]"  required /></td>
            <td ><input type="text" class="price_d" name="price_d[]"  required /></td>
            <td ><input type="text" class="numbers" name="numbers[]"  required /></td>
        </tr>
        @endif






    <tr id="after_tr">
        <!--
        <td colspan="4">合计：</td>
        <td >
            <!--
            <input type="text" id="sum_price_num" disabled value="0"/>
            -->
        <!--
            <b id="sum_price_num" style="color:red;font-size:18px;"></b>
        </td>
        -->
    </tr>
    <tr>
        <td colspan="8">
            商品编号：<input type="text" name="product_all_no"  style="width:150px;"  @if($compact['merge_goods_info']) value="{{ $compact['merge_goods_info'] -> product_no }}"  @endif  required />
            <br>
            商品名称：<input type="text" name="product_name"  style="width:150px;"  @if($compact['merge_goods_info']) value="{{ $compact['merge_goods_info'] -> product_name }}"  @endif  required />
            <br>
            商品重量：<input type="text" name="weight"  style="width:150px;"  @if($compact['merge_goods_info']) value="{{ $compact['merge_goods_info'] -> weight }}"  @endif  required />
            <br>
            是否允许单卖：
            <select name="can_on_sale">
                <option value="0" @if(isset($compact['merge_goods_info'] -> can_on_sale) && $compact['merge_goods_info'] -> can_on_sale == 0) selected @endif >允许</option>
                <option value="1" @if(isset($compact['merge_goods_info'] -> can_on_sale) && $compact['merge_goods_info'] -> can_on_sale == 1) selected @endif >不允许</option>
            </select>
            <br>


            商品图片：<input type="file" name="image" id="image"  />
            @if(isset($compact['merge_goods_info'] -> image))
            <img src="{{ asset('uploads/'.$compact['merge_goods_info'] -> image) }}"  style="width:150px;height:150px;"/>
            @endif
        </td>
    </tr>

    <input type="hidden" name="merge_goods_id" value="@if($compact['merge_goods_info']){{ $compact['merge_goods_info'] -> id }}@else{{ 0 }}@endif" />

    <input type="hidden" id="number_tr" value="{{ $compact['tr_num'] }}" />


    </tbody>
</table>


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



    })


    $('#addTr').click(function(){

        //计数器+1
        var num = parseInt($('#number_tr').val());
        $('#number_tr').val(num+1);


        orderTr(num);

        var num_this = num + 1;
        var html = '<tr>';
        html+= '<td>';
        html += '<button class="btn btn-info" type="button">删除</button>';
        html+= '</td>';

         html += '<td>';
        html += num_this;
         html += '</td>';
        html += '<td>';
        html += '<input type="text" class="product_no" name="product_no[]"  />';
        html += '</td>';


        html += '<td>';
        html += '<input type="text" class="price_s" name="price_s[]" />';
        html += '</td>';

        html += '<td>';
        html += '<input type="text" class="price_a" name="price_a[]" />';
        html += '</td>';

        html += '<td>';
        html += '<input type="text" class="price_b" name="price_b[]" />';
        html += '</td>';

        html += '<td>';
        html += '<input type="text" class="price_c" name="price_c[]" />';
        html += '</td>';

        html += '<td>';
        html += '<input type="text" class="price_d" name="price_d[]" />';
        html += '</td>';


        html += '<td>';
        html += '<input type="text" class="numbers" name="numbers[]" />';
        html += '</td>';
         html += '</tr>';
        $('#after_tr').before(html);
    });


    //算总价 单价*数量
    function sumTr(weight,price,num){
        return (price).toFixed(2);
        //return (weight*price*num).toFixed(2);
    }

    //重量变化
    $('#myTable').on('input','.weight',function(){
        var self = $(this);
        //重量
        var weight = self.val();
        //2KG：150元，3KG：195元，4KG：250元：5KG：300元
        //找到tr
        var tr = self.closest("tr");
        getPriceByWeight(weight,tr);
    });




    //删除按钮
    $('#myTable').on('click','.btn',function(){
        var self = $(this);
        self.parent().parent().remove();
        //删除此行 然后 重新排序 重新算数
        //计数器-1
        var num = parseInt($('#number_tr').val());
        $('#number_tr').val(num-1);
        orderTr(num-1);
        //sumPriceNumber();


    });


    //计算总数量
    function sumPriceNumber(){
        //将所有的总价加起来
        var length = $('.price_num').length;
        var sum_price = 0;
        for(var i=0;i<length;i++){
            sum_price += parseFloat($('.price_num').eq(i).text());
        }
        $('#sum_price_num').text(sum_price.toFixed(2));


    }

    //重新排序
    function orderTr(num){
        for(var i = 1 ;i<num;i++){
            $('#myTable tbody tr:eq('+i+') td:eq(1) ').text(i+1);
            //重量获取价格
        }
    }


    function getPriceByWeight(weight,tr){
        var userid = $('input[name=userid]').val();
        //通过接口请求获取价格

        var url = '{{ admin_url('getPriceByUserIdWeight') }}';
        if(weight){
            $.ajax({
                type: 'POST',
                url: url,
                data: {userid:userid,weight:weight},

                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success: function(data){
                    var price
                    price = parseFloat(data);
                    tr.find('.price').val(price);
                    var price_num = sumTr(weight,price,1);
                    tr.find('.price_num').text(price_num);
                    sumPriceNumber();

                },
                error: function(xhr, type){
                    alert('Ajax error!')
                }
            });

        }
    }


    $(function(){
        var mark = false;
        var is_first = false;
        $('form').submit(function(e){
            if(is_first){
                return false;
            }
            if(mark){
                is_first = true;
                return true;
            }
            //验证商品编号是否正确
            var url = '{{ admin_url('mergeGoodsAjax') }}';

            var product_no_str = [];
            var product_no;
            for(var i=0;i<$('.product_no').length;i++){
                product_no = $.trim($('.product_no').val());
                if(product_no_str.indexOf() > 0){
                    alert('商品编码重复');return false;
                }

                product_no_str.push(product_no);
            }

            var url_type = $('#url_type').val();

            var issubmit = false;

            $.ajax({
                type: 'POST',
                url: url,
                data: {product_no_str:product_no_str,url_type:url_type},
                dataType:"json",
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success: function(data){

                    if(data.code == 'no_up'){
                        alert(data.result+'没有在商城上架');
                        return false;
                    }
                    if(data.code == 'no_goods'){
                        alert(data.result+'没有此商品');
                        return false;
                    }
                    mark = true;
                    $('form').submit();
                    return true;



                },
                error: function(xhr, type){
                    return false;
                }
            });

            return false;

        });





    })

    function issubmit(issubmit){
        return issubmit;
    }










</script>
