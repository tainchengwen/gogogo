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

class OrderExport extends AbstractTool
{
    public function __construct($from_area_admin_name)
    {
        $this -> from_area_admin_name = $from_area_admin_name;
    }

    protected function script()
    {
        $url = Request::fullUrlWithQuery(['gender' => '_gender_']);

        return <<<EOT

$('input:radio.user-gender').change(function () {

    var url = "$url".replace('_gender_', $(this).val());

    $.pjax({container:'#pjax-container', url: url });

});

EOT;
    }

    public function render()
    {
        //Admin::script($this->script());

        $options = [
            'from_area_admin_name'   => $this -> from_area_admin_name ,
        ];

        return view('admin.tools.orderExport', compact('options'));
    }
}