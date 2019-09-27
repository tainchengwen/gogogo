<html>
<head>
    @extends('layouts.admin_header')
    <style>
        .col-lg-6{
            width:50%;
            float:left;
        }
        .col-sm-8{
            width:66%;
            float:left;
        }
        .col-sm-2{
            width:16.6%;
            float: left;
            padding-top: 7px;
        }
        .col-sm-1{
            float:left;
            width:11%;
            padding-top: 7px;
        }
        .col-sm-4{
            float:left;
            width:31%;
        }
        .form-group{
            margin-top:15px;
        }
        .redBorder{
            border:1px solid red;
        }
    </style>
    <meta name="_token" content="{{ csrf_token() }}"/>
    <script src="{{asset('vendor/laravel-admin/AdminLTE/plugins/jQuery/jQuery-2.1.4.min.js')}}"></script>
    <script src="{{ asset('js/laydate/laydate.js') }}"></script>
    <script src="{{ asset('js/layer/2.2/layer.js') }}"></script>
</head>
<body>
<div  style="height:100%;width:100%;" >
    <div class="col-md-12">

        <div class="btn-group pull-right">
            期望总税金<input type="text"  class="hopePrice" />
        </div>
    </div>
    <table style="width:100%;">
        <tr>
            <th>包裹编号</th>

            <th>物品1</th>
            <th>税号1</th>
            <th>数量1</th>
            <th>单价1</th>

            <th>税金1</th>
            <th>税率1</th>

            <th>物品2</th>
            <th>税号2</th>
            <th>数量2</th>
            <th>单价2</th>

            <th>税金2</th>
            <th>税率2</th>

            <th>物品3</th>
            <th>税号3</th>
            <th>数量3</th>
            <th>单价3</th>

            <th>税金3</th>
            <th>税率3</th>


            <th>总税金</th>
        </tr>
        @foreach($packages as $k => $vo)
        <tr class="s-tr">
            <input type="hidden" class="goods_id" value="{{ $vo -> id }}" />
            <td>{{ $vo -> package_num }}</td>

            <td><input value="{{ $vo -> s_content1 }}" class="content content1" /></td>
            <td><input value="{{ $vo -> Tax_code1 }}" style="width: 80px;"  class="tax_code tax_code1" data="1" index="{{ $k + 1 }}" /></td>
            <td><input value="{{ $vo -> s_pieces1 }}" style="width: 40px;" class="pieces pieces1"/></td>
            <td><input value="{{ $vo -> s_price1 }}" style="width: 40px;" class="price price1"/></td>

            <td><input value="{{ round($vo -> s_pieces1*$vo -> s_price1*$vo -> tax1,2) }}" style="width: 40px;" class="tax_price tax_price1" /></td>
            <td><a class="tax tax1" data="{{ $k + 1 }}">{{ $vo -> tax1 }}</a></td>

            <td><input value="{{ $vo -> s_content2 }}" class="content content2" /></td>
            <td><input value="{{ $vo -> Tax_code2 }}" style="width: 80px;" class="tax_code tax_code2" data="2"  index="{{ $k + 1 }}" /></td>
            <td><input value="{{ $vo -> s_pieces2 }}" style="width: 40px;" class="pieces pieces2"/></td>
            <td><input value="{{ $vo -> s_price2 }}" style="width: 40px;" class="price price2"/></td>

            <td><input value="{{ round($vo -> s_pieces2*$vo -> s_price2*$vo -> tax2,2) }}"  style="width: 40px;" class="tax_price tax_price2"/></td>
            <td><a class="tax tax2" data="{{ $k + 1 }}" >{{ $vo -> tax2 }}</a></td>

            <td><input value="{{ $vo -> s_content3 }}" class="content content3"/></td>
            <td><input value="{{ $vo -> Tax_code3 }}" style="width: 80px;" class="tax_code tax_code3" data="3" index="{{ $k + 1 }}" /></td>
            <td><input value="{{ $vo -> s_pieces3 }}" style="width: 40px;" class="pieces pieces3"/></td>
            <td><input value="{{ $vo -> s_price3 }}" style="width: 40px;" class="price price3"/></td>

            <td><input value="{{ round($vo -> s_pieces3*$vo -> s_price3*$vo -> tax3,2) }}"  style="width: 40px;" class="tax_price tax_price3" /></td>
            <td><a class="tax tax3" data="{{ $k + 1 }}" >{{ $vo -> tax3 }}</a></td>
            <td><input value="{{ round($vo -> s_pieces1*$vo -> s_price1*$vo -> tax1 + $vo -> s_pieces2*$vo -> s_price2*$vo -> tax2 + $vo -> s_pieces3*$vo -> s_price3*$vo -> tax3,2) }}" class="tax_price_all" style="width:40px;" /></td>

        </tr>
        @endforeach

    </table>

    <div class="col-md-12">

        <div class="btn-group pull-right">
            <button type="button" class="btn btn-info pull-right" id="tijiao">提交</button>
        </div>
    </div>
