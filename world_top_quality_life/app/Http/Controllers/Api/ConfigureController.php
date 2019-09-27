<?php

namespace App\Http\Controllers\Api;

use App\Brand;
use App\Configure;
use App\Port;
use App\Storehouse;
use App\Supplier;
use App\WareHouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Repositories\MpNameRepository;

class ConfigureController extends Controller
{

    public function __construct(MpNameRepository $mpNameRepository)
    {
        $this->mpNameRepository = $mpNameRepository;
    }
    //币种
    public function currency(){
        $arr = Configure::getCurrency();
        return Configure::dealArray($arr);
        //return Configure::getCurrency();
    }
    //属性
    public function attribute(){
        return Configure::dealArray(Configure::getAttribute());
    }
    //支付方式
    public function payMethod(){
        $type = config('admin.stock_order_pay_method');
        $arr = [];
        foreach ($type as $k=>$v){
            $arr[] = [
                'value'  => $k,
                'name'=> $v
            ];
        }
        return $arr;
    }
    public function priceLogType(){
        return config('admin.price_log_type');
    }

    //运费
    public function freightType(){
        return Configure::dealArray(Configure::freightType());
    }

    //港口物流单号，订单状态
    public function getLogisticsStatus(){
        return Configure::dealCalssArray(Configure::getLogisticsStatus());
    }

    //商品类别
    public function productClass(){
        $class1 = DB::table('erp_product_class') -> where('fid',0) -> orderBy('name','asc') -> get();
        $temp = [];
        foreach($class1 as $k => $vo){
            $temp[$k]['value'] = $vo -> id;
            $temp[$k]['label'] = $vo -> name;
            $temp[$k]['level'] = 0;
            $temp[$k]['next_level'] = 2;
            $temp2 = [];
            $temp2_arr = DB::table('erp_product_class') -> where('fid',$vo -> id)-> orderBy('name','asc') -> get();
            foreach($temp2_arr as $key => $value){
                $temp2[$key]['value'] = $value -> id;
                $temp2[$key]['label'] = $value -> name;
                $temp2[$key]['level'] = 2;
                $temp2[$key]['next_level'] = 1;

                $temp3 = [];
                $temp3_arr = DB::table('erp_product_class') -> where('fid',$value -> id) -> orderBy('name','asc')-> get();
                foreach($temp3_arr as $key3 => $value3){
                    $temp3[$key3]['value'] = $value3 -> id;
                    $temp3[$key3]['label'] = $value3 -> name;
                    $temp3[$key3]['level'] = 1;
                    //$temp3[$key3]['next_level'] = 1;
                }
                $temp2[$key]['children'] = $temp3;


            }


            $temp[$k]['children'] = $temp2;

        }
        return $temp;
        //return Configure::getProductClass($request -> fid);
    }



    //添加商品类别、品牌、系列
    public function addProductClass(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'type' => 'required',  //type 0类别 2品牌 1系列
            'fid' => 'required', //fid 类别的fid = 0
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $data = DB::table('erp_product_class') -> where([
            'fid' => $request -> fid,
            'name' => trim($request -> name)
        ]) -> first();
        if($data){
            return [
                'code' => 500,
                'msg' => '已存在'
            ];
        }else{
            DB::table('erp_product_class') -> insertGetId([
                'type_id' => $request -> type,
                'fid' => $request -> fid,
                'name' => trim($request -> name),
                'created_at' => time(),
                'updated_at' => time(),
            ]);
            return [
                'code' => 200,
                'msg' => '添加成功'
            ];
        }
    }

    //修改商品类别、品牌、系列
    public function editProductClass(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'id' => 'required',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $data = DB::table('erp_product_class') -> where([
            'id' => $request -> id,
            'flag' => 0
        ]) -> first();
        if(!$data){
            return [
                'code' => 500,
                'msg' => '没有此数据'
            ];
        }else{
            $count = DB::table('erp_product_class') -> where([
                'name' => trim($request -> name),
                'fid' => $data -> fid,
                'flag' => 0
            ]) -> count();
            if($count == 1){
                //改重名了
                return [
                    'code' => 500,
                    'msg' => '已存在'
                ];
            }

            DB::table('erp_product_class') -> where([
                'id' => $request -> id
            ]) -> update([
                'name' => trim($request -> name),
            ]);

            return [
                'code' => 200,
                'msg' => '修改成功'
            ];
        }
    }


