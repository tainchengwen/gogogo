<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

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
}
