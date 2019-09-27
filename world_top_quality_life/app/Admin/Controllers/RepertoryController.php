<?php

namespace App\Admin\Controllers;

use App\Admin\Extensions\CheckRepertory;
use App\Admin\Extensions\InvoiceExcelExpoter;
use App\Admin\Extensions\PassRepertory;
use App\Admin\Extensions\RepertoryExcelExpoter;
use App\AreaName;
use App\Configure;
use App\Invoice;
use App\Jobs\SendMail;
use App\Order;
use App\Repertory;
use App\RepertoryLog;
use App\SendOrder;
use App\User;
use App\WxUser;
use App\Zips;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\MessageBag;
use Maatwebsite\Excel\Facades\Excel;

class RepertoryController extends Controller
{
    use ModelForm;

    /**
     * Index interface.
     *
     * @return Content
     */
    public function index()
    {
        //查下  有没有没有分配的
        if (!isset($_GET['type']) || !$_GET['type'] == 'wei') {
            $repertory_info = Repertory::where(function ($query) {
                $query->where('user_id', NULL)->orWhere('user_id', 0)->orWhere('is_check', 1);
            })->where('flag', 0)->first();

            if ($repertory_info) {
                return redirect(admin_base_path('repertory') . '?type=wei');
            }
        }


        return Admin::content(function (Content $content) {
            if (isset($_GET['type']) && $_GET['type'] == 'wei') {
                $content->header('未分配到货库存');
                $content->description('必须分配完 才能进行到货库存操作');
            } else {
                $content->header('到货库存');
                $content->description('列表');
            }


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
        return Admin::grid(Repertory::class, function (Grid $grid) {
            //$grid->disableFilter();
            //区域
            $area_id = Admin::user()->from_area;
            //查找区域的admin_user_name
            $area_info = AreaName::find($area_id);


            $grid->paginate(100);
            $grid->id('编号')->display(function ($val) {
                return config('admin.repertory_id_prefix') . sprintf('%06s', $val);
            });


            $grid->model()->orderBy('id', 'desc');
            $grid->model()->orderBy('shiji_date', 'desc');
            $grid->model()->orderBy('check_date', 'desc');


            if (isset($_GET['type']) && $_GET['type'] == 'wei') {
                $grid->model()->where('flag', 0);
                $grid->model()->where(function ($query) use ($area_info) {
                    $query->where('is_fid', 0);
                    $query->where('admin_user_name', $area_info->admin_user_name);
                });
                $grid->model()->where(function ($query) {
                    $query->where('user_id', NULL)->orWhere('user_id', 0)->orWhere('is_check', 1);

                });
            } else {
                $grid->model()->where(function ($query) use ($area_info) {
                    $query->where('flag', 0);
                    $query->where('is_fid', 0);
                    $query->where('is_check', 0);
                    $query->where('user_id', '<>', 0);
                    $query->where('admin_user_name', $area_info->admin_user_name);

                });
            };


            //$grid -> model() -> where('status','<>',3);


            $grid->filter(function ($filter) {
                $filter->scope('status', '已发货')->where('status', 3);

                $filter->scope('status_wei', '未发货、未取消')->where('status', '<>', 3)->where('status', '<>', 7);

                $filter->scope('status_quxiao', '已取消')->where('status', 7);

                $filter->scope('status_tijiao', '客户提交')->where('sub_type', '>', 0);
                $filter->column(1 / 2, function ($filter) {
                    //客户筛选
                    $users = DB::table('wxuser')->get(['nickname', 'id']);
                    $user_arr = [];
                    foreach ($users as $vo) {
                        $user_arr[$vo->id] = $vo->nickname;
                    }
                    $filter->equal('user_id', '客户')->select($user_arr);

                    //到货状态筛选
                    $config_daohuo = config('admin.repertory_status');
                    $filter->in('status', '状态')->multipleSelect($config_daohuo);

                    //快递筛选
                    $config_kuaidi = config('admin.repertory_company');
                    $filter->equal('company', '快递公司')->select($config_kuaidi);


                    //母单号
                    $filter->where(function ($query) {
                        $input = $this->input;
                        if ($input) {
                            //根据单号 找repertory_id
                            $repertory_info = Repertory::where('numbers', 'like', '%' . $input . '%')->get();
                            $repertory_ids = [];
                            if (count($repertory_info)) {
                                foreach ($repertory_info as $vo) {
                                    $repertory_ids[] = $vo->id;
                                }
                                $query->whereIn('fid', $repertory_ids);
                            } else {
                                $query->where('id', 0);
                            }

                        }


                    }, '母单号');

                    //$filter -> equal('canghao','入仓号');


                });


                $filter->column(1 / 2, function ($filter) {

                    $filter->equal('dabao_num', '打包数量');
                    $filter->equal('fachu_num', '发出数量');
                    $filter->equal('shengyu_num', '剩余数量');

                    //包裹状态筛选
                    $config_package_status = config('admin.package_status');
                    $filter->in('package_status', '打包状态')->select($config_package_status);
                    $filter->where(function ($query) {
                        $input = $this->input;
                        if ($input) {
                            $query->where('numbers', 'like', '%' . trim($input) . '%')->orWhere('canghao', 'like', '%' . trim($input) . '%');
                        }
                    }, '单号');

                });


                // 去掉默认的id过滤器
                //$filter->disableIdFilter();
                //$filter -> useModal();
                $filter->expand();


            });

            if (isset($_GET['type']) && $_GET['type'] == 'wei') {
                $grid->numbers('单号')->display(function ($value) {
                    if (!$value) {
                        return $this->canghao;
                    } else {
                        return $value;
                    }
                });
                $user_config = [];
                $user_config_temp = WxUser::select('id', 'nickname')->get();
                foreach ($user_config_temp as $value) {
                    $user_config[$value->id] = $value->id . '-' . $value->nickname;
                }

                $grid->sub_type('提交类型')->display(function ($value) {
                    $sub_configs = config('admin.repertory_sub_type');
                    if (isset($sub_configs[$value])) {
                        return $sub_configs[$value];
                    }
                });

                //提交日期
                $grid->created_at('提交时间')->display(function ($value) {
                    return date('Y-m-d H:i', $value);
                });


                $grid->user_id('客户')->editable('select', $user_config);


                /*
                $area_config = [];
                $area_config_temp = AreaName::select('id','area_name') -> get();
                foreach($area_config_temp as $value){
                    $area_config[$value -> id] = $value -> id.'-'.$value -> area_name;
                }
                $grid -> area_id('区域') ->editable('select', $area_config);
                */

                /*
                $grid -> category('货物品类');
                $grid -> card('司机身份证');
                $grid -> tel('司机手机');
                $grid -> mail('邮箱');
                */


            }

            if (!isset($_GET['type'])) {
                /*
                $grid -> column('详情') -> display(function($value){
                    return view('admin.repertory_grid');
                });
                */


                $grid->fajian_date('发件');
                $grid->company('物流')->display(function ($value) {
                    $config_company = config('admin.repertory_company');
                    if ($value) {
                        return $config_company[$value];
                    }
                });


                $grid->user_id('客户')->display(function ($value) {
                    if ($value) {
                        $userinfo = DB::table('wxuser')->where([
                            'id' => $value
                        ])->first();
                        if ($userinfo) {
                            return $userinfo->nickname;
                        }

                    }

                });
                $grid->numbers('单号');
                /*
                $grid->fid('母单号')->display(function ($value) {
                    if ($value) {
                        $repertory = Repertory::find($value);
                        if ($repertory) {
                            return $repertory->numbers;
                        }

                    }
                });
                */


                $grid->num('件数');
                $grid->weight('重量')->editable();
                $grid->yubao_num('预报箱数');
                $grid->yuji_date('预计到港')->editable('date');
                $grid->shiji_date('实际到港');

                $status_config = config('admin.repertory_status');
                $grid->status('状态')->editable('select', $status_config);

                /*
                $grid->status('状态') -> display(function($value){
                    $status_config = config('admin.repertory_status');
                    if(isset($status_config[$value])){
                        return $status_config[$value];
                    }

                });
                */


                /*
                $grid->package_status('包裹状态')->display(function($value){
                    $package_config = config('admin.package_status');
                    if(isset($package_config[$value])){
                        return $package_config[$value];
                    }
                });
                */


                $grid->dabao_num('打包数量');
                $grid->fachu_num('发出数量');
                $grid->shengyu_num('剩余数量');
                $grid->up_numbers('上卡板数量');

                $grid->goods_value('货值')->editable();
                $currency_config = config('admin.currency');
                $grid->currency('币种')->editable('select', $currency_config);
                $deal_methods = config('admin.deal_method');
                $grid->deal_method('处理方案')->editable('select', $deal_methods);
                $grid->remark('备注')->editable('textarea');

                /*
                $grid -> updated_at('修改') -> display(function($value){
                    return date('m-d H:i',$value);
                });
                */

                /*
                $grid->actions(function ($actions) {
                    $id = $actions->getKey();
                    $actions->disableDelete();
                    $rows = $actions->row;


                    // append一个操作
                    $url_address = admin_url('repertoryAddress') . '?repertory_id=' . $id;
                    $actions->append('<a href="' . $url_address . '" style="margin-top:10px;" ><i class="fa fa-bank"></i></a>');

                    $url_chai = admin_url('repertorySplit') . '?repertory_id=' . $id;
                    //拆分订单
                    $actions->append('<a href="' . $url_chai . '" style="margin-top:10px;" ><i class="fa fa-bomb"></i></a>');



                    if($rows -> sub_type == 2 || $rows -> sub_type == 3 ){
                        $url_pdf = admin_url('repertoryPrint'). '?repertory_id=' . $id;
                        //打印pdf
                        $actions->append('<a href="' . $url_pdf . '" style="margin-top:10px;" ><i class="fa fa-print"></i></a>');



                        $url_eye = admin_url('repertoryCheckInfo').'?id=' .$id.'&sub_type='.$rows -> sub_type.'&from=info';
                        $actions->append('<a href="' . $url_eye . '" style="margin-top:10px;" ><i class="fa fa-eye"></i></a>');

                    }


                });

                */

                $grid->column('操作')->display(function ($value) {
                    $id = $this->id;
                    $sub_type = $this->sub_type;
                    $url_address = admin_url('repertoryAddress') . '?repertory_id=' . $id;
                    $url_chai = admin_url('repertorySplit') . '?repertory_id=' . $id;
                    $url_pdf = admin_url('repertoryPrint') . '?repertory_id=' . $id;
                    $url_eye = admin_url('repertoryCheckInfo') . '?id=' . $id . '&sub_type=' . $sub_type . '&from=info';


                    return view('admin.repertory_grid')->with([
                        'url_address' => $url_address,
                        'url_chai' => $url_chai,
                        'url_pdf' => $url_pdf,
                        'url_eye' => $url_eye,
                        'sub_type' => $sub_type,
                        'url_edit' => admin_url('repertory') . '/' . $id . '/edit',
                        'id' => $id
                    ]);
                });

                $grid->disableActions();
                $grid->exporter(new RepertoryExcelExpoter());
            }

            if (isset($_GET['type']) && $_GET['type'] == 'wei') {
                $grid->actions(function ($actions) {
                    $key = $actions->getKey();
                    $info = Repertory::find($key);
                    if ($info->is_check) {
                        //审核通过按钮
                        $actions->append(new CheckRepertory($info));
                    }

                    $actions->disableDelete();
                    $actions->disableEdit();
                });
            }

            if (isset($_GET['type']) && $_GET['type'] == 'wei') {
                $grid->tools(function ($tools) {
                    $tools->batch(function ($batch) {

                        //$batch->disableDelete();

                        $batch->add('通过', new PassRepertory());
                    });
                });
            } else {
                $grid->tools(function ($tools) {
                    $tools->batch(function ($batch) {
                        $batch->disableDelete();
                    });
                });
            }

        });
    }

    //批量通过操作
    public function PassRepertory()
    {
        //批量通过
        foreach ($_POST['ids'] as $vo) {

            //看下 这单单号 有没有重复
            $info = DB::table('repertory')->where([
                'id' => $vo
            ]) -> first();
            //单号重复 不允许通过
            $count = DB::table('repertory') -> where([
                'numbers' => trim($info->numbers),
                'flag' => 0
            ]) -> count();
            Log::info(trim($info->numbers));
            Log::info($count);
            if($count > 1){
                return [
                    'code' => 'error',
                    'msg' => $info->numbers.' 单号重复',
                ];
            }


            DB::table('repertory')->where([
                'id' => $vo
            ])->update([
                'is_check' => 0
            ]);
            //如果sub_type是2送货上门 并且 填写了邮箱 则发送
            $repertory = Repertory::where([
                'id' => $vo
            ])->first();
            if ($repertory->sub_type == 2 && $repertory->mail) {
                //发送邮件
                dispatch(new SendMail($repertory));
            }
        }
    }

    public function repertoryPrint()
    {
        $repertory_id = $_GET['repertory_id'];
        $info = Repertory::find($repertory_id);
        
        $config = config('admin.repertory_sub_type');

        return view('home.pdfPage.deliverGoods')->with([
            'info' => $info,
            'name' => $info->name,
            'sub_type' => $config[$info->sub_type],
            'canghao' => $info->canghao,
            'tel' => $info->tel,
            'card' => $info->card,
            'num' => $info->num,
            'numbers' => $info->numbers,
            'weight' => $info->weight,
        ]);

    }

    //打印标示卡
    public function markPdfPage()
    {
        return view('admin.pdfPage.markPdfPage');
    }


    //拆分订单
    public function repertorySplit()
    {
        $repertory_id = $_GET['repertory_id'];
        return Admin::content(function (Content $content) use ($repertory_id) {

            $content->header('拆分订单');

            $content->description('');

            $content->body($this->split($repertory_id)->edit($repertory_id));
        });
    }

    //拆分物流单号
    protected function split($repertory_id)
    {
        return Admin::form(Repertory::class, function (Form $form) use ($repertory_id) {
            $form->display('numbers', '物流单号');
            $form->number('chai_number', '拆分数量');

            $form->hidden('id', 'id');
            $form->setAction(admin_base_path('repertorySplitRes'));
        });
    }

    //拆分物流单号处理
    public function repertorySplitRes()
    {
        //拆成 物流单号-1 物流单号-2
        $repertory_info = Repertory::find($_POST['id']);
        Repertory::where('id', $_POST['id'])->update([
            'is_fid' => 1
        ]);
        for ($i = 1; $i <= intval($_POST['chai_number']); $i++) {
            DB::table('repertory')->insert([
                'numbers' => $repertory_info->numbers . '-' . $i,
                'fajian_date' => $repertory_info->fajian_date,
                'company' => $repertory_info->company,
                'user_id' => $repertory_info->user_id,
                'num' => $repertory_info->num,
                'weight' => $repertory_info->weight,
                'yubao_num' => $repertory_info->yubao_num,
                'yuji_date' => $repertory_info->yuji_date,
                'shiji_date' => $repertory_info->shiji_date,
                'status' => $repertory_info->status,
                'dabao_num' => $repertory_info->dabao_num,
                'fachu_num' => $repertory_info->fachu_num,
                'shengyu_num' => $repertory_info->shengyu_num,
                'remark' => $repertory_info->remark,
                'goods_value' => $repertory_info->goods_value,
                'currency' => $repertory_info->currency,
                'package_status' => $repertory_info->package_status,
                'ru_number' => $repertory_info->ru_number,
                'admin_user_name' => $repertory_info->admin_user_name,
                'weight_json' => '',
                'over_json' => '',
                'order_json' => '',
                'created_at' => time(),
                'updated_at' => time(),
                'fid' => $repertory_info->id
            ]);
        }

        RepertoryLog::addLog($repertory_info, 3);


        admin_toastr(trans('拆分订单成功'));
        return redirect(admin_base_path('repertory'));


    }


    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {

        return Admin::form(Repertory::class, function (Form $form) {
            //区域
            $area_id = Admin::user()->from_area;
            //查找区域的admin_user_name
            $area_info = AreaName::find($area_id);

            $admin_user_name = $area_info->admin_user_name;


            $form->display('id', 'ID');

            $form->date('fajian_date', '发件日期');
            $company_config = config('admin.repertory_company');
            $form->select('company', '物流公司')->options($company_config);
            $wxuser = DB::table('wxuser')->get(['nickname', 'id']);
            //dd($wxuser);
            $option_wxuser = [];
            foreach ($wxuser as $vo) {
                $option_wxuser[$vo->id] = 'ID:' . $vo->id . '---昵称:' . $vo->nickname;
            }
            if (isset($_GET['id'])) {
                $form->select('user_id', '客户')->options($option_wxuser)->default($_GET['id'])->rules('required');
            } else {
                $form->select('user_id', '客户')->options($option_wxuser)->rules('required');
            }


            $form->text('numbers', '单号')->rules('required');
            $form->number('num', '件数');
            $form->text('weight', '重量');

            $form->number('goods_value', '货值');
            $currency_config = config('admin.currency');
            $form->select('currency', '币种')->options($currency_config)->default(999);


            $form->number('yubao_num', '预报箱数');
            $form->date('yuji_date', '预计到港')->setWidth(8, 2);
            //$form -> date('shiji_date','实际到港')->setWidth(5);

            //$status_config = config('admin.repertory_status');
            //$form -> select('status','状态') -> options($status_config);
            $form->hidden('status')->default(6);

            /*
            $package_config = config('admin.package_status');
            krsort($package_config);
            $package_config['999'] = '未设置';
            //dd($package_config);
            $form -> select('package_status','包裹状态') -> options($package_config) ->default(999) ;
            */
            $form->hidden('package_status', '包裹状态')->default(999);


            $form->hidden('id', 'id');

            //$form -> number('dabao_num','打包数量');
            //$form -> number('fachu_num','发货数量');
            //$form -> number('shengyu_num','剩余数量');

            //$form -> number('up_numbers','上卡板数量');
            $deal_methods = config('admin.deal_method');
            $form->select('deal_method', '处理方案')->options($deal_methods);

            $form->text('remark', '备注');

            $form->hidden('admin_user_name')->default($admin_user_name);


            $form->saving(function ($form) {
                Log::info(print_r($form, true));
                Log::info(print_r($form->_editable, true));


                if ($form->_editable == 1) {
                    //编辑框状态
                    if ($form->status && $form->status == '5') {
                        //如果状态变成已到 实际到港时间 变成现在
                        //$form -> shiji_date = time();
                    }
                }


                $form->updated_at = time();
                if ($form->numbers) {

                    $form->numbers = preg_replace('# #', '', strtoupper($form->numbers));


                }

                if ($form->id && $form->numbers) {
                    //判断下 numbers 有没有重复
                    $info = Repertory::where('id', '<>', $form->id)->where('numbers', $form->numbers)->first();
                    if ($info) {
                        // 返回一个简单response
                        $error = new MessageBag([
                            'title' => '出错了',
                            'message' => '物流单号已存在了....',
                        ]);

                        return back()->with(compact('error'));
                    }
                }

                if (!$form->id && $form->numbers) {
                    //判断下 numbers 有没有重复
                    $info = Repertory::where('numbers', $form->numbers)->first();
                    if ($info) {
                        // 返回一个简单response
                        $error = new MessageBag([
                            'title' => '出错了',
                            'message' => '物流单号已存在了....',
                        ]);

                        return back()->with(compact('error'));
                    }
                }


            });


        });
    }


    //针对物流单号 维护地址库
    public function repertoryAddress()
    {
        $repertory_id = $_GET['repertory_id'];
        return Admin::content(function (Content $content) use ($repertory_id) {

            $content->header('物流单号-维护收货地址');
            $content->description('');

            $content->body($this->importFileForm($repertory_id)->edit($repertory_id));
        });
    }


    //维护收货地址
    protected function importFileForm($repertory_id)
    {

        return Admin::form(Repertory::class, function (Form $form) use ($repertory_id) {
            $form->display('numbers', '物流单号');
            $form->file('file_column', '地址excel')->rules('required');;

            $form->tools(function (Form\Tools $tools) use ($repertory_id) {
                // 去掉跳转列表按钮
                $tools->disableListButton();

                $url = '"' . url('download_repertory_address') . '"';
                $url_order = admin_url('repertory_under') . '?repertory_id=' . $repertory_id;


                // 添加一个按钮, 参数可以是字符串, 或者实现了Renderable或Htmlable接口的对象实例
                $tools->add('<a class="btn btn-info btn-sm" href="' . $url_order . '"><i class="fa fa-anchor"></i>&nbsp;&nbsp;下单</a>');

                $tools->add('<a class="btn btn-sm btn-danger" onclick=\'window.open(' . $url . ')\'><i class="fa fa-arrow-down"></i>&nbsp;&nbsp;下载模版</a>');

            });


            //如果是填写地址 把她的地址列表展示出来
            $address_res = DB::table('repertory_address')->where([
                'repertory_id' => $repertory_id
            ])->get();
            if (count($address_res)) {
                $form->disableSubmit();
                $form->disableReset();
                $compact = [
                    'address_use' => $address_res,
                    'repertory_id' => $repertory_id,
                    'type' => 'address_page'
                ];
                $view = view('admin.repertoryAddressPage', compact('compact'))->render();
                $form->html($view);
            }


            $form->hidden('id', 'id');
            $form->setAction(admin_base_path('repertoryAddressRes'));


        });
    }


    //物流单号 自动下单
    public function repertory_under()
    {
        return Admin::content(function (Content $content) {

            $content->header('物流单号-自动下单');
            $content->description('');

            $content->body($this->autoUnderOrderForm($_GET['repertory_id'])->edit($_GET['repertory_id']));
        });
    }

    //自动下单地址表单
    public function autoUnderOrderForm($id)
    {
        return Admin::form(Repertory::class, function (Form $form) use ($id) {

            $form->display('id', 'ID');
            $form->display('numbers', '物流单号');
            $form->hidden('id');
            $repertory_info = Repertory::find($id);

            $repertory_weight = DB::table('repertory_weight')
                ->where([
                    'repertory_id' => $id,
                    'flag' => 0,
                    'is_order' => 0
                ])->get();


            if (($repertory_info->over_json && json_decode($repertory_info->over_json)) || count($repertory_weight)) {



                $show_arr = [];
                $repertory_weight_ids = [];
                $repertory_weight_label = [];

                if (count($repertory_weight)) {
                    foreach ($repertory_weight as $vo) {
                        $repertory_weight_ids[] = $vo->id;
                        $repertory_weight_label[] = $vo->label;
                        if (isset($show_arr[$vo->weight])) {
                            $show_arr[$vo->weight] = intval($show_arr[$vo->weight]) + 1;
                        } else {
                            $show_arr[$vo->weight] = 1;
                        }

                    }

                    $options_arr = [];
                    foreach ($show_arr as $k => $vo) {
                        for ($i = 1; $i <= $vo; $i++) {
                            $options_arr[] = $k;
                        }
                    }
                    $options_arr_temp = $options_arr;

                    foreach ($options_arr as $k => $vo) {
                        $options_arr[$k] = '单号:' . $repertory_weight_label[$k] . '   重量:' . $vo;
                    }


                } else {
                    $weight_arr = json_decode($repertory_info->over_json, true);
                    $show_arr = [];
                    foreach ($weight_arr as $k => $vo) {
                        if ($vo > 0) {
                            $show_arr[$k] = $vo;
                        }
                    }

                    $options_arr = [];
                    foreach ($show_arr as $k => $vo) {
                        for ($i = 1; $i <= $vo; $i++) {
                            $options_arr[] = $k;
                        }
                    }
                    $options_arr_temp = $options_arr;
                }


                $form->text('text_formula', '公式下单')->help('如过3.9kg出3个、2.9kg出6个，则公式为3.9*3+2.9*6')->placeholder('3.9*3+2.9*6');

                $form->html('<input type="button" value="全选" id="checkAll" /> <input type="button" value="取消" id="cancelAll" /> ');

                // 竖排
                $form->checkbox('package_json_under', '选择')->options($options_arr)->stacked();

                $form->html('<input type="hidden" id="mark" value="" />');

                $form->html('<script>
$("#checkAll").click(function(){
    $("input:checkbox").attr("checked", "checked");
    $("input:checkbox").parent("div").addClass("checked");
});
$("#cancelAll").click(function(){
    $("input:checkbox").attr("checked", false);
    $("input:checkbox").parent("div").removeClass("checked");
});

</script>');


                $form->hidden('option_json')->default(json_encode($options_arr_temp));
                $form->hidden('repertory_weight_ids')->default(json_encode($repertory_weight_ids));
            } else {
                admin_toastr(trans('没有打好的包'), 'error');
                $form->disableSubmit();
                $form->disableReset();
            }

            $admin_user = Admin::user()->username;

            $area_names = DB::table('area_name')->get();
            foreach ($area_names as $vo) {
                $options[$vo->id] = $vo->area_name;
            }

            $form->select('from_area_id', '区域')->options($options)->rules('required');


            $form->hidden('id', 'id');
            $form->tools(function (Form\Tools $tools) {
                // 去掉跳转列表按钮
                $tools->disableListButton();
            });

            $form->setAction(admin_base_path('repertoryUnderOrderRes'));

        });
    }

    //物流单号自动下单
    public function repertoryUnderOrderRes()
    {
        //var_dump(json_encode($_REQUEST));exit;
        //$_REQUEST['package_json_under'] = json_decode($_REQUEST['package_json_under'],true);
        if (!$_REQUEST['id'] || !$_REQUEST['from_area_id']) {
            admin_toastr('请选择区域');
            return redirect(admin_base_path('repertory_under') . '?repertory_id=' . $_REQUEST['id']);
        }
        if (count($_REQUEST['package_json_under']) <= 1 && !$_POST['text_formula']) {
            admin_toastr(trans('没有要下的单'));
            return redirect(admin_base_path('repertory_under') . '?repertory_id=' . $_REQUEST['id']);
        }


        $from_area = $_REQUEST['from_area_id'];

        $repertory_info = DB::table('repertory')->where([
            'id' => $_REQUEST['id']
        ])->first();

        $user_id = $repertory_info->user_id;

        //总共的打包的包裹
        $option_json_arr = json_decode($_REQUEST['option_json'], true);

        //repertory_weight_ids
        $repertory_weight_ids = json_decode($_REQUEST['repertory_weight_ids'], true);


        //有公式 优先选用公式
        $text_formula = preg_replace('# #', '', trim($_POST['text_formula']));;
        $index_order = [];
        //判断公式是否正确
        $check_arr = [];

        if ($text_formula) {

            $weights = [];
            //以加号分割 3.9*3+2.9*6
            $temp_arr = explode('+', $text_formula);
            //检查数组每个部分 是否有乘号
            foreach ($temp_arr as $vo) {
                if (!strstr($vo, '*')) {
                    admin_toastr(trans('公式错误，单个元素必须是乘法'));
                    return redirect(admin_base_path('repertory_under') . '?repertory_id=' . $_REQUEST['id']);
                }
                $m_arr = explode('*', $vo);
                //看下是不是两个元素
                if (count($m_arr) <> 2) {
                    admin_toastr(trans('公式错误，乘法公式不规范'));
                    return redirect(admin_base_path('repertory_under') . '?repertory_id=' . $_REQUEST['id']);
                }
                //算下每个重量 下多少
                if (!isset($check_arr[$m_arr[0]])) {
                    $check_arr[$m_arr[0]] = intval($m_arr[1]);
                } else {
                    $check_arr[$m_arr[0]] = intval($check_arr[$m_arr[0]]) + intval($m_arr[1]);
                }

                for ($i = 0; $i < intval($m_arr[1]); $i++) {
                    $weights[] = $m_arr[0];
                }


            }


        }

        //计算下 总共的包裹里 有没有公式这些
        if (count($check_arr)) {
            $array_count_values = array_count_values($option_json_arr);
            $order_num = 0;
            foreach ($check_arr as $k => $vo) {
                if (!isset($array_count_values[$k])) {
                    admin_toastr(trans('公式错误，下单数量不正确'));
                    return redirect(admin_base_path('repertory_under') . '?repertory_id=' . $_REQUEST['id']);
                }


                if (intval($vo) > $array_count_values[$k]) {
                    admin_toastr(trans('公式错误，下单数量不正确'));
                    return redirect(admin_base_path('repertory_under') . '?repertory_id=' . $_REQUEST['id']);
                }
                $order_num += intval($vo);
            }


        } else {
            //算下下单的包裹数量
            $order_num = count($_REQUEST['package_json_under']) - 1;
            $weights = array_slice($_REQUEST['package_json_under'], 0, $order_num);
            //记录选择的索引，之后要用
            $index_order = $weights;
            foreach ($weights as $k => $vo) {
                $weights[$k] = $option_json_arr[$vo];
            }


        }


        //算下这个物流单号 有多少地址
        $address = DB::table('repertory_address')->where([
            'repertory_id' => $_REQUEST['id']
        ])->get();
        if (count($address)) {

            //有地址、一个地址 可下三单 比较一下
            if (count($address) * 3 < intval($order_num)) {
                admin_toastr(trans('地址不够用，您要下' . $order_num . '单，地址只有' . count($address) . '个'));
                return redirect(admin_base_path('repertory_under') . '?repertory_id=' . $_REQUEST['id']);
            }

            //有地址 .. 判断下有没有地址没用过
            //总共需要地址数
            $count_address_need = 0;
            $model_order = new Order();
            //最终下单的地址
            $under_address = [];
            foreach ($address as $vo) {
                $temp = $model_order->getNumbers([
                    'province' => $vo->province,
                    'city' => $vo->city,
                    'name' => $vo->name,
                    'tel' => $vo->tel,
                    'address' => $vo->address,
                ]);
                $under_address[] = $temp;

                $count_address_need += count($temp);
            }

            //var_dump(json_encode($under_address));exit;
            if ($count_address_need < intval($order_num)) {
                //地址不够
                admin_toastr(trans('还有' . abs(intval($order_num) - $count_address_need) . '个包裹 没有地址'));
                return redirect(admin_base_path('repertory_under') . '?repertory_id=' . $_REQUEST['id']);
            }

            //将地址 打乱
            //最最后 要下单的地址
            //var_dump(json_encode($under_address));
            $under_res_address = [];
            foreach ($under_address as $k => $vo) {
                if (isset($vo[0])) {
                    $under_res_address[] = $vo[0];
                    unset($under_address[$k][0]);
                }
            }

            foreach ($under_address as $k => $vo) {
                if (isset($vo[1])) {
                    $under_res_address[] = $vo[1];
                    unset($under_address[$k][1]);
                }
            }

            foreach ($under_address as $k => $vo) {
                if (isset($vo[2])) {
                    $under_res_address[] = $vo[2];
                    unset($under_address[$k][2]);
                }
            }

            $over_address = array_slice($under_res_address, 0, intval($order_num));
            //var_dump(json_encode($over_address));exit;

            $names = [];
            $address = [];
            $provinces = [];
            $citys = [];
            $tels = [];
            //var_dump($over_address);exit;
            foreach ($over_address as $k => $vo) {
                $names[] = $vo['name'];
                $address[] = $vo['address'];
                $provinces[] = $vo['province'];
                $citys[] = $vo['city'];
                $tels[] = $vo['tel'];
            }


            //直接下单
            //分成
            foreach ($names as $vo) {
                $uuid_names_arr[$vo] = $this->create_uuid();
            }


            $model = new Order();

            //判断下有无到货物流临时单号
            $package_numbers = [];
            //用到的单号id
            $repertory_weight_id_arr = [];
            if (count($repertory_weight_ids) && count($index_order)) {
                //找出这些单号
                foreach ($index_order as $vo) {
                    $repertory_weight_id_arr[] = $repertory_weight_ids[$vo];
                    $temp_repertory_weight = DB::table('repertory_weight')
                        ->where([
                            'id' => $repertory_weight_ids[$vo]
                        ])->first();
                    if (!$temp_repertory_weight) {
                        admin_toastr(trans('数据错误！到货物流维护重量错误'));
                        return redirect(admin_base_path('repertory_under') . '?repertory_id=' . $_REQUEST['id']);
                    }
                    $package_numbers[] = $temp_repertory_weight->label;
                }
            }


            $model->underOrder([
                'package_nums' => $package_numbers,
                'weights' => $weights,
                'from_area' => $from_area,
                'user_id' => $user_id,
                'names' => $names,
                'address' => $address,
                'provinces' => $provinces,
                'citys' => $citys,
                'tels' => $tels,
                'uuid_names_arr' => $uuid_names_arr,
                'remark' => '到货物流单号:' . $repertory_info->numbers,
                'repertory_id' => $_REQUEST['id'],

            ]);

            //下单结束后，统计over_json 和 order_json
            //zong
            $weight_json_arr = json_decode($repertory_info->weight_json, true);
            //order_json 累加
            if ($repertory_info->order_json) {
                $order_json_arr_pre = json_decode($repertory_info->order_json, true);
                //累加
                foreach ($weights as $vo) {
                    if (isset($order_json_arr_pre[$vo])) {
                        $order_json_arr_pre[$vo]++;
                    } else {
                        $order_json_arr_pre[$vo] = 1;
                    }

                }
                $order_json_arr = $order_json_arr_pre;
            } else {
                //不存在order_json 则将此次下单的加入进去
                $order_json_arr = [];
                foreach ($weights as $vo) {
                    if (isset($order_json_arr[$vo])) {
                        $order_json_arr[$vo]++;
                    } else {
                        $order_json_arr[$vo] = 1;
                    }

                }

            }


            //over_json = weight_json - order_json
            foreach ($weights as $vo) {
                //$weight_json_arr[$vo] = $weight_json_arr[$vo] - 1;
            }

            DB::table('repertory')->where([
                'id' => $repertory_info->id
            ])->update([
                'over_json' => json_encode($weight_json_arr),
                'order_json' => json_encode($order_json_arr),
                'updated_at' => time()
            ]);


            //下单成功后 把repertory_weight 标记
            if ($repertory_weight_id_arr) {
                foreach ($repertory_weight_id_arr as $vo) {
                    DB::table('repertory_weight')
                        ->where([
                            'id' => $vo
                        ])->update([
                            'is_order' => 1
                        ]);
                }
            }


            admin_toastr(trans('下单成功'));
            return redirect(admin_base_path('repertoryAddress') . '?repertory_id=' . $repertory_info->id);


        } else {
            admin_toastr(trans('没有可用的地址'));
            return redirect(admin_base_path('repertory_under') . '?repertory_id=' . $_POST['id']);
        }

    }


    //物流单号 导入收获地址
    public function repertoryAddressRes()
    {
        $result = $this->importAddress($_FILES['file_column']['tmp_name'], $_POST['id']);


        admin_toastr('导入成功');

        return redirect(admin_base_path('repertoryAddress') . '?repertory_id=' . $_POST['id']);
    }

    public function importAddress($filePath, $repertory_id)
    {
        $from_area = Admin::user()->from_area;
        //dd($from_area);
        //$filePath = 'storage/exports/'.iconv('UTF-8', 'GBK', '学生成绩').'.xls';
        Excel::load($filePath, function ($reader) use (&$res) {
            $reader = $reader->getSheet(0);
            $res = $reader->toArray();
        });


        foreach ($res as $key => $vo) {
            //忽略第一行 表头
            if ($key == 0) {
                continue;
            }


            if ($vo[0] && $vo[1] && $vo[2] && $vo[3] && $vo[4]) {
                //收货人姓名  电话       地址      城市        省份
                //添加到user_address中
                $name = trim($vo[0]);
                $tel = trim($vo[1]);
                $address = trim($vo[2]);
                $city = trim($vo[3]);
                $province = trim($vo[4]);


                //查看地址中 是否包含省 市
                if (!strstr($address, $province)) {
                    continue;
                }

                if (!strstr($address, $city)) {
                    //市
                    continue;
                }

                $isMob = "/^1[345789]{1}\d{9}$/";

                if (!preg_match($isMob, $tel)) {

                    continue;

                }


                $isset = DB::table('repertory_address')
                    ->where(function ($query) use ($name, $tel, $address, $city, $province, $repertory_id) {
                        $query->where([
                            'name' => $name
                        ])->where([
                            'tel' => $tel
                        ])->where([
                            'address' => $address
                        ])->where([
                            'repertory_id' => $repertory_id,
                        ]);
                    })->first();


                if (!$isset) {
                    DB::table('repertory_address')->insert([
                        'name' => $name,
                        'tel' => $tel,
                        'address' => $address,
                        'city' => $city,
                        'province' => $province,
                        'repertory_id' => $repertory_id,
                        'created_at' => time(),
                        'updated_at' => time(),
                    ]);
                }
            }
        }


    }


    //删除物流单号添加的地址
    public function deleteRepertoryAddress()
    {
        DB::table('repertory_address')->whereIn('id', $_POST['check_arr'])->delete();
        echo 'success';
    }


    //出货汇总
    public function shipmentSummary()
    {
        return Admin::content(function (Content $content) {

            $content->header('出货汇总');
            $content->description('');

            $view = $this->summaryContent();

            $content->body($view);
        });
    }

    //出货汇总页面
    public function summaryContent()
    {

        //查下今天的交货单
        $model = new SendOrder();

        if (isset($_GET['layDate']) && $_GET['layDate']) {
            $layDate = $_GET['layDate'];
        } else {
            $layDate = date('Y-m-d');
        }

        $list = $model->getSummaryList($layDate);

        return view('admin.summaryContent')->with([
            'list' => $list,
            'layDate' => $layDate
        ]);
    }

    //审核
    public function checkRepertory()
    {
        if ($_POST['type'] == 1) {
            //通过
            //看下这单入仓号 或者 单号 有没有维护
            $info = Repertory::where('id', $_POST['id'])->first();
            //如果此单 sub_type = 1 并且没有单号 那不让提交
            if ($info->sub_type == 1 && !$info->numbers) {
                return [
                    'code' => 'error',
                    'msg' => '必须填写单号',
                ];
            }

            //单号重复 不允许通过
            $count = DB::table('repertory') -> where([
                'numbers' => trim($info->numbers),
                'flag' => 0
            ]) -> count();

            Log::info($info->numbers);
            Log::info(DB::table('repertory') -> where([
                'numbers' => trim($info->numbers),
                'flag' => 0
            ]) -> toSql());
            Log::info($count);
            if($count > 1){
                return [
                    'code' => 'error',
                    'msg' => $info->numbers.' 单号重复',
                ];
            }




            if ($info->sub_type == 2 && $info->mail) {
                //发送邮件
                dispatch(new SendMail($info));
            }


            Repertory::where('id', $_POST['id'])->update([
                'is_check' => 0,
                'check_date' => time(),
            ]);
            return [
                'code' => 'success',
                'msg' => '成功'
            ];


        } else {
            Repertory::where('id', $_POST['id'])->update([
                'flag' => 1
            ]);
            return [
                'code' => 'success',
                'msg' => '成功'
            ];


        }
    }


    public function repertoryCheckInfo()
    {
        return Admin::content(function (Content $content) {

            $content->header('编辑详情');
            $content->description('');
            $content->body($this->repertoryCheckInfoForm($_GET['sub_type'])->edit($_GET['id']));
        });
    }


    protected function repertoryCheckInfoForm($sub_type)
    {
        return Admin::form(Repertory::class, function (Form $form) use ($sub_type) {
            if ($sub_type == 1) {
                //$form -> display('canghao','入仓号');
                //国际物流
                $form->date('fajian_date', '发件日期');
                $form->display('user_id', '客户名')->with(function ($value) {
                    $userinfo = WxUser::find($value);
                    return $userinfo->nickname;
                });
                $company_config = config('admin.repertory_company');
                $form->select('company', '物流公司')->options($company_config);
                $form->date('yuji_date', '预计到港')->setWidth(8, 2);
                $package_config = config('admin.package_status');
                krsort($package_config);
                $package_config['999'] = '未设置';
                //dd($package_config);
                $form->select('package_status', '包裹状态')->options($package_config)->default(999);

                $form->hidden('id', 'id');
                $form->text('numbers', '单号')->rules('required');
                $form->text('num', '件数')->rules('required');
                $form->text('weight', '重量')->rules('required');
                $form->text('goods_value', '货值')->rules('required');
                $currency_config = Configure::getCurrency();
                $form->select('currency', '币种')->options($currency_config)->default(999);

                /*
                $form -> display('currency','币种') -> with(function($val){
                    $currency_config = config('admin.currency');
                    if(isset($currency_config[$val])){
                        return $currency_config[$val];
                    }

                });
                */
                $form->textarea('remark', '备注');
                $form->display('photo', '照片')->with(function ($value) {
                    return "<img src='" . $value . "' style='width:100%;height:100%;' />";
                });

                $form->hidden('sub_type', 'sub_type');

                $form->setAction(admin_base_path('repertoryCheckInfoRes'));

            } elseif ($sub_type == 2) {
                $form->display('canghao', '入仓号');
                //送货上门
                $form->display('user_id', '客户名')->with(function ($value) {
                    $userinfo = WxUser::find($value);
                    return $userinfo->nickname;
                });
                $form->display('song_date', '送货日期');
                $form->display('tel', '电话号码');
                $form->display('card', '身份证号码');
                $form->display('mail', '接收送货单邮箱');
                $form->display('num', '件/板数');
                $form->display('weight', '重量');
                $form->display('package_status', '货物状态')->with(function ($val) {
                    $config = config('admin.package_status');
                    if (isset($config[$val])) {
                        return $config[$val];
                    } else {
                        return '其他';
                    }
                });
                $form->text('remark', '备注');
                $form->disableSubmit();
                $form->disableReset();
            } elseif ($sub_type == 3) {
                $form->display('canghao', '入仓号');
                //安排提货
                $form->display('user_id', '客户名')->with(function ($value) {
                    $userinfo = WxUser::find($value);
                    if ($userinfo) {
                        return $userinfo->nickname;
                    }

                });
                $form->display('ti_date', '预约提货日期');
                $form->display('name', '联系人');
                $form->display('tel', '电话号码');
                $form->display('address', '地址');

                $form->display('num', '件/板数');
                $form->display('weight', '重量');
                $form->display('service_type', '服务选择')->with(function ($val) {
                    $config = config('admin.repertory_service_type');
                    if (isset($config[$val])) {
                        return $config[$val];
                    } else {
                        return '其他';
                    }
                });
                $form->text('remark', '备注');
                $form->disableSubmit();
                $form->disableReset();
            }


            //$form -> setAction(admin_base_path('repertoryCheckInfoRes'));
        });
    }

    public function repertoryCheckInfoRes()
    {
        if ($_POST['numbers']) {
            Repertory::where('id', $_POST['id'])->update([
                'numbers' => preg_replace('# #', '', strtoupper(trim($_POST['numbers']))),
                'num' => $_POST['num'] ? $_POST['num'] : 0,
                'weight' => $_POST['weight'] ? $_POST['weight'] : 0,
                'goods_value' => $_POST['goods_value'] ? $_POST['goods_value'] : 0,
                'currency' => $_POST['currency'] ? $_POST['currency'] : '999',
                'remark' => $_POST['remark'],
                'fajian_date' => isset($_POST['fajian_date']) ? $_POST['fajian_date'] : '',
                'company' => isset($_POST['company']) ? $_POST['company'] : 0,
                'yuji_date' => isset($_POST['yuji_date']) ? $_POST['yuji_date'] : '',
                'package_status' => isset($_POST['package_status']) ? $_POST['package_status'] : '999',
            ]);
            return redirect(admin_base_path('repertory'));
        } else {
            admin_toastr(trans('单号必填'), 'error');
            return redirect(admin_base_path('repertoryCheckInfo') . '?id=' . $_POST['id'] . '&sub_type=' . $_POST['sub_type']);
        }
    }


    function create_uuid($prefix = "")
    {    //可以指定前缀
        $str = md5(uniqid(mt_rand(), true));
        $uuid = substr($str, 0, 8) . '-';
        $uuid .= substr($str, 8, 4) . '-';
        $uuid .= substr($str, 12, 4) . '-';
        $uuid .= substr($str, 16, 4) . '-';
        $uuid .= substr($str, 20, 12);
        return $prefix . $uuid;
    }

    public function invoiceList()
    {
        return Admin::content(function (Content $content) {
            $content->header('invoice');
            $content->description('列表');
            $content->body($this->grid_invoice());
        });
    }

    public function grid_invoice()
    {
        return Admin::grid(Invoice::class, function (Grid $grid) {
            $grid->disableFilter();
            //区域
            $area_id = Admin::user()->from_area;

            $grid->paginate(100);

            $grid->model()->orderBy('id', 'desc');
            $grid->model()->where('flag', 0);

            $grid -> id('编号');
            $grid -> mp_users() -> nickname('客户');
            $grid -> send_company('发件公司');
            $grid -> send_address('发件地址');
            $grid -> send_zipCode('邮政编码');
            $grid -> send_name('发件人');
            $grid -> send_tel('发件手机');




            $grid -> created_at('创建时间')->display(function($val){
                if(!$val){
                    return '';
                }
                return date('Y-m-d H:i',$val);
            });
            $grid->disableActions();
            $grid->exporter(new InvoiceExcelExpoter());

            $grid->tools(function ($tools) {
                $tools->batch(function ($batch) {
                    $batch->disableDelete();

                });
            });
            $grid->disableCreateButton();


        });
    }


}
