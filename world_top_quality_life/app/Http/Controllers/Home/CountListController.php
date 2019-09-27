<?php

namespace App\Http\Controllers\Home;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;


//统计
class CountListController extends Controller
{
    //按照月份统计
    public function monthCount(){
        //所有的区域
        $cache_arr = [];
        $area_names = DB::table('area_name') -> get();
        foreach($area_names as $vo){
            //查找此区域每个月的数据
            $year = 2018;
            $cache_arr[$vo -> id] = [];
            for($i=1;$i<=12;$i++){
                //这个月1号的时间戳
                $time = $year.substr(strval($i+100),1,2);
                $start = strtotime($time.'01');

                //下个月1号的时间戳  如果是12月 找下一年1月1号的时间戳
                if($i == 12){
                    $time_end = ($year+1).'01';
                }else{
                    $time_end = $year.substr(strval($i+1+100),1,2);
                }

                $end = strtotime($time_end.'01');


                //计算成本
                $cost = DB::table('packages')
                    -> join('order','packages.order_id','=','order.id')
                    -> where('packages.created_at','>=',$start)
                    -> where('packages.created_at','<',$end)
                    -> where('order.from_area','=',$vo -> id)
                    -> sum('packages.cost');
                $cache_arr[$vo -> id][$time]['cost'] = $cost;

                //计算销售
                $price = DB::table('packages')
                    -> join('order','packages.order_id','=','order.id')
                    -> where('packages.created_at','>=',$start)
                    -> where('packages.created_at','<',$end)
                    -> where('order.from_area','=',$vo -> id)
                    -> sum('packages.area_price');
                $cache_arr[$vo -> id][$time]['price'] = $price;

                //计算利润
                $profits = $price - $cost;
                $cache_arr[$vo -> id][$time]['profits'] = $profits;
                //月份
                $cache_arr[$vo -> id][$time]['month'] = $i;

            }
        }

        //dd($cache_arr);
        //保存cache

        //$k 区域id
        foreach($cache_arr as $k => $vo){
            //$key 201801 月份
            foreach($vo as $key => $value){
                //dd('monthcache_'.$k.'_'.$key.'_'.$value['month'], $value);
                $cacheKey = 'monthcache_'.$k.'_'.$key.'_'.$value['month'];
                $cacheData = Cache::get($cacheKey);
                if(!$cacheData){
                    Cache::forever($cacheKey,$value);
                }
            }
        }


    }

    //按照天统计
    public function dayCount(){

        $area_names = DB::table('area_name') -> get();
        $cache_arr = [];
        foreach($area_names as $vo){
            //查找此区域每个月的数据
            $year = 2018;
            $cache_arr[$vo -> id] = [];
            for($i=1;$i<=12;$i++){
                //这个月1号的时间戳
                $time = $year.substr(strval($i+100),1,2);
                $month_one = strtotime($time.'01');

                //找到每个月有几天
                $days = date('t',$month_one);
                for($j=1;$j<=$days;$j++){
                    //这一天日期
                    $start = $year.substr(strval($i+100),1,2).substr(strval($j+100),1,2);
                    //下一天日期
                    //如果是最后一天 将下个月日期拿出来
                    if($j == $days){
                        //如果是一年的最后一天 则把下一年第一天拿出来
                        if($i == 12){
                            $end = ($year+1).'01';
                        }else{
                            $end = $year.substr(strval($i+1+100),1,2).substr(strval($j+100),1,2);
                        }

                    }else{
                        $end = $year.substr(strval($i+100),1,2).substr(strval($j+1+100),1,2);
                    }



                    //计算成本
                    $cost = DB::table('packages')
                        -> join('order','packages.order_id','=','order.id')
                        -> where('packages.created_at','>=',strtotime($start))
                        -> where('packages.created_at','<',strtotime($end))
                        -> where('order.from_area','=',$vo -> id)
                        -> sum('packages.cost');
                    $cache_arr[$vo -> id][$start]['cost'] = $cost;

                    //计算销售
                    $price = DB::table('packages')
                        -> join('order','packages.order_id','=','order.id')
                        -> where('packages.created_at','>=',strtotime($start))
                        -> where('packages.created_at','<',strtotime($end))
                        -> where('order.from_area','=',$vo -> id)
                        -> sum('packages.area_price');
                    $cache_arr[$vo -> id][$start]['price'] = $price;

                    //计算利润
                    $profits = $price - $cost;
                    $cache_arr[$vo -> id][$start]['profits'] = $profits;
                    //月份
                    $cache_arr[$vo -> id][$start]['month'] = $start;
                }

            }
        }

        foreach($cache_arr as $k => $vo){
            //$key 20180101 天
            foreach($vo as $key => $value){
                //dd('monthcache_'.$k.'_'.$key.'_'.$value['month'], $value);
                $cacheKey = 'daycache_'.$k.'_'.$key;
                //dd($cacheKey);
                $cacheData = Cache::get($cacheKey);
                if(!$cacheData){
                    Cache::forever($cacheKey,$value);
                }
            }
        }

    }

    public function clearCache(){
        Cache::flush();
    }
}
