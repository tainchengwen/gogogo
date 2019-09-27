<?php

namespace App\Http\Controllers\Api;

use App\Account;
use App\Configure;
use App\NumberQueue;
use App\PurchaseOrderGoods;
use App\ReceiveGoodsRecord;
use App\Refund;
use App\Stock;
use App\StockOrder;
use App\StockOrderInfo;
use App\Storehouse;
use App\Repositories\BaseRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Repositories\MathRepository;

class StockOrderController extends Controller
{

    public function __construct(MathRepository $mathRepository)
    {
        $this->mathRepository = $mathRepository;
    }

    //库存销售单列表
    public function theList(Request $request){
//        $validator = Validator::make($request->all(), [
//            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
//        ]);
//        if ($validator->fails()) {
//            return new JsonResponse($validator->errors());
//        }

        $user = auth('api')->user();
        $model_has_roles = DB::table('model_has_roles')
            -> leftJoin('roles','model_has_roles.role_id','roles.id')
            -> where([
                'model_has_roles.model_id' => $user -> id
            ])
            -> select([
                'roles.name as  roles_name'
            ])
            -> get();

        //是不是客服角色
        $is_kefu_roles = 0;
        if($model_has_roles){
            foreach($model_has_roles as $vo){
                if(strstr($vo -> roles_name,'客服')){
                    $is_kefu_roles = 1;
                    break;
                }
            }
        }

        $orders = DB::table('erp_stock_order as order')
            -> leftJoin('wxuser','order.user_id','wxuser.id')
            -> leftJoin('erp_warehouse','order.warehouse_id','erp_warehouse.id')
            -> leftJoin('users','order.sale_user_id','users.id')
            -> leftJoin('erp_shopmp_identity_card as card' ,'card.id','order.ident_id')
            -> leftJoin('erp_business','erp_business.id','order.business_id')
            -> where([
                'order.flag' => 0,
            ])
            -> where(function($query)use($is_kefu_roles,$user){
                if($is_kefu_roles){
                    $query -> where('operator_user_id',$user -> id);
                }
            })
            -> where(function($query)use($request){
                if($request -> order_num ){ //订单编号
                    $query -> where('order.order_num',$request -> order_num);
                }
                if($request -> business_id ){ //事业部
                    $query -> where('order.business_id',$request -> business_id);
                }
                if($request -> sale_date_left){ //销售日期
                    $query -> where('order.sale_date','>=',strtotime($request -> sale_date_left));
                }
                if($request -> sale_date_right){
                    $query -> where('order.sale_date','<=',strtotime($request -> sale_date_right));
                }
                if($request -> pay_date_left){ //下单日期
                    $query -> where('order.created_at','>=',strtotime($request -> pay_date_left));
                }
                if($request -> pay_date_right){
                    $query -> where('order.created_at','<=',strtotime($request -> pay_date_right));
                }
                if($request -> user_id){ //客户id
                    $query -> where('order.user_id',$request -> user_id);
                }
                if($request -> sale_user_id){ //销售员
                    $query -> where('order.sale_user_id',$request -> sale_user_id);
                }
                if($request -> sale_status != null){ //付款状态
                    $query -> where('order.pay_status',$request -> sale_status);
                }
                if($request -> send_mark != ''){ //发货备注
                    //发货备注 0发货 1不发货 2预定
                    $query -> where('order.send_remark',$request -> send_mark);
                }
                if($request -> has('send_status')){ //订单状态
                    //发货状态 0未发货 1已发货 2发货中
                    $query -> where('order.send_status', $request -> send_status);
                }
                if($request -> warehouse_id){ //仓库筛选
                    $query -> where('order.warehouse_id',$request -> warehouse_id);
                }

                //支付方式
                if($request -> pay_method){
                    $query -> where('order.pay_method',$request -> pay_method);
                }


            })
            -> select([
                'order.*',
                'erp_warehouse.name as erp_warehouse_name',
                'users.name as sale_user_name',
                'wxuser.nickname',
                'card.name as realname',
                'card.idNumber as idCardNumber',
                'erp_business.name as businessname'
            ])
            -> orderBy('id','desc')
            -> paginate(isset($request -> per_page)?$request -> per_page:20);



        $payMethodList = config('admin.stock_order_pay_method');
        //send_status 发货状态 0未发货 1已发货 2发货中
        foreach($orders as $k => $vo){
            if($vo -> send_status == 2){
                $orders[$k] -> send_status_str = '发货中';
            }elseif($vo -> send_status == 1){
                $orders[$k] -> send_status_str = '已发货';
            }else{
                $orders[$k] -> send_status_str = '未发货';
            }

            //发货备注 0发货1不发货2预定
            if($vo -> send_remark == 2){
                $orders[$k] -> send_remark_str = '预定';
            }elseif($vo -> send_remark == 1){
                $orders[$k] -> send_remark_str = '不发货';
            }else{
                $orders[$k] -> send_remark_str = '发货';
            }

            //支付方式
             $orders[$k] -> pay_method_remark = isset($payMethodList[$vo->pay_method])? $payMethodList[$vo->pay_method]: '';
            //付款状态
            if($vo -> pay_status){
                $orders[$k] -> pay_status_str = '已付款';
                //如果已支付 则 显示 支付时间
                $erp_stock_order_pay = DB::table('erp_stock_order_pay') -> where([
                    'order_id' => $vo -> id,
                    'flag' => 0
                ])
                    -> select([
                        'created_at'
                    ])
                    -> first();
                if($erp_stock_order_pay){
                    $orders[$k] -> pay_time = date('Y-m-d H:i:s',$erp_stock_order_pay -> created_at);
                }else{
                    $orders[$k] -> pay_time = '';
                }
            }else{
                $orders[$k] -> pay_status_str = '未付款';
                $orders[$k] -> pay_time = '';
            }

            //打印配货单
            if($vo -> print_distribution){
                $orders[$k] -> distribute_order = '已打印';
            }else{
                $orders[$k] -> distribute_order = '';
            }

            //打印面单
            if($vo -> print_express){
                $orders[$k] -> express_order = '已打印';
            }else{
                $orders[$k] -> express_order = '';
            }
            $orders[$k] -> goods_name = DB::table('erp_stock_order_info as info')
                ->leftJoin('erp_product_list as list' ,'info.product_id','list.id')
                ->where(['info.stock_order_id'=>$vo->id])
                ->select('list.product_name')
                ->first();
            $orders[$k] -> goods_name =isset($orders[$k] -> goods_name->product_name)? $orders[$k] -> goods_name->product_name :'';
            $orders[$k] -> price_all = $this->mathRepository->math_add($this->mathRepository->math_add($vo -> price, $vo -> substitute), $vo -> freight);
            $orders[$k] -> created_at = date('Y-m-d H:i:s',$vo -> created_at);
        }

        return $orders;
    }


    //库存销售列表 点击显示发货记录
    public function fahuoList(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
            'stock_order_id' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        //通过stock_order_id 找发货记录
        $record = DB::table('erp_deliver_goods_record as record')
            -> leftJoin('erp_stock_order','record.stock_order_id','erp_stock_order.id')
            -> leftJoin('users','record.send_user_id','users.id')
            -> select([
                'record.record_num', //发货编号
                'erp_stock_order.order_num', //销售编号
                'record.created_at', //发货时间
                'record.company', //物流公司
                'record.deliver_num', //物流单号
                'record.deliver_remark', //物流备注
                'record.deliver_price', //运费
                'record.id',
                'users.name as send_user'
            ])
            -> where([
                'erp_stock_order.id' => $request -> stock_order_id,
                'record.flag' => 0,

            ]) -> get();

        foreach($record as $k => $vo){
            $record[$k] -> created_at = date('Y-m-d H:i',$vo -> created_at);
        }

        return [
            'code' => 200,
            'data' => $record
        ];


    }

