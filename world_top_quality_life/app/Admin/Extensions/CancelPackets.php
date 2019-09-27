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

class CancelPackets extends AbstractTool
{


    public function __construct($from_area_admin_name,$admin_name){
        $this -> from_area_admin_name = $from_area_admin_name;  //所属区域的添加人
        $this -> admin_name = $admin_name; //后台用户名
    }

    public function render()
    {
        //Admin::script($this->script());
        $compact = [
            'from_area_admin_name' => $this -> from_area_admin_name,
            'admin_name' => $this -> admin_name
        ];
        return view('admin.tools.cancelPackets', compact('compact'));
    }
}