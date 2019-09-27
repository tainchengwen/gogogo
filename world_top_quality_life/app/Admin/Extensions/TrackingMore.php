<?php

namespace App\Admin\Extensions;

use Encore\Admin\Admin;
use Illuminate\Support\Facades\DB;

class TrackingMore
{
    protected $id;

    public function __construct($id)
    {
        $this->id = $id;
        $this->apiUrl = admin_url('trackingMoreData');
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
                method: 'post',
                url: '{$this->apiUrl}',
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
        Admin::script($this->script());

        return "<a class='fa  grid-check-row' data-id='{$this->id}'  style='margin-left:10px;cursor:pointer;' >同步</a>";
    }

    public function __toString()
    {
        return $this->render();
    }
}