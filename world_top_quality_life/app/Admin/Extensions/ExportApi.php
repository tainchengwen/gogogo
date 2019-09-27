<?php

namespace App\Admin\Extensions;

use Encore\Admin\Admin;
use Encore\Admin\Grid\Tools\BatchAction;
use Illuminate\Support\Facades\DB;


class ExportApi extends BatchAction
{
    protected $action;

    public function __construct($action = 1)
    {
        $this->action = $action;
        $this->apiPage = admin_url('apiAlertPage');
    }

    public function script()
    {
        return <<<EOT

$('{$this->getElementClass()}').on('click', function() {
//页面层

    layer.load(1);
    $.ajax({
        method: 'post',
        url: '/admin/exportApi',
        data: {
            _token:LA.token,
            ids: selectedRows(),
            action: {$this->action}
        },
        success: function (data) {
               
            if(data == 'error'){
                layer.closeAll('loading');
                $.pjax.reload('#pjax-container');
                toastr.success('操作失败');
                return false;
            }   
            
            console.log($.parseJSON(data).length);
                    if(!$.parseJSON(data).length){
                    layer.closeAll('loading');
                        alert('未付款状态不可以生成单号');return false;
                    }
               
            $.ajax({
                method: 'post',
                url: '{$this->apiPage}',
                data: {
                    _token:LA.token,
                    data:data,
                    type:'post'
                },
                success:function(resdata){
                    layer.closeAll('loading');
                   
                    layer.open({
                      type: 1, 
                      area: ['80%', '80%'], //宽高
                      content: resdata,
                      end:function(){
                            location.reload();
                        }
                    }); 
                }
            });
            
            
            
        
        
            //layer.closeAll('loading');
            //$.pjax.reload('#pjax-container');
            //toastr.success('操作成功');
        }
    });
});

EOT;

    }
}