<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MakeSaleSheet extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:salesheet';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make sale sheet';

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
        //
        Log::info('开始更新销售日报');
        $date_str = intval(strtotime(date('Y-m-d')));
        $stock_orders = DB::table('erp_stock_order') -> where([
            'flag' => 0
        ])
            -> select([
                'sale_user_id',
                'pay_price'
            ])
            -> where('pay_price','>',0)
            -> where('created_at','>=',$date_str- 86400 )
            -> where('created_at','<=',$date_str)
            -> get();

        $temp = []; //销售日报 统计销售金额

        $temp_number = []; //销售日报 统计销售单数

        foreach($stock_orders as $vo){
            if(isset($temp[$vo -> sale_user_id])){
                $temp[$vo -> sale_user_id] = floatval($temp[$vo -> sale_user_id]) + floatval($vo -> pay_price);
            }else{
                $temp[$vo -> sale_user_id] = floatval($vo -> pay_price);
            }

            if(isset($temp_number[$vo -> sale_user_id])){
                $temp_number[$vo -> sale_user_id] = intval($temp_number[$vo -> sale_user_id]) + 1;
            }else{
                $temp_number[$vo -> sale_user_id] = 1;
            }


        }


        foreach($temp as $k => $vo){
            DB::table('erp_sale_sheet') -> insertGetId([
                'date_str' => $date_str,
                'price' => $vo,
                'number' => $temp_number[$k],
                'created_at' => time(),
                'updated_at' => time(),
                'user_id' => $k
            ]);
        }

        Log::info('结束更新销售日报');


    }
}