    //添加库存销售单
    public function addOrder(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
            'sale_date' => 'required|date', //销售日期
            //'warehouse_id' => 'required|numeric|exists:erp_warehouse,id', //仓库id
            'mp_id' => 'required|numeric|exists:erp_mp_name,id', //馆区id
            //'storehouse_id' => 'required|numeric|exists:erp_storehouse,id', //库位id
            'user_id' => 'required|numeric|exists:wxuser,id', //客户id
            'sale_user_id' => 'required|numeric|exists:users,id', //销售员id
            //'freight' => 'required|numeric', //运费
            //'substitute' => 'required|numeric', //代发费
            'send_remark' => 'required|numeric', //发货备注 0发货1不发货2预定

            //'province' => 'required|max:10',
            //'city' => 'required|max:20',
            //'area' => 'required|max:20',
            //'tel' => 'required|numeric|size:11',
            //'phone' => '' 电话 非必传
            //'name' => 'required|max:10', //收件人姓名
            //'address' => 'required|max:30',



            //'send_name' => 'required|max:10',
            //'send_tel' => 'required|numeric|size:11',
            //'send_province' => 'required|max:10',
            //'send_city' => 'required|max:10',
            //'send_area' => 'required|max:10',
            //'send_address' => 'required|max:30',
            //'remark' =>  非必填
            'order_info_json' => 'required|json'   //库存销售订单 新增明细   [{"goods_id 商品id":"1","price价格":"124","number数量":"55"},{}]

            //send_remark 发货备注 0发货1不发货2预定
            //sale_remark 销售备注


        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $order_info_arr=json_decode($request->order_info_json,true);
        //获取仓库id
        $firstWarehouse=$this->getWarehouseBymp($order_info_arr,$request);

        //加入购物车
        $cartIds=[];
        foreach ($order_info_arr as $v){
            $need_data=DB::table('erp_product_price')
                ->leftJoin('erp_spu_sku_link','erp_product_price.product_id','erp_spu_sku_link.sku_id')
                ->leftJoin('erp_spu_list','erp_spu_sku_link.spu_id','erp_spu_list.id')
                ->leftJoin('erp_mp_name_spu_link',function($q){
                    $q->on('erp_spu_list.id','=','erp_mp_name_spu_link.spu_id')
                        ->on('erp_product_price.mp_name_id','=','erp_mp_name_spu_link.mp_name_id');
                })
                ->where('erp_product_price.mp_name_id',$request->mp_id)
                ->where('erp_product_price.product_id',$v['goods_id'])
                ->where('erp_spu_sku_link.flag',0)
                ->where('erp_spu_list.flag',0)
                ->select([
                    'erp_product_price.id as skuId',
                    'erp_mp_name_spu_link.id as spuId',
                ])
                ->first();
            if (!empty($need_data) && !empty($need_data->spuId) && !empty($need_data->skuId)){
                $insert_data=[
                    'userId'        => $request->user_id,
                    'spuId'         => $need_data->spuId,
                    'skuId'         => $need_data->skuId,
                    'num'           => $v['number'],
                    'businessId'    => $request->business_id,
                    'isDirect'      => 1,
                    'createdTime'   => time(),
                ];
                $cartIds[]=DB::table('erp_shop_cart')->insertGetId($insert_data);
            }
        }

        $user = auth('api')->user();
        $model = new StockOrder();
        $orderResult = $model -> addStockOrder([
            'order_info_json' => $request -> order_info_json, //下单json 新增明细   [{"goods_id 商品id":"1","price价格":"124","number数量":"55"},{}]
            'warehouse_id' => $firstWarehouse -> id, //仓库id
            'business_id' => $request -> business_id, //事业部id
            'sale_date' => $request -> sale_date, //销售日期 date类型
            'user_id' => $request -> user_id, //wxuser的id
            'sale_user_id' => $request -> sale_user_id, //销售员users id
            'freight' => $request -> freight?$request -> freight:0, //运费
            'substitute' => $request -> substitute?$request -> substitute:0, //代发费
            'send_remark' => $request -> send_remark?$request -> send_remark:0, //	发货备注 0发货1不发货2预定
            'province' => $request -> province,
            'city' => $request -> city,
            'area' => $request -> area,
            'tel' => $request -> tel,
            'phone' => $request -> phone,
            'name' => $request -> name,
            'address' => $request -> address,
            'send_name' => $request -> send_name,
            'send_tel' => $request -> send_man_mobile,
            'send_phone' => $request -> send_man_phone,
            'send_province' => $request -> send_province,
            'send_city' => $request -> send_city,
            'send_area' => $request -> send_area,
            'send_address' => $request -> send_address,
            'remark' => $request -> remark,
            'sale_remark' => $request -> sale_remark,
            'user_remark' => $request -> user_remark,
            'operator_user_id' => $user -> id, //后台操作员users id
        ]);

        //下单成功，更新购物车
        if ($orderResult['code'] === 200) {
            DB::table('erp_shop_cart')
                ->whereIn('id', $cartIds)
                -> update([
                    'isOrder'   => 1,
                    'orderId'   => $orderResult['stock_id'],
                    'orderTime' => time()
                ]);
            return [
                'code'     => 200,
                'msg'      => '成功',
                'stock_id' => $orderResult['stock_id']
            ];
        } else {
            return [
                'code' => 500,
                'msg'  => $orderResult['msg']
            ];
        }

    }



