<?php
/**
 * Created by PhpStorm.
 * User: richer
 * Date: 2018/8/4
 * Time: 10:53
 */

namespace App\Admin\Extensions;

use Encore\Admin\Admin;
use Encore\Admin\Grid\Tools\AbstractTool;
use Illuminate\Support\Facades\Request;

class BatchScreen extends AbstractTool
{

    private $type;

    public function __construct($type){
        $this -> type = $type;
    }

    public function render()
    {
        $type = $this -> type;
        return view('admin.tools.BatchScreen',compact('type'));
    }
}