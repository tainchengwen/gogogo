<?php

namespace App\Admin\Controllers;

use App\Admin\Extensions\BatchScreen;
use App\Admin\Extensions\MakeWarningPackageOrder;
use App\Admin\Extensions\WarningExcelExpoter;
use App\AreaName;
use App\Configure;
use App\Order;
use App\WarningPackage;
use App\Http\Controllers\Controller;
use App\WxUser;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Support\Facades\DB;

class WarningPackageController extends Controller
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
            ->header('异常件')
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
            ->header('异常件')
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
            ->header('异常件')
            ->description('创建')
            ->body($this->form());
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new WarningPackage);


        if(isset($_GET['packages_nums']) && $_GET['packages_nums']){
            $packages_nums = explode("\r\n",$_GET['packages_nums']);
            foreach($packages_nums as $k => $vo){
                $packages_nums[$k] = trim($vo);
            }
            $grid -> model() -> whereIn('number',$packages_nums);

        }

        $grid->filter(function($filter){
            $filter->where(function ($query) {
                //先检查此名字的userid
                $userdata = DB::table('wxuser') -> where('nickname','like',"%"."{$this->input}"."%") -> get();
                if($userdata){
                    $userids = [];
                    foreach($userdata as $vo){
                        $userids[] = $vo -> id;
                    }
                }
                $query -> whereIn('user_id',$userids);
            }, '客户姓名');

        });

        $grid -> model() -> orderBy('id','desc');

        $grid->id('Id');
        //$grid->create_date('日期');
        $grid->number('单号');
        $grid->number_pre('原始单号');
        $grid->order_id('是否下单') -> display(function($val){
            if($val){
                $order_info = DB::table('order') -> where([
                    'id' => $val
                ]) -> first();
                return $order_info -> order_num;
            }
        });
        $grid->area_id('区域') -> display(function ($value){
            if($value){
                $area_name = AreaName::find($value);
                if($area_name){
                    return $area_name -> area_name;
                }
            }
        });
        $grid->user_id('客户') -> display(function($value){
            if($value){
                $userinfo = WxUser::find($value);
                if($userinfo){
                    return $userinfo -> nickname;
                }
            }
        });

        $grid -> name('收件人');
        //$grid -> tel('电话');
        //$grid -> address('地址');

        //$grid->status('状态');
        $grid->tax_price('税金');

        $reasons_config = config('admin.warning_reason');
        $grid -> reason('异常原因') -> editable('select', $reasons_config);

        /*
        $grid->reason('异常原因') -> display(function($value){
            $warning_reason = config('admin.warning_reason');
            if(isset($warning_reason[$value])){
                return $warning_reason[$value];
            }
        });
        */

        $tracking_status = config('admin.warning_tracking_status');
        $grid -> tracking_status('最新物流状态') -> editable('select', $tracking_status);



        $grid->remark('备注')->editable('textarea');
        /*
        $grid->created_at('创建时间') -> display(function($value){
            return date('Y-m-d H:i',$value);
        });
        */
        $grid->disableExport();

        $grid->tools(function ($tools) {

            $tools->batch(function ($batch) {
                //$batch->disableDelete();
                $batch->add('下单',new MakeWarningPackageOrder());

            });

            //批量筛选按钮
            $tools -> append(new BatchScreen(3));
        });


        $grid->disableCreateButton();


        //$grid->exporter( new WarningExcelExpoter());
        return $grid;
    }


    //异常件批量下单
    public function MakeWarningPackageOrder(){
        $ids = $_POST['ids'];
        $temp = [];


        foreach($ids as $vo){
            $info = DB::table('warning_package')
                -> where([
                    'id' => $vo,
                ]) -> first();
            //判断下此单 有没有在packages中出现
            $package_info = DB::table('packages')
                -> where([
                    'flag' => 0,
                    'package_num' => $info -> number
                ]) -> first();

            if(!$package_info){
                //if($info){
                $temp[] = $info;
            }
        }

        if(count($temp)){
            //批量下单页面
            return   view('admin.MakeWarningPackageOrderPage') -> with([
                'data' => $temp
            ]);
        }else{
            echo '500';exit;
        }
    }


    public function submitWarningOrder(){
        //选中的 是不需要付款的

        $check_ids = isset($_POST['check_ids'])?$_POST['check_ids']:[];

        $weights = $_POST['weights'];
        $ids = $_POST['names'];
        //下单
        foreach($ids as $k => $vo){
            $id = $vo;
            $weight = floatval($weights[$k]);

            $warning_package = DB::table('warning_package')
                -> where([
                    'id' => $id
                ]) -> first();
            if(!$warning_package){
                continue;
            }

            //查下之前此单的信息 如果查不到，则 不下单
            $package_info = DB::table('packages') -> where([
                'wuliu_num' => $warning_package -> number
            ]) -> first();
            if(!$package_info){
                continue;
            }

            //寻找之前的订单
            $order_info = DB::table('order') -> where([
                'id' => $package_info -> order_id
            ]) -> first();
            if(!$order_info){
                continue;
            }



            $model = new Order();


            $order_id = $model -> underOrder([
                'weights' => [$weight],
                'from_area' => $order_info -> from_area,
                'user_id' => $order_info -> userid,
                'names' => [$package_info -> name],
                'address' => [$package_info -> address],
                'provinces' => [$package_info -> province],
                'citys' => [$package_info -> city],
                'tels' => [$package_info -> tel],
                'uuid_names_arr' => [Configure::create_uuid()],
                'remark' => '退件下单',
                'package_nums' => [$warning_package -> number],
                'not_need_pay' => in_array($id,$check_ids)?1:0,
                'pay_status' => 1,
                'pay_type' => 9,
                'is_min_order' => 1
            ]);

            DB::table('order') -> where([
                'id' => $order_id
            ]) -> update([
                'warning_package_id' => $id
            ]);

            DB::table('warning_package')-> where([
                    'id' => $id
                ]) -> update([
                'order_id' => $order_id
            ]);



        }
    }



    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(WarningPackage::findOrFail($id));

        $show->id('Id');
        $show->create_date('Create date');
        $show->number('Number');
        $show->area_id('Area id');
        $show->addressee('Addressee');
        $show->status('Status');
        $show->tax_price('Tax price');
        $show->remark('Remark');
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
        $form = new Form(new WarningPackage);

        $form->date('create_date', '日期')->rules('required');
        $form->text('number', '单号')->rules('required');
        $form->text('number_pre', '原始单号');

        $area_names = DB::table('area_name') -> get();
        foreach($area_names as $vo){
            $area_setting[$vo -> id] = $vo -> area_name;
        }
        $form->select('area_id','区域')->options($area_setting)->rules('required');

        $wxuser = DB::table('wxuser') -> get(['nickname','id']);
        //dd($wxuser);
        $option_wxuser = [];
        foreach($wxuser as $vo){
            $option_wxuser[$vo -> id] = 'ID:'.$vo -> id.'---昵称:'.$vo -> nickname;
        }

        $form -> select('user_id','客户') -> options($option_wxuser)->rules('required');
        $warning_reason = config('admin.warning_reason');
        $form -> select('reason','异常原因') -> options($warning_reason) -> rules('required');
        $tracking_status = config('admin.warning_tracking_status');
        $form -> select('tracking_status','最新物流状态')-> options($tracking_status) -> rules('required');

        $form->text('name', '收件人');
        //$form->text('tel', '电话');
        //$form->text('address', '地址');
        //$form->number('status', '状态');
        $form->decimal('tax_price', '税金');
        $form->textarea('remark', '备注');

        return $form;
    }
}
