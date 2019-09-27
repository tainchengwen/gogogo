<?php

namespace App\Admin\Extensions;

use Encore\Admin\Admin;
use Encore\Admin\Grid\Tools\BatchAction;
use Illuminate\Support\Facades\DB;


class PrintMpPdf extends BatchAction
{
    protected $action;

    public function __construct($action = '')
    {
        $this->action = $action;
    }

    public function script()
    {


        return <<<EOT

$('{$this->getElementClass()}').on('click', function() {
window.open('/admin/printMpPdf?ids='+ selectedRows());return false;
    layer.load(1);
    $.ajax({
        method: 'post',
        url: '/admin/printMpPdf',
        dataType:'json',
        data: {
            _token:LA.token,
            ids: selectedRows(),
        },
        success: function (data) {
        layer.closeAll('loading');
           if(data.code == 'error'){
                $.pjax.reload('#pjax-container');
                toastr.error('操作失败,'+data.msg);
           }else if(data.code == 'success'){
                $.pjax.reload('#pjax-container');
                toastr.success('操作成功,'+data.msg);
           }
            
        }
    });
});

EOT;

    }
}