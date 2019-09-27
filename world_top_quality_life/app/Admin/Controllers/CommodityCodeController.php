<?php

namespace App\Admin\Controllers;

use App\Admin\Extensions\CommodityCodeExcelExpoter;
use App\Admin\Extensions\importFindGoods;
use App\AreaName;
use App\CommodityCode;
use App\CommodityCodeTemp;
use App\GoodsList;
use App\Http\Controllers\Controller;
use App\Package;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class CommodityCodeController extends Controller
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
            ->header('商品编码')
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

        return Admin::grid(CommodityCodeTemp::class,function(Grid $grid){
            $grid->exporter(new CommodityCodeExcelExpoter());

            $area_id = Admin::user() -> from_area;
            $admin_username = Admin::user() -> username;

            $handle = true;
            if(!strstr($admin_username,'admin')){
                $grid -> model() -> where('area_id',$area_id);
                $handle = false;
            }

            $grid -> model() -> where('fid',0);
            $grid -> model() -> orderBy('id','desc');

            $grid->filter(function($filter)use($handle){
                $filter->expand();
                // 去掉默认的id过滤器
                $filter->disableIdFilter();
                $filter->like('number', '包裹编号');
                $filter->where(function ($query) {
                    $input = $this->input;
                    //查找 code 里 包裹编号
                    $codes = CommodityCode::where('code',trim($input)) -> get();
                    if($codes){
                        $ids = [];
                        foreach($codes as $vo){
                            $ids[] = $vo -> fid;
                        }
                        $query -> whereIn('id',$ids);
                    }
                }, '商品编码');

                if($handle){
                    //允许筛选商品区域
                    $area_names = AreaName::get();
                    foreach($area_names as $vo){
                        $area_setting[$vo -> id] = $vo -> area_name;
                    }
                    $filter->where(function ($query) {
                        $input = $this->input;
                        $query->where('area_id', '=', "{$input}");
                    }, '区域')->select($area_setting);
                }

                //父单号
                $filter -> where(function($query){
                    $input = $this->input;
                    //查找 code 里 包裹编号
                    $info = CommodityCodeTemp::where('number',trim($input)) -> first();
                    $query -> where('fid',$info -> id);
                },'拆单前单号');






            });
            $grid->id('编号');
            $grid -> model() -> where('flag',0);



            $grid->number('包裹编号');



            /*
            $grid->fid('拆单前单号') -> display(function($value){
                if($value){
                    $commodity_info = CommodityCodeTemp::where('id',$value) -> first();
                    if($commodity_info){
                        return $commodity_info -> number;
                    }
                }
            });
            */



            //区域
            $grid -> area_id('区域') -> display(function($value){
                if($value){
                    //通过from_area 找 area_name
                    $area_info = AreaName::find($value);

                    return $area_info -> area_name;
                }

            });

            $grid -> column('详情') -> display(function($value){
                $id = $this -> id;
                //用id 查找子单号
                $code_infos = CommodityCodeTemp::where('fid',$id) -> get();
                if(count($code_infos)){
                    $code_infos = CommodityCodeTemp::where('fid',$id) -> orWhere('id',$id) -> get();
                    //有子单号 就按照列表显示
                    foreach($code_infos as $k => $vo){
                        //单号后边跟上商品列表
                        $temp = CommodityCode::where('fid',$vo -> id) -> get();
                        foreach($temp as $k_temp => $vo_temp){
                            $goods_info = GoodsList::where('product_id',$vo_temp -> code) -> first();
                            $goods_name = isset($goods_info -> product_name)?$goods_info -> product_name:'';

                            $temp[$k_temp] -> goods_name = $goods_name;
                        }

                        $code_infos[$k] -> codes_info = $temp;
                    }

                    //dd($code_infos);


                }else{

                    $code_infos = CommodityCodeTemp::where('id',$id) -> get();

                    //有子单号 就按照列表显示
                    foreach($code_infos as $k => $vo){
                        //单号后边跟上商品列表
                        $temp = CommodityCode::where('fid',$vo -> id) -> get();
                        foreach($temp as $k_temp => $vo_temp){
                            $goods_info = GoodsList::where('product_id',$vo_temp -> code) -> first();
                            $goods_name = isset($goods_info -> product_name)?$goods_info -> product_name:'';

                            $temp[$k_temp] -> goods_name = $goods_name;
                        }

                        $code_infos[$k] -> codes_info = $temp;
                    }

                }



                return view('admin.commodity_grid') -> with([
                    'code_infos' => $code_infos,
                    'number' => $this -> number
                ]);
            });


            /*
            $grid -> column('商品编码','商品编码') -> display(function($value){



                $id = $this -> id;
                //用id 查找子单号
                $code_infos = CommodityCodeTemp::where('fid',$id) -> get();
                if(count($code_infos)){
                    //有子单号 就按照列表显示
                    $html = '<table>';
                    $count = count($code_infos);
                    foreach($code_infos as $k => $vo){
                        $html .= '<tr>';

                        if($k + 1 == $count){
                            $html .= '<td style="border-right:1px solid #F00">'.$vo -> number.'</td>';
                        }else{
                            $html .= '<td style="border-bottom:1px solid #F00;border-right:1px solid #F00">'.$vo -> number.'</td>';
                        }

                        //
                        $temp = CommodityCode::where('fid',$vo -> id) -> get();
                        if($k + 1 == $count){
                            $html .= '<td >';
                        }else{
                            $html .= '<td style="border-bottom:1px solid #F00">';
                        }
                        foreach($temp as $temp_vo){
                            $goods_info = GoodsList::where('product_id',$temp_vo -> code) -> first();
                            $goods_name = isset($goods_info -> product_name)?$goods_info -> product_name:'';
                            $html .= '<div style="float:left;">'.$vo -> code.'</div><div style="float:left;margin-left:15px;">'.$goods_name.'</div><div style="clear: both"></div>';
                        }
                        $html .= '</td>';

                        $html .= '</tr>';
                    }
                    $html .= '</table>';
                    return $html;


                }else{

                    $info = CommodityCode::where('fid',$id) -> get();
                    if($info){
                        $html = '';
                        foreach($info as $vo){
                            $goods_info = GoodsList::where('product_id',$vo -> code) -> first();
                            $goods_name = isset($goods_info -> product_name)?$goods_info -> product_name:'';
                            $html .= '<div style="float:left;">'.$vo -> code.'</div><div style="float:left;margin-left:15px;">'.$goods_name.'</div><div style="clear: both"></div>';
                        }
                        return $html;
                    }
                }

            });

            */

            /*
            $grid -> colum('地址','地址') -> display(function($value){
                return $this -> address;
            });
            */


            $grid->created_at('创建时间') -> display(function($value){
                return date('Y-m-d H:i',$value);
            });


            $grid->actions(function ($actions)use($handle){

                $rows = $actions->row;

                $id = $actions->getKey();
                $actions->disableDelete();
                $actions->disableEdit();
                $actions->append('<a href="/admin/splitCommodityCode?id='.$id.'" style="margin-left:8px;font-size:15px;">拆分</a>');





            });

            $grid->disableRowSelector();

            $grid->disableCreateButton();


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
        $show = new Show(CommodityCodeTemp::findOrFail($id));

        $show->id('Id');
        $show->number('Number');
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
        $form = new Form(new CommodityCodeTemp);

        $form->text('number', 'Number');

        return $form;
    }


    //拆分商品编码的包裹
    public function splitCommodityCode(Content $content){
        $id = $_GET['id'];
        return $content
            ->header('拆分包裹')
            ->description('')
            ->body($this->splitCommodityCodeForm($id));


    }

    protected function splitCommodityCodeForm($id){
        //展示

        return Admin::form(CommodityCodeTemp::class, function (Form $form) use($id) {
            $codes = CommodityCode::where('fid',$id) -> get();

            $options_arr = [];
            foreach($codes as $vo){
                $goods_info = GoodsList::where('product_id',$vo -> code) -> first();
                $goods_name = isset($goods_info -> product_name)?$goods_info -> product_name:'';
                $options_arr[$vo -> id] = $vo -> code.'-------'.$goods_name;
            }
            $form->checkbox('options_arr','请选择商品生成包裹')->options($options_arr)->stacked();
            $form -> hidden('code_id','code_id') ->default($id);

            $form -> setAction(admin_base_path('splitCommodityCodeRes'));

        });
    }

    public function splitCommodityCodeRes(){
        $id = $_POST['code_id'];
        $arr = $_POST['options_arr'];
        if(!count($arr)){
            admin_toastr('没有商品','error');
            return redirect(admin_base_path('CommodityCode'));
        }

        //1、拆分单号 2、将商品编码挂在拆分后的单号中
        //先查下此单 有多少子单号
        $count_code = CommodityCodeTemp::where('fid',$id) -> count();

        $info = CommodityCodeTemp::find($id);
        // +1 生成单号
        $split_id = CommodityCodeTemp::insertGetId([
            'number' => $info -> number . '-' . sprintf('%2s',intval($count_code)+1),
            'fid' => $id,
            'created_at' => time(),
            'updated_at' => time(),
            'area_id' => $info -> area_id,
            'package_id' => $info -> package_id
        ]);


        foreach($arr as $vo){
            CommodityCode::where('id',$vo) -> update([
                'fid' => $split_id,
                'updated_at' => time()
            ]);
        }

        //拆分成功

        admin_toastr('拆分成功');
        return redirect(admin_base_path('CommodityCode'));


    }


    public function editCommodityCodeAddress(Content $content){
        $id = $_GET['id'];
        return $content
            ->header('维护地址')
            ->description('')
            ->body($this->editCommodityCodeAddressForm($id));
    }

    protected function editCommodityCodeAddressForm($id){
        $area_id = Admin::user() -> from_area;
        $admin_username = Admin::user() -> username;

        $handle = true;
        if(!strstr($admin_username,'admin')){
            $handle = false;
        }
        return Admin::form(CommodityCodeTemp::class, function (Form $form) use($id,$handle,$area_id) {

            $info = CommodityCodeTemp::find($id);
            if(!$handle){
                if($info -> area_id != $area_id){
                    exit;
                }
            }
            $package_info = Package::find($info -> package_id);



            $form -> display('number','单号') -> value($info -> number);
            //找到此包裹 对应的
            $form -> hidden('temp_id','id') -> value($id);
            $form -> text('province','省') -> setWidth(4,2)->help('原单号省：'.$package_info -> province)->value($info -> province);
            $form -> text('city','市')-> setWidth(4,2)->help('原单号市：'.$package_info -> city)->value($info -> city);
            $form -> text('name','收件人姓名')-> setWidth(4,2)->help('原单号姓名：'.$package_info -> name)->value($info -> name);
            $form -> text('tel','收件人电话')-> setWidth(4,2)->help('原单号电话：'.$package_info -> tel)->value($info -> tel);
            $form -> text('address','地址')->help('原单号地址：'.$package_info ->address)->value($info -> address);

            $form -> setAction(admin_base_path('editCommodityCodeAddressRes'));

        });
    }


    //保存编辑后的地址
    public function editCommodityCodeAddressRes(){
        if(!$_POST['province'] || !$_POST['city'] || !$_POST['name'] || !$_POST['tel'] || !$_POST['address']   ){
            admin_toastr('全部为必填项','error');
            return redirect(admin_base_path('editCommodityCodeAddress').'?id='.$_POST['temp_id']);
        }

        CommodityCodeTemp::where('id',$_POST['temp_id']) -> update([
            'province' => trim($_POST['province']),
            'city' => trim($_POST['city']),
            'name' => trim($_POST['name']),
            'tel' => trim($_POST['tel']),
            'address' => trim($_POST['address']),
        ]);
        admin_toastr('保存');
        return redirect(admin_base_path('CommodityCode'));
    }


}
