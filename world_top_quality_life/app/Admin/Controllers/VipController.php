<?php

namespace App\Admin\Controllers;

use App\Admin\Extensions\Vip;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;
use App\WxUser;

use EasyWeChat\Factory;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Encore\Admin\Widgets\Table;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

class VipController extends Controller
{
    use ModelForm;
    public function index()
    {
        return Admin::content(function (Content $content) {

            $content->header('VIP用户列表');
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

            $content->header('VIP用户管理');
            $content->description('编辑');

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

        return Admin::grid(WxUser::class, function (Grid $grid) {
            $from_area = Admin::user() -> from_area;
            $is_super = false;
            //查查 此人有没有绑定用户
            $area_info = DB::table('area_name') -> where([
                'id' => $from_area
            ]) -> first();
            if(isset($area_info -> wx_user_id) && !empty($area_info -> wx_user_id)){
                $is_super = true;
                $grid -> model()->whereIn('id',explode(',',trim($area_info -> wx_user_id)));
            }



            $grid->model()->where('flag', '=', 0);
            $grid->model()->where('nickname', '<>', '');
            $grid->model()->orderBy('id', 'desc');
            $grid->model() -> where('is_vip',1);


            $grid->filter(function($filter){

                // 去掉默认的id过滤器
                $filter->disableIdFilter();

                // 在这里添加字段过滤器
                $filter->like('nickname', '昵称');

                /*
                $filter->where(function($query){
                    dump($query);exit;
                },'created_at')->date();
                */



            });

            $grid->id('ID')->sortable();
            $grid->headimg('头像')->image('http://xxx.com', 80, 80);
            $grid->nickname('昵称')->display(function($value){
                return str_limit($value,10);
            }) ;
            $grid->erp_id('erp名称') ;
            //$grid->fandian('返点') ;
            $grid->from_userid('邀请人') -> display(function($value){
                if($value){
                    $fromuser = DB::table('wxuser') -> where([
                        'id' => $value
                    ]) -> first();
                    $url = admin_base_path('users').'/'.$value.'/'.'edit';
                    return '<a href="'.$url.'">'.str_limit($fromuser -> nickname,12,'...').'</a>';

                }else{
                    return '';
                }
            });
            $grid->class('物流等级') -> display(function($value){
                $user_config = config('admin.user_class');
                return $user_config[$value];
            });
            $grid->market_class('商城等级') -> display(function($value){
                $user_config = config('admin.market_class');
                return $user_config[$value];
            });


            $grid ->price('余额');

            /*
            $grid->is_sign('是否签署')->display(function($value){
               return $value?'是':'否';
            });
            */

            $grid->created_at('注册')->display(function($value){
                return date('Y-m-d',$value);
            });
            $grid->disableCreateButton();
            $grid->disableExport();
            $grid->disableRowSelector();



            $grid->actions(function ($actions)use($is_super) {
                $id = $actions->getKey();
                $actions->disableDelete();
                $actions->disableEdit();

                if($is_super){
                    //针对某个人导入订单
                    $actions->append('<a href="/admin/underOrder/'.$id.'" style="margin-left:8px;font-size:20px;">下单</a>');
                }else{

                    $actions->append('<a href="/admin/users/'.$id.'/edit" style="font-size:25px;"><img src="'.asset('img/admin_detail.jpg').'"/></a>');
                    // append一个操作
                    $actions->append('<a href="/admin/order/'.$id.'" style="font-size:25px;margin-left:8px;"><img src="'.asset('img/admin_xiadan.jpg').'"/></a>');

                    // append一个操作  充值
                    $actions->append('<a href="/admin/recharge/'.$id.'" style="margin-left:8px;font-size:25px;"><img src="'.asset('img/admin_chongzhi.jpg').'"/></a>');
                    //减值
                    $actions->append('<a href="/admin/subPrice/'.$id.'" style="margin-left:8px;font-size:25px;"><img src="'.asset('img/admin_jian.jpg').'"/></a>');
                    //返点日志
                    $actions->append('<a href="/admin/priceLog/'.$id.'" style="margin-left:8px;font-size:25px;"><img src="'.asset('img/admin_log.jpg').'"/></a>');

                    //标记vip
                    $actions->append(new Vip($id,2));




                    //针对某个人导入订单
                    $actions->append('<a href="/admin/underOrder/'.$id.'" style="margin-left:8px;font-size:20px;">下单</a>');
                    //地址维护
                    $actions->append('<a href="/admin/address/'.$id.'" style="margin-left:8px;font-size:20px;">地址</a>');
                    //价格模版
                    $actions->append('<a href="/admin/routeSettingUser/'.$id.'" style="margin-left:8px;font-size:20px;">价格</a>');
                }




            });

            //$grid->updated_at();
        });


        return Admin::grid(WxUser::class, function (Grid $grid) {

            $grid->model()->where('flag', '=', 0);
            $grid->model()->where('nickname', '<>', '');
            $grid->model()->orderBy('id', 'desc');
            $grid->model() -> where('is_vip',1);
            $grid->filter(function($filter){

                // 去掉默认的id过滤器
                $filter->disableIdFilter();

                // 在这里添加字段过滤器
                $filter->like('nickname', '昵称');

                /*
                $filter->where(function($query){
                    dump($query);exit;
                },'created_at')->date();
                */



            });

            $grid->id('ID')->sortable();
            $grid->headimg('头像')->image('http://xxx.com', 80, 80);
            $grid->nickname('昵称') ;
            $grid->from_userid('邀请人') -> display(function($value){
                if($value){
                    $fromuser = DB::table('wxuser') -> where([
                        'id' => $value
                    ]) -> first();
                    $url = admin_base_path('users').'/'.$value.'/'.'edit';
                    return '<a href="'.$url.'">'.str_limit($fromuser -> nickname,12,'...').'</a>';

                }else{
                    return '';
                }
            });
            $grid->class('会员等级') -> display(function($value){
                $user_config = config('admin.user_class');
                return $user_config[$value];
            });
            $grid ->price('余额');

            $grid->is_sign('是否签署')->display(function($value){
                return $value?'是':'否';
            });

            $grid->created_at('注册时间')->display(function($value){
                return date('Y-m-d H:i',$value);
            });
            $grid->disableCreateButton();
            $grid->disableExport();
            $grid->disableRowSelector();



            $grid->actions(function ($actions) {
                $id = $actions->getKey();
                $actions->disableDelete();
                $actions->disableEdit();

                $actions->append('<a href="/admin/users/'.$id.'/edit" style="font-size:25px;"><img src="'.asset('img/admin_detail.jpg').'"/></a>');
                // append一个操作
                $actions->append('<a href="/admin/order/'.$id.'" style="font-size:25px;margin-left:8px;"><img src="'.asset('img/admin_xiadan.jpg').'"/></a>');

                // append一个操作  充值
                $actions->append('<a href="/admin/recharge/'.$id.'" style="margin-left:8px;font-size:25px;"><img src="'.asset('img/admin_chongzhi.jpg').'"/></a>');
                //减值
                $actions->append('<a href="/admin/subPrice/'.$id.'" style="margin-left:8px;font-size:25px;"><img src="'.asset('img/admin_jian.jpg').'"/></a>');
                //返点日志
                $actions->append('<a href="/admin/priceLog/'.$id.'" style="margin-left:8px;font-size:25px;"><img src="'.asset('img/admin_log.jpg').'"/></a>');
            });

            //$grid->updated_at();
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form($id = null)
    {
        return Admin::form(WxUser::class, function (Form $form) use($id) {

            $form->display('id', 'ID');
            $form->display('nickname','昵称');
            $form ->number('class','等级')->min(0)->max(5)->step(0.1);
            //$form -> number('price','余额') -> min(0);

            $form->display('from_userid','邀请人') -> with(function($value){
                if($value){
                    $userinfo = DB::table('wxuser') -> where([
                        'id' => $value
                    ]) -> first();
                    $url = admin_base_path('users').'/'.$value.'/'.'edit';
                    return '<a href="'.$url.'">'.str_limit($userinfo -> nickname,12,'...').'</a>';
                }
            });


            $form->display('created_at', '创建时间')->with(function($value){
                return date('Y-m-d H:i',$value);
            });
        });
    }
}
