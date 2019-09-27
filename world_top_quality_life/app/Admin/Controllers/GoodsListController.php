<?php

namespace App\Admin\Controllers;

use App\Admin\Extensions\GoodsExcelExpoter;
use App\Admin\Extensions\MergeGoods;
use App\Admin\Extensions\UpGoods;
use App\GoodsPriceTemp;
use App\MallApi;
use App\GoodsList;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;
use Encore\Admin\Widgets\Tab;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\MessageBag;
use Maatwebsite\Excel\Facades\Excel;

class GoodsListController extends Controller
{
    use ModelForm;

    /**
     * Index interface.
     *
     * @return Content
     */

    //商品总库
    public function index($type)
    {
        //这里的type 相当与url_type  type = 0 代表商品总库
        return Admin::content(function (Content $content)use($type) {
            $content->header('商品库');

            $content->description('列表');

            $content->body($this->grid($type));
        });
    }


    //商品总库（某个url_type）
    public function allGoodsList($type){

    }


    //已上架商品
    public function upGoodsList($type){
        return Admin::content(function (Content $content)use($type) {

            $config = config('admin.url_type');
            $content->header($config[$type]['name'].'-已上架商品');
            $content->description('列表');

            $content->body($this->grid_other($type,1));
        });
    }


    //待上架商品
    public function loadGoodsList($type){
        return Admin::content(function (Content $content)use($type) {
            $config = config('admin.url_type');
            $content->header($config[$type]['name'].'-待上架商品');
            $content->description('列表');

            $content->body($this->grid_other($type,0));
        });
    }



    //已下架商品
    public function downGoodsList($type){
        return Admin::content(function (Content $content)use($type) {

            $config = config('admin.url_type');
            $content->header($config[$type]['name'].'-已下架商品');
            $content->description('列表');

            $content->body($this->grid_other($type,2));
        });
    }


    /**
     * Edit interface.
     *
     * @param $id
     * @return Content
     */
    public function editGoodsList($id)
    {
        return Admin::content(function (Content $content) use ($id) {

            $content->header('商品库');
            $content->description('维护');

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

            $content->header('商品库');
            $content->description('');

            $content->body($this->form());
        });
    }


