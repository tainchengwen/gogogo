<?php

namespace App\Admin\Controllers;

use App\AreaName;
use App\PayImage;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PayImageController extends Controller
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

            $content->header('线下支付参数配置');
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

            $content->header('支付二维码');
            $content->description('');

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

            $content->header('支付二维码');
            $content->description('');

            $content->body($this->form());
        });
    }


    public function editImage(){
        return Admin::content(function (Content $content) {

            $content->header('支付二维码');
            $content->description('');

            $content->body($this->_form());
        });
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Admin::grid(PayImage::class, function (Grid $grid) {

            $grid->id('ID')->sortable();
            //$grid -> model() -> where('area_id',0);
            $grid -> image('二维码图片')->image();
            $grid -> type('类型') -> display(function($value){
                if($value == 1){
                    return '微信';
                }else{
                    return '支付宝';
                }
            });


            $grid -> area_id('区域') -> display(function($value){
                if($value){
                    $area_name = AreaName::find($value);
                    return $area_name -> area_name;
                }

            });


            $grid->updated_at('修改时间')->display(function($value){
                return date('Y-m-d H:i',$value);
            });




            $grid->disableCreateButton();
            $grid->disableRowSelector();
            $grid->actions(function ($actions) {
                $id = $actions->getKey();
                $payimg = PayImage::find($id);
                if($payimg -> area_id){
                    //去掉编辑按钮
                    $actions->disableEdit();
                }
                $actions->disableDelete();
            });
            $grid->disableExport();
            $grid->filter(function($filter){

                // 去掉默认的id过滤器
                $filter->disableIdFilter();


            });
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function _form()
    {
        return Admin::form(AreaName::class, function (Form $form) {
            $area_id = Admin::user() -> from_area;


            $zhifu_img = PayImage::where([
                'type' => 0,
                'area_id' => $area_id
            ]) -> first();

            $weixin_img = PayImage::where([
                'type' => 1,
                'area_id' => $area_id
            ]) -> first();



            $form->image('image_zhifubao','支付宝图片');
            if($zhifu_img){
                $form -> html('<img src="'.asset('uploads/'.$zhifu_img -> image).'" style="width:100px;height:120px;"  />','当前支付宝支付图片')->rules('required');
            }


            $form->image('image_weixin','微信图片');
            if($weixin_img) {
                $form->html('<img src="' . asset('uploads/'.$weixin_img -> image) . '" style="width:100px;height:120px;"  />', '当前微信支付图片')->rules('required');
            }

            $form -> hidden('area_id')->default($area_id);

            $form -> setAction(admin_base_path('editAreaPayImageRes'));

            $form->tools(function (Form\Tools $tools) {
                // 去掉`列表`按钮
                $tools->disableList();
            });

            //$form->display('created_at', 'Created At');
            //$form->display('updated_at', 'Updated At');
        });
    }


    protected  function form(){
        return Admin::form(PayImage::class, function (Form $form) {

            $form->display('id', 'ID');
            $form->display('type','支付类型')->with(function($value){
                if($value == 1){
                    return '微信';
                }else{
                    return '支付宝';
                }
            });
            $form->image('image')->rules('required');
        });
    }


    //上传支付图片处理
    public function editAreaPayImageRes(){
        Log::info(json_encode($_FILES));
        if(isset($_FILES['image_weixin']) && $_FILES['image_weixin']['size']){
            //上传
            $newFileName = md5(time().rand(10000,99999)).'.jpg';
            $is_move = move_uploaded_file($_FILES['image_weixin']['tmp_name'],public_path().'/uploads/images/'.$newFileName);
            if(!$is_move){
                admin_error('上传失败');
                return redirect(admin_base_path('editImage'));
            }
            $weixin_name = 'images/'.$newFileName;
        }

        if(isset($_FILES['image_zhifubao']) && $_FILES['image_zhifubao']['size']){
            //上传
            $newFileName = md5(time().rand(10000,99999)).'.jpg';
            $is_move = move_uploaded_file($_FILES['image_zhifubao']['tmp_name'],public_path().'/uploads/images/'.$newFileName);
            if(!$is_move){
                admin_error('上传失败');
                return redirect(admin_base_path('editImage'));
            }
            $zhifubao_name = 'images/'.$newFileName;
        }




        //微信
        if(isset($weixin_name)){
            $weixin_img = PayImage::where([
                'area_id' => $_POST['area_id'],
                'type' => 1
            ]) -> first();
            if($weixin_img){
                //更新
                PayImage::where([
                    'area_id' => $_POST['area_id'],
                    'type' => 1
                ]) -> update([
                    'image' => $weixin_name
                ]);
            }else{
                //新增
                PayImage::insert([
                    'area_id' => $_POST['area_id'],
                    'type' => 1,
                    'image' => $weixin_name
                ]);
            }
        }



        //支付宝
        if(isset($zhifubao_name)){
            $zhifubao_img = PayImage::where([
                'area_id' => $_POST['area_id'],
                'type' => 0
            ]) -> first();
            if($zhifubao_img){
                //更新
                PayImage::where([
                    'area_id' => $_POST['area_id'],
                    'type' => 0
                ]) -> update([
                    'image' => $zhifubao_name
                ]);
            }else{
                //新增
                PayImage::insert([
                    'area_id' => $_POST['area_id'],
                    'type' => 0,
                    'image' => $zhifubao_name
                ]);
            }
        }

        admin_success('更新成功');
        return redirect(admin_base_path('editImage'));
    }

}
