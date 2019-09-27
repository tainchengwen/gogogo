
<div class="form-group ">
    <label class="col-sm-2 control-label">重量</label>
    <div class="col-sm-8">
        <input type="text" id="weightNum" />


    </div>
</div>
<table class="table" id="myTable">
    <thead>
    <tr>
        <th>操作</th>
        <th>包裹编号</th>
        <th>包裹重量</th>
        <th>单价</th>
        <th>金额</th>
    </tr>
    </thead>
    <tbody>
    <input type="hidden" name="userid" value="{{ $userid }}" />
    <tr>
        <td ></td>
        <td >1</td>
        <td ><input type="number" class="weight" name="weight[]" min="0" max="5" step="0.01" required /></td>
        <td ><input type="number"  class="price" disabled/></td>
        <td >
            <!--
            <input type="number"  class="price_num" value="0" disabled/>
            -->
            <b class="price_num" style="color:red;font-size:18px;">0</b>
        </td>
    </tr>
    <tr id="after_tr">
        <td colspan="4">合计：</td>
        <td >
            <!--
            <input type="number" id="sum_price_num" disabled value="0"/>
            -->
            <b id="sum_price_num" style="color:red;font-size:18px;"></b>
        </td>
    </tr>
    <input type="hidden" id="number_tr" value="1" />
    <tr>
        <td >备注：</td>
        <td colspan="4">
            <input type="text" name="remark" style="width:200px;" />（十个字以内）
        </td>
    </tr>

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
        $('#weightNum').blur(function(){
            var length = $('.weight').length;
            var sum_price = 0;
            var weight;
            var weight_input = $.trim($('#weightNum').val());
            if(weight_input){
                for(var i=0;i<length;i++){
                    var tr = $('tbody').eq(i).find('tr');
                    weight_input = parseFloat(weight_input) - 0.02;
                    //var pre = parseInt(randomNum(0,1));  //正负
                    var rand = parseInt(randomNum(0,15));

                    weight =parseFloat(weight_input) - rand/100;


                    $('.weight').eq(i).val(weight);
                    getPriceByWeight(weight,tr);
                }
            }

        });


        $('#weightNum').on('input propertychange',function(){
            var length = $('.weight').length;
            var sum_price = 0;
            var weight;
            var weight_input = $.trim($('#weightNum').val());
            if(weight_input){
                for(var i=0;i<length;i++){
                    var tr = $('tbody').eq(i).find('tr');
                    weight_input = parseFloat(weight_input) - 0.02;
                    var rand = parseInt(randomNum(0,15));
                    weight =parseFloat(weight_input) - rand/100;



                    $('.weight').eq(i).val(weight);
                    getPriceByWeight(weight,tr);
                }
            }
        });


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
        html += '<input type="number" class="weight" name="weight[]" min="0" max="5" step="0.01" />';
        html += '</td>';
        html += '<td>';
        html += '<input type="number" class="price" disabled />';
        html += '</td>';
        html += '<td>';
        html += '<b class="price_num" style="color:red;font-size:18px;">0</b>';
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
        sumPriceNumber();


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










</script>