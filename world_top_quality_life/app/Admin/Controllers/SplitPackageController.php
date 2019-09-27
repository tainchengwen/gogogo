<?php

namespace App\Admin\Controllers;

use App\Admin\Extensions\ImportSplitData;
use App\Admin\Extensions\SplitOrder;
use App\Admin\Extensions\SplitPackageExcelExpoter;
use App\AreaName;
use App\NoSplitPackage;
use App\Order;
use App\PrintSequeue;
use App\SplitOrderQueue;
use App\SplitPackage;
use App\Http\Controllers\Controller;
use App\WxUser;
use Encore\Admin\Admin;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Encore\Admin\Widgets\Table;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Excel;
use Illuminate\Support\Facades\Log;

class SplitPackageController extends Controller
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
            ->header('拆单列表')
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
        return \Encore\Admin\Facades\Admin::grid(SplitPackage::class,function(Grid $grid){
            $grid->filter(function($filter){
                $filter->expand();

                //快捷筛选 默认没有下单的
                $filter->scope('order_status_noorder', '没有下单')->where('is_order', 0);
                $filter->scope('order_status_order', '已下单')->where('is_order', 1);


                $filter->column(1/2, function ($filter) {
                    $area_names = DB::table('area_name') -> get();
                    foreach($area_names as $vo){
                        $area_setting[$vo -> id] = $vo -> area_name;
                    }
                    $filter->where(function ($query) {
                        $input = $this->input;

                        $query -> whereHas('packages', function ($query)use($input){
                            $query->where('from_area', '=', $input);
                        });
                    }, '区域')->select($area_setting);

                    $filter->equal('is_order','是否下单') -> select([
                        '0' => '否',
                        '1' => '是'
                    ]);


                    $filter -> equal('is_print','贴单状态') -> select([
                        '1' => '已贴单',
                        '0' => '未贴单'
                    ]);


                });

                $filter->column(1/2, function ($filter) {
                    $filter->equal('package_wuliu_num','拆单前单号');
                    $filter->equal('sp_numbers','拆单后单号');
                    //是否重量为空
                    $filter-> where(function($query){
                        $input = $this->input;
                        if($input == 1){
                            $query -> where('weight','>','0');
                        }elseif($input == 2){
                            $query -> where('weight','0') -> orWhereNull('weight');
                        }
                    },'是否维护重量') -> select([
                        '1' => '是',
                        '2' => '否'
                    ]);
                });


            });


            $grid->no('拆单后序号') -> display(function($val){
                return sprintf('%02s',$val);
            });
            $grid->package_id('包裹编号');
            //$grid->package_wuliu_num('拆分前单号');
            $grid->sp_numbers('拆分后单号');
            $grid -> userid('用户') -> display(function($val){
                $user = WxUser::find($val);
                if($user){
                    return $user -> nickname;
                }
            });
            $grid->weight('重量');

            $grid->column('name','姓名');
            $grid->column('tel','电话');
            $grid->column('address','地址');
            $grid->column('from_area','区域') -> display(function($val){
                $area_info = AreaName::find($val);
                if($area_info){
                    return $area_info -> area_name;
                }

            });
            $grid->is_order('是否下单') -> display(function($value){
                if($value){
                    return '是';
                }else{
                    return '否';
                }
            });

            $grid->column('是否贴单','是否贴单') -> display(function($val){
                if($this -> is_print){
                    return '已贴单';
                }else{
                    return '未贴单';
                }
            });


            $grid->updated_at('更新时间') -> display(function($value){
                return date('Y-m-d H:i',$value);
            });
            //$grid->disableExport();
            $grid->exporter(new SplitPackageExcelExpoter());


            $grid->tools(function ($tools) {
                $tools->batch(function ($batch) {
                    //$batch->disableDelete();
                    $batch->add('下单',new SplitOrder());
                });
            });
            //$grid->disableRowSelector();
            $grid->actions(function ($actions) {
                //$id = $actions->getKey();
                $actions->disableDelete();
                //$actions->disableEdit();

            });

            $grid->disableCreateButton();
            $grid->tools(function ($tools) {
                $tools->append(new ImportSplitData());
            });
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
        $show = new Show(SplitPackage::findOrFail($id));

        $show->id('Id');
        $show->package_id('Package id');
        $show->sp_numbers('Sp numbers');
        $show->package_wuliu_num('Package wuliu num');
        $show->created_at('Created at');
        $show->updated_at('Updated at');
        $show->weight('Weight');

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new SplitPackage);
        $form -> display('package_id', '原包裹id');
        $form -> display('package_wuliu_num', '拆分前单号');
        $form -> display('sp_numbers', '拆分后单号');


        $form -> text('name','收件人');
        $form -> text('tel','电话');
        $form -> text('province','省份');
        $form -> text('city','城市');
        $form -> text('address','地址');
        $form->decimal('weight', 'Weight');


        return $form;
    }


    function importSplitPackage(Content $content){
        return $content
            ->header('拆分后重量导入')
            ->description('')
            ->body($this->importSplitPackageForm());
    }

    function importNoSplitPackage(Content $content){
        return $content
            ->header('导入不允许拆单的单号')
            ->description('')
            ->body($this->importNoSplitPackageForm());
    }


    function importSplitPackageForm(){
        $form = new Form(new SplitPackage);
        $form -> setAction(admin_base_path('importSplitPackageRes'));
        $form -> file('file_name','excel');
        return $form;
    }


    function importNoSplitPackageForm(){
        $form = new Form(new SplitPackage);
        $form -> setAction(admin_base_path('importNoSplitPackageRes'));
        $form -> file('file_name','excel');



        //把目前导入的 展示出来
        $headers = ['ID', '单号', '导入时间'];

        $numbers = NoSplitPackage::get();
        $rows = [];
        foreach($numbers as $k => $vo){
            $rows[$k] = [
                $vo -> id,
                $vo -> number,
                $vo -> created_at
            ];
        }




        $table = new Table($headers, $rows);
        $form -> html($table);

        return $form;
    }

    function importNoSplitPackageRes(){

        if(!isset($_FILES['file_name']['tmp_name'])){
            echo "<script>history.back();</script>";
        }
        $filePath = $_FILES['file_name']['tmp_name'];
        \Maatwebsite\Excel\Facades\Excel::load($filePath, function($reader) use( &$res ) {
            $reader = $reader->getSheet(0);
            $res = $reader->toArray();
        });


        $number = 0;
        foreach($res as $k => $vo){
            if($k == 0){
                continue;
            }

            //把每列存入
            if(!trim($vo[0])){
                continue;
            }

            NoSplitPackage::insert([
                'number' => trim($vo[0]),
                'created_at' => time(),
                'updated_at' => time(),
            ]);

            $number ++;
        }

        admin_toastr('导入成功'.$number.'条');
        return redirect(admin_base_path('importNoSplitPackage'));


    }


    function importSplitPackageRes(){

        if(!isset($_FILES['file_name']['tmp_name'])){
            echo "<script>history.back();</script>";
        }
        $filePath = $_FILES['file_name']['tmp_name'];
        \Maatwebsite\Excel\Facades\Excel::load($filePath, function($reader) use( &$res ) {
            $reader = $reader->getSheet(0);
            $res = $reader->toArray();
        });



        //EK415360607HK-01 3.9
        //EK415360607HK-02 2.2
        $sp_numbers = [];

        $number = 0;
        $model_order = new Order();
        foreach($res as $k => $vo){
            if($k == 0){
                continue;
            }

            if(!trim($vo[0]) || !trim($vo[1])){
                continue;
            }

            //查看单号在不在拆单里边
            $info = SplitPackage::where('sp_numbers',trim($vo[0])) -> first();
            if(!$info){
                admin_toastr('没有拆过此单号：'.$vo[0],'error');
                return redirect(admin_base_path('importSplitPackage'));
            }


            //通过重量获取 申报数据
            $goods_id = $model_order -> getGoodsPartemerId(floatval($vo[1]));

            SplitPackage::where('sp_numbers',trim($vo[0])) -> update([
                'weight' => floatval($vo[1]),
                'goods_id' => $goods_id
            ]);
            $number ++;

            /*
            $info = SplitPackage::where('sp_numbers',trim($vo[0])) -> first();
            $sp_numbers[] =  $info;
            */
        }

        admin_toastr('更新成功'.$number.'条');
        return redirect(admin_base_path('importSplitPackage'));


    }



    //拆单后下单
    public function splitOrder(){
        //序号
        $no = intval($_POST['data']);
        //看下序号下单的队列 最大的时间是多少
        $queue_data = SplitOrderQueue::orderBy('id','desc') -> first();
        if($queue_data){
            /*
            //看比今天 是否超过24h
            $max_time = intval(strtotime($queue_data -> created_at->toDateTimeString()));
            if(time() - $max_time < 86400*24){
                //没有超过24h 看他是否序号相同
                //序号不同 不可以下单
                if($queue_data -> no != $no){
                    return [
                        'code' => 'error',
                        'msg' => '下这个序号的单，离上次还不到24h'
                    ];
                }
            }
            */
        }

        //区分区域 userid 直接下
        $packages = SplitPackage::where('is_order',0) -> where('no',$no) -> where('weight','>',0) -> get();
        $b_arr = [];
        foreach($packages as $k => $vo){
            $b_arr[$vo -> from_area.'-'.$vo -> userid][] = $vo;
        }



        if($b_arr){
            foreach($b_arr as $k => $vo){
                //这里都是一个区域 一个用户的所有单
                //凑数据
                //从k 中拿出area userid
                $temp = explode('-',$k);
                $from_area = $temp[0];
                $userid = $temp[1];
                $weights = [];
                $names = [];
                $address = [];
                $provinces = [];
                $citys = [];
                $tels = [];
                $uuid_names_arr = [];
                $split_id = [];
                $package_nums = [];
                foreach($vo as $value){
                    $weights[] = $value -> weight;
                    $names[] = $value -> name;
                    $address[] = $value -> address;
                    $provinces[] = $value -> province;
                    $citys[] = $value -> city;
                    $tels[] = $value -> tel;
                    $uuid_names_arr[$value -> name] = $this -> create_uuid();
                    $split_id[] = $value -> id;
                    $package_nums[] = $value -> sp_numbers;
                }



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
                    'remark' => '拆单后下单',
                    'pay_status' => 1,
                    'pay_type' => 2,
                    'split_id' =>$split_id,
                    'package_nums' => $package_nums
                ]);


                //回写packages_split is_order字段
                foreach($split_id as $id){
                    SplitPackage::where('id',$id) -> update([
                        'is_order' => 1
                    ]);
                }


            }

            //记录此次下单时间 下单序号到queue里
            SplitOrderQueue::insert([
                'created_at' => time(),
                'updated_at' => time(),
                'no' => $no,
            ]);

            return [
                'code' => 'success',
                'msg' => '下单成功'
            ];
        }else{
            return [
                'code' => 'error',
                'msg' => '没有要下单的数据'
            ];
        }

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

}
