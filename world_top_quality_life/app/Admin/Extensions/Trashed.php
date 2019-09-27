<?php

namespace App\Admin\Extensions;

use Encore\Admin\Admin;
use Encore\Admin\Grid\Tools\AbstractTool;
use Illuminate\Support\Facades\Request;

class Trashed extends AbstractTool
{


    public function render()
    {
        $url = admin_url('import');

        return <<<EOT

<div class="btn-group" data-toggle="buttons">
    <label class="btn btn-twitter btn-sm grid-status" onclick="window.open('$url')">
         <i class="fa fa-download"></i>&nbsp;&nbsp;导出
    </label>
</div>

EOT;
    }
}