</div>

<script>

    //计算每行的税金
    function checkShuijin(){
        var shuijin = 0;
        var pieces1;
        var price1;
        var tax1;
        var tax_price1;

        var pieces2;
        var price2;
        var tax2;
        var tax_price2;

        var pieces3;
        var price3;
        var tax3;
        var tax_price3;

        var i;
        var tr_length = $('.s-tr').length;
        for(i=0;i<tr_length;i++){
            pieces1 = parseInt($.trim($('.s-tr').eq(i).find('.pieces1').val()));
            price1 = parseFloat($.trim($('.s-tr').eq(i).find('.price1').val()));
            tax1 = parseFloat($.trim($('.s-tr').eq(i).find('.tax1').text()))
            //计算每行税金
            tax_price1 = (pieces1*price1*tax1).toFixed(2);
            $('.s-tr').eq(i).find('.tax_price1').val(tax_price1);
        }

        for(i=0;i<tr_length;i++){
            pieces2 = parseInt($.trim($('.s-tr').eq(i).find('.pieces2').val()));
            price2 = parseFloat($.trim($('.s-tr').eq(i).find('.price2').val()));
            tax2 = parseFloat($.trim($('.s-tr').eq(i).find('.tax2').text()))
            //计算每行税金
            tax_price2 = (pieces2*price2*tax2).toFixed(2);
            $('.s-tr').eq(i).find('.tax_price2').val(tax_price2);
        }


        for(i=0;i<tr_length;i++){
            pieces3 = parseInt($.trim($('.s-tr').eq(i).find('.pieces3').val()));
            price3 = parseFloat($.trim($('.s-tr').eq(i).find('.price3').val()));
            tax3 = parseFloat($.trim($('.s-tr').eq(i).find('.tax3').text()))
            //计算每行税金
            tax_price3 = (pieces3*price3*tax3).toFixed(2);
            $('.s-tr').eq(i).find('.tax_price3').val(tax_price3);
        }

        for(i=0;i<tr_length;i++){
            var tax_price_all_1 = $('.s-tr').eq(i).find('.tax_price1').val();
            var tax_price_all_2 = $('.s-tr').eq(i).find('.tax_price2').val();
            var tax_price_all_3 = $('.s-tr').eq(i).find('.tax_price3').val();
            $('.s-tr').eq(i).find('.tax_price_all').val((parseFloat(tax_price_all_1)+parseFloat(tax_price_all_2)+parseFloat(tax_price_all_3)).toFixed(2));
        }

        console.log('checkShuijin');



    }




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

    //通过单元格计算税金
    function countShuijin(price_all,tax3_price,tax2_price,tax1,pieces1){
        console.log(price_all);
        console.log(tax3_price);
        console.log(tax2_price);
        console.log(tax1);
        console.log(pieces1);
                        //总税金 税金3 税金2 税率1 数量1 单价1
        return parseFloat((price_all-tax3_price-tax2_price)/tax1/pieces1).toFixed(2);
    }


    $(function(){

        $('.hopePrice').on('blur',function(){
            var rand;
            var point;
            var price = $.trim($('.hopePrice').val());
            var tax3_price;
            var tax2_price;
            var tax1;
            var pieces1;
            var price1;
            var i;
            if(price){
                var tr_length = $('.s-tr').length;
                for(i=0;i<tr_length;i++){
                    rand = parseInt(randomNum(0,25));

                    point = parseFloat(parseFloat(price) - rand/100);
                    //console.log(rand);
                    //console.log(point);
                    //console.log(i);
                    //console.log($('.s-tr').eq(i).html());
                    //console.log($('.tax_price_all').eq(i).html());
                    $('.s-tr').eq(i).find('.tax_price_all').val(point);

                    //税金3
                     tax3_price = parseFloat($('.tax_price3').eq(i).val());
                    //税金2
                     tax2_price = parseFloat($('.tax_price2').eq(i).val());
                    //税率1
                     tax1 = parseFloat($('.tax1').eq(i).text());
                    //数量1
                     pieces1 = parseInt($('.pieces1').eq(i).val());

                     price1 = countShuijin(point,tax3_price,tax2_price,tax1,pieces1);

                     $('.price1').eq(i).val(price1);



                }
                //checkShuijin();
            }

        });



        //每个单元格动 改税金
        $('input').on('blur',function(){
            if(!$(this).hasClass('tax_price_all') && !$(this).hasClass('hopePrice')){
                checkShuijin();
            }
        });

        //填写完税号 校验
        $('.tax_code').on('blur',function(){
            var code = $.trim($(this).val());
            var data_input = $(this).attr('data');
            var index = $(this).attr('index');
            if(!code){
                alert('税号必填');return false;
            }

            //校验
            var url = '{{ admin_base_path('getGoodsTax') }}';
            var _this = this;
            $.ajax({
                type: 'POST',
                url: url,
                dataType:'json',
                data: {
                    tax:code,
                },

                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success: function(data){

                    if(data.code == 'success'){

                        var length = $('.tax'+data_input).length;
                        for(var i=0;i<length;i++){
                            if($('.tax'+data_input).attr('data') == index){
                                $('.tax'+data_input).text(data.msg);
                            }
                        }
                        //用每个的税率 计算总税金
                        checkShuijin();
                    }else{

                        alert('税号错误');
                        $(this).text('');

                    }

                },
                error: function(xhr, type){

                    alert('Ajax error!')
                }
            });

        });


        //修改总税金
        $('.tax_price_all').on('blur',function(){
            //总 -  （税金3 - 税金2）/税率1 /数量1  = 单价1
            var price_all = parseFloat($.trim($(this).val()));
            if(price_all){
                //税金3
                var tax3_price = parseFloat($(this).parents('.s-tr').find('.tax_price3').val());
                //税金2
                var tax2_price = parseFloat($(this).parents('.s-tr').find('.tax_price2').val());
                //税率1
                var tax1 = parseFloat($(this).parents('.s-tr').find('.tax1').text());
                //数量1
                var pieces1 = parseInt($(this).parents('.s-tr').find('.pieces1').val());

                var price1 = countShuijin(price_all,tax3_price,tax2_price,tax1,pieces1);

                $(this).parent().parent('.s-tr').find('.price1').val(price1);


                //console.log($(this).parents('.s-tr').html());
            }
        });









        $('#tijiao').click(function(){
            //有几行数据
            var tr_length = $('.s-tr').length;


            var content1 = [];
            var content2 = [];
            var content3 = [];

            var tax_code1 = [];
            var tax_code2 = [];
            var tax_code3 = [];

            var pieces1 = [];
            var pieces2 = [];
            var pieces3 = [];

            var price1 = [];
            var price2 = [];
            var price3 = [];



            var goods_ids = [];

            for(var i=0;i<tr_length;i++){
                content1.push($.trim($('.s-tr').eq(i).find('.content1').val()));
                content2.push($.trim($('.s-tr').eq(i).find('.content2').val()));
                content3.push($.trim($('.s-tr').eq(i).find('.content3').val()));

                tax_code1.push($.trim($('.s-tr').eq(i).find('.tax_code1').val()));
                tax_code2.push($.trim($('.s-tr').eq(i).find('.tax_code2').val()));
                tax_code3.push($.trim($('.s-tr').eq(i).find('.tax_code3').val()));

                pieces1.push($.trim($('.s-tr').eq(i).find('.pieces1').val()));
                pieces2.push($.trim($('.s-tr').eq(i).find('.pieces2').val()));
                pieces3.push($.trim($('.s-tr').eq(i).find('.pieces3').val()));

                price1.push($.trim($('.s-tr').eq(i).find('.price1').val()));
                price2.push($.trim($('.s-tr').eq(i).find('.price2').val()));
                price3.push($.trim($('.s-tr').eq(i).find('.price3').val()));


                goods_ids.push($.trim($('.s-tr').eq(i).find('.goods_id').val()));
            }





            //传递值
            var url = '{{ admin_base_path('editAllGoodsParatemerRes') }}';

            $.ajax({
                type: 'POST',
                url: url,
                dataType:'json',
                data: {
                    content1:content1,
                    content2:content2,
                    content3:content3,

                    tax_code1:tax_code1,
                    tax_code2:tax_code2,
                    tax_code3:tax_code3,

                    pieces1:pieces1,
                    pieces2:pieces2,
                    pieces3:pieces3,

                    price1:price1,
                    price2:price2,
                    price3:price3,


                    goods_ids:goods_ids

                },

                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success: function(data){
                    if(data.code == '200'){
                        alert('修改成功');
                        parent.location.reload();
                    }else{
                        alert(data.msg);
                    }

                },
                error: function(xhr, type){
                    //alert('Ajax error!')
                }
            });







            //alert(input_length);
        })
    })
</script>


</body>
</html>