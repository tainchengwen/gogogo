<?php

namespace App\Admin\Controllers;

use App\Admin\Extensions\DeleteMpOrder;
use App\Admin\Extensions\importMpNumber;
use App\Admin\Extensions\MakeMpNumber;
use App\Admin\Extensions\MakeMpPackageOrder;
use App\Admin\Extensions\MakePackageOrder;
use App\Admin\Extensions\MpAreaOrder;
use App\Admin\Extensions\MpExcelExpoter;
use App\Admin\Extensions\PdfApi;
use App\Admin\Extensions\PrintMpPdf;
use App\Admin\Extensions\PrintPdf;
use App\AreaName;
use App\Jobs\Reptile;
use App\MpPackageNumber;
use App\Http\Controllers\Controller;
use App\MpScanInfo;
use App\MpUser;
use App\Order;
use App\PrintSequeue;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Maatwebsite\Excel\Facades\Excel;

class MpPackageNumberController extends Controller
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
            ->header('小程序订单')
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
            ->header('编辑')
            ->description('')
            ->body($this->form($id)->edit($id));
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
        return Admin::grid(MpPackageNumber::class, function (Grid $grid) {
            $from_area = Admin::user()->from_area;
            $admin_user_name = Admin::user() -> username;
            $grid -> model() -> orderBy('updated_at','desc');
            $grid -> model() -> orderBy('user_id','desc');
            $grid -> model() -> where('is_scan',1);
            $grid -> model() -> where('order_type',0);


            if(!strstr($admin_user_name,'admin')){
                $grid -> model() -> where('area_id',$from_area);
            }
            $grid -> model() -> where('flag',0);


            $grid->expandFilter();
            $grid->filter(function($filter)use($admin_user_name){

                $filter->scope('order_scan', '已扫描未发货')->where('user_id','>', 0) ->where('send_order_id','=',0);



                $filter ->  where(function($query){
                    if($this->input == 1){
                        $query -> where('order_id','>',0);
                    }else{
                        $query -> where('order_id','=',0);
                    }
                },'是否下单') -> select([
                    '1' => '已下单',
                    '2' => '未下单'
                ]);

                //发货方式
                $filter ->  where(function($query){
                    $query -> where('mode_id',$this->input);
                },'发货方式') -> select(config('admin.mp_mode'));

                $filter ->  where(function($query){
                    $query -> where('package_num','like','%'.trim($this->input).'%');
                },'包裹编号');

                $filter ->  where(function($query){
                    $orders = DB::table('order') -> where('order_num','like','%'.trim($this->input).'%') -> get();
                    if(count($orders)){
                        $order_ids = [];
                        foreach($orders as $vo){
                            $order_ids[] = $vo -> id;
                        }
                    }
                    $query -> where('order_id','in',$order_ids);
                },'订单编号');

                $filter ->  where(function($query){
                    $user = DB::table('mp_users') -> where('nickname','like','%'.trim($this->input).'%') -> get();
                    if(count($user)){
                        $temp_user_ids = [];
                        foreach($user as $vo){
                            $temp_user_ids[] = $vo -> id;
                        }
                        $query -> whereIn('user_id',$temp_user_ids);
                    }

                },'客户');

                $filter ->  where(function($query){
                    $query -> where('remark','like','%'.trim($this->input).'%');
                },'备注');

                $filter ->  where(function($query){
                    $query -> where('name','like','%'.trim($this->input).'%');
                },'姓名');

                $filter ->  where(function($query){
                    $query -> where('tel','like','%'.trim($this->input).'%');
                },'电话');

                $filter->where(function ($query) {
                    $input = strtotime($this->input);
                    $query->where('created_at', '>=', "{$input}");

                }, '日期大于')->date();

                $filter->where(function ($query) {
                    $input = strtotime($this->input);
                    $query->where('created_at', '<=', "{$input}");

                }, '日期小于')->date();

                if(strstr($admin_user_name,'admin')) {
                    $area_names = DB::table('area_name') -> get();
                    foreach($area_names as $vo){
                        $area_setting[$vo -> id] = $vo -> area_name;
                    }
                    $filter->where(function ($query) {
                        $input = $this->input;
                        $query->where('area_id', '=', "{$input}");
                    }, '区域')->select($area_setting);
                }
                //$filter->between('created_at', '创建时间')->datetime();


            });
            //$grid -> model() -> where('user_id','>',0);
            $grid->id('Id');
            $grid->package_num('包裹编号');
            $grid->area_id('区域') -> display(function($val){
                if($val){
                    $area_name = AreaName::find($val);
                    return $area_name -> area_name;
                }
            });

            $grid->user_id('昵称') -> display(function($val){
                if($val){
                    $mp_user = MpUser::find($val);
                    return $mp_user -> nickname;
                }
            });

            //$grid->status('Status');


            /*
            $grid->province('省');
            $grid->city('市');
            $grid->country('区');
            $grid->address('地址');
            $grid->name('姓名');
            $grid->tel('电话');
            $grid->card('身份证');
            */
            $grid->mode_id('发货方式') -> display(function($val){
                if($val){
                    $arr = config('admin.mp_mode');
                    return $arr[$val];
                }

            });
            $grid->remark('备注');
            $grid -> order_id('订单单号') -> display(function($val){
                if($val){
                    $order_info = DB::table('order') -> where([
                        'id' => $val
                    ]) -> first();
                    if($order_info){
                        return $order_info -> order_num;
                    }

                }else{
                    return '';
                }
            });

            $grid -> order_status('是否支付') -> display(function($val){
                if($val){
                    return '已支付';
                }else{
                    return '未支付';
                }
            });
            $grid -> weight('重量');
            $grid -> province('省');
            $grid -> city('市');
            $grid -> country('区');
            $grid -> address('地址');
            $grid -> name('姓名');
            $grid -> tel('电话');
            $grid -> card('身份证');

            $grid -> send_order_id('是否发货')->display(function($val){
                if($val){
                    return '是';
                }
            });

            $grid->created_at('创建时间') -> display(function($val){
                return date('Y-m-d H:i:s',$val);
            });

            $grid->actions(function ($actions) {
                $actions->disableDelete();
            });



            $grid->tools(function ($tools)use($admin_user_name) {

                $tools->batch(function ($batch) {
                    $batch->disableDelete();
                    $batch->add('打印标签',new PrintMpPdf());
                    $batch->add('生成发货单',new MpAreaOrder());
                    $batch->add('删除',new DeleteMpOrder());
                    $batch->add('下单',new MakeMpPackageOrder());

                });


                $tools -> append(new MakeMpNumber());

                if(strstr($admin_user_name,'admin')){
                    $tools -> append(new importMpNumber());
                }

                //$tools->append(new PrintMpPdf());
            });

            $grid->exporter(new MpExcelExpoter());


            //$grid->disableExport();
            $grid->disableCreateButton();

            $grid->actions(function ($actions) {
                $id = $actions->getKey();
                //下单
                $info = MpPackageNumber::find($id);
                if(!$info -> order_id){
                    $actions->append(new MakePackageOrder($id));
                }
                $actions->disableDelete();
                //$actions->disableEdit();
            });



        });
    }


    public function importMpNumber(){
        return Admin::content(function (Content $content) {

            $content->header('小程序订单');
            $content->description('上传');

            $content->body($this->importMpNumberForm());
        });
    }

    public function importMpNumberForm(){
        return Admin::form(Order::class, function (Form $form){
            $form->file('file_column','小程序订单');

            $users = DB::table('mp_users') -> get([
                'id','nickname'
            ]);
            foreach ($users as $vo) {
                $user_arr[$vo->id] = $vo->id.'-'.$vo->nickname;
            }
            $form -> select('user_id','用户id') -> options($user_arr);


            $form->tools(function (Form\Tools $tools) {
                // 去掉跳转列表按钮
                $tools->disableListButton();
                $url = '"'.url('download_mp_order').'"';

                // 添加一个按钮, 参数可以是字符串, 或者实现了Renderable或Htmlable接口的对象实例
                $tools->add('<a class="btn btn-sm btn-danger" onclick=\'window.open('.$url.')\'><i class="fa fa-arrow-down"></i>&nbsp;&nbsp;下载模版</a>');
            });

            $form -> setAction(admin_base_path('importMpNumberRes'));


        });
    }

    public function importMpNumberRes(){
        $user_id = $_POST['user_id'];
        $this -> import($_FILES['file_column']['tmp_name'],$user_id);

        admin_toastr('导入成功');

        return redirect(admin_base_path('importMpNumber'));
    }

    public function import($filePath,$user_id){
        $from_area = Admin::user()->from_area;
        $username = Admin::user()->username;
        Excel::load($filePath, function($reader) use( &$res ) {
            $reader = $reader->getSheet(0);
            $res = $reader->toArray();
        });





        //dd($res);
        foreach($res as $k => $vo){
            if($k == 0){
                continue;
            }

            //品名1	税号1	数量1	单价1
            //8 9 10 11

            //品名2	税号2	数量2	单价2
            //12 13 14 15

            //品名3	税号3	数量3	单价3
            //16 17 18 19

            //加入 packages_goods_paratemer
            //申报
            if($vo[8] && $vo[9] && $vo[10] && $vo[11]){
                $s_content1 = trim($vo[8]);
                $Tax_code1 = trim($vo[9]);
                $s_price1 = trim($vo[11]);
                $s_pieces1 = trim($vo[10]);
            }else{
                continue;
            }

            $s_content2 = '';
            $Tax_code2 = '';
            $s_price2 = '';
            $s_pieces2 = '';

            $s_content3 = '';
            $Tax_code3 = '';
            $s_price3 = '';
            $s_pieces3 = '';


            if(isset($vo[12]) && isset($vo[13]) && isset($vo[14]) && isset($vo[15]) && $vo[12] && $vo[13] && $vo[14] && $vo[15]){
                $s_content2 = trim($vo[12]);
                $Tax_code2 = trim($vo[13]);
                $s_price2 = trim($vo[15]);
                $s_pieces2 = trim($vo[14]);
            }

            if(isset($vo[16]) && isset($vo[17]) && isset($vo[18]) && isset($vo[19]) && $vo[16] && $vo[17] && $vo[18] && $vo[19]){
                $s_content3 = trim($vo[16]);
                $Tax_code3 = trim($vo[17]);
                $s_price3 = trim($vo[19]);
                $s_pieces3 = trim($vo[18]);
            }

            $packages_goods_paratemer_id =  DB::table('packages_goods_paratemer') -> insertGetId([
                's_content1' => $s_content1,
                'Tax_code1' => $Tax_code1,
                's_price1' => $s_price1,
                's_pieces1' => $s_pieces1,

                's_content2' => $s_content2,
                'Tax_code2' => $Tax_code2,
                's_price2' => $s_price2,
                's_pieces2' => $s_pieces2,

                's_content3' => $s_content3,
                'Tax_code3' => $Tax_code3,
                's_price3' => $s_price3,
                's_pieces3' => $s_pieces3,

                'declare_currency' => 'RMB'
            ]);


            //判断 如果没有 $vo[0] 则 新增 mp_temp_package_number
            $mp_info = DB::table('mp_temp_package_number')
                -> where('package_num',trim($vo[0]))
                -> where([
                    'flag' => 0
                ]) -> first();
            if(!$mp_info){
                DB::table('mp_temp_package_number') -> insertGetId([
                    'package_num' => trim($vo[0]),
                    'user_id' => $user_id,
                    'area_id' => 5,
                    'weight' => trim($vo[1]),
                    'name' => trim($vo[2]),
                    'tel' => trim($vo[3]),
                    'address' => trim($vo[4]),
                    'province' => trim($vo[5]),
                    'city' => trim($vo[6]),
                    'country' => trim($vo[7]),
                    'declare_id' => $packages_goods_paratemer_id,
                    'is_scan' => 1,
                    'created_at' => time(),
                    'updated_at' => time(),
                ]);
            }else{
                DB::table('mp_temp_package_number')
                    -> where('package_num',trim($vo[0]))
                    -> where([
                        'flag' => 0
                    ])
                    -> update([
                        'user_id' => $user_id,
                        'weight' => trim($vo[1]),
                        'name' => trim($vo[2]),
                        'tel' => trim($vo[3]),
                        'address' => trim($vo[4]),
                        'province' => trim($vo[5]),
                        'city' => trim($vo[6]),
                        'country' => trim($vo[7]),
                        'declare_id' => $packages_goods_paratemer_id,
                        'is_scan' => 1
                    ]);
            }









        }



    }


    //批量小程序下单
    public function MakeMpPackageOrder(){
        $ids = $_POST['ids'];
        $temp = [];
        foreach($ids as $vo){
            $info = DB::table('mp_temp_package_number')
                -> where([
                    'id' => $vo,
                    'flag' => 0
                ]) -> first();

            if($info && !$info -> order_id){
            //if($info){
                $temp[] = $info;
            }
        }

        if(count($temp)){
            //批量下单页面
            return   view('admin.MakeMpPackageOrderPage') -> with([
                'data' => $temp
            ]);
        }else{
            echo '500';exit;
        }
    }


    //在区域发货单 批量小程序下单
    public function MakeMpPackageAreaOrder(){

        $info = DB::table('mp_temp_package_number')
            -> leftJoin('area_scan_order_info','mp_temp_package_number.id','area_scan_order_info.mp_number_id')
            -> where([
                'area_scan_order_info.order_id' => $_POST['area_scan_id'],
                'area_scan_order_info.flag' => 0
            ])
            -> select([
                'mp_temp_package_number.*'
            ])
            -> get();

        $temp = [];
        foreach($info as $k => $vo){
            if(!$vo -> order_id){
                $temp[] = $vo;
            }
        }

        if(count($temp)){
            //批量下单页面
            return   view('admin.MakeMpPackageOrderPage') -> with([
                'data' => $temp
            ]);
        }else{
            echo '500';exit;
        }



    }


    public function submitMpOrder(){
        $weights = explode(',',$_POST['weights']);
        $ids = explode(',',$_POST['names']);
        //下单
        foreach($ids as $k => $vo){
            $id = $vo;
            $weight = floatval($weights[$k]);
            $info = DB::table('mp_temp_package_number') -> where([
                'id' => $id
            ]) -> first();
            //如果下单了 就跳过
            if($info -> order_id){
                continue;
            }

            DB::table('mp_temp_package_number') -> where([
                'id' => $id
            ]) -> update([
                'weight' => $weight
            ]);

            $mp_user = DB::table('mp_users') -> where([
                'id' => $info -> user_id
            ]) -> first();

            $wxuser = DB::table('wxuser') -> where([
                'unionid' => $mp_user -> unionid
            ]) -> first();
            //dd($wxuser);

            $model = new Order();

            $info -> address = $info -> province.$info -> city.$info -> country.$info -> address;

            //自己申报税金
            $declare_ids = [];
            if($info -> declare_id){
                $declare_ids[] = $info -> declare_id;
            }

            $order_id = $model -> underOrder([
                'weights' => [$weight],
                'from_area' => $info -> area_id,
                'user_id' => $wxuser -> id,
                'names' => [$info -> name],
                'address' => [$info -> address],
                'provinces' => [$info -> province],
                'citys' => [$info -> city],
                'tels' => [$info -> tel],
                'uuid_names_arr' => [$this -> create_uuid()],
                'remark' => $info -> remark,
                'package_nums' => [$info -> package_num],
                'pay_status' => $_POST['pay'],
                'pay_type' => 9,
                'is_min_order' => 1,
                'declare_ids' => $declare_ids,
                'order_from' => 'min'
            ]);
            if($order_id){
                DB::table('order') -> where([
                    'id' => $order_id
                ]) -> update([
                    'mp_package_id' => $id
                ]);



                //更新
                DB::table('mp_temp_package_number') -> where([
                    'id' => $id
                ]) -> update([
                    'order_id' => $order_id,
                    'order_status' => 2,
                    'updated_at' => time(),

                ]);
            }


        }
    }



    //批量删除小程序订单
    public function DeleteMpOrder(){
        $ids = $_POST['ids'];
        foreach($ids as $vo){
            DB::table('mp_temp_package_number') -> where([
                'id' => $vo
            ]) -> update([
                'flag' => 1
            ]);

            DB::table('mp_scan_goods_info')
                -> where([
                    'package_id' => $vo
                ]) -> update([
                    'flag' => 1
                ]);
        }

        return [
            'code' => 200,
            'msg' => '删除成功'
        ];
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(MpPackageNumber::findOrFail($id));

        $show->id('Id');
        $show->package_num('Package num');
        $show->area_id('Area id');
        $show->is_scan('Is scan');
        $show->user_id('User id');
        $show->created_at('Created at');
        $show->updated_at('Updated at');
        $show->flag('Flag');
        $show->status('Status');
        $show->address_id('Address id');
        $show->province('Province');
        $show->city('City');
        $show->country('Country');
        $show->address('Address');
        $show->name('Name');
        $show->tel('Tel');
        $show->card('Card');
        $show->mode_id('Mode id');
        $show->remark('Remark');

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form($id = null)
    {
        $form = new Form(new MpPackageNumber);

        $form -> display('id','Id');
        $form->display('package_num', '箱子编号');
        $form->display('area_id', '区域') -> with(function($val){
            $area_name = AreaName::find($val);
            if($area_name){
                return $area_name -> area_name;
            }

        });

        $info = DB::table('mp_temp_package_number') -> where([
            'id' => $id
        ]) -> first();
        if(!$info -> order_id){
            //维护重量
            $form -> text('weight','重量') -> rules('required|min:1');
        }



        $form->display('user_id', '用户')->with(function($val){
            $users = MpUser::find($val);
            return $users -> nickname;
        });
        $form->text('province', '省');
        $form->text('city', '市');
        $form->text('country', '区');
        $form->text('address', '地址');
        $form->text('name', '姓名');
        $form->text('tel', '电话');
        $form->text('card', '身份证');

        $form -> select('mode_id','发货方式') -> options(config('admin.mp_mode'));

        /*

        $form->display('mode_id', '发货方式') -> with(function($val){
            if($val){
                $arr = config('admin.mp_mode');
                return $arr[$val];
            }

        });
        */
        $form->textarea('remark', '备注');




        $form ->display('id','存档照片') -> with(function($val){
            $photos = DB::table('mp_package_photos')
                -> where([
                    'flag' => 0,
                    'package_id' => $val
                ]) -> get();
            $html = '';
            if(count($photos)){
                foreach($photos as $vo){
                    $html .= '<img src="'.url('uploads/mp_photos').'/'.$vo -> image_url.'" 
                     style="width:120px;height:180px;float:left;" onclick="window.open('."'".url('uploads/mp_photos').'/'.$vo -> image_url."'".')"  />';
                }

            }
            return $html;

        });

        //货物清单
        $form -> display('货物清单','货物清单') -> with(function($val){
            $id = $this -> id;
            $scan_goods_info = DB::table('mp_scan_goods_info')
                -> where([
                    'package_id' => $id,
                    'flag' => 0
                ]) -> get();
            $html = '<style>td{border:1px solid #000;}</style><table style="width:100%;">';
            $html .= '<tr>
<th>编号</th>
<th>商品编码</th>
<th>商品名称</th>
<th>包装方式</th>

<th>英文名称</th>
<th>品牌</th>
<th>规格</th>
<th>单位</th>
<th>申报价格</th>
<th>淘宝链接</th>
<th>数量</th>
</tr>';

            $number_all = 0;
            foreach($scan_goods_info as $k => $vo){
                if($vo -> taobao_url){
                    $taobao = '<a href="'.$vo -> taobao_url.'" target="_blank">链接</a>';
                }else{
                    $taobao = '';
                }
                $html .= '<tr>';
                $html .= '<td>'.($k + 1).'</td>';
                $html .= '<td>'.$vo -> product_no.'</td>';
                $html .= '<td>'.$vo -> product_name.'</td>';
                $html .= '<td>'.$vo -> product_method.'</td>';
                $html .= '<td>'.$vo -> english_name.'</td>';
                $html .= '<td>'.$vo -> brand_name.'</td>';


                $html .= '<td>'.$vo -> product_format.'</td>';
                $html .= '<td>'.$vo -> product_unit.'</td>';
                $html .= '<td>'.$vo -> declare_price.'</td>';
                $html .= '<td>'.$taobao.'</td>';
                $html .= '<td>'.$vo -> goods_number.'</td>';

                $html .= '</tr>';
                $number_all += floatval($vo -> declare_price)*intval($vo -> goods_number);
            }

            $html .= '<tr><td colspan="8" style="height:10px;" ></td></tr>
<tr>
<td><b>总价</b></td>
<td colspan="7" style="font-size:18px;">'.$number_all.'</td>
</tr>';


            $html .= '</table>';
            return $html;

        });


        $form -> hidden('id','id');
        $info = DB::table('mp_temp_package_number') -> where([
            'id' => $id
        ]) -> first();
        /*
        if($info -> order_id){
            $form->disableSubmit();
        }
        */
        //$form->disableSubmit();
        $form->disableReset();
        $form->tools(function (Form\Tools $tools) {

            // 去掉`列表`按钮
            $tools->disableList();

            // 去掉`删除`按钮
            $tools->disableDelete();

            // 去掉`查看`按钮
            $tools->disableView();

            // 添加一个按钮, 参数可以是字符串, 或者实现了Renderable或Htmlable接口的对象实例
            //$tools->add('<a class="btn btn-sm btn-success"><i class="fa fa-save"></i>&nbsp;&nbsp;下单</a>');
        });
        $form -> setAction(admin_url('mpPackageNumberRes'));

        return $form;
    }


    //下单
    function mpPakcageNumber(){
        $id = $_REQUEST['id'];

        //下单
        $info = DB::table('mp_temp_package_number') -> where([
            'id' => $id
        ]) -> first();

        if($info -> order_id){
            return [
                'code' => 500,
                'msg' => '已下过单'
            ];
        }

        if(!$info -> weight){
            return [
                'code' => 500,
                'msg' => '没有维护重量'
            ];
        }


        $weight = $info -> weight;
        DB::table('mp_temp_package_number') -> where([
            'id' => $id
        ]) -> update([
            'weight' => $weight
        ]);

        $mp_user = DB::table('mp_users') -> where([
            'id' => $info -> user_id
        ]) -> first();

        $wxuser = DB::table('wxuser') -> where([
            'unionid' => $mp_user -> unionid
        ]) -> first();
        //dd($wxuser);

        $model = new Order();

        $info -> address = $info -> province.$info -> city.$info -> country.$info -> address;
        //自己申报税金
        $declare_ids = [];
        if($info -> declare_id){
            $declare_ids[] = $info -> declare_id;
        }


        $order_id = $model -> underOrder([
            'weights' => [$weight],
            'from_area' => $info -> area_id,
            'user_id' => $wxuser -> id,
            'names' => [$info -> name],
            'address' => [$info -> address],
            'provinces' => [$info -> province],
            'citys' => [$info -> city],
            'tels' => [$info -> tel],
            'uuid_names_arr' => [$this -> create_uuid()],
            'remark' => $info -> remark,
            'package_nums' => [$info -> package_num],
            'declare_ids' => $declare_ids,
            'order_from' => 'min'

        ]);

        DB::table('order') -> where([
            'id' => $order_id
        ]) -> update([
            'mp_package_id' => $id
        ]);


        //更新package_id
        $package_info = DB::table('packages') -> where([
            'order_id' => $order_id,
        ]) -> first();
        //更新
        DB::table('mp_temp_package_number') -> where([
            'id' => $id
        ]) -> update([
            'order_id' => $order_id,
            'order_status' => 1,
            'updated_at' => time(),
            'package_id' => $package_info -> id
        ]);

        return [
            'code' => 200,
            'msg' => '下单成功'
        ];



    }

    function mpPackageNumberRes(){
        $id = $_REQUEST['id'];

        //下单
        $info = DB::table('mp_temp_package_number') -> where([
            'id' => $id
        ]) -> first();

        /*
        if(!$info -> order_id && isset($_REQUEST['weight']) && $_REQUEST['weight']){
            //下了单 就不能
            $weight = $_REQUEST['weight'];

            DB::table('mp_temp_package_number') -> where([
                'id' => $id
            ]) -> update([
                'weight' => $weight
            ]);

            $mp_user = DB::table('mp_users') -> where([
                'id' => $info -> user_id
            ]) -> first();

            $wxuser = DB::table('wxuser') -> where([
                'unionid' => $mp_user -> unionid
            ]) -> first();
            //dd($wxuser);

            $model = new Order();

            $info -> address = $info -> province.$info -> city.$info -> country.$info -> address;

            $order_id = $model -> underOrder([
                'weights' => [$weight],
                'from_area' => $info -> area_id,
                'user_id' => $wxuser -> id,
                'names' => [$info -> name],
                'address' => [$info -> address],
                'provinces' => [$info -> province],
                'citys' => [$info -> city],
                'tels' => [$info -> tel],
                'uuid_names_arr' => [$this -> create_uuid()],
                'remark' => $info -> remark,
                'package_nums' => [$info -> package_num],

            ]);

            DB::table('order') -> where([
                'id' => $order_id
            ]) -> update([
                'mp_package_id' => $id
            ]);



            //更新
            DB::table('mp_temp_package_number') -> where([
                'id' => $id
            ]) -> update([
                'order_id' => $order_id,
                'order_status' => 1,
                'updated_at' => time(),

            ]);

        }
        */


        DB::table('mp_temp_package_number')
            -> where([
                'id' => $id
            ])
            -> update([
            'province' => isset($_REQUEST['province']) && $_REQUEST['province'] ? $_REQUEST['province']:$info -> province,
            'city' => isset($_REQUEST['city']) && $_REQUEST['city'] ? $_REQUEST['city']:$info -> city,
            'country' => isset($_REQUEST['country']) && $_REQUEST['country'] ? $_REQUEST['country']:$info -> country,
            'address' => isset($_REQUEST['address']) && $_REQUEST['address'] ? $_REQUEST['address']:$info -> address,
            'name' => isset($_REQUEST['name']) && $_REQUEST['name'] ? $_REQUEST['name']:$info -> name,
            'tel' => isset($_REQUEST['tel']) && $_REQUEST['tel'] ? $_REQUEST['tel']:$info -> tel,
            'card' => isset($_REQUEST['card']) && $_REQUEST['card'] ? $_REQUEST['card']:$info -> card,
            'mode_id' => isset($_REQUEST['mode_id']) && $_REQUEST['mode_id'] ? $_REQUEST['mode_id']:$info -> mode_id,
            'remark' => isset($_REQUEST['remark']) && $_REQUEST['remark'] ? $_REQUEST['remark']:$info -> remark,
        ]);


        //如果此小程序订单下了单 则 同时修改订单的包裹信息
        if($info -> order_id){
            $info = DB::table('mp_temp_package_number')
                -> where([
                    'id' => $id
                ]) -> first();
            DB::table('packages') -> where([
                'order_id' => $info -> order_id,
                'flag' => 0
            ]) -> update([
                'province' => $info -> province,
                'city' => $info -> city,
                'address' => $info -> province.$info -> city.$info -> country.$info -> address,
                'name' => $info -> name,
                'tel' => $info -> tel,
            ]);
        }





        admin_toastr('修改成功');
        return redirect(admin_base_path('MpPackageNumber'));
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


    //生成各自区域的单号
    public function makeTempNumberPdf(){
        $from_area = Admin::user()->from_area;
        //$admin_user_name = Admin::user() -> username;
        $number = isset($_POST['number']) && $_POST['number']? intval($_POST['number'])>1000?1000:intval($_POST['number']):20;
        $ids = MpPackageNumber::makeMpNumber($from_area,$number);

        return [
            'code' => 'success',
            'msg' => implode(',',$ids)
        ];


    }

    //请求html生成pdf 一整页
    public function makePdf(){
        if($_GET['type'] == 1){
            //a4纸打印
            $url = admin_url('printMpPdf').'?ids='.$_GET['ids'];
        }else{
            //一张一张的
            $url = admin_url('printMpPdfPage').'?ids='.$_GET['ids'];
        }

        //return redirect()->away('http://www.baidu.com');
        if(env('APP_ENV') != 'local'){
            if($_GET['type'] == 1){
                $pdfurl = PrintSequeue::printHtml($url);
            }else{
                $pdfurl = PrintSequeue::printHtml($url,0,2);
            }

            return redirect()->away($pdfurl);
        }else{
            return redirect()->away('http://www.baidu.com');
        }
    }




    //打印这些标签 a4纸打印
    public function printMpPdf(){
        $mp_temp_number_ids = explode(',',$_GET['ids']);

        //取出这些单号
        $number_info = DB::table('mp_temp_package_number')
            -> whereIn('id',$mp_temp_number_ids) -> select([
                'package_num'
            ]) -> get();
        return view('home.mpPdfPage') -> with([
            'url' => env('PDF_URL'),
            'number_info' => $number_info,
            'type' => 1
        ]);
    }

    public function printMpPdfPage(){
        $mp_temp_number_ids = explode(',',$_GET['ids']);

        //取出这些单号
        $number_info = DB::table('mp_temp_package_number')
            -> whereIn('id',$mp_temp_number_ids) -> select([
                'package_num'
            ]) -> get();
        return view('home.mpPdfOnePage') -> with([
            'url' => env('PDF_URL'),
            'number_info' => $number_info,
            'type' => 2
        ]);
    }


    //毫秒 + 微秒
    function milliseconds($format = 'u', $utimestamp = null)
    {
        if (is_null($utimestamp)){
            $utimestamp = microtime(true);
        }
        $timestamp = floor($utimestamp);
        $milliseconds = round(($utimestamp - $timestamp) * 1000000);//改这里的数值控制毫秒位数
        return $milliseconds;
    }


    function makeMpAreaOrder(){
        $ids = $_POST['ids'];





        $order_num = $_POST['order_num'];

        $temp = DB::table('area_scan_order_info')
            -> whereIn('mp_number_id',$ids)
            -> where([
                'flag' => 0
            ]) -> first();

        if($temp){
            return [
                'code' => 'error',
                'msg' => '不可重复生成交货单'
            ];
        }


        //查看这些订单 是否来自同一个区域
        $area_id = 0;
        foreach($ids as $vo){
            $mp_temp_number_info = DB::table('mp_temp_package_number')
                -> where([
                    'id' => $vo
                ]) -> first();


            if($area_id){
                if($area_id != $mp_temp_number_info -> area_id){
                    return [
                        'code' => 'error',
                        'msg' => '不同区域 不能生成交货单'
                    ];
                }
            }else{
                $area_id = $mp_temp_number_info -> area_id;
            }
        }


        //生成自定义发货单号
        //格式 日期 + 区域id
        /*
        $area_name = DB::table('area_name') -> where([
            'id' => $area_id
        ]) -> first();
        */

        $queue_info = DB::table('sequeue')
            -> where([
                'type' => 3,
                'from_area' => $area_id,
                'date' => date('Ymd'),
            ]) -> first();
        if(!$queue_info){
            DB::table('sequeue')
                -> insertGetId([
                    'type' => 3,
                    'date' => date('Ymd'),
                    'order_num' => 1,
                    'from_area' => $area_id
                ]);
            $order_num = date('Ymd').'-'.sprintf('%03s',$area_id).'-1';
        }else{
            //取出来 + 1
            DB::table('sequeue')
                -> where([
                    'type' => 3,
                    'from_area' => $area_id,
                    'date' => date('Ymd'),
                ]) -> update([
                    'order_num' => intval($queue_info -> order_num) + 1
                ]);

            $order_num = date('Ymd').'-'.sprintf('%03s',$area_id).'-'.(intval($queue_info -> order_num) + 1);
        }




        $order_id = DB::table('area_scan_order')
            -> insertGetId([
                'order_num' =>  trim($order_num),
                'created_at' => time(),
                'updated_at' => time(),
                'count_package' => count($ids),
                'area_id' => $area_id
            ]);

        foreach($ids as $vo){
            DB::table('area_scan_order_info')
                -> insertGetId([
                   'mp_number_id' => $vo,
                    'order_id' => $order_id,
                    'created_at' => time(),
                    'updated_at' => time(),
                ]);
            //标记
            DB::table('mp_temp_package_number')
                -> where([
                    'id' => $vo
                ]) -> update([
                    'send_order_id' => $order_id
                ]);
        }




        return [
            'code' => 'success',
            'msg' => '成功'
        ];


    }



    //类似小程序扫描
    //扫描小程序包裹
    public function scanMpPackage(){
        $text = '';
        if(isset($_GET) && isset($_GET['texts'])){
            $text = trim($_GET['texts']);
        }


        return Admin::content(function (Content $content) use($text)  {
            $package_info = '';
            $content->header('扫描小程序包裹');
            //$content->description('');
            $packages = [];
            //判断用户的区域
            $admin_user_name = Admin::user() -> username;
            $from_area = Admin::user() -> from_area;
            $mp_temp_package_numbers = [];
            $hide_input = [];
            if($text){
                //看下
                //先看有没有这个包裹
                $temp_info = DB::table('mp_temp_package_number')
                    -> where([
                        'package_num' => $text,
                        'flag' => 0
                    ]) -> first();
                if($temp_info && $temp_info -> area_id == $from_area){
                   //直接扫？
                    //展示包裹编号过去
                    $package_info = $temp_info;
                }
            }


            //compact('packages','batch_info')
            $view = view('admin.mp_scan_package',[
                'packages' => $package_info
            ]) -> render();
            $content->body($view);
        });
    }

    //扫描小程序包裹内件
    public function scanMpPackageGoods(){
        $text = '';
        if(isset($_GET) && isset($_GET['texts'])){
            $text = trim($_GET['texts']);
        }
        $package_id = $_GET['id'];
        $get = $_GET;

        return Admin::content(function (Content $content) use($text,$package_id,$get)  {
            $all_goods_info = [];
            $content->header('扫描小程序包裹');
            //$content->description('');
            $packages = [];
            //判断用户的区域
            $admin_user_name = Admin::user() -> username;
            $from_area = Admin::user() -> from_area;
            $mp_temp_package_numbers = [];
            $hide_input = [];
            if($text){
                //把这个商品 扫到内件里
                $goods_info = DB::table('mp_scan_goods_info')
                    -> where([
                        'package_id' => $package_id,
                        'product_no' => $text,
                        'flag' => 0
                    ]) -> first();
                if(!$goods_info){
                    //查找他是否在erp_product_list 中
                    $product_info = DB::table('goods_list')
                        -> where([
                            'flag' => 0,
                            'product_id' => $text
                        ]) -> first();
                    $info_id = DB::table('mp_scan_goods_info') -> insertGetId([
                        'product_no' => $text,
                        'product_id' => $product_info?$product_info -> id:0,
                        'product_name' => $product_info?$product_info -> product_name:'',
                        'declare_price' => $product_info?$product_info -> price:0,
                        'taobao_url' => $product_info?$product_info -> taobao_url:'',
                        'product_method' => $product_info?$product_info -> product_method:'',
                        'product_format' => $product_info?$product_info -> product_format:'',
                        'product_unit' => $product_info?$product_info -> product_unit:'',
                        'english_name' => $product_info?$product_info -> english_name:'',
                        'brand_name' => $product_info?$product_info -> brand_name:'',



                        'goods_number' => 1,
                        'package_id' => $package_id,
                        'created_at' => time(),
                        'updated_at' => time(),
                    ]);

                    dispatch(new Reptile(MpScanInfo::find($info_id)));

                }else{
                    //数量 + 1
                    DB::table('mp_scan_goods_info')
                        -> where([
                            'id' => $goods_info -> id
                        ]) -> update([
                            'goods_number' => intval($goods_info -> goods_number) + 1,
                            'updated_at' => time()
                        ]);
                    $info_id = $goods_info -> id;
                }



            }

            //展示这个包裹的所有内件
            $all_goods_info = DB::table('mp_scan_goods_info')
                -> where([
                    'package_id' => $package_id,
                    'flag' => 0
                ])
                -> select([
                    'id',
                    'goods_number',
                    'product_no'
                ])
                -> get();
            foreach($all_goods_info as $vo){
                $hide_input[] = $vo -> id;
            }

            if(count($hide_input)){
                $hide_input = implode(',',$hide_input);
            }else{
                $hide_input = '';
            }


            //compact('packages','batch_info')
            $view = view('admin.mp_scan_package_goods',[
                'packages' => $all_goods_info,
                'hide_input' => $hide_input
            ]) -> render();
            $content->body($view);
        });
    }





}