    //价格维护
    protected function grid_update_price($type)
    {
        return Admin::grid(GoodsList::class, function (Grid $grid)use($type) {

            $grid->disableExport();
            $grid->filter(function($filter)use($type){
                $filter->expand();

                // 去掉默认的id过滤器
                $filter->disableIdFilter();
                $filter->where(function ($query) {

                    $query -> where('product_id','like',"{$this->input}");
                }, '商品id');
                $filter->where(function ($query) {

                    $query -> where('product_name','like',"%{$this->input}%");
                }, '商品名称');
            });
            $grid->disableCreateButton();
            $grid->id('ID')->sortable();
            $grid->product_id('商品id');
            $grid->product_name('商品名称')->display(function($value){
                return $value;
                //return str_limit($value,10);
            });
            $grid->class_name('商品类别');
            $grid -> column('a b c d 价格','a b c d 价格') -> display(function()use($type){
                $value = $this -> id;
                //查找 a b c d 的价格
                $price_info = DB::table('goods_price_temp') -> where([
                    'flag' => 0,
                    'goods_id' => $value,
                    'url_type' => $type
                ]) -> first();
                if($price_info){
                    return $price_info -> price_a.'---'.$price_info -> price_b.'---'.$price_info -> price_c.'---'.$price_info -> price_d;
                }else{
                    return '未设置';
                }
            });

            /*
            $grid->updated_at('修改时间')->display(function($value){
                return date('Y-m-d H:i',$value);
            });
            */

            $grid->actions(function ($actions)use($type) {
                $actions->disableDelete();
                $actions->disableEdit();
                $actions->disableView();

                $id = $actions->getKey();
                //通过此商品id 找到
                //此商品 是否在temp_price里
                $price_temp_info = DB::table('goods_price_temp') -> where([
                    'flag' => 0,
                    'goods_id' => $id,
                    'url_type' => $type
                ]) -> first();
                if($price_temp_info){
                    $actions->append('<a href="'.admin_url('goodsListPrice').'?id='.$price_temp_info -> id.'&need_edit=2'.'&type='.$type.'" style="margin-left:8px;"><i class="fa fa-cny"></i>配置</a>');
                }else{
                    $actions->append('<a href="'.admin_url('addGoodsListPrice').'?goods_id='.$id.'&type='.$type.'" style="margin-left:8px;"><i class="fa fa-cny"></i>配置</a>');
                }

                //商品修改功能
                //$actions->append('<a href="'.admin_url('editGoodsList').'/'.$id.'" style="margin-left:8px;" target="_blank"><i class="fa fa-edit"></i></a>');




            });


            $grid->tools(function ($tools)use($type) {
                $tools->batch(function ($batch)use($type) {
                    $batch->disableDelete();
                    //$batch->add('上架', new UpGoods(2,$type));
                    //$batch->add('下架', new UpGoods(1,$url_type));
                });
                //导入更新价格
                $import_url = admin_url('importUpdatePrice').'/'.$type;
                $tools->append('<a class="btn btn-sm btn-primary"  href="'.$import_url.'" ><i class="fa fa-arrow-down"></i>&nbsp;导入更新价格</a>');

                //


            });



        });
    }



    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid($type)
    {



        return Admin::grid(GoodsList::class, function (Grid $grid)use($type) {
            //$grid -> model() -> where('is_show',1);
            //$grid->disableRowSelector();
            $grid->actions(function ($actions)use($type) {
                $id = $actions->getKey();
                $actions->disableDelete();

                //只有商品总库 有编辑商品的功能
                $actions->disableEdit();
                if($type){
                    $actions->append('<a href="'.admin_url('goodsListPrice').'?id='.$id.'&type='.$type.'" style="margin-left:8px;"><i class="fa fa-cny"></i>配置</a>');
                }else{
                    $actions->append('<a href="'.admin_url('editGoodsList').'/'.$id.'" style="margin-left:8px;"><i class="fa fa-edit"></i></a>');
                }

            });
            //$grid->disableExport();
            $grid->exporter(new GoodsExcelExpoter());
            $grid->filter(function($filter)use($type){
                $filter->expand();

                // 去掉默认的id过滤器
                $filter->disableIdFilter();
                $filter->where(function ($query) {

                    $query -> where('product_id','like',"{$this->input}");
                }, '商品id');
                $filter->where(function ($query) {

                    $query -> where('product_name','like',"%{$this->input}%");
                }, '商品名称');

                //如果有事业部 才需要筛选状态
                /*
                if($type){
                    $filter->where(function ($query) {

                        $query -> where('product_name','like',"%{$this->input}%");
                    }, '状态');
                }
                */


            });
            $grid->disableCreateButton();
            $grid->id('ID')->sortable();
            $grid->product_id('商品id');
            $grid->product_name('商品名称')->display(function($value){
                return $value;
            });
            $grid->class_name('商品类别');
            //$grid->weight('商品重量')->editable();
            $grid->weight('商品重量');

            $grid->image('图片')->image(url('/').'/uploads/');

            if($type){
                $grid -> column('a b c d 价格','a b c d 价格') -> display(function()use($type){
                    $value = $this -> id;
                    //查找 a b c d 的价格
                    $price_info = DB::table('goods_price_temp') -> where([
                        'flag' => 0,
                        'goods_id' => $value,
                        'shiyebu' => $type
                    ]) -> first();
                    if($price_info){
                        return $price_info -> price_a.'---'.$price_info -> price_b.'---'.$price_info -> price_c.'---'.$price_info -> price_d;
                    }else{
                        return '未设置';
                    }
                });


                $grid -> column('状态','状态') -> display(function()use($type){
                    $value = $this -> id;
                    //查找 a b c d 的价格
                    $price_info = DB::table('goods_price_temp') -> where([
                        'flag' => 0,
                        'goods_id' => $value,
                        'shiyebu' => $type
                    ]) -> first();
                    if($price_info){
                        return $price_info -> status?'未上架':'已上架';
                    }else{
                        return '待上架';
                    }
                });



            }

            /*
            $grid->status('状态') -> display(function($value){
                $config_status = config('admin.GoodsListStatus');
                return $config_status[$value];
            });
            */


            $grid->updated_at('修改时间')->display(function($value){
                return date('Y-m-d H:i',$value);
            });
            $grid->tools(function ($tools)use($type) {
                $tools->batch(function ($batch)use($type) {
                    $batch->disableDelete();
                    $batch->add('上架', new UpGoods(2,$type));
                    $batch->add('下架', new UpGoods(1,$type));
                });
                if(!$type){
                    $url = admin_url('importGoods');
                    $tools->append('<a class="btn btn-sm btn-primary"  href="'.$url.'" ><i class="fa fa-arrow-down"></i>&nbsp;导入</a>');


                    //更新小程序 扫描结果
                    $refresh_url = admin_url('updateMpScanInfo');
                    $tools->append('<a class="btn btn-sm btn-primary"  href="'.$refresh_url.'" target="_blank" ><i class="fa fa-refresh"></i>&nbsp;更新小程序扫描</a>');


                }

            });

        });
    }



