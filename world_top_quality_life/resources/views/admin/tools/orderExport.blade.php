<div class="btn-group ">
    <a class="btn btn-sm btn-twitter"><i class="fa fa-download"></i> 导出模板</a>
    <button type="button" class="btn btn-sm btn-twitter dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
        <span class="caret"></span>
        <span class="sr-only">Toggle Dropdown</span>
    </button>
    <ul class="dropdown-menu" role="menu">
        @if($options['from_area_admin_name'] == 'admin')
        <li><a href="{{ admin_url('import').'/1' }}" target="_blank">通用模板</a></li>
        <li><a href="{{ admin_url('import').'/2' }}" target="_blank">NN100</a></li>
            @else
            <li><a href="{{ admin_url('import').'/1' }}" target="_blank">通用模板</a></li>
            <li><a href="{{ admin_url('import').'/2' }}" target="_blank">XS001</a></li>
        @endif
    </ul>
</div>