<?php

namespace App\Admin\Controllers;

use App\Admin\Extensions\MergeDelete;
use App\MallApi;
use App\MallImage;

use App\MallImageA;
use App\MergeGoods;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Facades\Log;

class MergeGoodsController extends Controller
{
    use ModelForm;

    /**
     * Index interface.
     *
     * @return Content
     */
    public function index()
    {
        $url_type = $_GET['url_type'];
        $merge_goods_id = isset($_GET['merge_goods_id'])?$_GET['merge_goods_id']:0;
        return Admin::content(function (Content $content)use($url_type,$merge_goods_id) {
            $config_type = config('admin.url_type')[$url_type];
            $content->header($config_type['name'].'-套餐商品');
            $content->description('合并');
            $content->body($this -> form($url_type,$merge_goods_id));
            //$content->body($this->mergeGoods());
        });
    }

    public function mergeGoodsList(){
        $url_type = $_GET['url_type'];
        return Admin::content(function (Content $content)use($url_type) {
            $config_type = config('admin.url_type')[$url_type];
            $content->header($config_type['name'].'-套餐商品');
            $content->description('列表');
            $content->body($this -> grid($url_type));
            //$content->body($this->mergeGoods());
        });
    }


    public function form($url_type,$merge_goods_id){
        return Admin::form(MergeGoods::class, function (Form $form)use($url_type,$merge_goods_id){
            $form -> setAction(admin_base_path('mergeGoodsRes'));
            if($merge_goods_id){
                $merge_goods_info = DB::table('merge_goods') -> where([
                    'id' => $merge_goods_id
                ]) -> first();
                $merge_goods_detail = DB::table('merge_goods_detail') -> where([
                    'merge_goods_id' => $merge_goods_id
                ]) -> get();

            }else{
                $merge_goods_detail = null;
                $merge_goods_info = null;
            }



            $compact = [
                'url_type' => $url_type,
                'merge_goods_info' => $merge_goods_info,
                'merge_goods_detail' => $merge_goods_detail,
                'tr_num' => $merge_goods_detail?count($merge_goods_detail):1
            ];
            $form->tools(function (Form\Tools $tools) {

                // 去掉返回按钮
                $tools->disableBackButton();

                // 去掉跳转列表按钮
                $tools->disableListButton();
                $tools->add('<a class="btn btn-sm btn-danger" id="addTr"><i class="fa fa-arrows"></i>&nbsp;&nbsp;增加</a>');

                $tools->add('<a class="btn btn-info btn-sm" onClick="javascript :history.back(-1);" ><i class="fa fa-backward"></i>&nbsp;&nbsp;返回</a>');
            });

            $table = view('admin.mergeGoods',compact('compact')) -> render();
            $form -> html($table)->setWidth(12);

        });
    }


    //生成套餐商品前 ajax
    public function mergeGoodsAjax(){
        //var_dump($_POST);exit;
        //根据 product_no_str 看 在对应的 url_type 中 是否上架
        foreach($_POST['product_no_str'] as $vo){
            $goods_info = DB::table('goods_list') -> where([
                'product_id' => trim($vo)
            ]) -> first();
            if(!$goods_info){
                return [
                    'code' => 'no_goods',
                    'result' => trim($vo)
                ];
            }


            $is_up = DB::table('goods_price_temp') -> where([
                'goods_id' => $goods_info -> id,
                'url_type' => $_POST['url_type'],
                'status' => 1
            ]) -> first();

            if(!$is_up){
                /*
                //没有上架是不行的
                return [
                    'code' => 'no_up',
                    'result' => trim($vo)
                ];
                */
            }

        }

        return [
            'code' => 'success',
            'result' => ''
        ];


    }


