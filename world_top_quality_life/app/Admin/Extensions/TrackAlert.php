<?php

namespace App\Admin\Extensions;

use Encore\Admin\Admin;
use Illuminate\Support\Facades\DB;

class TrackAlert
{
    protected $id;

    public function __construct($id)
    {
        $this -> id = $id;
    }

    protected function script()
    {

            return <<<SCRIPT
        
        $('.grid-check-row').on('click',function(){
    layer.load(1);
    $.ajax({
        method: 'post',
        url: '/admin/getTrackList',
        data: {
            _token:LA.token,
            id:$(this).data('id'),
        },
        success: function (data) {
               
            if(data == 'error'){
                layer.closeAll('loading');
                $.pjax.reload('#pjax-container');
                toastr.success('没有数据');
                return false;
            }   
            
            layer.open({
              type: 1,
              skin: 'layui-layer-rim', //加上边框
              area: ['820px', '500px'], //宽高
              content: data
            });
            layer.closeAll('loading');
            //$.pjax.reload('#pjax-container');
            //toastr.success('操作成功');
        }
    });
});



SCRIPT;


    }

    protected function render()
    {
        Admin::script($this->script());

        //查看下此人是否有内网轨迹 或者外网轨迹


        //return "<a class='btn btn-xs btn-success fa fa-check grid-check-row' data-id='{$this->id}'>{$this->order_num}</a>";
        $button_str = "<a  data-id='{$this->id}' class='grid-check-row' style='font-size:12px;cursor:pointer;' >内网轨迹</a>";
        


        return $button_str;
    }

    public function __toString()
    {
        return $this->render();
    }
}