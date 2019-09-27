<?php

namespace App\Admin\Extensions;

use Encore\Admin\Admin;
use Encore\Admin\Grid\Tools\BatchAction;
use Illuminate\Support\Facades\DB;


class PackageSearchPrintPdf extends BatchAction
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
    layer.load(1);
    $.ajax({
        method: 'post',
        url: '/admin/makePrintQueue',
        dataType:'json',
        data: {
            _token:LA.token,
            check_arr: selectedRows(),
            type: 'package'
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