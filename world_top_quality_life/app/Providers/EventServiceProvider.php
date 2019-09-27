<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use DB;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'App\Events\Event' => [
            'App\Listeners\EventListener',
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //更新优惠券是否过期
        Event::listen('coupons.checkInvalid', function ($user_id) {
            $links=DB::table('erp_user_coupon_link')
                ->where('status',0)
                ->where('user_id',$user_id)
                ->get();
            foreach ($links as $link){
                //一开始写的$link->invalid_at < time()
                $latest_time=mktime(0,0,0,date('m',$link->invalid_at),date('d',$link->invalid_at)+1,date('Y',$link->invalid_at))-1;
                if ($latest_time < time()){
                    DB::table('erp_user_coupon_link')
                        ->where('id',$link->id)
                        ->update(['status'=>2]);
                }
            }
        });
        //下单后更新优惠券状态
        Event::listen('coupons.updateStatus', function ($user_id,$order_id,$coupon_ids) {
            $insert_data=[];
            foreach ($coupon_ids as $coupon_id){
                DB::table('erp_user_coupon_link')
                    ->where('market_coupon_id',$coupon_id)
                    ->where('user_id',$user_id)
                    ->update(['status'=>1]);
                $insert_data[]=[
                    'order_id'=>$order_id,
                    'market_coupon_id'=>$coupon_id,
                ];
            }
            DB::table('erp_order_coupon_link')->insert($insert_data);
            DB::table('erp_market_coupons')->whereIn('id',$coupon_ids)->increment('use_count');
        });
    }
}
