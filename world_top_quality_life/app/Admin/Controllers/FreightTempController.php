<?php

namespace App\Admin\Controllers;

use App\Admin\Extensions\CancelPackets;
use App\Admin\Extensions\CheckRow;
use App\Admin\Extensions\ExcelExpoter;
use App\Admin\Extensions\ExportApi;
use App\Admin\Extensions\OrderExport;
use App\Admin\Extensions\Trashed;
use App\AreaName;
use App\FreightTemp;
use App\FreightTempName;
use App\Http\Controllers\Controller;
use App\Order;
use Illuminate\Http\JsonResponse;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Widgets\Table;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\DB;
use EasyWeChat\Factory;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;

class FreightTempController extends Controller
{


    public function index(){
        return Admin::content(function (Content $content)  {

            $content->header('运费模板');
            $content->description('列表');

            $content->body($this -> grid());
        });
    }

    protected function grid()
    {
        return Admin::grid(FreightTempName::class, function (Grid $grid) {

            $grid->id('ID');
            $grid -> temp_name('模板名称');
            $grid -> send_address('发货地');
            $grid -> company('快递公司');

            $grid->filter(function($filter){

                // 去掉默认的id过滤器
                $filter->disableIdFilter();

            });


            $grid->actions(function ($actions) {
                $actions->disableDelete();
                //$actions->disableEdit();
                $key = $actions->getKey();
                $addTrUrl = admin_url('addTempTr/'.$key);
                $actions->append('<a href="'.$addTrUrl.'" style="margin-left:8px;"><i class="fa fa-plus-square-o"></i></a>');

            });



            $grid->tools(function ($tools) {
                $tools->batch(function ($batch) {
                    $batch->disableDelete();

                });
            });


            $grid->disableExport();
            //$grid->disableCreateButton();

        });
    }



    public function create()
    {
        return Admin::content(function (Content $content) {

            $content->header('运费模板');
            $content->description('添加');

            $content->body($this->form());
        });
    }

    public function edit($id)
    {
        return Admin::content(function (Content $content) use ($id) {

            $content->header('运费模板');
            $content->description('修改');

            $content->body($this->form($id)->edit($id));
        });
    }

    protected function form($id = null)
    {
        return Admin::form(FreightTempName::class, function (Form $form) use($id){

            $form -> text('temp_name','模板名称') -> rules('required');
            $form -> text('company','快递公司') -> rules('required');
            $form -> text('send_address','发货地') -> rules('required');

            $form->tools(function (Form\Tools $tools) {
                $tools->disableListButton();
            });

            //提交

            if(!$id){
                $form -> setAction(admin_url('addFreightTempName'));
            }else{
                $form -> hidden('id','id');
                $form -> setAction(admin_url('addFreightTempName'));
            }


        });
    }


    public function addFreightTempName(){
        $_POST['temp_name'] = trim($_POST['temp_name']);
        $_POST['company'] = trim($_POST['company']);
        $_POST['send_address'] = trim($_POST['send_address']);

        if(!$_POST['temp_name'] || !$_POST['company'] || !$_POST['send_address'] ){
            admin_toastr('必填','error');

            return redirect(admin_url('freightTemp/create'));
        }


        if(!isset($_POST['id'])){
            //添加
            $isset = DB::table('freight_temp_name') -> where([
                'temp_name' => $_POST['temp_name']
            ]) -> first();
            if($isset){
                admin_toastr('模板名称重复','error');

                return redirect(admin_url('freightTemp/create'));
            }


            $isset = DB::table('freight_temp_name') -> where([
                'temp_name' => $_POST['company']
            ]) -> first();
            if($isset){
                admin_toastr('快递公司名称重复','error');

                return redirect(admin_url('freightTemp/create'));
            }

            DB::table('freight_temp_name') -> insert([
                'temp_name' => $_POST['temp_name'],
                'company' => $_POST['company'],
                'send_address' => $_POST['send_address'],
                'created_at' => time(),
                'updated_at' => time(),
            ]);

            admin_toastr('添加成功');
            return redirect(admin_url('freightTemp'));

        }else{
            //编辑
            $isset = DB::table('freight_temp_name') -> where([
                'temp_name' => $_POST['temp_name']
            ]) -> where('id','<>',$_POST['id']) -> first();
            if($isset){
                admin_toastr('模板名称重复','error');

                return redirect(admin_url('freightTemp/'.$_POST['id'].'/edit'));
            }


            $isset = DB::table('freight_temp_name') -> where([
                'temp_name' => $_POST['company']
            ]) -> where('id','<>',$_POST['id']) -> first();
            if($isset){
                admin_toastr('快递公司名称重复','error');

                return redirect(admin_url('freightTemp/'.$_POST['id'].'/edit'));
            }


            DB::table('freight_temp_name') -> where([
                'id' => $_POST['id']
            ]) -> update([
                'temp_name' => $_POST['temp_name'],
                'company' => $_POST['company'],
                'send_address' => $_POST['send_address'],
                'updated_at' => time(),
            ]);


            admin_toastr('修改成功');
            return redirect(admin_url('freightTemp'));
        }




    }
    public function addTempTr($id){
        return Admin::content(function (Content $content)use($id) {

            $content->header('运费模板');
            $content->description('添加');

            $content->body($this->_form($id));
        });
    }

