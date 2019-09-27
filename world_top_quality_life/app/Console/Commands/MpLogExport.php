<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class MpLogExport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mpLogExport';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '导出mp_operation_log表中的自营商城商品详情浏览记录';

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
        $logs=DB::table('mp_operation_log')
            ->where('path','like','%shop_mp/spus/%')
            ->where('path','not like','%shop_mp/spus/s%')
            ->where('method','=','GET')
            ->select([
                'path',
                DB::raw('count(*) as total')
            ])
            ->groupBy('path')
            ->orderBy('total','desc')
            ->get();

        //取出mp_spu的id
        $mp_spu_ids=[];
        $pattern='/^\/api\/shop_mp\/spus\/(\d+)$/';
        foreach ($logs as $k=>$log){
            preg_match($pattern,explode('?',$log->path)[0],$matches);
            if (count($matches)!=2){
                unset($logs[$k]);
                continue;
            }
            $log->mp_spu_id=$matches[1];
            $mp_spu_ids[]=$matches[1];
        }
        //取出spu信息
        $spus=DB::table('erp_spu_list')
            ->leftJoin('erp_mp_name_spu_link','erp_spu_list.id','erp_mp_name_spu_link.spu_id')
            ->whereIn('erp_mp_name_spu_link.id',$mp_spu_ids)
            ->select([
                'erp_spu_list.id',
                'erp_spu_list.name',
                'erp_mp_name_spu_link.id as link_id',
            ])
            ->get()->keyBy('link_id')->toArray();

        foreach ($logs as $k=>$log){
            if (isset($spus[$log->mp_spu_id])){
                $log->spu_id=$spus[$log->mp_spu_id]->id;
                $log->spu_name=$spus[$log->mp_spu_id]->name;
            }else{
                unset($logs[$k]);
            }
        }

        $insertData=[];
        foreach ($logs as $log){
            $insertData[]=[
                'total'=>$log->total,
                'spu_id'=>$log->spu_id,
                'spu_name'=>$log->spu_name,
                'link_id'=>$log->mp_spu_id,
            ];
        }
        DB::table('erp_log_temp')->insert($insertData);

        $this->info('success');
    }
}