    //采购类型
    public function purchaseType(){
        return Configure::dealArray(Configure::purchaseType());
    }



    //增加供应商
    public function addSupplier(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'describe' => 'max:255',
            'business_id' => 'required|numeric|exists:erp_business,id'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }


        //查看这个 是否重复
        $info = Supplier::where([
            'flag' => 0,
            'business_id' => $request -> business_id,
            'name' => trim($request -> name)
        ]) -> first();
        if($info){
            return [
                'code' => 500,
                'msg' => '名字重复'
            ];
        }

        Supplier::insert([
            'name' => trim($request -> name),
            'describe' => trim($request -> describe),
            'business_id' => $request -> business_id,
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        return [
            'code' => 200,
            'msg' => '添加成功'
        ];
    }


    //供应商列表
    public function supplierList(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }


        $list = Supplier::where(function($query)use($request){
                $query -> where([
                    'business_id' => $request -> business_id,
                    'flag' => 0
                ]);

                if($request -> name){
                    $query -> where('name','like','%'.$request -> name.'%');
                }
            })
            -> get();


        if($request -> return_type == 'option'){
            return Configure::dealCalssArray($list);
        }

        return $list;
    }

    //供应商编辑
    public function editSupplier(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'describe' => 'max:255',
            'id' => 'required|numeric|exists:erp_supplier,id'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $info = Supplier::find($request -> id);
        if(!$info){
            return [
                'code' => 500,
                'msg' => '没有此条数据'
            ];
        }

        Supplier::where('id',$request -> id) -> update([
            'name' => isset($request -> name)?$request -> name:$info -> name,
            'describe' => isset($request -> describe)?$request -> describe:$info -> describe,
        ]);

        return [
            'code' => 200,
            'msg' => '编辑成功'
        ];

    }

    public function supplierInfo(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|numeric|exists:erp_supplier,id'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $info = Supplier::find($request -> id);
        return $info;
    }

    //删除供应商
    public function deleteSupplier(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|numeric|exists:erp_supplier,id'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $info = Supplier::find($request -> id);
        if($info -> flag){
            return [
                'code' => 200,
                'msg' => '已删除'
            ];
        }

        //查看下此供应商 有没有采购单
        $purchase_order = DB::table('erp_purchase_order')
            -> where([
                'supplier_id' => $request -> id,
                'flag' => 0
            ]) -> first();
        if($purchase_order){
            return [
                'code' => 500,
                'msg' => '此供应商存在采购单，不允许删除'
            ];
        }


        //删除
        Supplier::where([
            'id' => $request -> id
        ]) -> update([
            'updated_at' => time(),
            'flag' => 1
        ]);

        return [
            'code' => 200,
            'msg' => '删除成功'
        ];
    }

