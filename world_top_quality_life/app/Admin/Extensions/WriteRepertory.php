<?php
/**
 * Created by PhpStorm.
 * User: richer
 * Date: 2018/8/4
 * Time: 10:53
 */

namespace App\Admin\Extensions;

use Encore\Admin\Admin;
use Encore\Admin\Grid\Tools\BatchAction;
use Illuminate\Support\Facades\DB;

class WriteRepertory extends BatchAction
{


    public function __construct(){
        $this -> writeRepertoryPage = admin_url('writeRepertoryPage');
    }

    public function script(){
        return <<<SCRIPT
        
        $('{$this->getElementClass()}').on('click', function() {
        
            //先判断 这些货 是不是同一个区域的， 是同一个区域的 
            if(!selectedRows().length){
                alert('没有选择');return false;
            }
            
        
        
            console.log(selectedRows());
           
            layer.open({
                type: 2,
                title: '维护物流信息',
                shadeClose: true,
                shade: 0.8,
                area: ['90%','90%'],
                content: '{$this -> writeRepertoryPage}'+'?ids_str='+selectedRows().join()
            });

});
SCRIPT;
    }

}