<?php

namespace App\Admin\Extensions;

use Encore\Admin\Admin;
use Encore\Admin\Grid\Tools\BatchAction;
use Illuminate\Support\Facades\DB;

class UpGoods extends BatchAction
{
    protected $action;

    public function __construct($action = 1,$url_type)
    {
        $this->action = $action;
        $this -> url_type = $url_type;
    }

    public function script()
    {
        return <<<EOT

$('{$this->getElementClass()}').on('click', function() {
    if(confirm('确定操作么？')){
        $.ajax({
            method: 'post',
            url: '/admin/upGoods',
            data: {
                _token:LA.token,
                action: {$this->action},
                url_type: {$this->url_type},
                ids: selectedRows(),
            },
            success: function () {
                $.pjax.reload('#pjax-container');
                toastr.success('操作成功');
            }
        });
    }
    
    
    
    
});

EOT;

    }
}