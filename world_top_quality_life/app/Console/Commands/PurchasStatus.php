<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use Log;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class PurchasStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'purchas_status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '修改采购单状态';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $deal_count=0;
        $deal_arr=[];
        $count=DB::table('erp_purchase_order')
            ->where('order_status',3)
            ->where('flag',0)
            ->count();
        $bar = $this->output->createProgressBar($count);
        $list=DB::table('erp_purchase_order')
            ->where('order_status',3)
            ->where('flag',0)
            ->orderBy('id')->get();
        foreach ($list as $item){
                //首先查询该采购单下的所有采购商品
                $goods=DB::table('erp_purchase_order_goods')
                    ->where('flag',0)
                    ->where('order_id',$item->id)
                    ->get();

                //检查每个采购商品是否已经全部收获，是的话就更新采购单状态
                $order_status=4;//发货完成已入库
                foreach ($goods as $good){
                    //物流单详情ids
                    $logistics_info_ids=DB::table('erp_logistics_info')
                        ->where('flag',0)
                        ->where('goods_id',$good->id)
                        ->pluck('id');

                    //收货详数量的和
                    $receive_goods_sum=DB::table('erp_receive_goods_record')
                        ->whereIn('goods_id',$logistics_info_ids)
                        ->where('flag',0)
                        ->sum('true_num');
                    //报废的数量和
                    $scrap_sum=DB::table('erp_logistics_info')
                        ->whereIn('id',$logistics_info_ids)
                        ->sum('scrap_num');
                    //只要有一个采购goods没有收全，就不更新入库状态
                    if ($good->number != ($receive_goods_sum + $scrap_sum)){
                        $order_status=3;//发货完成未入库
                        break;
                    }
                }
                //更新状态
                if ($order_status == 4){
                    DB::table('erp_purchase_order')
                        ->where('id',$item->id)
                        ->update(['order_status'=>$order_status]);
                    $deal_count++;
                    $deal_arr[]=$item->id;
                }

                $bar->advance();
        }
        Log::info('更新状态为4的采购单id为:'.json_encode($deal_arr));
        $bar->finish();
        $this->info('success done-'.$deal_count);
    }
}