    //获取某仓库下的库存
    public function getStockByWarehouse(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
            //'storehouse_id' => 'required|numeric|exists:erp_storehouse,id' //库位id
            'ware_house_id' => 'required|numeric|exists:erp_warehouse,id' //仓库id
        ]);

        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }




        //寻找这些库位下的库存
        /*
        $list = DB::table('erp_stock')
            -> leftJoin('erp_product_list','erp_stock.product_id','erp_product_list.id')
            -> leftJoin('erp_storehouse','erp_stock.store_house_id','erp_storehouse.id')
            -> leftJoin('erp_warehouse','erp_storehouse.warehouse_id','erp_warehouse.id')

            //商品去找类别、品牌、系列
            //类别
            -> leftJoin('erp_product_class as product_class','erp_product_list.class_id','product_class.id')
            //品牌
            -> leftJoin('erp_product_class as product_brand','erp_product_list.brand_id','product_brand.id')
            //系列
            -> leftJoin('erp_product_class as product_series','erp_product_list.series_id','product_series.id')
            -> where('erp_stock.can_buy_num','>',0)
            -> where('erp_stock.warehouse_id',$request -> ware_house_id)
            -> select([
                'erp_stock.*',
                'erp_stock.product_id',
                'erp_product_list.id as id', //这里的id 是传到库存销售的goods_id
                'erp_product_list.product_name',
                'erp_product_list.model',
                'erp_product_list.number',
                'erp_product_list.product_no',
                'erp_storehouse.name as store_house_name',
                'erp_warehouse.name as warehouse_name',
                'product_class.name as product_class_name',
                'product_brand.name as product_brand_name',
                'product_series.name as product_series_name',

            ])
            -> select(DB::raw('erp_stock.product_id,any_values(erp_stock.*)'))
            -> groupBy('erp_stock.product_id')

            -> paginate(isset($request -> per_page)?$request -> per_page:20);
        */



        $temp_list = DB::table('erp_stock')
            -> leftJoin('erp_product_list','erp_stock.product_id','erp_product_list.id')

            //商品去找类别、品牌、系列
            //类别
            -> leftJoin('erp_product_class as product_class','erp_product_list.class_id','product_class.id')
            //品牌
            -> leftJoin('erp_product_class as product_brand','erp_product_list.brand_id','product_brand.id')
            //系列
            -> leftJoin('erp_product_class as product_series','erp_product_list.series_id','product_series.id')

            ->  select(DB::raw('erp_stock.product_id,
            erp_product_list.product_name,
            erp_product_list.model,
            erp_product_list.number,
            erp_product_list.product_no,
            product_class.name as product_class_name,
            product_brand.name as product_brand_name,
            product_series.name as product_series_name
            '))
            -> where('erp_stock.can_buy_num','>',0)
            -> where('erp_stock.flag','=',0)
            -> where('erp_stock.warehouse_id',$request -> ware_house_id)
            -> where(function($query)use($request){
                if($request -> remove_ids){
                    $removes = json_decode($request -> remove_ids,true);
                    if(count($removes)){
                        $query -> whereNotIn('erp_stock.product_id',$removes);
                    }
                }
                if($request -> product_name){
                    $query -> where('erp_product_list.product_name','like','%'.trim($request -> product_name).'%');
                }
                //筛选商品编号
                if($request -> product_no){
                    $query -> where('erp_product_list.product_no','like','%'.trim($request -> product_no).'%');
                }
                //类别
                if($request -> product_class){
                    $query -> where('erp_product_list.class_id',$request -> product_class);
                }
                //品牌
                if($request -> product_brand){
                    $query -> where('erp_product_list.brand_id',$request -> product_brand);
                }
                //系列
                if($request -> product_series){
                    $query -> where('erp_product_list.series_id',$request -> product_series);
                }


            })
            -> groupBy('erp_stock.product_id')
            -> paginate(isset($request -> per_page)?$request -> per_page:20);

        foreach($temp_list as $k => $vo){
            $temp_stock = DB::table('erp_stock')
                -> where([
                    'business_id' => $request -> business_id,
                    'warehouse_id' => $request -> ware_house_id,
                    'product_id' => $vo -> product_id,
                    'flag' => 0
                ])
                -> where('can_buy_num','>',0)
                -> sum('can_buy_num');

            $temp_list[$k] -> can_buy_num = $temp_stock;

        }

        return $temp_list;
    }

    public function getSkusByMp(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
            'mp_id' => 'required|numeric|exists:erp_mp_name,id', //馆区id
            'user_id' => 'required|numeric|exists:wxuser,id' //客户id
        ]);

        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $wxuser=DB::table('wxuser')->find($request->user_id);
        // vip等级价格map
        $market_class_map = config('admin.market_class');
        // 当前等级字母
        $current = $market_class_map[$wxuser->market_class];

        //取出该馆区关联的仓库id
        $warehouse_ids=DB::table('erp_warehouse')
            ->where('flag','0')
            ->where('mp_name_id',$request->mp_id)
            ->pluck('id');
        //取出有库存的product ids
        $product_ids=DB::table('erp_stock')
            ->where('can_buy_num','>',0)
            ->where('flag',0)
            ->whereIn('warehouse_id',$warehouse_ids)
            ->pluck('product_id');


        $spuids = DB::table('erp_mp_name_spu_link')
            -> where([
                'erp_mp_name_spu_link.mp_name_id' => $request->mp_id,
                'erp_mp_name_spu_link.flag'       => 0
            ])
            -> pluck('spu_id');
        $inskuids = DB::table('erp_spu_sku_link')
            -> where('erp_spu_sku_link.flag', 0)
            -> whereIn('erp_spu_sku_link.spu_id', $spuids)
            -> pluck('sku_id');

        //取交集
        if ($request->mp_id == 6){//阳阳的就取所有有库存的
            $inter_skuids=$product_ids;
        }else{
            $inter_skuids=$product_ids->intersect($inskuids);
        }

        $list = DB::table('erp_product_list')
            -> leftJoin('erp_product_price', function ($join) use ($request) {
                $join-> on('erp_product_list.id', '=', 'erp_product_price.product_id')
                    -> where('erp_product_price.mp_name_id', '=', $request->mp_id)
                    -> where('erp_product_price.flag', '=', 0);
            })
            -> leftJoin('erp_product_class as product_class', 'erp_product_list.class_id', 'product_class.id')
            -> leftJoin('erp_product_class as product_brand', 'erp_product_list.brand_id', 'product_brand.id')
            -> leftJoin('erp_product_class as product_series', 'erp_product_list.series_id', 'product_series.id')
            -> whereIn('erp_product_list.id', $inter_skuids)
            -> where(function($query)use($request){
                if($request -> remove_ids){
                    $removes = json_decode($request -> remove_ids,true);
                    if(count($removes)){
                        $query -> whereNotIn('erp_product_list.id',$removes);
                    }
                }
                if($request -> product_name){
                    $query -> where('erp_product_list.product_name','like','%'.trim($request -> product_name).'%');
                }
                //筛选商品编号
                if($request -> product_no){
                    $query -> where('erp_product_list.product_no','like','%'.trim($request -> product_no).'%');
                }
                //类别
                if($request -> product_class){
                    $query -> where('erp_product_list.class_id',$request -> product_class);
                }
                //品牌
                if($request -> product_brand){
                    $query -> where('erp_product_list.brand_id',$request -> product_brand);
                }
                //系列
                if($request -> product_series){
                    $query -> where('erp_product_list.series_id',$request -> product_series);
                }


            })
            ->  select([
                'erp_product_list.id as product_id',
                'erp_product_list.product_name',
                'erp_product_list.model',
                'erp_product_list.number',
                'erp_product_list.product_no',
                'product_class.name as product_class_name',
                'product_brand.name as product_brand_name',
                'product_series.name as product_series_name',
                'erp_product_price.price_' . $current . ' as currentPrice',
            ])
            -> orderBy('product_class_name','desc')
            -> orderBy('product_brand_name','desc')
            -> orderBy('product_series_name','desc')
            -> orderBy('product_id','desc')
            -> paginate(isset($request->per_page)?$request->per_page:20);

        foreach ($list as $v){
            $v->num=1;//默认数量
            $temp_stock = DB::table('erp_stock')
                -> where([
                    'product_id' => $v -> product_id,
                    'flag' => 0
                ])
                -> where('can_buy_num','>',0)
                -> whereIn('warehouse_id',$warehouse_ids)
                -> sum('can_buy_num');

            $v -> can_buy_num = $temp_stock;
        }
        return $list;
    }

    // EXCEL导出所需信息详情
    public function orderExcelInfo(Request $request)  {
        $validator = Validator::make($request->all(), [
            'business_id'   => 'required|numeric|exists:erp_business,id',   //事业部id
            'stockorderids' => 'required'                                   //选中的库存订单ids
        ]);

        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        // sku列表
        $list = DB::table('erp_stock_order')
            -> whereIn('erp_stock_order.id', $request->stockorderids)
            -> leftJoin('erp_stock_order_info','erp_stock_order_info.stock_order_id','erp_stock_order.id')
            -> leftJoin('admin_users','erp_stock_order.user_id','admin_users.id')
            -> leftJoin('erp_product_list','erp_stock_order_info.product_id','erp_product_list.id')
            -> leftJoin('erp_warehouse','erp_stock_order.warehouse_id','erp_warehouse.id')
            -> leftJoin('freight_temp_name','erp_warehouse.freight_temp_name_id','freight_temp_name.id')
            -> where('erp_stock_order_info.flag', 0)
            -> select([
                // 仓库Id
                'erp_stock_order.warehouse_id',
                'erp_stock_order.id',
                'erp_stock_order.order_num',
                'erp_stock_order.name as username',
                'erp_stock_order.phone as phone',
                'erp_stock_order.province',
                'erp_stock_order.city',
                'erp_stock_order.area',
                'erp_stock_order.address',
                // 运费模板类型
                'freight_temp_name.country',
                // 当前商品的价格申报价格
                'erp_product_list.declared_price',
                'erp_product_list.model',
                'erp_product_list.id as productId',
                'erp_product_list.product_no as productNo',
                'erp_product_list.product_name as productName',
                'erp_stock_order_info.number as num',
                // sku重量
                'erp_product_list.weight',
                // 是否有重量要求
                'freight_temp_name.is_weight',
                'freight_temp_name.id as freight_temp_name_id',
                // 重量要求是多少
                'freight_temp_name.weight_info',
                // 是否在国内
                'freight_temp_name.country',
                // 该仓库价值限制
                'freight_temp_name.package_limit',
            ])
            -> get();

        // 按照订单编号进行分组
        $BaseRepository = new BaseRepository();
        $groupList = $BaseRepository->arrayGrouping($list, 'id');

        $limit = 250;
        // $limit = (float)$list[0]->package_limit;
        $domesticlist = [];
        $incountrylist = [];
        $abroadlist = [];
        $packagemerge = [];
        $result = [];
        // 查询订单中是否国内外
        foreach ($groupList as $key => $value) {
            $k = 1;
            $test = 1;
            $CountryStatus = $value[0]->country;
            if ($CountryStatus === 0) {
                foreach ($value as $vo => $vol) {
                    $vol->test = 1;
                    $vol->single_num = $vol->num;
                    array_push($incountrylist, $vol);
                }
            }
            if ($CountryStatus === 1) {
                // 重置float
                foreach ($value as $vo => $vol) {
                    $value[$vo]->declared_price = (float)$vol->declared_price;
                    $value[$vo]->single_num = 1;
                }
                // 平铺一整个订单里的商品
                $list_i = $this->tileCart($value);
                // 按照价格倒序
                $sortResult = $BaseRepository->arrayMultisortMy($list_i, 'declared_price', 'DESC');
                // 按照单个包裹申报价值上限-拆包裹
                $orders = $this->unpack($sortResult, $limit);
                foreach ($orders as $vo) {
                    foreach ($vo as $v => $vol) {
                        $vo[$v]->test = $k;
                    }
                    $k = $k + 1;
                    array_push($packagemerge, $vo);
                }
                array_walk_recursive($packagemerge, function($value) use (&$result) {
                    array_push($result, $value);
                });
                $packagemerge = [];
                for ($i = 0; $i < count($result); $i++) {
                    for ($j = $i+1; $j < count($result); $j++) {
                        if (($result[$i]->productNo === $result[$j]->productNo) && ($result[$i]->test === $result[$j]->test)) {
                            unset($result[$j]);
                            $result = array_values($result);
                            $result[$i]->single_num = $result[$i]->single_num + 1;
                        }
                    }
                }
                array_push($abroadlist, $result);
                $result = [];
            }
        };
        array_push($domesticlist, $abroadlist);
        array_push($domesticlist, $incountrylist);
        // EXCEL表头
        $cellData[] = [
            '订单编号',
            '商品编码',
            '名称',
            '型号',
            '数量',
            '重量',
            '申报价格',
            '客户姓名',
            '手机号',
            '省份',
            '城市',
            '区域',
            '地址'

        ];
        // 所有订单转换成一维数组遍历取值放入EXCEL
        $result_all = [];
        array_walk_recursive($domesticlist, function($value) use (&$result_all) {
            array_push($result_all, $value);
        });
        foreach ($result_all as $v) {
            $difnum = $v->order_num . '-' . $v->test;
            $cellData[] = [
                $difnum,
                $v->productNo,
                $v->productName,
                $v->model,
                $v->single_num,
                $v->weight,
                $v->declared_price,
                $v->username,
                $v->phone,
                $v->province,
                $v->city,
                $v->area,
                $v->address


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
        // return $domesticlist;
        // return [
        //     'code' => 200,
        //     'msg'  => '导出成功'
        // ];
    }
    protected $cellLetter = [
        'A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q',
        'R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD',
        'AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN',
        'AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ'
    ];
    // 平铺多数量sku
    private function tileCart($list) {
        // 根据可能的一个商品买多个, 所以直接平铺
        foreach ($list as $key => $value) {
            if ($value->num > 1) {
                $list[$key]->index = 1;
                for ($i=0; $i < $value->num - 1; $i++) {
                    $newValue =  clone $value;
                    $newValue->index = 2 + $i;
                    $list[] = $newValue;
                }
            }
        }
        return $list;
     }

    // 拆包裹
    private function unpack($list, $limit) {
        $orders = [];
        while (count($list)) {
            $tmp = [];
            $tmp[] =  array_shift($list);
            foreach ($list as $key => $value) {
                if ($this->isUnderLimit($tmp, $value, $limit)) {
                    $tmp[] = $value;
                    unset($list[$key]);
                }
            }
            $orders[] = $tmp;

        }
        return $orders;
    }

    // 当前包裹+新商品价格，是否小于限制
    private function isUnderLimit($current, $add, $limit)
    {
        $BaseRepository = new BaseRepository();
        $sum = 0;
        foreach ($current as $key => $value) {
            $sum = $BaseRepository->math_add($sum,$value->declared_price);
        }
        $newSum = $BaseRepository->math_add($sum,$add->declared_price);

        return  $BaseRepository->math_comp($newSum,$limit) <=0 ;
    }

    //库存订单详情
    public function stockOrderInfo(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
            'stock_order_id' => 'required|numeric' //库存订单id
        ]);

        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $order_info = DB::table('erp_stock_order')
            -> where([
                'id' => $request -> stock_order_id,
                'business_id' => $request -> business_id
            ]) -> first();
        if(!$order_info){
            return [
                'code' => 500,
                'msg' => '没有此条数据'
            ];
        }

        if($order_info -> send_status > 0 && $request -> is_edit){
            return [
                'code' => 500,
                'msg' => '已发货不允许编辑'
            ];
        }

        $order = DB::table('erp_stock_order as order')
            -> leftJoin('wxuser','order.user_id','wxuser.id')
            -> leftJoin('erp_warehouse','order.warehouse_id','erp_warehouse.id')
            -> leftJoin('users','order.sale_user_id','users.id')
            -> select([
                'order.*',
                'wxuser.nickname',
                'erp_warehouse.mp_name_id as mp_id',
                'erp_warehouse.name as warehouse_name',
                'users.name as sale_user_name'
            ])
            -> where([
                'order.id' => $request -> stock_order_id,
            ])
            -> first();

        if(!$order){
            return [
                'code' => 500,
                'msg' => '没有此订单'
            ];
        }

        $order -> price_all = round(floatval($order -> price) + floatval($order -> substitute) + floatval($order -> freight),2);

        //退款金额
        $order -> back_price = 0;
        //需退款金额
        $order -> willback_price = 0;
        //最终金额
        $order -> last_price = round(floatval($order -> price) + floatval($order -> substitute) + floatval($order -> freight),2);
        //总金额
        $order -> totalnum = round(floatval($order -> price) + floatval($order -> substitute) + floatval($order -> freight),2);


        $order -> send_man_mobile = $order -> send_tel;

        $order -> send_man_phone = $order -> send_phone;

        //发货备注 0发货1不发货2预定'
        if($order -> send_remark == 1){
            $order -> send_remark_str = '不发货';
        }elseif($order -> send_remark == 2){
            $order -> send_remark_str = '预定';
        }else{
            $order -> send_remark_str = '发货';
        }


        $order_info = DB::table('erp_stock_order_info')

            -> leftJoin('erp_stock_order','erp_stock_order_info.stock_order_id','erp_stock_order.id')

            -> leftJoin('erp_product_list','erp_stock_order_info.product_id','erp_product_list.id')
            //类别
            -> leftJoin('erp_product_class as product_class','erp_product_list.class_id','product_class.id')
            //品牌
            -> leftJoin('erp_product_class as product_brand','erp_product_list.brand_id','product_brand.id')
            //系列
            -> leftJoin('erp_product_class as product_series','erp_product_list.series_id','product_series.id')

            -> where([
                'erp_stock_order_info.stock_order_id' => $request -> stock_order_id,
                'erp_stock_order_info.flag' => 0
            ]) -> select([
                'erp_stock_order_info.*',
                'erp_stock_order_info.number as order_info_num',
                'erp_product_list.*',
                'erp_stock_order_info.price',
                'erp_product_list.id as product_id',
                'erp_stock_order.warehouse_id',
                'erp_product_list.number as product_num',
                'product_class.name as product_class_name',
                'product_brand.name as product_brand_name',
                'product_series.name as product_series_name',
            ]) -> get();

        foreach($order_info as $k => $vo){
            //通过库位ID 查找 仓库id

            $temp = DB::table('erp_stock')
                -> where([
                    'flag' => 0,
                    'business_id' => $request -> business_id,
                    'warehouse_id' => $vo -> warehouse_id,
                    'product_id' => $vo -> product_id
                ]) -> first();
            if($temp){
                $order_info[$k] -> stock_num = intval($temp -> can_buy_num) + intval($vo -> order_info_num);
            }else{
                $order_info[$k] -> stock_num = intval($vo -> order_info_num);
            }


            //退货数量
            $order_info[$k] -> return_num = 0;
            //最终数量
            $order_info[$k] -> last_num = intval($vo -> order_info_num) - intval($vo -> send_num);

        }

        return [
            'order' => $order,
            'order_info' => $order_info
        ];



    }


    //库存订单详情编辑
    public function editStockOrder(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
            'stock_order_id' => 'required|numeric',
            'sale_date' => 'required|date', //销售日期
            'warehouse_id' => 'required|numeric|exists:erp_warehouse,id', //仓库id
            //'storehouse_id' => 'required|numeric|exists:erp_storehouse,id', //库位id
            'user_id' => 'required|numeric|exists:wxuser,id', //客户id
            'sale_user_id' => 'required|numeric|exists:users,id', //销售员id
            //'freight' => 'required|numeric', //运费
            //'substitute' => 'required|numeric', //代发费
            'send_remark' => 'required|numeric', //发货备注 0发货1不发货2预定

            //'province' => 'required|max:10',
            //'city' => 'required|max:20',
            //'area' => 'required|max:20',
            //'tel' => 'required|numeric|size:11',
            //'phone' => '' 电话 非必传
            //'name' => 'required|max:10', //收件人姓名
            //'address' => 'required|max:30',



            //'send_name' => 'required|max:10',
            //'send_tel' => 'required|numeric|size:11',
            //'send_province' => 'required|max:10',
            //'send_city' => 'required|max:10',
            //'send_area' => 'required|max:10',
            //'send_address' => 'required|max:30',
            //'remark' =>  非必填
            'order_info_json' => 'required|json'   //库存销售订单 新增明细   [{"goods_id 商品id":"1","price价格":"124","number数量":"55"},{}]

            //send_remark 发货备注 0发货1不发货2预定
            //sale_remark 销售备注


        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $model = new StockOrder();
        return $model -> editOrder($request->stock_order_id,$request -> order_info_json,$request -> warehouse_id,$request -> business_id,$request);


    }



    //单独编辑发货备注
    public function editOrderRemark(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
            'stock_order_id' => 'required|numeric',
            'send_remark' => 'required|numeric', //发货备注 0发货1不发货2预定
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        DB::table('erp_stock_order') -> where([
            'id' => $request -> stock_order_id,
            'business_id' => $request -> business_id,
            'flag' => 0
        ]) -> update([
            'send_remark' => $request -> send_remark,
            'updated_at' => time()
        ]);

        return [
            'code' => 200,
            'msg' => '修改成功'
        ];


    }

    //删除库存销售单
    //如果此销售单 已经付款， 或者已经发货则不允许
    public function deleteOrder(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
            'stock_order_id' => 'required|numeric' //库存订单id
        ]);

        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $stock_order = new StockOrder();
        return $stock_order -> deleteOrder($request -> stock_order_id,$request -> business_id);
    }


    //保存客户的发货地址 收货地址
    public function saveAddress(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
            'user_id' => 'required|numeric', //客户id
            'province' => 'required',
            'city' => 'required',
            'area' => 'required',
            'address' => 'required',
            'name' => 'required',
            'address_type' => 'required'  //地址类型 0 发货 1 收货

        ]);

        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        //保存客人的收货地址
        $info = DB::table('erp_user_address') -> where([
            'business_id' => $request -> business_id,
            'user_id' => $request -> user_id,
            'province' => $request -> province,
            'city' => $request -> city,
            'area' => $request -> area,
            'address' => $request -> address,
            'name' => $request -> name,
            'tel' => $request -> tel,
            'phone' => $request -> phone,
        ]) -> first();
        if($info){
            return [
                'code' => 500,
                'msg' => '地址已存在'
            ];
        }else{
            DB::table('erp_user_address') -> insertGetId([
                'business_id' => $request -> business_id,
                'user_id' => $request -> user_id,
                'province' => $request -> province,
                'city' => $request -> city,
                'area' => $request -> area,
                'address' => $request -> address,
                'name' => $request -> name,
                'tel' => $request -> tel,
                'phone' => $request -> phone,
                'address_type' => $request -> address_type,
                'created_at' => time(),
                'updated_at' => time(),
            ]);

            return [
                'code' => 200,
                'msg' => '添加成功'
            ];
        }





    }

    //获取客户的发货地址、收货地址
    public function getUserAddress(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
            'user_id' => 'required|numeric', //客户id
        ]);

        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $info = DB::table('erp_user_address') -> where([
            'business_id' => $request -> business_id,
            'user_id' => $request -> user_id,
            'flag' => 0
        ]) -> get();


        //收件人
        $options_fa = [];
        //发货人
        $options_send = [];

        foreach($info as $k => $vo){
            if($vo -> address_type == 1){
                //收货
                $options_fa[$k]['name'] = $vo -> name.'-'.$vo -> tel;
                $options_fa[$k]['value'] = $vo -> id;
            }else{
                //发货
                $options_send[$k]['name'] = $vo -> name.'-'.$vo -> tel;
                $options_send[$k]['value'] = $vo -> id;
            }
        }



        return [
            'code' => 200,
            'data' => $info,
            'options_fa' => $options_fa,
            'options_send' => $options_send
        ];
    }


    //销售明细
    public function saleDetails(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
        ]);

        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }
        $user = auth('api')->user();
        $model_has_roles = DB::table('model_has_roles')
            -> leftJoin('roles','model_has_roles.role_id','roles.id')
            -> where([
                'model_has_roles.model_id' => $user -> id
            ])
            -> select([
                'roles.name as  roles_name'
            ])
            -> get();

        //是不是客服角色
        $is_kefu_roles = 0;
        if($model_has_roles){
            foreach($model_has_roles as $vo){
                if(strstr($vo -> roles_name,'客服')){
                    $is_kefu_roles = 1;
                    break;
                }
            }
        }

        $data = DB::table('erp_stock_order_info')
            ->leftJoin('erp_stock_order','erp_stock_order_info.stock_order_id','erp_stock_order.id')
            // ->leftJoin('price_log', 'price_log.order_id', 'erp_stock_order.id')
            ->leftJoin('erp_stock_order_info_receive', 'erp_stock_order_info.id', 'erp_stock_order_info_receive.stock_order_info_id')

            -> leftJoin('wxuser','erp_stock_order.user_id','wxuser.id')
            -> leftJoin('users','erp_stock_order.sale_user_id','users.id')
            -> leftJoin('erp_product_list','erp_stock_order_info.product_id','erp_product_list.id')
            //类别
            -> leftJoin('erp_product_class as product_class','erp_product_list.class_id','product_class.id')
            //品牌
            -> leftJoin('erp_product_class as product_brand','erp_product_list.brand_id','product_brand.id')
            //系列
            -> leftJoin('erp_product_class as product_series','erp_product_list.series_id','product_series.id')
            // // 成本
            ->leftJoin('erp_receive_goods_record', 'erp_receive_goods_record.id', 'erp_stock_order_info_receive.receive_goods_record_id')
            // // 发货仓库
            ->leftJoin('erp_warehouse', 'erp_warehouse.id', 'erp_receive_goods_record.warehouse_id')
            // // 发货库位
            ->leftJoin('erp_storehouse', 'erp_storehouse.id', 'erp_receive_goods_record.store_house_id')
            // // 收货仓库对应的事业部
            ->leftJoin('erp_business', 'erp_business.id', 'erp_receive_goods_record.business_id')
            // // 发货时间，发货记录
            // ->leftJoin('erp_deliver_goods_record', 'erp_deliver_goods_record.stock_order_id', 'erp_stock_order_info.stock_order_id')
            ->leftJoin('erp_deliver_goods_record_info', 'erp_deliver_goods_record_info.stock_order_info_receive_id', 'erp_stock_order_info_receive.id')
            ->leftJoin('erp_deliver_goods_record', 'erp_deliver_goods_record.id', 'erp_deliver_goods_record_info.deliver_goods_record_id')
            -> where([
                'erp_stock_order.business_id' => $request -> business_id,
                'erp_stock_order_info.flag' => 0,
                'erp_stock_order.flag' => 0,
                'erp_stock_order_info_receive.flag' => 0,
                'erp_receive_goods_record.flag' => 0,
                'erp_deliver_goods_record_info.flag' => 0,
                'erp_deliver_goods_record.flag' => 0
                // 'wxuser.in_black_list' => 0
                // 'erp_stock_order.pay_status' => 1
            ])
            -> where(function($query)use($is_kefu_roles,$user){
                if($is_kefu_roles){
                    $query -> where('erp_stock_order.operator_user_id',$user -> id);
                }
            })

            -> where(function($query)use($request){
                if($request -> product_no){
                    $query -> where('erp_product_list.product_no',trim($request -> product_no));
                }
                if($request -> product_name){
                    $query -> where('erp_product_list.product_name','like','%'.trim($request -> product_name).'%');
                }
                if($request -> deal_date_left){
                    $query -> where('erp_stock_order.created_at','>=',strtotime($request -> deal_date_left));
                }
                if($request -> deal_date_right){
                    $query -> where('erp_stock_order.created_at','<=',strtotime($request -> deal_date_right));
                }
                if($request -> class_id){
                    $query -> where('erp_product_list.class_id',$request -> class_id);
                }
                if($request -> brand_id){
                    $query -> where('erp_product_list.brand_id',$request -> brand_id);
                }
                if($request -> series_id){
                    $query -> where('erp_product_list.series_id',$request -> series_id);
                }
                if($request -> order_num){
                    $query -> where('erp_stock_order.order_num',$request -> order_num);
                }
                if($request -> user_id){
                    $query -> where('erp_stock_order.user_id',$request -> user_id);
                }



            })

            -> select([
                'erp_stock_order_info.*',
                'erp_stock_order.created_at as pay_timestamp', // 销售时间
                'erp_product_list.product_no', // 商品编号
                'erp_product_list.product_name', // 商品名称
                'erp_stock_order_info.price as sale_price', // 售价
                'erp_stock_order_info.number as num', // 销售订单上的商品数量
                'erp_product_list.id as product_id',
                'erp_stock_order.warehouse_id',
                'erp_stock_order.order_num', // 订单编号
                'erp_product_list.number as product_num',
                'erp_product_list.model',
                'product_class.name as product_class_name',
                'product_brand.name as product_brand_name',
                'product_series.name as product_series_name',
                'wxuser.nickname',
                'users.name as saler',
                'erp_deliver_goods_record_info.send_num', // 订单中 仓库 的 某一库位 的 已发货 数量 （商品的来源可能来自不同仓库的多个库位拼凑）
                'erp_stock_order_info_receive.number as deduct_num', // 订单中 仓库 的 某一库位 取出的数量
                'erp_receive_goods_record.cost as costPrice', // 商品的成本
                'erp_deliver_goods_record.record_num as send_no', // 发货记录的编号
                'erp_deliver_goods_record.created_at as send_time', // 订单的发货时间
                'erp_storehouse.name as storehouse_name', // 库位名称
                'erp_warehouse.name as warehouse_name', // 仓库名称
                'erp_business.name as business_name' // 事业部名称
            ])
            -> orderBy('erp_stock_order.created_at','desc')
            -> paginate(isset($request -> per_page)?$request -> per_page:20);

        foreach($data as $k => $vo){
            $data[$k] -> sale_date = date('Y-m-d H:i',$vo -> pay_timestamp);
            if ($vo -> send_no) {
                $data[$k] -> send_time = date('Y-m-d H:i', $vo -> send_time);
            }
            $vo->sale_price = number_format($vo->sale_price, 2);
            $vo->warehouse = "$vo->business_name / $vo->warehouse_name / $vo->storehouse_name";
        }

        // 如果不需要成本，过滤信息去掉成本字段
        if (!$request->needCost) {
            foreach($data as $key => $value) {
                unset($value->costPrice);
            }
        }

        // return $data;
        return [
            'code' => 200,
            'data' => $data
        ];


    }



    //待付款列表
    public function waitPayOrderList(Request $request){

        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }






    }


    //销售单退货
    public function backOrderList(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $orders = DB::table('erp_stock_order as order')
            -> leftJoin('wxuser','order.user_id','wxuser.id')
            -> leftJoin('erp_warehouse','order.warehouse_id','erp_warehouse.id')
            -> leftJoin('users','order.sale_user_id','users.id')
            -> select([
                'order.*',
                'erp_warehouse.name as erp_warehouse_name',
                'users.name as sale_user_name',
                'wxuser.nickname'
            ])
            -> where([
                'order.business_id' => $request -> business_id,
                'order.flag' => 0,
            ])

            -> where(function($query)use($request){
                //订单编号
                if($request -> order_num ){
                    $query -> where('order.order_num',$request -> order_num);
                }
                //销售日期
                if($request -> sale_date_left){
                    $query -> where('order.sale_date','>=',strtotime($request -> sale_date_left));
                }
                if($request -> sale_date_right){
                    $query -> where('order.sale_date','<=',strtotime($request -> sale_date_right));
                }

                //客户id
                if($request -> user_id){
                    $query -> where('order.user_id',$request -> user_id);
                }

                //销售员
                if($request -> sale_user_id){
                    $query -> where('order.sale_user_id',$request -> sale_user_id);
                }

                //付款状态
                if($request -> sale_status){
                    $query -> where('order.pay_status',$request -> sale_status);
                }

                //发货备注
                if($request -> send_mark != ''){
                    //发货备注 0发货 1不发货 2预定
                    $query -> where('order.send_remark',$request -> send_mark);
                }


                //订单状态
                if($request -> send_status){
                    //发货状态 0未发货 1已发货 2发货中
                    $query -> where('order.send_status',$request -> send_status);
                }

                //仓库筛选
                if($request -> warehouse_id){
                    $query -> where('order.warehouse_id',$request -> warehouse_id);
                }


            })
            -> orderBy('id','desc')
            -> paginate(isset($request -> per_page)?$request -> per_page:20);

        //send_status 发货状态 0未发货 1已发货 2发货中
        foreach($orders as $k => $vo){

            if($vo -> send_status == 2){
                $orders[$k] -> send_status_str = '发货中';
            }elseif($vo -> send_status == 1){
                $orders[$k] -> send_status_str = '已发货';
            }else{
                $orders[$k] -> send_status_str = '未发货';
            }


            //发货备注 0发货1不发货2预定
            if($vo -> send_remark == 2){
                $orders[$k] -> send_remark_str = '预定';
            }elseif($vo -> send_remark == 1){
                $orders[$k] -> send_remark_str = '不发货';
            }else{
                $orders[$k] -> send_remark_str = '发货';
            }


            //付款状态
            if($vo -> pay_status){
                $orders[$k] -> pay_status_str = '已付款';
            }else{
                $orders[$k] -> pay_status_str = '未付款';
            }

            //打印配货单
            if($vo -> print_distribution){
                $orders[$k] -> distribute_order = '已打印';
            }else{
                $orders[$k] -> distribute_order = '';
            }

            //打印面单
            if($vo -> print_express){
                $orders[$k] -> express_order = '已打印';
            }else{
                $orders[$k] -> express_order = '';
            }


            $orders[$k] -> price_all = round(floatval($vo -> price) + floatval($vo -> substitute) + floatval($vo -> freight),2);


        }
        return $orders;
    }


    //员工销售周报
    public function saleSheetWeek(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }


        //找出这个事业部 所有的销售
        //先找这个事业部 所有的销售角色
        $user_ids = DB::table('model_has_roles')
            -> leftJoin('roles','model_has_roles.role_id','roles.id')
            -> where([
                'roles.business_id' => $request -> business_id
            ])
            -> where('roles.name','like','%销售%')
            -> pluck('model_id');



        $users = DB::table('users')
            -> whereIn('id',$user_ids) -> get();




        if($request -> year){
            $year = $request -> year;
        }else{
            $year = date('Y');
        }

        if($request -> week){
            $week = $request -> week;
        }else{
            $week = date('W');
            Log::info('第几周？'.$week);
        }
        $return_arr = [];


        $start_week = $this -> weekday($year,$week);
        $start_date = $start_week['start'];
        $end_date = $start_week['end'];

        for($i = $start_date;$i <= $end_date;$i+=86400){
            Log::info($i);
            foreach($users as $vo){
                //这天的时间
                $time = $i;

                //查日报表 此人 这周7天的数据
                $price = DB::table('erp_sale_sheet')
                    -> where([
                        'user_id' => $vo -> id
                    ])
                    -> where('date_str','=',$time)
                    -> sum('price');

                $number = DB::table('erp_sale_sheet')
                    -> where([
                        'user_id' => $vo -> id
                    ])
                    -> where('date_str','=',$time)
                    -> sum('number');

                $return_arr[$vo -> name][date('Y-m-d',$i)]['price'] = $price; //销售金额
                $return_arr[$vo -> name][date('Y-m-d',$i)]['number'] = $number; //销售单数

            }
        }

        return [
            'data' => $return_arr,
            'week' => date('W'),
            'year' => date('Y'),
        ];



    }



    //员工销售月报
    public function saleSheetMonth(Request $request){
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }


        //找出这个事业部 所有的销售
        //先找这个事业部 所有的销售角色
        $user_ids = DB::table('model_has_roles')
            -> leftJoin('roles','model_has_roles.role_id','roles.id')
            -> where([
                'roles.business_id' => $request -> business_id
            ])
            -> where('roles.name','like','%销售%')
            -> pluck('model_id');



        $users = DB::table('users')
            -> whereIn('id',$user_ids) -> get();




        $year = date('Y');
        $return_arr = [];
        for($i=1;$i<=12;$i++){

            foreach($users as $vo){
                //这个月1号的时间戳
                $time = $year.substr(strval($i+100),1,2).'01';

                //下个月1号的时间戳  如果是12月 找下一年1月1号的时间戳
                if($i == 12){
                    $time_end = ($year+1).'0101';
                }else{
                    $time_end = $year.substr(strval($i+1+100),1,2).'01';
                }


                //查日报表 此人 这个月的数据
                $price = DB::table('erp_sale_sheet')
                    -> where([
                        'user_id' => $vo -> id
                    ])
                    -> where('date_str','>=',strtotime($time))
                    -> where('date_str','<',strtotime($time_end))
                    -> sum('price');

                $number = DB::table('erp_sale_sheet')
                    -> where([
                        'user_id' => $vo -> id
                    ])
                    -> where('date_str','>=',strtotime($time))
                    -> where('date_str','<',strtotime($time_end))
                    -> sum('number');

                $return_arr[$vo -> name][$i]['price'] = $price; //销售金额
                $return_arr[$vo -> name][$i]['number'] = $number; //销售单数

            }

        }

        return [
            'data' => $return_arr,
        ];
    }


    //销售月报 带成本
    public function saleSheetMonthCost(Request $request) {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $year = $request->year ? $request->year : date('Y');
        $first_half_firstDay = strtotime("{$year}-01-01");
        $first_half_lastDay = strtotime("{$year}-06-30");

        $second_half_firstDay = strtotime("{$year}-07-01");
        $second_half_lastDay = strtotime("{$year}-12-31");

        // 上半年数据
        $first_half_year = $this->searchData($request->business_id, $first_half_firstDay, $first_half_lastDay);
        $first_half_result = $this->tableData($first_half_year, 'Y-m', $year, $first_half_firstDay, 6, $request->needCost);

        // 下半年数据
        $second_half_year = $this->searchData($request->business_id, $second_half_firstDay, $second_half_lastDay);
        $second_half_result = $this->tableData($second_half_year, 'Y-m', $year, $second_half_firstDay, 6, $request->needCost);

        // 季度数据
        $season = [];
        foreach($first_half_result as $month => $data) {
            $obj = [];
            if ($request->needCost) {
                $obj = [
                    'type' => $data['type'],
                    // "sale_price_1" => number_format(floatval($data['sale_price_1']) + floatval($data['sale_price_2']) + floatval($data['sale_price_3']), 2, '.', ''),
                    "sale_price_1" => bcadd( $data['sale_price_1'], bcadd($data['sale_price_2'], $data['sale_price_3'], 1), 1 ),
                    // "sale_price_2" => number_format(floatval($data['sale_price_4']) + floatval($data['sale_price_5']) + floatval($data['sale_price_6']), 2, '.', ''),
                    "sale_price_2" => bcadd( $data['sale_price_4'], bcadd($data['sale_price_5'], $data['sale_price_6'], 1), 1 ),
                    // "cost_1" => number_format(floatval($data['cost_1']) + floatval($data['cost_2']) + floatval($data['cost_3']), 2, '.', ''),
                    "cost_1" => bcadd( $data['cost_1'], bcadd($data['cost_2'], $data['cost_3'], 1), 1 ),
                    // "profit_1" => number_format(floatval($data['profit_1']) + floatval($data['profit_2']) + floatval($data['profit_3']), 2, '.', ''),
                    "profit_1" => bcadd( $data['profit_1'], bcadd($data['profit_2'], $data['profit_3'], 1), 1 ),
                    // "cost_2" => number_format(floatval($data['cost_4']) + floatval($data['cost_5']) + floatval($data['cost_6']), 2, '.', ''),
                    "cost_2" => bcadd( $data['cost_4'], bcadd($data['cost_5'], $data['cost_6'], 1), 1 ),
                    // "profit_2" => number_format(floatval($data['profit_4']) + floatval($data['profit_5']) + floatval($data['profit_6']), 2, '.', '')
                    "profit_2" => bcadd( $data['profit_4'], bcadd($data['profit_5'], $data['profit_6'], 1), 1 )
                ];
            } else {
                $obj = [
                    'type' => $data['type'],
                    // "sale_price_1" => number_format(floatval($data['sale_price_1']) + floatval($data['sale_price_2']) + floatval($data['sale_price_3']), 2, '.', ''),
                    "sale_price_1" => bcadd(
                        bcadd($data['sale_price_1'], $data['sale_price_2'], 1),
                        $data['sale_price_3'],
                        1
                    ),
                    // "sale_price_2" => number_format(floatval($data['sale_price_4']) + floatval($data['sale_price_5']) + floatval($data['sale_price_6']), 2, '.', '')
                    "sale_price_2" => bcadd(
                        bcadd($data['sale_price_4'], $data['sale_price_5'], 1),
                        $data['sale_price_6'],
                        1
                    )
                ];
            }

            $obj_2 = array_filter($second_half_result, function ($item) use ($obj) {
                return $item['type'] === $obj['type'];
            }, ARRAY_FILTER_USE_BOTH);
            $obj_2 = array_values($obj_2)[0];

            // $obj['sale_price_3'] = number_format(floatval($obj_2['sale_price_1']) + floatval($obj_2['sale_price_2']) + floatval($obj_2['sale_price_3']), 2, '.', '');
            $obj['sale_price_3'] = bcadd( $obj_2['sale_price_1'], bcadd($obj_2['sale_price_2'], $obj_2['sale_price_3'], 1), 1 );
            $obj['sale_price_4'] = bcadd( $obj_2['sale_price_4'], bcadd($obj_2['sale_price_5'], $obj_2['sale_price_6'], 1), 1 );
            $obj['sale_price_5'] = bcadd( $data['sale_price_7'], $obj_2['sale_price_7'], 1 );
            if ($request->needCost) {
                // $obj['cost_3'] = number_format(floatval($obj_2['cost_1']) + floatval($obj_2['cost_2']) + floatval($obj_2['cost_3']), 2, '.', '');
                $obj['cost_3'] = bcadd($obj_2['cost_1'], bcadd($obj_2['cost_2'], $obj_2['cost_3'], 1), 1);
                $obj['cost_4'] = bcadd($obj_2['cost_4'], bcadd($obj_2['cost_5'], $obj_2['cost_6'], 1), 1);
                $obj['profit_3'] = bcadd($obj_2['profit_1'], bcadd($obj_2['profit_2'], $obj_2['profit_3'], 1), 1);
                $obj['profit_4'] = bcadd($obj_2['profit_4'], bcadd($obj_2['profit_5'], $obj_2['profit_6'], 1), 1);
                $obj['cost_5'] = bcadd($data['cost_7'], $obj_2['cost_7'], 1);
                $obj['profit_5'] = bcadd($data['profit_7'], $obj_2['profit_7'], 1);
            }
            $season[] = $obj;
        }

        foreach($second_half_result as $month => $data) {
            $filter = array_filter($season, function ($item) use ($data) {
                return $item['type'] === $data['type'];
            });
            $filter = array_values($filter);
            if (!$filter) {
                if ($request->needCost) {
                    $season[] = [
                        'type' => $data['type'],
                        'sale_price_1' => '0.0',
                        'cost_1' => '0.0',
                        'profit_1' => '0.0',
                        'sale_price_2' => '0.0',
                        'cost_2' => '0.0',
                        'profit_2' => '0.0',
                        'sale_price_3' => bcadd($data['sale_price_1'], bcadd($data['sale_price_2'], $data['sale_price_3'], 1), 1),
                        'cost_3' => bcadd($data['cost_1'], bcadd($data['cost_2'], $data['cost_3'], 1), 1),
                        'profit_3' => bcadd($data['profit_1'], bcadd($data['profit_2'], $data['profit_3'], 1), 1),
                        'sale_price_4' => bcadd($data['sale_price_4'], bcadd($data['sale_price_5'], $data['sale_price_6'], 1), 1),
                        'cost_4' => bcadd($data['cost_4'], bcadd($data['cost_5'], $data['cost_6'], 1), 1),
                        'profit_4' => bcadd($data['profit_4'], bcadd($data['profit_5'], $data['profit_6'], 1), 1),
                        'sale_price_5' => $data['sale_price_5'],
                        'cost_5' => $data['cost_5'],
                        'profit_5' => $data['profit_5']
                    ];
                } else {
                    $season[] = [
                        'type' => $data['type'],
                        'sale_price_1' => '0.0',
                        'sale_price_2' => '0.0',
                        'sale_price_3' => bcadd(($data['sale_price_1']), bcadd($data['sale_price_2'], $data['sale_price_3'], 1), 1),
                        'sale_price_4' => bcadd(($data['sale_price_4']), bcadd($data['sale_price_5'], $data['sale_price_6'], 1), 1),
                        'sale_price_5' => $data['sale_price_5']
                    ];
                }
            }
        }

        $total = [];
        foreach($season as $index => $value) {
            if ($value['type'] === '总计') {
                $total = $value;
                unset($season[$index]);
            }
        }
        $season[] = $total;
        $season = array_values($season);

        return [
            'data1' => $first_half_result,
            'data2' => $second_half_result,
            'data3' => $season
        ];
    }

    /**
     * @description 周数的计算
     * 根据ISO 8601 的规则。
     * 1、每年有52周或者53周
     * 2、周一至周日为一个完整周。
     * 3、每周的周一是该周的第1天。周日是该周的第7天
     * 4、每年的第一周 为 每年的第一个周四所在的周。比如 2017年1月5日为当年的第一个周四，那么 2017-01-02 至 2017-01-08 为2017年第一周。
     * 5、每年的最后一周为当年最后一个周四所在的周。比如2016年12月29日为当年的最后一个周四，那么2016-12-26 至2017-01-01 为2016年的最后一周。
     */
    //销售周报（带成本）
    public function saleSheetWeekCost(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        // 记录 年份 和 周数
        $year = $request->year ? $request->year : date('Y');
        $week = $request->week ? $request->week : date('W');

        // 指定周的第一天和最后一天的时间戳
        $date = new \DateTime();
        // 开始的时间
        $date->setISODate($year, $week, 1);
        $start_date = strtotime($date->format('Y-m-d'));
        // 结束时间
        $date->setISODate($year, $week, 7);
        $end_date = strtotime($date->format('Y-m-d')) + 86400;

        $temp_week = [];
        for($time = $start_date; $time < $end_date; $time += 86400) {
        $temp_week[] = date('Y-m-d', $time);
        }

        // 搜索一周的发货数据
        $data = $this->searchData($request->business_id, $start_date, $end_date);

        $result = $this->tableData($data, 'Y-m-d', $year, $start_date, 7, $request->needCost);

        return [
            'data' => $result,
            'temp_week' => $temp_week,
            'week' => $week,
            'year' => $year,
        ];
    }

    //销售周报显示详情
    public function getWeekDetail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
            'categoryId' => 'required|numeric', // 产品分类id, 查询所有就是为-1
            'startDate' => 'required|string',
            'endDate' => 'required|string'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $start_time = strtotime($request->startDate);
        $end_time = strtotime($request->endDate) + 86400;
        $data = $this->searchData($request->business_id, $start_time, $end_time, $request->categoryId);

        foreach($data as $key => $value) {
            $value->created_at = date('Y-m-d H:i', $value->created_at);
            if ($request->needCost) {
                $value->profit = number_format(($value->price - $value->cost) * $value->number, 2, '.', '');
                $value->price = number_format($value->price, 2, '.', '');
            } else {
                unset($value->cost);
            }
        }
        return [
            'status' => 1,
            'data' => $data
        ];
    }

    /**
     * @description 检索某一时间范围内的销售记录（以发货记录为准）
     * @param int $start_time 查询数据的起始时间
     * @param int $end_time 查询数据的结束时间
     * @param int $class_id 查询指定商品分类的id
     * @return array 返回检索到的数据
     */
    public function searchData(int $business_id, int $start_time, int $end_time, int $class_id = -1) :array
    {
        return DB::table('erp_stock_order as order')
            ->leftJoin('erp_stock_order_info as orderInfo', 'orderInfo.stock_order_id', 'order.id')
            ->leftJoin('erp_stock_order_info_receive as orderInfoReceive', 'orderInfoReceive.stock_order_info_id', 'orderInfo.id')
            ->leftJoin('erp_receive_goods_record as receiveGoodsRecord', 'receiveGoodsRecord.id', 'orderInfoReceive.receive_goods_record_id')
            ->leftJoin('erp_deliver_goods_record_info as deliverRecordInfo', 'deliverRecordInfo.stock_order_info_receive_id', 'orderInfoReceive.id')
            ->leftJoin('erp_deliver_goods_record as deliverRecord', 'deliverRecord.id', 'deliverRecordInfo.deliver_goods_record_id')
            ->leftJoin('erp_product_list as product', 'product.id', 'orderInfo.product_id') // 商品名称
            ->leftJoin('erp_product_class as brand', 'product.brand_id', 'brand.id') // 品牌
            ->leftJoin('erp_product_class as productClass', 'productClass.id', 'product.class_id') // 类别
            ->leftJoin('erp_product_class as series', 'series.id', 'product.series_id') // 系列
            ->leftJoin('wxuser', 'order.user_id', 'wxuser.id') // 客户
            ->where([
                'order.flag' => 0,
                'order.send_status' => 1, // 发货完成的
                'orderInfo.flag' => 0,
                'orderInfoReceive.flag' => 0,
                'receiveGoodsRecord.flag' => 0,
                'deliverRecordInfo.flag' => 0,
                'deliverRecord.flag' => 0,
                'wxuser.in_black_list' => 0,
                'order.business_id' => $business_id
            ])
            ->whereBetween('deliverRecord.created_at', [$start_time, $end_time])
            ->where(function ($query) use ($class_id) {
                if ($class_id > -1) {
                    $query->where(['productClass.id' => $class_id]);
                }
            })
            ->select([
                'order.order_num', // 订单编号
                'wxuser.nickname', // 客户名称
                'deliverRecordInfo.send_num as number', // 发货数量
                'orderInfo.price', // 售价
                'receiveGoodsRecord.cost', // 成本
                'product.product_name', // 商品名称
                'product.product_no', // 商品编号
                'product.model', // 型号
                'brand.name as brand_name', // 品牌
                'productClass.name as category_name', // 类别
                'series.name as series_name', // 系列
                'deliverRecord.created_at', // 发货日期
                'productClass.id as category_id', // 类别id
            ])
            ->orderBy('productClass.id', 'asc')
            ->orderBy('deliverRecord.created_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * @description 将传入的数据整理成element-ui中table组件所需要的数组格式
     * @param array $data 需要整理的数据
     * @param string $format 按什么格式切分日期区间（Y-m-d:按天，Y-m:按月)(table中每一列对应的天/月)
     * @param int $year 所属年
     * @param int $start_time 起始时间
     * @param int $end_time 截止时间
     * @param int $column_number table中有多少列(注意：不算总计的那一列)
     * @param boolean need_cost 是否需要展示成本和利润
     * @return array 整理完成的数组数据
     */
    public function tableData(array $data, string $format, int $year, int $start_time, int $column_number, bool $need_cost) :array
    {
        $date_arr = [];
        $startTimestamp = $start_time;
        for ($index = 1; $index <= $column_number; $index++) {
            if ($column_number === 7) { // 按周展示
                $date_arr[$startTimestamp] = [];
                $startTimestamp += 86400;
            } elseif ($column_number === 6) { // 按月展示
                if (date('m', $start_time) === '01') { // 上半年
                    $date_arr[strtotime("$year-$index")] = [];
                } else { // 下半年
                    $month = $index + 6;
                    $date_arr[strtotime("$year-$month")] = [];
                }
            }
        }

        $class_arr = [];
        foreach($data as $key => $value) {
            if (!isset($class_arr[$value->category_name])) {
                $class_arr[$value->category_name] = [];
            }
            $dayTime = strtotime(date($format, $value->created_at));
            $date_arr[$dayTime][] = $value;
        }

        // 返回给前端的数组格式整理
        $result = [];
        $all_sale_price = 0; // 总销售额
        $all_cost = 0; // 总成本
        $all_profit = 0; // 总利润
        $last_column = $column_number + 1;
        foreach($class_arr as $className => $class_name_item) {
            $index = 1;
            $total_price = 0; // 每一类的总销售额
            $total_cost = 0; // 每一类的总成本
            $total_profit = 0; // 每一类的总利润
            foreach($date_arr as $date_item) {
                $today_sale_price = 0; // 当 日/月 的总销售额
                $today_cost = 0; // 当 日/月 的总销成本
                foreach($date_item as $item) {
                    $today_sale_price = bcadd($item->price * $item->number, $today_sale_price, 1);
                    $today_cost = bcadd($item->cost * $item->number, $today_cost, 1);
                }

                $class_arr['total']["sale_price_{$index}"] = $today_sale_price;
                if ($need_cost) {
                    $class_arr['total']["cost_{$index}"] = $today_cost;
                    $class_arr['total']["profit_{$index}"] = bcsub($today_sale_price, $today_cost, 1);  // 当 日/月 的总销利润
                }

                // 过滤出当天的某一类商品
                $filter = array_filter($date_item, function($item) use ($className) {
                    return $item->category_name === $className;
                });
                $sale_price = 0; // 这类商品当天的销售额
                $cost = 0; // 这类商品当天的成本
                $profit = 0; // 这类商品当天的利润
                $categoryId = null; // 商品分类的id
                foreach($filter as $item) {
                    $sale_price = bcadd($item->price * $item->number, $sale_price, 1);
                    $cost = bcadd($item->cost * $item->number, $cost, 1);
                    $categoryId = $item->category_id;
                }
                if (!isset($class_arr[$className]['category_id'])) {
                    $class_arr[$className]['category_id'] = $categoryId;
                }
                $class_arr[$className]["sale_price_{$index}"] = $sale_price;
                if ($need_cost) {
                    $class_arr[$className]["cost_{$index}"] = $cost;
                    $class_arr[$className]["profit_{$index}"] = bcsub($sale_price, $cost, 1);
                }
                $total_price = bcadd($sale_price, $total_price, 1);
                $total_cost = bcadd($total_cost, $cost, 1);
                $total_profit = bcadd(bcsub($sale_price, $cost, 1), $total_profit, 1);
                $index++;
            }
            $class_arr[$className]['type'] = $className;
            // $class_arr[$className]['category_id'] = $class_name_item['category_id'];
            $class_arr[$className]["sale_price_{$last_column}"] = $total_price;
            if ($need_cost) {
                $class_arr[$className]["cost_{$last_column}"] = $total_cost;
                $class_arr[$className]["profit_{$last_column}"] = $total_profit;
            }

            $all_sale_price = bcadd($total_price, $all_sale_price, 1);
            $all_cost = bcadd($total_cost, $all_cost, 1);
            $result[] = $class_arr[$className];
        }
        // 每周的总利润
        $class_arr['total']["sale_price_{$last_column}"] = $all_sale_price;
        if ($need_cost) {
            $class_arr['total']["cost_{$last_column}"] = $all_cost;
            $class_arr['total']["profit_{$last_column}"] = bcsub($all_sale_price, $all_cost, 1);
        }
        $class_arr['total']["type"] = '总计';
        $result[] = $class_arr['total'];

        return $result;
    }

    public function refund(Request $request)
    {
        $where=[];
        if ($request->filled('order_num')){
            $where[]=['erp_stock_order.order_num','=',$request->order_num];
        }
        if ($request->filled('user_id')){
            $where[]=['erp_stock_order.user_id','=',$request->user_id];
        }
        if ($request->filled('sale_date_left')){
            $where[]=['erp_stock_order.sale_date','>=',$request->sale_date_left];
        }
        if ($request->filled('sale_date_right')){
            $where[]=['erp_stock_order.sale_date','=',$request->sale_date_right];
        }

        $list=DB::table('erp_refunds')
            ->leftJoin('erp_stock_order','erp_refunds.order_id','erp_stock_order.id')
            ->leftJoin('users','erp_refunds.operate_id','users.id')
            ->where($where)
            ->select([
                'erp_refunds.*',
                'erp_stock_order.id as order_id',
                'erp_stock_order.order_num',
                'users.name',
            ])
            ->paginate();
        foreach ($list as $vo){
            $vo->created_at=date('Y-m-d H:i:s',$vo->created_at);
            if ($vo->result == 1){
                $vo->result_str='已同意';
            }elseif ($vo->result == 2){
                $vo->result_str='已拒绝';
            }elseif ($vo->result === 0){
                $vo->result_str='未处理';
            }
        }
        return $list;
    }

    /**
     * 退款申请操作
     * @param Request $request
     * @return array|JsonResponse
     */
    public function refundEdit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id', //事业部id
            'id' => 'required|numeric|exists:erp_refunds,id',
            'result' => 'required|numeric|in:1,2',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        //检测状态
        $refund=DB::table('erp_refunds')
            ->leftJoin('erp_stock_order','erp_refunds.order_id','erp_stock_order.id')
            ->where('erp_refunds.id',$request->id)
            ->select([
                'erp_refunds.result',
                'erp_stock_order.flag',
                'erp_stock_order.pay_status',
                'erp_stock_order.print_express',
                'erp_stock_order.print_distribution',
            ])
            ->first();
        if ($refund->result != 0 || $refund->flag == 1 || $refund->pay_status == 0 || $refund->print_express == 1 || $refund->print_distribution == 1){
            return [
                'code'=>500,
                'msg'=>'该订单已改变状态，不允许操作'
            ];
        }

        DB::beginTransaction();
        try{
            $refund_info=Refund::find($request->id);

            //如果同意退款
            if ($request->result == 1){
                $erp_stock_order_pay = DB::table('erp_stock_order_pay')
                    -> where([
                        'order_id' => $refund_info->order_id,
                        'flag' => 0
                    ]) -> first();
                if(!$erp_stock_order_pay){
                    return [
                        'code' => 500,
                        'msg' => '退款失败'
                    ];
                }
                $stock_order=DB::table('erp_stock_order')->find($refund_info->order_id);
                DB::table('wxuser')
                    ->where('id',$stock_order->user_id)
                    ->increment('price',floatval($erp_stock_order_pay -> price));

                $wxuser = DB::table('wxuser') -> where([
                    'id' => $stock_order->user_id
                ])->first();

                DB::table('price_log') -> insert([
                    'userid'       => $stock_order -> user_id,
                    'price'        => floatval($erp_stock_order_pay -> price),
                    'created_at'   => time(),
                    'updated_at'   => time(),
                    'from_user_id' => 1,
                    'type'         => 9,//撤销
                    'in_out'       => 0,// 收入
                    'order_id'     => $stock_order->id,
                    'end_price'    => $wxuser->price,
                ]);

                //删除此条pay记录
                DB::table('erp_stock_order_pay')
                    -> where([
                        'id' => $erp_stock_order_pay->id,
                    ]) -> update([
                        'flag' => 1
                    ]);

                //订单已付金额减少
                DB::table('erp_stock_order')
                    -> where([
                        'id' => $stock_order->id,
                    ]) -> update([
                        'pay_price' => floatval($stock_order -> pay_price) - floatval($erp_stock_order_pay -> price) + floatval($erp_stock_order_pay -> service_charge),
                        'pay_status' => 0,
                    ]);
                if ($stock_order->pay_method == 1 || $stock_order->pay_method == 2){
                    //账户余额 减少
                    $erp_purchase_order_pay = DB::table('erp_purchase_order_pay')
                        -> where([
                            'order_id' => $stock_order->id,
                            'pay_type'       => 2,
                        ]) -> first();
                    if($erp_purchase_order_pay){
                        //账户余额减少 日志
                        Account::addLog([
                            'business_id'  => $request->business_id,
                            'account_id'   => $erp_purchase_order_pay -> account_id,
                            'user_id'      => $request->user()->id,
                            'log_type'     => 99,
                            'price'        => floatval($erp_stock_order_pay -> price) - floatval($erp_stock_order_pay -> service_charge),
                            'parameter_id' => $stock_order->id
                        ]);

                        //减少账户余额
                        DB::table('erp_account')
                            -> where([
                                'id' => $erp_purchase_order_pay -> account_id
                            ]) -> decrement('balance',floatval($erp_stock_order_pay -> price) - floatval($erp_stock_order_pay -> service_charge));
                    }
                }
            }

            $refund_info->update([
                'result'=>$request->result,//操作结果
                'operate_id'=>$request->user()->id,//操作人
                'updated_at'=>time(),//操作时间
                'reason'=>$request->filled('reason')?$request->reason:''//操作原因
            ]);

            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
            return [
                'code'=>500,
                'msg'=>$e->getMessage()
            ];
        }

        return [
            'code'=>200,
            'msg'=>'操作成功'
        ];
    }

    protected function getWarehouseBymp($order_info_arr,$request)
    {
        // 然后取第一个(先取自营的)
        $firstWarehouse = DB::table('erp_warehouse')
            -> where('flag', 0)
            -> where('mp_name_id', $request->mp_id)
            -> first();
        //获取仓库id结束
        return $firstWarehouse;
    }
}
