<?php
/**
 * Created by PhpStorm.
 * User: richer
 * Date: 2018/8/4
 * Time: 10:53
 */

namespace App\Admin\Extensions;

use Encore\Admin\Admin;
use Encore\Admin\Grid\Tools\AbstractTool;
use Illuminate\Support\Facades\Request;

class importFindGoods extends AbstractTool
{


    public function __construct(){

    }

    public function render()
    {
        return '<a class="btn btn-sm btn-primary " href="'.admin_base_path('importFindGoodsExcel').'"><i class="fa fa-remove"></i> 导入</a>';
    }
}