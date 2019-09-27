<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\PriceTemp;
use App\WxUser;
use Encore\Admin\Controllers\ModelForm;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Form;
use Illuminate\Support\Facades\DB;

class RouteSettingController extends Controller
{
    use ModelForm;
    public function routeSettingUser($id){
        return Admin::content(function (Content $content) use ($id) {

            $content->header('用户价格模板管理');
            $content->description('配置');

            $content->body($this->_form($id)->edit($id));
        });
    }

    protected function _form($id)
    {
        return Admin::form(WxUser::class, function (Form $form) use($id) {

            $form->display('id', 'ID');
            $form->display('nickname','昵称');
            //找到他此人配置
            $price_setting = DB::table('price_temp') -> where([
                'user_id' => $id
            ]) -> first();

            if($price_setting){
                $form -> text('small_price','<=1kg价格') ->default($price_setting -> small_price);
            }else{
                $form -> text('small_price','<=1kg价格');
            }


            if($price_setting){
                $form -> text('first_price','>1kg价格') ->default($price_setting -> first_price);
            }else{
                $form -> text('first_price','>1kg价格');
            }

            $form -> hidden('id','id');

            if($price_setting){
                $form -> text('price_other','>1kg 续重（0.5kg）') ->default($price_setting -> price_other);
            }else{
                $form -> text('price_other','>1kg 续重（0.5kg）');
            }

            $form -> setAction(admin_base_path('routeSettingRes'));

        });
    }

    //用户价格模板提交结果
    public function routeSettingRes(){
        //保存
        $isset = DB::table('price_temp') -> where([
            'user_id' => $_POST['id']
        ]) -> first();
        if(!$isset){
            DB::table('price_temp') -> insert([
                'user_id' => $_POST['id'],
                'small_price' => floatval($_POST['small_price']),
                'first_price' => floatval($_POST['first_price']),
                'price_other' => floatval($_POST['price_other']),
            ]);
        }else{
            DB::table('price_temp') -> where([
                'user_id' => $_POST['id']
            ]) -> update([
                'small_price' => floatval($_POST['small_price']),
                'first_price' => floatval($_POST['first_price']),
                'price_other' => floatval($_POST['price_other']),
            ]);
        }

        return redirect(admin_base_path('routeSettingUser').'/'.$_POST['id']);

    }

    //后台价格配置模板
    public function index(){
        return Admin::content(function (Content $content) {

            $content->header('成本价格配置');
            $content->description('');

            $content->body($this->grid());
        });
    }


    protected function grid()
    {
        return Admin::grid(PriceTemp::class, function (Grid $grid) {

            $grid->model()->where('user_id', '=', 0);
            $grid->model()->where('area_id', '=', 0);

            $grid->id('ID')->sortable();
            $grid->route_id('路线') -> display(function($value){
                //获取配置
                $route_setting = config('admin.route_setting');
                return $route_setting[$value];
            });

            $grid -> small_price('<=1kg价格');
            $grid -> first_price('>1kg首重');
            $grid -> price_other('>1kg 续重（0.5kg）');

            $grid->disableCreateButton();
            $grid->disableExport();
            $grid->disableRowSelector();



            $grid->actions(function ($actions) {
                $id = $actions->getKey();
                $actions->disableDelete();
            });

            //$grid->updated_at();
        });
    }

    public function edit($id)
    {
        return Admin::content(function (Content $content) use ($id) {

            $content->header('成本价格配置');
            $content->description('编辑');

            $content->body($this->form($id)->edit($id));
        });
    }

    /**
     * Create interface.
     *
     * @return Content
     */
    public function create()
    {
        return Admin::content(function (Content $content) {

            $content->header('成本价格配置');
            $content->description('创建');

            $content->body($this->form());
        });
    }

    protected function form($id = '')
    {
        return Admin::form(PriceTemp::class, function (Form $form) use($id) {

            $form->display('id', 'ID');
            $form -> text('small_price','<=1kg单价');
            $form -> text('first_price','>1kg首重');
            $form -> text('price_other','>1kg 续重（0.5kg）');


        });
    }



}