    //商品信息导入后  更新小程序扫描
    public function updateMpScanInfo(){
        $scan_goods_info = DB::table('mp_scan_goods_info')
            //-> where('product_id',0)
            //-> where('product_name','')
            -> get();

        foreach($scan_goods_info as $vo){
            $product_info = DB::table('goods_list')
                -> where([
                    'product_id' => trim($vo -> product_no)
                ]) -> first();
            if($product_info){
               DB::table('mp_scan_goods_info')
                   -> where([
                       'id' => $vo -> id
                   ]) -> update([
                       'product_id' => $product_info -> id,
                       'product_name' => $product_info -> product_name, //商品名称
                       'declare_price' => $product_info -> price, //价格
                       'taobao_url' => $product_info -> taobao_url, //淘宝链接,
                       'product_method' => $product_info -> product_method,
                       'product_format' => $product_info -> product_format,
                       'product_unit' => $product_info -> product_unit,
                       'english_name' => $product_info -> english_name,
                       'brand_name' => $product_info -> brand_name,
                   ]);
            }
        }

        echo '同步成功';


    }



    //已上架、待上架、已下架 商品 - url_type 角度
    protected function grid_other($url_type,$status)
    {
        return Admin::grid(GoodsPriceTemp::class, function (Grid $grid)use($status,$url_type) {


            $grid -> model() -> where('url_type',$url_type);
            $grid -> model() -> where('flag',0);
            $grid -> model() -> where('status',$status);
            //$grid->disableRowSelector();

            $grid->filter(function($filter){

                // 去掉默认的id过滤器
                $filter->disableIdFilter();
                $filter->where(function ($query) {
                    $input = trim($this->input);
                    //查找商品名称的id
                    $goods_info = DB::table('goods_list') -> where('product_id','like',$input) -> first();
                    if($goods_info){
                        $query -> where('goods_id','like',"{$goods_info -> id}");
                    }

                }, '商品id');
                $filter->where(function ($query) {
                    $input = trim($this->input);
                    //查找商品名称的id
                    $goods_info = DB::table('goods_list') -> where('product_name','like','%'.$input.'%') -> get();
                    $temp_goods_id = [];
                    if(count($goods_info)){
                        foreach($goods_info as $vo){
                            $temp_goods_id[] = $vo -> id;
                        }
                        $query -> whereIn('goods_id',$temp_goods_id);
                    }




                }, '商品名称');


            });


            $grid->actions(function ($actions)use($url_type,$status) {
                $actions->disableDelete();
                $actions->disableEdit();
                $actions->disableView();

                $id = $actions->getKey();

                if($status == 2){
                    //如果是下架  则给他上架的按钮
                    //$actions->append(new UpGoods(2,$url_type));
                }

                $actions->append('<a href="'.admin_url('goodsListPrice').'?id='.$id.'&type='.$url_type.'" style="margin-left:8px;"><i class="fa fa-cny"></i>配置</a>');
            });
            $grid->disableExport();

            $grid->disableCreateButton();
            $grid->id('ID')->sortable();
            $grid->goodslist()->product_id('商品id');
            $grid->goodslist()->product_name('商品名称')->display(function($value){
                return $value;
            });
            $grid->goodslist()->class_name('商品类别');
            $grid->price_s('s');
            $grid->price_a('a');
            $grid->price_b('b');
            $grid->price_c('c');
            $grid->price_d('d');
            $grid->goodslist()->image('图片')->image(url('/').'/uploads/');
            //$grid -> rows('<input type="hidden" id="url_type" value="'.$url_type.'" />');



            /*
            $grid->status('状态') -> display(function($value){
                $config_status = config('admin.GoodsListStatus');
                return $config_status[$value];
            });
            */


            $grid->updated_at('修改时间')->display(function($value){
                return date('Y-m-d H:i',$value);
            });
            $grid->tools(function ($tools)use($url_type,$status) {
                $tools->batch(function ($batch)use($url_type,$status) {
                    $batch->disableDelete();
                    //$batch->add('上架', new UpGoods(2,$url_type));
                    if($status == 2){
                        $batch->add('上架', new UpGoods(2,$url_type));
                    }
                    //$batch->add('发布文章', new MergeGoods());
                    //$batch->add('上架', new UpGoods(2,$type));
                    //$batch->add('下架', new UpGoods(1,$url_type));
                });
                //同步数据按钮
                //$url = admin_url('sameGoodsData').'/'.$url_type;
                $url = url('sameGoodsData').'/'.$url_type;
                $tools->append('<a class="btn btn-sm btn-primary"  href="'.$url.'"  target="_blank"><i class="fa fa-arrow-down"></i>&nbsp;同步数据</a>');

                //套餐商品
                //$tools->append(new MergeGoods());

                //$tools->append('<a class="btn btn-sm btn-success"  href="'.$url.'"  target="_blank">生成套餐</a>');

                //导入更新价格
                //$import_url = admin_url('importUpdatePrice').'/'.$url_type;
                //$tools->append('<a class="btn btn-sm btn-primary"  href="'.$import_url.'" ><i class="fa fa-arrow-down"></i>&nbsp;导入更新价格</a>');

            });



        });
    }


