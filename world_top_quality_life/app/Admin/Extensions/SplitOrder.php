<?php

namespace App\Admin\Extensions;

use Encore\Admin\Admin;
use Encore\Admin\Grid\Tools\BatchAction;
use Illuminate\Support\Facades\DB;


class SplitOrder extends BatchAction
{
    protected $action;

    public function __construct()
    {

    }

    public function script()
    {
        return <<<EOT

$('{$this->getElementClass()}').on('click', function() {
//页面层

    layer.load(1);
    $.ajax({
        method: 'post',
        url: '/admin/splitPackageOrder',
        data: {
            _token:LA.token,
            ids: selectedRows(),
        },
        success: function (data) {
              
        }
    });
});

EOT;

    }
}