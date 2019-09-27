<?php

namespace App\Admin\Controllers;

use App\Zips;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;
use Illuminate\Support\Facades\DB;

class ZipsController extends Controller
{
    use ModelForm;

    /**
     * Index interface.
     *
     * @return Content
     */
    public function index()
    {
        return Admin::content(function (Content $content) {

            $content->header('邮编管理');
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

            $content->header('header');
            $content->description('description');

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
        return Admin::grid(Zips::class, function (Grid $grid) {

            //$grid->disableFilter();
            $grid -> model() -> orderBy('city');
            $grid->filter(function($filter){

                // 去掉默认的id过滤器
                //$filter->disableIdFilter();
                //$filter -> useModal();

                //筛选所有的省
                $province = DB::table('zips') -> select([
                    'city'
                ]) ->  groupBy('city')  -> get();

                $province_set = [];
                foreach($province as $vo){
                    $province_set[$vo -> city] =$vo -> city;
                }
                //区域筛选
                $filter->where(function ($query) {
                    $input = $this->input;
                    $query->where('city','=',"{$input}");
                }, '省')->select($province_set);


            });

            $grid->disableExport();
            $grid->disableRowSelector();
            //$grid->id('ID')->sortable();
            $grid->province('市');
            $grid->city('省');
            $grid->zip_code('邮编');

        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Admin::form(Zips::class, function (Form $form) {

            $form->display('id', 'ID');

            $form -> text('city','省');
            $form -> text('province','市');
            $form -> text('zip_code','邮编');
        });
    }
}
