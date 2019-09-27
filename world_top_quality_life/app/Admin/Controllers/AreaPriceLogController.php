<?php

namespace App\Admin\Controllers;

use App\Admin\Extensions\PriceLogExport;
use App\AreaPriceLog;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;
use Encore\Admin\Widgets\Box;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class AreaPriceLogController extends Controller
{
    use ModelForm;
    protected $cellLetter = [
        'A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q',
        'R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD',
        'AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN',
        'AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ'
    ];
    /**
     * Index interface.
     *
     * @return Content
     */
    public function index()
    {
        return Admin::content(function (Content $content) {
            $from_area = Admin::user() -> from_area;
            $area_info = DB::table('area_name') -> where([
                'id' => $from_area
            ]) -> first();

            $content->header('区域余额');
            $content->description("明细");
            $content->body("<a style='color:red;font-size:18px;'>账户余额:".$area_info -> price."</a>");
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

            $content->header('header');
            $content->description('description');

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
        return Admin::grid(AreaPriceLog::class, function (Grid $grid) {
            //区域只能看到自己下边的
            //admin 可以看所有的
            $admin_user_name = Admin::user() -> username;
            $from_area = Admin::user()->from_area;
            //dd($admin_user_name);
            if(!strstr($admin_user_name,'admin')){
                if(in_array($admin_user_name,config('admin.partner_number'))){

                    //他自己新增的区域
                    $area_names = DB::table('area_name') -> where([
                        'admin_user_name' => $admin_user_name
                    ]) -> get();
                    if(count($area_names)){
                        $area_ids = [];
                        foreach($area_names as $vo){
                            $area_ids[] = $vo -> id;
                        }
                        $grid -> model()->whereIn('area_id',$area_ids);
                    }else{

                        $grid->model()->where('area_id',$from_area);
                    }
                }else{
                    $grid->model()->where('area_id',$from_area);
                }

            }

            $grid -> model() -> orderBy('id','desc');

            $grid->filter(function($filter){
                $filter->where(function ($query) {
                    //查找区域名称
                    $area_info = DB::table('area_name') -> where('area_name','like','%'."{$this->input}".'%') -> first();
                    if($area_info){
                        $query -> where('area_id','=',$area_info -> id);
                    }

                }, '区域');


                $filter->where(function ($query) {

                    $query -> where('package_num','like',"{$this->input}");
                }, '包裹编号');

                $filter->where(function ($query) {
                    $input = strtotime($this->input);
                    $query->where('created_at', '>=', "{$input}");

                }, '日期>=')->date();

                $filter->where(function ($query) {
                    $input = strtotime($this->input);
                    $query->where('created_at', '<=', "{$input}");

                }, '日期<=')->date();

                $price_type = config('admin.area_price_setting');
                //交易类型搜索
                $filter -> where(function($query){
                    $input = $this->input;
                    $query->where('type', "{$input}");
                },'交易类型') -> select($price_type);


                // 去掉默认的id过滤器
                //$filter->disableIdFilter();

            });

            $grid -> area_id('区域名称')->display(function($value){
                $area_info = DB::table('area_name') -> where([
                    'id' => $value
                ]) -> first();
                if($area_info){
                    return $area_info -> area_name;
                }

            });

            $grid->created_at('交易时间')->display(function($value){
                return date('Y-m-d H:i',$value);
            });


            $grid -> id('交易金额') -> display(function($id){
                $type = AreaPriceLog::find($id) -> type;
                $price = AreaPriceLog::find($id) -> price;
                $config_sign = config('admin.area_price_setting_sign');
                if($config_sign[$type]){
                    return '+'.$price;
                }else{
                    return '-'.$price;
                }

            });
            $grid -> package_num('单号');
            $grid-> type('交易类型') -> display(function($value){
               $config = config('admin.area_price_setting');
               $config_sign = config('admin.area_price_setting_sign');

               return $config[$value];
            });
            $grid -> remark('备注');



            $grid->actions(function ($actions) {
                $id = $actions->getKey();
                $actions->disableDelete();
                $actions->disableEdit();
            });

            $grid->tools(function ($tools) {
                $tools->batch(function ($batch) {
                    $batch->disableDelete();
                });
            });
            $grid->tools(function ($tools) {

                //分模板导出  导出模板按钮
                $tools -> append(new PriceLogExport());


            });
            $grid->disableCreateButton();
            $grid->disableExport();
            $grid->disableRowSelector();
            $grid->disableActions();
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Admin::form(AreaPriceLog::class, function (Form $form) {

            $form->display('id', 'ID');

            $form->display('created_at', 'Created At');
            $form->display('updated_at', 'Updated At');
        });
    }

    public function exportPriceLog(){
        //查出订单
        //属于的区域
        $from_area = Admin::user()->from_area;
        $username = Admin::user()->username;


        $cellData = [
            ['区域名称',	'交易时间',	'交易金额','单号','交易类型']
        ];

        $logs = DB::table('area_price_log') -> where([
            'area_id' => $from_area
        ]) -> orderBy('id','desc') -> get();
        $area_info = DB::table('area_name') -> where([
            'id' => $from_area
        ]) -> first();
        $config = config('admin.area_price_setting');
        //正负号
        $config_sign = config('admin.area_price_setting_sign');

        foreach($logs as $vo){
            if($config_sign[$vo -> type]){
                //正
                $sign = '';
            }else{
                $sign = '-';
            }
            $cellData[] = [
                $area_info -> area_name,
                date('Y-m-d H:i:s',$vo -> created_at),
                //拼正负
                $sign.$vo -> price,
                $vo -> package_num,
                $config[$vo -> type]
            ];
        }

        //Log::info('cellData',$cellData);
        Excel::create(date('Y-m-d-H-i').'余额明细',function($excel) use ($cellData){
            $excel->sheet('余额明细', function($sheet) use ($cellData){
                $sheet->rows($cellData);
                for ($i = 1; $i <= count($cellData[0]) + 1; $i++) {
                    $sheet->setWidth($this->cellLetter[$i - 1], 30);
                }
            });
        })->export('xlsx');
    }
}
