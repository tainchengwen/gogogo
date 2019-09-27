<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use Log;
use App\Repositories\OssRepository;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class UploadImg2Oss extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'upload2oss {table_name} {--dir=upload/} {--field=image}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '传输img到oss';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(OssRepository $ossRepository)
    {
        parent::__construct();
        $this->oss=$ossRepository;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $table=$this->argument('table_name');
        //创建备份目录
        if(!file_exists(storage_path('backup/sql'))){
            mkdir(storage_path('backup/sql'),0777,true);
        }
        if(!file_exists(storage_path('backup/img/'.$table))){
            mkdir(storage_path('backup/img/'.$table),0777,true);
        }
        //备份数据表
        /*$this->process = new Process(sprintf(
            'mysqldump -h%s -u%s -p%s %s %s > %s',
            config('database.connections.mysql.host'),
            config('database.connections.mysql.username'),
            config('database.connections.mysql.password'),
            config('database.connections.mysql.database'),
            $table,
            storage_path('backup/sql/'.$table.'.sql')
        ));
        try {
            $this->process->mustRun();

            $this->info('The table has been proceed successfully.');
        } catch (ProcessFailedException $exception) {
            $this->error($exception->getMessage());
        }*/

        //转移到oss
        $dir = $this->option('dir');
        $field=$this->option('field');
        $count=DB::table($table)->count();
        $bar = $this->output->createProgressBar($count);
        DB::table($table)->orderBy('id')->chunk(100, function ($items)use($bar,$table,$dir,$field) {
            foreach ($items as $item) {
                //不处理不存在的或者已经在oss的文件
                if(!$item->$field || starts_with($item->$field,'ali_oss:')){
                    Log::info('TABLE:'.$table.'---ID:'.$item->id.'---null or transferd');
                    $bar->advance();
                    continue;
                }
                $localfile=public_path('uploads/images/'.$item->$field);

                if(file_exists($localfile)){
                    $new_path=$this->oss->uploadFile($localfile,$dir);
                    //更新数据库
                    DB::table($table)->where('id',$item->id)->update([$field=>$new_path]);
                    //备份文件
                    copy($localfile,storage_path('backup/img/'.$table.'/'.$item->$field));
                    //删除文件
                    @unlink($localfile);
                }else{
                    Log::info('TABLE:'.$table.'---ID:'.$item->id.'---no loacal file');
                    //$this->error('id:'.$item->id.'的本地图片不存在');
                }
                $bar->advance();
            }
        });
        $bar->finish();
        $this->info('success done');
    }
}
