<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class BaseRepository
{

    /**
     * 数组分组
     */
    public function arrayGrouping($obj, $key)
    {
        $result=[];

        foreach($obj as $k=>$v){
            $result[$v->$key][] = $v;
        }

        return $result;
    }

    /**
     * 二维数组根据指定键值排序
     */
    public function arrayMultisortMy($obj, $sortField, $sortBy='ASC'){
        $result = [];

        foreach($obj as $k=>$v){
            $result[$k] = $v->$sortField;
        }
        $sortBy=strtoupper($sortBy);

        $sortBy=='ASC'?asort($result):($sortBy=='DESC'?arsort($result):'');

        foreach($result as $k=>$v){
            $result[$k] = $obj[$k];
        }

        return $result;
    }

    /**
     * 精确加法
     * @param [type] $a [description]
     * @param [type] $b [description]
     */
    function math_add($a,$b,$scale = '2')
    {
        return bcadd($a,$b,$scale);
    }

    /**
     * 精确减法
     * @param [type] $a [description]
     * @param [type] $b [description]
     */
    function math_sub($a,$b,$scale = '2')
    {
        return bcsub($a,$b,$scale);
    }

    /**
     * 精确乘法
     * @param [type] $a [description]
     * @param [type] $b [description]
     */
    function math_mul($a,$b,$scale = '2') 
    {
        return bcmul($a,$b,$scale);
    }

    /**
     * 精确除法
     * @param [type] $a [description]
     * @param [type] $b [description]
     */
    function math_div($a,$b,$scale = '2') 
    {
        return bcdiv($a,$b,$scale);
    }

    /**
     * 精确求余/取模
     * @param [type] $a [description]
     * @param [type] $b [description]
     */
    function math_mod($a,$b) {
        return bcmod($a,$b);
    }

    /**
     * 比较大小
     * @param [type] $a [description]
     * @param [type] $b [description]
     * 大于 返回 1 等于返回 0 小于返回 -1
     */
    function math_comp($a,$b,$scale = '5') {
        return bccomp($a,$b,$scale); // 比较到小数点位数
    }

    /**
     * curl
     * @param [type] $url [description]
     * @param [type] $data [description]
     * url请求地址data请求数据
     */
    public function curl_func($url,$data=[]) 
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);// 设置 POST 参数
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        $respData = curl_exec($curl);
        curl_close($curl);

        return $respData;
    }

}