    //更新价格
    public function updatePriceGoodsList($type){

        return Admin::content(function (Content $content)use($type) {
            $config = config('admin.url_type');
            $content->header($config[$type]['name'].'-价格维护');
            $content->description('列表');

            $content->body($this->grid_update_price($type));
        });
    }

    //同步某url_type 的数据  转到Home/SameDataController
    public function sameGoodsData($type){


        $config = config('admin.url_type');
        $configs = $config[$type];

        $model = new MallApi();
        for($i=1;$i<=1000;$i++){
            $data = $model -> getGoodsList($i,15,[
                'Organization' => $configs['Organization'],
                'WarehouseName' => $configs['WarehouseName'],
            ]);

            //dd($data);
            //Log::info(print_r($data,true));
            if(!count($data)){
                echo '同步完毕';exit;
            }

            foreach($data as $vo){

                $isset = DB::table('goods_list') -> where([
                    'product_id' => $vo['ProductNo']
                ]) -> first();
                if(!$isset){
                    //如果都不在商品库里 则跳过
                    continue;
                }

                //在商品库
                //查下此Product_no在不在此url_type 中
                $is_price = DB::table('goods_price_temp') -> where([
                    'url_type' => $type,
                    'goods_id' => $isset -> id,
                    'flag' => 0
                ]) -> first();
                if(!$is_price){
                    //如果不存在  则加到待上架中
                    DB::table('goods_price_temp') -> insert([
                        'created_at' => time(),
                        'updated_at' => time(),
                        'url_type' => $type,
                        'goods_id' => $isset -> id,
                        'status' => 0,  //待上架状态
                    ]);
                }else{
                    //如果存在 检查下参数是否完整
                    if($is_price -> price_a && $is_price -> price_b && $is_price -> price_c && $is_price -> price_d && $isset -> weight){
                        //完整 更新为已上架
                        DB::table('goods_price_temp') -> where([
                            'url_type' => $type,
                            'goods_id' => $isset -> id,
                            'flag' => 0
                        ]) -> update([
                            'status' => 1,  //上架状态
                            'updated_at' => time(),
                        ]);
                    }else{
                        DB::table('goods_price_temp') -> where([
                            'url_type' => $type,
                            'goods_id' => $isset -> id,
                            'flag' => 0
                        ]) -> update([
                            'status' => 0,  //待上架状态
                            'updated_at' => time(),
                        ]);
                    }
                }




            }
            sleep(1);



        }




    }

