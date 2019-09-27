<?php

namespace App\Jobs;

use App\Order;
use App\Package;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessPodcast implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $package;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Package $package)
    {
        //
        $this-> package = $package;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //
        //通过

        $model_order = new Order();
        $package_info = DB::table('packages') -> where([
            'id' => $this -> package -> id
        ]) -> first();
        if($package_info && $package_info -> wuliu_num && !$package_info -> pdf){
            $order_info = DB::table('order') -> where([
                'id' => $package_info -> order_id
            ]) -> first();
            if($order_info){
                $post_data = $model_order -> makePdf($package_info -> wuliu_num,$package_info -> route_id);
                Log::info('queue:');
                Log::info($post_data);
                if(!count($post_data)){
                    //如果没收到 增加1分钟延时
                    dispatch(new ProcessPodcast(Package::find($this -> package -> id)))->delay(now()->addMinutes(1));
                }

                $model_order -> javaMergePdf($package_info -> route_id,$post_data,[],false);
                DB::table('packages') -> where([
                    'id' => $this -> package -> id
                ]) -> update([
                    'is_queue' => 1
                ]);
            }

        }

    }
}
