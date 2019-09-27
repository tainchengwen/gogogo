<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Encore\Admin\Widgets\Tab;
use Encore\Admin\Widgets\Table;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CountListController extends Controller
{
    use ModelForm;
    public function index(){
        $type = '';
        if(isset($_GET['type'])){
            $type = $_GET['type'];
        }
        return Admin::content(function (Content $content) use($type)  {



            $content->header('统计');
            $content->description('');
            $cache_arr = [];
            $cache_name = [];
            //月报 显示12个月的
            $area_names = DB::table('area_name') -> get();

            if($type == 'month'){
                $year = 2018;
                for($i=1;$i<=12;$i++){
                    foreach($area_names as $key => $vo){
                        //这个月1号的时间戳
                        $time = $year.substr(strval($i+100),1,2);

                        //下个月1号的时间戳  如果是12月 找下一年1月1号的时间戳
                        if($i == 12){
                            $time_end = ($year+1).'01';
                        }else{
                            $time_end = $year.substr(strval($i+1+100),1,2);
                        }

                        //获取缓存数组拼接
                        $cacheKey = 'monthcache_'.$vo -> id.'_'.$time.'_'.$i;
                        $cacheData = Cache::get($cacheKey);

                        if($cacheData){
                            $cache_arr[$i][$vo -> id]['cost'] = round($cacheData['cost'],2);

                            $cache_arr[$i][$vo -> id]['price'] = round($cacheData['price'],2);

                            $cache_arr[$i][$vo -> id]['profits'] = round($cacheData['profits'],2);
                        }else{
                            $cache_arr[$i][$vo -> id]['cost'] = 0;

                            $cache_arr[$i][$vo -> id]['price'] = 0;

                            $cache_arr[$i][$vo -> id]['profits'] = 0;
                        }



                        //月份
                        $cache_arr[$i][$vo -> id]['month'] = $i;

                        $cache_name[$vo -> id] = $vo -> area_name;
                    }
                }

            }elseif($type == 'week'){
                if(isset($_GET['year']) && $_GET['year']){
                    $year = $_GET['year'];
                }else{
                    $year = date('Y');
                }

                if(isset($_GET['week']) && $_GET['week']){
                    $week = $_GET['week'];
                }else{
                    $week = date('W');
                }



                $start_week = $this -> weekday($year,$week);
                $start_date = $start_week['start'];
                $end_date = $start_week['end'];

                for($i = $start_date;$i <= $end_date;$i+=86400){
                    foreach($area_names as $key => $vo){
                        //这天时间戳
                        //$time = $year.substr(strval($i+100),1,2);
                        $time = date('Ymd',$i);
                        $month = intval(date('m',$i));

                        //获取缓存数组拼接
                        $cacheKey = 'daycache_'.$vo -> id.'_'.$time;
                        $cacheData = Cache::get($cacheKey);
                        //dump($time);
                        if($cacheData){
                            $cache_arr[$i][$vo -> id]['cost'] = round($cacheData['cost'],2);

                            $cache_arr[$i][$vo -> id]['price'] = round($cacheData['price'],2);

                            $cache_arr[$i][$vo -> id]['profits'] = round($cacheData['profits'],2);
                        }else{
                            $cache_arr[$i][$vo -> id]['cost'] = 0;

                            $cache_arr[$i][$vo -> id]['price'] = 0;

                            $cache_arr[$i][$vo -> id]['profits'] = 0;
                        }



                        //月份
                        $cache_arr[$i][$vo -> id]['month'] = $i;

                        $cache_name[$vo -> id] = $vo -> area_name;
                    }
                }






            }else{
                if(isset($_GET['layDate']) && $_GET['layDate']){
                    $date_form = $_GET['layDate'];
                }else{
                    //取每个月的
                    $date_form = date('Ym');
                }


                //看下7月份有几天
                $days = date('t',strtotime($date_form.'01'));
                for($i = 1 ;$i <= $days;$i++){
                    foreach($area_names as $key => $vo){
                        $date = $date_form.substr(strval($i+100),1,2);
                        $cacheKey = 'daycache_'.$vo -> id.'_'.$date;
                        $cacheData = Cache::get($cacheKey);
                        //dd($cacheKey);
                        if($cacheData){
                            $cache_arr[$date][$vo -> id]['cost'] = round($cacheData['cost'],2);

                            $cache_arr[$date][$vo -> id]['price'] = round($cacheData['price'],2);

                            $cache_arr[$date][$vo -> id]['profits'] = round($cacheData['profits'],2);
                        }else{
                            $cache_arr[$date][$vo -> id]['cost'] = 0;

                            $cache_arr[$date][$vo -> id]['price'] = 0;

                            $cache_arr[$date][$vo -> id]['profits'] = 0;
                        }
                        $cache_name[$vo -> id] = $vo -> area_name;
                    }

                }

            }




            $view = view('admin.admin_count_list',[
                'cache_arr' => $cache_arr,
                'area_names'=>$area_names,
                'cache_name' => $cache_name,
                'year' => isset($year)?$year:'',
                'week' => isset($week)?$week:'',
                'start_date' => isset($start_date)?$start_date:0,
            ]) -> render();
            $content->body($view);


            /*
            $content->header('统计');
            $content->description('');

            $tab = new Tab();
            $headers = ['Id', 'Email', 'Name', 'Company'];
            $rows = [
                [1, 'labore21@yahoo.com', 'Ms. Clotilde Gibson', 'Goodwin-Watsica'],
                [2, 'omnis.in@hotmail.com', 'Allie Kuhic', 'Murphy, Koepp and Morar'],
                [3, 'quia65@hotmail.com', 'Prof. Drew Heller', 'Kihn LLC'],
                [4, 'xet@yahoo.com', 'William Koss', 'Becker-Raynor'],
                [5, 'ipsa.aut@gmail.com', 'Ms. Antonietta Kozey Jr.'],
            ];

            $table = new Table($headers, $rows);

            $tab->add('Pie', 'adfsadfadadsf');
            $tab->add('Table', $table);
            $tab->add('Text', 'blablablabla....');



            //$view = view('admin.admin_order_packets',compact('packages')) -> render();
            $content->body($tab->render());
            */


        });
    }



    /**
     * 获取某年第几周的开始日期和结束日期
     * @param int $year
     * @param int $week 第几周;
     */
    public function weekday($year,$week=1){
        $year_start = mktime(0,0,0,1,1,$year);
        $year_end = mktime(0,0,0,12,31,$year);

        // 判断第一天是否为第一周的开始
        if (intval(date('W',$year_start))===1){
            $start = $year_start;//把第一天做为第一周的开始
        }else{
            $week++;
            $start = strtotime('+1 monday',$year_start);//把第一个周一作为开始
        }

        // 第几周的开始时间
        if ($week===1){
            $weekday['start'] = $start;
        }else{
            $weekday['start'] = strtotime('+'.($week-0).' monday',$start);
        }

        // 第几周的结束时间
        $weekday['end'] = strtotime('+1 sunday',$weekday['start']);
        if (date('Y',$weekday['end'])!=$year){
            $weekday['end'] = $year_end;
        }
        return $weekday;
    }

    /**
     * 计算一年有多少周，每周从星期一开始，
     * 如果最后一天在周四后（包括周四）算完整的一周，否则不计入当年的最后一周
     * 如果第一天在周四前（包括周四）算完整的一周，否则不计入当年的第一周
     * @param int $year
     * return int
     */
    public function week($year){
        $year_start = mktime(0,0,0,1,1,$year);
        $year_end = mktime(0,0,0,12,31,$year);
        if (intval(date('W',$year_end))===1){
            return date('W',strtotime('last week',$year_end));
        }else{
            return date('W',$year_end);
        }
    }


}
