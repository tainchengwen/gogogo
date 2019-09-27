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
    @if($goods_paratemer)
        <div class="col-md-12">


            <label  class="col-sm-1 control-label">名称1</label>
            <div class="col-sm-3">
                <div class="input-group" style="width: 100%" >
                    <input style="width: 100%" type="text"  class="form-control " value="{{ $goods_paratemer -> s_content1 }}" >
                </div>
            </div>
            <label  class="col-sm-1 control-label">名称2</label>
            <div class="col-sm-3">
                <div class="input-group" style="width: 100%" >
                    <input style="width: 100%" type="text"  class="form-control " value="{{ $goods_paratemer -> s_content2 }}" >
                </div>
            </div>
            <label  class="col-sm-1 control-label">名称3</label>
            <div class="col-sm-3">
                <div class="input-group" style="width: 100%" >
                    <input style="width: 100%" type="text"  class="form-control " value="{{ $goods_paratemer -> s_content3 }}" >
                </div>
            </div>





            <label  class="col-sm-1 control-label">税号1</label>
            <div class="col-sm-3">
                <div class="input-group" style="width: 100%" >
                    <input style="width: 100%" type="text"  class="form-control code1" value="{{ $goods_paratemer -> Tax_code1 }}"  >
                </div>
            </div>
            <label  class="col-sm-1 control-label">税号2</label>
            <div class="col-sm-3">
                <div class="input-group" style="width: 100%" >
                    <input style="width: 100%" type="text"  class="form-control code2" value="{{ $goods_paratemer -> Tax_code2 }}" >
                </div>
            </div>
            <label  class="col-sm-1 control-label">税号3</label>
            <div class="col-sm-3">
                <div class="input-group" style="width: 100%" >
                    <input style="width: 100%" type="text"  class="form-control code3" value="{{ $goods_paratemer -> Tax_code3 }}" >
                </div>
            </div>

            <label  class="col-sm-1 control-label">数量1</label>
            <div class="col-sm-3">
                <div class="input-group" style="width: 100%" >
                    <input style="width: 100%" type="text"  class="form-control pieces"  value="{{ $goods_paratemer -> s_pieces1 }}"  >
                </div>
            </div>
            <label  class="col-sm-1 control-label">数量2</label>
            <div class="col-sm-3">
                <div class="input-group" style="width: 100%" >
                    <input style="width: 100%" type="text"  class="form-control pieces"  value="{{ $goods_paratemer -> s_pieces2 }}" >
                </div>
            </div>
            <label  class="col-sm-1 control-label">数量3</label>
            <div class="col-sm-3">
                <div class="input-group" style="width: 100%" >
                    <input style="width: 100%" type="text"  class="form-control pieces"  value="{{ $goods_paratemer -> s_pieces3 }}" >
                </div>
            </div>
            <label  class="col-sm-1 control-label">单价1</label>
            <div class="col-sm-3">
                <div class="input-group" style="width: 100%" >
                    <input style="width: 100%" type="text"  class="form-control price"  value="{{ $goods_paratemer -> s_price1 }}" >
                </div>
            </div>
            <label  class="col-sm-1 control-label">单价2</label>
            <div class="col-sm-3">
                <div class="input-group" style="width: 100%" >
                    <input style="width: 100%" type="text"  class="form-control price"  value="{{ $goods_paratemer -> s_price2 }}" >
                </div>
            </div>
            <label  class="col-sm-1 control-label">单价3</label>
            <div class="col-sm-3">
                <div class="input-group" style="width: 100%" >
                    <input style="width: 100%" type="text"  class="form-control price"  value="{{ $goods_paratemer -> s_price3 }}" >
                </div>
            </div>

            <label  class="col-sm-1 control-label">重量1</label>
            <div class="col-sm-3">
                <div class="input-group" style="width: 100%" >
                    <input style="width: 100%" type="text"  class="form-control " value="{{ $goods_paratemer -> s_weight1 }}" >
                </div>
            </div>
            <label  class="col-sm-1 control-label">重量2</label>
            <div class="col-sm-3">
                <div class="input-group" style="width: 100%" >
                    <input style="width: 100%" type="text"  class="form-control " value="{{ $goods_paratemer -> s_weight2 }}" >
                </div>
            </div>
            <label  class="col-sm-1 control-label">重量3</label>
            <div class="col-sm-3">
                <div class="input-group" style="width: 100%" >
                    <input style="width: 100%" type="text"  class="form-control " value="{{ $goods_paratemer -> s_weight3 }}" >
                </div>
            </div>


            <label  class="col-sm-1 control-label">税率1</label>
            <div class="col-sm-3">
                <div class="input-group" style="width: 100%" >
                    <h5 class="code1_value"></h5>
                </div>
            </div>
            <label  class="col-sm-1 control-label">税率2</label>
            <div class="col-sm-3">
                <div class="input-group" style="width: 100%" >
                    <h5 class="code2_value"></h5>
                </div>
            </div>
            <label  class="col-sm-1 control-label">税率3</label>
            <div class="col-sm-3">
                <div class="input-group" style="width: 100%" >
                    <h5 class="code3_value"></h5>
                </div>
            </div>

            <input type="hidden" id="is_true_1" value="0" />
            <input type="hidden" id="is_true_2" value="0" />
            <input type="hidden" id="is_true_3" value="0" />



            <br>
            <div style="margin-top:20%;">应缴税额：<a id="shuijin"></a></div>

            <div class="col-md-12">
                <div class="btn-group pull-right">
                    <button type="button" class="btn btn-info pull-right" id="tijiao">修改</button>
                </div>
            </div>



        </div>
        <input type="hidden" id="package_id" value="{{ $package_id }}" />


    @endif
