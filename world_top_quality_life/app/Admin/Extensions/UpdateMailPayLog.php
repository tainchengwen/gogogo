<?php
/**
 * Created by PhpStorm.
 * User: richer
 * Date: 2018/8/4
 * Time: 10:53
 */

namespace App\Admin\Extensions;

use Encore\Admin\Admin;
use Encore\Admin\Grid\Tools\AbstractTool;
use Illuminate\Support\Facades\Request;

//同步关邮e通已缴税记录
class UpdateMailPayLog extends AbstractTool
{


    public function __construct(){

    }
    public function script(){
        return <<<SCRIPT
        
        $('#updateMailPayLog').on('click',function(){
    
    $.ajax({
        method: 'post',
        url: '/admin/updateMailPayLog',
        data: {
            _token:LA.token,
        },
        success: function (data) {
           alert('正在同步，请稍后');
        }
    });
});



SCRIPT;
    }

    public function render()
    {
        Admin::script($this->script());
        return '<a class="btn btn-sm btn-primary " id="updateMailPayLog"><i class="fa fa-remove"></i> 同步缴税数据</a>';
    }
}