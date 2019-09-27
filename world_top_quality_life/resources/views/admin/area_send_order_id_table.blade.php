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



    </tr>
    </thead>
    <tbody>
    @foreach($scan_goods_info as $key => $vo)
    <tr>

        <td >{{ $key + 1 }}</td>
        <td >{{ $vo -> package_num }}</td>
    </tr>
    @endforeach

    </tbody>
</table>
