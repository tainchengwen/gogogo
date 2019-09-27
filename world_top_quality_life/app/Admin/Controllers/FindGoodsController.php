<?php

namespace App\Admin\Controllers;

use App\Admin\Extensions\importFindGoods;
use App\FindGoods;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Maatwebsite\Excel\Facades\Excel;

class FindGoodsController extends Controller
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
            ->header('查找包裹')
            ->description('列表')
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
        $grid = new Grid(new FindGoods);
        $grid -> model() -> orderBy('id','desc');
        $grid->id('Id');
        $grid->code('单号');
        $grid->status('是否找到') -> display(function($value){
            if($value){
                return '找到';
            }else{
                return '未找到';
            }
        });
        $grid->created_at('创建时间') -> display(function($value){
            return date('Y-m-d H:i',$value);
        });
        $grid->updated_at('查找时间') -> display(function($value){
            if($this -> status){
                return date('Y-m-d H:i',$value);
            }
        });
        $grid->disableExport();
        $grid->tools(function ($tools) {

            $tools -> append(new importFindGoods());

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
        $show = new Show(FindGoods::findOrFail($id));

        $show->id('Id');
        $show->code('单号');
        $show->status('Status');
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
        $form = new Form(new FindGoods);

        $form->text('code', '单号');

        return $form;
    }



    public function importFindGoodsExcel(){
        return Admin::content(function(Content $content){
            $content->header('导入要查找的包裹编号');
            $content->description('');

            $content->body($this -> importFindGoodsExcelForm());
        });
    }


    public  function importFindGoodsExcelForm(){
        $form = new Form(new FindGoods);

        $form->file('filename', 'Excel');
        $form -> setAction(admin_base_path('importFindGoodsExcelRes'));
        return $form;
    }


    public function importFindGoodsExcelRes(){
        $filePath = $_FILES['filename']['tmp_name'];
        Excel::load($filePath, function($reader) use( &$res ) {
            $reader = $reader->getSheet(0);
            $res = $reader->toArray();
        });


        foreach($res as $k => $vo){

            if(isset($vo[0]) && $vo[0]){
                //存到要查找的表
                FindGoods::insert([
                    'code' => trim($vo[0]),
                    'created_at' => time(),
                    'updated_at' => time(),
                ]);
            }
        }

        admin_toastr('导入成功');

        return redirect(admin_base_path('FindGoods'));


    }



}
