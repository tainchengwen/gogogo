<?php

namespace App\Admin\Controllers;

use App\Admin\Extensions\Vip;
use App\Order;
use App\User;
use App\WxUser;

use EasyWeChat\Factory;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;
use Encore\Admin\Widgets\Table;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class WxUserController extends Controller
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

            $content->header('用户列表');
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

            $content->header('用户管理');
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
            $grid->filter(function($filter){

                // 去掉默认的id过滤器
                //$filter->disableIdFilter();

                // 在这里添加字段过滤器
                $filter->like('nickname', '昵称');
                $filter->like('erp_id','erp名称');
                $filter->between('price','商城余额');
                $filter->like('market_class','商城等级');
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
            $grid->fandian('返点') ;
            /*
            $grid->is_sign('是否签署')->display(function($value){
               return $value?'是':'否';
            });
            */

            $grid->created_at('注册')->display(function($value){
                return date('Y-m-d H:i',$value);
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

                    $actions->append('<a href="/admin/users/'.$id.'/edit" style="font-size:15px;"><img src="'.asset('img/admin_detail.jpg').'"/></a>');
                    // append一个操作
                    //$actions->append('<a href="/admin/order/'.$id.'" style="font-size:15px;margin-left:8px;"><img src="'.asset('img/admin_xiadan.jpg').'"/></a>');

                    // append一个操作  充值
                    $actions->append('<a href="/admin/recharge/'.$id.'" style="margin-left:8px;font-size:15px;"><img src="'.asset('img/admin_chongzhi.jpg').'"/></a>');
                    //减值
                    $actions->append('<a href="/admin/subPrice/'.$id.'" style="margin-left:8px;font-size:15px;"><img src="'.asset('img/admin_jian.jpg').'"/></a>');
                    //返点日志
                    $actions->append('<a href="/admin/priceLog/'.$id.'" style="margin-left:8px;font-size:15px;"><img src="'.asset('img/admin_log.jpg').'"/></a>');


                    $userinfo = DB::table('wxuser') -> where('id',$id) -> first();
                    if(!$userinfo -> is_vip){
                        //标记vip
                        $actions->append(new Vip($id,1));
                    }


                    //针对某个人导入订单
                    $actions->append('<a href="/admin/underOrder/'.$id.'" style="margin-left:8px;font-size:15px;">下单</a>');
                    //地址维护
                    $actions->append('<a href="/admin/address/'.$id.'" style="margin-left:8px;font-size:15px;">地址</a>');
                    //价格模版
                    $actions->append('<a href="/admin/routeSettingUser/'.$id.'" style="margin-left:8px;font-size:15px;">价格</a>');
                    //到货库存
                    //$actions->append('<a href="/admin/importKucun/'.$id.'" style="margin-left:8px;font-size:15px;">库存</a>');
                    $actions->append('<a href="/admin/repertory/create?id='.$id.'" style="margin-left:8px;font-size:15px;" target="_blank">库存</a>');

                }




            });

            //$grid->updated_at();
        });
    }

    public function setVipUser(){
        if($_POST['type'] == 1){
            DB::table('wxuser') -> where([
                'id' => $_POST['id']
            ]) -> update([
                'is_vip' => 1
            ]);
        }else{
            DB::table('wxuser') -> where([
                'id' => $_POST['id']
            ]) -> update([
                'is_vip' => 0
            ]);
        }

        echo 'success';
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
            $form ->number('class','物流等级')->min(0)->max(5);
            $form ->number('market_class','商城等级')->min(0)->max(4);
            $form ->number('hope_market_class','到期后变成商城等级')->min(0)->max(4);
            $form -> date('hope_date','指定日期');
            //$form -> text('fandian','返点');
            $form -> text('code','编码设置');
            $form -> text('erp_id','erp客户名称');
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


    public function recharge($id){
        return Admin::content(function (Content $content) use ($id) {

            $content->header('用户管理');
            $content->description('充值');

            $content->body($this->recharge_form()->edit($id));
        });
    }

    //subPrice
    public function subPrice($id){
        return Admin::content(function (Content $content) use ($id) {

            $content->header('用户管理');
            $content->description('扣款');

            $content->body($this->subPrice_form()->edit($id));
        });
    }



    protected function recharge_form(){
        return Admin::form(WxUser::class, function (Form $form) {

            $form->display('id', 'ID');
            $form->display('nickname','昵称');
            $form -> number('price_add','充值金额') -> min(1) ->default('1');
            $form -> display('price','余额');
            $form->hidden('id');
            $form -> setAction(admin_base_path('rechargeRes'));
            $form->tools(function (Form\Tools $tools) {
                // 去掉跳转列表按钮
                $tools->disableListButton();
                // 去掉返回按钮
                //$tools->disableBackButton();
            });

        });
    }

    //扣款操作 subPrice_form
    protected function subPrice_form(){
        return Admin::form(WxUser::class, function (Form $form) {

            $form->display('id', 'ID');
            $form->display('nickname','昵称');
            $form -> number('price_add','扣除金额') -> min(1) ->default('1');
            $form -> display('price','余额');
            $form->hidden('id');
            $form -> setAction(admin_base_path('subPriceRes'));
            $form->tools(function (Form\Tools $tools) {
                // 去掉跳转列表按钮
                $tools->disableListButton();
                // 去掉返回按钮
                //$tools->disableBackButton();
            });

        });
    }


    //充值成功
    public function rechargeRes(){
        /*
         array:5 [▼
              "price_add" => "1"
              "id" => "1"
              "_token" => "zHyH3pM7uu518g0OdKR2oxqoam7mPjukR7KotPxf"
              "_method" => "PUT"
              "_previous_" => "http://package.com/admin/auth/login"
            ]
         */
        //充值成功
        DB::table('wxuser') -> where([
            'id' => $_POST['id']
        ]) -> increment('price', intval($_POST['price_add']));
        $userinfo = DB::table('wxuser') -> where([
            'id' => $_POST['id']
        ]) -> first();
        //添加日志
        DB::table('price_log') -> insert([
            'userid' => $_POST['id'],
            'price' => $_POST['price_add'],
            'type' => 1,
            'created_at' => time(),
            'updated_at' => time(),
            'from_user_id' => Admin::user() -> id,
            'in_out' => 0 ,// 0收入1支出
            'end_price' => $userinfo -> price
        ]);

        //累计充值
        $price_sum = DB::table('price_log') -> where([
            'userid' => $_POST['id'],
            'type' => 0,
        ]) -> sum('price');

        $config = [
            'app_id' => env('WECHAT_OFFICIAL_ACCOUNT_APPID'),
            'secret' => env('WECHAT_OFFICIAL_ACCOUNT_SECRET'),
        ];

        $app = Factory::officialAccount($config);
        $res = $app->template_message->send([
            'touser' => $userinfo -> openid,
            'template_id' => 'wYyGvnA8UueXkkw6FogTD_Cf-M3AQ_XmIWnEfYTNNS0',
            'url' => url('priceTable'),
            'data' => [
                'first' => '尊敬的会员，您已充值成功！',
                'keyword1' => $_POST['price_add'], //充值金额
                'keyword2' => $_POST['price_add'],
                'keyword3' => date('Y-m-d H:i'),
                'keyword4' =>$price_sum, // 累计充值
                'remark' => '感谢您的使用'
            ]
        ]);



        admin_toastr('充值成功');

        return redirect(admin_base_path('users'));
    }


    //扣款成功
    public function subPriceRes(){
        /*
         array:5 [▼
              "price_add" => "1"
              "id" => "1"
              "_token" => "zHyH3pM7uu518g0OdKR2oxqoam7mPjukR7KotPxf"
              "_method" => "PUT"
              "_previous_" => "http://package.com/admin/auth/login"
            ]
         */
        //扣款成功
        DB::table('wxuser') -> where([
            'id' => $_POST['id']
        ]) -> decrement('price', intval($_POST['price_add']));
        $userinfo = DB::table('wxuser') -> where([
            'id' => $_POST['id']
        ]) -> first();
        //添加日志
        DB::table('price_log') -> insert([
            'userid' => $_POST['id'],
            'price' => $_POST['price_add'],
            'type' => 2,
            'created_at' => time(),
            'updated_at' => time(),
            'from_user_id' => Admin::user() -> id,
            'in_out' => 1 ,// 0收入1支出
            'end_price' => $userinfo -> price
        ]);





        admin_toastr('扣款成功');

        return redirect(admin_base_path('users'));
    }

    public function priceLog($id){
        //显示收支记录
        return Admin::content(function (Content $content) use ($id) {

            $content->header('用户管理');
            $content->description('返点记录');

            $content->body($this->price_log_form($id)->edit($id));
        });
    }

    protected function price_log_form($id = null)
    {
        return Admin::form(WxUser::class, function (Form $form) use($id) {

            $form->display('id', 'ID');
            $form->display('nickname','昵称');
            $headers = ['日期', '邀请人', '返点金额'];
            $rows = [];
            $price_log = DB::table('price_log') -> where([
                'type' => 3,
                'userid' =>  $id
            ]) -> get();
            if(!empty($price_log)){
                foreach($price_log as $k => $vo){
                    //邀请人信息
                    if($vo -> from_user_id){
                        $temp = DB::table('wxuser') -> where([
                            'id' => $vo -> from_user_id
                        ]) -> first();
                        $username = $temp -> nickname;
                    }else{
                        $username = '';
                    }
                    $rows[$k] = [date('Y-m-d H:i',$vo -> created_at),$username,$vo ->price];
                }
            }


            /*
            $rows = [
                [1, 'labore21@yahoo.com', 'Ms. Clotilde Gibson', 'Goodwin-Watsica'],
                [2, 'omnis.in@hotmail.com', 'Allie Kuhic', 'Murphy, Koepp and Morar'],
                [3, 'quia65@hotmail.com', 'Prof. Drew Heller', 'Kihn LLC'],
                [4, 'xet@yahoo.com', 'William Koss', 'Becker-Raynor'],
                [5, 'ipsa.aut@gmail.com', 'Ms. Antonietta Kozey Jr.'],
            ];
            */

            //dd($rows);

            $table = new Table($headers, $rows);
            $form->html($table -> render());
            $form->disableSubmit();

            $form->disableReset();
        });
    }


    //用户下单
    public function underOrder($id){
        return Admin::content(function (Content $content) use($id) {

            $content->header('下单');
            $content->description('');

            $content->body($this->exportFileForm($id)->edit($id));
        });
    }

    public function address($id){
        return Admin::content(function (Content $content) use($id) {

            $content->header('维护收货地址');
            $content->description('');

            $content->body($this->exportFileForm($id,'address')->edit($id));
        });
    }


    //维护收货地址  跟 下单一个 方法
    protected function exportFileForm($id,$type = ''){
        $from_area = Admin::user() -> from_area;
        $is_super = false;
        $area_info = DB::table('area_name') -> where([
            'id' => $from_area
        ]) -> first();
        if($area_info -> wx_user_id){
            $is_super = true;
        }


        return Admin::form(WxUser::class, function (Form $form) use($id,$type,$is_super){
            $form -> display('nickname','用户');
            $form->file('file_column','订单')->rules('required');;

            $form->tools(function (Form\Tools $tools)use($type,$id,$is_super) {
                // 去掉跳转列表按钮
                $tools->disableListButton();

                if(!$is_super){
                    if($type =='address'){
                        $url = '"'.url('download_user_address').'"';
                    }else{
                        //下单

                        $url = '"'.url('download_user').'"';

                        //自动下单的地址
                        $url_under = admin_url('autoUnderOrder').'/'.$id;
                        $tools->add('<a class="btn btn-sm btn-primary"  href="'.$url_under.'" ><i class="fa fa-arrow-down"></i>&nbsp;&nbsp;自动下单</a>');

                    }
                }else{
                    $url = '"'.url('download_user').'"';
                }



                // 添加一个按钮, 参数可以是字符串, 或者实现了Renderable或Htmlable接口的对象实例
                $tools->add('<a class="btn btn-sm btn-danger" onclick=\'window.open('.$url.')\'><i class="fa fa-arrow-down"></i>&nbsp;&nbsp;下载模版</a>');

            });


            //如果是填写地址 把她的地址列表展示出来
            if($type =='address') {


                $address_res = DB::table('user_address') -> where([
                    'user_id' => $id
                ]) -> get();
                if(count($address_res)){
                    $form->disableSubmit();
                    $form->disableReset();
                    $compact = [
                        'address_use' => $address_res,
                        'user_id' => $id,
                        'type' => 'address_page'
                    ];
                    $view = view('admin.autoUnderOrderPage', compact('compact'))->render();
                    $form->html($view);
                }


            }else{
                $form -> text('remark','备注');

                //如果是admin  可以显示区域
                $admin_user = Admin::user() -> username;
                if(strstr($admin_user,'admin')){
                    $area_names = DB::table('area_name') -> get();
                    foreach($area_names as $vo){
                        $options[$vo -> id] = $vo -> area_name;
                    }

                    $form -> select('from_area_id','区域') -> options($options) -> rules('required');
                }

                //下单时候 选择路线
                //$route_config = config('admin.route_setting');
                //$form->select('route','路线选择')->options($route_config) -> rules('required');
            }


            $form -> hidden('id','id');
            if($type =='address'){
                $form -> setAction(admin_base_path('addressRes'));
            }else{
                $form -> setAction(admin_base_path('underOrderRes'));
            }



        });
    }

    //到货库存
    public function importKucun($id){
        return Admin::content(function (Content $content) use($id) {

            $content->header('到货库存');
            $content->description('导入');

            $content->body($this->importKucunForm($id)->edit($id));
        });
    }


    public function importKucunForm($id){
        return Admin::form(WxUser::class, function (Form $form) use($id) {

            $form->display('id', 'ID');
            $form->display('nickname','昵称');
            $form -> file('file_name','导入文件') -> rules('required') ;

            $form->tools(function (Form\Tools $tools) {
                // 去掉跳转列表按钮
                $tools->disableListButton();
                $url = '';
                $tools->add('<a class="btn btn-sm btn-danger" onclick=\'window.open('.$url.')\'><i class="fa fa-arrow-down"></i>&nbsp;&nbsp;下载模版</a>');
            });




            //$form -> hidden('id','user_id');
            $form -> setAction(admin_base_path('importKucunRes'));

        });
    }

    //导入库存 处理
    public function importKucunRes(){
        if(!isset($_FILES['file_name']['tmp_name'])){
            echo "<script>history.back();</script>";
        }

        $filePath = $_FILES['file_name']['tmp_name'];
        Excel::load($filePath, function($reader) use( &$res ) {
            $reader = $reader->getSheet(0);
            $res = $reader->toArray();
        });



        foreach($res as $key => $vo) {
            //忽略第一行 表头
            if ($key == 0) {
                continue;
            }

            if($vo[0] && $vo[1] && $vo[2] && $vo[3] && $vo[4]){



            }
        }


    }



    //自动下单
    public function autoUnderOrder($id){
        return Admin::content(function (Content $content) use($id) {

            $content->header('自动下单');
            $content->description('');

            $content->body($this->autoUnderOrderForm($id)->edit($id));
        });
    }

    //自动下单地址表单
    public function autoUnderOrderForm($id){
        return Admin::form(WxUser::class, function (Form $form) use($id) {

            $form->display('id', 'ID');
            $form->display('nickname','昵称');
            $form -> number('order_num','下单数量') -> min(1) ->rules('required') ->default(1);
            $form -> text('order_weight','包裹重量')  ->rules('required') ;

            $admin_user = Admin::user() -> username;
            if(strstr($admin_user,'admin')){
                $area_names = DB::table('area_name') -> get();
                foreach($area_names as $vo){
                    $options[$vo -> id] = $vo -> area_name;
                }

                $form -> select('from_area_id','区域') -> options($options) -> rules('required');
            }


            $form->tools(function (Form\Tools $tools) {
                // 去掉跳转列表按钮
                $tools->disableListButton();
            });


            $form -> hidden('id','user_id');
            $form -> setAction(admin_base_path('autoUnderOrderRes'));

        });
    }

    //自动下单地址表单处理
    public function autoUnderOrderRes(){
        $user_id = $_POST['id'];
        if($_POST['order_num']){
            $num = intval($_POST['order_num']);
        }else{
            $num = 1;
        }

        if($_POST['order_weight']){
            $order_weight = floatval($_POST['order_weight']);
        }else{
            $order_weight = 3.8;
        }

        if(isset($_POST['from_area_id']) && $_POST['from_area_id']){
            $from_area = $_POST['from_area_id'];
        }else{
            $from_area = '';
        }





        //user 下 num 个包裹  生成一个订单
        //去取地址


        //挑一个能用的地址
        $address = DB::table('user_address') -> where([
            'user_id' => $user_id
        ]) -> get();
        //乱排$address
        $temp = [];

        foreach($address as $k => $vo){
            $temp[] = $vo;
        }

        shuffle($temp);
        $address = $temp;



        //shuffle($address);
        //dd($address);
        //看有多少个能用的地址
        $address_use = [];
        foreach($address as $vo){
            //今天用过的先去掉
            $package_info_by_name = DB::table('packages') -> where([
                'wuliu_num' => '',
                'name' => trim($vo -> name),
                'flag' => 0
            ]) -> orderBy('created_at','desc') -> first();
            if($package_info_by_name && date('Y-m-d',$package_info_by_name -> created_at) == date('Y-m-d')){
                continue;
            }


            $package_info_by_tel = DB::table('packages') -> where([
                'wuliu_num' => '',
                'tel' => trim($vo -> tel),
                'flag' => 0
            ]) -> orderBy('created_at','desc') -> first();
            if($package_info_by_tel && date('Y-m-d',$package_info_by_tel -> created_at) == date('Y-m-d')){
                continue;
            }


            $package_info_by_address = DB::table('packages') -> where([
                'wuliu_num' => '',
                'address' => trim($vo -> address),
                'flag' => 0
            ]) -> orderBy('created_at','desc') -> first();
            if($package_info_by_address && date('Y-m-d',$package_info_by_address -> created_at) == date('Y-m-d')){
                continue;
            }




            //查找下 有没有用过
            $count_name = DB::table('packages') -> where([
                'wuliu_num' => '',
                'name' => trim($vo -> name),
                'flag' => 0
            ])  -> count();

            //dd($count_name);
            if($count_name >= 3){
                continue;
            }

            $count_tel = DB::table('packages') -> where([
                'wuliu_num' => '',
                'tel' => trim($vo -> tel),
                'flag' => 0
            ])  -> count();
            if($count_tel >= 3){
                continue;
            }

            $count_address = DB::table('packages') -> where([
                'wuliu_num' => '',
                'address' => trim($vo -> address),
                'flag' => 0
            ])  -> count();
            if($count_address >= 3){
                continue;
            }

            //符合要求后 找到几个值的最大值
            $max_num = max($count_name,$count_tel,$count_address);
            $for_num = 3 - $max_num;

            for($i = 1;$i<=$for_num;$i++ ){
                $address_use[] = $vo;
                //每次判断下
                if(count($address_use) == $num){
                    break;
                }
            }

            if(count($address_use) == $num){
                break;
            }

        }


        //优先取完  开始随便取
        foreach($address as $vo){
            //今天用过的先去掉
            $package_info_by_name = DB::table('packages') -> where([
                'wuliu_num' => '',
                'name' => trim($vo -> name),
                'flag' => 0
            ]) -> orderBy('created_at','desc') -> first();
            if($package_info_by_name && date('Y-m-d',$package_info_by_name -> created_at) == date('Y-m-d')){
                //continue;
            }else{
                continue;
            }


            $package_info_by_tel = DB::table('packages') -> where([
                'wuliu_num' => '',
                'tel' => trim($vo -> tel),
                'flag' => 0
            ]) -> orderBy('created_at','desc') -> first();
            if($package_info_by_tel && date('Y-m-d',$package_info_by_tel -> created_at) == date('Y-m-d')){

            }else{
                continue;
            }


            $package_info_by_address = DB::table('packages') -> where([
                'wuliu_num' => '',
                'address' => trim($vo -> address),
                'flag' => 0
            ]) -> orderBy('created_at','desc') -> first();
            if($package_info_by_address && date('Y-m-d',$package_info_by_address -> created_at) == date('Y-m-d')){

            }else{
                continue;
            }




            //查找下 有没有用过
            $count_name = DB::table('packages') -> where([
                'wuliu_num' => '',
                'name' => trim($vo -> name),
                'flag' => 0
            ])  -> count();

            //dd($count_name);
            if($count_name >= 3){
                continue;
            }

            $count_tel = DB::table('packages') -> where([
                'wuliu_num' => '',
                'tel' => trim($vo -> tel),
                'flag' => 0
            ])  -> count();
            if($count_tel >= 3){
                continue;
            }

            $count_address = DB::table('packages') -> where([
                'wuliu_num' => '',
                'address' => trim($vo -> address),
                'flag' => 0
            ])  -> count();
            if($count_address >= 3){
                continue;
            }

            //符合要求后 找到几个值的最大值
            $max_num = max($count_name,$count_tel,$count_address);
            $for_num = 3 - $max_num;

            for($i = 1;$i<=$for_num;$i++ ){
                $address_use[] = $vo;
                //每次判断下
                if(count($address_use) == $num){
                    break;
                }
            }

            if(count($address_use) == $num){
                break;
            }

        }





        //$address_use 就是最终的数组
        if($address_use){
            //渲染一个页面

            return Admin::content(function (Content $content) use($address_use,$user_id,$order_weight,$from_area) {

                // optional
                $content->header('自动下单');

                // optional
                $content->description('');
                $compact = [
                    'address_use' => $address_use,
                    'user_id' => $user_id,
                    'order_weight' => $order_weight,
                    'from_area' => $from_area
                ];
                $view = view('admin.autoUnderOrderPage',compact('compact')) -> render();
                $content->body($view);
            });


        }else{
            admin_toastr(trans('没有可用的地址'));

            return redirect(admin_base_path('autoUnderOrder').'/'.$user_id);
        }





    }


    //自动下单处理结果
    public function submitUnderOrderRes(){

        $names = $_POST['name'];
        $weights = $_POST['weights'];
        $userid = $_POST['user_id'];
        $address = $_POST['address'];
        $provinces = $_POST['province'];
        $citys = $_POST['city'];
        $tels = $_POST['tel'];
        $remark = $_POST['remark'];
        $uuid_names_arr = [];
        $from_area = isset($_POST['from_area'])?$_POST['from_area']:Admin::user()->from_area;


        foreach($names as $vo){
            $uuid_names_arr[$vo] = $this -> create_uuid();
        }


        //属于的区域
        $model = new Order();
        $model -> underOrder([
            'weights' => $weights,
            'from_area' => $from_area,
            'user_id' => $userid,
            'names' => $names,
            'address' => $address,
            'provinces' => $provinces,
            'citys' => $citys,
            'tels' => $tels,
            'uuid_names_arr' => $uuid_names_arr,
            'remark' => $remark,

        ]);

        echo 'success';



    }


    //导入下单处理
    public function underOrderRes(){
        if(isset($_POST['from_area_id']) && $_POST['from_area_id']){
            $from_area = $_POST['from_area_id'];
        }else{
            $from_area = Admin::user()->from_area;
        }





        $result = $this -> import($_FILES['file_column']['tmp_name'],$_POST['id'],$_POST['remark'],$from_area);

        if($result['result'] == 'error'){
            //出错
            if(isset($result['error_num'])){
                admin_toastr('第'.$result['error_num'].'行：'.$result['error_msg']);
            }else{
                admin_toastr($result['error_msg']);
            }


            return redirect(admin_base_path('underOrder/'.$_POST['id']));

        }

        admin_toastr('导入成功');

        return redirect(admin_base_path('underOrder/'.$_POST['id']));
    }


    public function addressRes(){
        $result = $this -> importAddress($_FILES['file_column']['tmp_name'],$_POST['id']);


        admin_toastr('导入成功');

        return redirect(admin_base_path('address/'.$_POST['id']));
    }


    //用户导入收货地址处理
    public function importAddress($filePath,$userid){
        $from_area = Admin::user()->from_area;
        //dd($from_area);
        //$filePath = 'storage/exports/'.iconv('UTF-8', 'GBK', '学生成绩').'.xls';
        Excel::load($filePath, function($reader) use( &$res ) {
            $reader = $reader->getSheet(0);
            $res = $reader->toArray();
        });



        foreach($res as $key => $vo) {
            //忽略第一行 表头
            if ($key == 0) {
                continue;
            }

            if($vo[0] && $vo[1] && $vo[2] && $vo[3] && $vo[4]){
            //收货人姓名  电话       地址      城市        省份
                //添加到user_address中
                $name = trim($vo[0]);
                $tel = trim($vo[1]);
                $address = trim($vo[2]);
                $city = trim($vo[3]);
                $province = trim($vo[4]);


                //查看地址中 是否包含省 市
                if(!strstr($address,$province)){
                    continue;
                }

                if(!strstr($address,$city)){
                    //市
                    continue;
                }

                $isMob="/^1[345789]{1}\d{9}$/";

                if(!preg_match($isMob,$tel)){

                    continue;

                }

                $isset = DB::table('user_address')
                    -> where(function($query)use($name,$tel,$address,$city,$province){
                        $query -> where([
                            'name' => $name
                        ]) -> orWhere([
                            'tel' => $tel
                        ]) -> orWhere([
                            'address' => $address
                        ]);
                    }) -> first();



                if(!$isset){
                    DB::table('user_address') -> insert([
                        'name' => $name,
                        'tel' => $tel,
                        'address' => $address,
                        'city' => $city,
                        'province' => $province,
                        'user_id' => $userid,
                        'created_at' => time(),
                        'updated_at' => time(),
                    ]);
                }
            }
        }




    }

    //用户下单导入处理
    public function import($filePath,$userid,$remark,$from_area){
        //dd($from_area);
        //$filePath = 'storage/exports/'.iconv('UTF-8', 'GBK', '学生成绩').'.xls';
        Excel::load($filePath, function($reader) use( &$res ) {
            $reader = $reader->getSheet(0);
            $res = $reader->toArray();
        });

        $config = [
            'app_id' => env('WECHAT_OFFICIAL_ACCOUNT_APPID'),
            'secret' => env('WECHAT_OFFICIAL_ACCOUNT_SECRET'),
        ];

        $app = Factory::officialAccount($config);

        /*
                array:2 [▼
          0 => array:7 [▼
            0 => "收件人"
            1 => "电话"
            2 => "地址"
            3 => "城市"
            4 => "省份"
            5 => "重量"
            6 => null
          ]
          1 => array:7 [▼
            0 => "赖奕如"
            1 => 13049278785.0
            2 => "广东省揭阳普宁市流沙镇汇润幸福里3栋保安室"
            3 => "揭阳市"
            4 => "广东省"
            5 => "4.95"
            6 => null
          ]
        ]
        */


        //检查数据
        //先分类所有的数据
        //先存入临时数据

        //收件人姓名
        $names = [];
        //收件人电话
        $tels = [];
        //收件人省份
        $provinces = [];
        //收件人市
        $citys = [];
        //收件人地址
        $address = [];
        //重量
        $weights = [];
        //每个包裹的价格
        $package_price = [];


        $count_values_name_arr = [];
        $count_values_tel_arr = [];
        $count_values_addressstr_arr = [];
        $is_super_package_num = false;
        $package_nums = [];
        $taxs_arr = [];
        foreach($res as $key => $vo){
            //忽略第一行 表头
            if($key == 0){
                continue;
            }

            if($vo['0'] && $vo['1'] && $vo['2'] && $vo['3'] && $vo['4'] && $vo['5']){
                //全部存在 才算真实的数据
                $names[] = trim($vo['0']);
                $tels[] = trim($vo['1']);
                $address[] = trim($vo['2']);
                $provinces[] = trim($vo['4']);
                $citys[] = trim($vo['3']);
                $weights[] = trim($vo['5']);
                $address_str[] = trim($vo['4']).trim($vo['3']).trim($vo['2']);

                $tax_str = '';

                //订单编号

                if(isset($vo['6'])){
                    $is_super_package_num = true;
                    $package_nums[] = trim($vo['6']);
                    //看下给的订单编号是否重复
                    if(in_array(trim($vo['6']),$package_nums)){
                        $count_package_nums = array_count_values($package_nums);
                        if($count_package_nums[trim($vo['6'])] > 1){
                            //重复
                            return [
                                'result' => 'error',
                                'error_msg' => '上传表中的订单编号互相重复',
                                'error_num' => $key + 1
                            ];
                        }

                    }
                }

                //申报物品
                if(isset($vo['7']) && isset($vo['8']) && isset($vo['9']) && isset($vo['10']) && isset($vo['11'])){
                    //名称、税号、价值、数量、重量
                    //检查税号存不存在
                    $tax_info = DB::table('goods_tax') -> where([
                        'code' => trim($vo['8'])
                    ]) -> first();
                    if(!$tax_info){
                        return [
                            'result' => 'error',
                            'error_msg' => '上传表中的税号1错误',
                            'error_num' => $key + 1
                        ];
                    }

                    $tax_str.= trim($vo['7']).'&&'.trim($vo['8']).'&&'.trim($vo['9']).'&&'.trim($vo['10']).'&&'.trim($vo['11']).'&&';
                }


                if(isset($vo['12']) && isset($vo['13']) && isset($vo['14']) && isset($vo['15']) && isset($vo['16'])){
                    //名称、税号、价值、数量、重量
                    //检查税号存不存在
                    $tax_info = DB::table('goods_tax') -> where([
                        'code' => trim($vo['13'])
                    ]) -> first();
                    if(!$tax_info){
                        return [
                            'result' => 'error',
                            'error_msg' => '上传表中的税号2错误',
                            'error_num' => $key + 1
                        ];
                    }
                    $tax_str.= trim($vo['12']).'&&'.trim($vo['13']).'&&'.trim($vo['14']).'&&'.trim($vo['15']).'&&'.trim($vo['16']).'&&';

                }

                if(isset($vo['17']) && isset($vo['18']) && isset($vo['19']) && isset($vo['20']) && isset($vo['21'])){
                    //名称、税号、价值、数量、重量
                    //检查税号存不存在
                    $tax_info = DB::table('goods_tax') -> where([
                        'code' => trim($vo['18'])
                    ]) -> first();
                    if(!$tax_info){
                        return [
                            'result' => 'error',
                            'error_msg' => '上传表中的税号3错误',
                            'error_num' => $key + 1
                        ];
                    }

                    $tax_str.= trim($vo['17']).'&&'.trim($vo['18']).'&&'.trim($vo['19']).'&&'.trim($vo['20']).'&&'.trim($vo['21']);

                }


                if($tax_str){
                    $temp = explode('&&',trim($tax_str,'&&'));

                    $temp_arr = array_chunk($temp,5);
                    $temp_arr_res = [];
                    foreach($temp_arr as $k_temp => $v_temp){
                        $temp_arr_res['s_content'.($k_temp+1)] = $v_temp[0];
                        $temp_arr_res['Tax_code'.($k_temp+1)] = $v_temp[1];
                        $temp_arr_res['s_price'.($k_temp+1)] = $v_temp[2];
                        $temp_arr_res['s_pieces'.($k_temp+1)] = $v_temp[3];
                        $temp_arr_res['s_weight'.($k_temp+1)] = $v_temp[4];
                    }
                    //dump($temp_arr_res);
                    $taxs_arr[] = $temp_arr_res;
                }else{
                    $taxs_arr[] = '';
                }



                //每次赋值的时候 都判断下 用过几次了
                $count_values_name_arr = array_count_values($names);
                $count_values_name = $count_values_name_arr[trim($vo['0'])];
                if($count_values_name == 4){
                    //名字重复
                    return [
                        'result' => 'error',
                        'error_msg' => '上传表中的名字互相重复',
                        'error_num' => $key + 1
                    ];
                }


                $count_values_tel_arr = array_count_values($tels);
                $count_values_tels = $count_values_tel_arr[trim($vo['1'])];
                if($count_values_tels == 4){
                    //电话重复
                    return [
                        'result' => 'error',
                        'error_msg' => '上传表中的电话号码互相重复',
                        'error_num' => $key + 1
                    ];
                }

                //省市地址 加起来重复
                $count_values_addressstr_arr = array_count_values($address_str);
                $count_values_addressstr = $count_values_addressstr_arr[trim($vo['4']).trim($vo['3']).trim($vo['2'])];
                if($count_values_addressstr == 4){
                    //电话重复
                    return [
                        'result' => 'error',
                        'error_msg' => '上传表中的地址互相重复',
                        'error_num' => $key + 1
                    ];
                }

                //查看地址中 是否包含省 市
                if(!strstr(trim($vo['2']),trim($vo['4']))){
                    //省
                    return [
                        'result' => 'error',
                        'error_msg' => '地址中没有包含省份',
                        'error_num' => $key + 1
                    ];
                }

                if(!strstr(trim($vo['2']),trim($vo['3']))){
                    //市
                    return [
                        'result' => 'error',
                        'error_msg' => '地址中没有包含市名',
                        'error_num' => $key + 1
                    ];
                }

            }

        }

        //数据处理完毕 --> 开始判断 有没有在别的订单中出现过
        //dump($taxs_arr);exit;

        //验证下 每个名字 每个电话 每个地址 在以前+这次 一共出现了几次
        //一个名字 一个uuid
        $uuid_names_arr = [];
        foreach($count_values_name_arr as $key => $vo){
            $uuid_names_arr[$key] = $this -> create_uuid();
            $count_pre = DB::table('packages') -> where([
                'wuliu_num' => '',
                'name' => trim($key),
                'flag' => 0
            ])  -> count();
            if(intval($count_pre) + intval($vo)  >= 4 ){
                //判断下 是第几行
                $temp = array_search(trim($key),$names);
                return [
                    'result' => 'error',
                    'error_msg' => '名字在未发货订单中已存在',
                    'error_num' => intval($temp) + 1
                ];
            }
        }

        foreach($count_values_tel_arr as $key => $vo){
            $count_pre = DB::table('packages') -> where([
                'wuliu_num' => '',
                'tel' => trim($key),
                'flag' => 0
            ])  -> count();
            if(intval($count_pre) + intval($vo)  >= 4 ){
                //判断下 是第几行
                $temp = array_search(trim($key),$tels);
                return [
                    'result' => 'error',
                    'error_msg' => '电话号码在未发货订单中已存在',
                    'error_num' => intval($temp) + 1
                ];
            }
        }

        foreach($count_values_addressstr_arr as $key => $vo){
            $count_pre = DB::table('packages') -> where([
                'wuliu_num' => '',
                'address' => trim($key),
                'flag' => 0
            ])  -> count();
            if(intval($count_pre) + intval($vo)  >= 4 ){
                //判断下 是第几行
                $temp = array_search(trim($key),$address);
                return [
                    'result' => 'error',
                    'error_msg' => '地址在未发货订单中已存在',
                    'error_num' => intval($temp) + 1
                ];
            }
        }


        if($is_super_package_num){
            //如果有订单编号
            //查看订单编号是否个数一样
            if(count($weights) != count($package_nums)){
                return [
                    'result' => 'error',
                    'error_msg' => '订单编号短缺',
                ];
            }
            //检查 订单编号 是否存在
            foreach($package_nums as $vo){
                $package_info = DB::table('packages')
                    -> where('package_num','=',$vo)
                    -> where('flag','=',0)
                    -> first();
                if($package_info){
                    return [
                        'result' => 'error',
                        'error_msg' => '订单编号'.$vo.'已存在',
                    ];
                }

            }
        }


        //验证完毕=======>

        //直接生成订单


        //特定的区域  导入下单之后  自动变成已支付
        $area_info = DB::table('area_name') -> where([
            'id' => $from_area
        ]) -> first();
        if($area_info -> auto_pay){
            $pay_status = 1;
            $pay_type = 9;
        }else{
            $pay_status = 0;
            $pay_type = 0;
        }


        //属于的区域
        $model = new Order();
        $model -> underOrder([
            'weights' => $weights,
            'from_area' => $from_area,
            'user_id' => $userid,
            'names' => $names,
            'address' => $address,
            'provinces' => $provinces,
            'citys' => $citys,
            'tels' => $tels,
            'uuid_names_arr' => $uuid_names_arr,
            'remark' => $remark,
            'package_nums' => $package_nums,
            'pay_status' => $pay_status,
            'pay_type' => $pay_type,
            'taxs_arr' => $taxs_arr
        ]);



        return [
            'result' => 'success'
        ];

        //dump($res);exit;




    }


    function create_uuid($prefix = ""){    //可以指定前缀
        $str = md5(uniqid(mt_rand(), true));
        $uuid  = substr($str,0,8) . '-';
        $uuid .= substr($str,8,4) . '-';
        $uuid .= substr($str,12,4) . '-';
        $uuid .= substr($str,16,4) . '-';
        $uuid .= substr($str,20,12);
        return $prefix . $uuid;
    }

    //删除地址
    public function deleteUserAddress(){
        //删除掉
        DB::table('user_address') -> whereIn('id',$_POST['check_arr']) -> delete();
        echo 'success';
    }


}
