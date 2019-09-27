<?php

namespace App\Admin\Controllers;

use App\MallApi;
use App\MallImage;

use App\MallImageA;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Facades\Log;

class MallImageAController extends Controller
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

            $content->header('商品维护');
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

            $content->header('商品维护');
            $content->description('');

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

            $content->header('商品维护');
            $content->description('');

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
        return Admin::grid(MallImageA::class, function (Grid $grid) {

            $grid->disableRowSelector();
            $grid->actions(function ($actions) {
                $id = $actions->getKey();
                $actions->disableDelete();
            });
            $grid->disableExport();
            $grid->filter(function($filter){

                // 去掉默认的id过滤器
                $filter->disableIdFilter();
                $filter->where(function ($query) {

                    $query -> where('product_id','like',"{$this->input}");
                }, '商品id');
                $filter->where(function ($query) {

                    $query -> where('product_name','like',"%{$this->input}%");
                }, '商品名称');


            });
            $grid->disableCreateButton();
            $grid->id('ID')->sortable();
            $grid->product_id('商品id');
            $grid->product_name('商品名称');
            $grid->class_name('商品类别');
            $grid->price('a');
            $grid->price_b('b');
            $grid->price_c('c');
            $grid->price_d('d');
            $grid->image('图片')->image(url('/').'/uploads/');
            $grid->is_show('状态') -> display(function($value){
                if($value){
                    return '上架';
                }else{
                    return '下架';
                }
            });
            $grid->updated_at('修改时间')->display(function($value){
                return date('Y-m-d H:i',$value);
            });
            $grid->actions(function ($actions) {
                $id = $actions->getKey();
                //$actions->disableEdit();
                $actions->disableDelete();
            });
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form($id = null)
    {
        return Admin::form(MallImageA::class, function (Form $form)use($id) {

            $form->display('id', 'ID');
            $form->image('image','商品图片')->uniqueName()->rules('required');

            $form -> text('product_id','商品编号')->rules('required');
            $form -> display('product_name','商品名称');
            $form -> text('price','商品价格a')->rules('required');
            $form -> text('price_b','商品价格b')->rules('required');
            $form -> text('price_c','商品价格c')->rules('required');
            $form -> text('price_d','商品价格d')->rules('required');
            //dd($form->id);
            if($id){
                $info = MallImageA::find($id);
                $is_show = $info -> is_show;
            }else{
                $is_show = 1;
            }
            $form->radio('is_show','状态')->options(['0' => '下架', '1'=> '上架'])->default($is_show);
            //$form->display('created_at', 'Created At');
            //$form->display('updated_at', 'Updated At');
            $form->saving(function(Form $form){
                //dd($form->product_id);
                $form->product_id = trim($form->product_id);
                //看这个product_id 是否存在了多次
                $count_id = DB::table('mall_image') -> where([
                    'product_id' =>$form->product_id
                ]) -> count();
                //dd($count_id);
                //编辑状态
                if($form->model()->id){
                    if($count_id > 1){
                        //admin_toastr('商品编号重复');
                        $error = new MessageBag([
                            'title'   => '商品编号重复',
                            'message' => '',
                        ]);

                        return back()->with(compact('error'));
                    }
                }else{
                    if($count_id >= 1){
                        //admin_toastr('商品编号重复');
                        $error = new MessageBag([
                            'title'   => '商品编号重复',
                            'message' => '',
                        ]);

                        return back()->with(compact('error'));
                    }
                }


            });
        });
    }


    //同步数据
    public function sameData(){
        $model = new MallApi();
        for($i=1;$i<=1000;$i++){
            $data = $model -> getGoodsListA($i,10);
            //dd($data);
            if(!count($data)){
                break;
            }

            foreach($data as $vo){
                //查下此Product_no在不在
                $isset = DB::table('mall_image_a') -> where([
                    'product_id' => $vo['ProductNo']
                ]) -> first();
                if(!$isset){
                    DB::table('mall_image_a') -> insert([
                        'product_id' => $vo['ProductNo'],
                        'product_name' => $vo['PartNumber'],
                        'class_name' => $vo['CategoryName'],
                        'created_at' => time(),
                        'updated_at' => time(),
                        'price' => $vo['Price'],
                    ]);
                }elseif(!$isset->image){
                   //没有图片 去拿图片
                   $temp_pic_name = $model -> getImg($vo['ProductNo']);
                   Log::info($temp_pic_name);
                   if(!$temp_pic_name){
                      continue;
                   }
                   $temp_name = 'images/'.basename($temp_pic_name);
                   DB::table('mall_image_a')->where([
                      'product_id' => $vo['ProductNo']
                   ])-> update([
                      'image' => $temp_name
                   ]);
                }
            }
        }
    }
}
