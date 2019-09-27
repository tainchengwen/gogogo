<?php

namespace App\Admin\Controllers;

use App\ReturnShop;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class ReturnShopController extends Controller
{
    use HasResourceActions;

    /**
     * Index interface.
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        return $content
            ->header('返点商铺设置')
            ->description('')
            ->body($this->grid());
    }

    /**
     * Show interface.
     *
     * @param mixed $id
     * @param Content $content
     * @return Content
     */
    public function show($id, Content $content)
    {
        return $content
            ->header('Detail')
            ->description('description')
            ->body($this->detail($id));
    }

    /**
     * Edit interface.
     *
     * @param mixed $id
     * @param Content $content
     * @return Content
     */
    public function edit($id, Content $content)
    {
        return $content
            ->header('Edit')
            ->description('description')
            ->body($this->form()->edit($id));
    }

    /**
     * Create interface.
     *
     * @param Content $content
     * @return Content
     */
    public function create(Content $content)
    {
        return $content
            ->header('Create')
            ->description('description')
            ->body($this->form());
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new ReturnShop);

        $grid->id('Id');
        $grid->shop_name('商铺名称');
        /*
        $grid->city_id('国家') -> display(function($value){
            $config = config('admin.city_id');
            return $config[$value];
        });
        */
        $grid->rate('汇率');
        $grid->bili('返点比例');
        //$grid->flag('Flag');
        $grid->created_at('创建时间') -> display(function($value){
            return date('Y-m-d H:i',$value);
        });
        $grid->updated_at('修改时间')-> display(function($value){
            return date('Y-m-d H:i',$value);
        });
        $grid->actions(function ($actions) {
            $id = $actions->getKey();
            $actions->disableDelete();

        });
        $grid->disableCreateButton();
        $grid->disableExport();
        $grid->disableRowSelector();

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(ReturnShop::findOrFail($id));

        $show->id('Id');
        $show->shop_name('商铺名称');
        $show->rate('汇率');
        $show->bili('返点比例');
        $show->created_at('Created at');
        $show->updated_at('Updated at');

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new ReturnShop);

        $form->text('shop_name', '商铺名称');
        $form->decimal('rate', '汇率');
        $form->decimal('bili', '返点比例');
        //$city_config = config('admin.city_id');
        //$form -> select('city_id','国家') -> options($city_config);
        //$form->number('flag', 'Flag');

        return $form;
    }
}
