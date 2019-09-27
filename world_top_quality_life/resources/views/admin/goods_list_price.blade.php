<style>
    .table td input{
        float:left;
    }
</style>

<table class="table" id="myTable">
    <thead>
    <tr>
        <th>价格s</th>
        <th>价格a</th>
        <th>价格b</th>
        <th>价格c</th>
        <th>价格d</th>
    </tr>
    </thead>
    <input type="hidden" name="id" value="{{ $compact['id'] }}"   />
    <input type="hidden" name="url_type" value="{{ $compact['url_type'] }}"   />
    <input type="hidden" name="goods_id" value="{{ $compact['goods_id'] }}"   />
    <tbody>
    <tr>
        @if($compact['price_temp'])

            <td ><input type="number"  step="0.01" required id="price_s" name="price_s"  value="{{ $compact['price_temp'] -> price_s }}" /></td>
            <td ><input type="number"  step="0.01" required id="price_a" name="price_a"  value="{{ $compact['price_temp'] -> price_a }}" /></td>
            <td ><input type="number"  step="0.01" required id="price_b" name="price_b"  value="{{ $compact['price_temp'] -> price_b }}" /></td>
            <td ><input type="number"  step="0.01" required id="price_c" name="price_c"  value="{{ $compact['price_temp'] -> price_c }}" /></td>
            <td ><input type="number"  step="0.01" required id="price_d" name="price_d"  value="{{ $compact['price_temp'] -> price_d }}" /></td>
            @else
            <td ><input type="number"  step="0.01" required id="price_s" name="price_s" /></td>
            <td ><input type="number"  step="0.01" required id="price_a" name="price_a" /></td>
            <td ><input type="number"  step="0.01" required id="price_b" name="price_b"  /></td>
            <td ><input type="number"  step="0.01" required id="price_c" name="price_c"  /></td>
            <td ><input type="number"  step="0.01" required id="price_d" name="price_d"  /></td>
        @endif






    </tr>


    </tbody>




</table>


@if($compact['price_temp'])
    <h4 style="margin-top:15px;">特价价格设置</h4>
<table class="table">
    <thead>

    <tr>
        <th>开始时间</th>
        <th>结束时间</th>
        <th>特价价格a</th>
        <th>特价价格b</th>
        <th>特价价格c</th>
        <th>特价价格d</th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td><input type="text"  id="startTime" name="startTime"   autocomplete="off" class="form-control time_start" style="width: 150px" @if($compact['price_temp'] -> s_starttime) value="{{ date('Y-m-d H:i:s',$compact['price_temp'] -> s_starttime) }}" @endif ></td>
        <td><input type="text"  id="endTime" name="endTime"  autocomplete="off" class="form-control time_start" style="width: 150px"  @if($compact['price_temp'] -> s_endtime) value="{{ date('Y-m-d H:i:s',$compact['price_temp'] -> s_endtime) }}" @endif ></td>
        <td ><input type="number"  step="0.01"   name="s_price_a"  value="{{ $compact['price_temp'] -> s_price_a }}" /></td>
        <td ><input type="number"  step="0.01"   name="s_price_b"  value="{{ $compact['price_temp'] -> s_price_b }}" /></td>
        <td ><input type="number"  step="0.01"   name="s_price_c"  value="{{ $compact['price_temp'] -> s_price_c }}" /></td>
        <td ><input type="number"  step="0.01"   name="s_price_d"  value="{{ $compact['price_temp'] -> s_price_d }}" /></td>
    </tr>

    </tbody>

</table>
<script src="{{ asset('js/layer/2.2/layer.js') }}"></script>
<script>
    $(function(){
        laydate.render({
            elem: '#startTime',
            type:'datetime',
            //指定元素
        });
        laydate.render({
            elem: '#endTime',
            type:'datetime',
            //指定元素
        });


    })
</script>
    @endif