    protected function _form($id)
    {
        return Admin::form(FreightTemp::class, function (Form $form)use($id) {
            $form->tools(function (Form\Tools $tools) {

                // 去掉跳转列表按钮
                $tools->disableListButton();

                //$tools->add('<a class="btn btn-sm btn-danger" id="addTr"><i class="fa fa-plus-square-o"></i>&nbsp;&nbsp;增加</a>');
                // 添加一个按钮, 参数可以是字符串, 或者实现了Renderable或Htmlable接口的对象实例
                $tools->add('<a class="btn btn-info btn-sm" onClick="javascript :history.back(-1);" ><i class="fa fa-backward"></i>&nbsp;&nbsp;返回</a>');
            });


            $compact = [];
            $province_arr = config('admin.provinces');
            //排除用过的省
            $temp_name_info = DB::table('freight_temp') -> where([
                'temp_name_id' => $id
            ]) -> get();

            //$temp_infos = [];
            if(count($temp_name_info)){





                //排除已用过的
                $address_arr = [];
                foreach($temp_name_info as $vo){
                    $temp_str = $vo -> address;
                    $temp_arr = explode(',',$temp_str);
                    foreach($temp_arr as $value){
                        $address_arr[] = $value;
                    }
                }

                $address_arr = array_unique($address_arr);
                //查到最终数组
                foreach($province_arr as $k => $vo){
                    if(in_array($vo,$address_arr)){
                        unset($province_arr[$k]);
                    }
                }

            }



            $form->disableSubmit();
            $form->disableReset();
            $compact['province_arr'] = $province_arr;
            $compact['temp_name_id'] = $id;
            $compact['temp_name_info'] = $temp_name_info;

            $form -> setAction(admin_url('addFreightTempRes'));
            $view = view('admin.freight_temp',compact('compact')) -> render();
            $form -> html($view)->setWidth(12);




        });
    }


    public function addFreightTempRes(){
        //dd($_POST);
        if(!count($_POST['address'])){
            admin_toastr('省份必填','error');

            return redirect(admin_url('addTempTr').'/'.$_POST['temp_name_id']);
        }


        DB::table('freight_temp') -> insert([
            'temp_name_id' => $_POST['temp_name_id'],
            'address' => implode(',',$_POST['address']),
            'first_weight' => $_POST['first_weight'],
            'first_price' => $_POST['first_price'],
            'secend_weight' => $_POST['secend_weight'],
            'secend_price' => $_POST['secend_price'],
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        admin_toastr('添加成功');

        return redirect(admin_url('addTempTr').'/'.$_POST['temp_name_id']);


    }

    //删除运费模板
    public function deleteFreightTemp($freight_temp_id){
        //删除模板
        $info = DB::table('freight_temp') -> where([
            'id' => $freight_temp_id
        ]) -> first();
        DB::table('freight_temp') -> where([
            'id' => $freight_temp_id
        ]) -> delete();
        admin_toastr('删除成功');

        return redirect(admin_url('addTempTr').'/'.$info -> temp_name_id);

    }

    //编辑运费模板
    public function editFreightTemp(){
        $info = DB::table('freight_temp') -> where([
            'id' => $_GET['freight_temp_id']
        ]) -> first();
        DB::table('freight_temp') -> where([
            'id' => $_GET['freight_temp_id']
        ]) -> update([
            'first_weight' => $_GET['first_weight'],
            'first_price' => $_GET['first_price'],
            'secend_weight' => $_GET['secend_weight'],
            'secend_price' => $_GET['secend_price']
        ]);

        admin_toastr('编辑成功');

        return redirect(admin_url('addTempTr').'/'.$info -> temp_name_id);

    }









}
