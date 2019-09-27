<?php

namespace App\Admin\Extensions;

use Encore\Admin\Admin;
use Encore\Admin\Grid\Tools\BatchAction;
use Illuminate\Support\Facades\DB;


class MpAreaOrder extends BatchAction
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
    if(selectedRows().length){
    console.log(selectedRows());
    var order_num=1; 
        if(!$.trim(order_num)){
            return false;
        }
        
        layer.load(1);
        if(!confirm('确定操作么')){
            return false;
        }
        
    $.ajax({
        method: 'post',
        url: '/admin/makeMpAreaOrder',
        dataType:'json',
        data: {
            _token:LA.token,
            ids: selectedRows(),
            order_num:order_num
        },
        success: function (data) {
        layer.closeAll('loading');
           if(data.code == 'error'){
                $.pjax.reload('#pjax-container');
                toastr.error('操作失败,'+data.msg);
           }else if(data.code == 'success'){
                $.pjax.reload('#pjax-container');
                toastr.success('操作成功,'+data.msg);
                setTimeout(function(){ location.reload(); }, 800);
                
           }
            
        }
    });
    }
    
});

EOT;

    }
}