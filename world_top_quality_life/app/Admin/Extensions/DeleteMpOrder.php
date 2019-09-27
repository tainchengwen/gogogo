<?php

namespace App\Admin\Extensions;

use Encore\Admin\Admin;
use Encore\Admin\Grid\Tools\BatchAction;
use Illuminate\Support\Facades\DB;


class DeleteMpOrder extends BatchAction
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
if(confirm('确定要删除么')){
    layer.load(1);
    $.ajax({
        method: 'post',
        url: '/admin/DeleteMpOrder',
        dataType:'json',
        data: {
            _token:LA.token,
            ids: selectedRows(),
        },
        success: function (data) {
        layer.closeAll('loading');
           if(data.code == '200'){
                $.pjax.reload('#pjax-container');
                toastr.success('操作成功,'+data.msg);
                location.record()
                
           }else{
                $.pjax.reload('#pjax-container');
                toastr.error('操作失败');
           }
            
        }
    });
}
})

EOT;

    }
}