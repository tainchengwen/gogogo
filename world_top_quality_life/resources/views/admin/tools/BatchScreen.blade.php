<form>
<div class="btn-group ">
    <textarea name="packages_nums">@if(isset($_GET['packages_nums']) && $_GET['packages_nums'] ){{ $_GET['packages_nums'] }}@endif</textarea>
    <input type="submit" value="搜索"  />
    @if($type == 1)
    <a href="{{ admin_url('order') }}"><input type="button" value="重置"  /></a>
    @elseif($type == 2)
    <a href="{{ admin_url('packageSearch') }}"><input type="button" value="重置"  /></a>
    @elseif($type == 3)
        <a href="{{ admin_url('warning') }}"><input type="button" value="重置"  /></a>
    @elseif($type == 4)
        <a href="{{ admin_url('AreaScanOrder') }}"><input type="button" value="重置"  /></a>
        @endif
</div>
</form>


