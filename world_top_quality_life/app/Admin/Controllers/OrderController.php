<?php

namespace App\Admin\Controllers;

use App\Admin\Extensions\BatchScreen;
use App\Admin\Extensions\CancelPackets;
use App\Admin\Extensions\CheckRow;
use App\Admin\Extensions\DeclareAll;
use App\Admin\Extensions\EmsPackageExcelExpoter;
use App\Admin\Extensions\ExcelExpoter;
use App\Admin\Extensions\ExportApi;
use App\Admin\Extensions\MailStatus;
use App\Admin\Extensions\OrderExport;
use App\Admin\Extensions\PackageExcelExpoter;
use App\Admin\Extensions\PackageSearchPdfApi;
use App\Admin\Extensions\PackageSearchPrintPdf;
use App\Admin\Extensions\PdfApi;
use App\Admin\Extensions\PdfApip;
use App\Admin\Extensions\PrintPdf;
use App\Admin\Extensions\TrackAlert;
use App\Admin\Extensions\TrackAlerts;
use App\Admin\Extensions\Trashed;
use App\Admin\Extensions\UpdateMailPayLog;
use App\Admin\Extensions\Vip;
use App\AreaName;
use App\CommodityCodeTemp;
use App\Http\Controllers\Controller;
use App\Jobs\GetMailPayLog;
use App\Order;
use App\Package;
use App\PrintSequeue;
use App\Repertory;
use App\SplitPackage;
use App\WxUser;
use Encore\Admin\Controllers\ModelForm;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Widgets\Table;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\DB;
use EasyWeChat\Factory;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfReader\PdfReader;
use Imagick;
use ImagickPixel;

class OrderController extends Controller
{


    public function index(){
        return Admin::content(function (Content $content)  {

            $content->header('订单列表');
            $content->description('');

            $content->body($this -> grid());
            //$content -> body("<div style='height:200px;width:100px;'></div>");
        });
    }

