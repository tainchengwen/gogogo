
<div class="foot_box
@if(Route::currentRouteName() == 'return_index')
foot_active
@endif
" onclick="location.href='{{ url('return/index') }}' ">
    <div class="foot_img
@if(Route::currentRouteName() == 'return_index')
    foot_img_list_active
@else
            foot_img_list
@endif
"  ></div>
    <span>申请返点</span>
</div>
<div class="foot_box
@if(Route::currentRouteName() == 'return_list')
        foot_active
@endif
" onclick="location.href='{{ url('return/returnList') }}' " >
    <div class="foot_img

@if(Route::currentRouteName() == 'return_list')
            foot_img_my_active
@else
            foot_img_my
@endif
"  ></div>
    <span>我的订单</span>
</div>