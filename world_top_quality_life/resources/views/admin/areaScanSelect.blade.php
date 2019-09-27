<style>
    .table td{
        text-align: center;
    }
    .s-box{
        width:100%;
    }
    .s-box img {
        width:100%;
        height:100%;
    }
    .col-md-2{
        height:560px;
    }
    .title{
        height: 50px;
        line-height: 50px;
        text-align: center;
        font-size: 20px;
        font-weight: 900;
        border-top:1px solid #000000;
    }
</style>
<form method="post" action="{{ admin_url('areaScanSelectRes') }}">
    {{ csrf_field() }}
<div class="col-md-12" style="padding-left:9px;">
    @foreach($info as $vo)
        <div class="col-md-12 title" >
            {{ $vo['scan_info'] -> product_name }} {{ $vo['scan_info'] -> product_no }}
        </div>
        @if(count($vo['reptile_info']))
            @foreach($vo['reptile_info'] as $value)
        <div class="col-md-2">
            <div class="s-box" style="height:160px;" >{!! $value -> image !!}</div>
            <div class="s-box" style="height:160px;">{{ $value -> jap_name }}</div>
            <div class="s-box" style="height:100px;">{{ $value -> zh_name }}</div>
            <div class="s-box" style="height:100px;">{{ $value -> en_name }}</div>
            <div class="s-box">{{ $value -> price }}</div>
            <div class="s-box"><input type="radio" name="{{ $vo['scan_info'] -> id }}" value="{{ $value -> id }}" /></div>
        </div>
            @endforeach
        @endif
    @endforeach
</div>

    <div class="col-md-12">
        <div class="col-md-2"></div>
        <div class="col-md-8">
            <div class="btn-group pull-left">
                <button class="btn btn-info submit btn-sm">提交</button>
            </div>
        </div>
    </div>

</form>