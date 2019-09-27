<?php

namespace App\Admin\Extensions;

use Encore\Admin\Admin;
use Encore\Admin\Grid\Tools\BatchAction;
use Illuminate\Support\Facades\DB;


class CheckReturn extends BatchAction
{
    public function __construct($id)
    {
        $this -> id = $id;
    }

    public function script()
    {
        return <<<EOT

$('{$this->getElementClass()}').on('click', function() {
    // Your code.
    console.log($(this).data('id'));
    var id = $(this).data('id');
    if(confirm('你确定返点么')){
        layer.load(1);
        $.ajax({
            method: 'post',
            url: '/admin/checkReturnPoint',
            data: {
                _token:LA.token,
                id: {$this -> id}
            },
            success: function (data) {
                   
               alert('审核成功');
              location.reload();
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
        return "<a class='fa  grid-check-row' data-id='{$this->id}'  style='margin-left:10px;cursor: pointer' >审核</a>";
    }


}