<?php

namespace App\Admin\Extensions;

use Encore\Admin\Admin;
use Encore\Admin\Grid\Tools\BatchAction;
use Illuminate\Support\Facades\DB;


class PdfApi extends BatchAction
{
    protected $action;

    public function __construct($action = 1)
    {
        $this->action = $action;
        $this -> pdfpath = asset('pdf');
    }

    public function script()
    {
        return <<<EOT

$('{$this->getElementClass()}').on('click', function() {
    layer.load(1);
    $.ajax({
        method: 'post',
        url: '/admin/pdfApi',
        data: {
            _token:LA.token,
            ids: selectedRows()
        },
        success: function (data) {
        layer.closeAll('loading');
           if(data == 'error'){
                $.pjax.reload('#pjax-container');
                toastr.error('操作失败');
           }else if(data == 'pdftype_error'){
                $.pjax.reload('#pjax-container');
                toastr.error('不能勾选不是同一渠道的订单');
           }else if(data == 'success'){
                $.pjax.reload('#pjax-container');
                toastr.success('操作成功');
           }else{
                window.open('{$this -> pdfpath}/' + data);
           }
            
        }
    });
});

EOT;

    }
}