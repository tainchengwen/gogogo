<?php

namespace App\Admin\Controllers;

use App\Admin\Extensions\CheckReturnPoint;
use App\ReturnPoint;
use App\Http\Controllers\Controller;
use App\WxUser;
use EasyWeChat\Factory;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Support\Facades\DB;

class ReturnPointController extends Controller
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
            ->header('返点设置')
            ->description('审核')
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
        $grid = new Grid(new ReturnPoint);
        $grid -> model() -> orderBy('id','desc');
        $grid->id('Id');
        $grid->mpusers() -> nickname('申请人');
        $grid->shop_id('店铺') -> display(function($value){
            $shop_info = DB::table('return_shops') -> where([
                'id' => $value
            ]) -> first();
            if($shop_info){
                return $shop_info -> shop_name;
            }

        });
        $grid->price('实付金额');
        $grid->fan_price('应返金额');
        $grid->numbers('凭证编号');
        $grid->column('验证码','验证码') -> display(function($value){
            $return_info = ReturnPoint::find($this -> id);
            return sprintf("%06s",$return_info -> user_id);
        });
        $grid->image('凭证')->image(url('/').'/uploads/return');
        $grid->status('状态') -> display(function($value){
            if($value == 1){
                return '已返';
            }elseif($value == 2){
                return '驳回';
            }else{
                return '待返';
            }
        });
        $grid->created_at('申请时间') -> display(function($value){
            return date('Y-m-d H:i',$value);
        });
        $grid->actions(function ($actions) {
            $id = $actions->getKey();
            $actions->disableDelete();
            $info = ReturnPoint::find($id);
            $url = $info -> image;
            $actions->append('<a href="'.$url.'" target="_blank">查看图片</a>');
            if(!$info -> status){
                $actions->append(new CheckReturnPoint($id));
            }

            $actions->disableEdit();
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
        $show = new Show(ReturnPoint::findOrFail($id));

        $show->id('Id');
        $show->shop_id('Shop id');
        $show->price('Price');
        $show->numbers('Numbers');
        $show->image('Image');
        $show->status('Status');
        $show->created_at('Created at');
        $show->updated_at('Updated at');
        $show->flag('Flag');
        $show->user_id('User id');

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new ReturnPoint);

        $form->number('shop_id', 'Shop id');
        $form->decimal('price', '价格');
        $form->text('numbers', '凭证编号');
        $form->image('image', 'Image')->image(url('/').'/uploads/return');
        $form->number('status', 'Status');
        $form->number('flag', 'Flag');
        $form->number('user_id', 'User id');

        return $form;
    }


    //返点审核成功
    public function checkReturnPoint(){
        $id = $_POST['id'];
        $type = $_POST['type'];
        DB::table('return_point') -> where([
            'id' => $id
        ]) -> update([
            'status' => $type
        ]);




        //返点成功后 给她本人增加余额
        $return_info = ReturnPoint::find($id);
        $user_id = $return_info -> user_id;

        $userinfo = DB::table('wxuser') -> where([
            'id' => $user_id
        ]) -> first();
        $env  = env('APP_ENV');
        if($type == 1){
            //返点成功 给他发送模板消息
            /*
            if($env != 'local'){
                $config = [
                    'app_id' => env('WECHAT_OFFICIAL_ACCOUNT_APPID'),
                    'secret' => env('WECHAT_OFFICIAL_ACCOUNT_SECRET'),
                ];

                $app = Factory::officialAccount($config);
                $res = $app->template_message->send([
                    'touser' => $userinfo -> openid,
                    'template_id' => 'gf-hofnj0wcfNNo8I8PIzqeTo42Njiddao8EkKvwCNY',
                    'url' => url('return/returnList'),
                    'data' => [
                        'first' => '尊敬的会员，您的返点申请已通过！',
                        'keyword2' => '审核通过',
                        'keyword1' => $userinfo -> nickname,
                        'keyword3' => date('Y-m-d H:i'),
                        'remark' => '感谢您的使用'
                    ]
                ]);
            }
            */

            if($_REQUEST['fan_type'] == 'up'){
                DB::table('wxuser') -> where([
                    'id' => $user_id
                ]) -> update([
                    'price' => floatval($userinfo -> price) + floatval($return_info -> fan_price),
                    'updated_at' => time()
                ]);

                //添加日志
                DB::table('price_log') -> insert([
                    'userid' => $user_id,
                    'price' => $return_info -> fan_price,
                    'type' => 5,
                    'created_at' => time(),
                    'updated_at' => time(),
                    'from_user_id' => 0,
                    'in_out' => 0 ,// 0收入1支出
                    'end_price' => floatval($userinfo -> price) + floatval($return_info -> fan_price)
                ]);
            }

            /*
            DB::table('wxuser') -> where([
                'id' => $user_id
            ]) -> update([
                'price' => floatval($userinfo -> price) + floatval($return_info -> fan_price),
                'updated_at' => time()
            ]);

            //添加日志
            DB::table('price_log') -> insert([
                'userid' => $user_id,
                'price' => $return_info -> fan_price,
                'type' => 5,
                'created_at' => time(),
                'updated_at' => time(),
                'from_user_id' => 0,
                'in_out' => 0 ,// 0收入1支出
                'end_price' => floatval($userinfo -> price) + floatval($return_info -> fan_price)
            ]);

            */
        }else{
            //驳回

            /*
            if($env != 'local'){
                $config = [
                    'app_id' => env('WECHAT_OFFICIAL_ACCOUNT_APPID'),
                    'secret' => env('WECHAT_OFFICIAL_ACCOUNT_SECRET'),
                ];

                $app = Factory::officialAccount($config);
                $res = $app->template_message->send([
                    'touser' => $userinfo -> openid,
                    'template_id' => 'gf-hofnj0wcfNNo8I8PIzqeTo42Njiddao8EkKvwCNY',
                    'url' => url('return/returnList'),
                    'data' => [
                        'first' => '尊敬的会员，您的返点申请被驳回！',
                        'keyword1' => $userinfo -> nickname,
                        'keyword2' => '申请驳回',
                        'keyword3' => date('Y-m-d H:i'),
                        'remark' => $_POST['remark']
                    ]
                ]);
            }
            */



        }




    }



}
