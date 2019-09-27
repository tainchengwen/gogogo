<?php

namespace App\Admin\Extensions;

use Encore\Admin\Admin;
use Illuminate\Support\Facades\DB;

class MakePackageOrder
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
    if(confirm('您确定下单么？')){
    $.ajax({
        method: 'post',
        url: '/admin/mpPakcageNumber',
        data: {
            _token:LA.token,
            id:$(this).data('id'),
        },
        dataType:'json',
        success: function (data) {
               if(data.code == 200){
                alert('下单成功');location.reload();
               }else{
                alert(data.msg);
               }
              
        }
    });
    }else{
    layer.closeAll('loading');
    }
    
});



SCRIPT;


    }

    protected function render()
    {
        Admin::script($this->script());

        $button_str = "<a  data-id='{$this->id}' class='grid-check-row' style='font-size:12px;cursor:pointer;' >下单</a>";
        


        return $button_str;
    }

    public function __toString()
    {
        return $this->render();
    }
}