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
        <th>创建时间</th>




    </tr>
    </thead>
    <tbody>
    @foreach($checkGoodsDetail as $key => $vo)
    <tr>

        <td >{{ $key + 1 }}</td>
        <td >{{ $vo -> scan_goods_number }}</td>
        <td >{{ date('Y-m-d H:i',$vo -> created_at) }}</td>
    </tr>
    @endforeach

    </tbody>
</table>
