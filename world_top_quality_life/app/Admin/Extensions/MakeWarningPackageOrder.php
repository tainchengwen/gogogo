<?php

namespace App\Admin\Extensions;

use Encore\Admin\Admin;
use Encore\Admin\Grid\Tools\BatchAction;
use Illuminate\Support\Facades\DB;


//异常件批量下单
class MakeWarningPackageOrder extends BatchAction
{
    protected $action;

    protected $url;

    public function __construct($action = '')
    {
        $this->action = $action;
    }

    public function script()
    {


        return <<<EOT
        
$('{$this->getElementClass()}').on('click', function() {
$.ajax({
        method: 'post',
        url: '/admin/MakeWarningPackageOrder',
        data: {
            _token:LA.token,
            ids: selectedRows(),
        },
        success: function (data) {
            if(data == '500'){
            alert('没有需要下单的数据');
            }else{
            layer.open({
                type: 1,
                title: '批量下单',
                shadeClose: true,
                shade: 0.8,
                area: ['90%','90%'],
                content: data
            });
            }
           
        }
    });
})

EOT;

    }
}