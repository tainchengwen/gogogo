<style>
    .table td input{
        float:left;
    }
</style>
<table class="table" id="myTable">
    <thead>
    <tr>
        <th>包裹编号</th>
        <th>包裹重量</th>
        <th>总价</th>
        <th>省|市|地址</th>
        <th>联系人</th>
        <th>联系方式</th>

    </tr>
    </thead>
    <tbody>
    @foreach($packages as $vo)
    <tr>
        <td >{{ $vo -> package_num }}</td>
        <td >{{ $vo -> weight }}</td>
        <td >{{ $vo -> price }}</td>
        <td ><input type="text" name="provinces[]" value="{{ $vo -> province }}" required  /><input type="text" value="{{ $vo -> city }}" name="citys[]" required/><input type="text"  style="width:200px;" name="address[]" value="{{ $vo -> address }}" required/></td>
        <td ><input type="text" value="{{ $vo -> name }}" name="names[]" required/></td>
        <td ><input type="number" minlength="11" value="{{ $vo -> tel }}" name="tels[]" required/></td>

    </tr>
    @endforeach
    <input type="hidden" name="order_id" value="{{ $packages[0] -> order_id }}" />


    </tbody>
</table>
