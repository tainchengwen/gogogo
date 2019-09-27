@if($compact['temp_name_info'])
<table class="table" id="myTable">
    <thead>
    <tr>

        <th>序号</th>
        <th>运送到</th>
        <th>首重(kg)</th>
        <th>首费(元)</th>
        <th>续重(kg)</th>
        <th>续费(元)</th>
        <th>操作</th>
    </tr>
    </thead>
    <tbody>

        @foreach($compact['temp_name_info'] as $key => $vo)
            <tr class="supertr">
                <td >{{ $key + 1 }}</td>
                <td >
                    {{ $vo -> address }}
                </td>
                <td ><input type="number"  value="{{ $vo -> first_weight }}" class="first_weight"  /></td>
                <td ><input type="number"  value="{{ $vo -> first_price }}"  class="first_price"  /></td>
                <td ><input type="number"  value="{{ $vo -> secend_weight }}"  class="secend_weight"  /></td>
                <td ><input type="number"  value="{{ $vo -> secend_price }}"   class="secend_price"  /></td>
                <td >
                    <button type="button" class="btn btn-primary btn-danger btn-sm deleteData" data="{{ $vo -> id }}"   >删除</button>
                    <button type="button" class="btn btn-primary btn-info btn-sm editData" data="{{ $vo -> id }}"   >修改</button>
                </td>
            </tr>
        @endforeach


    </tbody>
</table>
@endif

    <table class="table" id="myTable">
        <thead>
        <tr>


            <th>运送到</th>
            <th>首重(kg)</th>
            <th>首费(元)</th>
            <th>续重(kg)</th>
            <th>续费(元)</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>
                @foreach($compact['province_arr'] as $k => $vo)
                    @if($k % 6 == 0)
                        <br>
                    @endif
                    <label style="padding-left:5px;"><input type="checkbox" value="{{ $vo }}" name="address[]"   />  {{ $vo }}</label>
                @endforeach
            </td>
            <td ><input type="number" class="first_weight" name="first_weight"  required /></td>
            <td ><input type="number" class="first_price" name="first_price"  required /></td>
            <td ><input type="number" class="secend_weight" name="secend_weight"  required /></td>
            <td ><input type="number" class="secend_price" name="secend_price"  required /></td>

        </tr>

        </tbody>
    </table>

    <input type="hidden" name="temp_name_id" value="{{ $compact['temp_name_id'] }}" />

<div class="box-footer">

    <input type="hidden" name="_token" value="nJRiHBcnPXQEkabHdFCb6eQQaBPnO0vMwTAAvYPr">

    <div class="col-md-2">
    </div>

    <div class="col-md-8">

        <div class="btn-group pull-right">
            <button type="submit" class="btn btn-primary btn-danger" id="addTr">增加</button>
        </div>

    </div>
</div>
<script>
    $('.deleteData').click(function(){
        if(confirm('确认删除么')){
            var freight_temp_id = $(this).attr('data');
            location.href='{{ admin_url('deleteFreightTemp') }}' + '/' + freight_temp_id;
        }
    })

    $('.editData').click(function(){
        var first_weight = $.trim($(this).parents('.supertr').find('.first_weight').val());
        var first_price = $.trim($(this).parents('.supertr').find('.first_price').val());
        var secend_weight = $.trim($(this).parents('.supertr').find('.secend_weight').val());
        var secend_price = $.trim($(this).parents('.supertr').find('.secend_price').val());
        if(!first_weight || !first_price || !secend_weight || !secend_price){
            alert('请填写必填项');
            return false;
        }

        if(confirm('确认修改么')){
            var freight_temp_id = $(this).attr('data');
            location.href='{{ admin_url('editFreightTemp') }}' + '?first_weight='+first_weight+'&first_price='+first_price + '&secend_weight='+secend_weight + '&secend_price='+secend_price+'&freight_temp_id='+freight_temp_id;
        }
    })

</script>