    //导入价格更新
    public function importUpdatePrice($url_type){
        return Admin::content(function (Content $content)use($url_type) {
            $config = config('admin.url_type');
            $content->header($config[$url_type]['name'].'-更新价格');

            //$content->header('更新价格');
            $content->description('导入');

            $content->body($this->importUpdatePriceForm($url_type));
        });
    }


    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form($id = null)
    {
        return Admin::form(GoodsList::class, function (Form $form)use($id) {

            $form->display('id', 'ID');
            $form->image('image','商品图片')->uniqueName()->move('public/upload/images/')-> rules('required');
            //$form->multipleImage('image_detail', '配图');
            $form->multipleImage('image_detail', '配图')->removable();
            $form -> display('product_id','商品编号')->rules('required');
            $form -> display('product_name','商品名称');
            $form -> text('weight','重量');
            $form -> text('taobao_url','淘宝链接');
            $form -> text('price','价格');
            $form -> hidden('id','id');
            $form -> hidden('product_id','product_id');
            $goods_info = GoodsList::find($id);
            $images = DB::table('mpshop_product_images')
                -> where([
                    'product_no' => $goods_info -> product_id,
                    'flag' => 0
                ]) -> get();
            if(count($images)){
                foreach($images as $k => $vo){
                    $images[$k] -> url = getImageUrl($vo -> image);
                }

                $view = view('admin.product_images',compact('images')) -> render();
                $form -> html($view);
            }


            $form->tools(function (Form\Tools $tools) {

                // 去掉跳转列表按钮
                $tools->disableListButton();
                // 添加一个按钮, 参数可以是字符串, 或者实现了Renderable或Htmlable接口的对象实例
                $tools->add('<a class="btn btn-info btn-sm" onClick="javascript :history.back(-1);" ><i class="fa fa-backward"></i>&nbsp;&nbsp;返回</a>');
            });

            /*
            if($id){
                $info = GoodsList::find($id);
                $is_show = $info -> is_show;
            }else{
                $is_show = 1;
            }
            $form -> display('status','状态') -> with(function($value){
                $config_status = config('admin.GoodsListStatus');
                return $config_status[$value];
            });
            */
            $form -> setAction(admin_url('editGoodsListRes'));

            //$form->radio('is_show','状态')->options(['0' => '下架', '1'=> '上架'])->default($is_show);
            //$form->display('created_at', 'Created At');
            //$form->display('updated_at', 'Updated At');
            /*
            $form->saving(function(Form $form){
                $form->product_id = trim($form->product_id);
                //看这个product_id 是否存在了多次
                $count_id = DB::table('goods_list') -> where([
                    'product_id' =>$form->product_id
                ]) -> count();
                //dd($count_id);
                //编辑状态
                if($form->model()->id){
                    if($count_id > 1){
                        //admin_toastr('商品编号重复');
                        $error = new MessageBag([
                            'title'   => '商品编号重复',
                            'message' => '',
                        ]);

                        return back()->with(compact('error'));
                    }
                }else{
                    if($count_id >= 1){
                        //admin_toastr('商品编号重复');
                        $error = new MessageBag([
                            'title'   => '商品编号重复',
                            'message' => '',
                        ]);

                        return back()->with(compact('error'));
                    }
                }


            });
            */



        });
    }

    //删除多图
    public function deleteProductImage(){
        DB::table('mpshop_product_images') -> where([
            'id' => $_GET['id']
        ]) -> update([
            'flag' => 1
        ]);
        echo '删除成功';
    }


    //编辑商品处理
    public function editGoodsListRes(){
        if(isset($_FILES['image']['tmp_name']) && $_FILES['image']['tmp_name']){
            $file_name = uniqid('image').'.jpg';
            file_put_contents(public_path('uploads/images').'/'.$file_name,file_get_contents($_FILES['image']['tmp_name']));
            DB::table('goods_list') -> where([
                'id' => $_POST['id']
            ]) -> update([
                'image' => 'images/'.$file_name,
                'updated_at' => time()
            ]);
        }

        if(isset($_FILES['image_detail']['tmp_name'][0]) && $_FILES['image_detail']['tmp_name'][0]){
            foreach($_FILES['image_detail']['tmp_name'] as  $vo){
                $file_name = uniqid('mp_image').'.jpg';
                file_put_contents(public_path('uploads/images').'/'.$file_name,file_get_contents($vo));
                DB::table('mpshop_product_images') -> insertGetId([
                    'product_no' => trim($_POST['product_id']),
                    'image' => $file_name,
                    'created_at' => time(),
                    'updated_at' => time(),
                ]);
            }
        }

        DB::table('goods_list') -> where([
            'id' => $_POST['id']
        ]) -> update([
            'weight' => $_POST['weight'],
            'updated_at' => time()
        ]);




        admin_toastr('修改成功 ');

        return redirect(admin_base_path('goodsList').'/0');
    }





    //excel 导入商品
    public function importGoods(){
        return Admin::content(function (Content $content) {

            $content->header('商品管理');
            $content->description('导入');

            $content->body($this->importForm());
        });
    }

    protected function importForm(){
        return Admin::form(GoodsList::class, function (Form $form){
            $form->tools(function (Form\Tools $tools) {

                // 去掉跳转列表按钮
                $tools->disableListButton();
                // 添加一个按钮, 参数可以是字符串, 或者实现了Renderable或Htmlable接口的对象实例
                $tools->add('<a class="btn btn-info btn-sm" onClick="javascript :history.back(-1);" ><i class="fa fa-backward"></i>&nbsp;&nbsp;返回</a>');
                $downurl = '"'.url('download_product_list').'"';
                $tools->add('<a class="btn btn-sm btn-danger" onclick=\'window.open('.$downurl.')\'><i class="fa fa-arrow-down"></i>&nbsp;&nbsp;下载模版</a>');
            });
            $form->file('file_list','商品列表')->rules('required');;
            $form -> setAction(admin_base_path('importGoodsRes'));

        });
    }


