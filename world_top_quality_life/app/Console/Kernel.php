<?php

namespace App\Console;

use App\Console\Commands\MailStatus;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\DB;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
        MailStatus::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function () {
            DB::beginTransaction();
            try{
                $now=time();
                //团购到期自动关闭
                $groups=DB::table('erp_market_groups')
                    ->where('end_at','<=',$now)
                    ->update(['flag'=>1]);
                //检测团购单十分钟内是否付款
                $orders=DB::table('erp_stock_order')
                    ->whereIn('group_status',[1,3])
                    ->where('flag',0)
                    ->get();
                foreach ($orders as $order){
                    if ((time()-$order->created_at)>=600){
                        $request=request();
                        $request->offsetSet('business_id',49);
                        $request->offsetSet('orderId',$order->id);
                        $order_controller=app('App\Http\Controllers\ApiShopMp\OrderController');
                        $result=app()->call([$order_controller, "cancelOrder"]);
                        if ($result['status']){
                            $group_buy=DB::table('erp_market_group_buyers')->where('order_id',$order->id)->first();
                            if (!$group_buy)
                                continue;
                            if ($group_buy->group_detail_id){
                                DB::table('erp_market_group_details')->where('id',$group_buy->group_detail_id)->decrement('buy_count');
                            }else{
                                DB::table('erp_market_groups')
                                    ->leftJoin('erp_market_open_groups','erp_market_groups.id','erp_market_open_groups.group_id')
                                    ->where('erp_market_open_groups.id',$group_buy->open_id)->decrement('erp_market_groups.union_buy_count');
                            }
                            DB::table('erp_market_open_groups')->where('id',$group_buy->open_id)->update(['status'=>0]);
                            DB::table('erp_market_group_buyers')->where('order_id',$order->id)->delete();
                        }
                    }
                }
                //机器人拼团
                $opens=DB::table('erp_market_open_groups')
                    ->leftJoin('erp_market_groups','erp_market_open_groups.group_id','erp_market_groups.id')
                    ->where('erp_market_open_groups.status',0)
                    ->select(['erp_market_open_groups.*','erp_market_groups.support_bot','erp_market_groups.group_people_num'])
                    ->get();
                foreach ($opens as $open){
                    if ($open->support_bot){
                        $order_ids=DB::table('erp_market_group_buyers')->where('open_id',$open->id)->pluck('order_id');
                        $user_count=DB::table('erp_market_group_buyers')->where('open_id',$open->id)->count();
                        $bot_count=$open->group_people_num - $user_count;//剩余拼团人数
                        $insert_data=[];
                        for ($i=0;$i<$bot_count;$i++){
                            $random_bot_user = DB::table('wxuser_bot')->inRandomOrder()->first();//随机取一个机器人用户
                            $insert_data[]=[
                                'open_id'=>$open->id,
                                'user_id'=>$random_bot_user->id,
                                'group_detail_id'=>0,
                                'created_at'=>time(),
                                'is_bot'=>1,
                                'order_status'=>1
                            ];
                        }
                        DB::table('erp_market_group_buyers')->insert($insert_data);
                        DB::table('erp_market_open_groups')->where('id',$open->id)->update(['status'=>1]);
                        DB::table('erp_stock_order')->whereIn('id',$order_ids)->update(['group_status'=>4]);
                    }
                }
                DB::commit();
            }catch (\Exception $exception){
                DB::rollBack();
            }
        })->everyTenMinutes();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
