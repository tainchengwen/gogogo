<?php

namespace App\Admin\Extensions;

use Encore\Admin\Admin;
use Encore\Admin\Grid\Tools\BatchAction;
use Illuminate\Support\Facades\DB;


class CheckRepertory extends BatchAction
{
    public function __construct($info)
    {
        $this -> info = $info;
    }

    public function script()
    {
        return <<<EOT

$('.pass').on('click', function() {
    // Your code.
    console.log($(this).data('id'));
    var id = $(this).data('id');
    if(confirm('你确定通过此条单号么')){
        layer.load(1);
        $.ajax({
            method: 'post',
            url: '/admin/checkRepertory',
            data: {
                _token:LA.token,
                type:1,
                id:id
            },
            dataType:'json',
            success: function (data) {
                   if(data.code == 'error'){
                        alert(data.msg);
                        location.reload();
                        return false;
                   }
               alert('成功');
              location.href='/admin/repertory?&_scope_=status_wei';
              return false;
            }
        });
    }
});

$('.nopass').on('click', function() {
    // Your code.
    console.log($(this).data('id'));
    var id = $(this).data('id');
    if(confirm('你确定作废此条单号么')){
        layer.load(1);
        $.ajax({
            method: 'post',
            url: '/admin/checkRepertory',
            data: {
                _token:LA.token,
                type:2,
                id:id
            },
            success: function (data) {
                   
               alert('作废成功');
              location.href='/admin/repertory?&_scope_=status_wei';
              return false;
            }
        });
    }
});

EOT;

    }


    public function render()
    {
        Admin::script($this->script());
        $html = "
<a class='fa  grid-check-row pass' data-id='{$this -> info ->id}'  style='margin-left:10px;cursor: pointer' >通过</a>
<a class='fa  grid-check-row nopass' data-id='{$this -> info ->id}'  style='margin-left:10px;cursor: pointer' >作废</a>
";
        if($this -> info -> sub_type){
            $html .= "<a class='fa  grid-check-row' data-id='{$this -> info ->id}'  style='margin-left:10px;cursor: pointer' href='".admin_base_path('repertoryCheckInfo')."?id=".$this -> info -> id."&sub_type=".$this -> info -> sub_type."'  >详情</a>";
        }

        return $html ;
    }

    public function __toString()
    {
        return $this->render();
    }


}