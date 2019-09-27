<?php

namespace App\Admin\Controllers;

use App\PriceLog;
use App\Repertory;
use App\User;
use App\Zips;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

class PriceLogController extends Controller
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

            $content->header('用户消费记录');
            $content->description('列表');

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

            $content->header('到货库存');
            $content->description('编辑');

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

            $content->header('到货库存');
            $content->description('添加');

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
        return Admin::grid(PriceLog::class, function (Grid $grid) {
            $grid -> model() -> orderBy('id','desc');
            //$grid->disableFilter();
            $grid->paginate(100);
            $grid->filter(function($filter){

                // 去掉默认的id过滤器
                //$filter->disableIdFilter();
                //$filter -> useModal();
                $filter->expand();
                //客户筛选
                $users = DB::table('wxuser') -> get(['nickname','id']);
                $user_arr = [];
                foreach($users as $vo){
                    $user_arr[$vo -> id] = $vo -> nickname;
                }
                $filter -> equal('userid','客户') -> select($user_arr);
                $config = config('admin.price_log_type');
                $filter -> equal('type','类型') -> select($config);


            });

            $grid->disableExport();
            $grid->disableRowSelector();
            $grid -> column('userdid','客户id') -> display(function($value){
                return $this -> userid;
            });

            $grid -> userid('客户') -> display(function($value){
                if($value){
                    $userinfo = DB::table('wxuser') -> where([
                        'id' => $value
                    ]) -> first();
                    return $userinfo -> nickname;
                }

            });
            $grid -> price('金额');
            $grid -> end_price('余额');

            $grid -> in_out('收入/支出') -> display(function($value){
                if($value){
                    return '支出';
                }else{
                    return '收入';
                }
            });

            $grid -> type('类型') -> display(function($value){
                $config = config('admin.price_log_type');
                return $config[$value];
            });

            $grid -> from_user_id('操作人') -> display(function($value){
                if($value){
                    $user_from = DB::table('admin_users') -> where([
                        'id' => $value
                    ]) -> first();
                    if(isset($user_from -> name)){
                        return $user_from -> name;
                    }

                }
            });


            $grid -> updated_at('操作时间') -> display(function($value){
                return date('Y-m-d H:i',$value);
            });

            $grid->actions(function ($actions) {
                $actions->disableDelete();
                $actions->disableEdit();
                $actions->disableView();
            });
            $grid->disableCreateButton();




        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {

        return Admin::form(Repertory::class, function (Form $form) {

            $form->display('id', 'ID');

            $form -> date('fajian_date','发件日期');
            $company_config = config('admin.repertory_company');
            $form -> select('company','物流公司')->options($company_config);
            $wxuser = DB::table('wxuser') -> get(['nickname','id']);
            //dd($wxuser);
            $option_wxuser = [];
            foreach($wxuser as $vo){
                $option_wxuser[$vo -> id] = 'ID:'.$vo -> id.'---昵称:'.$vo -> nickname;
            }
            if(isset($_GET['id'])){
                $form -> select('user_id','客户') -> options($option_wxuser)->default($_GET['id'])->rules('required');
            }else{
                $form -> select('user_id','客户') -> options($option_wxuser)->rules('required');
            }


            $form -> text('numbers','单号')->rules('required');
            $form -> number('num','件数');
            $form -> text('weight','重量');
            $form -> number('yubao_num','预报箱数');
            $form -> date('yuji_date','预计到港')->setWidth(8,2);
            $form -> date('shiji_date','实际到港')->setWidth(5);

            $status_config = config('admin.repertory_status');
            $form -> select('status','状态') -> options($status_config);
            $form -> number('dabao_num','打包数量');
            $form -> number('fachu_num','发货数量');
            $form -> number('shengyu_num','剩余数量');
            $form -> text('remark','备注');
            $script = <<<SCRIPT
$(function(){
    $("#dabao_num").keyup(function(){
        alert(1);
    })
})
SCRIPT;
            $form -> html('<script>'.$script.'</script>');

        });
    }





    public function users(Request $request)
    {
        $q = $request->get('q');

        return User::where()->paginate(null, ['id', 'name as text']);
    }
}