    //上传仓库图片
    public function uploadImage(Request $request){
        $validator = Validator::make($request->all(), [
               //'image' => 'required|image|max:10'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $newFileName = md5(time().rand(10000,99999)).'.jpg';
        \Illuminate\Support\Facades\Log::info(json_encode($_FILES));

        $is_move = file_put_contents(public_path().'/uploads/images/'.$newFileName,file_get_contents($_FILES['image']['tmp_name']));

        //$is_move = move_uploaded_file($request -> image,public_path().'/uploads/images/'.$newFileName);
        if(!$is_move){
            return [
                'code' => 500,
                'msg' => '上传失败'
            ];
        }else{
            return [
                'code' => 200,
                'file_name' => $newFileName,
                'url' => getImageUrl($newFileName)
            ];
        }
    }

    //查询运费模板作为标签显示
    public function theFreightOfTagsList(Request $request){
        if($request->freight_name){
            $where[] = [
                'freight_temp_name.temp_name','like','%'.trim($request->freight_name).'%'
            ];
        } else {
            $where = [];
        }
        $tpls = DB::table('freight_temp_name')
                    -> where($where)
                    -> orderBy('id','desc')
                    -> select([
                        'freight_temp_name.id as id',
                        'freight_temp_name.temp_name as name'
                    ])
                    -> get();
        return $tpls;
    }

    //省份是否全部分配完毕
    public function isProvincesAll(Request $request) {
        $provinces_arr_all_len = count(config('admin.provinces'));
        $provinces = DB::table('freight_temp')
                    -> where('temp_name_id', $request->temp_name_id)
                    -> where('flag', 0)
                    -> pluck('address');


        $provinces_arr = explode(",",$provinces);
        $provinces_arr_len = count($provinces_arr);

        if ($provinces_arr_len != $provinces_arr_all_len) {
            return [
                'code' => 500,
                'msg' => '此模板未分配完所有省份，请重新选择'
            ];
        }
        if ($provinces_arr_len === $provinces_arr_all_len) {
            return [
                'code' => 200,
                'msg' => '选择运费模板成功'
            ];
        }
    }

    //仓库
    public function addWareHouse(Request $request){
        $validator = Validator::make($request->all(), [
            'name'        => 'required',
            'describe'    => 'max:255',
            'business_id' => 'required|numeric',
            // 'mp_name'     => 'required',
            'image'       => 'required',
            // 'freights_id' => 'required'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        //查看这个 是否重复
        $info = WareHouse::where([
            'flag' => 0,
            'business_id' => $request -> business_id,
            'name' => trim($request -> name)
        ]) -> first();
        if($info){
            return [
                'code' => 500,
                'msg' => '名字重复'
            ];
        }
        // $CheckMpName = WareHouse::where([
        //     'mp_name'     => $request->mp_name,
        //     'business_id' => $request->business_id,
        //     'flag'        => 0
        // ])-> first();
        // if($CheckMpName){
        //     return [
        //         'code' => 500,
        //         'msg' => '已有小程序分类'
        //     ];
        // }
        // if ($request->mp_flag === '是') {
        //     $mp_flag = 1;
        // } else {
        //     $mp_flag = 0;
        // }
        WareHouse::insert([
            'name'                 => trim($request -> name),
            'describe'             => trim($request -> describe),
            'business_id'          => $request -> business_id,
            'image'                => 'ali_oss:'.$request -> image,
            // 'freight_temp_name_id' => $request -> freights_id,
            // 'mp_name'              => $request -> mp_name,
            // 'mp_flag'              => $mp_flag,
            'created_at'           => time(),
            'updated_at'           => time()
        ]);
        return [
            'code' => 200,
            'msg' => '添加成功'
        ];
    }
    public function editWareHouse(Request $request){
        $validator = Validator::make($request->all(), [
            'name'       => 'required',
            'describe'   => 'max:255',
            'id'         => 'required|numeric|exists:erp_warehouse,id',
            // 'mp_name'    => 'required',
            'image'      => 'required',
            // 'freight_id' => 'required'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $info = WareHouse::find($request -> id);
        if(!$info){
            return [
                'code' => 500,
                'msg' => '没有此条数据'
            ];
        }
        // $CheckMpName = WareHouse::where([
        //     'mp_name'     => $request->mp_name,
        //     'business_id' => $request->business_id,
        //     'flag'        => 0
        // ])-> get();
        // if(count($CheckMpName) > 1){
        //     return [
        //         'code' => 500,
        //         'msg' => '已有小程序分类'
        //     ];
        // }
        // if ($request->mp_flag === '是') {
        //     $mp_flag = 1;
        // } else {
        //     $mp_flag = 0;
        // }
        WareHouse::where('id',$request -> id) -> update([
            'name'                 => isset($request -> name)?$request -> name:$info -> name,
            'describe'             => isset($request -> describe)?$request -> describe:$info -> describe,
            'image'                => isset($request -> image)?'ali_oss:'.$request -> image:$info -> image,
            // 'freight_temp_name_id' => isset($request -> freight_id)?$request -> freight_id:$info -> freight_id,
            // 'mp_name'              => isset($request -> mp_name)?$request -> mp_name:$info -> mp_name,
            // 'mp_flag'              => $mp_flag,
        ]);

        return [
            'code' => 200,
            'msg' => '编辑成功'
        ];
    }


    public function wareHouseInfo(Request $request){
        $validator = Validator::make($request->all(), [
            'warehouse_id' => 'required|numeric|exists:erp_warehouse,id'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $info = WareHouse::find($request -> warehouse_id);
       if($info -> image){
            $info -> image_url = getImageUrl($info -> image);
            $info -> image = substr($info -> image,8);
        }
        // if ($info->mp_flag === 1) {
        //     $info->mp_flag = "是";
        // } else {
        //     $info->mp_flag = "否";
        // }
        return [
          'code' => 200,
          'data' => $info
        ];
    }


    public function wareHouseList(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        $where = [];
        if($request->name){
            $where[] = [
                'erp_spu_list.name','like','%'.trim($request -> spu_name).'%'
            ];
        };
        $list = DB::table('erp_warehouse')
                    // -> leftJoin('freight_temp_name','erp_warehouse.freight_temp_name_id','freight_temp_name.id')
                    -> where([
                        'business_id' => $request->business_id,
                        'flag' => 0
                        ])
                    -> where('name','not like','%虚拟%')
                    -> select([
                        '*',
                        'erp_warehouse.id as id'
                    ])
                    -> where($where)
                    -> get();
        // foreach ($list as $key => $value) {
        //     if ($value->mp_flag === 0) {
        //         $list[$key]->mpshow = '否';
        //     } else {
        //         $list[$key]->mpshow = '是';
        //     }
        // }
        if($request -> return_type == 'option'){
            return Configure::dealCalssArray($list);
        }

        return $list;
    }
    public function deleteWareHouse(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|numeric|exists:erp_warehouse,id'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $info = WareHouse::find($request -> id);
        if($info -> flag){
            return [
                'code' => 200,
                'msg' => '已删除'
            ];
        }

        //查看 是否有库位
        $store_house_info = DB::table('erp_storehouse')
            -> where([
                'warehouse_id' => $request -> id,
                'flag' => 0
            ]) -> first();
        if($store_house_info){
            return [
                'code' => 500,
                'msg' => '存在库位，不允许删除'
            ];
        }




        //删除
        WareHouse::where([
            'id' => $request -> id
        ]) -> update([
            'updated_at' => time(),
            'flag' => 1
        ]);

        return [
            'code' => 200,
            'msg' => '删除成功'
        ];
    }



    //库位
    public function addStorehouse(Request $request){
        $validator = Validator::make($request->all(), [
            'name'         => 'required',
            'describe'     => 'max:255',
            'warehouse_id' => 'required|numeric|exists:erp_warehouse,id',
            'business_id'  => 'required|numeric|exists:erp_business,id',
            'is_unusual'   => 'required|numeric',                           //是否异常库位 0异常库位 1正常库位
        ],[
            'name.required' => '库位名称必填',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        //查看这个 是否重复
        $info = Storehouse::where([
            'flag' => 0,
            'business_id' => $request -> business_id,
            'warehouse_id' => $request -> warehouse_id,
            'name' => trim($request -> name)
        ]) -> first();
        if($info){
            return [
                'code' => 500,
                'msg' => '名字重复'
            ];
        }

        Storehouse::insert([
            'name' => trim($request -> name),
            'describe' => trim($request -> describe),
            'business_id' => $request -> business_id,
            'warehouse_id' => $request -> warehouse_id,
            'created_at' => time(),
            'updated_at' => time(),
            'is_unusual' => $request -> is_unusual
        ]);
        return [
            'code' => 200,
            'msg' => '添加成功'
        ];
    }
    public function editStorehouse(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'describe' => 'max:255',
            'id' => 'required|numeric|exists:erp_storehouse,id',
            'is_unusual' => 'required|numeric'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $info = Storehouse::find($request -> id);
        if(!$info){
            return [
                'code' => 500,
                'msg' => '没有此条数据'
            ];
        }

        Storehouse::where('id',$request -> id) -> update([
            'name' => isset($request -> name)?$request -> name:$info -> name,
            'describe' => isset($request -> describe)?$request -> describe:$info -> describe,
            'is_unusual' => isset($request -> is_unusual)?$request -> is_unusual:$info -> is_unusual
        ]);

        return [
            'code' => 200,
            'msg' => '编辑成功'
        ];
    }

    public function storehouseInfo(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|numeric|exists:erp_storehouse,id'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $info = Storehouse::find($request -> id);
        if($info){
            $info -> warehouse_info = WareHouse::find($info -> warehouse_id);
            $info -> is_unusual_str = $info -> is_unusual?'正常库位':'异常库位';
        }
        return $info;
    }

    public function storehouseList(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        $list = Storehouse::where(function($query)use($request){
                $query -> where([
                    'business_id' => $request -> business_id,
                    'flag' => 0
                ]);
                if($request -> name){
                    $query -> where('name','like','%'.$request -> name.'%');
                }
                if($request -> warehouse_id){
                    $query -> where('warehouse_id',$request -> warehouse_id);
                }
                $query -> where('name','not like','%虚拟%');
            })
            -> orderBy('warehouse_id','desc')
            -> orderBy('id','desc')

            -> get();


        foreach($list as $k => $vo){
            $ware_house_info = WareHouse::find($vo -> warehouse_id);

            if($ware_house_info){
                $list[$k] -> warehouse_name = $ware_house_info -> name;
            }else{
                $list[$k] -> warehouse_name = '';
            }

            if($vo -> is_unusual){
                $list[$k] -> is_unusual_str = '正常库位';
            }else{
                $list[$k] -> is_unusual_str = '异常库位';
            }
        }

        return $list;
    }



    public function storehouseTreeList(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $warehouse_data = WareHouse::where([
            'flag' => 0
        ])->where(function($q) use($request){
            $q->where('business_id' , $request -> business_id)
                ->orWhere('business_id',0);
        }) -> get();
        $temp = [];
        foreach($warehouse_data as $k => $vo){

            $store_info = Storehouse::where('warehouse_id',$vo -> id)->where('business_id', $request->business_id) -> get();
            $temp_store = [];
            foreach($store_info as $key => $value){
                $temp_store[$key]['value'] = $value -> id;
                $temp_store[$key]['label'] = $value -> name;
            }

            $temp[$k] = [
                'value' => $vo -> id,
                'label' => $vo -> name,
                'children' =>$temp_store
            ];

            //warehouse_data[$k] -> store_info = $store_info;
        }


        return $temp;




    }


    public function deleteStorehouse(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|numeric|exists:erp_storehouse,id'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }


        try{
            DB::beginTransaction();
            $info = Storehouse::find($request -> id);
            if($info -> flag){
                return [
                    'code' => 200,
                    'msg' => '已删除'
                ];
            }


            //判断 此库位 有没有库存
            $stock_info = DB::table('erp_stock')
                -> where([
                    'store_house_id' => $request -> id,
                    'flag' => 0
                ]) -> first();
            if($stock_info && $stock_info -> can_buy_num){
                return [
                    'code' => 500,
                    'msg' => '此库位还有库存，不可以删除'
                ];
            }

            DB::table('erp_stock')
                -> where([
                    'store_house_id' => $request -> id,
                ]) -> update([
                    'flag' => 1
                ]);

            //删除
            Storehouse::where([
                'id' => $request -> id
            ]) -> update([
                'updated_at' => time(),
                'flag' => 1
            ]);

            DB::commit();


            return [
                'code' => 200,
                'msg' => '删除成功'
            ];
        }catch (\Exception $exception){
            DB::rollBack();
            Log::info($exception->getTraceAsString());
            return [
                'code' => 500,
                'msg' => '删除成功'
            ];
        }


    }


    //港口
    public function addPort(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'describe' => 'max:255',

            'business_id' => 'required|numeric|exists:erp_business,id',
            //'warehouse_ids' => 'required|json',
        ],[
            'name.required' => '港口名字必填',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        //查看这个 是否重复
        $info = Port::where([
            'flag' => 0,
            'business_id' => $request -> business_id,
            'name' => trim($request -> name)
        ]) -> first();
        if($info){
            return [
                'code' => 500,
                'msg' => '名字重复'
            ];
        }

        Port::insert([
            'name' => trim($request -> name),
            'describe' => trim($request -> describe),
            'business_id' => $request -> business_id,
            'created_at' => time(),
            'updated_at' => time(),
            'warehouse_ids' => trim($request -> warehouse_ids)
        ]);


        return [
            'code' => 200,
            'msg' => '添加成功'
        ];
    }
    public function editPort(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'describe' => 'max:255',
            'id' => 'required|numeric|exists:erp_port,id',
            //'warehouse_ids' => 'required|json',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $info = Port::find($request -> id);
        if(!$info){
            return [
                'code' => 500,
                'msg' => '没有此条数据'
            ];
        }

        Port::where('id',$request -> id) -> update([
            'name' => isset($request -> name)?$request -> name:$info -> name,
            'describe' => isset($request -> describe)?$request -> describe:$info -> describe,
            'warehouse_ids' => isset($request -> warehouse_ids)?$request -> warehouse_ids:$info -> warehouse_ids
        ]);

        return [
            'code' => 200,
            'msg' => '编辑成功'
        ];
    }

    public function portInfo(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|numeric|exists:erp_port,id'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $info = Port::find($request -> id);
        if(!$info){
            return [
                'code' => 500,
                'msg' => '没有此条数据'
            ];
        }
        return [
            'code' => 200,
            'data' => $info
        ];
    }

    public function portList(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }


        //如果是代理版的事业部 则 只需要返回固定的港口
        $business_info = DB::table('erp_business') -> where([
            'id' => $request -> business_id,
            'flag' => 0
        ]) -> first();
        if($business_info -> business_type){
            $list = Port::where(function($query)use($request){
                $query -> where('flag',0);
                $query -> where('business_id',$request -> business_id);
                $query -> where('name','like','%深圳%');
            }) -> get();
        }else{
            $list = Port::where(function($query)use($request){
                $query -> where('flag',0);
                $query -> where('business_id',$request -> business_id);
                if($request -> name){
                    $query -> where('name','like','%'.trim($request -> name).'%');
                }
                $query -> where('name','not like','%虚拟%');
            }) -> get();
        }





        if($request -> return_type == 'option'){
            return Configure::dealCalssArray($list);
        }

        return $list;
    }
    public function deletePort(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|numeric|exists:erp_port,id'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $info = Port::find($request -> id);
        if($info -> flag){
            return [
                'code' => 200,
                'msg' => '已删除'
            ];
        }

        //删除
        Port::where([
            'id' => $request -> id
        ]) -> update([
            'updated_at' => time(),
            'flag' => 1
        ]);

        return [
            'code' => 200,
            'msg' => '删除成功'
        ];
    }


    //商品品牌
    public function addBrand(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'business_id' => 'required|numeric|exists:erp_brand,id'
        ],[
            'name.required' => '品牌必填',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        //查看这个 是否重复
        $info = Brand::where([
            'flag' => 0,
            'business_id' => $request -> business_id,
            'name' => trim($request -> name)
        ]) -> first();
        if($info){
            return [
                'code' => 500,
                'msg' => '名字重复'
            ];
        }

        Brand::insert([
            'name' => trim($request -> name),
            'business_id' => $request -> business_id,
            'created_at' => time(),
            'updated_at' => time(),
        ]);
        return [
            'code' => 200,
            'msg' => '添加成功'
        ];
    }
    public function editBrand(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'id' => 'required|numeric|exists:erp_brand,id'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $info = Brand::find($request -> id);
        if(!$info){
            return [
                'code' => 500,
                'msg' => '没有此条数据'
            ];
        }

        Brand::where('id',$request -> id) -> update([
            'name' => isset($request -> name)?$request -> name:$info -> name,
        ]);

        return [
            'code' => 200,
            'msg' => '编辑成功'
        ];
    }

    public function brandInfo(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|numeric|exists:erp_brand,id'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $info = Brand::find($request -> id);
        return $info;
    }


    public function brandList(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        $list = Brand::where([
            'business_id' => $request -> business_id,
            'flag' => 0
        ]) -> get();

        return $list;
    }
    public function deleteBrand(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|numeric|exists:erp_brand,id'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $info = Brand::find($request -> id);
        if($info -> flag){
            return [
                'code' => 200,
                'msg' => '已删除'
            ];
        }

        //删除
        Brand::where([
            'id' => $request -> id
        ]) -> update([
            'updated_at' => time(),
            'flag' => 1
        ]);

        return [
            'code' => 200,
            'msg' => '删除成功'
        ];
    }

    //获取配置 快递类型
    public function getExpressType(){
        return Configure::dealArray(Configure::getExpressNumbers());
    }

    //发货地址
    public function sendAddress(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        $data = DB::table('erp_send_address')
            -> where([
                'business_id' => $request -> business_id,
                'flag' => 0
            ]) -> first();

        return [
            'code' => 200,
            'data' => $data
        ];

    }

    //发货地址编辑
    public function editSendAddress(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric',
            'name' => 'required',
            'tel' => 'required',
            'province' => 'required',
            'city' => 'required',
            'area' => 'required',
            'address' => 'required',
            'zip_code' => 'required',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }


        $data = DB::table('erp_send_address')
            -> where([
                'business_id' => $request -> business_id,
                'flag' => 0
            ]) -> first();
        if($data){
           DB::table('erp_send_address')
               -> where([
                   'business_id' => $request -> business_id,
                   'flag' => 0
               ]) -> update([
                   'name' => $request -> name,
                   'tel' => $request -> tel,
                   'phone' => $request -> phone,
                   'province' => $request -> province,
                   'city' => $request -> city,
                   'area' => $request -> area,
                   'address' => $request -> address,
                   'zip_code' => $request -> zip_code,
                   'address_alias' => $request -> address_alias,
               ]);

        }else{
            DB::table('erp_send_address') -> insertGetId([
                'name' => $request -> name,
                'tel' => $request -> tel,
                'phone' => $request -> phone,
                'province' => $request -> province,
                'city' => $request -> city,
                'area' => $request -> area,
                'address' => $request -> address,
                'zip_code' => $request -> zip_code,
                'address_alias' => $request -> address_alias,
                'business_id' => $request -> business_id,
                'flag' => 0,
                'created_at' => time(),
                'updated_at' => time(),
            ]);

        }

        return [
            'code' => 200,
            'msg' => '编辑成功'
        ];


    }

    /**
     * 增加馆区
     */
    public function addMpName(Request $request){
        $validator = Validator::make($request->all(), [
            'mp_name'    => 'required',
            'mp_flag'    => 'required',
            'image'      => 'required',
            'freight_id' => 'required'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        return $this->mpNameRepository->addMpName($request);
    }

    /**
     *修改馆区
     */
    public function editMpName(Request $request){
        $validator = Validator::make($request->all(), [
            'mp_name'    => 'required',
            'id'         => 'required|numeric',
            'image'      => 'required',
            'freight_id' => 'required'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        return $this->mpNameRepository->editMpname($request);
    }

    /**
     * 馆区信息
     */
    public function mpNameInfo(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|numeric'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        return $this->mpNameRepository->mpNameInfo($request);
    }

    /**
     * 馆区列表
     */
    public function mpNameList(Request $request){
        return $this->mpNameRepository->mpNameList($request);
    }

    /**
     * 简化版的馆区列表，只返回id，name
     * @return \Illuminate\Support\Collection
     */
    public function simpleMpList()
    {
        $list=DB::table('erp_mp_name')
            ->where('flag', 0)
            //->where('is_show',1)
            ->select([
                'id','mp_name'
            ])
            ->get();
        return $list;
    }

    /**
     * 馆区删除
     */
    public function deleteMpName(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|numeric'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        return $this->mpNameRepository->deleteMpName($request);
    }





}
