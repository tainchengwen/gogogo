<?php

namespace App\Console\Commands;

use Goutte\Client as GoutteClient;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Pool;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class Spider extends Command
{

    protected $signature = 'command:spider {concurrency} {keyWords*}'; //concurrency为并发数  keyWords为查询关键词

    protected $description = 'php spider';

    public function __construct()
    {
        parent::__construct();
    }


    public function handle()
    {
        //
        $concurrency = $this->argument('concurrency');  //并发数
        $keyWords = $this->argument('keyWords');    //查询关键词
        $guzzleClent = new GuzzleClient();
        $client = new GoutteClient();
        $client->setClient($guzzleClent);
        $request = function ($total) use ($client,$keyWords){
            $url='https://shopping.yahoo.co.jp/search?first=1&p=4908049429690';
            yield function () use($client,$url){
                return $client->request('GET',$url);
            };
        };
        //p=4908049429690
        $pool = new Pool($guzzleClent,$request(1),[
            'concurrency' => $concurrency,
            'fulfilled' => function ($response, $index) use ($client){
                $response->reduce(function($node) use ($client){
                    if(isset($node -> span)){
                        Log::info($node -> span);
                    }

                });
            },
            'rejected' => function ($reason, $index){
                $this->error("Error is ".$reason);
            }
        ]);
        //开始爬取
        $promise = $pool->promise();
        $promise->wait();
    }
}