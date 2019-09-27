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

class MakeMpPackageAreaScan extends AbstractTool
{


    public function __construct($id){
        $this -> id = $id;
    }

    public function script(){
        return <<<SCRIPT
        
        $('.makeOrder').on('click', function () {
            var data = $(this).attr('data');
            $.ajax({
                method: 'post',
                url: '/admin/MakeMpPackageAreaOrder',
                data: {
                    _token:LA.token,
                    area_scan_id:data,
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

});
SCRIPT;
    }

    public function render()
    {
        Admin::script($this->script());

        return '<a class="makeOrder" data="'.$this -> id.'"   style="margin-left:12px;font-size:15px;cursor:pointer;">下单</a>';
    }
}