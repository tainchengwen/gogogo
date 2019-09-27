<?php

namespace App\Admin\Extensions;

use Encore\Admin\Admin;
use Illuminate\Support\Facades\DB;

class MergeDelete
{
    protected $id;

    public function __construct($id)
    {
        $this->id = $id;
        $this->apiUrl = admin_url('deleteMerge');
    }

    protected function script()
    {
        return <<<SCRIPT

$('.grid-check-row').on('click', function () {

    // Your code.
    console.log($(this).data('id'));
    if(confirm('确定要删除么？')){
        $.ajax({
                method: 'post',
                url: '{$this->apiUrl}',
                data: {
                    _token:LA.token,
                    id:$(this).data('id'),
                },
                success:function(resdata){
                    location.reload();
                }
            });
    }
    

});

SCRIPT;
    }

    protected function render()
    {
        Admin::script($this->script());

        return "<a class='fa fa-trash grid-check-row' data-id='{$this->id}'  style='margin-left:10px;' ></a>";
    }

    public function __toString()
    {
        return $this->render();
    }
}