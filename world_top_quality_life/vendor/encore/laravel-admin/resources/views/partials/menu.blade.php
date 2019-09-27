<style>
    .treeview-menu-new{
        display: block;
        background: #f4f4f5;
        list-style: none;
        padding: 0;
        margin: 0;
        padding-left: 5px;
    }

    .treeview-menu-new>li>a {
        padding: 5px 5px 5px 15px;
        display: block;
        font-size: 14px;
    }

    .treeview-menu-new>li>a>.fa,  .treeview-menu-new>li>a>.glyphicon,  .treeview-menu-new>li>a>.ion {
        width: 20px;
    }

    .treeview-menu-new>li>a {
        color: #777;
    }

    .skin-blue-light .sidebar a {
        color: #777;
    }

</style>
@if(Admin::user()->visible($item['roles']))
    @if(!isset($item['children']))
        <li>
            @if(url()->isValidUrl($item['uri']))
                <a href="{{ $item['uri'] }}" target="_blank">
            @else
                 <a href="{{ admin_base_path($item['uri']) }}">
            @endif
                <i class="fa {{$item['icon']}}"></i>
                @if (Lang::has($titleTranslation = 'admin.menu_titles.' . trim(str_replace(' ', '_', strtolower($item['title'])))))
                    <span>{{ __($titleTranslation) }}</span>
                @else
                    <span>{{ $item['title'] }}</span>
                @endif
            </a>
        </li>
    @else
        <li class="treeview_new" >
            <a >
                <i class="fa {{ $item['icon'] }}"></i>
                @if (Lang::has($titleTranslation = 'admin.menu_titles.' . trim(str_replace(' ', '_', strtolower($item['title'])))))
                    <span>{{ __($titleTranslation) }}</span>
                @else
                    <span>{{ $item['title'] }}</span>
                @endif
                <i class="fa fa-angle-left pull-right"></i>
            </a>
            <ul class="treeview-menu-new">
                @foreach($item['children'] as $item)
                    @include('admin::partials.menu', $item)
                @endforeach
            </ul>
        </li>
    @endif
@endif