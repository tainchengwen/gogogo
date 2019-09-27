<?php

namespace App\Admin\Controllers;

use App\Admin\Extensions\CheckGoodsExcelExpoter;
use App\CheckGoods;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Support\Facades\DB;

class CheckGoodsController extends Controller
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
            ->header('盘点')
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
            ->header('盘点数据')
            ->description('编辑')
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


        return Admin::grid(CheckGoods::class, function (Grid $grid) {
            $grid -> model() -> where('flag',0);
            $grid -> model() -> orderBy('id','desc');
            $grid->id('Id');
            $grid->goods_number('盘点编号');

            $grid->column('盘点数量','盘点数量') -> display(function($val){
                $id = $this -> id;
                $details = DB::table('check_goods_detail')
                    -> where([
                        'flag' => 0,
                        'check_goods_id' => $id
                    ]) -> count();
                return $details;
            });

            $grid->created_at('创建时间') -> display(function($val){
                return date('Y-m-d H:i',$val);
            });
            $grid->tools(function ($tools) {

                $tools->batch(function ($batch) {
                    $batch->disableDelete();
                });
                //$tools->append(new PrintMpPdf());
            });
            $grid->actions(function ($actions) {
                $id = $actions->getKey();

                $actions->disableDelete();
                //$actions->disableEdit();
                //添加操作
                $actions->append('<a href="/admin/checkGoodsDetail?id='.$id.'" style="margin-left:8px;font-size:15px;cursor:pointer;">明细</a>');

                $url = "'".'/admin/deleteCheckGoods?id='.$id."'";
                $actions->append('<a onclick="if(confirm('."'确定要删除么'".')){location.href='.$url.'}"  style="margin-left:12px;font-size:15px;cursor:pointer;">删除</a>');


                //href="/admin/deleteAreaOrderDetail?id='.$id.'"  target="_blank"
            });

            $grid->exporter(new CheckGoodsExcelExpoter());


            //$grid->disableExport();
            $grid->disableCreateButton();
        });
    }

    public function deleteCheckGoods(){
        DB::table('check_goods') -> where([
            'id' => $_GET['id']
        ]) -> update([
            'flag' => 1
        ]);

        DB::table('check_goods_detail')
            -> where([
                'check_goods_id' => $_GET['id']
            ]) -> update([
                'flag' => 1
            ]);


        return redirect(admin_url('CheckGoods'));

    }

    public function checkGoodsDetail(){
        $id = $_GET['id'];
        return Admin::form(CheckGoods::class, function (Form $form) use($id) {
            $checkGoodsDetail = DB::table('check_goods_detail')
                -> where([
                    'check_goods_id' => $id,
                    'flag' => 0
                ]) -> get();


            //表编辑
            $view_table = view('admin.check_goods_detail_table',compact('checkGoodsDetail')) -> render();
            $form -> html($view_table)->setWidth(12);
            $form->tools(function (Form\Tools $tools) {
                // 去掉返回按钮
                //$tools->disableBackButton();
                // 去掉跳转列表按钮
                $tools->disableListButton();
                //$tools->add('<a class="btn btn-sm btn-danger" href="/admin/sendOrderList" style="font-size:13px;" > << 返回</a>');
            });
            $form->disableSubmit();
            $form->disableReset();


        });
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(CheckGoods::findOrFail($id));

        $show->id('Id');
        $show->goods_number('Goods number');
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
        $form = new Form(new CheckGoods);

        $form->text('goods_number', '盘点编号');


        return $form;
    }
}
