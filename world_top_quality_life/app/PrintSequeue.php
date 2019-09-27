<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PrintSequeue extends Model
{
    //
    protected $table = 'print_sequeue';


    protected $dateFormat = 'U';


    //添加打印队列

    /**
     * @param int $type 扫描打印类型 0扫描拆单后的号打印面单  1拆单打印小标签 2扫描单号打印面单 3后台打印 4etk自助打印 5补充打印小标签 6仓库维护重量标记
     * @param $number
     * @param $file_name
     * @param string $printer_num
     * @param int $page_set 纸张配置
     * @param int $page_dir 纸张方向 1横 2竖
     *
     */
    static function addQueue($type = 0,$number,$file_name,$printer_num = '008',$page_dir = 0){
        //扫描打印类型 0扫描拆单后的号打印面单  1拆单打印小标签 2扫描单号打印面单 3后台打印 4etk自助打印 5补充打印小标签 6仓库维护重量标记
        //根据打印类型 判断纸张
        switch ($type){
            case 0: $long = 290;$width = 443;break;
            case 1: $long = 220;$width = 110;break;
            case 2: $long = 290;$width = 443;break;
            case 3: $long = 290;$width = 443;break;
            case 4: $long = 290;$width = 443;break;
            case 5: $long = 220;$width = 110;break;
            case 6: $long = 220;$width = 110;break;
            default:$long = 220;$width = 110;
        }

        //根据打印机编号 判断margin
        $margin_config = config('admin.printer_margin');



        //1、拆标签  2、根据拆单后标签 打印
        //1 扫描什么 开始打印的 2、打印类型 3、
        DB::table('print_sequeue') -> insert([
            'type' => $type,
            'number' => $number,
            'file_name' => $file_name,
            'printer_num' => $printer_num,
            'created_at' => time(),
            'updated_at' => time(),
            'long' => $long,
            'width' => $width,
            'dir' => $page_dir,
            'marginLeft' => isset($margin_config[$printer_num]['marginLeft'])?$margin_config[$printer_num]['marginLeft']:0,
            'marginRight' => isset($margin_config[$printer_num]['marginRight'])?$margin_config[$printer_num]['marginRight']:0,
            'marginTop' => isset($margin_config[$printer_num]['marginTop'])?$margin_config[$printer_num]['marginTop']:0,
            'marginBottom' => isset($margin_config[$printer_num]['marginBottom'])?$margin_config[$printer_num]['marginBottom']:0,
        ]);
    }


    //打印小标签
    static function makeSPdf($sp_number,$show_number = ''){
        if(!$show_number){
            $show_number = $sp_number;
        }
        $selfurl =  $_SERVER['PHP_SELF'];

        $url_arr = explode('/',$selfurl);

        unset($url_arr[count($url_arr) - 1]);
        $public_url = $url_arr;

        $pdfurl = 'http://'.$_SERVER['HTTP_HOST'].'/testPdfPage?sp_number='.$sp_number.'----'.$show_number;
        \Illuminate\Support\Facades\Log::info($pdfurl);
        $path = "  /var/www/html/world_top_quality_life/public/pdf/";
        $time = date('Y-m-d-H-i-s').$sp_number;
        // wkhtmltopdf     --page-width 65 --page-height 65  --disable-smart-shrinking -L 0mm -R 0mm -T 0mm -B 0mm http://hqyp.fenith.com/testPdfPage?sp_number=EK439815435HK-01----20EK439815435HK-01---1.9---ZP948888   /var/www/html/world_top_quality_life/public/pdf/aaaaa1.pdf
        //exec("wkhtmltopdf  --page-width 45 --page-height 45  --disable-smart-shrinking -L 0mm -R 0mm -T 0mm -B 0mm   ".$pdfurl.$path.$time.".pdf 2>&1",$output);
        exec("wkhtmltopdf    --page-width 80 --page-height 40  --disable-smart-shrinking -L 0mm -R 0mm -T 0mm -B 0mm   ".$pdfurl.$path.$time.".pdf 2>&1",$output);
        if(count($output)){
            $save_file = 'http://'.$_SERVER['HTTP_HOST'].implode('/',$public_url).'/pdf/'.$time.'.pdf';
            return $save_file;
        }

    }


    //打印网页

    /**
     * @param $url 网页url
     * @param $returnType 返回类型 0 返回远程地址 1 返回储存路径
     * @param $pageType 页面尺寸类型
     * @return string
     */
    static function printHtml($url,$returnType = 0,$pageType = 1){
        $selfurl =  $_SERVER['PHP_SELF'];
        $url_arr = explode('/',$selfurl);
        unset($url_arr[count($url_arr) - 1]);
        $public_url = $url_arr;
        $time = time().rand(1000,9999);
        $path = "  /var/www/html/world_top_quality_life/public/pdf/";
        //wkhtmltopdf      --disable-smart-shrinking -L 0mm -R 0mm -T 0mm -B 0mm   http://hqyp.fenith.com/

        if($pageType == 1){
            exec("wkhtmltopdf      --disable-smart-shrinking -L 0mm -R 0mm -T 0mm -B 0mm   ".$url.$path.$time.".pdf 2>&1",$output);
        }else{
            exec("wkhtmltopdf    --page-width 80 --page-height 40  --disable-smart-shrinking -L 0mm -R 0mm -T 0mm -B 0mm   ".$url.$path.$time.".pdf 2>&1",$output);
        }

        //exec("wkhtmltopdf     --page-width 90 --page-height 90  --disable-smart-shrinking -L 0mm -R 0mm -T 0mm -B 0mm   ".$url.$path.$time.".pdf 2>&1",$output);
        if(count($output)){
            if($returnType){
                $save_file = '/var/www/html/world_top_quality_life/public/pdf/'.$time.'.pdf';
            }else{
                $save_file = 'http://hqyp.fenith.com/pdf/'.$time.'.pdf';
            }

            //$save_file = 'http://'.$_SERVER['HTTP_HOST'].implode('/',$public_url).'/pdf/'.$time.'.pdf';
            return $save_file;
        }
    }




}
