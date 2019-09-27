<?php

namespace App\Admin\Extensions;

use Encore\Admin\Admin;
use Encore\Admin\Grid\Tools\BatchAction;
use Illuminate\Support\Facades\DB;


class DeclareAll extends BatchAction
{
    protected $action;

    public function __construct($action = 1)
    {
        $this->action = $action;
        $this -> pdfpath = asset('pdf');
    }

    public function script()
    {
        if($this->action == 'package'){
            return <<<EOT

$('{$this->getElementClass()}').on('click', function() {
    
    $.ajax({
        method: 'post',
        url: '/admin/declareAll',
        data: {
            _token:LA.token,
            ids: selectedRows(),
            type:'package'
        },
        success: function (data) {
            layer.open({
                type: 1,
                title: '批量预报',
                shadeClose: true,
                shade: 0.8,
                area: ['90%','90%'],
                content: data,
                end:function(){
                    location.reload();
                }
            });
        }
    });
});

EOT;
        }else{
            return <<<EOT

$('{$this->getElementClass()}').on('click', function() {
    
    $.ajax({
        method: 'post',
        url: '/admin/declareAll',
        data: {
            _token:LA.token,
            ids: selectedRows(),
            type:'order'
        },
        success: function (data) {
            layer.open({
                type: 1,
                title: '批量预报',
                shadeClose: true,
                shade: 0.8,
                area: ['90%','90%'],
                content: data,
                end:function(){
                    location.reload();
                }
            });
        }
    });
});

EOT;
        }



    }
}