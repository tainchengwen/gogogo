<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateEcharts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:emsCharts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ems update Echarts';

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

        //先清空表
        DB::table('ems_charts') -> delete();
        

        //从今天 倒退20天好了
        for($i=0;$i<=20;$i++){
            if($i == 0){
                $left_time = time();
            }else{
                $left_time = strtotime('-'.$i.' days');
            }

            $right_time = strtotime('-'.($i+1).' days');

            $left_time = strtotime(date('Y-m-d',$left_time));
            $right_time = strtotime(date('Y-m-d',$right_time));

            /*
                'ems_status' => [
                    '1' => '未到达海关',
                    '2' => '放行',
                    '3' => '已退运',
                    '4' => '查验',
                    '5' => '待缴税',
                    '6' => '退运申请已通过',
                    '7' => '已缴税',
                    '8' => '需办理退运',
                    '9' => '退运申请已提交',
                ],
            */
            //需要统计的东西
            $status_type_arr = [
                '1' => 0,
                '2' => 0,
                '3' => 0,
                '4' => 0,
                '5' => 0,
                '6' => 0,
                '7' => 0,
                '8' => 0,
                '9' => 0,
                '10' => 0,
            ];

            $send_orders = DB::table('send_order_list')
                -> where('created_at','>=',$right_time)
                -> where('created_at','<=',$left_time)
                -> get();

            //统计每个交货单的托盘
            if(!count($send_orders)){
                continue;
            }


            foreach($send_orders as $vo){
                $batch_packages = DB::table('batch_packages')
                    -> where([
                        'send_order_id' => $vo -> id
                    ]) -> get();
                if(!count($batch_packages)){
                    continue;
                }

                foreach($batch_packages as $value){
                    //找包裹 与 托盘的关系
                    $batch_packages_relation = DB::table('batch_packages_relation')
                        -> where([
                            'batch_id' => $value -> id
                        ]) -> get();
                    if(!count($batch_packages_relation)){
                        continue;
                    }
                    foreach($batch_packages_relation as $packages){
                        $package_info = DB::table('packages')
                            -> leftJoin('ems_package','packages.id','ems_package.package_id')
                            -> select([
                                'ems_package.id',
                                'ems_package.ems_status',
                            ])
                            -> where([
                                'packages.id' => $packages -> package_id
                            ]) -> first();
                        //统计状态 包裹数量 + 1
                        if($package_info && $package_info -> ems_status){
                            if(isset($status_type_arr[$package_info -> ems_status])){
                                $status_type_arr[$package_info -> ems_status] ++ ;
                            }
                        }
                    }
                }

            }

            //dump($status_type_arr);
            //$right_time
            foreach($status_type_arr as $k => $vo){
                DB::table('ems_charts') -> insertGetId([
                    'date_str' => date('Y-m-d',$right_time),
                    'date_true_str' => $right_time,
                    'status_type' => $k,
                    'num' => $vo,
                    'created_at' => time(),
                    'updated_at' => time(),
                ]);

            }


        }
    }
}
