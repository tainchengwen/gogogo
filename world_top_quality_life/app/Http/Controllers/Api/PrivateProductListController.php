<?php

namespace App\Http\Controllers\Api;

use App\Log;
use App\ProductList;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class PrivateProductListController extends Controller
{
    //
    public function theList(Request $request){
        $validator = Validator::make($request->all(), [
            'class_id' => 'sometimes|numeric',
            'remove_ids' => 'sometimes|json', //[1023,917]
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $where = [];
        if($request -> product_no){
            //商品编号
            $where[] = [
                'erp_product_list.product_no','like','%'.trim($request -> product_no).'%'
            ];
        }
        if($request -> product_name){
            //商品编号
            $where[] = [
                'erp_product_list.product_name','like','%'.trim($request -> product_name).'%'
            ];
        }
        if($request -> class_id){
            //商品类别
            $where[] = [
                'erp_product_list.class_id','=',$request -> class_id
            ];
        }
        $whereNotIn = [];
        if($request -> remove_ids){
            $whereNotIn = json_decode($request -> remove_ids,true);
            if(!count($whereNotIn)){
                $whereNotIn = [];
            }
        }
        $submitList =[];
        if($request->unsubmit){
            $submitList = DB::table('erp_sku_review')->where('business_id',$request->business_id)->pluck('sku_id');
        }



        $list = DB::table('erp_product_list')
            //商品去找类别、品牌、系列
            //类别
            -> leftJoin('erp_agent_spu_category as product_class','erp_product_list.class_id','product_class.id')
            -> select([
                'erp_product_list.*',
                'product_class.name as product_class_name',
            ])
            -> where($where)
            ->where(function($query)use($whereNotIn){
                if(count($whereNotIn)){
                    $query -> whereNotIn('erp_product_list.id',$whereNotIn);
                }
            })
            ->where(function($query)use($request,$submitList){
                if(isset($request->business_id)){
                    $query->where('erp_product_list.business_id',$request->business_id);
                }
                if(isset($submitList) && count($submitList)>0){
                    $query->whereNotIn('erp_product_list.id',$submitList);
                }
            })
            -> orderBy('product_class.name','asc')
            -> orderBy('erp_product_list.product_no','desc')
            -> orderBy('erp_product_list.updated_at','desc')
            -> paginate(isset($request -> per_page)?$request -> per_page:20);
        foreach($list as $k => $vo){
            $volumn = round(floatval($vo -> product_height) * floatval($vo -> product_long) * floatval($vo -> product_wide),2);
            $list[$k] -> volume = $volumn;
            $list[$k] -> review_status = -1;

        }


        foreach($list as $k => $vo){
            $list[$k] -> image = getImageUrl($vo -> image);
        }

        return $list;
    }

    //上传商品图片
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


    //商品详情
    public function productInfo(Request $request){
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|numeric|exists:erp_product_list,id',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }


        $info = DB::table('erp_product_list')
            //商品去找类别
            //类别
            -> leftJoin('erp_agent_spu_category as product_class','erp_product_list.class_id','product_class.id')
            //品牌
            -> select([
                'erp_product_list.*',
                'product_class.name as product_class_name',
            ])
            -> where([
                'erp_product_list.id' => $request -> product_id
            ]) -> first();
        $info->declared_price = (float)$info->declared_price;
        if($info -> image){
            $info -> image_url = getImageUrl($info -> image);
            $info -> filename = substr($info -> image,8);
        }

        //查下多图
        $image_details = DB::table('mpshop_product_images') -> where([
            'product_no' => $info -> product_no,
            'flag' => 0
        ]) -> get();
        $image_arr = [];
        if($image_details){
            foreach($image_details as $k => $vo){
                $image_arr[$k]['name'] = substr($vo -> image,8);
                $image_arr[$k]['url'] = getImageUrl($vo -> image);
            }
        }

        //[{name: 'food.jpg', url: 'https://xxx.cdn.com/xxx.jpg'}]



        $info -> image_details = $image_arr;
        return [
            'code' => 200,
            'data' => $info
        ];
    }

    //编辑商品
    public function editProduct(Request $request){
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|numeric|exists:erp_product_list,id',
            'product_name' => 'required',
            'weight' => 'required|numeric',
            'model' => 'required', //规格
            'number' => 'required|numeric', //包装数量
            'product_long' => 'required|numeric',
            'product_wide' => 'required|numeric',
            'product_height' => 'required|numeric',
            'product_describe' => 'max:100',
            'class_id' => 'required|numeric',
            //'image' => 'required', //主图用这个
            'filename' => 'required', //主图用这个,主图文件名
            'image_details' => 'json', //副图 多图
            'volume_weight' => 'required|numeric', //体积重
            'declared_price' => 'required|numeric', //价格
            'japan_name' => 'max:100', //日文名
            'element_zh' => 'max:100', //成分（中文）
            'element_ja' => 'max:100', //成分（日文）
            'product_country' => 'max:100', //生产国

        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }


        $product_info = DB::table('erp_product_list')
            -> where([
                'id' => $request -> product_id
            ]) -> first();

        DB::table('erp_product_list')
            -> where([
                'id' => $request -> product_id
            ]) -> update([
                'product_name'     => $request -> product_name,
                'weight'           => $request -> weight,
                'declared_price'   => $request -> declared_price,
                'model'            => $request -> model,
                'number'           => $request -> number,
                'product_long'     => $request -> product_long,
                'product_wide'     => $request -> product_wide,
                'product_height'   => $request -> product_height,
                'product_describe' => $request -> product_describe,
                'class_id'         => $request -> class_id,
                'image'            => 'ali_oss:'.$request -> filename,
                'price'            => $request -> price,
                'japan_name'            => $request -> japan_name,
                'element_zh'            => $request -> element_zh,
                'element_ja'            => $request -> element_ja,
                'product_country'            => $request -> product_country,
                'volume_weight'            => $request -> volume_weight,
                'physics_weight'            => $request -> physics_weight,
                'updated_at'       => time(),
            ]);

        //副图
        if($request -> image_details){
            DB::table('mpshop_product_images') -> where([
                'product_no' => $product_info -> product_no
            ]) -> update([
                'flag' => 1
            ]);
            $temp = json_decode($request -> image_details,true);
            foreach($temp as $vo){
                DB::table('mpshop_product_images') -> insertGetId([
                    'product_no' => $product_info -> product_no,
                    'image' => 'ali_oss:'.$vo,
                    'created_at' => time(),
                    'updated_at' => time(),
                ]);
            }
        }
        $sku = DB::table('erp_product_list')
                    -> where('id', $request->product_id)
                    -> first();

        if (empty($sku->image)) {
            return [
                'code' => 200,
                'msg'  => '编辑成功'
            ];
        } else if((float)$sku->declared_price > 250 || (float)$sku->declared_price <= 0){
            return [
                'code' => 200,
                'msg'  => '编辑成功'
            ];
        } else if($sku->weight <= 0){
            return [
                'code' => 200,
                'msg'  => '编辑成功'
            ];
        } else {
            $mpNameSkus = DB::table('erp_product_price')
                              -> where([
                                'product_id' => $request->product_id,
                                'flag'       => 0
                                ])
                              -> get();
            foreach ($mpNameSkus as $sku => $v) {
                if ((float)$v->price_s <= 0) {
                    return [
                        'code' => 200,
                        'msg'  => '编辑成功'
                    ];
                } else if((float)$v->price_a <= 0){
                    return [
                        'code' => 200,
                        'msg'  => '编辑成功'
                    ];
                } else if((float)$v->price_b <= 0){
                    return [
                        'code' => 200,
                        'msg'  => '编辑成功'
                    ];
                } else if((float)$v->price_c <= 0){
                    return [
                        'code' => 200,
                        'msg'  => '编辑成功'
                    ];
                } else if((float)$v->price_d <= 0){
                    return [
                        'code' => 200,
                        'msg'  => '编辑成功'
                    ];
                } else {
                    DB::table('erp_product_price')
                        -> where('id', $v->id)
                        -> update([
                            'status' => 1
                        ]);
                }
            }
        }
        return [
            'code' => 200,
            'msg' => '编辑成功'
        ];


    }




    public function addProduct(Request $request){
        $validator = Validator::make($request->all(), [
            //供应商id
            'product_no' => 'required|unique:erp_product_list,product_no',
            //'image' => 'required',
            'filename' => 'required',//主图文件名
            'class_id' => 'required|numeric',
            'product_name' => 'required',
            'weight' => 'required|numeric',
            'model' => 'required', //型号
            'number' => 'required|numeric', //包装数量
            'product_long' => 'required|numeric',
            'product_wide' => 'required|numeric',
            'product_height' => 'required|numeric',
            'volume' => 'required|numeric',  //体积
            'product_describe' => 'required',  //商品描述
            'image_details' => 'json', //副图 多图
            'business_id' => 'required',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }



        ProductList::insert([
            'product_no' => $request -> product_no,
            'business_id' => $request -> business_id,
            'series_id' => 0, //系列
            'image' => 'ali_oss:'.$request -> filename,
            'class_id' => $request -> class_id,
            'product_name' => $request -> product_name,
            'weight' => $request -> weight,
            'brand_id' => 0,
            'model' => $request -> model,
            'number' => $request -> number,
            'product_long' => floatval($request -> product_long),
            'product_wide' => floatval($request -> product_wide),
            'product_height' => floatval($request -> product_height),
            'volume' => floatval($request -> product_long) * floatval($request -> product_wide) * floatval($request -> product_height),
            'product_describe' => $request -> product_describe,
            'price' => $request -> price,
            'declared_price'   => $request -> declared_price?$request -> declared_price:0.00,
            'japan_name'            => $request -> japan_name,
            'element_zh'            => $request -> element_zh,
            'element_ja'            => $request -> element_ja,
            'product_country'            => $request -> product_country,
            'volume_weight'            => $request -> volume_weight,
            'physics_weight'            => $request -> physics_weight,
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        //副图
        if($request -> image_details){
            DB::table('mpshop_product_images') -> where([
                'product_no' => $request -> product_no,
            ]) -> update([
                'flag' => 1
            ]);
            $json = json_decode($request -> image_details,true);
            foreach($json as $vo){
                DB::table('mpshop_product_images') -> insertGetId([
                    'product_no' => $request -> product_no,
                    'image' => 'ali_oss:'.$vo,
                    'created_at' => time(),
                    'updated_at' => time(),
                ]);
            }
        }

        return [
            'code' => 200,
            'msg' => '添加成功'
        ];
    }


    public function uploadExcel(Request $request){
        if (!$request->hasFile('file')) {
            //
            return [
                'code' => 500,
                'msg' => '请上传文件'
            ];
        }
        if(!isset($request->business_id)){
            return [
                'code' => 500,
                'msg' => '缺少对应事业部id参数'
            ];
        }
        if ($request->file('file')->isValid()) {
            $filePath = $request -> file -> path();

            Excel::load($filePath, function($reader) use( &$res ) {
                $reader = $reader->getSheet(0);
                $res = $reader->toArray();
            });

            //计数器
            //成功
            $number = 0;
            //总
            $number_all = 0;
            //失败的行数
            $fail_arr = [];

            foreach($res as $k => $vo){
                if($k == 0){
                    continue;
                }

                /*
                array (
                    0 => '商品编号',
                    1 => '类别',
                    2 => '品牌',
                    3 => '系列',
                    4 => '品名',
                    5 => '型号',
                    6 => '包装数量',
                    7 => '体积重',
                    8 => '物理重',
                    9 => '长',
                    10 => '宽',
                    11 => '高',
                    12 => '体积',
                    13 => '价格',
                    14 => '产品描述',
                  ),
                */

                if(isset($vo[0]) && $vo[0]){
                    $number_all ++;
                }




                if(isset($vo[0]) && isset($vo[1]) && isset($vo[2]) && isset($vo[3])  && isset($vo[4]) && $vo[0] && $vo[1] && $vo[2] && $vo[3] && $vo[4] ){

                    //先找下 类别 品牌 系列 对不对
                    $class = DB::table('erp_product_class as type0')
                        -> where([
                            'type0.name' => trim($vo[1]), //类别
                        ]) -> select([
                            'type0.id as class_id'
                        ]) -> first();
                    \Illuminate\Support\Facades\Log::info($vo[1]);
                    if(!$class){
                        $fail_arr[] = $k + 1;
                        continue;
                    }

                    $info = ProductList::where([
                        'product_no' => trim($vo[0]),
                        'business_id'=> $request->business_id
                    ]) -> first();
                    if($info){
                        ProductList::where([
                            'id' => $info -> id
                        ]) -> update([
                            'class_id' => $class -> class_id,
                            'product_name' => $vo[4],
                            'model' => isset($vo[5])?$vo[5]:'',
                            'number' => isset($vo[6])?$vo[6]:0,
                            'volume_weight' => isset($vo[7])?$vo[7]:0,
                            'physics_weight' => isset($vo[8])?$vo[8]:0,
                            'product_long' => isset($vo[9])?$vo[9]:0,
                            'product_wide' => isset($vo[10])?$vo[10]:0,
                            'product_height' => isset($vo[11])?$vo[11]:0,
                            'price' => isset($vo[13])?$vo[13]:0,
                            'product_describe' => isset($vo[14])?$vo[14]:'',
                            //'weight' => isset($vo[15])?$vo[15]:'',
                            'updated_at' => time(),
                        ]);
                    }else{
                        ProductList::insert([
                            'product_no' => trim($vo[0]),
                            'class_id' => $class -> class_id,
                            'product_name' => $vo[4],
                            'model' => isset($vo[5])?$vo[5]: $info -> model,
                            'number' => isset($vo[6])?$vo[6]:$info -> number,
                            'volume_weight' => isset($vo[7])?$vo[7]:$info -> volume_weight,
                            'physics_weight' => isset($vo[8])?$vo[8]:$info -> physics_weight,
                            'product_long' => isset($vo[9])?$vo[9]:$info -> product_long,
                            'product_wide' => isset($vo[10])?$vo[10]:$info -> product_wide,
                            'product_height' => isset($vo[11])?$vo[11]:$info -> product_height,
                            'price' => isset($vo[13])?$vo[13]:$info -> price,
                            'business_id' => $request->business_id,
                            'product_describe' => isset($vo[14])?$vo[14]:$info -> product_describe,
                            //'weight' => isset($vo[15])?$vo[15]:$info -> weight,
                            'created_at' => time(),
                            'updated_at' => time(),
                        ]);
                    }

                    $number ++;
                }else{

                }

            }


            return [
                'code' => 'success',
                'msg' => '总上传'.$number_all.'条，上传成功：'.$number.'条'.' 失败行数'.implode(',',$fail_arr)
            ];

        }else{
            return [
                'code' => 'error',
                'msg' => '上传失败'
            ];
        }
    }






}
