<?php

namespace App\Admin\Extensions;

use Encore\Admin\Admin;
use Illuminate\Support\Facades\DB;

class Vip
{
    protected $id;

    public function __construct($id,$type)
    {
        $this -> type = $type;  //1是设置 2是取消

        $this -> id = $id;
        $this -> action  = admin_url('setVipUser');
    }

    protected function script()
    {
        if($this -> type == 1){
            return <<<SCRIPT
        
        $('.grid-check-row').on('click', function () {

    // Your code.
    console.log($(this).data('id'));
    if(confirm('确定把他设置成星标用户么')){
        $.ajax({
            method: 'post',
            url: '{$this -> action}',
            data: {
                _token:LA.token,
                id:$(this).data('id'),
                type:1
            },
            success: function (data) {
                alert('标记成功');
                location.reload();
            }
        });
    }

});
SCRIPT;
        }elseif($this -> type == 2){
            return <<<SCRIPT
        
        $('.grid-check-row').on('click', function () {

    // Your code.
    console.log($(this).data('id'));
    if(confirm('确定把他取消星标用户么')){
        $.ajax({
            method: 'post',
            url: '{$this -> action}',
            data: {
                _token:LA.token,
                id:$(this).data('id'),
                type:2
            },
            success: function (data) {
                alert('取消标记成功');
                location.reload();
            }
        });
    }

});
SCRIPT;
        }

    }

    protected function render()
    {
        Admin::script($this->script());
        //return "<a class='btn btn-xs btn-success fa fa-check grid-check-row' data-id='{$this->id}'>{$this->order_num}</a>";
        return "<a  data-id='{$this->id}' class='grid-check-row' style='margin-left:8px;font-size:25px;cursor:pointer;' ><i class='fa fa-star-half-o'></i></a>";
    }

    public function __toString()
    {
        return $this->render();
    }
}