<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class HopeMarketClass extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hope:marketclass';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hope Market Class';

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
        $users = DB::table('wxuser')
            -> where('hope_date','<>','')
            -> get();
        $now_time = time();
        $number = 0;
        foreach($users as $vo){
            //更新
            $date = strtotime($vo -> hope_date);
            if(intval($now_time) > intval($date)){
                $number ++ ;
                //更新hope等级
                DB::table('wxuser') -> where([
                    'id' => $vo -> id,
                ]) -> update([
                    'market_class' => $vo -> hope_market_class
                ]);
            }
        }
        echo $number;
    }
}
