<?php

namespace App\Console\Commands;

use App\Jobs\GetMailStatus;
use App\Package;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MailStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:getStatus';

    /**
     * The console command description.
     *
     * @var string
     */
    //关邮e通获取单号状态
    protected $description = 'get mail status';

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
        Log::info('抓取关邮e通开始eeeeeeee');


        //先登录
        $res = Package::eLogin();
        if(!$res){
            Log::info('登录失败-重试第1次');
            $res2 = Package::eLogin();
            if(!$res2){
                Log::info('登录失败-重试第2次');
                exit;
            }
        }



        Log::info('登录成功开始抓取');


        //只拿35天以后的数据
        $time = time() - 86400*35;
        for($i = 0;$i<999999;$i+=10){
            $datas = DB::table('packages')
                -> where('wuliu_num','<>','')
                -> where('created_at','>=',$time)
                -> where('flag',0)
                -> where('is_end_mail',0)
                -> limit(10)
                -> offset($i)
                -> orderBy('id','desc')
                -> select([
                    'id'
                ])
                ->get();

            if(!$datas){
                echo 'ending';exit;
            }
            foreach($datas as $vo){
                Log::info('请求队列开始');
                dispatch(new GetMailStatus(Package::find($vo -> id)));
                sleep(1);

            }
            sleep(2);

            //Log::info('endddd');exit;
        }

    }

}
