<?php

namespace App\Admin\Extensions;

use Encore\Admin\Admin;
use Encore\Admin\Grid\Tools\AbstractTool;
use Illuminate\Support\Facades\DB;


class MakeMpNumber extends AbstractTool
{
    protected $action;

    public function __construct($action = '')
    {
        $this->action = $action;
    }

    public function script()
    {


        return <<<EOT

$('#makePrint').on('click', function() {
    layer.load(1);
    $.ajax({
        method: 'post',
        url: '/admin/makeTempNumberPdf',
        dataType:'json',
        data: {
            _token:LA.token,
        },
        success: function (data) {
        layer.closeAll('loading');
           if(data.code == 'error'){
                $.pjax.reload('#pjax-container');
                toastr.error('操作失败');
                
           }else if(data.code == 'success'){
                window.open('/admin/makePdf?ids='+ data.msg);return false;
           }
            
        }
    });
});

EOT;

    }


    public function render(){
        //Admin::script($this->script());
        return view('admin.tools.makeMpNumber');

    }

}