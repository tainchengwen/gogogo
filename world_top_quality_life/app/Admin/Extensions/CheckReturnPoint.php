<?php

namespace App\Admin\Extensions;

use Encore\Admin\Admin;
use Illuminate\Support\Facades\DB;

class CheckReturnPoint
{
    protected $id;

    public function __construct($id)
    {
        $this -> id = $id;
    }

    protected function script()
    {
        return <<<SCRIPT

$('.grid-check-row-up').on('click', function () {

    // Your code.
    //console.log($(this).data('id'));
    
    var id = $(this).data('id');
    if(confirm('你确定通过审核，并且线上返现给他么')){
        
        $.ajax({
            method: 'post',
            url: '/admin/checkReturnPoint',
            data: {
                _token:LA.token,
                id:$(this).data('id'),
                type:1,
                fan_type:'up'
            },
            success: function (data) {
                   
               alert('审核成功');
              location.reload();
              return false;
            }
        });
    }

});


$('.grid-check-row').on('click', function () {

    // Your code.
    //console.log($(this).data('id'));
    
    var id = $(this).data('id');
    if(confirm('你确定通过审核，并且线下返现给他么')){
        
        $.ajax({
            method: 'post',
            url: '/admin/checkReturnPoint',
            data: {
                _token:LA.token,
                id:$(this).data('id'),
                type:1,
                fan_type:'down'
            },
            success: function (data) {
                   
               alert('审核成功');
              location.reload();
              return false;
            }
        });
    }

});

$('.grid-check-rows').on('click', function () {

    // Your code.
    //console.log($(this).data('id'));
    var value = $.trim(prompt('请输入驳回理由'));
    if(!value){
        alert('请输入驳回理由');return false;
    }
    var id = $(this).data('id');
    if(confirm('你确定驳回么')){
        
        $.ajax({
            method: 'post',
            url: '/admin/checkReturnPoint',
            data: {
                _token:LA.token,
                id:$(this).data('id'),
                type:2,
                remark:value
            },
            success: function (data) {
                   
               alert('驳回成功');
              location.reload();
              return false;
            }
        });
    }

});

SCRIPT;
    }

    protected function render()
    {
        Admin::script($this->script());
        return "
<a class='fa  grid-check-row' data-id='{$this->id}'  style='margin-left:10px;cursor: pointer' >通过(线下)</a>
<a class='fa  grid-check-row-up' data-id='{$this->id}'  style='margin-left:10px;cursor: pointer' >通过(线上)</a>
<a class='fa  grid-check-rows' data-id='{$this->id}'  style='margin-left:10px;cursor: pointer' >驳回</a>
";
    }

    public function __toString()
    {
        return $this->render();
    }
}