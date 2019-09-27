<?php

namespace App\Admin\Controllers;

use App\AdminRoute;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Support\Facades\DB;

class AdminRouteSettingController extends Controller
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
            ->header('路线设置')
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
            ->header('路线')
            ->description('创建')
            ->body($this->form());
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new AdminRoute);

        $grid->id('Id');
        $grid->port() -> name('口岸名称');
        $grid->name('路线名称');
        $grid->created_at('创建时间')->display(function($val){
            return date('Y-m-d H:i:s',$val);
        });

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
        $show = new Show(AdminRoute::findOrFail($id));

        $show->id('Id');
        $show->port_id('Port id');
        $show->name('Name');
        $show->created_at('Created at');
        $show->updated_at('Updated at');
        $show->flag('Flag');

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new AdminRoute);

        //$form->number('port_id', '口岸');


        $ports = DB::table('port')
            -> get();
        $options = [];
        foreach($ports as $k => $vo){
            $options[$vo -> id] = $vo -> name;
        }

        $form -> select('port_id','口岸') -> options($options);
        $form->text('name', '路线名称');

        return $form;
    }
}
