<?php

namespace App\Admin\Controllers;

use App\AreaName;

use App\AreaPriceLog;
use App\Package;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\MessageBag;

class PackageController extends Controller
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

            $content->header('订单列表');
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
        return Admin::grid(Package::class, function (Grid $grid) {

            $grid->id('ID')->sortable();
            $grid -> model() -> orderBy('id','desc');
            $grid->created_at('日期')->display(function($value){
                return date('Y-m-d H:i',$value);
            });
            $grid->order() -> from_area('区域')->display(function($value){
                $area_info = DB::table('area_name') -> where([
                    'id' => $value
                ]) -> first();
                return $area_info -> area_name;
            });
            $grid->order()->order_num('订单');
            $grid->package_num('包裹编号');
            $grid->wuliu_num('运单号');
            $grid->order()->userid('客户')->display(function($val){
                $user_info = DB::table('wxuser') -> where([
                    'id' => $val
                ]) -> first();
                return $user_info -> nickname;
            });
            $grid -> from_area() -> display(function($val){
                //路线配置
                $route_config = config('admin.route_setting');
                if(isset($route_config[$val])){
                    return $route_config[$val];
                }else{
                    $val;
                }

            });
            $grid -> order() -> status('发货状态')->display(function($val){
                $status_configs = config('admin.order_status_new');
                return $status_configs[$val];
            });

            $grid -> order() -> pay_status('支付状态') ->display(function($val){
                $pay_status_configs = config('admin.pay_status_new');
                return $pay_status_configs[$val];
            });

            $grid -> weight('重量');

            $grid -> address('地址');



            $grid->disableCreateButton();

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
