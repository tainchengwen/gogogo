<?php

namespace App\Admin\Extensions;

use Encore\Admin\Admin;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckRow
{
    protected $id;

    public function __construct($row)
    {
        $this -> row = $row;
    }

    protected function script()
    {
        return <<<SCRIPT

$('.grid-check-row').on('click', function () {

    // Your code.
    console.log($(this).data('id'));

});

SCRIPT;
    }

    protected function render()
    {
        //Admin::script($this->script());
        $rows = $this -> row;
        $status_configs = config('admin.order_status_new');
        $pay_status_configs = config('admin.pay_status_new');

        //根据每个订单 查出所有包裹
        $packages = DB::table('packages') -> where([
            'order_id' => $rows['id'],
            'flag' => 0
        ]) -> get();

        //查出用户
        $userinfo = DB::table('wxuser') -> where([
            'id' => $rows['userid']
        ]) -> first();

        //区域的配置
        //所属区域 取area_name 表
        $areas_arr = [];
        $username = \Encore\Admin\Facades\Admin::user()->username;

        $areas = DB::table('area_name')->get();
        foreach ($areas as $vo) {
            $areas_arr[$vo->id] = $vo->area_name;
        }
        //路线配置
        $route_config = config('admin.route_setting');

        //dump($rows);
        $compact = [
            'orderinfo' => $rows,
            'packages' => $packages,
            'userinfo' => $userinfo,
            'status_configs' => $status_configs,
            'pay_status_configs' => $pay_status_configs,
            'areas_arr' => $areas_arr,
            'username' => $username,
            'route_name' => isset( $packages[0]) && isset($packages[0]->route_id)? '-------路线:'.$route_config[$packages[0]->route_id]:''
        ];
        //Log::info(json_encode($compact));
        $table = view('layouts.admin_table_temp',compact('compact')) -> render();
        return $table;
        //return "<a class='btn btn-xs btn-success fa fa-check grid-check-row' data-id='{$this->id}'>{$this->order_num}</a>";
    }

    public function __toString()
    {
        return $this->render();
    }
}