<?php

namespace App\Admin\Controllers;

use App\Admin\Extensions\BatchScreen;
use App\Admin\Extensions\MakeMpPackageAreaScan;
use App\Admin\Extensions\WriteRepertory;
use App\AreaScanOrder;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class AreaScanOrderController extends Controller
{
    use HasResourceActions;

    protected $cellLetter = [
        'A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q',
        'R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD',
        'AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN',
        'AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ'
    ];

    /**
     * Index interface.
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        return $content
            ->header('区域发货单')
            ->description('')
            ->body($this->grid());
    }

    public function scanMakeAreaScanOrder(Content $content)
    {
        return $this->scan_grid();
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



    protected function scan_grid()
    {
        $text = '';
        if(isset($_GET) && isset($_GET['texts'])){
            $text = explode("\r\n",$_GET['texts']);
        }
        $get = $_GET;

        return Admin::content(function (Content $content) use($text,$get)  {

            $content->header('扫描生成交货单');
            //$content->description('');
            $packages = [];
            //判断用户的区域
            $admin_user_name = Admin::user() -> username;
            $from_area = Admin::user() -> from_area;
            $mp_temp_package_numbers = [];
            $hide_input = [];
            if($text){
                $mp_temp_package_numbers = DB::table('mp_temp_package_number')
                    -> leftJoin('mp_users','mp_temp_package_number.user_id','mp_users.id')
                    -> whereIn('package_num',$text)
                    -> orWhere(function($query)use($get){
                        if(isset($get['hide_input']) && $get['hide_input']){
                            $query -> whereIn('mp_temp_package_number.id',explode(',',$_GET['hide_input']));
                        }
                    })
                    -> where([
                        'mp_temp_package_number.area_id' => $from_area,
                        'mp_temp_package_number.send_order_id' => 0,
                        'mp_temp_package_number.flag' => 0,
                    ])
                    -> select([
                        'mp_temp_package_number.*',
                        'mp_users.nickname'
                    ])
                    -> get();
                if($mp_temp_package_numbers){
                    foreach($mp_temp_package_numbers as $vo){
                        $hide_input[] = $vo -> id;
                    }
                }
            }

            if(count($hide_input)){
                if(isset($_GET['del_id']) && $_GET['del_id']){
                    foreach($hide_input as $k => $vo){
                        if($vo == $_GET['del_id']){
                            unset($hide_input[$k]);
                        }
                    }
                    $hide_input = array_values($hide_input);
                    //return redirect(admin_url('scanMakeAreaScanOrder').'?hide_input='.implode(',',$hide_input));

                }
                $hide_input = implode(',',$hide_input);
            }else{
                $hide_input = '';
            }

            //compact('packages','batch_info')
            $view = view('admin.scan_area_order',[
                'packages' => $mp_temp_package_numbers,
                'hide_input' => $hide_input
            ]) -> render();
            $content->body($view);
        });
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new AreaScanOrder);

        //admin 可以看所有的
        $admin_user_name = Admin::user() -> username;
        $from_area = Admin::user()->from_area;

        if(!strstr($admin_user_name,'admin')){
            $grid -> model() -> where('area_id',$from_area);
        }

        if(isset($_GET['packages_nums']) && $_GET['packages_nums']){

            $packages_nums = explode("\r\n",$_GET['packages_nums']);
            foreach($packages_nums as $k => $vo){
                $packages_nums[$k] = trim($vo);
            }

            $scan_goods_info = DB::table('mp_temp_package_number')
                -> leftJoin('area_scan_order_info','mp_temp_package_number.id','area_scan_order_info.mp_number_id')
                -> leftJoin('area_scan_order','area_scan_order_info.order_id','area_scan_order.id')
                -> whereIn('mp_temp_package_number.package_num',$packages_nums)
                -> select([
                    'mp_temp_package_number.package_num',
                    'area_scan_order.id'
                ])
                -> get();

            if($scan_goods_info){
                //dd($scan_goods_info);
                $ids = [];
                foreach($scan_goods_info as $vo){
                    $ids[] = $vo -> id;
                }
                $grid -> model() -> whereIn('id',$ids);
            }




            //dd($_GET['packages_nums']);
        }

        $grid -> model() -> orderBy('id','desc');

        $grid->expandFilter();
        $grid->filter(function($filter){

            $filter ->  where(function($query){
                $query -> where('order_num','like','%'.trim($this->input).'%');
            },'单号');

            /*
            $filter ->  where(function($query){
                $input = trim($this->input);
                //通过包裹编号 搜索包裹id
                $scan_goods_info = DB::table('mp_temp_package_number')
                    -> leftJoin('area_scan_order_info','mp_temp_package_number.id','area_scan_order_info.mp_number_id')
                    -> where('mp_temp_package_number.package_num',$input)
                    -> select([
                        'mp_temp_package_number.package_num',
                    ])
                    -> first();
                if($package_info){

                }

            },'包裹编号');
            */



        });



        $grid -> model() -> where('flag',0);
        $grid->id('Id');
        $grid->order_num('发件编号');
        $grid->area_id('区域') -> display(function($val){
            if($val){
                $area_name = DB::table('area_name') -> where([
                    'id' => $val
                ]) -> first();
                return $area_name -> area_name;
            }
        });

        //物流单号
        $grid -> column('物流单号','物流单号') -> display(function($val){
            if($this -> repertory_id){
                //找到物流信息
                $repertory_info = DB::table('repertory')
                    -> where([
                        'id' => $this -> repertory_id
                    ]) -> first();
                if($repertory_info){
                    return $repertory_info -> numbers;
                }
            }
        });


        $grid->count_package('包裹数量')-> display(function($val){
            return "<a>".$val."</a>";
        });
        $grid->created_at('创建时间') -> display(function($val){
            return date('Y-m-d H:i',$val);
        });
        $grid->disableCreateButton();
        $grid->disableExport();
        $grid->tools(function ($tools) {
            $tools->batch(function ($batch) {
                $batch->disableDelete();
                $batch->add('维护物流',new WriteRepertory());
            });

            //批量筛选按钮
            $tools -> append(new BatchScreen(4));
        });

        $grid->actions(function ($actions) {
            $id = $actions->getKey();

            $actions->disableDelete();
            $actions->disableEdit();
            //添加操作
            $actions->append('<a href="/admin/areaSendOrderId?id='.$id.'" style="margin-left:8px;font-size:15px;cursor:pointer;">单号</a>');
            $actions->append('<a href="/admin/areaSendOrderDetail?id='.$id.'" style="margin-left:8px;font-size:15px;cursor:pointer;">明细</a>');
            $actions->append('<a href="/admin/exportAreaOrderDetail?id='.$id.'"  target="_blank" style="margin-left:12px;font-size:15px;cursor:pointer;">导出明细</a>');
            $url = "'".'/admin/deleteAreaOrderDetail?id='.$id."'";
            $actions->append('<a onclick="if(confirm('."'确定要删除么'".')){location.href='.$url.'}"  style="margin-left:12px;font-size:15px;cursor:pointer;">删除发货单</a>');

            $actions->append('<a href="/admin/areaScanSelect?id='.$id.'" style="margin-left:12px;font-size:15px;cursor:pointer;">选择商品</a>');


            //$actions->append(new WriteRepertory($id));
            $actions->append(new MakeMpPackageAreaScan($id));

            //href="/admin/deleteAreaOrderDetail?id='.$id.'"  target="_blank"

        });

        return $grid;
    }


    //填写物流信息页面
    public function writeRepertoryPage(){
        $ids_str = explode(',',$_GET['ids_str']);

        //判断这些 area_scan_order 是否是一个区域de
        $area_id_temp = 0;
        $error = 0;
        foreach($ids_str as $vo){
            $temp_order_info = DB::table('area_scan_order')
                -> where([
                    'id' => $vo
                ]) -> first();
            if($area_id_temp && $area_id_temp != $temp_order_info -> area_id){
                //不是同一个区域
                $error = 1;
            }else{
                $area_id_temp = $temp_order_info -> area_id;
            }
        }


        $username = Admin::user()->username;
        $repertorys = [];
        if(strstr($username,'admin')){
            //如果是admin 开头的 则 允许选 物流单号
            $repertorys = DB::table('repertory') -> where([
                'flag' => 0
            ])
                -> orderBy('id','desc')
                -> get();
        }

        return   view('admin.writeRepertoryPage') -> with([
            'company' => config('admin.repertory_company'),
            'error' => $error,
            'repertorys' => $repertorys
        ]);
    }

    //物流信息提交
    public function writeRepertoryRes(){
        $ids_arr = explode(',',$_POST['ids_str']);
        $company = $_POST['company'];
        $company_number = $_POST['company_number'];
        $repertorys = $_POST['repertorys'];
        $area_id = 0;

        //统计此交货单 有多少包裹
        $count_package = 0;
        foreach($ids_arr as $vo){
            //校验
            $area_scan_order = DB::table('area_scan_order') -> where([
                'id' => $vo,
                'flag' => 0
            ]) -> first();
            if(!$area_scan_order ||  $area_scan_order -> repertory_id){
                return [
                    'code' => 500,
                    'msg' => '没有此编号或者已经有物流信息'
                ];
            }
            $area_id = $area_scan_order -> area_id;

            $mp_count = DB::table('mp_temp_package_number')
                -> where([
                    'send_order_id' => $vo,
                    'flag' => 0
                ]) -> count();

            $count_package += $mp_count;

        }

        //查找此区域 绑定的用户id
        $area_info = DB::table('area_name')
            -> where([
                'id' => $area_id
            ]) -> first();
        if(!$area_info -> mp_bind_user_id){
            return [
                'code' => 500,
                'msg' => '此区域没有绑定用户'
            ];
        }




        if(!$repertorys){
            //提交到 出入库管理 未分配
            $repertory_id = DB::table('repertory') -> insertGetId([
                'user_id' => $area_info -> mp_bind_user_id,
                'created_at' => time(),
                'updated_at' => time(),
                'admin_user_name' => 'admin',
                'is_check' => 1,
                'sub_type' => 4,
                'status' => 6,
                'numbers' => $company_number,
                'company'=>$company,
                //包裹状态 已打包
                'package_status' => 1,
                //打包数量
                'dabao_num' =>$count_package,
            ]);
        }else{
            $repertory_id = $repertorys;

            $area_scan_order = DB::table('area_scan_order')
                -> where([
                    'repertory_id' => $repertory_id
                ]) -> first();
            if($area_scan_order){
                return [
                    'code' => 500,
                    'msg' => '此物流单已经被绑定'
                ];
            }



        }



        foreach($ids_arr as $vo){
            DB::table('area_scan_order')
                -> where([
                    'id' => $vo
                ]) -> update([
                    'repertory_id' => $repertory_id
                ]);
        }

        return [
            'code' => 200,
            'msg' => '提交成功'
        ];

    }

    public function areaScanSelect(){
        return Admin::content(function (Content $content) {

            $content->header('选择商品');
            $content->description('');

            //查此发货单id 下的 mp_temp_number
            $area_scan_order_info = DB::table('area_scan_order_info') -> where([
                'flag' => 0,
                'order_id' => $_GET['id']
            ]) -> get();
            $mp_number_ids = [];
            foreach($area_scan_order_info as $vo){
                $mp_number_ids[] = $vo -> mp_number_id;
            }


            //每些包裹下 找他的内件
            $scan_info = DB::table('mp_scan_goods_info')
                -> whereIn('package_id',$mp_number_ids)
                -> where('jap_name','=','')
                -> where('flag',0)
                -> get();


            //找每个内件的日文名字
            $temp = [];
            foreach($scan_info as $k => $vo){
                //爬虫的信息
                $reptile_info = DB::table('reptile_product_list')
                    -> where([
                        'product_no' => $vo -> product_no
                    ]) -> limit(6) ->orderBy('id','desc') ->  get();

                $temp[$k]['reptile_info'] = $reptile_info;
                $temp[$k]['scan_info'] = $vo;

            }

            $info = [
                'info' => $temp
            ];
            //dd($info);
            $view = view('admin.areaScanSelect',$info) -> render();
            $content->body($view);
        });
    }

    public function areaScanSelectRes(){

        //dd($_POST);
        unset($_POST['_token']);

        foreach($_POST as $k => $vo){
            $reptile_info = DB::table('reptile_product_list')
                -> where([
                    'id' => $vo
                ]) -> first();

            DB::table('mp_scan_goods_info')
                -> where([
                    'id' => $k
                ]) -> update([
                    'jap_name' => $reptile_info -> jap_name,
                    'jap_price' => $reptile_info -> price,
                    'zh_name' => $reptile_info -> zh_name,
                    'en_name' => $reptile_info -> en_name,
                ]);
        }

        return redirect(admin_url('AreaScanOrder'));

    }




    public function deleteAreaOrderDetail(){

        //先检验下 此发货单 是否生成了物流订单 如果生成了有效的 则不允许删除
        $scan_order = DB::table('area_scan_order') -> where([
            'id' => $_GET['id']
        ]) -> first();
        if($scan_order -> repertory_id){
            $repertory_info = DB::table('repertory') -> where([
                'id' => $scan_order -> repertory_id,
                'flag' => 0
            ]) -> first();
            if($repertory_info){
                echo '已存在到货物流订单，不允许删除';exit;
            }
        }




        DB::table('area_scan_order') -> where([
            'id' => $_GET['id']
        ]) -> update([
            'flag' => 1
        ]);

        $info = DB::table('area_scan_order_info') -> where([
            'order_id' => $_GET['id']
        ]) -> get();

        foreach($info as $vo){
            DB::table('area_scan_order_info')
                -> where([
                    'id' => $vo -> id
                ]) -> update([
                    'flag' => 1
                ]);

            DB::table('mp_temp_package_number')
                -> where([
                    'id' => $vo -> mp_number_id
                ]) -> update([
                    'send_order_id' => 0
                ]);
        }

        return redirect(admin_url('AreaScanOrder'));

    }


    //导出明细
    public function exportAreaOrderDetail(){
        $scan_goods_info = DB::table('mp_scan_goods_info')
            -> leftJoin('mp_temp_package_number','mp_scan_goods_info.package_id','mp_temp_package_number.id')
            -> leftJoin('area_scan_order_info','mp_temp_package_number.id','area_scan_order_info.mp_number_id')
            -> leftJoin('area_name','mp_temp_package_number.area_id','area_name.id')
            -> leftJoin('mp_users','mp_temp_package_number.user_id','mp_users.id')
            -> where([
                'area_scan_order_info.order_id' => $_GET['id'],
                'mp_scan_goods_info.flag' => 0
            ])
            -> select([
                'mp_scan_goods_info.product_no',
                'mp_scan_goods_info.product_name',
                'mp_scan_goods_info.product_method',
                'mp_scan_goods_info.product_format',
                'mp_scan_goods_info.product_unit',
                'mp_scan_goods_info.declare_price',
                'mp_scan_goods_info.taobao_url',
                'mp_scan_goods_info.goods_number',
                'mp_temp_package_number.package_num',
                'area_name.area_name',
                'mp_users.nickname',
                'mp_temp_package_number.province',
                'mp_temp_package_number.city',
                'mp_temp_package_number.country',
                'mp_temp_package_number.address',
                'mp_temp_package_number.name',
                'mp_temp_package_number.tel',
                'mp_temp_package_number.card',
                'mp_temp_package_number.mode_id',
                'mp_temp_package_number.remark',
                'mp_temp_package_number.weight',
                'mp_scan_goods_info.english_name',
                'mp_scan_goods_info.brand_name',
                'mp_scan_goods_info.en_name',
                'mp_scan_goods_info.jap_name',
                'mp_scan_goods_info.jap_price',
            ])
            -> get();
        $cellData[] = [
            '商品编码',
            '商品名称',
            '包装方式',
            '商品规格',
            '商品单位',
            '英文名称',
            '品牌',
            '申报价值',
            '淘宝链接',
            '个数',
            '包裹编号',
            '区域名称',
            '下单人',
            '省',
            '市',
            '区',
            '地址',
            '姓名',
            '电话',
            '身份证',
            '发货方式',
            '备注',
            '重量',
            '英文名称',
            '日文名称',
            '日本价格',
        ];
        foreach($scan_goods_info as $vo){
            $mode = config('admin.mp_mode');
            $cellData[] = [
                (string)$vo -> product_no,
                (string)$vo -> product_name,
                (string)$vo -> product_method,
                (string)$vo -> product_format,
                (string)$vo -> product_unit,
                (string)$vo -> english_name,
                (string)$vo -> brand_name,
                (string)$vo -> declare_price,
                (string)$vo -> taobao_url,
                (string)$vo -> goods_number,

                (string)$vo -> package_num,
                (string)$vo -> area_name,
                (string)$vo -> nickname,
                (string)$vo -> province,
                (string)$vo -> city,
                (string)$vo -> country,
                (string)$vo -> address,
                (string)$vo -> name,
                (string)$vo -> tel,
                $vo -> card."\t",
                isset($mode[$vo -> mode_id])?$mode[$vo -> mode_id]:'',
                (string)$vo -> remark,
                (string)$vo -> weight,
                (string)$vo -> en_name,
                (string)$vo -> jap_name,
                (string)$vo -> jap_price,

            ];
        }



        Excel::create(date('Y-m-d-H-i').'导出交货单',function($excel) use ($cellData){
            $excel->sheet('order', function($sheet) use ($cellData){
                $sheet->rows($cellData);
                for ($i = 1; $i <= count($cellData[0]) + 1; $i++) {
                    $sheet->setWidth($this->cellLetter[$i - 1], 30);
                }
            });
        })->export('xlsx');
    }


    public function areaSendOrderDetail(){
        return Admin::content(function (Content $content){

            $content->header('发货单详情');
            $content->description('');

            $content->body($this->orderdetail($_GET['id']));
        });
    }

    public function areaSendOrderId(){
        return Admin::content(function (Content $content){

            $content->header('包裹单号');
            $content->description('');

            $content->body($this->orderid($_GET['id']));
        });
    }

    public function orderid($id){
        return Admin::form(AreaScanOrder::class, function (Form $form) use($id) {


            $scan_goods_info = DB::table('mp_temp_package_number')
                -> leftJoin('area_scan_order_info','mp_temp_package_number.id','area_scan_order_info.mp_number_id')
                -> where([
                    'area_scan_order_info.order_id' => $id,
                ])
                -> select([
                    'mp_temp_package_number.package_num',
                ])
                -> get();

            //dd($scan_goods_info);

            //表编辑
            $view_table = view('admin.area_send_order_id_table',compact('scan_goods_info')) -> render();
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



    public function orderdetail($id){
        return Admin::form(AreaScanOrder::class, function (Form $form) use($id) {


            $scan_goods_info = DB::table('mp_scan_goods_info')
                -> leftJoin('mp_temp_package_number','mp_scan_goods_info.package_id','mp_temp_package_number.id')
                -> leftJoin('area_scan_order_info','mp_temp_package_number.id','area_scan_order_info.mp_number_id')
                -> leftJoin('area_name','mp_temp_package_number.area_id','area_name.id')
                -> leftJoin('mp_users','mp_temp_package_number.user_id','mp_users.id')
                -> where([
                    'area_scan_order_info.order_id' => $id,
                    'mp_scan_goods_info.flag' => 0
                ])
                -> select([
                    'mp_scan_goods_info.*',
                    'mp_temp_package_number.package_num',
                    'area_name.area_name',
                    'mp_users.nickname'
                ])
                -> get();

            //dd($scan_goods_info);

            //表编辑
            $view_table = view('admin.area_send_order_table',compact('scan_goods_info')) -> render();
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

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(AreaScanOrder::findOrFail($id));

        $show->id('Id');
        $show->order_num('Order num');
        $show->created_at('Created at');
        $show->updated_at('Updated at');
        $show->flag('Flag');

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new AreaScanOrder);

        $form->text('order_num', 'Order num');
        $form->number('flag', 'Flag');

        return $form;
    }
}
