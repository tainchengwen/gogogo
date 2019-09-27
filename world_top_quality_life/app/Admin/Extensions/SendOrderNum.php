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

//创建
class SendOrderNum extends AbstractTool
{
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
        return view('admin.tools.sendOrderNum');
    }
}