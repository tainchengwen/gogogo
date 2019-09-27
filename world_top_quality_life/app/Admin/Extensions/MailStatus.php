<?php

namespace App\Admin\Extensions;

use Encore\Admin\Admin;
use Illuminate\Support\Facades\DB;

//海关状态
class MailStatus
{
    protected $id;

    public function __construct($id)
    {
        $this -> id = $id;
    }

    protected function script()
    {

        return <<<SCRIPT
        
$('.grid-shenbao').on('click',function(){
var id =  $(this).data('id');
layer.open({
    type: 2,
    title: '申报物品修改',
    shadeClose: true,
    shade: 0.8,
    area: ['50%', '50%'],
    content: '/admin/editGoodsParatemer/' + id
});

})
        
        
        
$('.grid-shanchu').on('click',function(){
    var id =  $(this).data('id');
    
    if(confirm('确定要删除么')){
            
            var token = $('meta[name="_token"]').attr('content');
            $.ajax({
                type: 'POST',
                url: '/admin/deletePackage',
                data: {
                    _token:LA.token,
                    package_id:id,
                    type:'packageSearch'
                },
                success: function(data){
                    if(data == 'success'){
                        alert('已删除')
                        location.reload();
                    }
                    if(data == 'notdel'){
                        alert('已存在单号，不允许删除，请执行取消单号操作');
                    }
                    if(data == 'delOrder'){
                        alert('请执行删除订单操作');
                    }
                    if(data == 'fa_nodel'){
                        alert('部分发货、已发货的订单不允许删除');
                    }
                },
                error: function(xhr, type){
                    //alert('Ajax error!')
                }
            });
        }
})
    
        
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

       
$('.grid-wai').on('click',function(){
    layer.load(1);
    $.ajax({
        method: 'post',
        url: '/admin/getTrackListWai',
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
})
        
$('.grid-haiguan').on('click',function(){
    layer.load(1);
    $.ajax({
        method: 'post',
        url: '/admin/getMailStatus',
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
})


SCRIPT;


    }

    protected function render()
    {
        Admin::script($this->script());

        //查看下此人是否有内网轨迹 或者外网轨迹
        $button_str = "<div  data-id='{$this->id}' class='grid-wai' style='font-size:12px;cursor:pointer;color: #3c8dbc;' >外网</div>
<div  data-id='{$this->id}' class='grid-check-row' style='font-size:12px;cursor:pointer;color: #3c8dbc;' >内网</div>
<div  data-id='{$this->id}' class='grid-haiguan' style='font-size:12px;cursor:pointer;color: #3c8dbc;' >海关</div>";
        //$button_str .= "</br><a  data-id='{$this->id}' class='grid-check-row' style='font-size:12px;cursor:pointer;' >内网</a>";
        //$button_str .= "</br><a  data-id='{$this->id}' class='grid-haiguan' style='font-size:12px;cursor:pointer;' >海关状态</a>";

        $button_str .= "<div  data-id='{$this->id}'  class='grid-shanchu' style='font-size:12px;cursor:pointer;color: #3c8dbc;' >删除</div>";
        $button_str .= "<div  data-id='{$this->id}' class='grid-dizhi'    style='font-size:12px;cursor:pointer;color: #3c8dbc;' ><a  href='".admin_base_path('editPacketAddressPage').'/'.$this -> id."' target='_blank'>地址</a></div>";
        $button_str .= "<div  data-id='{$this->id}' class='grid-shenbao' style='font-size:12px;cursor:pointer;color: #3c8dbc;' >申报</div>";
        $button_str .= "<div  data-id='{$this->id}' class='grid-gaizhong'    style='font-size:12px;cursor:pointer;color: #3c8dbc;' ><a  href='".admin_base_path('editPacketPage').'/'.$this -> id."' target='_blank'>改重</a></div>";

        return $button_str;
    }

    public function __toString()
    {
        return $this->render();
    }
}