<?php

namespace App\Admin\Extensions;

use Encore\Admin\Admin;
use Illuminate\Support\Facades\DB;

class NoPassPackage
{
    protected $id;

    public function __construct($id)
    {
        $this->id = $id;
        $this->url = admin_url('noPassPackage');
    }

    protected function script()
    {
        return <<<SCRIPT

$('.grid-check-row').on('click', function () {

    // Your code.
    console.log($(this).data('id'));
    if(confirm('确定要同步么？')){
        layer.load(1);
        $.ajax({
                method: 'get',
                url: '{$this->url}'+'?send_order_id='+$(this).data('id'),
                data: {
                    _token:LA.token,
                    id:$(this).data('id'),
                },
                success:function(resdata){
                    alert('同步成功');
                    location.reload();
                }
            });
    }
    

});

SCRIPT;
    }

    protected function render()
    {
        //Admin::script($this->script());

        return "<a class='fa  grid-check-row' data-id='{$this->id}' href='".$this -> url.'?send_order_id='.$this -> id."'  target='_blank' style='margin-left:10px;cursor:pointer;' >未过机</a>";
    }

    public function __toString()
    {
        return $this->render();
    }
}