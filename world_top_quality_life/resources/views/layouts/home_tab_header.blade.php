<div class="tab_header">
    <div class="tab_sp5 @if(!isset($_GET['status'])) tab_sp_active @endif ">
        <span onclick="location.href='{{ url('order') }}'">全部</span>
    </div>
    <div class="tab_sp5 @if(isset($_GET['status']) && $_GET['status'] == 0 ) tab_sp_active @endif"  >
        <span onclick="location.href='{{ url('order').'?status=0' }}'">待付款</span>
    </div>
    <div class="tab_sp5 @if(isset($_GET['status']) && $_GET['status'] == 1 ) tab_sp_active @endif">
        <span  onclick="location.href='{{ url('order').'?status=1' }}'" >待填地址</span>
    </div>
    <div class="tab_sp5 @if(isset($_GET['status']) && $_GET['status'] == 2 ) tab_sp_active @endif">
        <span  onclick="location.href='{{ url('order').'?status=2' }}'" >待收货</span>
    </div>
    <div class="tab_sp5 @if(isset($_GET['status']) && $_GET['status'] == 3 ) tab_sp_active @endif">
        <span onclick="location.href='{{ url('order').'?status=3' }}'" >已完成</span>
    </div>
</div>