<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class CheckIsNew extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'checkisnew';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '检测是不是新人,并改成相应的状态';

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
        $count=DB::table('wxuser')->where('is_new',1)->count();
        $bar = $this->output->createProgressBar($count);

        $total=0;
        DB::table('wxuser')->where('is_new',1)->orderBy('id')->chunk(100, function ($items)use($bar,$total) {
            foreach ($items as $item) {
                $exists=DB::table('erp_stock_order')->where('user_id',$item->id)->exists();
                if ($exists){
                    DB::table('wxuser')->where('id',$item->id)->update(['is_new'=>0]);
                    $total++;
                }
                $bar->advance();
            }
        });
        $bar->finish();
        $this->info('success done '.$total);
    }
}
