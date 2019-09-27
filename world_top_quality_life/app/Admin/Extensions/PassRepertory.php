<?php

namespace App\Admin\Extensions;

use Encore\Admin\Admin;
use Encore\Admin\Grid\Tools\BatchAction;
use Illuminate\Support\Facades\DB;

//批量通过未分配出入库管理
class PassRepertory extends BatchAction
{
    protected $action;

    protected $success_url;

    public function __construct()
    {
        $this -> success_url = admin_url('repertory');
    }

    public function script()
    {
        return <<<EOT

$('{$this->getElementClass()}').on('click', function() {
    if(confirm('确定操作么？')){
        $.ajax({
            method: 'post',
            url: '/admin/PassRepertory',
            data: {
                _token:LA.token,
                ids: selectedRows(),
            },
            dataType:'json',
            success: function (data) {
            if(data.code == 'error'){
                        alert(data.msg);
                        location.reload();
                        return false;
                   }
                   
                $.pjax.reload('#pjax-container');
                toastr.success('操作成功');
                location.href='{$this -> success_url}';
            }
        });
    }
    
    
    
    
});

EOT;

    }
}