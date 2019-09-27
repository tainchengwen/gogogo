<?php

namespace App\Admin\Extensions;

use Encore\Admin\Admin;
use Encore\Admin\Grid\Tools\BatchAction;
use Illuminate\Support\Facades\DB;


class PdfApip extends BatchAction
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
        url: '/admin/pdfApi',
        data: {
            _token:LA.token,
            check_arr: check_arr
        },
        success: function (data) {
        layer.closeAll('loading');
           if(data == 'error'){
                $.pjax.reload('#pjax-container');
                toastr.error('操作失败');
           }else if(data == 'pdftype_error'){
                $.pjax.reload('#pjax-container');
                toastr.error('不能勾选不是同一渠道的包裹');
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