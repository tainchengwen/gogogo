<?php

namespace App\Admin\Controllers;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;
use App\Price;

class MakePriceController extends Controller
{
    use ModelForm;
    public function index()
    {
        return Admin::content(function (Content $content) {

            $content->header('返点设置');
            $content->description('');

            $content->body($this->grid());
        });
    }

    /**
     * Edit interface.
     *
     * @param $id
     * @return Content
     */
    public function edit($id)
    {
        return Admin::content(function (Content $content) use ($id) {

            $content->header('返点设置');
            $content->description('编辑');

            $content->body($this->form()->edit($id));
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

            $content->header('header');
            $content->description('description');

            $content->body($this->form());
        });
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Admin::grid(Price::class, function (Grid $grid) {

            $grid->id('ID')->sortable();
            $grid->class_num('等级') -> display(function($value){
               $configs = config('admin.user_class');
               return $configs[$value];
            });
            $grid->price_point('返点');
            $grid->bili('优惠比例');
            $grid->disableExport();
            $grid->disableCreateButton();
            //$grid->disableActions();
            $grid->disableRowSelector();
            $grid->filter(function($filter){

                // 去掉默认的id过滤器
                $filter->disableIdFilter();

            });
            $grid->actions(function ($actions) {
                //$id = $actions->getKey();
                $actions->disableDelete();
                //$actions->disableEdit();

            });
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Admin::form(Price::class, function (Form $form) {

            $form->display('id', 'ID');
            $form -> display('class_num','等级') -> with(function($value){
                $configs = config('admin.user_class');
                return $configs[$value];
            });
            $form -> text('price_point','返点');
            $form -> text('bili','比例');
        });
    }


}