</div>

<script>

    //计算税金
    function checkShuijin(){
        var shuijin = 0;
        var pieces1 = parseInt($.trim($('.pieces').eq(0).val()));
        var price1 = parseFloat($.trim($('.price').eq(0).val()));
        var tax1 = parseFloat($.trim($('.code1_value').text()))
        if(pieces1 && price1 && tax1){
            shuijin = (shuijin + (pieces1 * price1 * tax1));
        }


        var pieces2 = parseInt($.trim($('.pieces').eq(1).val()));
        var price2 = parseFloat($.trim($('.price').eq(1).val()));
        var tax2 = parseFloat($.trim($('.code2_value').text()))
        if(pieces2 && price2 && tax2){
            shuijin = (shuijin + (pieces2 * price2 * tax2));
        }



        var pieces3 = parseInt($.trim($('.pieces').eq(2).val()));
        var price3 = parseFloat($.trim($('.price').eq(2).val()));
        var tax3 = parseFloat($.trim($('.code3_value').text()))
        if(pieces3 && price3 && tax3){
            shuijin = (shuijin + (pieces3 * price3 * tax3));
        }





        $('#shuijin').text(shuijin.toFixed(2));
    }



    function getGoodsTax(index,tax){
        if(!tax){
            return false;
        }
        var url = '{{ admin_base_path('getGoodsTax') }}';
        layer.load(1);
        $.ajax({
            type: 'POST',
            url: url,
            dataType:'json',
            data: {
                tax:tax,
            },

            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            success: function(data){
                layer.closeAll('loading');
                if(data.code == 'success'){
                    $('.code'+index+'_value').text(data.msg);
                    //用每个的税率 计算总税金
                    checkShuijin();
                    $('#is_true_'+index).val(1);
                }else{
                    $('#is_true_'+index).val(0);
                    layer.msg('税号:'+tax+'未匹配');
                    $('.code'+index+'_value').text('');

                }

            },
            error: function(xhr, type){
                layer.closeAll('loading');
                alert('Ajax error!')
            }
        });
    }


    $(function(){
        getGoodsTax(1,$('.code1').val());
        getGoodsTax(2,$('.code2').val());
        getGoodsTax(3,$('.code3').val());



        $('input').on('blur',function(){
            checkShuijin();
        });



        $('.code1').on('blur',function(){
            var code = $.trim($(this).val());
            getGoodsTax(1,code);
        });
        $('.code2').on('blur',function(){
            var code = $.trim($(this).val());
            getGoodsTax(2,code);
        });
        $('.code3').on('blur',function(){
            var code = $.trim($(this).val());
            getGoodsTax(3,code);
        });






        $('#tijiao').click(function(){

            var input_length = $('.form-control').length;
            //判断必填

            var content = [];
            var code = [];
            var price = [];
            var pieces = [];
            var weight = [];


            if($.trim($('.form-control').eq(0).val()) && $.trim($('.form-control').eq(3).val()) && $.trim($('.form-control').eq(6).val()) && $.trim($('.form-control').eq(9).val()) && $.trim($('.form-control').eq(12).val())){
                //console.log($('#is_true_1').val());
                if($('#is_true_1').val() == '0'){
                    alert('税号1填写有误');
                    return false;
                }

                content.push($.trim($('.form-control').eq(0).val()));
                code.push($.trim($('.form-control').eq(3).val()));
                pieces.push($.trim($('.form-control').eq(6).val()));
                price.push($.trim($('.form-control').eq(9).val()));
                weight.push($.trim($('.form-control').eq(12).val()));
            }else{
                alert('您必须至少写一套申报物品');
                return false;
            }



            if($.trim($('.form-control').eq(1).val()) && $.trim($('.form-control').eq(4).val()) && $.trim($('.form-control').eq(7).val()) && $.trim($('.form-control').eq(10).val()) && $.trim($('.form-control').eq(13).val())){
                if($('#is_true_2').val() == '0'){
                    alert('税号2填写有误');
                    return false;
                }

                content.push($.trim($('.form-control').eq(1).val()));
                code.push($.trim($('.form-control').eq(4).val()));
                pieces.push($.trim($('.form-control').eq(7).val()));
                price.push($.trim($('.form-control').eq(10).val()));
                weight.push($.trim($('.form-control').eq(13).val()));
            }

            if($.trim($('.form-control').eq(2).val()) && $.trim($('.form-control').eq(5).val()) && $.trim($('.form-control').eq(8).val()) && $.trim($('.form-control').eq(11).val()) && $.trim($('.form-control').eq(14).val())){
                if($('#is_true_3').val() == '0'){
                    alert('税号3填写有误');
                    return false;
                }
                content.push($.trim($('.form-control').eq(2).val()));
                code.push($.trim($('.form-control').eq(5).val()));
                pieces.push($.trim($('.form-control').eq(8).val()));
                price.push($.trim($('.form-control').eq(11).val()));
                weight.push($.trim($('.form-control').eq(14).val()));
            }


            //传递值
            var url = '{{ admin_base_path('editGoodsParatemerRes') }}';
            var package_id = $('#package_id').val();
            $.ajax({
                type: 'POST',
                url: url,
                dataType:'json',
                data: {
                    package_id:package_id,
                    content:content,
                    code:code,
                    price:price,
                    pieces:pieces,
                    weight:weight,
                },

                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success: function(data){
                    if(data.code == 'success'){
                        alert('修改成功');
                        parent.location.reload();
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