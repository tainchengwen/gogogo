
<table style="width:100%;">
    @foreach($code_infos as $vo)
        <tr>
            @if(count($code_infos) > 1)
            <td style="border:1px solid #000000;width:120px;text-align: center">{{ $vo -> number }}</td>
                @else
                <td style="border:1px solid #000000;width:120px;text-align: center">{{ $number }}</td>
            @endif

            <td style="border:1px solid #000000;width:120px;text-align: center"   >
            @foreach($vo -> codes_info as $value)
                {{ $value -> code  }}<br>
            @endforeach
            </td>




                <td style="border:1px solid #000000;">
                    @foreach($vo -> codes_info as $value)
                        {{ $value -> goods_name  }}<br>
                    @endforeach
                </td>



                <td style="border:1px solid #000000;width:80px;">
                    <a href="{{ admin_base_path('editCommodityCodeAddress').'?id='.$vo -> id }}">地址维护 </a>
                </td>
                <td style="border:1px solid #000000;width:200px;">
                    @foreach($vo -> codes_info as $value)
                        {{ $value -> address  }}<br>
                    @endforeach
                </td>
        </tr>
    @endforeach
</table>