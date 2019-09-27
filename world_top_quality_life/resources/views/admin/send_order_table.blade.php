<style>
    .table td input{
        float:left;
    }
</style>
<table class="table" id="myTable">
    <thead>
    <tr>
        <th>序号</th>
        <th>包裹编号</th>
        <th>运单号</th>
        <th>姓名</th>
        <th>电话</th>
        <th>重量</th>

    </tr>
    </thead>
    <tbody>
    @foreach($packages_res as $key => $vo)
    <tr>

        <td >{{ $key + 1 }}</td>
        <td >{{ $vo -> package_num }}</td>
        <td >{{ $vo -> wuliu_num }}</td>
        <td >{{ $vo -> name }}</td>
        <td >{{ $vo -> tel }}</td>
        <td >{{ $vo -> weight }}</td>


    </tr>
    @endforeach
    <input type="hidden" name="order_id" value="{{ $packages_res[0] -> order_id }}" />


    </tbody>
</table>
