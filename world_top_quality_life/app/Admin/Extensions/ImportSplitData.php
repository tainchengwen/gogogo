<?php
/**
 * Created by PhpStorm.
 * User: richer
 * Date: 2018/8/4
 * Time: 10:53
 */

namespace App\Admin\Extensions;

use App\SplitOrderQueue;
use Encore\Admin\Admin;
use Encore\Admin\Grid\Tools\AbstractTool;
use Illuminate\Support\Facades\Request;

class ImportSplitData extends AbstractTool
{


    public function __construct(){

    }

    protected function script(){
        return <<<EOT
$('.makeOrderPage').click(function(){
    var data = $(this).attr('data');
    if(confirm('你确定要将拆单后序号为'+data + '的包裹下单么？')){
        $.ajax({
                method: 'post',
                url: '/admin/splitOrder',
                data: {
                    _token:LA.token,
                    data:data,
                },
                dataType:'json',
                success:function(data){
                    layer.closeAll('loading');
                    alert(data.msg);
                    location.reload();
                }
            });
    }
})


EOT;
    }


    public function render()
    {
        Admin::script($this->script());
        $queue_data = SplitOrderQueue::orderBy('id','desc') -> first();
        if($queue_data){
            $time = $queue_data -> created_at->toDateTimeString();
        }else{
            $time = '';
        }

        $html = '
        <a class="btn btn-sm btn-primary " href="'.admin_base_path('importSplitPackage').'" ><i class="fa fa-remove"></i> 导入拆分包裹重量</a>
        <a class="btn btn-sm btn-primary " href="'.admin_base_path('importNoSplitPackage').'" ><i class="fa fa-remove"></i> 导入不允许拆的单号</a>
        <div class="btn-group ">
    <a class="btn btn-sm  btn-info"><i class="fa fa-anchor"></i> 下单</a>
    <button type="button" class="btn btn-sm btn-info dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
        <span class="caret"></span>
        <span class="sr-only">Toggle Dropdown</span>
    </button>
    <ul class="dropdown-menu" role="menu">
        <li><a class="makeOrderPage"  data="01" >序号01</a></li>
        <li><a class="makeOrderPage"  data="02" >序号02</a></li>
        <li><a class="makeOrderPage"  data="03" >序号03</a></li>

    </ul>
</div>
        ';
        if($time){
            $html.= '<h3>上次下单时间：'.$time.'，序号为：'.sprintf('%02s',$queue_data -> no).'</h3>';
        }

        return $html;
    }
}