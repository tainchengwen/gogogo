<?php

namespace App\Http\Controllers\ApiShopMp;

use App\Repositories\OrderRepository;
use App\Repositories\CartRepository;
use App\Repositories\SKURepository;
use App\Repositories\AddressRepository;
use App\Repositories\FreightRepository;
use App\Repositories\CategoryRepository;
use Illuminate\Http\Request;
use App\WxUsers as ShopMPUsers;
use App\Http\Controllers\ApiShopMp\Controller;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class TestController extends Controller
{

    public function __construct(
        OrderRepository $orderRepository,
        CartRepository $cartRepository,
        FreightRepository $freightRepository,
        AddressRepository $addressRepository,
        SKURepository $skuRepository,
        CategoryRepository $categoryRepository
    ){
        $this->orderRepository    = $orderRepository;
        $this->cartRepository     = $cartRepository;
        $this->skuRepository      = $skuRepository;
        $this->freightRepository  = $freightRepository;
        $this->addressRepository  = $addressRepository;
        $this->categoryRepository = $categoryRepository;
    }
    public function test(Request $request)
    {
        echo phpinfo();
    }

    public function updateAgentPrice(Request $request)
    {
        // 找出所有的代理的
        // 然后重置价格
        $request->id = 7;
        $request->business_id = 49;
        $mpNameId   = $request->id;
        $businessId = $request->business_id;
    
        // 所有代理的spu
        $spuids = DB::table('erp_business_spu_link')
                      -> where([
                          'erp_business_spu_link.mp_name_id'  => $request->id,
                          'erp_business_spu_link.flag'        => 0,
                          'erp_business_spu_link.business_id' => $request->business_id
                        ])
                      -> pluck('spu_id');

        // 对应关联的sku
        $inskuids = DB::table('erp_spu_sku_link')
                      -> where('erp_spu_sku_link.flag', 0)
                      -> whereIn('erp_spu_sku_link.spu_id', $spuids)
                      -> distinct('sku_id')
                      -> pluck('sku_id');
        $list = DB::table('erp_product_list')
                    -> leftJoin('erp_product_price', 'erp_product_list.id', 'erp_product_price.product_id')
                    -> where('erp_product_price.mp_name_id', $request->id)
                    -> where('erp_product_price.flag', 0)
                    -> where('erp_product_price.status', 1)
                    -> whereIn('erp_product_list.id', $inskuids)
                    -> select([
                      'erp_product_list.id as id',
                      'erp_product_price.price_a',
                      'erp_product_price.price_b',
                      'erp_product_price.price_c',
                      'erp_product_price.price_d',
                      'erp_product_price.price_s',
                      'erp_product_price.mp_name_id'
                    ])
                    -> orderBy('id')
                    -> get();

        $insertData = [];
        foreach ($list as $key => $value) {
            $insertData[] = [
                'sku_id'         => $value->id,
                'business_id'    => $businessId,
                'mp_name_id'     => $value->mp_name_id,
                'price'          => $value->price_c,
                'original_price' => $value->price_d,
                'status'         => 1
            ];
        }

        // 删掉原来的
        DB::table('erp_agent_price')
        -> where('business_id', $businessId)
        -> where('mp_name_id', $mpNameId)
        -> delete();

        // 新增
        DB::table('erp_agent_price')
        -> insert($insertData);

        dd('success');
    }

    public function oneKey(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id'
        ]);

        $business_id = $request->business_id;
        // copy分类
        $categories = DB::table('erp_agent_spu_category')
        -> where('business_id', config('admin.zhanpeng_business_id'))
        -> select([
            'name',
            'image',
            'mp_flag',
            'sort_index'
        ])
        -> get();
        $insertData = [];
        foreach ($categories as $key => $value) {
            $insertData[] = [
                'name'        => $value->name,
                'image'       => $value->image,
                'mp_flag'     => $value->mp_flag,
                'sort_index'  => $value->sort_index,
                'business_id' => $business_id
            ];
        }
        DB::table('erp_agent_spu_category')
        -> insert($insertData);

        // copy 代理spu
        $zhanpengSPUs = DB::table('erp_business_spu_link as link')
        -> leftJoin('erp_agent_spu_category as category', 'link.class_id', 'category.id')
        -> where('link.business_id', config('admin.zhanpeng_business_id'))
        -> where('link.flag', 0)
        -> select([
            'link.spu_id',
            'link.status',
            'link.mp_name_id',
            'category.name'
        ])
        -> get();

        $agentSPUs = [];
        $catgoryMap = [];

        foreach ($zhanpengSPUs as $key => $value) {
            if (!empty($value->name) && empty($catgoryMap[$value->name])) {
                // 查一下
                $category = DB::table('erp_agent_spu_category')
                -> where('business_id', $business_id)
                -> where('name', $value->name)
                -> first();

                if ($category) {
                    $catgoryMap[$value->name] = $category->id;
                }
            }

            $agentSPUs[] = [
                'business_id' => $business_id,
                'spu_id'      => $value->spu_id,
                'status'      => $value->status,
                'mp_name_id'  => $value->mp_name_id,
                'class_id'    => !empty($catgoryMap[$value->name]) ? $catgoryMap[$value->name] : 0
            ];
            
        }
        DB::table('erp_business_spu_link')
        -> insert($agentSPUs);

        // copy 售价
        $zhanpengPrice = DB::table('erp_agent_price')
        -> where('flag', 0)
        -> where('business_id', config('admin.zhanpeng_business_id'))
        -> select([
            'sku_id',
            'mp_name_id',
            'price',
            'original_price',
            'status'
        ])
        -> get();

        $insterPrice = [];

        foreach ($zhanpengPrice as $key => $value) {
            $insterPrice[] = [
                'sku_id'         => $value->sku_id,
                'business_id'    => $business_id,
                'mp_name_id'     => $value->mp_name_id,
                'price'          => $value->price,
                'original_price' => $value->original_price,
                'status'         => $value->status,
            ];
        }
        DB::table('erp_agent_price')
        -> insert($insterPrice);

        dd('success');
    }

    /**
     * 获取当前上架的sku列表带价格
     */
    private function getPutOnPriceList(Request $request)
    {
        // 先拿所有的正常的skus
        $erp_mp_name_spu_link_ids = DB::table('erp_product_price as sku')
        -> leftJoin('erp_product_list', 'sku.product_id', 'erp_product_list.id')
        -> leftJoin('erp_mp_name', 'sku.mp_name_id', 'erp_mp_name.id')
        //类别
        -> leftJoin('erp_product_class as product_class','erp_product_list.class_id','product_class.id')
        //品牌
        -> leftJoin('erp_product_class as product_brand','erp_product_list.brand_id','product_brand.id')
        //系列
        -> leftJoin('erp_product_class as product_series','erp_product_list.series_id','product_series.id')
        -> where('sku.flag', 0)
        -> where('sku.status', 1)
        -> where('sku.is_show', 1)
        -> where('sku.has_stock', '>' , 0)
        -> select([
            'erp_product_list.product_name',
            "erp_mp_name.mp_name",
            "sku.price_s",
            "sku.price_a",
            "sku.price_b",
            "sku.price_c",
            "sku.price_d",
            "sku.has_stock as stock",
            'product_class.name as product_class_name',
            'product_brand.name as product_brand_name',
            'product_series.name as product_series_name'
        ])
        -> orderBy('product_class.id','desc')
        -> orderBy('sku.id','desc')
        -> get();

        return $erp_mp_name_spu_link_ids;
    }

    /**
     * 修改VIP等级
     */
    private function changeVIP()
    {
        DB::table('wxuser')
        -> where('market_class', '<=', 2)
        -> update(['market_class' => 2]);
    }

    /**
     * 物流报表
     */
    public function getV2V3 ($request)
    {

        $list = DB::table('erp_receive_goods_record')
            -> leftJoin('erp_receive_record','erp_receive_goods_record.receive_record_id','erp_receive_record.id')
            -> leftJoin('erp_storehouse','erp_receive_record.store_house_id','erp_storehouse.id')
            -> leftJoin('erp_warehouse','erp_storehouse.warehouse_id','erp_warehouse.id')
            -> leftJoin('erp_logistics_info','erp_receive_goods_record.goods_id','erp_logistics_info.id')
            -> leftJoin('erp_purchase_order_goods','erp_logistics_info.goods_id','erp_purchase_order_goods.id')
            -> leftJoin('erp_purchase_order','erp_purchase_order_goods.order_id','erp_purchase_order.id')
            -> leftJoin('erp_product_list','erp_purchase_order_goods.product_id','erp_product_list.id')
            -> leftJoin('erp_product_price','erp_product_list.id','erp_product_price.product_id')
            -> leftJoin('erp_mp_name','erp_product_price.mp_name_id','erp_mp_name.id')
            //商品去找类别、品牌、系列
            //类别
            -> leftJoin('erp_product_class as product_class','erp_product_list.class_id','product_class.id')
            //品牌
            -> leftJoin('erp_product_class as product_brand','erp_product_list.brand_id','product_brand.id')
            //系列
            -> leftJoin('erp_product_class as product_series','erp_product_list.series_id','product_series.id')
            -> where([
                'erp_receive_goods_record.business_id' => 49
            ])
            -> where(function($query)use($request){
                //入库编号
                if($request -> receive_num){
                    $query -> where('erp_receive_record.receive_num','like','%'.trim($request -> receive_num).'%');
                }
                //仓库
                if($request -> warehouse_id){
                    $query -> where('erp_storehouse.warehouse_id',$request -> warehouse_id);
                }
                //库位
                if($request -> storehouse_id){
                    $query -> where('erp_receive_record.store_house_id',$request -> storehouse_id);
                }

                //入库日期
                if($request -> receive_date_left){
                    $query -> where('erp_receive_goods_record.created_at','>=',strtotime($request -> receive_date_left));
                }
                if($request -> receive_date_right){
                    $query -> where('erp_receive_goods_record.created_at','<=',strtotime($request -> receive_date_right));
                }

                //产品编号
                if($request -> product_no){
                    $query -> where('erp_product_list.product_no','like','%'.trim($request -> product_no).'%');
                }

                //产品类别
                if($request -> class_id){
                    $query -> where('erp_product_list.class_id',$request -> class_id);
                }

                //品牌
                if($request -> brand_id){
                    $query -> where('erp_product_list.brand_id',$request -> brand_id);
                }

                //系列
                if($request -> series_id){
                    $query -> where('erp_product_list.series_id',$request -> series_id);
                }

                //订单编号
                if($request -> order_num){
                    $query -> where('erp_purchase_order.order_num','like','%'.trim($request -> order_num).'%');
                }
            })
            -> where([
                'erp_receive_goods_record.flag' => 0
            ])
            -> select([
                'erp_receive_goods_record.id',
                'erp_warehouse.name as warehouse_name',
                'erp_storehouse.name as storehouse_name',
                'erp_product_list.product_no',
                'product_class.name as product_class_name',
                'product_brand.name as product_brand_name',
                'product_series.name as product_series_name',
                'erp_product_list.product_name',
                'erp_product_list.model',
                'erp_product_list.number as product_number',
                'erp_receive_goods_record.number as ruku_number',
                'erp_receive_goods_record.can_buy_num as current_number',
                'erp_receive_goods_record.created_at',
                'erp_receive_goods_record.cost',
                'erp_product_price.price_a',
                'erp_product_price.price_b',
                'erp_product_price.price_c',
                'erp_product_price.price_d',
                'erp_mp_name.mp_name'
            ])
            -> orderBy('erp_receive_goods_record.id','desc')
            -> get();
            return $this->successResponse($request, $list);
    }

    // 下架敏感自营
    public function putOffSelf()
    {
        // agent 一票否决 is_show 改为0 就好了
        // 1、食品分类、奶粉分类下的sku
        // 2  38 分类下的所有skuIds

        // 先拿所有的正常的skus
        $ids = DB::table('erp_product_price as sku')
        -> leftJoin('erp_spu_sku_link', 'sku.product_id', 'erp_spu_sku_link.sku_id')
        -> leftJoin('erp_spu_list', 'erp_spu_sku_link.spu_id', 'erp_spu_list.id')
        -> leftJoin('erp_mp_name_spu_link', function($q) {
            $q->on('erp_mp_name_spu_link.spu_id', '=', 'erp_spu_sku_link.spu_id')
            ->on('erp_mp_name_spu_link.mp_name_id', '=', 'sku.mp_name_id');
        })
        -> where('sku.flag', 0)
        -> where('sku.status', 1)
        -> where('sku.is_show', 1)
        -> where('sku.has_stock', '>' , 0)
        -> where('erp_mp_name_spu_link.flag', 0)
        -> whereIn('erp_spu_list.class_id', [17, 38])
        -> select([
            'sku.id'
        ])
        -> pluck('id');

        $ids[] = 1614;

        DB::table('erp_product_price')
          -> whereIn('id', $ids)
          -> update(['is_show' => 0]);

        dd('success');

    }

    // 有库存，没上架的清单
    public function hasStockButNotPutOnList () {
        $a = DB::table('erp_product_price as sku')
        -> leftJoin('erp_product_list', 'sku.product_id', 'erp_product_list.id')
        -> leftJoin('erp_mp_name', 'sku.mp_name_id', 'erp_mp_name.id')
        -> where('sku.flag', 0)
        -> where('sku.has_stock', '>' , 0)
        -> where(function($query) use ($request){
            $query -> where('sku.is_show', '!=', 1)
            -> orWhere('sku.status', '!=', 1);
        })
        -> select([
            'sku.mp_name_id',
            'erp_product_list.product_no',
            'erp_product_list.product_name',
            'erp_mp_name.mp_name',
            'sku.product_id'
        ])
        ->get();
        return $this->successResponse($request, $a);
    }

    // 上架敏感
    public function putOnSelf()
    {
        // 状态正常的敏感商品改为1
        $erp_mp_name_spu_link_ids = DB::table('erp_product_price as sku')
        -> leftJoin('erp_spu_sku_link', 'sku.product_id', 'erp_spu_sku_link.sku_id')
        -> leftJoin('erp_spu_list', 'erp_spu_sku_link.spu_id', 'erp_spu_list.id')
        -> leftJoin('erp_mp_name_spu_link', function($q) {
            $q->on('erp_mp_name_spu_link.spu_id', '=', 'erp_spu_sku_link.spu_id')
            ->on('erp_mp_name_spu_link.mp_name_id', '=', 'sku.mp_name_id');
        })
        -> where('sku.flag', 0)
        -> where('sku.status', 1)
        -> where('sku.has_stock', '>' , 0)
        -> where('erp_mp_name_spu_link.flag', 0)
        -> whereIn('erp_spu_list.class_id', [17, 38])
        -> select([
            'sku.id',
            'erp_spu_list.name'
        ])
        -> get();

        dd($erp_mp_name_spu_link_ids);
    }

    // 全量主动更新小程序库存
    private function update()
    {
        $product_ids = DB::table('erp_product_price')
        -> where('flag', 0)
        -> pluck('product_id');
        $this->skuRepository->autoPutOnOrOff($product_ids);
    }

    // 批量改价格
    public function daojiage()
    {
        $data = '[
            {"id":"622","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"49325263","Vip3":"29","Vip2":"32"},
            {"id":"694","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"2000110282356","Vip3":"110","Vip2":"115.5"},
            {"id":"702","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"2000110282370","Vip3":"110","Vip2":"115.5"},
            {"id":"701","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"2000110282387","Vip3":"110","Vip2":"115.5"},
            {"id":"706","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"2000110282417","Vip3":"110","Vip2":"115.5"},
            {"id":"711","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"2000110283407","Vip3":"110","Vip2":"115.5"},
            {"id":"712","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"2000110284800","Vip3":"110","Vip2":"115.5"},
            {"id":"707","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"2000110284817","Vip3":"110","Vip2":"115.5"},
            {"id":"688","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"2000112891624","Vip3":"110","Vip2":"115.5"},
            {"id":"700","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"2000116200705","Vip3":"110","Vip2":"115.5"},
            {"id":"704","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"2000116200774","Vip3":"110","Vip2":"115.5"},
            {"id":"691","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"2000118294467","Vip3":"110","Vip2":"115.5"},
            {"id":"698","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"2000118294528","Vip3":"110","Vip2":"115.5"},
            {"id":"692","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"2000118294535","Vip3":"110","Vip2":"115.5"},
            {"id":"715","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"2000118295587","Vip3":"110","Vip2":"115.5"},
            {"id":"710","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"2000118295594","Vip3":"110","Vip2":"115.5"},
            {"id":"705","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"2000120022270","Vip3":"110","Vip2":"115.5"},
            {"id":"351","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"3614272376588","Vip3":"630","Vip2":"645"},
            {"id":"534","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4511413404874","Vip3":"25","Vip2":"27"},
            {"id":"1998","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4514254028766","Vip3":"605","Vip2":"619"},
            {"id":"432","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4514254028766","Vip3":"605","Vip2":"619"},
            {"id":"354","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4514254028766","Vip3":"605","Vip2":"619"},
            {"id":"355","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4514254028780","Vip3":"660","Vip2":"672"},
            {"id":"434","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4514254028827","Vip3":"778","Vip2":"792"},
            {"id":"1523","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4514254032275","Vip3":"400","Vip2":"409"},
            {"id":"1754","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4514254036075","Vip3":"318","Vip2":"330"},
            {"id":"1765","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4514254036082","Vip3":"318","Vip2":"330"},
            {"id":"1753","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4514254036082","Vip3":"318","Vip2":"330"},
            {"id":"1509","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4514254036082","Vip3":"318","Vip2":"330"},
            {"id":"2055","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4514254069707","Vip3":"541","Vip2":"550"},
            {"id":"1524","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4514254069905","Vip3":"755","Vip2":"780"},
            {"id":"522","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4514254099926","Vip3":"525","Vip2":"536"},
            {"id":"1764","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4514254181980","Vip3":"338","Vip2":"350"},
            {"id":"1752","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4514254181980","Vip3":"338","Vip2":"350"},
            {"id":"524","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4514254661888","Vip3":"750","Vip2":"765"},
            {"id":"1772","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4514254662540","Vip3":"441","Vip2":"455"},
            {"id":"1768","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4514254662557","Vip3":"441","Vip2":"455"},
            {"id":"2032","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4514254662571","Vip3":"561","Vip2":"575"},
            {"id":"1769","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4514254662571","Vip3":"561","Vip2":"575"},
            {"id":"1757","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4514254662571","Vip3":"561","Vip2":"575"},
            {"id":"1767","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4514254670521","Vip3":"375","Vip2":"388"},
            {"id":"1587","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4514254670521","Vip3":"375","Vip2":"388"},
            {"id":"526","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4514254677773","Vip3":"474","Vip2":"485"},
            {"id":"525","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4514254957745","Vip3":"515","Vip2":"530"},
            {"id":"437","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4530025009451","Vip3":"132","Vip2":"143"},
            {"id":"359","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4530025009451","Vip3":"132","Vip2":"143"},
            {"id":"342","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4530025009451","Vip3":"132","Vip2":"143"},
            {"id":"543","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4534551080205","Vip3":"138","Vip2":"145"},
            {"id":"546","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4534551198726","Vip3":"839","Vip2":"845"},
            {"id":"651","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4549735534627","Vip3":"135","Vip2":"142"},
            {"id":"573","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4562248590164","Vip3":"303","Vip2":"315"},
            {"id":"499","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4562344352772","Vip3":"174","Vip2":"183"},
            {"id":"503","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4562344359931","Vip3":"135","Vip2":"142"},
            {"id":"504","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4562344359993","Vip3":"138","Vip2":"145"},
            {"id":"504","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4562344359993","Vip3":"138","Vip2":"145"},
            {"id":"1843","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4571205850623","Vip3":"270","Vip2":"279"},
            {"id":"1662","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4571414670018","Vip3":"100","Vip2":"110"},
            {"id":"1289","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4571414670037","Vip3":"100","Vip2":"110"},
            {"id":"644","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4571414670062","Vip3":"100","Vip2":"110"},
            {"id":"1559","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4582167870017","Vip3":"128","Vip2":"135"},
            {"id":"440","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4589775440021","Vip3":"993","Vip2":"1010"},
            {"id":"365","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4589775440021","Vip3":"993","Vip2":"1010"},
            {"id":"327","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4589775440021","Vip3":"993","Vip2":"1010"},
            {"id":"1345","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4901234299719","Vip3":"37","Vip2":"39"},
            {"id":"864","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4901301036179","Vip3":"18","Vip2":"19"},
            {"id":"882","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4901301254252","Vip3":"22","Vip2":"24"},
            {"id":"882","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4901301254252","Vip3":"22","Vip2":"24"},
            {"id":"1530","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4901301254269","Vip3":"22","Vip2":"24"},
            {"id":"742","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4901301254269","Vip3":"22","Vip2":"24"},
            {"id":"660","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4901301276421","Vip3":"22","Vip2":"24"},
            {"id":"664","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4901301289445","Vip3":"22","Vip2":"24"},
            {"id":"328","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4901301334510","Vip3":"117","Vip2":"123"},
            {"id":"1342","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4901301336811","Vip3":"48","Vip2":"52"},
            {"id":"1341","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4901301350862","Vip3":"48","Vip2":"52"},
            {"id":"2115","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4901417630674","Vip3":"35","Vip2":"38"},
            {"id":"2099","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4901417630674","Vip3":"35","Vip2":"38"},
            {"id":"1583","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4901417630674","Vip3":"35","Vip2":"38"},
            {"id":"2116","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4901417630988","Vip3":"35","Vip2":"38"},
            {"id":"2100","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4901417631381","Vip3":"35","Vip2":"38"},
            {"id":"1586","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4901417631381","Vip3":"35","Vip2":"38"},
            {"id":"1507","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4901417631381","Vip3":"35","Vip2":"38"},
            {"id":"1440","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4901417631381","Vip3":"35","Vip2":"38"},
            {"id":"567","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4901696534922","Vip3":"25","Vip2":"27"},
            {"id":"370","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4901872082339","Vip3":"299","Vip2":"299"},
            {"id":"343","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4901872082339","Vip3":"299","Vip2":"299"},
            {"id":"330","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4901872082339","Vip3":"299","Vip2":"299"},
            {"id":"446","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4901872082346","Vip3":"499","Vip2":"499"},
            {"id":"371","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4901872082346","Vip3":"499","Vip2":"499"},
            {"id":"447","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4901872083350","Vip3":"145","Vip2":"149"},
            {"id":"620","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4901872099245","Vip3":"145","Vip2":"149"},
            {"id":"1500","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4901872099528","Vip3":"178","Vip2":"187"},
            {"id":"2095","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4901872166893","Vip3":"420","Vip2":"430"},
            {"id":"450","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4901872327775","Vip3":"68","Vip2":"75"},
            {"id":"333","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4901872327782","Vip3":"68","Vip2":"75"},
            {"id":"377","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4901872444915","Vip3":"28","Vip2":"30"},
            {"id":"377","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4901872444915","Vip3":"28","Vip2":"30"},
            {"id":"2098","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4902424430295","Vip3":"70","Vip2":"74"},
            {"id":"1294","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4902424433050","Vip3":"34","Vip2":"36"},
            {"id":"379","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4902508003506","Vip3":"96","Vip2":"101"},
            {"id":"1579","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4902508003537","Vip3":"98","Vip2":"103"},
            {"id":"722","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4902508003667","Vip3":"35","Vip2":"37"},
            {"id":"454","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4902508011358","Vip3":"51","Vip2":"54"},
            {"id":"455","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4902508011389","Vip3":"55","Vip2":"58"},
            {"id":"381","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4902508011389","Vip3":"55","Vip2":"58"},
            {"id":"877","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4902508121125","Vip3":"35","Vip2":"37"},
            {"id":"879","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4902508121323","Vip3":"25","Vip2":"27"},
            {"id":"603","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4902806104066","Vip3":"44","Vip2":"47"},
            {"id":"605","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4902806314946","Vip3":"44","Vip2":"47"},
            {"id":"2096","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4902806437980","Vip3":"39","Vip2":"41"},
            {"id":"2114","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4902806438161","Vip3":"40","Vip2":"42"},
            {"id":"1516","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4902806438161","Vip3":"40","Vip2":"42"},
            {"id":"1505","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4902806438161","Vip3":"40","Vip2":"42"},
            {"id":"1547","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4903301240990","Vip3":"21","Vip2":"23"},
            {"id":"1547","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4903301240990","Vip3":"21","Vip2":"23"},
            {"id":"673","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4903301241447","Vip3":"17","Vip2":"19"},
            {"id":"557","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4903335695254","Vip3":"41","Vip2":"44"},
            {"id":"460","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4904710420614","Vip3":"305","Vip2":"320"},
            {"id":"387","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4904710420614","Vip3":"305","Vip2":"320"},
            {"id":"1171","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4908049283452","Vip3":"20","Vip2":"22"},
            {"id":"536","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4908049283452","Vip3":"20","Vip2":"22"},
            {"id":"535","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4908049283476","Vip3":"24","Vip2":"26"},
            {"id":"539","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4908049335069","Vip3":"205","Vip2":"215"},
            {"id":"462","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4908049338817","Vip3":"90","Vip2":"97"},
            {"id":"389","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4908049338817","Vip3":"90","Vip2":"97"},
            {"id":"542","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4908049338862","Vip3":"205","Vip2":"215"},
            {"id":"1223","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4908049397883","Vip3":"178","Vip2":"185"},
            {"id":"1175","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4908049397883","Vip3":"178","Vip2":"185"},
            {"id":"1220","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4908049446253","Vip3":"108","Vip2":"114"},
            {"id":"2064","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4908049446260","Vip3":"110","Vip2":"116"},
            {"id":"1224","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4908049446260","Vip3":"110","Vip2":"116"},
            {"id":"2066","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4908049446277","Vip3":"108","Vip2":"114"},
            {"id":"466","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4908049446277","Vip3":"108","Vip2":"114"},
            {"id":"394","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4908049446277","Vip3":"108","Vip2":"114"},
            {"id":"1222","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4908049446291","Vip3":"90","Vip2":"95"},
            {"id":"468","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4908049446307","Vip3":"90","Vip2":"95"},
            {"id":"1226","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4908049446314","Vip3":"90","Vip2":"95"},
            {"id":"397","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4908049446314","Vip3":"90","Vip2":"95"},
            {"id":"2075","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4908049454906","Vip3":"375","Vip2":"389"},
            {"id":"470","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4931449410678","Vip3":"225","Vip2":"235"},
            {"id":"471","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4931449412801","Vip3":"270","Vip2":"284"},
            {"id":"336","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4931449412801","Vip3":"270","Vip2":"284"},
            {"id":"548","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4931449414102","Vip3":"192","Vip2":"202"},
            {"id":"550","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4931449428628","Vip3":"366","Vip2":"385"},
            {"id":"1217","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4935059041208","Vip3":"302","Vip2":"315"},
            {"id":"401","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4935059041208","Vip3":"302","Vip2":"315"},
            {"id":"474","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4936201102297","Vip3":"34","Vip2":"36"},
            {"id":"475","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4953923303559","Vip3":"656","Vip2":"670"},
            {"id":"563","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4953923314814","Vip3":"513","Vip2":"540"},
            {"id":"2021","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4953923339398","Vip3":"155","Vip2":"162"},
            {"id":"2021","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4953923339398","Vip3":"155","Vip2":"162"},
            {"id":"476","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4953923339411","Vip3":"168","Vip2":"175"},
            {"id":"476","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4953923339411","Vip3":"168","Vip2":"175"},
            {"id":"405","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4953923339411","Vip3":"168","Vip2":"175"},
            {"id":"405","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4953923339411","Vip3":"168","Vip2":"175"},
            {"id":"346","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4953923339411","Vip3":"168","Vip2":"175"},
            {"id":"346","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4953923339411","Vip3":"168","Vip2":"175"},
            {"id":"406","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4961989409016","Vip3":"74","Vip2":"78"},
            {"id":"406","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4961989409016","Vip3":"74","Vip2":"78"},
            {"id":"478","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4968909061200","Vip3":"57","Vip2":"59"},
            {"id":"407","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4968909061200","Vip3":"57","Vip2":"59"},
            {"id":"1775","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4969527118239","Vip3":"528","Vip2":"535"},
            {"id":"560","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4969527156231","Vip3":"222","Vip2":"234"},
            {"id":"576","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4969527158631","Vip3":"330","Vip2":"335"},
            {"id":"578","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4969527162829","Vip3":"330","Vip2":"335"},
            {"id":"1773","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4969527178318","Vip3":"318","Vip2":"330"},
            {"id":"1762","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4969527178318","Vip3":"318","Vip2":"330"},
            {"id":"2035","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4969527178684","Vip3":"600","Vip2":"629"},
            {"id":"481","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4971710362817","Vip3":"415","Vip2":"436"},
            {"id":"410","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4971710362817","Vip3":"415","Vip2":"436"},
            {"id":"482","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4971710362831","Vip3":"415","Vip2":"436"},
            {"id":"483","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4971710364439","Vip3":"305","Vip2":"320"},
            {"id":"412","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4971710364439","Vip3":"305","Vip2":"320"},
            {"id":"1771","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4971710364446","Vip3":"285","Vip2":"295"},
            {"id":"484","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4971710364446","Vip3":"285","Vip2":"295"},
            {"id":"2049","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4971710366587","Vip3":"242","Vip2":"260"},
            {"id":"595","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4971710367478","Vip3":"399","Vip2":"410"},
            {"id":"347","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4973167185728","Vip3":"90","Vip2":"95"},
            {"id":"564","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4973167233658","Vip3":"336","Vip2":"353"},
            {"id":"505","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4974305211736","Vip3":"222","Vip2":"234"},
            {"id":"1520","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4979006067071","Vip3":"904","Vip2":"920"},
            {"id":"2094","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4979006074635","Vip3":"960","Vip2":"960"},
            {"id":"569","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4979006074635","Vip3":"960","Vip2":"960"},
            {"id":"638","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4987009156883","Vip3":"28","Vip2":"30"},
            {"id":"491","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4987030196681","Vip3":"108","Vip2":"114"},
            {"id":"1576","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4987072038697","Vip3":"17","Vip2":"18"},
            {"id":"1556","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4987072061756","Vip3":"25","Vip2":"27"},
            {"id":"684","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4987072078501","Vip3":"27","Vip2":"29"},
            {"id":"556","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4987107623423","Vip3":"77","Vip2":"81"},
            {"id":"556","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4987107623423","Vip3":"77","Vip2":"81"},
            {"id":"1658","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4987115540781","Vip3":"37","Vip2":"39"},
            {"id":"424","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4987240631118","Vip3":"15","Vip2":"16"},
            {"id":"670","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4987241134151","Vip3":"42","Vip2":"45"},
            {"id":"532","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4997770105874","Vip3":"11","Vip2":"12"},
            {"id":"654","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4590429142710-红色","Vip3":"145","Vip2":"153"},
            {"id":"655","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"4590429142710-米色","Vip3":"145","Vip2":"153"},
            {"id":"652","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"806409395712-藏青","Vip3":"200","Vip2":"210"},
            {"id":"508","warehouse_name":"苏州库","storehouse_name":"主库","product_no":"MZB-0001","Vip3":"45","Vip2":"48"}
            ]';
            $data = json_decode($data);

            foreach ($data as $key => $value) {
                $abc = DB::table('erp_product_list')
                ->leftJoin('erp_product_price', 'erp_product_list.id', 'erp_product_price.product_id')
                -> where('erp_product_list.product_no', $value->product_no)
                -> where('erp_product_price.mp_name_id', 5)
                -> select([
                    'erp_product_price.id'
                ])
                -> first();
                if ($abc) {
                    DB::table('erp_product_price')
                    -> where('id', $abc->id)
                    -> update([
                        'price_a' => $value->Vip3,
                        'price_b' => $value->Vip2
                    ]);
                }
            }
            dd('success');

    }

    /**
     * 获取某个用户的token
     */
    public function test1(Request $request)
    {
        $mpUser = ShopMPUsers::where([
            'id'    => $request->id
        ])
        -> first();

        $token = JWTAuth::fromUser($mpUser);
        return $this->successResponse($request, [
            'token' => $token
        ]);

    }

}