    //导入更新价格模板
    protected function importUpdatePriceForm($url_type){
        return Admin::form(GoodsList::class, function (Form $form)use($url_type){
            $form->tools(function (Form\Tools $tools) {

                // 去掉跳转列表按钮
                $tools->disableListButton();
                // 添加一个按钮, 参数可以是字符串, 或者实现了Renderable或Htmlable接口的对象实例
                $tools->add('<a class="btn btn-info btn-sm" onClick="javascript :history.back(-1);" ><i class="fa fa-backward"></i>&nbsp;&nbsp;返回</a>');
                $downurl = '"'.url('download_update_price').'"';
                $tools->add('<a class="btn btn-sm btn-danger" onclick=\'window.open('.$downurl.')\'><i class="fa fa-arrow-down"></i>&nbsp;&nbsp;下载模版</a>');
            });
            $form->file('file_list','更新价格模板')->rules('required');;

            $form -> setAction(admin_base_path('importUpdatePriceRes').'?url_type='.$url_type);

        });
    }

    //导入更新价格模板
    public function importUpdatePriceRes(){
        $filePath = $_FILES['file_list']['tmp_name'];
        Excel::load($filePath, function($reader) use( &$res ) {
            $reader = $reader->getSheet(0);
            $res = $reader->toArray();
        });

        $url_type = $_GET['url_type'];

        $zong = 0;
        foreach($res as $key => $vo){
            //忽略第一行 表头
            if($key == 0){
                continue;
            }

            //$vo['0'] 商品编号  a b c d
            if($vo['0'] && $vo['1'] && $vo['2'] && $vo['3'] && $vo['4']){
                $isset = DB::table('goods_list') -> where([
                    'product_id' => $vo['0']
                ]) -> first();
                //如果不存在 则忽略
                if(!$isset){
                    continue;
                }
                //存在的话。去更新price_temp
                $res = DB::table('goods_price_temp') -> where([
                    'flag' => 0,
                    'url_type' => $url_type,
                    'goods_id' => $isset -> id
                ]) -> update([
                    'price_a' => floatval($vo[1]),
                    'price_b' => floatval($vo[2]),
                    'price_c' => floatval($vo[3]),
                    'price_d' => floatval($vo[4]),
                    'updated_at' => time()
                ]);
                if($res){
                    $zong ++ ;
                }


            }
        }

        admin_toastr('导入成功 '.$zong.'条数据');

        return redirect(admin_base_path('loadGoodsList').'/'.$url_type);

    }


    public function importGoodsRes(){
        $filePath = $_FILES['file_list']['tmp_name'];
        Excel::load($filePath, function($reader) use( &$res ) {
            $reader = $reader->getSheet(0);
            $res = $reader->toArray();
        });

        $zong = 0;
        foreach($res as $key => $vo){
            //忽略第一行 表头
            if($key == 0){
                continue;
            }
            //dd($vo);
            //$vo['0'] 商品编号  $vo['1'] 类别 $vo['4'] 品名 && $vo['19'] 重量 （非必填）
            if($vo['0'] && $vo['4']){
                $isset = DB::table('goods_list') -> where([
                    'product_id' => $vo['0']
                ]) -> first();
                $zong ++;
                if(!$isset){

                    DB::table('goods_list') -> insert([
                        'product_id' => $vo['0'],
                        'product_name' => $vo['4'],
                        'class_name' => $vo['1'],
                        'weight' => $vo['19'],
                        'taobao_url' => $vo['20'],
                        'price' => $vo['21'],
                        'product_method' => $vo['22'],
                        'product_format' => $vo['23'],
                        'product_unit' => $vo['24'],
                        'english_name' => $vo['25'],
                        'brand_name' => $vo['26'],
                        'created_at' => time(),
                        'updated_at' => time(),
                    ]);
                }else{
                    //存在的话 就去更新
                    DB::table('goods_list') -> where([
                        'product_id' => (string)$vo['0']
                    ]) -> update([
                        'product_name' => $vo['4'],
                        'class_name' => $vo['1'],
                        //'weight' => $vo['19'],
                        'updated_at' => time(),
                    ]);
                    if($vo['19']){
                        DB::table('goods_list') -> where([
                            'product_id' => $vo['0']
                        ]) -> update([
                            'weight' => $vo['19'],
                        ]);
                    }

                    if($vo['20']){
                        //淘宝链接
                        DB::table('goods_list') -> where([
                            'product_id' => $vo['0']
                        ]) -> update([
                            'taobao_url' => $vo['20'],
                        ]);
                    }

                    if($vo['21']){
                        //价格
                        DB::table('goods_list') -> where([
                            'product_id' => $vo['0']
                        ]) -> update([
                            'price' => $vo['21'],
                        ]);
                    }
                    //product_method
                    if($vo['22']){
                        DB::table('goods_list') -> where([
                            'product_id' => $vo['0']
                        ]) -> update([
                            'product_method' => $vo['22'],
                        ]);
                    }
                    //product_format
                    if($vo['23']){
                        DB::table('goods_list') -> where([
                            'product_id' => $vo['0']
                        ]) -> update([
                            'product_format' => $vo['23'],
                        ]);
                    }
                    //单位 product_unit
                    if($vo['24']){
                        DB::table('goods_list') -> where([
                            'product_id' => $vo['0']
                        ]) -> update([
                            'product_unit' => $vo['24'],
                        ]);
                    }
                    //英文名称
                    if($vo['25']){
                        DB::table('goods_list') -> where([
                            'product_id' => $vo['0']
                        ]) -> update([
                            'english_name' => $vo['25'],
                        ]);
                    }
                    //品牌
                    if($vo['26']){
                        DB::table('goods_list') -> where([
                            'product_id' => $vo['0']
                        ]) -> update([
                            'brand_name' => $vo['26'],
                        ]);
                    }



                }
            }
        }

        admin_toastr('导入成功 '.$zong.'条数据');

        return redirect(admin_base_path('goodsList/0'));

    }



