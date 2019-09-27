<?php

namespace App\Admin\Controllers;

use App\AreaName;

use App\AreaPriceLog;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\MessageBag;

class AreaNameController extends Controller
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

            $content->header('区域名称管理');
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

            $content->header('区域名称管理');
            $content->description('');

            $content->body($this->form()->edit($id));
        });
    }

    public function editAreaprice($id)
    {
        return Admin::content(function (Content $content) use ($id) {

            $content->header('充值');
            $content->description('');

            $content->body($this->_form()->edit($id));
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

            $content->header('区域名称管理');
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
        return Admin::grid(AreaName::class, function (Grid $grid) {

            $grid->id('ID')->sortable();
            $admin_username = Admin::user() -> username;
            if($admin_username != 'admin' && $admin_username != 'admin_dan'){
                //只能看到他增加的
                $grid -> model() -> where('admin_user_name',$admin_username);
            }


            $grid->filter(function($filter){

                // 去掉默认的id过滤器
                $filter->disableIdFilter();


            });

            $grid -> area_name('区域名称');
            $grid -> area_num('区域编号');
            $grid -> wx_user_id('绑定用户编号');
            $grid -> mp_bind_user_id('小程序用户');

            $grid->disableExport();
            $grid->disableRowSelector();
            $grid -> price_temp()->small_price('<=1kg价格');
            $grid -> price_temp()->first_price('>1kg首重价格');
            $grid -> price_temp()->price_other('>1kg续重价格');
            $grid -> price('区域余额');
            if($admin_username == 'admin'){
                $grid -> auto_pay('自动支付') -> display(function($value){
                    if($value){
                        return '是';
                    }else{
                        return '否';
                    }
                });
                //$grid->auto_pay('自动支付')->editable('select', [0 => '否', 1 => '是']);
            }

            $grid->created_at('创建时间')->display(function($value){
                return date('Y-m-d H:i',$value);
            });

            $grid->actions(function ($actions) {
                $id = $actions->getKey();
                $actions->disableDelete();
                $actions->append('<a href="/admin/editAreaprice/'.$id.'" style="margin-left:8px;font-size:12px;">充值</a>');
                $actions->append('<a href="/admin/editImage?area_id='.$id.'" style="margin-left:8px;font-size:12px;">二维码</a>');

            });

            //$grid->updated_at();
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Admin::form(AreaName::class, function (Form $form) {

            $form->display('id', 'ID');
            $form -> text('area_name','区域名称') -> rules('required');
            $form -> text('area_num','区域编号')->rules(function ($form) {


            });
            $form -> text('price_temp.small_price','<=1kg价格');
            $form -> text('price_temp.first_price','>1kg首重价格');
            $form -> text('price_temp.price_other','>1kg续重价格');
            $form -> text('wx_user_id','绑定用户编号');
            $form -> text('mp_bind_user_id','小程序用户编号');
            $form -> hidden('admin_user_name');
            $form -> hidden('id');
            $admin_username = Admin::user() -> username;
            if($admin_username == 'admin'){
                $form -> select('auto_pay','是否自动支付') -> options([0 => '否', 1 => '是']);
            }


            //$form -> text('price','金额') -> setWidth(2,2);

            $form -> saving(function($form){
                $form -> price = floatval($form -> price);
                if(!$form -> id){
                    //新增的时候 增加admin_user_name
                    $admin_username = Admin::user() -> username;
                    $form -> admin_user_name = $admin_username;
                    //区域名称 不能重复
                    $info = DB::table('area_name') -> where([
                        'area_name' => $form ->  area_name
                    ]) -> orWhere([
                        'area_num' => $form -> area_num
                    ]) -> first();
                    if($info){
                        //如果存在 返回错误
                        $error = new MessageBag([
                            'title'   => '错误信息',
                            'message' => '区域编号或者区域名称不能重复',
                        ]);

                        return back()->with(compact('error'));
                    }



                }else{
                    //编辑的时候
                    //区域名称 不能重复
                    $info = DB::table('area_name') -> where([
                        'area_name' => $form ->  area_name
                    ]) -> orWhere([
                        'area_num' => $form -> area_num
                    ]) -> get();
                    if(count($info) > 1){
                        //如果存在 返回错误
                        $error = new MessageBag([
                            'title'   => '错误信息',
                            'message' => '区域编号或者区域名称不能重复',
                        ]);

                        return back()->with(compact('error'));
                    }


                    //编辑的时候 admin_user_name 不能变
                    $area_info = DB::table('area_name') -> where([
                        'id' =>$form -> id
                    ]) -> first();
                    $form -> admin_user_name = $area_info -> admin_user_name;
                }


            });
        });
    }

    //充值form
    protected function _form()
    {
        return Admin::form(AreaName::class, function (Form $form) {

            $form->display('id', 'ID');
            $form -> display('area_name','区域名称')-> setWidth(2,2);
            $form -> text('price_new','充值金额') -> setWidth(2,2);
            $form->hidden('id');
            $form -> display('price','目前金额') -> setWidth(2,2);
            $form -> setAction(admin_base_path('chargeAreaRes'));

            $form->tools(function (Form\Tools $tools) {
                // 去掉跳转列表按钮
                $tools->disableListButton();

            });
        });
    }


    //充值结果
    public function  chargeAreaRes(){
        $area_id = $_POST['id'];
        $price_charge = floatval($_POST['price_new']);
        if(!$price_charge){
            admin_toastr('充值失败','error');
            return redirect(admin_base_path('editAreaprice/'.$area_id));
        }else{
            $area_model = new AreaName();
            $area_model -> chargePrice($area_id,$price_charge);
            admin_toastr('充值成功','success');
            return redirect(admin_base_path('editAreaprice/'.$area_id));

        }
    }
}
