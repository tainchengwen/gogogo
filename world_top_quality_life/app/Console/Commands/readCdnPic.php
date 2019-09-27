<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class readCdnPic extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'readCdnPic {--start=1/} ';

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
        set_time_limit(0);
        $start = $this->option('start');
        $pic = DB::table('erp_product_list')->where('image','like','%ali_oss:sku_img%')->offset($start)->limit(10000)->pluck('image')->toArray();
        $bar = $this->output->createProgressBar(count($pic));
        foreach ($pic as $v){
            $a = str_replace('https://fenithcdn.oss-cn-shanghai.aliyuncs','http://cdn.fenith',getImageUrl($v));
            file_get_contents($a);
            $bar->advance();

        }
        $this->info('success done '.count($pic));

    }
}