    //上下架
    public function upGoods(){
        if($_POST['action'] == 1){
            //下架
            $status = 2;
        }else{
            //上架操作
            $status = 1;
        }



        foreach($_POST['ids'] as $vo){
            //价格 和 图片 都维护好 才可以上架
            if($status == 1){
                $goods_info = DB::table('goods_price_temp') -> where([
                    'id' => $vo,
                    //'url_type' => $_POST['url_type'],
                    'flag' => 0
                ]) -> first();
                if(!$goods_info || !$goods_info -> price_a || !$goods_info -> price_b || !$goods_info -> price_c || !$goods_info -> price_d ){
                    continue;
                }
            }


            DB::table('goods_price_temp') -> where([
                'id' => $vo,
                //'url_type' => $_POST['url_type'],
                'flag' => 0
            ]) -> update([
                'status' => $status
            ]);
        }
    }


    //价格设置
    public function goodsListPrice(){
        $id = $_GET['id'];
        $type = $_GET['type'];
        $need_edit = isset($_GET['need_edit'])?$_GET['need_edit']:1;

        //这个type 是 url_type
        return Admin::content(function (Content $content)use($id,$type,$need_edit) {
            $config = config('admin.url_type');

            $content->header($config[$type]['name'].'商品库');
            $content->description('价格配置');

            $content->body($this->priceTemp($id,$type,'',$need_edit) -> edit($id));
        });
    }
    //价格设置（goods_price_temp 里不存在的时候）
    public function addGoodsListPrice(){
        $type = $_GET['type'];
        $goods_id = $_GET['goods_id'];
        //这个type 是 url_type
        return Admin::content(function (Content $content)use($type,$goods_id) {
            $config = config('admin.url_type');

            $content->header($config[$type]['name'].'商品库');
            $content->description('价格配置');

            $content->body($this->priceTemp('',$type,$goods_id));
        });
    }




