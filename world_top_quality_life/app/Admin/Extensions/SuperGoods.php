<?php

namespace App\Admin\Extensions;

use Encore\Admin\Admin;
use Encore\Admin\Grid\Tools\AbstractTool;
use Illuminate\Support\Facades\DB;


class SuperGoods extends AbstractTool
{

    public function __construct($url_type)
    {
        $this -> url_type = $url_type;
        //$this->apiPage = admin_url('apiAlertPage');
    }

    public function script()
    {


    }


    public function render()
    {

        //Admin::script($this->script());

        $compact = [
            'url_type' => $this -> url_type
        ];

        return view('admin.tools.superGoods', compact('compact'));
    }
}