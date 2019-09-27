<div class="foot">
    <div class="foot_box @if(Route::currentRouteName() == 'market') foot_active @endif" onclick="location.href='{{ url('market').'?from=foot' }}'" >
        <div class="
        foot_img
        @if(Route::currentRouteName() == 'market')
                foot_img_home_active
                @else
                foot_img_home
        @endif
        ">
        </div>
        <span>购买</span>
    </div>
    <div class="foot_box @if(Route::currentRouteName() == 'car') foot_active @endif" onclick="location.href='{{ url('mall/car') }}'" >
        <div class="
        foot_img
        @if(Route::currentRouteName() == 'car')
                foot_img_buy_active
                @else
                foot_img_buy
        @endif
                ">
        </div>

        <span>购物车</span>
    </div>
    <div class="foot_box @if(Route::currentRouteName() == 'center') foot_active @endif" onclick="location.href='{{ url('mall/center') }}'" >
        <div class="
        foot_img
        @if(Route::currentRouteName() == 'center')
                foot_img_my_active
                @else
                foot_img_my
        @endif
                ">
        </div>


        <span>个人中心</span>
    </div>
</div>