    public function priceTemp($id,$type,$goods_id=0,$need_edit=1){
        return Admin::form(GoodsPriceTemp::class, function (Form $form)use($id,$type,$goods_id,$need_edit){
            if($id){
                //要区分 价格维护。


                //查找price_temp 里 此商品的价格 以table展示'
                $price_temp = DB::table('goods_price_temp') -> where([
                    'id' => $id,
                    'url_type' => $type,
                    'flag' => 0
                ]) -> first();
                //dd($price_temp);
                $compact['price_temp'] = $price_temp;
                $compact['id'] = $id;
                $compact['url_type'] = $type;
                $compact['goods_id'] = $price_temp -> goods_id;
                $goods_info = DB::table('goods_list') -> where([
                    'id' => $price_temp -> goods_id
                ]) -> first();

                $image = isset($goods_info -> image)?$goods_info -> image:'';

                //dd($compact['price_temp']);

                $form -> setAction(admin_url('goodsPriceTempRes'));
            }else{

                //这里 肯定是价格维护里的。从来没有维护过


                $compact['price_temp'] = [];
                $compact['id'] = $id;
                $compact['url_type'] = $type;
                $compact['goods_id'] = $goods_id;
                $form -> setAction(admin_url('goodsPriceTempRes'));
                $goods_info = DB::table('goods_list') -> where([
                    'id' => $goods_id
                ]) -> first();

                $image = isset($goods_info -> image)?$goods_info -> image:'';
            }


            //$compact['goods_info'] = $goods_info;

            if($need_edit == 1){
                $form->image('goods_img','商品图片');
                /*
                $form->column('商品图片') -> display(function($value){
                    return $this -> id;
                });
                */




                $form->html('<img src="'.asset('uploads/'.$image).'"  style="width:220px;height:220px;" />', '目前商品图片');
                $form -> text('weight','重量')->default($goods_info -> weight);
            }







            $form->tools(function (Form\Tools $tools) {

                // 去掉跳转列表按钮
                $tools->disableListButton();
                // 添加一个按钮, 参数可以是字符串, 或者实现了Renderable或Htmlable接口的对象实例
                $tools->add('<a class="btn btn-info btn-sm" onClick="javascript :history.back(-1);" ><i class="fa fa-backward"></i>&nbsp;&nbsp;返回</a>');
            });

            $view = view('admin.goods_list_price', compact('compact'))->render();
            $form->html($view) -> setWidth(12);


        });
    }

    public function goodsPriceTempRes(){
        //先看下此商品 此事业部 有没有定价
        /*
        $isset = DB::table('goods_price_temp') -> where([
            'url_type' => $_POST['url_type'],
            'id' => $_POST['id'],
            'flag' => 0
        ]) -> first();
        */


        if(isset($_FILES['goods_img']['tmp_name']) && $_FILES['goods_img']['tmp_name'] ){
            $file_name = uniqid('image').'.jpg';
            file_put_contents(public_path('uploads/images').'/'.$file_name,file_get_contents($_FILES['goods_img']['tmp_name']));
            //更新图片
            DB::table('goods_list') -> where([
                'id' => $_POST['goods_id']
            ]) -> update([
                'image' => 'images/'.$file_name,
                'updated_at' => time()
            ]);
        }
        //dd($_POST);
        if(isset($_POST['weight']) && $_POST['weight']){
            DB::table('goods_list') -> where([
                'id' => $_POST['goods_id']
            ]) -> update([
                'weight' => floatval($_POST['weight']),
                'updated_at' => time()
            ]);
        }

        $goods_info = DB::table('goods_list') -> where([
            'id' => $_POST['goods_id']
        ]) -> first();





        if($_POST['id']){

            $price_temp_id = $_POST['id'];
            DB::table('goods_price_temp') -> where([
                'url_type' => $_POST['url_type'],
                'id' => $_POST['id'],
                'flag' => 0
            ]) -> update([
                'price_s' => $_POST['price_s'],
                'price_a' => $_POST['price_a'],
                'price_b' => $_POST['price_b'],
                'price_c' => $_POST['price_c'],
                'price_d' => $_POST['price_d'],

                's_price_a' => $_POST['s_price_a']?$_POST['s_price_a']:0,
                's_price_b' => $_POST['s_price_b']?$_POST['s_price_b']:0,
                's_price_c' => $_POST['s_price_c']?$_POST['s_price_c']:0,
                's_price_d' => $_POST['s_price_d']?$_POST['s_price_d']:0,

                's_starttime' => $_POST['startTime']?strtotime($_POST['startTime']):'',
                's_endtime' => $_POST['endTime']?strtotime($_POST['endTime']):'',


                'status' => 0,
                'updated_at' => time(),
            ]);




        }else{
            $price_temp_id = DB::table('goods_price_temp') -> insertGetId([
                'url_type' => $_POST['url_type'],
                'goods_id' => $_POST['goods_id'],
                'price_s' => $_POST['price_s'],
                'price_a' => $_POST['price_a'],
                'price_b' => $_POST['price_b'],
                'price_c' => $_POST['price_c'],
                'price_d' => $_POST['price_d'],
                'status' => 0,
                'updated_at' => time(),
            ]);



        }


        //判断price_a price_b price_c price_d weight image 都存在 才可以上架
        if($_POST['price_a'] && $_POST['price_b'] && $_POST['price_c'] && $_POST['price_d'] &&  $goods_info -> image && $goods_info -> weight ){
            DB::table('goods_price_temp') -> where([
                'id' => $price_temp_id
            ]) -> update([
                'status' => 1,
                'updated_at' => time(),
            ]);
        }


        admin_toastr('上架成功');

        return redirect(admin_base_path('loadGoodsList').'/'.$_POST['url_type']);
    }


}
