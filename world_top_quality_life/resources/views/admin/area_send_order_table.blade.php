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
        <th>区域</th>
        <th>下单人</th>
        <th>商品编码</th>
        <th>商品名称</th>
        <th>包装方式</th>
        <th>英文名称</th>
        <th>品牌</th>
        <th>规格</th>
        <th>单位</th>
        <th>申报价格</th>
        <th>淘宝链接</th>
        <th>数量</th>



    </tr>
    </thead>
    <tbody>
    @foreach($scan_goods_info as $key => $vo)
    <tr>

        <td >{{ $key + 1 }}</td>
        <td >{{ $vo -> package_num }}</td>
        <td >{{ $vo -> area_name }}</td>
        <td >{{ $vo -> nickname }}</td>
        <td >{{ $vo -> product_no }}</td>
        <td >{{ $vo -> product_name }}</td>
        <td >{{ $vo -> product_method }}</td>
        <td >{{ $vo -> english_name }}</td>
        <td >{{ $vo -> brand_name }}</td>
        <td >{{ $vo -> product_format }}</td>
        <td >{{ $vo -> product_unit }}</td>
        <td >{{ $vo -> declare_price }}</td>
        <td >@if($vo -> taobao_url)<a href="{{ $vo -> taobao_url }}" target="_blank">链接</a>@endif</td>
        <td >{{ $vo -> goods_number }}</td>


    </tr>
    @endforeach

    </tbody>
</table>