    //生成套餐商品
    public function mergeGoodsRes(){
        //直接生成
        //先计算 总的价格
        $price_s_all = 0;
        $price_a_all = 0;
        $price_b_all = 0;
        $price_c_all = 0;
        $price_d_all = 0;
        foreach($_POST['product_no'] as $k => $vo){
            $price_s_all += $_POST['numbers'][$k] *  $_POST['price_s'][$k];
            $price_a_all += $_POST['numbers'][$k] *  $_POST['price_a'][$k];
            $price_b_all += $_POST['numbers'][$k] *  $_POST['price_b'][$k];
            $price_c_all += $_POST['numbers'][$k] *  $_POST['price_c'][$k];
            $price_d_all += $_POST['numbers'][$k] *  $_POST['price_d'][$k];
        }

        if(!$_POST['merge_goods_id']){
            //dd($_POST);
            $new_goods_id = DB::table('merge_goods') -> insertGetId([
                'url_type' => $_POST['url_type'],
                'product_no' => 'M'.$_POST['product_all_no'],
                'product_name' => $_POST['product_name'],
                'weight' => $_POST['weight'],
                'can_on_sale' => $_POST['can_on_sale'],
                'price_s' => $price_s_all,
                'price_a' => $price_a_all,
                'price_b' => $price_b_all,
                'price_c' => $price_c_all,
                'price_d' => $price_d_all,
                'flag' => 0,
                'created_at' => time(),
                'updated_at' => time(),
            ]);

            foreach($_POST['product_no'] as $k => $vo){
                DB::table('merge_goods_detail') -> insert([
                    'url_type' => $_POST['url_type'],
                    'product_no' => trim($vo),
                    'price_s' => $_POST['price_s'][$k],
                    'price_a' => $_POST['price_a'][$k],
                    'price_b' => $_POST['price_b'][$k],
                    'price_c' => $_POST['price_c'][$k],
                    'price_d' => $_POST['price_d'][$k],
                    'number' => $_POST['numbers'][$k],
                    'merge_goods_id' => $new_goods_id,
                    'created_at' => time(),
                    'updated_at' => time(),
                ]);
            }


            if(isset($_FILES['image']['tmp_name']) && $_FILES['image']['tmp_name']){
                $file_name = uniqid('image').'.jpg';
                file_put_contents(public_path('uploads/images').'/'.$file_name,file_get_contents($_FILES['image']['tmp_name']));
                DB::table('merge_goods') -> where([
                    'id' => $new_goods_id
                ]) -> update([
                    'image' => 'images/'.$file_name,
                    'updated_at' => time()
                ]);
            }



            admin_toastr('添加套餐商品成功');

            return redirect(admin_base_path('mergeGoodsList').'?url_type='.$_POST['url_type']);
        }else{
            //直接修改
            DB::table('merge_goods') ->where([
                'id' => $_POST['merge_goods_id']
            ]) ->  update([
                //'url_type' => $_POST['url_type'],
                'product_no' => $_POST['product_all_no'],
                'product_name' => $_POST['product_name'],
                'can_on_sale' => $_POST['can_on_sale'],
                'weight' => $_POST['weight'],
                'price_s' => $price_s_all,
                'price_a' => $price_a_all,
                'price_b' => $price_b_all,
                'price_c' => $price_c_all,
                'price_d' => $price_d_all,
                'flag' => 0,
                'updated_at' => time(),
            ]);

            //全部删掉 再增加
            DB::table('merge_goods_detail') -> where([
                'merge_goods_id' => $_POST['merge_goods_id'],
            ]) -> delete();


            foreach($_POST['product_no'] as $k => $vo){
                DB::table('merge_goods_detail') -> insert([
                    'url_type' => $_POST['url_type'],
                    'product_no' => trim($vo),
                    'price_s' => $_POST['price_s'][$k],
                    'price_a' => $_POST['price_a'][$k],
                    'price_b' => $_POST['price_b'][$k],
                    'price_c' => $_POST['price_c'][$k],
                    'price_d' => $_POST['price_d'][$k],
                    'number' => $_POST['numbers'][$k],
                    'merge_goods_id' => $_POST['merge_goods_id'],
                    'created_at' => time(),
                    'updated_at' => time(),
                ]);
            }


            if(isset($_FILES['image']['tmp_name']) && $_FILES['image']['tmp_name']){
                $file_name = uniqid('image').'.jpg';
                file_put_contents(public_path('uploads/images').'/'.$file_name,file_get_contents($_FILES['image']['tmp_name']));
                DB::table('merge_goods') -> where([
                    'id' => $_POST['merge_goods_id']
                ]) -> update([
                    'image' => 'images/'.$file_name,
                    'updated_at' => time()
                ]);
            }


            return redirect(admin_base_path('mergeGoodsList').'?url_type='.$_POST['url_type']);


        }








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

            $content->header('商品维护');
            $content->description('');

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

            $content->header('商品维护');
            $content->description('');

            $content->body($this->form());
        });
    }


    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid($url_type)
    {
        return Admin::grid(MergeGoods::class, function (Grid $grid)use($url_type) {
            $grid -> model() -> where('url_type',$url_type);
            $grid -> model() -> where('flag',0);
            $grid->disableRowSelector();
            $grid->actions(function ($actions) {
                $id = $actions->getKey();
                $actions->disableDelete();
            });
            $grid->disableExport();
            $grid->filter(function($filter){

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
            $grid->product_no('商品id');
            $grid->product_name('商品名称');
            $grid->weight('商品重量');
            $grid->can_on_sale('是否允许单卖') -> display(function($value){
                if($value){
                    return '不允许';
                } else{
                    return '允许';
                }
            });
            $grid->image('图片')->image(url('/').'/uploads/');
            $grid->price_s('s');
            $grid->price_a('a');
            $grid->price_b('b');
            $grid->price_c('c');
            $grid->price_d('d');
            $grid -> status('是否上架') -> display(function($value){
               if($value){
                   return '上架';
               } else{
                   return '未上架';
               }
            });

            $grid->tools(function ($tools)use($url_type) {
                $tools->append(new \App\Admin\Extensions\MergeGoods($url_type));
            });

            $grid->updated_at('修改时间')->display(function($value){
                return date('Y-m-d H:i',$value);
            });
            $grid->actions(function ($actions)use($url_type) {
                $id = $actions->getKey();
                $actions->disableDelete();
                $actions->disableEdit();
                $actions->append('<a href="'.admin_url('mergeGoods').'?url_type='.$url_type.'&merge_goods_id='.$id.'" style="margin-left:10px;" ><i class="fa fa-eye"></i></a>');

                $actions -> append(new MergeDelete($id));
                //$actions->disableDelete();
            });
        });
    }


    public function deleteMerge(){
        //删除此id
        DB::table('merge_goods') -> where([
            'id' => $_POST['id']
        ]) -> update([
            'flag' => 1
        ]);

        DB::table('merge_goods_detail') -> where([
            'merge_goods_id' => $_POST['id']
        ]) -> update([
            'flag' => 1
        ]);
    }


}