    protected function grid()
    {
        return Admin::grid(Order::class, function (Grid $grid) {


            $grid->id('ID');
            $grid->model()->where('flag','0');
            $grid->model()->orderBy('id', 'desc');


            if(isset($_GET['packages_nums']) && $_GET['packages_nums']){
                /*
                adsfasf1\r\n
                asdfasfd1

                */

                $packages_nums = explode("\r\n",$_GET['packages_nums']);
                foreach($packages_nums as $k => $vo){
                    $packages_nums[$k] = trim($vo);
                }
                //找 包裹
                $packages  = DB::table('packages')
                    -> whereIn('package_num',$packages_nums)
                    -> where('flag',0)
                    -> select([
                        'order_id'
                    ])
                    -> get();
                if(count($packages)){
                    $order_ids = [];
                    foreach($packages as $vo){
                        $order_ids[] = $vo -> order_id;
                    }
                    $grid -> model() -> whereIn('id',$order_ids);
                }

                //dd($_GET['packages_nums']);
            }

            //属于的区域
            //admin 可以看所有的
            $admin_user_name = Admin::user() -> username;
            $from_area = Admin::user()->from_area;

            //通过区域 找到所属的超级管理员
            $from_area_admin_info = DB::table('area_name') -> where([
                'id' => $from_area
            ]) -> first();
            $from_area_admin_name = $from_area_admin_info -> admin_user_name;

            //dd($admin_user_name);
            if(!strstr($admin_user_name,'admin')){
                //如果是合作人，则能看到他自己新增区域下的订单
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
                        $grid -> model()->whereIn('from_area',$area_ids);
                    }else{

                        $grid->model()->where('from_area',$from_area);
                    }
                }else{
                    $grid->model()->where('from_area',$from_area);
                }

            }



            $grid->expandFilter();
            $grid->filter(function($filter)use($admin_user_name){

                $filter->column(1/3, function ($filter)use($admin_user_name) {
                    $filter->where(function ($query) {
                        //先检查此名字的userid
                        $userdata = DB::table('wxuser') -> where('nickname','like',"%"."{$this->input}"."%") -> get();
                        if($userdata){
                            $userids = [];
                            foreach($userdata as $vo){
                                $userids[] = $vo -> id;
                            }
                        }
                        $query -> whereIn('userid',$userids);
                    }, '客户姓名');



                    //区域筛选
                    if(strstr($admin_user_name,'admin')) {
                        $area_names = DB::table('area_name') -> get();
                        foreach($area_names as $vo){
                            $area_setting[$vo -> id] = $vo -> area_name;
                        }
                        $filter->where(function ($query) {
                            $input = $this->input;
                            $query->where('from_area', '=', "{$input}");
                        }, '区域')->select($area_setting);
                    }

                    if(in_array($admin_user_name,config('admin.partner_number'))){
                        //他自己新增的区域
                        $area_names = DB::table('area_name') -> where([
                            'admin_user_name' => $admin_user_name
                        ]) -> get();
                        if(count($area_names)){
                            foreach($area_names as $vo){
                                $area_setting[$vo -> id] = $vo -> area_name;
                            }
                            $filter->where(function ($query) {
                                $input = $this->input;
                                $query->where('from_area', '=', "{$input}");
                            }, '区域')->select($area_setting);

                        }
                    }

                    //新订单状态筛选 发货状态
                    $status_setting = config('admin.order_status_new');
                    $filter->where(function ($query) {
                        $input = $this->input;
                        if($input == '10'){
                            //全部
                            $query->where('status','!=',9 );
                        }else{
                            $query->where('status','=',"{$input}");
                        }
                    }, '发货状态')->select($status_setting);

                    //$filter->where('user_id') -> column('12');


                    //新订单状态筛选 支付状态
                    $pay_status_setting = config('admin.pay_status_new');
                    $filter->where(function ($query) {
                        $input = $this->input;
                        if($input == '10'){
                            //全部
                            $query->where('pay_status','!=',9 );
                        }else{
                            $query->where('pay_status','=',"{$input}");
                        }



                    }, '付款状态')->select($pay_status_setting);




                });
                $filter->column(1/3, function ($filter) {
                    $filter->where(function ($query) {

                        $query -> where('order_num','like',"%"."{$this->input}"."%");
                    }, '订单编号');

//筛选包裹编号
                    $filter->where(function ($query) {

                        //根据包裹编号查order_id
                        $package_info = DB::table('packages') -> where('package_num', 'like', "{$this->input}") -> first();
                        //dd($package_info);
                        if($package_info){
                            $query -> where('id','=',$package_info -> order_id);
                        }else{
                            $query -> where('id','=',0);
                        }

                    }, '包裹编号');


                    //筛选运单号
                    $filter->where(function ($query) {

                        //根据包裹编号查order_id
                        $package_info = DB::table('packages') -> where('wuliu_num', 'like', "{$this->input}") -> first();
                        //dd($package_info);
                        if($package_info){
                            $query -> where('id','=',$package_info -> order_id);
                        }else{
                            $query -> where('id','=',0);
                        }

                    }, '运单编号');

                    $filter -> where(function($query){
                        $repertory_info = DB::table('repertory') -> where('numbers' ,'like','%'.$this->input.'%') -> get();
                        if(count($repertory_info)){
                            $repertory_ids = [];
                            //dd($repertory_info);
                            foreach($repertory_info as $vo){
                                $repertory_ids[] = $vo -> id;
                            }
                            $query -> whereIn('repertory_id',$repertory_ids);
                        }else{
                            $query -> where('id','=',0);
                        }

                    },'物流编号');







                });
                $filter->column(1/3, function ($filter) {
//筛选包裹编号

                    $filter->equal('pay_type','支付方式')->select([
                        '0' => '余额支付',
                        '1' => '微信支付',
                        '2' => '支付宝支付',
                        '9' => '虚拟支付',
                    ]);

                    $filter->where(function ($query) {
                        $input = strtotime($this->input);
                        $query->where('created_at', '>=', "{$input}");

                    }, '日期大于')->date();

                    $filter->where(function ($query) {
                        $input = strtotime($this->input);
                        $query->where('created_at', '<=', "{$input}");

                    }, '日期小于')->date();
                });








            });

            $conditions = $grid->getFilter()->conditions();

            //订单的默认状态
            //假如没有筛选发货状态  并且 没有筛选支付状态时
            if(!isset($conditions[1]) && !isset($conditions[2])){
                //dump($conditions);
                $grid->model()->where('status','!=',9);
            }


            

            //$grid -> switchGroup();

            $grid->actions(function ($actions) {
                $actions->disableDelete();
                $actions->disableEdit();
                // 添加操作
                $actions->append(new CheckRow($actions->row));
            });



            $grid->tools(function ($tools)use($admin_user_name) {
                $tools->batch(function ($batch)use($admin_user_name) {
                    $batch->disableDelete();


                    /*
                    //获取 后台管理员名字
                    if($admin_user_name == 'XS001'){
                        $batch->add('生成面单HK(XS001)', new ExportApi(5)); //这里的5 是route_id
                    }else{
                        $batch->add('生成面单HK(NN100)', new ExportApi(4)); //这里的4 是route_id

                        $batch->add('生成面单MO(NN100)', new ExportApi(6)); //这里的4 是route_id
                    }

                    */
                    $batch->add('生成pdf(勾选订单)',new PdfApi());
                    $batch->add('生成pdf(勾选包裹)',new PdfApip());




                    if(strstr($admin_user_name,'admin')){
                        $batch->add('打印pdf(订单)',new PrintPdf('order'));
                        $batch->add('打印pdf(包裹)',new PrintPdf('package'));
                        $batch->add('批量修改申报',new DeclareAll());
                    }




                });
            });

            //导出历史订单按钮
            $grid->exporter( new ExcelExpoter($admin_user_name));
            /*
            if(strstr($admin_user_name,'admin')){
                $grid->exporter( new ExcelExpoter($admin_user_name));
            }else{
                $grid->disableExport();
            }
            */
            //$grid->disableExport();




            $grid->tools(function ($tools)use($from_area_admin_name,$admin_user_name) {

                //dd($from_area_admin_name);

                $tools -> append(new OrderExport($from_area_admin_name));

                /*
                if($from_area_admin_name == 'admin'){
                    //分模板导出  导出模板按钮
                    $tools -> append(new OrderExport());
                }
                if($from_area_admin_name == 'xx'){

                }
                */


                //批量取消订单按钮
                $tools -> append(new CancelPackets($from_area_admin_name,$admin_user_name));


                //批量筛选按钮
                $tools -> append(new BatchScreen(1));

                //批量 取消面单按钮


            });
            $grid->disableCreateButton();


            /*
            $grid->id('ID')->sortable();
            $grid->order_num('订单编号');
            $grid->userid('用户')->display(function($value){
                $userinfo = DB::table('wxuser') -> where([
                    'id' => $value
                ]) -> first();
                $url = admin_base_path('users').'/'.$value.'/'.'edit';
                return '<a href="'.$url.'">'.str_limit($userinfo -> nickname,12,'...').'</a>';
            });

            $grid -> package_num('包裹数量');

            $grid->status('账单状态') -> display(function($value){
                $order_config = config('admin.order_status');
                return $order_config[$value];
            });
            $grid->created_at('下单时间')->display(function($value){
                return date('Y-m-d H:i',$value);
            });
            $grid->actions(function ($actions) {
                // 当前行的数据数组
                $temp = $actions->row;
                dd($temp);
                $actions->disableDelete();
            });
            $grid->disableCreateButton();
            $grid->exporter( new ExcelExpoter());

            $grid->tools(function ($tools) {

                $tools->append(new Trashed());

            });
            */

        });
    }


    //申报
    public function declareAll(){

        if(isset($_POST['type']) && $_POST['type'] == 'package'){
            $packages = DB::table('packages')
                -> leftJoin('packages_goods_paratemer','packages.goods_id','packages_goods_paratemer.id')
                -> select([
                    //'packages.id',
                    'packages.package_num',
                    'packages_goods_paratemer.*'
                ])
                -> whereIn('packages.id',$_POST['ids'])
                -> get();
        }else{
            $packages = DB::table('packages')
                -> leftJoin('packages_goods_paratemer','packages.goods_id','packages_goods_paratemer.id')
                -> select([
                    //'packages.id',
                    'packages.package_num',
                    'packages_goods_paratemer.*'
                ])
                -> whereIn('packages.order_id',$_POST['ids'])
                -> get();
        }




        foreach($packages as $k => $vo){
            if(!$vo -> s_content2){
                $packages[$k] -> s_pieces2 = 0;
                $packages[$k] -> s_price2 = 0;
            }
            if(!$vo -> s_content3){
                $packages[$k] -> s_pieces3 = 0;
                $packages[$k] -> s_price3 = 0;
            }
            //通过税号 拿税率
            $temp = DB::table('goods_tax') -> where([
                'code' => $vo -> Tax_code1
            ]) -> first();
            if($temp){
                $packages[$k] -> tax1 = $temp -> tax;
            }else{
                $packages[$k] -> tax1 = 0;
            }

            $temp = DB::table('goods_tax') -> where([
                'code' => $vo -> Tax_code2
            ]) -> first();
            if($temp){
                $packages[$k] -> tax2 = $temp -> tax;
            }else{
                $packages[$k] -> tax2 = 0;
            }

            $temp = DB::table('goods_tax') -> where([
                'code' => $vo -> Tax_code3
            ]) -> first();
            if($temp){
                $packages[$k] -> tax3 = $temp -> tax;
            }else{
                $packages[$k] -> tax3 = 0;
            }
        }



        return view('admin.editDeclarePage') -> with([
            'packages' => $packages
        ]);
    }

    //将订单、包裹 pdf 放入打印队列
    public function makePrintQueue(){
        if($_POST['type'] == 'order' ){
            $ids = isset($_POST['ids'])?$_POST['ids']:'';
            if(!$ids){
                return [
                    'code' => 'error',
                    'msg' => '没有选择任何订单'
                ];
            }
        }else{
            $check_arr = isset($_POST['check_arr'])?$_POST['check_arr']:'';
            if(!$check_arr){
                return [
                    'code' => 'error',
                    'msg' => '没有选择任何包裹'
                ];
            }
        }

        if(isset($ids)){
            //把选中订单的pdf 加到香港队列中
            foreach($ids as $vo){
                $package_info = Package::where([
                    'order_id' => $vo,
                    'flag' => 0,
                ]) -> where('pdf','<>','') -> get();
                if(count($package_info)){
                    foreach($package_info as $value){
                        PrintSequeue::addQueue(3,$value -> wuliu_num,url('temp_pdf').'/'.$value -> pdf,'008');
                    }

                    return [
                        'code' => 'success',
                        'msg' => '已加入香港打印机打印队列准备打印'
                    ];

                }else{
                    return [
                        'code' => 'error',
                        'msg' => '暂时还未生成pdf'
                    ];
                }

            }
        }

        if(isset($check_arr)){
            //选中包裹
            $pdf_arr = [];
            foreach($check_arr as $vo){
                $packages = DB::table('packages') -> where([
                    'id' => $vo,
                    'flag' => 0
                ])-> first();
                if(!$packages -> pdf){
                    continue;
                }else{
                    $pdf_arr[$packages -> wuliu_num] = url('temp_pdf').'/'.$packages -> pdf;
                }

            }

            if(!count($pdf_arr)){
                return [
                    'code' => 'error',
                    'msg' => '暂时还未生成pdf'
                ];
            }else{
                foreach($pdf_arr as $k => $vo){
                    PrintSequeue::addQueue(3,$k,$vo,'008');

                }

                return [
                    'code' => 'success',
                    'msg' => '已加入香港打印机打印队列准备打印'
                ];


            }
        }






    }


    //导出订单
    //根据type 区分哪套模板 1通用 2NN100 10MX01
    public function importFile($type){
        //查出订单
        //属于的区域
        $from_area = Admin::user()->from_area;
        $username = Admin::user()->username;
        //只导出待发货的订单
        if(!strstr($username,'admin')){
            //非admin 只可以导出他自己区域的
            //判断是否合作伙伴
            if(in_array($username,config('admin.partner_number'))){
                //他自己新增的区域
                $area_names = DB::table('area_name') -> where([
                    'admin_user_name' => $username
                ]) -> get();
                if(count($area_names)){
                    $area_ids = [];
                    foreach($area_names as $vo){
                        $area_ids[] = $vo -> id;
                    }
                    $orders = DB::table('order') -> whereIn('status',[1,2]) -> whereIn('from_area',$area_ids) -> where('flag',0) -> get();

                }else{
                    $orders = DB::table('order') -> whereIn('status',[1,2]) -> where('from_area',$from_area) -> where('flag',0) -> get();
                }
            }else{
                $orders = DB::table('order') -> whereIn('status',[1,2]) -> where('from_area',$from_area) -> where('flag',0) -> get();
            }




        }else{
            //admin 可以导出所有的
            $orders = DB::table('order') -> whereIn('status',[1,2]) -> where('flag',0)-> get();
        }
        $model_order = new Order();

        //取导出表头
        $cellData =  $model_order -> getTableTopTr($type);
        $cellData2 =  $model_order -> getTableTopTr(3);



        $key = 0;
        $names_arr = [];
        foreach($orders as  $vo){
            //得到每个订单的id

            //通过订单查包裹
            $packages = DB::table('packages') -> where([
                'order_id' => $vo->id,
                'flag' => 0
            ]) -> where('wuliu_num','=','') ->join('goods_paratemer', 'packages.goods_id', '=', 'goods_paratemer.id')-> get();


            foreach($packages as $k => $vol){
                $temp = $model_order -> exportPacketsTemp($vol,$key,$type);
                $key ++ ;
                $cellData[] = $temp;
            }


            foreach($packages as $k2 => $vol2){
                $temp2 = $model_order -> exportPacketsTemp($vol2,$key,3);
                //把名字合并
                $temp_name_arr = [];
                foreach($temp2 as $key_temp => $value_temp){
                    $temp_name_arr[] = $value_temp['0'];
                }
                $names_arr[] = implode(',',$temp_name_arr);
                //dd($temp_name_arr);

                foreach($temp2 as $value){
                    /*
                    [
                        (string)$vol -> s_content1.'',
                        (string)$vol -> Tax_code1.'',
                        (string)$vol -> s_price1.'',
                        (string)$vol -> s_pieces1.'',
                        (string)$vol -> s_weight1.''
                    ],
                    */
                    $cellData2[] = [
                        (string)$vol2 -> package_num,
                        (string)'',
                        (string)$value[0],
                        (string)$value[0],
                        (string)'',
                        (string)'',
                        (string)$value[3],
                        (string)'',
                        (string)$value[2],
                        (string)'CNY',
                        (string)$value[4],
                        (string)$value[1],
                    ];
                }

                //dd($temp2);
            }





        }




        if($type == 2){



            //如果是 NN00 导出 则需要把最后的货物名称拼上
            foreach($cellData as $cellkey => $cellval){
                if($cellkey == 0){
                    continue;
                }
                $cellData[$cellkey][] = $names_arr[$cellkey - 1];
                //如果是NN100 导出  每个数组的 索引14 添加货物名称
                $cellData[$cellkey][14] = $names_arr[$cellkey - 1];

            }





        }


        //dd($cellData);






        //dump($cellData);
        //dump($cellData2);exit;


        //Log::info('cellData',$cellData);
        Excel::create(date('Y-m-d-H-i').'导出订单',function($excel) use ($cellData,$cellData2){
            $excel->sheet('order', function($sheet) use ($cellData){
                $sheet->rows($cellData);
                for ($i = 1; $i <= count($cellData[0]) + 1; $i++) {
                    $sheet->setWidth($this->cellLetter[$i - 1], 30);
                }
            });
            $excel->sheet('orderitem', function($sheet) use ($cellData2){
                $sheet->rows($cellData2);
                for ($i = 1; $i <= count($cellData2[0]) + 1; $i++) {
                    $sheet->setWidth($this->cellLetter[$i - 1], 30);
                }
            });
        })->export('xlsx');
    }


    //调用api
    public function exportApi(){
        header("Content-type:text/html;charset=utf-8");
        $ids = isset($_POST['ids'])?$_POST['ids']:'';
        $route = $_POST['action'];


        if(!$ids){
            echo 'error';exit;
        }



        //api返回结果
        $apiRes = [];
        Log::info('下单请求111:');
        foreach($ids as $vo){
            //订单
            //判断 只有待发货 或者部分发货的订单才可以
            $order_info = DB::table('order') -> where([
                'id' => $vo,
                'flag' => 0
            ]) -> whereIn('status',[1,2]) -> first();

            if(!$order_info){
                Log::info('下单订单错误:');
                continue;
            }
            Log::info('下单请求:');
            Log::info('order_id:'.$order_info -> id);

            $packages = DB::table('packages')
                ->leftJoin('packages_goods_paratemer', 'packages.goods_id', '=', 'packages_goods_paratemer.id')
                ->select('packages.*', 'packages_goods_paratemer.*','packages.id as id')
                -> where([
                    'packages.order_id' => $vo,
                    'packages.flag' => 0
                ])
                -> get();



            foreach($packages as $vol){
                Log::info('下单请求:');
                Log::info('order_id:'.$order_info -> id);
                if($vol -> wuliu_num){
                    continue;
                }

                $model_order = new Order();
                $apiRes[] = $model_order -> sendPackage($vol,$order_info,$route);


            }

        }

        echo json_encode($apiRes);

    }

    //以订单的角度生成pdf
    public function pdfApi(){
        $temp_str = '';
        $check_arr = isset($_POST['check_arr'])?$_POST['check_arr']:'';
        $ids = isset($_POST['ids'])?$_POST['ids']:'';

        //生成PDF需要判断是用香港的接口 还是 澳门的接口。所以，要判断下routeid
        /*
        $route_pdf = [
            //1是香港
            '4' => 1,
            '5' => 1,


            //2是澳门
            '6' => 2,
            '7' => 2,


            //3是

        ];
        */
        $pdf_type = '';
        $route_id = '';

        //直接打印邮局pdf时候  就 直接把pdf 文件拿出来
        $pdf_files = [];


        //订单的角度生成PDF
        if($ids){
            foreach($ids as $vo){
                $packages = DB::table('packages') -> where([
                    'order_id' => $vo,
                    'flag' => 0
                ])-> get();


                foreach($packages as $vol){
                    if(!$vol -> route_id){
                        continue;
                    }

                    //判断这些包裹 是不是同一个路线
                    if($route_id && $route_id != $vol -> route_id){
                        echo 'pdftype_error';exit;
                    }
                    $route_id = $vol -> route_id;



                    //判断下 这些包裹 是否是同一个pdftype里的
                    /*
                    $temp_pdftype = $route_pdf[$vol -> route_id];
                    if($pdf_type && $pdf_type != $temp_pdftype ){
                        //不是同一个pdftype则报错
                        echo 'pdftype_error';exit;
                    }
                    $pdf_type = $temp_pdftype;
                    */


                    $expressNo = $vol -> wuliu_num;
                    if(!$expressNo){
                        continue;
                    }
                    $temp_str .= $expressNo.',';
                    $pdf_files[] = $vol -> pdf;

                }
            }
        }

        //包裹的角度生成Pdf
        if($check_arr){

            foreach($check_arr as $vo){
                $packages = DB::table('packages') -> where([
                    'id' => $vo,
                    'flag' => 0
                ])-> first();
                if(!$packages -> route_id){
                    continue;
                }


                //判断这些包裹 是不是同一个路线
                if($route_id && $route_id != $packages -> route_id){
                    echo 'pdftype_error';exit;
                }
                $route_id = $packages -> route_id;

                //判断下 这些包裹 是否是同一个pdftype里的
                /*
                $temp_pdftype = $route_pdf[$packages -> route_id];
                if($pdf_type && $pdf_type != $temp_pdftype ){
                    //不是同一个pdftype则报错
                    echo 'pdftype_error';exit;
                }
                $pdf_type = $temp_pdftype;
                */


                $expressNo = $packages -> wuliu_num;
                if(!$expressNo){
                    continue;
                }
                $temp_str .= $expressNo.',';
                $pdf_files[] = $packages -> pdf;

            }
        }






        if(!$temp_str){
            echo 'error';exit;
        }

        $model_order = new Order();

        //如果是route 8 请求 则 直接拿表中的pdf 文件
        if(in_array($route_id,[8])){

            //处理pdf_files
            foreach($pdf_files as $k => $vo){
                if(!$vo){
                    unset($pdf_files[$k]);
                }
                $pdf_files[$k] = url('temp_pdf').'/'.$vo;
            }

            $post_data = $model_order -> makeYoujuPdf($pdf_files);
            if($post_data && $post_data != 'error'){
                echo $post_data;exit;
            }else{
                echo 'error';exit;
            }
        }





        if($temp_str){
            //排除 包裹里 有pdf文件的
            $expressNo = trim($temp_str,',');
            //物流单号数组
            $expressNo_arr = explode(',',$expressNo);

            //处理pdf_files
            foreach($pdf_files as $k => $vo){
                if(!$vo){
                    unset($pdf_files[$k]);
                    continue;
                }
                $pdf_files[$k] = url('temp_pdf').'/'.$vo;
                unset($expressNo_arr[$k]);
            }

            /*
            foreach($expressNo_arr as $k => $vo){
                if($pdf_files[$k]){
                    unset($expressNo_arr[$k]);
                }
                //$pdf_files[$k] = url('temp_pdf').'/'.$vo;
            }
            */

            if(count($expressNo_arr)){
                //字符 传到接口中
                $temp_str = implode(',',$expressNo_arr);
                $post_data = $model_order -> makePdf($temp_str,$route_id);
                //var_dump($post_data);
                //echo "<br/>";
                //返回融通 返回的json
            }else{
                $post_data = '';
            }


            $java_merge_res = $model_order -> javaMergePdf($route_id,$post_data,$pdf_files);


            if($java_merge_res && $java_merge_res != 'error'){
                echo $java_merge_res;
            }else{
                echo 'error';
            }
        }else{
            echo 'error';
        }



    }









    public  function post($url, $post_data = '',$headers = []){
        header("Content-type:text/html;charset=utf-8");
        $ch = curl_init();
        curl_setopt ($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS,json_encode($post_data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            return  curl_error($ch);
        }
        curl_close($ch);
        return $result;
    }



    public function getData($url,$headers = []){
        header("Content-type:text/html;charset=utf-8");
        $ch = curl_init();
        curl_setopt ($ch, CURLOPT_URL, $url);
        //curl_setopt ($ch, CURLOPT_POST, 1);

        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $file_contents = curl_exec($ch);
        if (curl_errno($ch)) {
            return  curl_error($ch);
        }
        curl_close($ch);
        return $file_contents;
    }





    public function edit($id)
    {
        return Admin::content(function (Content $content) use ($id) {

            $content->header('订单管理');
            $content->description('编辑');

            $content->body($this->_form($id)->edit($id));
        });
    }


    public function order($id){
        return Admin::content(function (Content $content) use ($id) {

            $content->header('用户管理');
            $content->description('下单');

            $content->body($this->form($id));
        });
    }


    protected function form($id = null)
    {
        return Admin::form(Order::class, function (Form $form) use($id){
            $form -> setAction(admin_base_path('addOrderRes'));
            $userinfo = DB::table('wxuser') -> where([
                'id' => $id
            ]) -> first();

            $form->display('nickname','昵称') -> with(function($value)use($userinfo){
                return $userinfo -> nickname;
            });

            $admin_user = Admin::user() -> username;
            if(strstr($admin_user,'admin')){
                $area_names = DB::table('area_name') -> get();
                foreach($area_names as $vo){
                    $options[$vo -> id] = $vo -> area_name;
                }

                $form -> select('from_area_id','区域') -> options($options) -> rules('required');
            }




            $form->tools(function (Form\Tools $tools) {
// 添加一个按钮, 参数可以是字符串, 或者实现了Renderable或Htmlable接口的对象实例
                $tools->add('<a class="btn btn-sm btn-danger" id="addTr"><i class="fa fa-trash"></i>&nbsp;&nbsp;增加</a>');
                // 去掉跳转列表按钮
                $tools->disableListButton();
            });
            $userid = $id;
            $table = view('layouts.admin_table',compact('userid')) -> render();
            $form -> html($table)->setWidth(12);

            $form->saved(function () {
                admin_toastr(trans('admin.update_succeeded'));

                return redirect(admin_base_path('auth/setting'));
            });







        });
    }

    //通过userid 获取报价
    public function getPriceByUserIdWeight(){
        $userid = $_POST['userid'];
        $weight = $_POST['weight'];

        $model = new Order();
        $price = $model -> getPriceTempByWeightUserid($userid,$weight);
        echo $price;
    }


    //订单详情
    public function _form($id){
        return Admin::form(Order::class, function (Form $form) use($id) {
            $order_info = Order::find($id);
            //根据订单Id
            $packages = DB::table('packages') -> where([
                'order_id' => $id,
                'flag'=> 0
            ]) -> get();
            $userinfo = DB::table('wxuser') -> where([
                'id' => $order_info -> userid
            ]) -> first();

            //会员等级
            $userconfig = config('admin.user_class');
            $userinfo -> class_name = $userconfig[$userinfo ->class];

            //发展人
            if($userinfo -> from_userid){
                $from_user = DB::table('wxuser') -> where([
                    'id' => $userinfo -> from_userid
                ]) -> first();
                $from_user -> class_name = $userconfig[$from_user ->class];
                $userinfo -> from_userinfo = $from_user;
            }else{
                $userinfo -> from_userinfo = null;
            }


            $admin_user_name = Admin::user() -> username;


            $compact = [
                'order_info' => $order_info,
                'packages' => $packages,
                'userinfo' => $userinfo,
                'admin_user_name' => $admin_user_name
            ];

            /*
            $headers = ['包裹编号', '包裹重量',  '总价','地址','联系人','联系方式'];
            foreach($packages as $k => $vo){
                $rows[$k] = [
                    $vo -> package_num,$vo -> weight,$vo->price,$vo->address,$vo->name,$vo->tel
                ];
            }


            $table = new Table($headers, $rows);
            $form  -> html($table -> render());
            */

            //表编辑
            $view_table = view('layouts.admin_table_order',compact('packages')) -> render();
            $form -> html($view_table)->setWidth(12);


            $view = view('layouts.admin_order_edit',compact('compact')) -> render();
            $form -> html($view);


            /*
            //dd($packages);
            foreach($packages as $k => $vo){
                //单价
                $packages[$k] -> price_num = floatval($vo -> price);
            }






            $form -> row(function($row)use($order_info){
                /*
                $rows = [
                    '订单编号:'   => $order_info -> order_num,
                ];

                $table = new Table('', $rows);
                $row -> width(4) -> html($table -> render());

                $order_config = config('admin.order_status');
                $rows = [
                    '订单状态:'   => $order_config[$order_info -> status],
                ];

                $table = new Table('', $rows);
                $table -> setStyle([
                    '.table' => 'width:20px'
                ]);
                $row -> width(4) -> html($table -> render());
                */
            /*
                $row -> width(3) -> display('order_num','订单编号');

                $row -> width(3) -> display('status','订单状态') ->with(function($value){
                    $order_config = config('admin.order_status');
                    return '<a style="color:red;">'.$order_config[$value].'</a>';
                });
                $row -> width(3) -> display('users.nickname','会员名称');
                /*
                $row -> width(4) -> display('users.nickname','会员名称')->with(function($value){
                    $url = admin_base_path('users').'/'.$value.'/'.'edit';
                    return '<a href="'.$url.'">'.str_limit($value,12,'...').'</a>';
                });
                */
            /*
                $row -> width(3) -> display('users.class','会员等级') -> with(function($value){
                    $user_class = config('admin.user_class');
                    return $user_class[$value];
                });

            });


            $form -> row(function($row)use($order_info){

                $row -> width(3) -> display('price','总金额');
                $row -> width(3) -> display('minus_price','优惠金额');


            });




            $form -> row(function($row) use($packages){
                $headers = ['包裹编号', '包裹重量',  '总价'];
                foreach($packages as $k => $vo){
                    $rows[$k] = [
                        $vo -> package_num,$vo -> weight,$vo->price_num
                    ];
                }


                $table = new Table($headers, $rows);

                /*
                $headers = ['Keys', 'Values'];
                $rows = [
                    'name'   => 'Joe',
                    'age'    => 25,
                    'gender' => 'Male',
                    'birth'  => '1989-12-05',
                ];

                $table = new Table($headers, $rows);
                */
            /*

                $row -> html($table->render())->setWidth(12);
                //echo $table->render();
                //$table = view('layouts.admin_table_order',compact('packages')) -> render();
                //$row -> html($table)->setWidth(12);
            });
            */

            //会员信息
            /*
            $form -> row(function($row){
                $row->divide();
                $row -> width(4) -> display('users.nickname','会员名称');
                /*
                $row -> width(4) -> display('users.nickname','会员名称')->with(function($value){
                    $url = admin_base_path('users').'/'.$value.'/'.'edit';
                    return '<a href="'.$url.'">'.str_limit($value,12,'...').'</a>';
                });
                */
            /*
                $row -> width(4) -> display('users.class','会员等级') -> with(function($value){
                    $user_class = config('admin.user_class');
                    return $user_class[$value];
                });
            });
        */



            /*
            $form->display('nickname','昵称') -> with(function($value)use($userinfo){
                return $userinfo -> nickname;
            });
            */

            $form->tools(function (Form\Tools $tools) {
// 添加一个按钮, 参数可以是字符串, 或者实现了Renderable或Htmlable接口的对象实例
                //$tools->add('<a class="btn btn-sm btn-danger" id="addTr"><i class="fa fa-trash"></i>&nbsp;&nbsp;增加</a>');
                // 去掉跳转列表按钮
                $tools->disableListButton();
            });
            //$form->disableSubmit();
            $form->disableReset();
            $form->setAction(admin_base_path('orderEditRes')) ;



        });
    }

    //订单编辑
    public function orderEditRes(){
        $names = $_REQUEST['names'];
        $tels = $_REQUEST['tels'];
        $address = $_REQUEST['address'];
        $provinces = $_REQUEST['provinces'];
        $citys = $_REQUEST['citys'];
        $order_id = $_REQUEST['order_id'];
        $price = floatval($_REQUEST['price']);
        $price_pre = floatval($_REQUEST['price_pre']);

        //$remarks = $_REQUEST['remarks'];
        //订单上的备注
        $remark = $_REQUEST['remark'];

        $packages  = DB::table('packages') -> where([
            'order_id' => $order_id,
            'flag' => 0
        ]) -> get();

        $order_info = DB::table('order')
            -> where([
                'id' => $order_id
            ]) -> first();
        /*
        if($order_info -> mp_package_id){
            //修改小程序订单 地址
            DB::table('mp_temp_package_number')
                -> where([
                    'province' => trim($provinces[0]),
                    'city' => trim($citys[0]),
                    'address' => trim($address[0])
                ]);
        }
        */

        if(count($names) == count($packages) && count($names) == count($tels) && count($names) == count($address) && count($names) == count($provinces) && count($names) == count($citys) ){

            foreach($packages as $k => $vo){
                DB::table('packages') -> where([
                    'id' => $vo -> id
                ]) -> update([
                   'name' => $names[$k],
                    'tel' => $tels[$k],
                    'province' => $provinces[$k],
                    'city' => $citys[$k],
                    'address' => $address[$k],
                    //'remark' => $remarks[$k]
                ]);
            }
            admin_toastr('保存成功');


            //保存订单的备注
            DB::table('order') -> where([
                'id' => $order_id
            ]) -> update([
                'remark' => $remark,
                'price' => $price
            ]);

            //如果修改了价格 则重新计算每个包裹的价格
            if($price != $price_pre){
                //更改价格比例
                $temp  = $price/$price_pre;
                foreach($packages as $k => $vo){
                    DB::table('packages') -> where([
                        'id' => $vo -> id
                    ]) -> update([
                        'price' => round($vo -> price * $temp,2)
                    ]);
                }
            }



            return redirect(admin_base_path('order'));


        }else{
            admin_toastr('数据错误');

            return redirect(admin_base_path('order/'.$order_id.'/edit'));
        }
        //互相判断下
        /*
        if(count($names) != count(array_unique($names))){
            admin_toastr('名字重复');

            return redirect(admin_base_path('users'));
        }

        if(count($tels) != count(array_unique($tels))){
            echo 'repeat_address';exit;
        }

        if(count($address) != count(array_unique($address))){
            echo 'repeat_address';exit;
        }
        */

        //验证完毕=======>







    }

    //下单成功
    public function addOrderRes(){

        if(isset($_POST['from_area_id']) && $_POST['from_area_id']){
            $from_area = $_POST['from_area_id'];
        }else{
            //属于的区域
            $from_area = Admin::user()->from_area;
        }

        $model = new Order();
        $model -> underhandOrder([
            'from_area' => $from_area,
            'userid' => $_POST['userid'],
            'weights' => $_POST['weight'],
            'remark' => $_POST['remark'],
        ]);

        admin_toastr('下单成功');

        return redirect(admin_base_path('users'));



    }









    //导入物流单号
    public function exportFile(){

        return Admin::content(function (Content $content) {

            $content->header('订单管理');
            $content->description('上传');

            $content->body($this->exportFileForm());
        });
    }
    public function exportFile2(){

        return Admin::content(function (Content $content) {

            $content->header('订单管理');
            $content->description('上传');

            $content->body($this->exportFileForm2());
        });
    }

    //导入过机重量
    public function importPassWeight(){
        return Admin::content(function (Content $content) {

            $content->header('导入过机重量');
            $content->description('上传');

            $content->body($this->importPassWeightForm());
        });
    }

    //导入过机重量
    protected function importPassWeightForm(){

        return Admin::form(Order::class, function (Form $form){
            $form->file('file_column','过机重量列表');

            $form->tools(function (Form\Tools $tools) {
                // 去掉跳转列表按钮
                $tools->disableListButton();
                $url = '"'.url('download_passweight').'"';

                // 添加一个按钮, 参数可以是字符串, 或者实现了Renderable或Htmlable接口的对象实例
                $tools->add('<a class="btn btn-sm btn-danger" onclick=\'window.open('.$url.')\'><i class="fa fa-arrow-down"></i>&nbsp;&nbsp;下载模版</a>');
            });

            //$route_config = config('admin.route_setting');
            //$form->select('route','路线选择')->options($route_config) -> rules('required');


            $form -> setAction(admin_base_path('importPassWeightRes'));


        });
    }

    //导入过机重量 处理
    public function importPassWeightRes(){
        $filePath = $_FILES['file_column']['tmp_name'];
        Excel::load($filePath, function($reader) use( &$res ) {
            $reader = $reader->getSheet(0);
            $res = $reader->toArray();
        });

        $wuliu_num = [];
        $update_res = [];
        foreach($res as $k => $vo){
            if($k == 0){
                continue;
            }
            //如果存在第一列 和 第二列
            $model = new Order();
            if($vo[1] && $vo[0]){
                //根据0 运单号 1 过机重量 补扣/返还
                $wuliu_num[] = trim($vo[0]);
                $update_res[] = $model -> changePriceByPassWeight(trim($vo[0]),trim($vo[1]));
            }
        }

        $rows = [];
        $headers = ['运单单号', '处理结果'];
        foreach($wuliu_num as $k => $vo){
            $rows[] = [
                $vo,
                $update_res[$k]
            ];
        }


        $table = new Table($headers, $rows);

        echo $table->render();exit;


        /*
        $cellData = [
            '运单单号',
            '处理结果'
        ];
        foreach($wuliu_num as $k => $vo){
            $cellData[] = [
                $vo,
                $update_res[$k]
            ];
        }


        Excel::create(date('Y-m-d-H-i').'过机重量处理结果',function($excel) use ($cellData){
            $excel->sheet('过机重量处理结果', function($sheet) use ($cellData){
                $sheet->rows($cellData);
                for ($i = 1; $i <= count($cellData[0]) + 1; $i++) {
                    $sheet->setWidth($this->cellLetter[$i - 1], 30);
                }
            });
        })->export('xlsx');

        */
        //admin_toastr('导入成功');

        /*
        return redirect(admin_base_path('importPassWeightResPage'))->with([
            'wuliu_num' => $wuliu_num,
            'update_res' => $update_res
        ]);
        */
    }


    public function importPassWeightResPage(){
        $cellData = [
            '运单单号',
            '处理结果'
        ];


        Excel::create(date('Y-m-d-H-i').'过机重量处理结果',function($excel) use ($cellData){
            $excel->sheet('过机重量处理结果', function($sheet) use ($cellData){
                $sheet->rows($cellData);
                for ($i = 1; $i <= count($cellData[0]) + 1; $i++) {
                    $sheet->setWidth($this->cellLetter[$i - 1], 30);
                }
            });
        })->export('xlsx');
    }

    protected function exportFileForm(){

        return Admin::form(Order::class, function (Form $form){
            $from_area = Admin::user()->from_area;

            //通过区域 找到所属的超级管理员
            $from_area_admin_info = DB::table('area_name') -> where([
                'id' => $from_area
            ]) -> first();
            $from_area_admin_name = $from_area_admin_info -> admin_user_name;


            $form->file('file_column','物流单号');


            $form->tools(function (Form\Tools $tools) {
                // 去掉跳转列表按钮
                $tools->disableListButton();
                $url = '"'.url('download').'"';

                // 添加一个按钮, 参数可以是字符串, 或者实现了Renderable或Htmlable接口的对象实例
                $tools->add('<a class="btn btn-sm btn-danger" onclick=\'window.open('.$url.')\'><i class="fa fa-arrow-down"></i>&nbsp;&nbsp;下载模版</a>');
            });

            $route_config = config('admin.route_setting');
            if($from_area_admin_name == 'xx'){
                $route_config = [
                    5 => 'HK(XS001)',
                    8 => 'B线路',
                ];
            }
            $form->select('route','路线选择')->options($route_config) -> rules('required');


            $form -> setAction(admin_base_path('exportFileRes'));


        });
    }
    protected function exportFileForm2(){

        return Admin::form(Order::class, function (Form $form){
            $from_area = Admin::user()->from_area;

            //通过区域 找到所属的超级管理员
            $from_area_admin_info = DB::table('area_name') -> where([
                'id' => $from_area
            ]) -> first();
            $from_area_admin_name = $from_area_admin_info -> admin_user_name;


            $form->file('file_column','物流单号sssss');

            $form->tools(function (Form\Tools $tools) {
                // 去掉跳转列表按钮
                $tools->disableListButton();
                $url = '"'.url('download').'"';

                // 添加一个按钮, 参数可以是字符串, 或者实现了Renderable或Htmlable接口的对象实例
                $tools->add('<a class="btn btn-sm btn-danger" onclick=\'window.open('.$url.')\'><i class="fa fa-arrow-down"></i>&nbsp;&nbsp;下载模版</a>');
            });

            $route_config = config('admin.route_setting');
            if($from_area_admin_name == 'xx'){
                $route_config = [
                    5 => 'HK(XS001)',
                    8 => 'B线路',
                ];
            }
            $form->select('route','路线选择')->options($route_config) -> rules('required');


            $form -> setAction(admin_base_path('exportFileRes2'));


        });
    }


    //发货处理
    public function exportFileRes(){
        $route = $_POST['route'];
        $this -> import($_FILES['file_column']['tmp_name'],$route);

        admin_toastr('导入成功');

        return redirect(admin_base_path('exportFile'));
    }
    public function exportFileRes2(){
        $route = $_POST['route'];
        $this -> import2($_FILES['file_column']['tmp_name'],$route);

        admin_toastr('导入成功');

        return redirect(admin_base_path('exportFile'));
    }
    public function import2($filePath,$route){
        $from_area = Admin::user()->from_area;
        $username = Admin::user()->username;
        //dd($username);
        //dd($from_area);
        //$filePath = 'storage/exports/'.iconv('UTF-8', 'GBK', '学生成绩').'.xls';
        Excel::load($filePath, function($reader) use( &$res ) {
            $reader = $reader->getSheet(0);
            $res = $reader->toArray();
        });


        /*
        for($i=0;$i<1000;$i++){
            //计算个数随机数

            $count_tmp = rand(3,6);
            for($j = 0;$j<$count_tmp;$j++){
                $number_tmp = rand(1,148);
                //dd($res[$number_tmp]);
                $cellData[] = [
                    (string)$res[$number_tmp][0],
                    (string)$res[$number_tmp][1],
                    (string)$res[$number_tmp][2],
                    (string)$res[$number_tmp][3],
                ];
            }
            $cellData[] = [
                '',
                '',
                '',
                '',
            ];

        }

        echo json_encode($cellData);exit;
        */











        /*
        $number = 0;
        foreach($res as $k => $vo){

            if($k == 0){
                continue;
            }
            $number++;
            //$vo[0] 类别
            //$vo[1] 系列
            //$vo[2] 品牌

            //0商品类别 1系列 2品牌
            $class_id = DB::table('erp_product_class')
                -> where([
                    'type_id' => 0,
                    'name' => trim($vo[0])
                ]) -> first();

            if(!$class_id){
                $class_id = DB::table('erp_product_class')
                    -> insertGetId([
                        'name' => trim($vo[0]),
                        'type_id' => 0,
                        'fid' => 0,
                        'created_at' => time(),
                        'updated_at' => time(),
                        'flag' => 0
                    ]);
            }else{
                $class_id = $class_id -> id;
            }

            //品牌
            $brand_id = DB::table('erp_product_class')
                -> where([
                    'type_id' => 2,
                    'name' => trim($vo[2]),
                    'fid' => $class_id
                ]) -> first();
            if(!$brand_id){
                $brand_id = DB::table('erp_product_class')
                    -> insertGetId([
                        'name' => trim($vo[2]),
                        'type_id' => 2,
                        'fid' => $class_id,
                        'created_at' => time(),
                        'updated_at' => time(),
                        'flag' => 0
                    ]);
            }else{
                $brand_id = $brand_id -> id;
            }

            //系列
            $series_id = DB::table('erp_product_class')
                -> where([
                    'type_id' => 1,
                    'name' => trim($vo[1]),
                    'fid' => $brand_id
                ]) -> first();
            if(!$series_id){
                $series_id = DB::table('erp_product_class')
                    -> insertGetId([
                        'name' => trim($vo[1]),
                        'type_id' => 1,
                        'fid' => $brand_id,
                        'created_at' => time(),
                        'updated_at' => time(),
                        'flag' => 0
                    ]);
            }






        }

        echo $number;exit;
        */

        /*
        $number = 0;
        foreach($res as $k => $vo){
            $number++;
            if($k == 0){
                continue;
            }

            $product_no = trim($vo[0]);





            //品牌 系列 类别
            $info = DB::table('erp_product_class as type2')
                -> leftJoin('erp_product_class as type1','type2.fid','type1.id')
                -> leftJoin('erp_product_class as type0','type1.fid','type0.id')
                -> where([
                    'type2.name' => trim($vo[1]), //系列
                    'type1.name' => trim($vo[2]), //品牌
                    'type0.name' => trim($vo[3]), //类别
                ]) -> select([
                    'type2.id as series_id',
                    'type1.id as brand_id',
                    'type0.id as class_id'
                ]) -> first();

            if($info){
                DB::table('erp_product_list') -> where([
                    'product_no' => $product_no
                ]) -> update([
                    'class_id' => $info -> class_id,
                    'brand_id' => $info -> brand_id,
                    'series_id' => $info -> series_id
                ]);
            }






        }

        echo $number;exit;
        */






        //dd($res);
        foreach($res as $vo){
            //如果存在第一列 和 第二列
            if($vo[1] && $vo[0]){
                //找下有没有此包裹编号
                $package_info = DB::table('packages')
                    -> where('package_num','like',trim($vo[1]))
                    -> where('flag','=',0)
                    -> first();
                if(empty($package_info)){
                    continue;
                }
                //找下此订单  是不是登陆这个区域的订单
                //update 0723 admin 可以随便发
                if(!strstr($username,'admin')){


                    //如果是合作人，则能看到他自己新增区域下的订单
                    if(in_array($username,config('admin.partner_number'))){
                        //他自己新增的区域
                        $area_names = DB::table('area_name') -> where([
                            'admin_user_name' => $username
                        ]) -> get();
                        if(count($area_names)){
                            $area_ids = [];
                            foreach($area_names as $vo_temp){
                                $area_ids[] = $vo_temp -> id;
                            }
                            $order_info = DB::table('order') -> where([
                                'id' => $package_info -> order_id,
                            ]) -> whereIn('from_area',$area_ids) -> first();
                        }else{
                            $order_info = DB::table('order') -> where([
                                'id' => $package_info -> order_id,
                                'from_area' => $from_area
                            ]) -> first();
                        }
                    }else{
                        $order_info = DB::table('order') -> where([
                            'id' => $package_info -> order_id,
                            'from_area' => $from_area
                        ]) -> first();
                    }




                    if(empty($order_info)){
                        continue;
                    }
                }else{
                    $order_info = DB::table('order') -> where([
                        'id' => $package_info -> order_id
                    ]) -> first();
                    if(empty($order_info)){
                        continue;
                    }
                }

                $model_order =  new Order();
                //计算成本 -- 如果余额充足 则允许发货
                $is_sendOrder = $model_order -> checkPrice($package_info,$order_info,$route);
                if(!$is_sendOrder){
                    //不允许发货
                    continue;
                }
                $model_order -> sendOrder($package_info,$order_info,$route,$vo[0],false);


            }

        }



    }

    public function import($filePath,$route){
        $from_area = Admin::user()->from_area;
        $username = Admin::user()->username;
        //dd($username);
        //dd($from_area);
        //$filePath = 'storage/exports/'.iconv('UTF-8', 'GBK', '学生成绩').'.xls';
        Excel::load($filePath, function($reader) use( &$res ) {
            $reader = $reader->getSheet(0);
            $res = $reader->toArray();
        });


        /*
        for($i=0;$i<1000;$i++){
            //计算个数随机数

            $count_tmp = rand(3,6);
            for($j = 0;$j<$count_tmp;$j++){
                $number_tmp = rand(1,148);
                //dd($res[$number_tmp]);
                $cellData[] = [
                    (string)$res[$number_tmp][0],
                    (string)$res[$number_tmp][1],
                    (string)$res[$number_tmp][2],
                    (string)$res[$number_tmp][3],
                ];
            }
            $cellData[] = [
                '',
                '',
                '',
                '',
            ];

        }

        echo json_encode($cellData);exit;
        */











        /*
        $number = 0;
        foreach($res as $k => $vo){

            if($k == 0){
                continue;
            }
            $number++;
            //$vo[0] 类别
            //$vo[1] 系列
            //$vo[2] 品牌

            //0商品类别 1系列 2品牌
            $class_id = DB::table('erp_product_class')
                -> where([
                    'type_id' => 0,
                    'name' => trim($vo[0])
                ]) -> first();

            if(!$class_id){
                $class_id = DB::table('erp_product_class')
                    -> insertGetId([
                        'name' => trim($vo[0]),
                        'type_id' => 0,
                        'fid' => 0,
                        'created_at' => time(),
                        'updated_at' => time(),
                        'flag' => 0
                    ]);
            }else{
                $class_id = $class_id -> id;
            }

            //品牌
            $brand_id = DB::table('erp_product_class')
                -> where([
                    'type_id' => 2,
                    'name' => trim($vo[2]),
                    'fid' => $class_id
                ]) -> first();
            if(!$brand_id){
                $brand_id = DB::table('erp_product_class')
                    -> insertGetId([
                        'name' => trim($vo[2]),
                        'type_id' => 2,
                        'fid' => $class_id,
                        'created_at' => time(),
                        'updated_at' => time(),
                        'flag' => 0
                    ]);
            }else{
                $brand_id = $brand_id -> id;
            }

            //系列
            $series_id = DB::table('erp_product_class')
                -> where([
                    'type_id' => 1,
                    'name' => trim($vo[1]),
                    'fid' => $brand_id
                ]) -> first();
            if(!$series_id){
                $series_id = DB::table('erp_product_class')
                    -> insertGetId([
                        'name' => trim($vo[1]),
                        'type_id' => 1,
                        'fid' => $brand_id,
                        'created_at' => time(),
                        'updated_at' => time(),
                        'flag' => 0
                    ]);
            }






        }

        echo $number;exit;
        */

        /*
        $number = 0;
        foreach($res as $k => $vo){
            $number++;
            if($k == 0){
                continue;
            }

            $product_no = trim($vo[0]);





            //品牌 系列 类别
            $info = DB::table('erp_product_class as type2')
                -> leftJoin('erp_product_class as type1','type2.fid','type1.id')
                -> leftJoin('erp_product_class as type0','type1.fid','type0.id')
                -> where([
                    'type2.name' => trim($vo[1]), //系列
                    'type1.name' => trim($vo[2]), //品牌
                    'type0.name' => trim($vo[3]), //类别
                ]) -> select([
                    'type2.id as series_id',
                    'type1.id as brand_id',
                    'type0.id as class_id'
                ]) -> first();

            if($info){
                DB::table('erp_product_list') -> where([
                    'product_no' => $product_no
                ]) -> update([
                    'class_id' => $info -> class_id,
                    'brand_id' => $info -> brand_id,
                    'series_id' => $info -> series_id
                ]);
            }






        }

        echo $number;exit;
        */






        //dd($res);
        foreach($res as $vo){
            //如果存在第一列 和 第二列
            if($vo[1] && $vo[0]){
                //找下有没有此包裹编号
                $package_info = DB::table('packages')
                    -> where('package_num','like',trim($vo[1]))
                    -> where('flag','=',0)
                    -> first();
                if(empty($package_info)){
                    continue;
                }
                //找下此订单  是不是登陆这个区域的订单
                //update 0723 admin 可以随便发
                if(!strstr($username,'admin')){


                    //如果是合作人，则能看到他自己新增区域下的订单
                    if(in_array($username,config('admin.partner_number'))){
                        //他自己新增的区域
                        $area_names = DB::table('area_name') -> where([
                            'admin_user_name' => $username
                        ]) -> get();
                        if(count($area_names)){
                            $area_ids = [];
                            foreach($area_names as $vo_temp){
                                $area_ids[] = $vo_temp -> id;
                            }
                            $order_info = DB::table('order') -> where([
                                'id' => $package_info -> order_id,
                            ]) -> whereIn('from_area',$area_ids) -> first();
                        }else{
                            $order_info = DB::table('order') -> where([
                                'id' => $package_info -> order_id,
                                'from_area' => $from_area
                            ]) -> first();
                        }
                    }else{
                        $order_info = DB::table('order') -> where([
                            'id' => $package_info -> order_id,
                            'from_area' => $from_area
                        ]) -> first();
                    }




                    if(empty($order_info)){
                        continue;
                    }
                }else{
                    $order_info = DB::table('order') -> where([
                        'id' => $package_info -> order_id
                    ]) -> first();
                    if(empty($order_info)){
                        continue;
                    }
                }

                $model_order =  new Order();
                //计算成本 -- 如果余额充足 则允许发货
                $is_sendOrder = $model_order -> checkPrice($package_info,$order_info,$route);
                if(!$is_sendOrder){
                    //不允许发货
                    continue;
                }
                $model_order -> sendOrder($package_info,$order_info,$route,$vo[0],false);


            }

        }



    }


    //取消订单
    public function cancelOrder(){
        $order_id = $_POST['id'];

        //如果删除订单  把占用的order_num 返回去
        //先查from_area
        $order_info = DB::table('order') -> where([
            'id' => $order_id
        ]) -> first();



        //如果 订单下 有物流单号 则不允许删除
        $package_info = DB::table('packages') -> where('order_id','=',$order_id) -> where('wuliu_num','<>','') -> where('flag','=',0) -> first();

        if($package_info && ($order_info -> status == 2 || $order_info -> status == 3) ){
             //不允许删除
             echo 'nodel';exit;
        }


        //开始执行删除订单操作
        $model_order = new Order();
        $model_order -> deleteOrder($order_info);





        echo 'success';
    }

    //删除包裹
    public function deletePackage(){
        $package_id = $_POST['package_id'];

        $package_info = DB::table('packages') -> where([
            'id' => $package_id,
            'flag' => 0
        ]) -> first();
        $order_info = DB::table('order') -> where([
            'id' => $package_info -> order_id,
            'flag' => 0
        ]) -> first();

        //如果有物流单号 则不让他删除
        if($package_info -> wuliu_num){
            echo 'notdel';exit;
        }

        if(isset($_POST['type']) && $_POST['type'] == 'packageSearch'){
            //包裹搜索的删除
            if(($order_info -> status == 2 || $order_info -> status == 3) ){
                //部分发货、已发货 不允许删除
                echo 'fa_nodel';exit;
            }
            $model_order = new Order();
            //先删除包裹
            $model_order -> deletePackage($package_info,$order_info);
            //后删除订单
            $model_order -> deleteOrder($order_info);

        }else{
            //如果删除的是最后一个包裹 则让他执行删除订单操作
            $count_package = DB::table('packages') -> where([
                'order_id' => $package_info -> order_id,
                'flag' => 0
            ]) -> count();
            if($count_package == 1){
                echo 'delOrder';exit;
            }

            $model_order = new Order();

            $model_order -> deletePackage($package_info,$order_info);
        }



        echo 'success';
    }

    //编辑包裹重量页面
    public function editPacketPage($id){
        //dd(1);
        //判断下他是否有权限
        $admin_user_name = Admin::user() -> username;
        $from_area = Admin::user() -> from_area;
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
                    //$grid -> model()->whereIn('from_area',$area_ids);
                    //判断这个包裹是否属于这个区域
                    $package_info = DB::table('packages') -> where([
                        'id' => $id,
                        'flag' => 0
                    ]) -> whereIn('from_area',$area_ids) -> first();
                }else{

                    //判断这个包裹是否属于这个区域
                    $package_info = DB::table('packages')  -> where([
                        'id' => $id,
                        'flag' => 0,
                        'from_area' => $from_area
                    ]) -> first();

                }
            }else{

                //判断这个包裹是否属于这个区域
                $package_info = DB::table('packages')  -> where([
                    'id' => $id,
                    'flag' => 0,
                    'from_area' => $from_area
                ]) -> first();

            }
            if(!$package_info){
                exit;
            }
        }


        return Admin::content(function (Content $content) use ($id) {

            $content->header('订单管理');
            $content->description('包裹重量编辑');

            $content->body($this->formPackage($id)->edit($id));
        });
    }


    //编辑包裹地址页面
    public function editPacketAddressPage($id){
        //判断下他是否有权限
        $admin_user_name = Admin::user() -> username;
        $from_area = Admin::user() -> from_area;
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
                    //$grid -> model()->whereIn('from_area',$area_ids);
                    //判断这个包裹是否属于这个区域
                    $package_info = DB::table('packages') -> where([
                        'id' => $id,
                        'flag' => 0
                    ]) -> whereIn('from_area',$area_ids) -> first();
                }else{

                    //判断这个包裹是否属于这个区域
                    $package_info = DB::table('packages')  -> where([
                        'id' => $id,
                        'flag' => 0,
                        'from_area' => $from_area
                    ]) -> first();

                }
            }else{

                //判断这个包裹是否属于这个区域
                $package_info = DB::table('packages')  -> where([
                    'id' => $id,
                    'flag' => 0,
                    'from_area' => $from_area
                ]) -> first();

            }
            if(!$package_info){
                exit;
            }
        }


        return Admin::content(function (Content $content) use ($id) {

            $content->header('订单管理');
            $content->description('包裹地址编辑');

            $content->body($this->formPackageAddress($id)->edit($id));
        });
    }


    public function formPackageAddress($id){
        return Admin::form(Package::class, function (Form $form) use($id) {
            $form -> display('package_num','包裹编号');
            //$form -> display('weight','包裹重量');
            $form -> text('province','省');
            $form -> text('city','市');
            $form -> text('address','地址');
            $form -> text('tel','联系电话');


            //$form -> text('weight_new','重量')->rules('required')->default(Package::find($id)->weight);
            $form -> hidden('id','id');
            //$form -> hidden('weight','weight');

            $form->tools(function (Form\Tools $tools) {
                // 去掉跳转列表按钮
                $tools->disableListButton();
            });

            $form->setAction(admin_base_path('editPacketAddressRes')) ;
        });
    }

    public function formPackage($id){
        return Admin::form(Package::class, function (Form $form) use($id) {
            $form -> text('weight_new','重量')->rules('required')->default(Package::find($id)->weight);
            $form -> hidden('id','id');
            $form -> hidden('weight','weight');

            $form->tools(function (Form\Tools $tools) {
                // 去掉跳转列表按钮
                $tools->disableListButton();
            });

            $form->setAction(admin_base_path('editPacketRes')) ;
        });
    }


    //单个包裹编辑处理结果
    public function editPacketAddressRes(){
        //dd($_POST);
        $package_id = $_POST['id'];

        $package_info = DB::table('packages') -> where([
            'id' => $package_id
        ]) -> first();

        $order_info = DB::table('order') -> where([
            'id' => $package_info -> order_id
        ]) -> first();

        $res = DB::table('packages') -> where([
            'id' => $package_id
        ]) -> update([
            'province' => $_POST['province'],
            'city' => $_POST['city'],
            'address' => $_POST['address'],
            'tel' => $_POST['tel'],
            'updated_at' => time()
        ]);



        if($res){
            admin_toastr('修改成功');

            return redirect(admin_base_path('order'));
        }else{
            admin_toastr('没有任何修改','error');

            return redirect(admin_base_path('order'));
        }

    }



    //编辑包裹处理结果
    public function editPacketRes(){
        $package_id = $_POST['id'];
        $weight = round(floatval($_POST['weight_new']),2);
        $weight_pre = floatval($_POST['weight']);

        if(!$weight || $weight == $weight_pre){
            admin_toastr('没有修改');

            return redirect(admin_base_path('editPacketPage/'.$package_id));
        }
        $package_info = DB::table('packages') -> where([
            'id' => $package_id
        ]) -> first();

        $order_info = DB::table('order') -> where([
            'id' => $package_info -> order_id
        ]) -> first();


        $admin_user_name = Admin::user() -> username;
        $from_area = Admin::user() -> from_area;
        if(!strstr($admin_user_name,'admin')){
            if($from_area != $order_info -> from_area){
                exit;
            }
        }


        $model = new Order();
        //修改包裹重量
        $update_res = $model -> updateWeight($package_info,$order_info,$weight);
        if($update_res['res'] == 'success'){
            //判断下 如果是小程序下的订单  则把小程序订单的重量也改掉
            if($order_info -> mp_package_id){
                DB::table('mp_temp_package_number')
                    -> where([
                        'id' => $order_info -> mp_package_id
                    ]) -> update([
                        'weight' => $weight
                    ]);
            }


            admin_toastr('修改成功');

            return redirect(admin_base_path('order'));
        }else{
            admin_toastr($update_res['msg'],'error');

            return redirect(admin_base_path('order'));
        }

    }


    //支付确认页面
    public function confirmPayPage($id){
        //订单信息
        $orderinfo = DB::table('order') -> where([
            'id' => $id
        ]) -> first();
        $userinfo = DB::table('wxuser') -> where([
            'id' => $orderinfo -> userid
        ]) -> first();
        return view('admin.confirmPayPage')->with([
            'orderinfo' => $orderinfo,
            'userinfo' => $userinfo
        ]);
    }

    public function submitPayOrder(){
        $orderid = $_POST['orderid'];
        //流水号
        $paynumber = $_POST['pay_number'];
        //支付时间
        $paytime = $_POST['paytime'];
        //支付方式 1 微信 2支付宝
        $paytype = $_POST['paytype'];
        if($orderid && $paynumber && $paytime && $paytype){


            //修改账单状态
            //支付结束 判断下 发货状态
            $order_info = DB::table('order') -> where([
                'id' => $orderid
            ]) -> first();
            if($order_info -> status == 5){
                $order_status = 1 ;
            }else{
                $order_status = $order_info -> status;
            }


            DB::table('order') -> where([
                'id' => $orderid
            ]) -> update([
                'pay_time' => strtotime($paytime),
                'pay_type' =>  $paytype,
                'pay_number' => $paynumber,
                'pay_status' => 1,
                'status' => $order_status
            ]);

            if($order_info -> mp_package_id){
                DB::table('mp_temp_package_number')
                    -> where([
                        'id' => $order_info -> mp_package_id
                    ]) -> update([
                        'order_status' => 2
                    ]);
            }







            echo 'success';
        }else{
            echo 'error';
        }
    }




    //导出订单
    public function exportOrder($id){
        $admin_user_name = Admin::user() -> username;
        //查出此订单的所有包裹
        $order_info = DB::table('order') -> where([
            'id' => $id
        ]) -> first();

        //客户
        $username = DB::table('wxuser') -> where([
            'id' => $order_info -> userid
        ]) -> first();

        //查找所属区域id
        $from_area = DB::table('area_name') -> where([
            'id' => $order_info -> from_area
        ]) -> first();

        $packages = DB::table('packages') -> where([
            'order_id' => $id
        ]) -> get();

        $route_config = config('admin.route_setting');


        if($admin_user_name == 'admin'){
            $cellData = [
                ['订单日期','订单编号','包裹编号','运单号','客户','收件人','电话','地址','城市','省份','邮编','重量','区域',	'线路','成本价格',	'区域价格','客户价格']
            ];
        }else{
            $cellData = [
                ['订单日期','订单编号','包裹编号','运单号','客户','收件人','电话','地址','城市','省份','邮编','重量','区域',	'线路',	'区域价格','客户价格']
            ];
        }


        foreach($packages as $k => $vo){
            //路线名称
            if($vo -> route_id){
                $route_name = $route_config[$vo -> route_id];
            }else{
                $route_name = '未知';
            }


            //查看邮编
            $zip_info = DB::table('zips')
                -> where('city','like','%'.mb_substr(trim($vo -> province),0,2,'utf-8').'%')
                -> where('province','like','%'.mb_substr(trim($vo -> city),0,2,'utf-8').'%')
                -> first();

            if(!empty($zip_info)){
                $zip = $zip_info -> zip_code;
            }else{
                $zip = $vo -> zip;
            }

            if($admin_user_name == 'admin') {
                $cellData[] = [
                    (string)date('Y-m-d', $order_info->created_at),
                    (string)$order_info->order_num,
                    (string)$vo->package_num,
                    (string)$vo->wuliu_num,
                    (string)$username->nickname,
                    (string)$vo->name,
                    (string)$vo->tel,
                    (string)$vo->address,
                    (string)$vo->city,
                    (string)$vo->province,
                    (string)$zip,
                    (string)$vo->weight,
                    (string)$from_area->area_name,
                    (string)$route_name,
                    (string)$vo->cost,
                    (string)$vo->area_price,
                    (string)$vo->price,
                ];
            }else{
                $cellData[] = [
                    (string)date('Y-m-d', $order_info->created_at),
                    (string)$order_info->order_num,
                    (string)$vo->package_num,
                    (string)$vo->wuliu_num,
                    (string)$username->nickname,
                    (string)$vo->name,
                    (string)$vo->tel,
                    (string)$vo->address,
                    (string)$vo->city,
                    (string)$vo->province,
                    (string)$zip,
                    (string)$vo->weight,
                    (string)$from_area->area_name,
                    (string)$route_name,

                    (string)$vo->area_price,
                    (string)$vo->price,
                ];
            }
        }

        //dd($cellData);


        Excel::create(date('Y-m-d-H-i').'导出历史订单',function($excel) use ($cellData){
            $excel->sheet('导出历史订单', function($sheet) use ($cellData){
                $sheet->rows($cellData);
                for ($i = 1; $i <= count($cellData[0]) + 1; $i++) {
                    $sheet->setWidth($this->cellLetter[$i - 1], 30);
                }
            });
        })->export('xlsx');
    }


    //订单显示 包裹维度显示
    public function orderPacket(){
        $text = '';
        if(isset($_GET) && isset($_GET['texts'])){
            $text = explode("\r\n",$_GET['texts']);
        }
        $batch_id = '';
        //批次添加
        if(isset($_GET) && isset($_GET['batch_id'])){
            //找到这些批次
            $batch_id = $_GET['batch_id'];
        }

        return Admin::content(function (Content $content) use($text,$batch_id)  {

            $content->header('创建托盘');
            //$content->description('');
            $packages = [];
            //判断用户的区域
            $admin_user_name = Admin::user() -> username;
            $from_area = Admin::user() -> from_area;
            if($text){


                //根据每个数组 查packages
                foreach($text as $vo){
                    if(!trim($vo)){
                        continue;
                    }




                    $package_info = DB::table('packages') -> where(function($query)use($vo){
                        $query -> where('package_num','like','%'.trim($vo).'%')-> orWhere('wuliu_num','like','%'.trim($vo).'%');
                    }) -> where(function($query)use($admin_user_name,$from_area){
                        $query -> where('flag','=',0);
                    }) -> first();

                    if($package_info){
                        if(!strstr($admin_user_name,'admin')){
                            //所属区域
                            //查看这个包裹 所在的区域 是否在这个区域
                            $order_info = DB::table('order') -> where([
                                'id' => $package_info -> order_id
                            ]) -> first();
                            if($order_info -> from_area != $from_area){
                                continue;
                            }
                        }
                        $packages[] = $package_info;

                    }
                }
            }

            $batch_info = [];
            if($batch_id){
                $batch_info = DB::table('batch_packages') -> where('id','=',$batch_id) -> first();


                $package_ids = explode(',',$batch_info -> package_ids);

                foreach($package_ids as $vo){
                    $package_info = DB::table('packages')
                        -> where('id','=',$vo)
                        -> first();

                    if($package_info){
                        $packages[] = $package_info;
                    }
                }

            }






            $temp_ids = [];
            foreach($packages as $k =>  $vo){
                if(in_array($vo -> id,$temp_ids)){
                    unset($packages[$k]);
                    continue;
                }
                $temp_ids[] = $vo -> id;
            }

            //compact('packages','batch_info')
            $view = view('admin.admin_order_packets',[
                'packages' => $packages,
                'batch_info' => $batch_info,
                'admin_username' => $admin_user_name
            ]) -> render();
            $content->body($view);
        });
    }


    public function orderPacketExport($type = 1){
        $package_ids = $_GET['check_arr'];
        $pici_num = $_GET['pici'];
        $is_save = $_GET['is_save'];
        $batch_id = isset($_POST['batch_id'])?$_POST['batch_id']:'';


        if($is_save == 'yes'){
            if($batch_id){
                DB::table('batch_packages') -> where('id','=',$batch_id) -> update([
                    'package_ids' => implode(',',$package_ids),
                    'updated_at' => time(),
                ]);
            }else{
                DB::table('batch_packages') -> insert([
                    'package_ids' => implode(',',$package_ids),
                    'batch_num' => $pici_num,
                    'created_at' => time(),
                    'updated_at' => time(),
                ]);
            }


        }
        if(isset($_POST['type']) && $_POST['type'] == 'save'){
            echo 'save_success';exit;
        }
        $model_order = new Order();
        //取导出表头
        $cellData =  $model_order -> getTableTopTr($type);
        $cellData2 =  $model_order -> getTableTopTr(3);
        $key = 0;

        foreach($package_ids as  $vo){
            //得到每个订单的id

            //通过订单查包裹
            $packages = DB::table('packages') -> where([
                'packages.id' => $vo
            ]) ->join('goods_paratemer', 'packages.goods_id', '=', 'goods_paratemer.id')-> get();


            foreach($packages as $k => $vol) {
                ///////
                $temp = $model_order -> exportPacketsTemp($vol,$key,$type);
                $key ++ ;
                $cellData[] = $temp;
            }

            foreach($packages as $k2 => $vol2){
                $temp2 = $model_order -> exportPacketsTemp($vol2,$key,3);
                foreach($temp2 as $value){
                    /*
                    [
                        (string)$vol -> s_content1.'',
                        (string)$vol -> Tax_code1.'',
                        (string)$vol -> s_price1.'',
                        (string)$vol -> s_pieces1.'',
                        (string)$vol -> s_weight1.''
                    ],
                    */
                    $cellData2[] = [
                        (string)$vol2 -> package_num,
                        (string)'',
                        (string)$value[0],
                        (string)$value[0],
                        (string)'',
                        (string)'',
                        (string)$value[3],
                        (string)'',
                        (string)$value[2],
                        (string)'CNY',
                        (string)$value[4],
                        (string)$value[1],
                    ];
                }

                //dd($temp2);
            }


        }

        //dd($cellData);

        //exit;
        //Log::info('cellData',$cellData);

        Excel::create(date('Y-m-d-H-i').'导出订单',function($excel) use ($cellData,$cellData2){
            $excel->sheet('order', function($sheet) use ($cellData){
                $sheet->rows($cellData);
                for ($i = 1; $i <= count($cellData[0]) + 1; $i++) {
                    $sheet->setWidth($this->cellLetter[$i - 1], 30);
                }
            });
            $excel->sheet('orderitem', function($sheet) use ($cellData2){
                $sheet->rows($cellData2);
                for ($i = 1; $i <= count($cellData2[0]) + 1; $i++) {
                    $sheet->setWidth($this->cellLetter[$i - 1], 30);
                }
            });
        })->export('xlsx');



    }

    //检验批次编号 是否重复
    public function  isBatchRepeat(){
        $isset = DB::table('batch_packages') -> where('batch_num','=',trim($_POST['pici'])) -> first();
        if($isset){
            echo 'repeat';
        }else{
            echo 'norepeat';
        }

    }

    //api 结果 弹出层
    public function apiAlertPage(){
        if(isset($_POST['type']) && $_POST['type']){

            $json = json_decode($_POST['data'],true);


            $headers = ['订单号', '处理结果','运单号'];
            foreach($json as $vo){
                if($vo['result'] == 'success'){
                    $temp = '<a >处理成功</a>';
                }else{
                    $temp = '<a style="color:red;">处理失败</a>';
                }
                $rows[] =  [
                    $vo['package_num'],
                    $temp,
                    $vo['data']
                ];
            }


            $table = new Table($headers, $rows);

            echo $table->render();
        }

        //return view('admin.apiAlertPage');
    }

    //取消物流单号
    public function cancelPackageWuliuNum(){
        $from_area = Admin::user() -> from_area;
        $admin_username = Admin::user() -> username;
        $package_ids = $_POST['check_arr'];
        //var_dump($package_ids);exit;
        $package_ids_res = [];
        //取消包裹 必须是勾选包裹取消， 必须是他区域自己的包裹 必须是同一个route
        $route_id = '';

        if(!strstr($admin_username,'admin')){
            //判断下是否是他的订单
            foreach($package_ids as $vo){
                $package_info = DB::table('packages') -> where([
                    'id' => $vo,
                    'flag' => 0
                ]) -> first();
                if(!$package_info || !$package_info -> wuliu_num){
                    continue;
                }
                //查查他的订单  看区域符合不符合要求
                $order_info = DB::table('order') -> where([
                    'id' => $package_info -> order_id,
                    'flag' => 0
                ]) -> first();
                if(!$order_info){
                    continue;
                }

                if($order_info -> from_area != $from_area){
                    continue;
                }


                //检查 route_id 是不是同一个
                if($route_id && $route_id != $package_info -> route_id){
                    echo 'route_error';exit;
                }
                $route_id = $package_info -> route_id;


                $package_ids_res[] = $package_info;

            }
        }else{
            foreach($package_ids as $vo){
                $package_info = DB::table('packages') -> where([
                    'id' => $vo,
                    'flag' => 0
                ]) -> first();
                if(!$package_info || !$package_info -> wuliu_num){
                    continue;
                }

                //检查 route_id 是不是同一个
                if($route_id && $route_id != $package_info -> route_id){
                    echo 'route_error';exit;
                }
                $route_id = $package_info -> route_id;

                $package_ids_res[] = $package_info;

            }
        }

        if(!$package_ids_res){
            echo 'nodata';exit;
        }

        $apiRes = [];
        $model_area_price = new AreaName();
        $model_order = new Order();
        //$package_ids_res 拿到 所有需要取消单号的packageinfo


        foreach($package_ids_res as $package_info){
            //取消单号
            //请求api
            $api_data = $model_order -> deleteApiData($package_info -> wuliu_num,$route_id);


            $json_data = json_decode($api_data,true);
            if($package_info -> is_api){
                $json_data['Code'] = 200;
            }
            //$json_data['Code'] = 200;
            if($json_data['Code'] == '200'){
                //取消成功的话 给他返余额
                if(!$package_info -> split_id){
                    $model_area_price -> cancelPackage($package_info -> id);
                }

                //并且 把包裹的单号 删除掉
                DB::table('packages') -> where([
                    'id' => $package_info -> id
                ]) -> update([
                    'wuliu_num' => '',
                    //删除单号 把pdf 也删掉
                    'pdf' => ''
                ]);

                //小程序订单里 package_id 为此的 改为 待发货
                DB::table('mp_temp_package_number')
                    -> where([
                        'package_id' => $package_info -> id
                    ]) -> update([
                        'order_status' => 2
                    ]);


                //判断订单里有几个没发货的 如果全没发货 则订单状态改为 待发货1  否则 为部分发货2
                $count_send = DB::table('packages') -> where([
                    'order_id' => $package_info -> order_id,
                    'flag' => 0,
                ]) -> where('wuliu_num','!=','') -> count();
                if($count_send){
                    //部分发货
                    DB::table('order') -> where([
                        'id' => $package_info -> order_id,
                    ]) -> update([
                        'status' => 2  //部分发货
                    ]);
                }else{
                    DB::table('order') -> where([
                        'id' => $package_info -> order_id,
                    ]) -> update([
                        'status' => 1 //待发货
                    ]);
                }

                $apiRes[$package_info -> package_num]['data'] = $json_data['Data'];
                $apiRes[$package_info -> package_num]['result'] = 'success';
                $apiRes[$package_info -> package_num]['package_num'] = $package_info -> package_num;
            }else{
                $apiRes[$package_info -> package_num]['data'] = $json_data['ErrorMsg'];
                $apiRes[$package_info -> package_num]['result'] = 'error';
                $apiRes[$package_info -> package_num]['package_num'] = $package_info -> package_num;
            }


        }




        echo json_encode($apiRes);




    }


    //取消订单弹框
    public function cancelpackageAlert(){
        $json = json_decode($_POST['data'],true);


        $headers = ['订单号', '处理结果','说明'];
        foreach($json as $vo){
            if($vo['result'] == 'success'){
                $temp = '<a >处理成功</a>';
            }else{
                $temp = '<a style="color:red;">处理失败</a>';
            }
            $rows[] =  [
                $vo['package_num'],
                $temp,
                $vo['data']
            ];
        }


        $table = new Table($headers, $rows);

        echo $table->render();

    }

    //查询订单状态api
    public function refreshPackageStatus($wuliu_num){
        $token = $this -> sendXmlGetToken();
        dump($token);
        $res = $this -> sendXmlGetTax($wuliu_num);
        dd($res);

        //$this -> sendXmlGetWeight($token,$wuliu_num);
        $this -> getPackageStatus($wuliu_num,$token);

    }

    function get_utf8_string($content) {    //  将一些字符转化成utf8格式
        $encoding = mb_detect_encoding($content, array('ASCII','UTF-8','GB2312','GBK','BIG5'));
        return  mb_convert_encoding($content, 'utf-8', $encoding);
    }


    

    //超级删除
    public function superDel(){
        //先看下 是不是他的
        $admin_user_name = Admin::user() -> username;
        $from_area = Admin::user() -> from_area;

        $package_info = DB::table('packages') -> where([
            'id' => $_POST['id'],
        ]) -> first();
        $order_info = DB::table('order') -> where([
            'id' => $package_info -> order_id,
        ]) -> first();

        if(!strstr($admin_user_name,'admin')){
            if($order_info -> from_area != $from_area){
                echo 'error';exit;
            }
        }


        if($_POST['type'] == 2){
            //不返还余额

        }else{
            //返还到余额
            $model_area = new AreaName();
            $model_area -> updatePackage($order_info -> from_area,$package_info -> area_price,1,$package_info -> package_num);
        }

//强制删除
        DB::table('packages') -> where([
            'id' => $_POST['id']
        ]) -> update([
            'updated_at' => time(),
            'flag' => 1
        ]);
        //删除包裹后 重新计算订单
        DB::table('order') -> where([
            'id' => $package_info -> order_id
        ]) -> update([
            'price' => $order_info -> price - $package_info -> price,
            'cost' => $order_info -> cost - $package_info -> cost,
            //订单的包裹数量减1
            'count_package' => $order_info -> count_package - 1,
            'count_address' => ceil(($order_info -> count_package - 1)/3)
        ]);



        echo 'success';

    }


    //包裹搜索
    public function packageSearch(){
        return Admin::content(function (Content $content)  {

            $content->header('包裹搜索');
            $content->description('');

            $content->body($this -> grid_package());
        });
    }

    public function packageEms(){
        return Admin::content(function (Content $content)  {

            $content->header('关邮e通数据');
            $content->description('');

            $content->body($this -> grid_package_ems());
        });
    }


    protected function grid_package_ems()
    {
        return Admin::grid(Package::class, function (Grid $grid) {

            //$grid->id('ID');

            $grid->model()->where('flag','0');
            $grid->model()->orderBy('id', 'desc');
            $grid->model()->where('wuliu_num','<>', '');

            //属于的区域
            //admin 可以看所有的
            $admin_user_name = Admin::user() -> username;

            $from_area = Admin::user()->from_area;




            if(!strstr($admin_user_name,'admin')){
                //如果是合作人，则能看到他自己新增区域下的订单
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
                        $grid -> model()->whereIn('from_area',$area_ids);
                    }else{

                        $grid->model()->where('from_area',$from_area);
                    }
                }else{
                    $grid->model()->where('from_area',$from_area);
                }

            }



            $grid->expandFilter();
            $grid->filter(function($filter)use($admin_user_name){
                $filter->column(1/2, function ($filter)use($admin_user_name) {
                    $area_names = DB::table('area_name') -> get();
                    foreach($area_names as $vo){
                        $area_setting[$vo -> id] = $vo -> area_name;
                    }
                    //区域筛选
                    if(strstr($admin_user_name,'admin')) {
                        $filter->where(function ($query) {
                            $input = $this->input;


                            $query->where('from_area', '=', "{$input}");
                        }, '区域')->select($area_setting);
                    }

                    $filter->where(function ($query) {
                        $input = $this->input;
                        $query -> where('package_num','=',"{$input}");

                    }, '包裹编号');


                    //筛选运单号
                    $filter->where(function ($query) {

                        $input = $this->input;
                        $query -> where('wuliu_num','=',"{$input}");

                    }, '运单编号');

                    //筛选提单状态
                    $tracking_status = config('admin.tracking_status');
                    $filter->where(function ($query) {
                        $input = $this->input;


                        $query->where('trackingStatus', '=', "{$input}");
                    }, '提单状态')->select($tracking_status);



                });


                $filter->column(1/2, function ($filter) {


                    //通过物流单号查找包裹
                    $filter -> where(function($query){
                        $input = trim($this->input);
                        //通过input 查找物流单号信息
                        $repertory_info = Repertory::where('numbers','like','%'.$input.'%') -> get();
                        if(count($repertory_info)){
                            //查找物流单号的包裹
                            $repertory_ids = [];
                            foreach($repertory_info as $vo){
                                $repertory_ids[] = $vo -> id;
                            }

                            $batch_packages_relation = DB::table('batch_packages_relation') -> whereIn('repertory_id',$repertory_ids) -> get();
                            $package_ids = [];
                            if(count($batch_packages_relation)){
                                foreach($batch_packages_relation as $vo){
                                    $package_ids[] = $vo -> package_id;
                                }
                                $query -> whereIn('id',$package_ids);
                            }else{
                                $query -> where('id',0);
                            }
                        }else{
                            $query -> where('id',0);
                        }

                    },'物流单号');

                    $filter -> where(function($query){
                        $query->whereHas('packageEms', function ($query) {
                            $query -> where('ems_status',$this->input);
                        });

                    },'清关状态') -> select(config('admin.ems_status'));

                    $filter->where(function ($query) {
                        $input = strtotime($this->input);
                        $query->where('created_at', '>=', "{$input}");

                    }, '日期大于')->date();

                    $filter->where(function ($query) {
                        $input = strtotime($this->input);
                        $query->where('created_at', '<=', "{$input}");

                    }, '日期小于')->date();
                    $filter->where(function ($query) {
                        //先检查此名字的userid
                        $userdata = DB::table('wxuser') -> where('nickname','like',"%"."{$this->input}"."%") -> get();
                        if($userdata){
                            $userids = [];
                            foreach($userdata as $vo){
                                $userids[] = $vo -> id;
                            }
                        }
                        $query -> whereIn('userid',$userids);
                    }, '客户姓名');

                    $filter -> where(function($query){
                        if($this -> input == 1){
                            $query -> whereHas('packageEms',function($query){
                                $query -> where('mail_pay_time','<>','');
                            });
                        }elseif($this -> input == 2){
                            $query -> whereHas('packageEms',function($query){
                                $query -> where('mail_pay_time','=','');
                            });
                        }
                    },'是否支付税金') -> select([
                        '1' => '是',
                        '2' => '否'
                    ]);



                });


            });

            $grid->actions(function ($actions) {
                $actions->disableDelete();
                $actions->disableEdit();
                // 添加操作
                $key = $actions->getKey();

                //$actions->append(new CheckRow($key));
            });


            $grid->disableCreateButton();
            //$grid->disableExport();
            $grid->exporter( new EmsPackageExcelExpoter());


            $grid -> wxusers() -> nickname('用户');
            $grid -> areas() -> area_name('区域');

            $grid -> package_num('包裹编号');
            $grid -> wuliu_num('运单号');



            $grid -> column('交货单号','交货单号') -> display(function(){
                //包裹id
                $value = $this -> id;
                //查找包裹 批次关系 ， 有批次关系 再查找批次对应交货单
                $relations = DB::table('batch_packages_relation') -> where([
                    'package_id' => $value
                ]) -> first();
                if($relations){
                    //查找pici
                    $batch_info = DB::table('batch_packages') -> where([
                        'id' => $relations -> batch_id
                    ]) -> first();
                }else{
                    //根据package_id 查托盘号
                    $batch_info = DB::table('batch_packages') -> where(function($query)use($value){
                        $query -> where('package_ids','like','%'.$value.',%');
                        $query -> orWhere('package_ids','like','%,'.$value.',%');
                        $query -> orWhere('package_ids','like','%,'.$value.'%');
                    }) -> first();
                }


                if($batch_info){
                    //发货单
                    $send_order_info = DB::table('send_order_list') -> where([
                        'id' => $batch_info -> send_order_id
                    ]) -> first();
                    if($send_order_info){
                        return $send_order_info -> order_num;
                    }else{
                        return '';
                    }

                }else{
                    return '';
                }
            });



            $grid -> name('姓名');
            $grid -> tel('电话');


            $grid->trackingStatus('提单状态') -> display(function($value){
                /*
                //提单状态 代码转换
                $trackingStatus_config = config('admin.tracking_status');
                if(isset($trackingStatus_config[$value])){
                    return $trackingStatus_config[$value];
                }else{
                    return '';
                }
                */
                $model = new Order();
                return $model -> transTrackingStatus($value);
            });
            $grid -> packageEms() -> ems_status('清关状态') -> display(function($val){
                $ems_status = config('admin.ems_status');
                if($val){
                    return $ems_status[$val];
                }
            });

            $grid -> packageEms() -> release_time('放行时间')->display(function($val){
                if(!$val){
                    return '';
                }
                return date('Y-m-d',$val);
            });
            $grid -> packageEms() -> inspection_time('查验时间')->display(function($val){
                if(!$val){
                    return '';
                }
                return date('Y-m-d',$val);
            });
            $grid -> column('申报税金','申报税金') -> display(function($val){
                $paratemer = $this -> paratemer;
                $taxs_config = config('admin.goods_tax');

                if($paratemer){
                    $tax1 = 0;
                    $tax2 = 0;
                    $tax3 = 0;
                    foreach($taxs_config as $vo){
                        if($vo['code'] == $paratemer -> Tax_code1){
                            $tax1 = floatval($vo['tax']);
                        }
                        if($vo['code'] == $paratemer -> Tax_code2){
                            $tax2 = floatval($vo['tax']);
                        }
                        if($vo['code'] == $paratemer -> Tax_code3){
                            $tax3 = floatval($vo['tax']);
                        }
                    }
                    return round((floatval($paratemer -> s_price1) * floatval($paratemer -> s_pieces1) * $tax1)+(floatval($paratemer -> s_price2) * floatval($paratemer -> s_pieces2) * $tax2)+(floatval($paratemer -> s_price3) * floatval($paratemer -> s_pieces3) * $tax3),2);
                }

            });
            $grid -> packageEms() -> taxes('实际税金');
            $grid -> packageEms() -> mail_pay_time('支付税金时间')->display(function($val){
                if($val){
                    return substr($val,0,8);
                }

            });
            $grid -> packageEms() -> send_time('送货时间')->display(function($val){
                if(!$val){
                    return '';
                }
                return date('Y-m-d',$val);
            });
            $grid -> packageEms() -> into_time('到货时间')->display(function($val){
                if(!$val){
                    return '';
                }
                return date('Y-m-d',$val);
            });


            $grid->actions(function ($actions) {
                $id = $actions->getKey();
                /*
                $package_info = DB::table('packages') -> where([
                    'id' => $id
                ]) -> first();
                */
                //内网轨迹
                //$actions->append(new TrackAlert($id));
                //外网轨迹
                //$actions->append(new TrackAlerts($id));
                //海关状态
                $actions->append(new MailStatus($id));
                $actions->disableDelete();
                $actions->disableEdit();
            });

            $grid->tools(function ($tools)use($admin_user_name) {
                $tools->batch(function ($batch)use($admin_user_name) {
                    $batch->disableDelete();
                });
                $tools -> append(new UpdateMailPayLog());
            });

        });
    }


    //交货单echarts
    public function emsEcharts(){
        return Admin::content(function (Content $content)  {
            $content->header('关邮e通数据');
            $content->description('');
            $view = view('admin.emsEcharts') -> render();
            $content->body($view);
        });
    }
    public function emsEchartsAjax(){
        $data = DB::table('ems_charts')
            -> where('date_true_str','>=',strtotime('-20 days'))
            -> orderBy('date_true_str','asc')
            -> get();
        //找出有多少日期
        $date_arr = [];

        //找出每个类别的个数
        $status_type_arr = [
            '1' => [],
            '2' => [],
            '3' => [],
            '4' => [],
            '5' => [],
            '6' => [],
            '7' => [],
            '8' => [],
            '9' => [],
        ];

        //最大包裹数量
        $max = 0;
        foreach($data as $vo){
            if(intval($vo -> num) > $max){
                $max = intval($vo -> num);
            }
            if(!in_array($vo -> date_str,$date_arr)){
                $date_arr[] = $vo -> date_str;
            }

            if(isset($status_type_arr[$vo -> status_type])){
                $status_type_arr[$vo -> status_type][] = $vo -> num ;
            }
        }

        return [
            'data_arr' => $date_arr,
            'status_arr' => $status_type_arr,
            'max' => $max,
            'ems_status' => config('admin.ems_status'),
            'ems_status_str' => array_values(config('admin.ems_status'))

        ];


    }




    public function updateMailPayLog(){
        var_dump(1111);
        dispatch(new GetMailPayLog());
    }



    protected function grid_package()
    {
        return Admin::grid(Package::class, function (Grid $grid) {

            //$grid->id('ID');
            if(isset($_GET['packages_nums']) && $_GET['packages_nums']){
                /*
                adsfasf1\r\n
                asdfasfd1

                */

                $packages_nums = explode("\r\n",$_GET['packages_nums']);
                foreach($packages_nums as $k => $vo){
                    $packages_nums[$k] = trim($vo);
                }


                $grid -> model() -> whereIn('package_num',$packages_nums);


                //dd($_GET['packages_nums']);
            }

            $grid->model()->where('flag','0');
            $grid->model()->orderBy('id', 'desc');
            //$grid->model()->where('wuliu_num','<>', '');

            //属于的区域
            //admin 可以看所有的
            $admin_user_name = Admin::user() -> username;

            $from_area = Admin::user()->from_area;

            


            if(!strstr($admin_user_name,'admin')){
                //如果是合作人，则能看到他自己新增区域下的订单
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
                        $grid -> model()->whereIn('from_area',$area_ids);
                    }else{

                        $grid->model()->where('from_area',$from_area);
                    }
                }else{
                    $grid->model()->where('from_area',$from_area);
                }

            }



            $grid->expandFilter();
            $grid->filter(function($filter)use($admin_user_name){
                $filter->column(1/3, function ($filter)use($admin_user_name) {
                    $area_names = DB::table('area_name') -> get();
                    foreach($area_names as $vo){
                        $area_setting[$vo -> id] = $vo -> area_name;
                    }
                    //区域筛选
                    if(strstr($admin_user_name,'admin')) {
                        $filter->where(function ($query) {
                            $input = $this->input;


                            $query->where('from_area', '=', "{$input}");
                        }, '区域')->select($area_setting);
                    }

                    $filter->where(function ($query) {
                        $input = $this->input;
                        $query -> where('package_num','=',"{$input}");

                    }, '包裹编号');


                    //筛选运单号
                    $filter->where(function ($query) {

                        $input = $this->input;
                        $query -> where('wuliu_num','=',"{$input}");

                    }, '运单编号');

                    //筛选提单状态
                    $tracking_status = config('admin.tracking_status');
                    $filter->where(function ($query) {
                        $input = $this->input;


                        $query->where('trackingStatus', '=', "{$input}");
                    }, '提单状态')->select($tracking_status);



                });


                $filter->column(1/3, function ($filter) {
                    $filter -> where(function($query){
                        $query->whereHas('packageEms', function ($query) {
                            $query -> where('ems_status',$this->input);
                        });

                    },'清关状态') -> select(config('admin.ems_status'));


                    //通过物流单号查找包裹
                    $filter -> where(function($query){
                        $input = trim($this->input);
                        //通过input 查找物流单号信息
                        $repertory_info = Repertory::where('numbers','like','%'.$input.'%') -> get();
                        if(count($repertory_info)){
                            //查找物流单号的包裹
                            $repertory_ids = [];
                            foreach($repertory_info as $vo){
                                $repertory_ids[] = $vo -> id;
                            }

                            $batch_packages_relation = DB::table('batch_packages_relation') -> whereIn('repertory_id',$repertory_ids) -> get();
                            $package_ids = [];
                            if(count($batch_packages_relation)){
                                foreach($batch_packages_relation as $vo){
                                    $package_ids[] = $vo -> package_id;
                                }
                                $query -> whereIn('id',$package_ids);
                            }else{
                                $query -> where('id',0);
                            }
                        }else{
                            $query -> where('id',0);
                        }

                    },'物流单号');

                    $filter->where(function ($query) {
                        $input = strtotime($this->input);
                        $query->where('created_at', '>=', "{$input}");

                    }, '日期大于')->date();

                    $filter->where(function ($query) {
                        $input = strtotime($this->input);
                        $query->where('created_at', '<=', "{$input}");

                    }, '日期小于')->date();
                });


                $filter->column(1/3, function ($filter) {
                    $status_setting = config('admin.order_status_new');
                    $filter->where(function ($query) {

                        $input = $this->input;
                        if($input == '10'){
                            $query->whereHas('order', function ($query)use($input) {
                                $query->where('status', '!=', 9);
                            });
                        }else{
                            $query->whereHas('order', function ($query)use($input) {
                                $query->where('status', '=', "{$input}");
                            });
                        }

                    }, '发货状态')->select($status_setting);


                    //新订单状态筛选 支付状态
                    $pay_status_setting = config('admin.pay_status_new');
                    $filter->where(function ($query) {
                        $input = $this->input;
                        if($input == '10'){
                            $query->whereHas('order', function ($query)use($input) {
                                $query->where('pay_status', '!=', 9);
                            });
                        }else{
                            $query->whereHas('order', function ($query)use($input) {
                                $query->where('pay_status', '=', "{$input}");
                            });
                        }

                    }, '付款状态')->select($pay_status_setting);


                    $filter->where(function ($query) {
                        //先检查此名字的userid
                        $userdata = DB::table('wxuser') -> where('nickname','like',"%"."{$this->input}"."%") -> get();
                        $userids = [];
                        if($userdata){

                            foreach($userdata as $vo){
                                $userids[] = $vo -> id;
                            }
                        }
                        $query -> whereIn('userid',$userids);
                    }, '昵称');

                    $filter->where(function ($query) {
                        $batch_packages = DB::table('batch_packages_relation')
                            -> leftJoin('batch_packages','batch_packages_relation.batch_id','batch_packages.id')
                            -> where('batch_packages.batch_num','like','%'.trim($this -> input).'%')
                            -> select([
                                'batch_packages_relation.package_id'
                            ])
                            -> get();
                        $package_ids = [];
                        if(count($batch_packages)){
                            foreach($batch_packages as $k => $vo){
                                $package_ids[] = $vo -> package_id;
                            }
                        }
                        $query -> whereIn('id',$package_ids);
                    }, '托盘号');
                    $filter->where(function ($query) {
                        $batch_packages = DB::table('batch_packages_relation')
                            -> leftJoin('batch_packages','batch_packages_relation.batch_id','batch_packages.id')
                            -> leftJoin('send_order_list','send_order_list.id','batch_packages.send_order_id')
                            -> where('send_order_list.order_num','like','%'.trim($this -> input).'%')
                            -> select([
                                'batch_packages_relation.package_id'
                            ])
                            -> get();
                        $package_ids = [];
                        if(count($batch_packages)){
                            foreach($batch_packages as $k => $vo){
                                $package_ids[] = $vo -> package_id;
                            }
                        }
                        $query -> whereIn('id',$package_ids);
                    }, '交货单号');
                    $filter->where(function ($query) {


                        $area_scan_order = DB::table('area_scan_order_info')
                            -> leftJoin('area_scan_order','area_scan_order_info.order_id','area_scan_order.id')
                            -> leftJoin('repertory','area_scan_order.repertory_id','repertory.id')
                            -> select([
                                'area_scan_order_info.mp_number_id'
                            ])
                            -> where('repertory.numbers','like','%'.trim($this -> input).'%')
                            -> get();
                        $mp_number_ids = [];
                        if(count($area_scan_order)){
                            foreach($area_scan_order as $vo){
                                $mp_number_ids[] = $vo -> mp_number_id;
                            }
                        }


                        $query->whereHas('order', function ($query)use($mp_number_ids) {
                            $query->whereIn('mp_package_id',$mp_number_ids );
                        });

                    }, '区域发货单号');


                });



            });

            $grid->tools(function ($tools)use($admin_user_name) {
                $tools->batch(function ($batch)use($admin_user_name) {
                    $batch->disableDelete();

                    $batch->add('生成pdf',new PackageSearchPdfApi());

                    if(strstr($admin_user_name,'admin')){
                        $batch->add('打印pdf',new PackageSearchPrintPdf('package'));
                        $batch->add('批量修改申报',new DeclareAll('package'));
                    }


                });

                //批量筛选按钮
                $tools -> append(new BatchScreen(2));
                //$tools -> append(new CancelPackets($admin_user_name,$admin_user_name));
            });




            $grid->disableCreateButton();
            //$grid->disableExport();
            $grid->exporter( new PackageExcelExpoter());

            //$grid->disableRowSelector();

            $grid -> created_at('创建日期') -> display(function($value){
                return date('Y-m-d H:i',$value);
            });

            $grid -> from_area('区域') -> display(function($value){
                if($value){
                    $area_info = DB::table('area_name') -> where([
                        'id' => $value
                    ]) -> first();
                    if($area_info){
                        return  $area_info -> area_name;
                    }

                }
            });

            $grid -> package_num('包裹编号');

            $grid -> order() -> status('发货状态') -> display(function($val){
                $status_setting = config('admin.order_status_new');
                if(isset($status_setting[$val])){
                    return $status_setting[$val];
                }
            });
            $grid -> order() -> pay_status('支付状态') -> display(function($val){

                if($val == 1){
                    if($this -> order -> pay_type == 9){
                        return '<a style="color:red;">虚拟支付</a>';
                    }else{
                        return '已支付';
                    }

                }else{
                    //dd($this -> pay_type);
                    $status_setting = config('admin.pay_status_new');
                    if(isset($status_setting[$val])){

                        return $status_setting[$val];
                    }
                }

            });

            $grid -> wxusers() -> nickname('昵称');
            $grid -> wuliu_num('运单号');



            $grid -> column('托盘号','托盘号') -> display(function(){
                //包裹id
                $value = $this -> id;
                //先利用包裹id 在batch_packages_relation 中找
                $relations = DB::table('batch_packages_relation') -> where([
                    'package_id' => $value
                ]) -> first();
                if($relations){
                    //查找pici
                    $batch_info = DB::table('batch_packages') -> where([
                        'id' => $relations -> batch_id
                    ]) -> first();
                }else{
                    //根据package_id 查托盘号
                    $batch_info = DB::table('batch_packages') -> where(function($query)use($value){
                        $query -> where('package_ids','like','%'.$value.',%');
                        $query -> orWhere('package_ids','like','%,'.$value.',%');
                        $query -> orWhere('package_ids','like','%,'.$value.'%');
                    }) -> first();
                }
                if($batch_info){
                    return $batch_info -> batch_num;
                }else{
                    return '';
                }



            });


            $grid -> column('交货单号','交货单号') -> display(function(){
                //包裹id
                $value = $this -> id;
                //查找包裹 批次关系 ， 有批次关系 再查找批次对应交货单
                $relations = DB::table('batch_packages_relation') -> where([
                    'package_id' => $value
                ]) -> first();
                if($relations){
                    //查找pici
                    $batch_info = DB::table('batch_packages') -> where([
                        'id' => $relations -> batch_id
                    ]) -> first();
                }else{
                    //根据package_id 查托盘号
                    $batch_info = DB::table('batch_packages') -> where(function($query)use($value){
                        $query -> where('package_ids','like','%'.$value.',%');
                        $query -> orWhere('package_ids','like','%,'.$value.',%');
                        $query -> orWhere('package_ids','like','%,'.$value.'%');
                    }) -> first();
                }


                if($batch_info){
                    //发货单
                    $send_order_info = DB::table('send_order_list') -> where([
                        'id' => $batch_info -> send_order_id
                    ]) -> first();
                    if($send_order_info){
                        return $send_order_info -> order_num;
                    }else{
                        return '';
                    }

                }else{
                    return '';
                }

            });


            //物流单号
            $grid -> column('区域发货单号','区域发货单号') -> display(function(){

                //包裹id
                $value = $this -> id;
                //查找包裹所属于的relation
                $relations = DB::table('batch_packages_relation') -> where([
                    'package_id' => $value
                ]) -> first();
                if($relations){
                    $repertory_info = Repertory::find($relations -> repertory_id);
                    if(!$repertory_info){
                        return '';
                    }
                    return $repertory_info -> numbers;
                }else{
                    $order_id = $this -> order_id;
                    $order_info = DB::table('order')
                        -> where([
                            'id' => $order_id
                        ]) -> first();
                    if($order_info && $order_info -> mp_package_id){
                        //查找此小程序包裹的 物流单号
                        $area_scan_order_info = DB::table('area_scan_order_info')
                            -> where([
                                'mp_number_id' => $order_info -> mp_package_id
                            ]) -> first();
                        if($area_scan_order_info){

                            $area_scan = DB::table('area_scan_order') -> where([
                                'id' => $area_scan_order_info -> order_id
                            ]) -> first();
                            if($area_scan && $area_scan -> repertory_id){
                                $repertory_info = DB::table('repertory') -> where([
                                    'id' => $area_scan -> repertory_id
                                ]) -> first();
                                if($repertory_info){
                                    return $repertory_info ->numbers;
                                }

                            }

                        }
                    }


                    return '';
                }


            });

            $grid -> name('姓名');
            //$grid -> tel('电话');
            $grid -> weight('申报重');
            $grid -> pass_weight('过机重');

            $grid->trackingStatus('提单状态') -> display(function($value){
                /*
                //提单状态 代码转换
                $trackingStatus_config = config('admin.tracking_status');
                if(isset($trackingStatus_config[$value])){
                    return $trackingStatus_config[$value];
                }else{
                    return '';
                }
                */
                $model = new Order();
                return $model -> transTrackingStatus($value);
            });
            $grid -> packageEms() -> ems_status('清关状态') -> display(function($val){
                $ems_status = config('admin.ems_status');
                if($val){
                    return $ems_status[$val];
                }
            });




            /*
            $grid -> column('详情','详情') -> display(function($val){
                $numbers = $this -> wuliu_num;
                if($numbers){
                    $html = '';
                    //第一次拆单
                    $first_data = DB::table('packages_split_20181226') -> where('package_wuliu_num',$numbers) -> first();
                    //第二次拆单
                    $second_data = SplitPackage::where('package_wuliu_num',$numbers) -> first();
                    //扫描
                    $commodity_data = CommodityCodeTemp::where('number',$numbers) -> first();
                    if($first_data){
                        $html .= '第一次拆单、';
                    }
                    if($second_data){
                        $html .= '第二次拆单、';
                    }
                    if(isset($second_data -> is_order)){
                        $html .= '第二次拆单且下单、';
                    }
                    if($commodity_data){
                        $html .= '扫描包裹';
                    }
                    return $html;

                }
            });
            */

            $grid->actions(function ($actions) {
                $id = $actions->getKey();
                /*
                $package_info = DB::table('packages') -> where([
                    'id' => $id
                ]) -> first();
                */

                    //内网轨迹
                    //$actions->append(new TrackAlert($id));


                    //外网轨迹
                    //$actions->append(new TrackAlerts($id));
                    //海关状态
                    $actions->append(new MailStatus($id));


                $actions->disableDelete();
                $actions->disableEdit();
            });

            $grid->tools(function ($tools)use($admin_user_name) {
                $tools->batch(function ($batch)use($admin_user_name) {
                    $batch->disableDelete();
                });
            });


        });
    }


    //获取包裹轨迹
    public function getTrackList(){
        $id = $_POST['id'];
        $package_info = DB::table('packages') -> where([
            'id' => $id
        ]) -> first();
        if(!$package_info -> trackingList){
            echo 'error';exit;
        }

        $json_data = json_decode($package_info -> trackingList,true);
        $headers = ['时间', '状态'];
        $rows = [];
        foreach($json_data as $vo){
            $rows[] =  [
                $vo['Time'],
                $vo['Context'],
            ];
        }
        $table = new Table($headers, $rows);

        echo $table->render();
    }

    //获取包裹 外网轨迹
    public function getTrackListWai(){
        $id = $_POST['id'];
        $package_info = DB::table('packages') -> where([
            'id' => $id
        ]) -> first();
        if(!$package_info -> trackingMoreJson){
            echo 'error';exit;
        }
        $json_data = json_decode($package_info -> trackingMoreJson,true);
        $headers = ['',''];
        $rows[] = ['destination_info', ''];
        //$rows = [];
        foreach($json_data['destination_info']['trackinfo'] as $vo){
            $rows[] =  [
                $vo['Date'],
                $vo['StatusDescription'],
            ];
        }
        $rows[] = ['',''];
        //$table = new Table($headers, $rows);

        //echo $table->render();


        $rows[] = ['origin_info', ''];
        foreach($json_data['origin_info']['trackinfo'] as $vo){
            $rows[] =  [
                $vo['Date'],
                $vo['StatusDescription'],
            ];
        }
        $rows[] = ['',''];

        $table = new Table($headers, $rows);

        echo $table->render();



        /*

$headers = ['destination_info', ''];
        $rows = [];
        foreach($json_data['destination_info']['trackinfo'] as $vo){
            $rows[] =  [
                $vo['Date'],
                $vo['StatusDescription'],
            ];
        }
        $rows[] = ['',''];
        $table = new Table($headers, $rows);

        echo $table->render();


        $headers = ['origin_info', ''];
        $rows = [];
        foreach($json_data['origin_info']['trackinfo'] as $vo){
            $rows[] =  [
                $vo['Date'],
                $vo['StatusDescription'],
            ];
        }
        $rows[] = ['',''];

        $table = new Table($headers, $rows);

        echo $table->render();


        */

    }

    //获取海关状态
    public function getMailStatus(){
        $id = $_POST['id'];
        $package_info = DB::table('packages') -> where([
            'id' => $id
        ]) -> first();
        if(!$package_info -> mail_status_table){
            echo 'error';exit;
        }

        $html = "<h5>运单编号：".$package_info -> wuliu_num."</h5>";

        $html .= $package_info -> mail_status_table;

        echo $html;



    }




    //中通api 对接方法
    public function loginForCustomer(){
        $xml_post_string = '<?xml version="1.0" encoding="utf-8"?>
                            <soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
                              <soap:Body>
                                 <eExpress_Login xmlns="http://linexsolutions.com/">
                                      <loginID>NN100-A</loginID>
                                      <pwd>123456</pwd>
                                    </eExpress_Login>
                              </soap:Body>
                            </soap:Envelope>';

        $headers = array(
            "Content-type: text/xml;charset=\"utf-8\"",
            "Accept: text/xml",
            "Cache-Control: no-cache",
            "Pragma: no-cache",
            "SOAPAction: http://linexsolutions.com/eExpress_Login",
            "Content-length: ".strlen($xml_post_string),
        );

        $url = 'http://eexpress-ws.linexsolutions.com/eExpressClientWebService.asmx?op=eExpress_Login';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_post_string); // the SOAP request
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // converting
        $response = curl_exec($ch);
        if(curl_error($ch)){
            return curl_error($ch);
        }
        curl_close($ch);

        $p = xml_parser_create();
        xml_parse_into_struct($p, $response, $vals, $index);
        xml_parser_free($p);
        $request_index = $index['RESULT'][0];
        $msg_index = $index['MSG'][0];
        if($vals[$request_index]['value'] == 'T'){
            return $vals[$msg_index]['value'];
        }else{
            return false;
        }
    }


    public function editGoodsParatemer($id){
        $package_info = Package::find($id);

        if($id > config('admin.paratemer_package_id')){
            $goods_paratemer = DB::table('packages_goods_paratemer') -> where([
                'id' => $package_info -> goods_id
            ]) -> first();
        }else{
            $goods_paratemer = DB::table('goods_paratemer') -> where([
                'id' => $package_info -> goods_id
            ]) -> first();
        }


        $admin_name = Admin::user() -> username;
        if(!strstr($admin_name,'admin') && $package_info -> goods_id < 10000){
            $goods_paratemer -> s_content1 = '';
            $goods_paratemer -> s_content2 = '';
            $goods_paratemer -> s_content3 = '';

            $goods_paratemer -> Tax_code1 = '';
            $goods_paratemer -> Tax_code2 = '';
            $goods_paratemer -> Tax_code3 = '';


            $goods_paratemer -> s_price1 = '';
            $goods_paratemer -> s_price2 = '';
            $goods_paratemer -> s_price3 = '';


            $goods_paratemer -> s_pieces1 = '';
            $goods_paratemer -> s_pieces2 = '';
            $goods_paratemer -> s_pieces3 = '';


            $goods_paratemer -> s_weight1 = '';
            $goods_paratemer -> s_weight2 = '';
            $goods_paratemer -> s_weight3 = '';
        }

        //dd($goods_paratemer);
        return view('admin.editGoodsParatemerPage') -> with([
            'goods_paratemer' => $goods_paratemer,
            'package_id' => $id
        ]);
    }



    public function editGoodsParatemerRes(){
        if($_POST['package_id'] <= config('admin.paratemer_package_id')){
            //新增 goods_paratemer
            $goods_id = DB::table('goods_paratemer') -> insertGetId([
                's_content1' => isset($_POST['content'][0])?$_POST['content'][0]:'',
                's_content2' => isset($_POST['content'][1])?$_POST['content'][1]:'',
                's_content3' => isset($_POST['content'][2])?$_POST['content'][2]:'',

                'Tax_code1' => isset($_POST['code'][0])?$_POST['code'][0]:'',
                'Tax_code2' => isset($_POST['code'][1])?$_POST['code'][1]:'',
                'Tax_code3' => isset($_POST['code'][2])?$_POST['code'][2]:'',


                's_price1' => isset($_POST['price'][0])?$_POST['price'][0]:'',
                's_price2' => isset($_POST['price'][1])?$_POST['price'][1]:'',
                's_price3' => isset($_POST['price'][2])?$_POST['price'][2]:'',


                's_pieces1' => isset($_POST['pieces'][0])?$_POST['pieces'][0]:'',
                's_pieces2' => isset($_POST['pieces'][1])?$_POST['pieces'][1]:'',
                's_pieces3' => isset($_POST['pieces'][2])?$_POST['pieces'][2]:'',

                's_weight1' => isset($_POST['weight'][0])?$_POST['weight'][0]:'',
                's_weight2' => isset($_POST['weight'][1])?$_POST['weight'][1]:'',
                's_weight3' => isset($_POST['weight'][2])?$_POST['weight'][2]:'',
                'is_super' => 1,
                'declare_currency' => 'RMB',
                'declare_value' => 1000

            ]);
            Package::where('id',$_POST['package_id']) -> update([
                'goods_id' => $goods_id,
                'updated_at' => time()
            ]);
        }else{
            $package_info = Package::find($_POST['package_id']);
            DB::table('packages_goods_paratemer')
                -> where('id',$package_info -> goods_id)
                -> update([
                    's_content1' => isset($_POST['content'][0])?$_POST['content'][0]:'',
                    's_content2' => isset($_POST['content'][1])?$_POST['content'][1]:'',
                    's_content3' => isset($_POST['content'][2])?$_POST['content'][2]:'',

                    'Tax_code1' => isset($_POST['code'][0])?$_POST['code'][0]:'',
                    'Tax_code2' => isset($_POST['code'][1])?$_POST['code'][1]:'',
                    'Tax_code3' => isset($_POST['code'][2])?$_POST['code'][2]:'',


                    's_price1' => isset($_POST['price'][0])?$_POST['price'][0]:'',
                    's_price2' => isset($_POST['price'][1])?$_POST['price'][1]:'',
                    's_price3' => isset($_POST['price'][2])?$_POST['price'][2]:'',


                    's_pieces1' => isset($_POST['pieces'][0])?$_POST['pieces'][0]:'',
                    's_pieces2' => isset($_POST['pieces'][1])?$_POST['pieces'][1]:'',
                    's_pieces3' => isset($_POST['pieces'][2])?$_POST['pieces'][2]:'',

                    's_weight1' => isset($_POST['weight'][0])?$_POST['weight'][0]:'',
                    's_weight2' => isset($_POST['weight'][1])?$_POST['weight'][1]:'',
                    's_weight3' => isset($_POST['weight'][2])?$_POST['weight'][2]:'',
                ]);
        }




        return [
            'code' => 'success',
            'msg' => ''
        ];
        //var_dump(json_encode($_POST));
    }


    //批量修改申报
    public function editAllGoodsParatemerRes(){
        //var_dump(json_encode($_POST));
        try{

            if(isset($_POST['goods_ids']) && count($_POST['goods_ids'])){

                foreach($_POST['goods_ids'] as $k => $vo){

                    $content1 = $_POST['content1'][$k];
                    $content2 = $_POST['content2'][$k];
                    $content3 = $_POST['content3'][$k];

                    //税号
                    $tax_code1 = $_POST['tax_code1'][$k];
                    $tax_code2 = $_POST['tax_code2'][$k];
                    $tax_code3 = $_POST['tax_code3'][$k];

                    $pieces1 = $_POST['pieces1'][$k];
                    $pieces2 = $_POST['pieces2'][$k];
                    $pieces3 = $_POST['pieces3'][$k];

                    $price1 = $_POST['price1'][$k];
                    $price2 = $_POST['price2'][$k];
                    $price3 = $_POST['price3'][$k];





                    //先校验
                    if($content1 && $tax_code1 && $price1 && $pieces1){

                    }else{
                        return [
                            'code' => 500,
                            'msg' => '物品1填写不完整'
                        ];
                    }

                    if($content2 && $tax_code2 && $price2 && $pieces2){

                    }elseif($content2){
                        return [
                            'code' => 500,
                            'msg' => '物品2填写不完整'
                        ];
                    }else{
                        $_POST['content2'][$k] = '';
                        $_POST['tax_code2'][$k] = '';
                        $_POST['price2'][$k] = '';
                        $_POST['pieces2'][$k] = '';
                    }

                    if($content3 && $tax_code3 && $price3 && $pieces3 ){

                    }elseif($content3){
                        return [
                            'code' => 500,
                            'msg' => '物品3填写不完整'
                        ];
                    }else{
                        $_POST['content3'][$k] = '';
                        $_POST['tax_code3'][$k] = '';
                        $_POST['price3'][$k] = '';
                        $_POST['pieces3'][$k] = '';
                    }





                    if($tax_code1){
                        //Log::info($tax_code1);
                        //检验税号 是否存在
                        $tax_info1 = DB::table('goods_tax') -> where([
                            'code' => $tax_code1
                        ]) -> first();
                        if(!$tax_info1){
                            return [
                                'code' => 500,
                                'msg' => '第'.($k + 1).'行'.$tax_code1.'税号有误'
                            ];
                        }
                    }

                    if($tax_code2){
                        $tax_info2 = DB::table('goods_tax') -> where([
                            'code' => $tax_code2
                        ]) -> first();
                        if(!$tax_info2){
                            return [
                                'code' => 500,
                                'msg' => '第'.($k + 1).'行'.$tax_code2.'税号有误'
                            ];
                        }
                    }

                    if($tax_code3){
                        $tax_info3 = DB::table('goods_tax') -> where([
                            'code' => $tax_code3
                        ]) -> first();
                        if(!$tax_info3){
                            return [
                                'code' => 500,
                                'msg' => '第'.($k + 1).'行'.$tax_code3.'税号有误'
                            ];
                        }
                    }



                }


                foreach($_POST['goods_ids'] as $k => $vo){
                    //先校验
                    $content1 = $_POST['content1'][$k];
                    $content2 = $_POST['content2'][$k];
                    $content3 = $_POST['content3'][$k];

                    //税号
                    $tax_code1 = $_POST['tax_code1'][$k];
                    $tax_code2 = $_POST['tax_code2'][$k];
                    $tax_code3 = $_POST['tax_code3'][$k];

                    $pieces1 = $_POST['pieces1'][$k];
                    $pieces2 = $_POST['pieces2'][$k];
                    $pieces3 = $_POST['pieces3'][$k];

                    $price1 = $_POST['price1'][$k];
                    $price2 = $_POST['price2'][$k];
                    $price3 = $_POST['price3'][$k];


                    //Log::info($tax_code1);

                    //保存 goods_id
                    DB::table('packages_goods_paratemer')
                        -> where('id',$vo)
                        -> update([
                            's_content1' => $content1,
                            's_content2' => $content2,
                            's_content3' => $content3,

                            'Tax_code1' => $tax_code1,
                            'Tax_code2' => $tax_code2,
                            'Tax_code3' => $tax_code3,


                            's_price1' => $price1,
                            's_price2' => $price2,
                            's_price3' => $price3,


                            's_pieces1' => $pieces1,
                            's_pieces2' => $pieces2,
                            's_pieces3' => $pieces3,

                        ]);


                }




                return [
                    'code' => 200,
                    'msg' => '保存成功'
                ];



            }else{
                return [
                    'code' => 500,
                    'msg' => '数据有误'
                ];
            }
        }catch (\Exception $exception){
            Log::info($exception ->getTraceAsString());
            return [
                'code' => 500,
                'msg' => $exception ->getMessage()
            ];
        }

    }


    //通过税号 获取税率
    public function getGoodsTax(){
        $code = trim($_POST['tax']);
        $tax_info = DB::table('goods_tax') -> where([
            'code' => $code
        ]) -> first();
        if($tax_info){
            return [
                'code' => 'success',
                'msg' => $tax_info -> tax
            ];
        }else{
            return [
                'code' => 'error',
                'msg' => ''
            ];
        }
    }


    //退件下单
    public function returnOrder(Content $content)
    {
        // 选填
        $content->header('退件下单');
        // 选填
        $content->description();

        $content->body($this -> returnUnderOrder());
        return $content;
    }


    //退件下单表单
    public function returnUnderOrder(){
        return Admin::form(Order::class,function(Form $form){

            $form -> textarea('return_number','退单单号') -> rows(10)->setWidth(2, 1);
            $form -> setAction(admin_base_path('returnUnderOrderRes'));
        });
    }

    public function returnUnderOrderRes(){
        $text = '';
        if($_REQUEST['return_number']){
            $text = explode("\r\n",trim($_REQUEST['return_number']));
        }
        //dd($text);

        if(!$text){
            admin_toastr('请输入退单单号');
            return redirect(admin_base_path('returnOrder'));
        }

        foreach($text as $vo){
            if($vo){

            }
        }




    }







}
