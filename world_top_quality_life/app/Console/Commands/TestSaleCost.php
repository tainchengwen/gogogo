<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestSaleCost extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:cost';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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


        for($i = 0;$i<=10;$i++){
            if($i == 0){
                $date_str = intval(strtotime(date('Y-m-d')));
            }else{
                $date_str = intval(strtotime(date('Y-m-d'))) - $i * 86400;
            }

            echo $date_str;
            echo "\n";

            //所有的事业部
            $business_info = DB::table('erp_business') -> get();


            foreach($business_info as $value){

                $erp_stock_order_info = DB::table('erp_stock_order_info')
                    -> leftJoin('erp_stock_order','erp_stock_order_info.stock_order_id','erp_stock_order.id')
                    -> leftJoin('erp_product_list','erp_product_list.id','erp_stock_order_info.product_id')
                    -> leftJoin('erp_product_class','erp_product_class.id','erp_product_list.class_id')
                    -> leftJoin('erp_stock_order_info_receive','erp_stock_order_info.id','erp_stock_order_info_receive.stock_order_info_id')
                    -> leftJoin('erp_receive_goods_record','erp_receive_goods_record.id','erp_stock_order_info_receive.receive_goods_record_id')
                    -> where('erp_stock_order_info.created_at','>=',$date_str- 86400 )
                    -> where('erp_stock_order_info.created_at','<=',$date_str)
                    -> where('erp_stock_order.pay_status',1)
                    -> where('erp_stock_order.business_id',$value -> id)
                    -> where([
                        'erp_stock_order.flag' => 0,
                        'erp_stock_order_info.flag' => 0
                    ])
                    -> select([
                        'erp_product_class.name as class_name',
                        'erp_stock_order_info.number',
                        'erp_stock_order_info.price',
                        'erp_receive_goods_record.cost'
                    ])
                    -> get();



                $temp = [];
                foreach($erp_stock_order_info as $vo){
                    if(!isset($temp[$vo -> class_name])){
                        $temp[$vo -> class_name]['cost'] = intval($vo -> number) * floatval($vo -> cost);
                        $temp[$vo -> class_name]['price'] = intval($vo -> number) * floatval($vo -> price);
                    }else{
                        $temp[$vo -> class_name]['cost'] = floatval($temp[$vo -> class_name]['cost'])  + intval($vo -> number) * floatval($vo -> cost);
                        $temp[$vo -> class_name]['price'] = floatval($temp[$vo -> class_name]['price'])  + intval($vo -> number) * floatval($vo -> price);
                    }
                }

                foreach($temp as $k => $vo){
                    DB::table('erp_sale_cost_sheet') -> insertGetId([
                        'class_name' => $k,
                        'price' => $vo['price'],
                        'cost' => $vo['cost'],
                        'date_str' => date('Ymd',$date_str),
                        'created_at' => time(),
                        'updated_at' => time(),
                        'business_id' => $value -> id
                    ]);
                }

            }




        }


        //$date_str = intval(strtotime(date('Y-m-d')));




    }
}
