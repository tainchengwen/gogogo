<?php

namespace App\Admin\Extensions;

use Encore\Admin\Admin;
use Encore\Admin\Grid\Tools\BatchAction;
use Illuminate\Support\Facades\DB;


class PrintPdf extends BatchAction
{
    protected $action;

    public function __construct($action = '')
    {
        $this->action = $action;
    }

    public function script()
    {


        if($this -> action == 'order'){
            return <<<EOT

$('{$this->getElementClass()}').on('click', function() {
    layer.load(1);
    $.ajax({
        method: 'post',
        url: '/admin/makePrintQueue',
        dataType:'json',
        data: {
            _token:LA.token,
            ids: selectedRows(),
            type: 'order'
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
        }else{
            return <<<EOT

$('{$this->getElementClass()}').on('click', function() {
    var length = $('.package_ids').length;
        var check_arr =  [];



        for(var i = 0 ;i < length; i++){
            if($('.package_ids').eq(i).is(':checked')){
                check_arr.push($('.package_ids').eq(i).attr('data'));
            }
        }
        console.log(check_arr);
        if(!check_arr.length){
            alert('请选择');return false;
        }
        


    layer.load(1);
    $.ajax({
        method: 'post',
        url: '/admin/makePrintQueue',
        dataType:'json',
        data: {
            _token:LA.token,
            check_arr: check_arr,
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
}