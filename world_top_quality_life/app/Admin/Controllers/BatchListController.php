<?php

namespace App\Admin\Controllers;

use App\Admin\Extensions\NoPassPackage;
use App\Admin\Extensions\SendOrderNum;
use App\Admin\Extensions\TrackingMore;
use App\BatchList;

use App\BatchPackagesRelation;
use App\BatchRepertoryRelation;
use App\Order;
use App\Package;
use App\SendOrder;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;
use Encore\Admin\Widgets\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class BatchListController extends Controller
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

            $content->header('托盘列表');
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

            $content->header('托盘列表');
            $content->description('详情');

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
    protected function grid($is_sendorder = false)
    {
        return Admin::grid(BatchList::class, function (Grid $grid)use($is_sendorder) {
            $grid->paginate(50);
            $grid->model() -> orderBy('id','desc');
            if(!$is_sendorder){
                //$grid->model()->where('order_num', '<>', '');
            }else{
                //创建交货单
                $grid->model()->where('send_order_id', '=', 0);
            }
            $grid->id('ID')->sortable();
            $grid->batch_num('批次号');
            $grid->count_packages('包裹数量');
            $grid->column('idsss','到货物流单号')->display(function($value){

                $relation = DB::table('batch_repertory_relation') -> where([
                    'batch_id' => $this -> id
                ]) -> get();
                if(count($relation)){
                    $repertory_numbers = [];

                    foreach($relation as $vo){
                        $temp = DB::table('repertory') -> where([
                            'id' => $vo -> repertory_id
                        ]) -> first();
                        if($temp){
                            $repertory_numbers[] = $temp -> numbers;
                        }

                    }
                    return implode('  <br> ',$repertory_numbers);

                }else{
                    return '';
                }
            });
            if(!$is_sendorder){
                //$grid->order_num('交货单号');
                $grid->send_order_id('交货单号')->display(function($value){
                    if(!$value){
                        return '';
                    }
                    $send_order_info = DB::table('send_order_list') -> where([
                        'id' => $value
                    ]) -> first();
                    return $send_order_info -> order_num;
                });
            }


            //根据 托盘id 查找包裹
            $grid -> column('route','路线') -> display(function($value){
                //路线
                $route_config = config('admin.route_setting');
                //通过batch_id 查关联的第一个包裹
                $relation = DB::table('batch_packages_relation') -> where([
                    'batch_id' => $this -> id
                ]) -> first();
                if(!$relation){
                    return '';
                }else{
                    $package_info = DB::table('packages') -> where([
                        'id' => $relation -> package_id
                    ]) -> first();
                    if($package_info){
                        return $route_config[$package_info -> route_id];
                    }

                }

            });



            $grid->filter(function($filter){
                //$filter->equal('order_num','交货单号');
                $filter -> expand();
                $filter->where(function ($query) {
                    $input = $this -> input;
                    $send_order_info = DB::table('send_order_list') -> where([
                        'order_num' => trim($input)
                    ]) -> first();
                    if($send_order_info){
                        $query -> where('send_order_id',$send_order_info -> id);
                    }


                }, '交货单号');

                $filter->where(function ($query) {
                    $input = $this -> input;
                    $repertory_info = DB::table('repertory') -> where([
                        'numbers' => trim($input)
                    ]) -> first();
                    if($repertory_info){
                        //通过物流单号 找到物流单号 - 批次关系
                        $relation = DB::table('batch_repertory_relation') -> where([
                            'repertory_id' => $repertory_info -> id
                        ]) -> get();
                        if(count($relation)){
                            $batch_ids = [];
                            foreach($relation as $vo){
                                $batch_ids[] = $vo -> batch_id;
                            }
                            $query -> whereIn('id',$batch_ids);
                        }
                    }


                }, '到货物流编号');


            });


            $grid->disableCreateButton();
            $grid->disableExport();

            $grid->created_at('生成时间')->display(function($value){
                return date('Y-m-d H:i',$value);
            });



            $grid->actions(function ($actions)use($is_sendorder) {
                $id = $actions->getKey();
                $actions->disableDelete();
                if(!$is_sendorder){
                    //$actions->append('<a href="/admin/exportPici/'.$id.'"  target="_blank"  style="font-size:15px;margin-left:8px;">导出</a>');

                    $actions->append('<a href="/admin/orderPacket?batch_id='.$id.'"  target="_blank"  style="font-size:15px;margin-left:8px;">添加</a>');
                }else{
                    $actions->disableEdit();
                }





            });

            $grid->tools(function ($tools)use($is_sendorder) {
                $tools->batch(function ($batch)use($is_sendorder) {
                    $batch->disableDelete();

                });
                if($is_sendorder){
                    $tools->append(new SendOrderNum());
                }
            });

        });
    }


    public function exportPici($id){
        $batch_packages = DB::table('batch_packages') -> where('id','=',$id) -> first();

        $url_str = '?is_save=no';
        $package_info = $batch_packages -> package_ids;
        $package_info = explode(',',$package_info);
        foreach($package_info as $vo){
            $url_str .= '&check_arr[]='.$vo;
        }

        $url_str .= '&pici='.time();

        return redirect(admin_url('orderPacketExport').$url_str);
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form($id=0)
    {
        return Admin::form(BatchList::class, function (Form $form) use($id)  {
            $form->display('id', 'ID');
            $form->display('batch_num','批次号');

            $form->tools(function (Form\Tools $tools) {
                // 去掉跳转列表按钮
                $tools->disableListButton();
            });

            $form->disableSubmit();
            $form->disableReset();

            if($id){
                $headers = ['编号', '运单号', '收货人', '电话','地址','重量'];
                $batch_info = BatchList::find($id);

                //找所有包裹 需要到batch_packages_relation
                $packages = BatchPackagesRelation::where([
                    'batch_id' => $id
                ]) -> get();
                $package_ids = [];
                foreach($packages as $vo){
                    $package_ids[]  = $vo -> package_id;
                }



                //dd($batch_info);
                $packages = DB::table('packages') -> whereIn('id',$package_ids) -> get();

                foreach($packages as $vo){
                    $rows[] = [
                        $vo -> package_num,
                        $vo -> wuliu_num,
                        $vo -> name,
                        $vo -> tel,
                        $vo -> address,
                        $vo -> weight
                    ];
                }

                $table = new Table($headers, $rows);
                $form -> html($table) -> setWidth('12');
            }


            $form->display('created_at', '创建时间')->with(function($value){
                return date('Y-m-d H:i',$value);
            });
        });
    }



    //创建发货单
    public function makeSendOrder(){
        return Admin::content(function (Content $content) {

            $content->header('创建发货单');
            $content->description('');

            $content->body($this->grid(true));
        });
    }

    //创建发货单结果
    public function makeSendOrderRes(){
        //var_dump($_POST['check_arr']);exit;
        $isset = DB::table('send_order_list') -> where([
            'order_num' => trim($_POST['order_num'])
        ]) -> first();
        if($isset){
            echo 'isset';exit;
        }



        //计算下包裹数量
        //有了托盘id 看 包裹数量
        $count_packages = 0;
        $model = new BatchList();
        foreach($_POST['check_arr'] as $vo){
            $count_packages += $model -> getPackagesByPici($vo);
        }



        $send_order_id = DB::table('send_order_list') -> insertGetId([
            'order_num' => trim($_POST['order_num']),
            'created_at' => time(),
            'updated_at' => time(),
            'batch_num' => count($_POST['check_arr']), //托盘数量
            'package_num' =>$count_packages //包裹数量
        ]);

        /*




        $isset = DB::table('batch_packages') -> where([
            'order_num' => trim($_POST['order_num'])
        ]) -> first();
        if($isset){
            echo 'isset';exit;
        }
        */

        $batch_repertory_relation = new BatchRepertoryRelation();

        foreach($_POST['check_arr'] as $vo){
            DB::table('batch_packages') -> where([
                'id' => $vo
            ])  -> update([
                'send_order_id' => $send_order_id
                //'order_num' => trim($_POST['order_num'])
            ]);



        }
        foreach($_POST['check_arr'] as $vo){
            //看下这个托盘 属于哪个物流单号，将其变为出货中
            $batch_repertory_relation -> updateRepertoryStatus($vo);
        }


        echo 'success';
    }

    //交货列表
    public function sendOrderList(){
        return Admin::content(function (Content $content) {

            $content->header('交货单列表');
            $content->description('');

            $content->body($this->grid_order());
        });
    }


    protected function grid_order()
    {
        return Admin::grid(SendOrder::class, function (Grid $grid) {
            $grid->paginate(50);
            $grid->model() -> orderBy('id','desc');

            $grid->id('ID')->sortable();
            $grid->order_num('交货单号');
            $grid->batch_num('托盘数量');
            $grid->package_num('包裹数量');
            $grid->pass_num('过机包裹数量');
            $grid -> column('过机率','过机率') -> display(function($value){
                $info = SendOrder::find($this -> id);
                return (round(intval($info -> pass_num) / intval($info -> package_num),2) * 100) . '%';
            });



            $grid->filter(function($filter){
                $filter->equal('order_num','交货单号');
            });


            $grid->disableCreateButton();
            $grid->disableExport();

            $grid->created_at('生成时间')->display(function($value){
                return date('Y-m-d H:i',$value);
            });



            $grid->actions(function ($actions) {
                $id = $actions->getKey();
                $data = SendOrder::find($id);

                $actions->disableDelete();
                $actions->disableEdit();
                //添加操作
                $actions->append('<a href="/admin/sendOrderDetail/'.$id.'" style="margin-left:8px;font-size:15px;">明细</a>');
                $actions->append('<a href="/admin/exportOrderDetail/'.$id.'"  target="_blank" style="margin-left:12px;font-size:15px;">导出</a>');

                if($data -> package_num != $data -> pass_num){
                    // 查看未过机
                    $actions->append(new NoPassPackage($id));
                }

                /*
                //update by 20181119 22:30  只有nn100的 需要同步这个按钮
                if(!$data -> is_trackingMore ) {
                    //交货单下 找一个包裹
                    //交货单下 找一个批次先
                    $batch_info = DB::table('send_order_list')->where([
                        'id' => $id
                    ])->first();
                    if ($batch_info) {
                        //找批次-包裹的对应关系 一条就行
                        $relations = DB::table('batch_packages_relation')->where([
                            'batch_id' => $batch_info->id
                        ])->first();
                        if ($relations) {
                            $package_info = DB::table('packages')->where([
                                'id' => $relations->package_id
                            ])->first();
                            if ($package_info && ($package_info->route_id == 4 || $package_info->route_id == 6)) {
                                $actions->append(new TrackingMore($id));
                            }
                        }
                    }
                }
                */
                /*
                if(!$data -> is_trackingMore ){
                    $actions->append(new TrackingMore($id));
                }
                */

            });

            $grid->tools(function ($tools) {
                $tools->batch(function ($batch) {
                    $batch->disableDelete();

                });
            });

        });
    }


    //通过交货单id 查看未过机数据
    public function noPassPackage(){
        $batchs = BatchList::where('send_order_id',$_GET['send_order_id']) -> get();
        if(count($batchs)){
            //查处所有
            $batch_ids = [];
            foreach($batchs as $vo){
                $batch_ids[] = $vo -> id;
            }

            $relations = BatchPackagesRelation::whereIn('batch_id',$batch_ids) -> get();
            $package_ids = [];
            foreach($relations as $vo){
                $package_ids[] = $vo -> package_id;
            }

            $no_pass_data = Package::whereIn('id',$package_ids) -> where('pass_mark',0) -> get();
            $this -> nopassGrid($no_pass_data);exit;
            return Admin::content(function (Content $content) use($no_pass_data) {

                $content->header('未过机包裹');
                $content->description('');

                $content->body($this->nopassGrid($no_pass_data));
            });


        }
    }

    public function nopassGrid($no_pass_data){
        $headers = ['运单号'];

        foreach($no_pass_data as $k => $vo){
            $rows[] = [
                $vo -> wuliu_num
            ];
        }


        $table = new Table($headers, $rows);

        echo $table->render();
    }

    //将交货单数据 同步到trackingMore里
    public function trackingMoreData(){

        //发起异步请求
        $model = new Order();
        $res = $model -> asyPost(url('asy/trackingMore'),[
            'id'=>$_POST['id'],
            'sign' => 's1d1f2g3h4k5'
        ]);
        //Log::info(print_r($res,true));
        //发送成功后，修改值
        DB::table('send_order_list') -> where([
            'id'=>$_POST['id'],
        ]) -> update([
            'updated_at' => time(),
            'is_trackingMore' => 1
        ]);
        echo 'success';

    }

    //导出
    public function exportOrderDetail($id){
        $batch_list = DB::table('batch_packages') -> where([
            'send_order_id' => $id
        ]) -> get();

        $packages_ids = [];
        $cellData[] = [
            '序号',
            '包裹编号',
            '运单号',
            '姓名',
            '电话',
            '重量',
        ];


        //发货单下所有批次
        foreach($batch_list as $vo){
            //通过批次 查找所有包裹

            //找所有包裹 需要到batch_packages_relation
            $packages = BatchPackagesRelation::where([
                'batch_id' => $vo -> id
            ]) -> get();
            foreach($packages as $value){
                $packages_ids[]  = $value -> package_id;
            }
        }


        $packages_ids = array_unique($packages_ids);
        foreach($packages_ids as $k => $vo){
            $temp = DB::table('packages') -> where([
                'id' => $vo
            ]) -> first();

            $cellData[] = [
                $k+1,
                $temp -> package_num,
                $temp -> wuliu_num,
                $temp -> name,
                $temp -> tel,
                $temp -> weight,
            ];
        }

        //dd($cellData);


        Excel::create(date('Y-m-d-H-i').'导出交货单',function($excel) use ($cellData){
            $excel->sheet('order', function($sheet) use ($cellData){
                $sheet->rows($cellData);
                for ($i = 1; $i <= count($cellData[0]) + 1; $i++) {
                    $sheet->setWidth($this->cellLetter[$i - 1], 30);
                }
            });
        })->export('xlsx');
        //dd($packages);
    }

    public function sendOrderDetail($id){
        return Admin::content(function (Content $content)use($id) {

            $content->header('交货单详情');
            $content->description('');

            $content->body($this->jiaohuoform($id));
        });
    }

    public function jiaohuoform($id){
        return Admin::form(SendOrder::class, function (Form $form) use($id) {
            //$order_info = SendOrder::find($id);
            //dd($order_info);
            //寻找这个发货单的批次
            $batch_list = DB::table('batch_packages') -> where([
                'send_order_id' => $id
            ]) -> get();

            $packages_ids = [];
            $packages = [];


            //发货单下所有批次
            foreach($batch_list as $vo){
                //通过批次 查找所有包裹

                //找所有包裹 需要到batch_packages_relation
                $packages = BatchPackagesRelation::where([
                    'batch_id' => $vo -> id
                ]) -> get();
                foreach($packages as $value){
                    $packages_ids[]  = $value -> package_id;
                }
            }







            foreach($packages_ids as $vo){
                $packages_res[] = DB::table('packages') -> where([
                    'id' => $vo
                ]) -> first();
            }


            //表编辑
            $view_table = view('admin.send_order_table',compact('packages_res')) -> render();
            $form -> html($view_table)->setWidth(12);
            $form->tools(function (Form\Tools $tools) {

                // 去掉返回按钮
                //$tools->disableBackButton();

                // 去掉跳转列表按钮
                $tools->disableListButton();
                //$tools->add('<a class="btn btn-sm btn-danger" href="/admin/sendOrderList" style="font-size:13px;" > << 返回</a>');
            });
            $form->disableSubmit();
            $form->disableReset();


        });
    }

}
