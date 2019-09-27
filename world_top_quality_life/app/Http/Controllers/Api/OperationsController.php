<?php
namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OperationsController extends Controller
{
    // 待付款数据透视表
    public function arrearage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $userId = $request->has('user_id') ? $request->user_id : '';

        $arrearageList = DB::table('erp_stock_order as order')
            ->leftJoin('wxuser', 'order.user_id', 'wxuser.id')
            ->where([
                'order.flag' => 0,
                'order.pay_status' => 0,
                'order.business_id' => $request->business_id
            ])
            ->where(function($query) use ($userId) {
                if ($userId) {
                    $query->where('order.user_id', '=', $userId);
                }
            })
            ->groupBy('order.user_id')
            ->selectRaw(
                '`wxuser`.`nickname`, `order`.`user_id` AS userId, COUNT(`order`.`id`) as orderCount, SUM(`order`.`price`) AS amountPrice, SUM(`order`.`pay_price`) as amountPayPrice'
            )
            ->orderBy('amountPrice', 'desc')
            ->paginate(isset($request -> per_page) ? $request->per_page : 20);
        
        // dd($arrearageList->items(), $arrearageList->total());
        $list = $arrearageList->items();
        foreach($list as &$data) {
            $data->theBalance = bcsub($data->amountPrice, $data->amountPayPrice, 1);
            $data->amountPrice = number_format($data->amountPrice, 1);
            $data->amountPayPrice = number_format($data->amountPayPrice, 1);
            $data->theBalance = number_format($data->theBalance, 1);
        }
        return [
            'status' => 1,
            'data' => [
                'list' => $list,
                'total' => $arrearageList->total()
            ]
        ];
    }

    // 个体用户的待付款详情
    public function arrearageDetail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id',
            'user_id' => 'required|numeric|exists:wxuser,id'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $detail = DB::table('erp_stock_order as order')
            ->leftJoin('wxuser', 'wxuser.id', 'order.user_id')
            ->leftJoin('users', 'users.id', 'order.sale_user_id')
            ->where([
                'order.user_id' => $request->user_id,
                'order.flag' => 0,
                'order.pay_status' => 0,
                'order.business_id' => $request->business_id
            ])
            ->selectRaw(
                '`order`.`order_num` as orderNum,
                FROM_UNIXTIME(`order`.`created_at`, "%Y-%m-%d") as date,
                `users`.`username` AS `saleUserName`,
                `wxuser`.`nickname`,
                `order`.`send_status`,
                `order`.`pay_status`,
                `order`.`price`,
                `order`.`freight`,
                `order`.`price`+`order`.`freight` as `totalPrice`,
                `order`.`pay_price`,
                `order`.`sale_remark`'
            )
            ->get();
                
        return [
            'status' => 1,
            'data' => $detail
        ];
    }

    // 用户消费金额的数据透视表
    public function consumption(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id',
            'page' => 'required|numeric'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $startTimeStamp = strtotime("{$request->year}-01-01");
        $nextYear = $request->year + 1;
        $endTimeStamp = strtotime("{$nextYear}-01-01");
        $data = DB::table('erp_stock_order as order')
            ->leftJoin('wxuser', 'wxuser.id', 'order.user_id')
            ->where([
                'order.flag' => 0,
                'wxuser.in_black_list' => 0,
                'order.business_id' => $request->business_id
            ])
            ->where(function($query) use ($request) {
                if ($request->user_id) {
                    $query->where('order.user_id', '=', $request->user_id);
                }
            })
            ->whereBetween('order.created_at', [$startTimeStamp, $endTimeStamp])
            ->orderBy('wxuser.id')
            ->selectRaw(
                "`wxuser`.`id`, `wxuser`.`nickname`, FROM_UNIXTIME(`order`.`created_at`, '%M') as `month`, `order`.`price`"
            )
            ->get()
            ->toArray();
        // dd($data);

        $tableData = [];
        foreach($data as $index => $user) {
            if (!isset($tableData["{$user->nickname}"])) {
                $tableData["{$user->nickname}"] = [
                    'id' => $user->id,
                    'nickname' => $user->nickname,
                    'January' => 0.0, 'February' => 0.0, 'March' => 0.0,
                    'April' => 0.0, 'May' => 0.0, 'June' => 0.0,
                    'July' => 0.0, 'August' => 0.0, 'September' => 0.0,
                    'October' => 0.0, 'November' => 0.0, 'December' => 0.0,
                    'average' => 0, 'total' => 0.0
                ];
            }
            $tableData["{$user->nickname}"]["{$user->month}"] = bcadd($tableData["{$user->nickname}"]["{$user->month}"], $user->price, 1);
            $tableData["{$user->nickname}"]['total'] = bcadd($tableData["{$user->nickname}"]['total'], $user->price, 1);
            $tableData["{$user->nickname}"]["average"] = bcdiv($tableData["{$user->nickname}"]['total'], 12, 1);
        }
        $tableData = array_values($tableData);
        $total = count($tableData);

        $perPage = $request->has('per_page') ? $request->per_page : 20;
        $responseData = array_slice($tableData, $perPage * ($request->page - 1), 20);

        return [
            'status' => 1,
            'data' => [
                'list' => $request->get_all ? $tableData : $responseData,
                'total' => $total
            ]
        ];
    }

    // 用户消费金额明细
    public function consumptionDetail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id',
            'year' => 'required|numeric',
            'month' => 'required|numeric',
            'user_id' => 'required|numeric|exists:wxuser,id'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $year = $request->year;
        $month = $request->month;
        $startTime = strtotime("{$year}-{$month}-01");
        $endTime = 0;
        $nextYear = $year + 1;
        $nextMonth = $month + 1;
        if ($month === 12) {
            $endTime = strtotime("{$nextYear}-01-01");
        } else if ($month < 12) {
            $endTime = strtotime("{$year}-{$nextMonth}-01");
        } else {
            $startTime = strtotime("{$year}-01-01");
            $endTime = strtotime("{$nextYear}-01-01");
        }

        $detail = DB::table('erp_stock_order as order')
            ->leftJoin('wxuser', 'wxuser.id', 'order.user_id')
            ->leftJoin('erp_stock_order_info as orderInfo', 'orderInfo.stock_order_id', 'order.id')
            ->leftJoin('erp_product_list as product', 'product.id', 'orderInfo.product_id')
            ->leftJoin('erp_product_class as class', 'product.class_id', 'class.id')
            ->leftJoin('erp_product_class as brand', 'product.brand_id', 'brand.id')
            ->leftJoin('erp_product_class as series', 'product.series_id', 'series.id')
            ->where([
                'order.flag' => 0,
                'order.user_id' => $request->user_id,
                'order.business_id' => $request->business_id,
                'orderInfo.flag' => 0
            ])
            ->whereBetween('order.created_at', [$startTime, $endTime])
            ->selectRaw('
                FROM_UNIXTIME(`order`.`created_at`, "%Y-%m-%d %H:%i") as `date`,
                `order`.`order_num`,
                `wxuser`.`nickname`,
                `product`.`product_no`,
                `product`.`product_name`,
                `product`.`model`,
                `class`.`name` as `className`,
                `brand`.`name` as `brandName`,
                `series`.`name` as `seriesName`,
                `orderInfo`.`price`,
                `orderInfo`.`number`
            ')
            ->get()->toArray();

        return [
            'status' => 1,
            'data' => $detail
        ];
    }

    // 商品报表
    public function goodsReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id',
            'rank' => 'required|string'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        // log表的wx_user_id是后期添加，报表从开始记录userid的时间开始算
        $startTime = DB::table('mp_operation_log')
            ->where('wx_user_id', '>', 0)
            ->selectRaw('MIN(`created_at`) AS start_time')
            ->get()
            ->toArray()[0]->start_time;

        $today = strtotime('today');
        // 取60天前的数据，如果60天前还没有开始记录userid,就从记录userid的时间开始算
        $startTime = $today - 60*24*60*60 > $startTime
            ? $today - 60*24*60*60
            : $startTime;

        // 销售数据：每个商品的总销售额
        $data = DB::table('erp_stock_order_info as orderInfo')
            ->leftJoin('erp_stock_order as order', 'order.id', 'orderInfo.stock_order_id')
            ->leftJoin('erp_product_list as product', 'orderInfo.product_id', 'product.id')
            ->leftJoin('erp_spu_sku_link as link', 'link.sku_id', 'product.id')
            ->leftJoin('erp_spu_list as spu', 'spu.id', 'link.spu_id')
            ->where([
                'order.business_id' => $request->business_id,
                'order.flag' => 0,
                'orderInfo.flag' => 0,
                'link.flag' => 0,
                'order.insert_type' => 1
            ])
            ->where('order.created_at', '>=', $startTime)
            ->selectRaw('
                `order`.`user_id`,
                `product`.`id` AS `skuId`,
                `product`.`product_no`,
                `product`.`image`,
                `product`.`product_name`,
                `spu`.`id` AS `spuId`,
                `link`.`id` AS `spu_sku_id`,
                `orderInfo`.`send_num`,
                `orderInfo`.`price` * `orderInfo`.`send_num` as `price`
            ')
            // ->orderBy('price', 'desc')
            ->get()
            ->toArray();
        // dd($data);
        $saleData = [];
        foreach($data as $key => &$value) {
            if (!isset($saleData["{$value->skuId}"])) {
                $saleData["{$value->skuId}"]['price'] = round($value->price, 1);
                $saleData["{$value->skuId}"]['users'] = [$value->user_id];
                $saleData["{$value->skuId}"]['product_no'] = $value->product_no;
                $saleData["{$value->skuId}"]['image'] = $value->image;
                $saleData["{$value->skuId}"]['product_name'] = $value->product_name;
                $saleData["{$value->skuId}"]['salesVolume'] = $value->send_num;
            } else {
                $saleData["{$value->skuId}"]['price'] = bcadd($saleData["{$value->skuId}"]['price'], $value->price, 1);
                $saleData["{$value->skuId}"]['salesVolume'] = bcadd($saleData["{$value->skuId}"]['salesVolume'], $value->send_num, 0);
                $saleData["{$value->skuId}"]['users'][] = $value->user_id;
                $saleData["{$value->skuId}"]['users'] = array_unique($saleData["{$value->skuId}"]['users']); // 去重
            }
        }
        uasort($saleData, function($a, $b) { return $b['price'] - $a['price']; }); // 销量降序
        // dd($saleData);

        $paths = DB::table('mp_operation_log')
                ->where('wx_user_id', '>', 0)
                ->where('path', 'like', "/api/shop_mp/spus/%?%business_id={$request->business_id}%")
                ->orWhere('path', 'like', "/api/shop_mp/special/%?%business_id={$request->business_id}%")
                ->select('path', 'wx_user_id')
                ->get()
                ->toArray();

        // dd($paths);
        // 查询spu_link的id和special的id
        $spus = [];
        $linkIds = [];
        $specialGoods = [];
        $specialIds = [];
        foreach($paths as $path) {
            if (preg_match("/^\/api\/shop_mp\/spus\/(\d+)/" ,$path->path, $match)) {
                $id = $match[1];
                if (isset($spus["{$id}"])) {
                    $spus["{$id}"]['count'] += 1;
                    if (!in_array($path->wx_user_id, $spus["{$id}"]['users'])) {
                        $spus["{$id}"]['users'][] = $path->wx_user_id;
                    }
                } else {
                    $spus["{$id}"]['id'] = $id;
                    $spus["{$id}"]['count'] = 1;
                    $spus["{$id}"]['users'] = [$path->wx_user_id];
                    $linkIds[] = $id;
                }
            } else if (preg_match("/^\/api\/shop_mp\/special\/(\d+)/" ,$path->path, $match)) {
                $id = $match[1];
                if (isset($specialGoods["{$id}"])) {
                    $specialGoods["{$id}"]['count'] += 1;
                    if (!in_array($path->wx_user_id, $specialGoods["{$id}"]['users'])) {
                        $specialGoods["{$id}"]['users'][] = $path->wx_user_id;
                    }
                } else {
                    $specialGoods["{$id}"]['id'] = $id;
                    $specialGoods["{$id}"]['count'] = 1;
                    $specialGoods["{$id}"]['users'] = [$path->wx_user_id];
                    $specialIds[] = $id;
                }
            }
        }
        // dd($spus, $linkIds, $specialGoods, $specialIds);

        // 用这些id查对应的sku的id
        $skuIds = DB::table('erp_mp_name_spu_link')
            ->leftJoin('erp_spu_sku_link', 'erp_spu_sku_link.spu_id', 'erp_mp_name_spu_link.spu_id')
            // ->leftJoin('erp_spu_list', 'erp_spu_list.id', 'erp_mp_name_spu_link.spu_id')
            ->leftJoin('erp_product_list', 'erp_spu_sku_link.sku_id', 'erp_product_list.id')
            ->whereIn('erp_mp_name_spu_link.id', $linkIds)
            ->where([
                'erp_mp_name_spu_link.flag' => 0,
                'erp_spu_sku_link.flag' => 0
            ])
            ->select([
                'erp_mp_name_spu_link.id',
                'erp_mp_name_spu_link.spu_id',
                'erp_spu_sku_link.sku_id',
                'erp_product_list.product_no',
                'erp_product_list.product_name',
                'erp_product_list.image'
            ])
            ->orderBy('sku_id')
            ->get()
            ->toArray();

        $skuList = [];
        foreach($skuIds as $data) {
            if (isset($skuList["{$data->sku_id}"])) {
                $skuList["{$data->sku_id}"]['count'] += $spus["{$data->id}"]['count'];
                // dd($skuList["{$data->sku_id}"]['users'], $spus["{$data->id}"]['wx_user_id']);
                $skuList["{$data->sku_id}"]['users'] = array_merge($skuList["{$data->sku_id}"]['users'], $spus["{$data->id}"]['users']);
                $skuList["{$data->sku_id}"]['users'] = array_unique($skuList["{$data->sku_id}"]['users']); // 去重
                // $skuList["{$data->sku_id}"]['users'] = array_values($skuList["{$data->sku_id}"]['users']);
            }
            $skuList["{$data->sku_id}"] = [
                'count' => $spus["{$data->id}"]['count'],
                'users' => $spus["{$data->id}"]['users'],
                'image' => $data->image,
                'product_name' => $data->product_name,
                'product_no' => $data->product_no
            ];
        }
        // dd($skuList);
        $special_sku_ids = DB::table('erp_special_price')
            ->leftJoin('erp_product_list', 'erp_product_list.id', 'erp_special_price.sku_id')
            ->whereIn('erp_special_price.id', $specialIds)
            ->select([
                'erp_special_price.id',
                'erp_special_price.sku_id',
                'erp_product_list.product_no',
                'erp_product_list.product_name',
                'erp_product_list.image'
            ])
            ->get()
            ->toArray();
        // dd($special_sku_ids);
        foreach($special_sku_ids as $sku) {
            if (isset($skuList["{$sku->sku_id}"])) {
                $skuList["{$sku->sku_id}"]['count'] += $specialGoods["{$sku->id}"]['count'];
                $skuList["{$sku->sku_id}"]['users'] = array_merge($skuList["{$sku->sku_id}"]['users'], $specialGoods["{$sku->id}"]['users']);
                $skuList["{$sku->sku_id}"]['users'] = array_unique($skuList["{$sku->sku_id}"]['users']); // 去重
                // $skuList["{$sku->sku_id}"]['users'] = array_values($skuList["{$sku->sku_id}"]['users']);
            } else {
                $skuList["{$sku->sku_id}"] = [
                    'count' => $specialGoods["{$sku->id}"]['count'],
                    'users' => $specialGoods["{$sku->id}"]['users'],
                    'image' => $sku->image,
                    'product_name' => $sku->product_name,
                    'product_no' => $sku->product_no
                ];
            }
        }
        
        uasort($skuList, function($a, $b) { return $b['count'] - $a['count']; }); // pv降序
        // dd($skuList, $saleData);

        $rank = [];
        if ($request->rank === 'pv') {
            foreach($skuList as $skuId => $detail) {
                $rank["{$skuId}"] = [
                    'product_no' => $detail['product_no'], // 商品编码
                    'image' => $detail['image'], // 商品图片
                    'product_name' => $detail['product_name'], // 商品名称
                    'pv' => $detail['count'], // 浏览量
                    'pv_user_count' => count($detail['users']), // 浏览的客户数
                    'sales' => isset($saleData["{$skuId}"]) ? $saleData["{$skuId}"]['price'] : '0.0', // 销售额
                    'sales_volume' => isset($saleData["{$skuId}"]) ? $saleData["{$skuId}"]['salesVolume'] : 0,
                    'purchaserCount' => isset($saleData["{$skuId}"]) ? count($saleData["{$skuId}"]['users']) : 0, // 购买人数
                    'conversion' =>  isset($saleData["{$skuId}"]) // 转化率
                        ? (bcdiv(count($saleData["{$skuId}"]['users']), count($detail['users']), 2) * 100) . '%'
                        : '0%'
                ];
            }
        } else {
            foreach($saleData as $skuId => $detail) {
                $rank["{$skuId}"] = [
                    'product_no' => $detail['product_no'],
                    'image' => $detail['image'],
                    'product_name' => $detail['product_name'],
                    'pv' => isset($skuList["{$skuId}"]) ? $skuList["{$skuId}"]['count'] : 0,
                    'pv_user_count' => isset($skuList["{$skuId}"]) ? count($skuList["{$skuId}"]['users']) : 0,
                    'sales' => $detail['price'],
                    'sales_volume' => $detail['salesVolume'],
                    'purchaserCount' => count($detail['users']),
                    'conversion' => isset($skuList["{$skuId}"])
                        ? (bcdiv(count($detail['users']), count($skuList["{$skuId}"]['users']), 2) * 100) . '%'
                        : '0%'
                ];
            }
        }
        $rank = array_values($rank);
        foreach($rank as &$data) {
            $data['image'] = getImageUrl($data['image']);
        }
        return [
            'status' => 1,
            'data' => $rank
        ];
    }

    // 访问记录统计
    public function visitorReoprt(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $startTime = DB::table('mp_operation_log')
            ->where('wx_user_id', '>', 0)
            ->selectRaw('MIN(`created_at`) AS start_time')
            ->get()
            ->toArray()[0]->start_time;

        $visitors = DB::table('wxuser')
            ->leftJoin('mp_operation_log as log', 'log.wx_user_id', 'wxuser.id')
            ->where('log.wx_user_id', '>', 0)
            -> where(function($query)use($request){
                //付款状态
                if($request->nickname){
                    //0未付款 1 已付款
                    $query -> where('wxuser.nickname', 'like', '%'. $request->nickname .'%');
                }
            })
            ->groupBy('wxuser.id')
            ->selectRaw('`wxuser`.`id`, `wxuser`.`nickname`, COUNT(`wxuser`.`id`) AS visitTimes, MIN(`log`.`created_at`) AS firstTime')
            ->orderBy('wxuser.id')
            ->paginate(isset($request->per_page) ? $request->per_page : 20);
            
        $list = $visitors->items();
        $total = $visitors->total();
        // 选择当前页所有的用户id，方便下面查询他们的订单信息
        $users = [];
        foreach($list as $user) {
            $users[] = $user->id;
        }
        // dd($users);

        $today = strtotime('now');
        $nearMonthVisitCount = DB::table('mp_operation_log')
            ->whereIn('wx_user_id', $users)
            ->whereBetween('created_at', [$today - 30*24*60*60, $today])
            ->groupBy('wx_user_id')
            ->selectRaw('
                `wx_user_id`, COUNT(`wx_user_id`) as `count`
            ')
            ->get()->toArray();
        $nearMonthOrderCount = DB::table('erp_stock_order')
            ->whereIn('user_id', $users)
            ->whereBetween('created_at', [$today - 30*24*60*60, $today])
            ->where([
                'flag' => 0,
                'business_id' => $request->business_id,
                'insert_type' => 1
            ])
            ->groupBy('user_id')
            ->selectRaw('`user_id`, COUNT(`user_id`) as `orderCount`')
            ->get()->toArray();

        $nearWeekVisitCount = DB::table('mp_operation_log')
            ->whereIn('wx_user_id', $users)
            ->whereBetween('created_at', [$today - 7*24*60*60, $today])
            ->groupBy('wx_user_id')
            ->selectRaw('`wx_user_id`, COUNT(`wx_user_id`) as `count`')
            ->get()->toArray();
        $nearWeekOrderCount = DB::table('erp_stock_order')
                ->whereIn('user_id', $users)
                ->whereBetween('created_at', [$today - 7*24*60*60, $today])
                ->where([
                    'flag' => 0,
                    'business_id' => $request->business_id,
                    'insert_type' => 1
                ])
                ->groupBy('user_id')
                ->selectRaw('`user_id`, COUNT(`user_id`) as `orderCount`')
                ->get()->toArray();
        // dd($nearMonthVisitCount, $nearWeekVisitCount);

        $userOrder = DB::table('wxuser')
            ->leftJoin('erp_stock_order as order', 'order.user_id', 'wxuser.id')
            ->where([
                'order.flag' => 0,
                'order.business_id' => $request->business_id,
                'order.insert_type' => 1
            ])
            ->where('order.created_at', '>', $startTime)
            ->whereIn('order.user_id', $users)
            ->groupBy('wxuser.id')
            ->selectRaw('`wxuser`.`id`, COUNT(`wxuser`.`id`) AS `count`')
            ->orderBy('wxuser.id')
            ->get()
            ->toArray();
        // dd($userOrder);
        foreach($list as &$visitor) {
            if (strtotime('now') - $visitor->firstTime < 24*60*60) {
                $visitor->averageTimes = $visitor->visitTimes;
            } else {
                $visitor->averageTimes = bcdiv(
                    $visitor->visitTimes,
                    bcdiv(strtotime('now') - $visitor->firstTime, 24 * 60 * 60, 0), // 从第一次访问到现在有多少天
                    0
                );
            }
            $visitor->orderCount = 0;
            $visitor->nearWeekVisitCount = 0;
            $visitor->nearWeekOrdedrCount = 0;
            $visitor->nearMonthVisitCount = 0;
            $visitor->nearMonthOrderCount = 0;
            foreach($userOrder as $order) {
                if ($order->id === $visitor->id) {
                    $visitor->orderCount = $order->count;
                }
            }
            foreach($nearWeekVisitCount as $user) {
                if ($visitor->id === $user->wx_user_id) {
                    $visitor->nearWeekVisitCount = $user->count;
                }
            }
            foreach($nearWeekOrderCount as $order) {
                if ($visitor->id === $order->user_id) {
                    $visitor->nearWeekOrderCount = $order->orderCount;
                }
            }
            foreach($nearMonthVisitCount as $user) {
                if ($visitor->id === $user->wx_user_id) {
                    $visitor->nearMonthVisitCount = $user->count;
                }
            }
            foreach($nearMonthOrderCount as $order) {
                if ($visitor->id === $order->user_id) {
                    $visitor->nearMonthOrderCount = $order->orderCount;
                }
            }
        }
        
        return [
            'status' => 1,
            'data' => [
                'list' => $list,
                'total' => $total
            ]
        ];
    }

    // 访问记录详情
    public function visitRecordDetail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id',
            'user_id' => 'required|numeric|exists:wxuser,id'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $startTime = DB::table('mp_operation_log')
            ->where('wx_user_id', '>', 0)
            ->selectRaw('MIN(`created_at`) as time')
            ->get()->toArray()[0]->time;
        // dd($startTime);
        // 订单信息
        $orders = DB::table('erp_stock_order as order')
            ->leftJoin('erp_stock_order_info as orderInfo', 'orderInfo.stock_order_id', 'order.id')
            ->where('order.created_at', '>', $startTime)
            ->where([
                'order.business_id' => $request->business_id,
                'order.user_id' => $request->user_id,
                'order.flag' => 0,
                'orderInfo.flag' => 0,
                'order.insert_type' => 1
            ])
            ->groupBy('orderInfo.product_id')
            ->selectRaw('`orderInfo`.`product_id`, SUM(`orderInfo`.`price` * `orderInfo`.`number`) as price, COUNT(`order`.`id`) as orderCount, SUM(`orderInfo`.`number`) as `number`')
            ->get()->toArray();
        // dd($orders);

        // 访问的商品
        $paths = DB::table('mp_operation_log as log')
            ->where('log.wx_user_id', '=', $request->user_id)
            ->where(function ($query) {
                $query->where('log.path', 'like', '/api/shop_mp/spus/%')
                    ->orWhere('log.path', 'like', '/api/shop_mp/special/%');
            })
            ->select('log.path')
            ->get()
            ->toArray();
        // dd($paths);
        $mpSpuLinkCount = []; // 普通商品的浏览次数
        $specialCount = []; // 特价商品的浏览次数
        foreach($paths as $path) {
            if (preg_match("/^\/api\/shop_mp\/spus\/(\d+)/" ,$path->path, $match) ) {
                // dd($match[1]);
                $linkId = $match[1];
                $mpSpuLinkCount[$linkId] = isset($mpSpuLinkCount[$linkId])
                    ? $mpSpuLinkCount[$linkId] + 1
                    : 1;
                // $mpSpuLinkIds[] = $match[1];
            } else if (preg_match("/^\/api\/shop_mp\/special\/(\d+)/", $path->path, $match)) {
                // dd($match);
                // $specialIds[] = $match[1];
                $linkId = $match[1];
                $specialCount[$linkId] = isset($specialCount[$linkId])
                    ? $specialCount[$linkId] + 1
                    : 1;
            }
        }
        // dd($mpSpuLinkCount, $specialCount);
        $mpSpuLinkIds = [];
        $specialIds = [];
        foreach($mpSpuLinkCount as $id => $count) {
            $mpSpuLinkIds[] = $id;
        }
        foreach($specialCount as $id => $count) {
            $specialIds[] = $id;
        }
        $mpSpuLinkIds = array_values(array_unique($mpSpuLinkIds));
        $specialIds = array_values(array_unique($specialIds));
        // dd($mpSpuLinkIds, $specialIds);

        $spuSkuInfo = DB::table('erp_mp_name_spu_link as mpSpuLink')
            ->leftJoin('erp_spu_sku_link as spuSkuLink', 'mpSpuLink.spu_id', 'spuSkuLink.spu_id')
            ->leftJoin('erp_product_list as product', 'product.id', 'spuSkuLink.sku_id')
            ->whereIn('mpSpuLink.id', $mpSpuLinkIds)
            ->select([
                'mpSpuLink.id',
                'product.id as skuId',
                'product.image',
                'product.product_name',
                'product.product_no'
            ])
            ->get()->toArray();
        // dd($skuInfo);

        $specialSkuInfo = DB::table('erp_special_price as special')
            ->leftJoin('erp_product_list as product', 'product.id', 'special.sku_id')
            ->whereIn('special.id', $specialIds)
            ->select([
                'special.id',
                'product.id as skuId',
                'product.image',
                'product.product_name',
                'product.product_no'
            ])
            ->get()->toArray();
        // dd($specialSkuInfo);
        $skuList = [];
        // 整理普通商品
        // dd($specialSkuInfo, $orders);
        foreach ($spuSkuInfo as $key => $value) {
            $order = array_values(array_filter($orders, function ($order) use ($value) {
                return $order->product_id === $value->skuId;
            }));
            $orderInfo = $order ? $order[0] : [];
            $skuList[$value->skuId] = [
                'visitCount' => $mpSpuLinkCount[$value->id],
                'orderCount' => isset($orderInfo->orderCount) ? $orderInfo->orderCount : 0,
                'number' => isset($orderInfo->number) ? $orderInfo->number : 0,
                'price' => isset($orderInfo->price) ? $orderInfo->price : 0,
                'productName' => $value->product_name,
                'productNo' => $value->product_no,
                'image' => getImageUrl($value->image)
            ];
        }
        // 整理特殊商品
        foreach ($specialSkuInfo as $key => $value) {
            $order = array_values(array_filter($orders, function ($order) use ($value) {
                return $order->product_id === $value->skuId;
            }));
            $orderInfo = $order ? $order[0] : [];
            if (isset($skuList[$value->skuId])) {
                $skuList[$value->skuId]['visitCount'] += $specialCount[$value->id];
            } else {
                $skuList[$value->skuId] = [
                    'visitCount' => $specialCount[$value->id],
                    'orderCount' => isset($orderInfo->orderCount) ? $orderInfo->orderCount : 0,
                    'number' => isset($orderInfo->number) ? $orderInfo->number : 0,
                    'price' => isset($orderInfo->price) ? $orderInfo->price : 0,
                    'productName' => $value->product_name,
                    'productNo' => $value->product_no,
                    'image' => getImageUrl($value->image)
                ];
            }
        }
        // dd($skuList);
        uasort($skuList, function($a, $b) { return $b['visitCount'] - $a['visitCount']; });
        $skuList = array_values($skuList);
        return [
            'status' => 1,
            'data' => $skuList
        ];
    }

    // 特价商品的七天数据
    public function specialGoodsReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $startTime = strtotime($request->start_time);
        $endTime = strtotime($request->end_time);

        $tableHeader = [];
        $dateData = [];

        $visitors = DB::table('mp_operation_log')
                ->where('path', 'like', '/api/shop_mp/special/%')
                ->whereBetween('created_at', [$startTime, $endTime])
                ->groupBy('date')
                ->groupBy('wx_user_id')
                ->selectRaw('`wx_user_id`, FROM_UNIXTIME(`created_at`, "%Y-%m-%d") as `date`')
                ->get()
                ->toArray();
        // dd($visitors);
        $orders = DB::table('erp_stock_order as order')
                ->leftJoin('erp_stock_order_info as orderInfo', 'order.id', 'orderInfo.stock_order_id')
                ->leftJoin('wxuser', 'order.user_id', 'wxuser.id')
                ->where([
                    'order.flag' => 0,
                    'order.business_id' => $request->business_id,
                    'orderInfo.flag' => 0
                ])
                ->where('orderInfo.special_id', '>', 0)
                ->whereBetween('order.created_at', [$startTime, $endTime])
                ->selectRaw('
                    `wxuser`.`id`, `orderInfo`.`price`, `orderInfo`.`number`, FROM_UNIXTIME(`order`.`created_at`, "%Y-%m-%d") as `date`
                ')
                ->get()
                ->toArray();
        // dd($orders);

        for ($start = $startTime; $start <= $endTime; $start += 86400) {
            $date = date('Y-m-d', $start); // 日期
            $tableHeader[] = $date;
            $dateData[$date] = [
                'date' => $date,
                'users' => [],
                'amountPrice' => 0.0,
                'userCount' => 0,
                'orderCount' => 0,
                'newUserCount' => 0,
                'newUserOrderPrice' => 0.0
            ];
            $visitorInDay = array_filter($visitors, function ($visitor) use ($date) {
                return $visitor->date === $date;
            });
            $orderInDay = array_filter($orders, function($order) use ($date) {
                return $order->date === $date;
            });
            $dateData[$date]['visitorCount'] = count($visitorInDay);
            if ($orderInDay) {
                foreach($orderInDay as $order) {
                    $dateData[$date]['users'][] = $order->id;
                    $dateData[$date]['amountPrice'] = bcadd(bcmul($order->price, $order->number, 1), $dateData[$date]['amountPrice'], 1);
                }
                $dateData[$date]['users'] = array_unique($dateData[$date]['users']);
                $dateData[$date]['userCount'] = count($dateData[$date]['users']);
                $dateData[$date]['orderCount'] = count($orderInDay);
            }

            // 查询第一次下单时间
            if ($dateData[$date]['users']) {
                $firstTime = DB::table('erp_stock_order')
                    ->whereIn('user_id', $dateData[$date]['users'])
                    ->where([
                        'business_id' => $request->business_id,
                        'flag' => 0
                    ])
                    ->groupBy('user_id')
                    ->selectRaw("
                        `user_id`, IF(MIN(`created_at`) > {$start}, 1, 0) as newUser, `price`
                    ")
                    ->get()
                    ->toArray();
                $newUser = array_filter($firstTime, function($item) {
                    return $item->newUser;
                });
                $dateData[$date]['newUserCount'] = count($newUser);
                foreach($newUser as $user) {
                    $dateData[$date]['newUserOrderPrice'] = bcadd($user->price, $dateData[$date]['newUserOrderPrice'], 1);
                }
            }
            unset($dateData[$date]['users']);
        }

        // for ($start = $startTime; $start <= $endTime; $start += 86400) {
        //     $date = date('Y-m-d', $start); // 日期
        //     $tableHeader[] = $date;
        //     $dateData[$date] = [
        //         'date' => $date,
        //         'users' => [],
        //         'amountPrice' => 0.0,
        //         'userCount' => 0,
        //         'orderCount' => 0,
        //         'newUserCount' => 0,
        //         'newUserOrderPrice' => 0.0
        //     ];

        //     // $visitor = DB::table('mp_operation_log')
        //     //     ->where('path', 'like', '/api/shop_mp/special/%')
        //     //     ->whereBetween('created_at', [$start, $start + 24*60*60])
        //     //     ->groupBy('wx_user_id')
        //     //     ->select('wx_user_id')
        //     //     ->get()
        //     //     ->toArray();
        //     $dateData[$date]['visitorCount'] = count($visitor);

        //     // $orders = DB::table('erp_stock_order as order')
        //     //     ->leftJoin('erp_stock_order_info as orderInfo', 'order.id', 'orderInfo.stock_order_id')
        //     //     ->leftJoin('wxuser', 'order.user_id', 'wxuser.id')
        //     //     ->where([
        //     //         'order.flag' => 0,
        //     //         'order.business_id' => $request->business_id,
        //     //         'orderInfo.flag' => 0
        //     //     ])
        //     //     ->where('orderInfo.special_id', '>', 0)
        //     //     ->whereBetween('order.created_at', [$start, $start + 24*60*60])
        //     //     ->selectRaw('
        //     //         `wxuser`.`id`, `orderInfo`.`price`, `orderInfo`.`number`
        //     //     ')
        //     //     ->get()
        //     //     ->toArray();
        //     // dd($orders);
        //     if ($orders) {
        //         foreach($orders as $order) {
        //             $dateData[$date]['users'][] = $order->id;
        //             $dateData[$date]['amountPrice'] = bcadd(bcmul($order->price, $order->number, 1), $dateData[$date]['amountPrice'], 1);
        //         }
        //         $dateData[$date]['users'] = array_unique($dateData[$date]['users']);
        //         $dateData[$date]['userCount'] = count($dateData[$date]['users']);
        //         $dateData[$date]['orderCount'] = count($orders);
        //     }

        //     // 查询第一次下单时间
        //     if ($dateData[$date]['users']) {
        //         $firstTime = DB::table('erp_stock_order')
        //             ->whereIn('user_id', $dateData[$date]['users'])
        //             ->where([
        //                 'business_id' => $request->business_id,
        //                 'flag' => 0
        //             ])
        //             ->groupBy('user_id')
        //             ->selectRaw("
        //                 `user_id`, IF(MIN(`created_at`) > {$start}, 1, 0) as newUser, `price`
        //             ")
        //             ->get()
        //             ->toArray();
        //         $newUser = array_filter($firstTime, function($item) {
        //             return $item->newUser;
        //         });
        //         $dateData[$date]['newUserCount'] = count($newUser);
        //         foreach($newUser as $user) {
        //             $dateData[$date]['newUserOrderPrice'] = bcadd($user->price, $dateData[$date]['newUserOrderPrice'], 1);
        //         }
        //     }
        //     unset($dateData[$date]['users']);
        // }
        $dateData = array_values($dateData);
        // dd($dateData);
        return [
            'status' => 1,
            'data' => [
                'list' => $dateData,
                'header' => $tableHeader
            ]
        ];
    }

    // 特价商品近八周的数据
    public function specialWeeksData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $thisYear = date('Y'); // 今年
        $thisWeek = date('W'); // 本周

        $date = new \DateTime();
        $date->setISODate($thisYear, $thisWeek - 8, 1);
        // $week = $date->format('W'); // 当前周数
        $startTime = strtotime($date->format('Y-m-d'));

        $date->setISODate($thisYear, $thisWeek, 1);
        $endTime = strtotime($date->format('Y-m-d'));
        // dd($startTime, $endTime);

        $visitors = DB::table('mp_operation_log')
            ->where('wx_user_id', '>', 0)
            ->where('path', 'like', '/api/shop_mp/special/%')
            ->whereBetween('created_at', [$startTime, $endTime])
            // ->selectRaw('`wx_user_id`, FROM_UNIXTIME(`created_at`, "%Y-%m-%d") as `date`')
            ->selectRaw('`wx_user_id`, `created_at` as `date`')
            ->orderBy('created_at', 'asc')
            ->get()
            ->toArray();
        // dd($visitors);
        
        $orders = DB::table('erp_stock_order as order')
            ->leftJoin('erp_stock_order_info as orderInfo', 'orderInfo.stock_order_id', 'order.id')
            ->where([
                'order.flag' => 0,
                'order.business_id' => $request->business_id,
                'orderInfo.flag' => 0
            ])
            ->where('orderInfo.special_id', '>', 0)
            ->whereBetween('order.created_at', [$startTime, $endTime])
            // ->selectRaw('`order`.`user_id`, `order`.`price`, FROM_UNIXTIME(`order`.`created_at`, "%Y-%m-%d") as `date`')
            ->selectRaw(' `order`.`user_id`, `orderInfo`.`price`, `orderInfo`.`number`,`order`.`created_at` as `date` ')
            ->get()
            ->toArray();
        // dd($orders);

        $users = [];
        foreach($orders as $order) {
            $users[] = $order->user_id;
        }
        $users = array_values(array_unique($users));
        // dd($users);
        $firstOrderTime = DB::table('erp_stock_order as order')
            ->leftJoin('erp_stock_order_info as orderInfo', 'order.id', 'orderInfo.stock_order_id')
            ->where([
                'order.flag' => 0,
                'orderInfo.flag' => 0,
                'business_id' => $request->business_id
            ])
            ->where('orderInfo.special_id', '>', 0)
            ->whereIn('order.user_id', $users)
            ->groupBy('order.user_id')
            // ->selectRaw('`user_id`, FROM_UNIXTIME(MIN(`created_at`), "%Y-%m-%d") as `date`')
            ->selectRaw('
                `order`.`user_id`, `order`.`created_at` as `date`
            ')
            ->get()
            ->toArray();
        // dd($firstOrderTime);

        $weeks = [];
        $data = [];
        for ($index = 0, $weekStartTime = $startTime; $index < 8; $index++, $weekStartTime += 7*24*60*60) {
            $weekData = [
                'visitorCount' => 0, // 访客数
                'userCount' => 0, // 下单用户数
                'orderCount' => 0, // 订单数
                'amountPrice' => 0, // 订单总金额
                'newUserCount' => 0, // 新用户数
                'newUserOrderPrice' => 0 // 新用户下单金额
            ];
            $weekEndTime = $weekStartTime + 7 * 24 * 60 * 60;
            $weeks[] = [
                'week' => date('W', $weekStartTime),
                'range' => [date('Y-m-d', $weekStartTime), date('Y-m-d', $weekEndTime)]
            ];

            $visitorIds = [];
            foreach($visitors as $visitor) {
                if ($visitor->date >= $weekStartTime && $visitor->date < $weekEndTime) {
                    $visitorIds[] = $visitor->wx_user_id;
                }
            }
            $weekData['visitorCount'] = count(array_unique($visitorIds));

            $userIds = [];
            foreach($orders as $order) {
                if ($order->date >= $weekStartTime && $order->date < $weekEndTime) {
                    $weekData['orderCount']++;
                    $weekData['amountPrice'] = bcadd(bcmul($order->price, $order->number, 1), $weekData['amountPrice'], 1);
                    $userIds[] = $order->user_id;
                }
            }
            $weekData['userCount'] = count(array_unique($userIds));

            $newUsers = [];
            foreach($firstOrderTime as $value) {
                if ($value->date >= $weekStartTime && $value->date < $weekEndTime) {
                    $weekData['newUserCount']++;
                    $newUsers[] = $value->user_id;
                }
            }

            $firstOrderPrice = DB::table('erp_stock_order as order')
                ->leftJoin('erp_stock_order_info as orderInfo', 'orderInfo.stock_order_id', 'order.id')
                ->where([
                    'order.flag' => 0,
                    'orderInfo.flag' => 0,
                    'business_id' => $request->business_id
                ])
                ->where('orderInfo.special_id', '>', 0)
                ->whereIn('order.user_id', $newUsers)
                ->groupBy('order.user_id')
                ->selectRaw("`order`.`user_id`, MIN(`order`.`created_at`) as `date`, `order`.`price`")
                ->get()
                ->toArray();
            foreach($firstOrderPrice as $price) {
                $weekData['newUserOrderPrice'] = bcadd($weekData['newUserOrderPrice'], $price->price, 1);
            }
            $data[] = $weekData;
        }
        
        $tableData = [
            'visitorCount' => ['type' => '访问人数', 'total' => 0],
            'userCount' => ['type' => '下单人数', 'total' => 0],
            'orderCount' => ['type' => '下单数', 'total' => 0],
            'amountPrice' => ['type' => '下单金额', 'total' => 0],
            'newUserCount' => ['type' => '新客户下单人数', 'total' => 0],
            'newUserOrderPrice' => ['type' => '新客户下单金额', 'total' => 0]
        ];
        foreach ($tableData as $key => &$value) {
            foreach($data as $index => $week) {
                $prop = 'week_' . ($index + 1);
                $value[$prop] = $week[$key];
                if ($key === 'amountPrice' || $key === 'newUserOrderPrice') {
                    $value['total'] = bcadd($value['total'], $week[$key], 1);
                } else {
                    $value['total'] += $week[$key];
                }
            }
        }

        $tableData = array_values($tableData);
        
        return [
            'status' => 1,
            'data' => [
                'list' => $tableData,
                'weeks' => $weeks
            ]
        ];
    }

    // 特价商品年度数据
    public function specialYearData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $year = isset($request->year) ? $request->year : date('Y');

        $startTime = strtotime("{$year}-01-01");
        $nextYear = $year + 1;
        $endTime = strtotime("{$nextYear}-01-01");

        $visitors = DB::table('mp_operation_log')
            ->where('wx_user_id', '>', 0)
            ->where('path', 'like', '/api/shop_mp/special/%')
            ->whereBetween('created_at', [$startTime, $endTime])
            // ->selectRaw('`wx_user_id`, FROM_UNIXTIME(`created_at`, "%Y-%m-%d") as `date`')
            ->selectRaw('`wx_user_id`, `created_at` as `date`')
            ->orderBy('created_at', 'asc')
            ->get()
            ->toArray();
        
            $orders = DB::table('erp_stock_order as order')
                ->leftJoin('erp_stock_order_info as orderInfo', 'orderInfo.stock_order_id', 'order.id')
                ->where([
                    'order.flag' => 0,
                    'order.business_id' => $request->business_id,
                    'orderInfo.flag' => 0
                ])
                ->where('orderInfo.special_id', '>', 0)
                ->whereBetween('order.created_at', [$startTime, $endTime])
                // ->selectRaw('`order`.`user_id`, `order`.`price`, FROM_UNIXTIME(`order`.`created_at`, "%Y-%m-%d") as `date`')
                ->selectRaw(' `order`.`user_id`, `orderInfo`.`price`, `orderInfo`.`number`, `order`.`created_at` as `date` ')
                ->get()
                ->toArray();
        // dd($orders);

        $users = [];
        foreach($orders as $order) {
            $users[] = $order->user_id;
        }
        $users = array_values(array_unique($users));
        // dd($users);
        $firstOrderTime = DB::table('erp_stock_order as order')
            ->leftJoin('erp_stock_order_info as orderInfo', 'order.id', 'orderInfo.stock_order_id')
            ->where([
                'order.flag' => 0,
                'orderInfo.flag' => 0,
                'business_id' => $request->business_id
            ])
            ->where('orderInfo.special_id', '>', 0)
            ->whereIn('order.user_id', $users)
            ->groupBy('order.user_id')
            // ->selectRaw('`user_id`, FROM_UNIXTIME(MIN(`created_at`), "%Y-%m-%d") as `date`')
            ->selectRaw('
                `order`.`user_id`, `order`.`created_at` as `date`
            ')
            ->get()
            ->toArray();
        // dd($firstOrderTime);

        $data = [];
        for ($index = 1; $index < 13; $index++) {
            $monthData = [
                'visitorCount' => 0, // 访客数
                'userCount' => 0, // 下单用户数
                'orderCount' => 0, // 订单数
                'amountPrice' => 0, // 订单总金额
                'newUserCount' => 0, // 新用户数
                'newUserOrderPrice' => 0 // 新用户下单金额
            ];
            $monthStartTime = strtotime("{$year}-{$index}-01");
            $monthEndTime = 0;
            if ($index === 12) {
                $monthEndTime = strtotime("{$nextYear}-01-01");
            } else {
                $nextMonth = $index + 1;
                $monthEndTime = strtotime("{$year}-{$nextMonth} -01");
            }

            $visitorIds = [];
            foreach($visitors as $visitor) {
                if ($visitor->date >= $monthStartTime && $visitor->date < $monthEndTime) {
                    $visitorIds[] = $visitor->wx_user_id;
                }
            }
            $monthData['visitorCount'] = count(array_unique($visitorIds));

            $userIds = [];
            foreach($orders as $order) {
                if ($order->date >= $monthStartTime && $order->date < $monthEndTime) {
                    $monthData['orderCount']++;
                    $monthData['amountPrice'] = bcadd(bcmul($order->price, $order->number, 1), $monthData['amountPrice'], 1);
                    $userIds[] = $order->user_id;
                }
            }
            $monthData['userCount'] = count(array_unique($userIds));

            $newUsers = [];
            foreach($firstOrderTime as $value) {
                if ($value->date >= $monthStartTime && $value->date < $monthEndTime) {
                    $monthData['newUserCount']++;
                    $newUsers[] = $value->user_id;
                }
            }

            $firstOrderPrice = DB::table('erp_stock_order as order')
                ->leftJoin('erp_stock_order_info as orderInfo', 'orderInfo.stock_order_id', 'order.id')
                ->where([
                    'order.flag' => 0,
                    'orderInfo.flag' => 0,
                    'business_id' => $request->business_id
                ])
                ->where('orderInfo.special_id', '>', 0)
                ->whereIn('order.user_id', $newUsers)
                ->groupBy('order.user_id')
                ->selectRaw("`order`.`user_id`, MIN(`order`.`created_at`) as `date`, `order`.`price`")
                ->get()
                ->toArray();
            foreach($firstOrderPrice as $price) {
                $monthData['newUserOrderPrice'] = bcadd($monthData['newUserOrderPrice'], $price->price, 1);
            }
            $data[] = $monthData;
        }

        $tableData = [
            'visitorCount' => ['type' => '访问人数', 'total' => 0],
            'userCount' => ['type' => '下单人数', 'total' => 0],
            'orderCount' => ['type' => '下单数', 'total' => 0],
            'amountPrice' => ['type' => '下单金额', 'total' => 0],
            'newUserCount' => ['type' => '新客户下单人数', 'total' => 0],
            'newUserOrderPrice' => ['type' => '新客户下单金额', 'total' => 0]
        ];
        foreach ($tableData as $key => &$value) {
            foreach($data as $index => $month) {
                $prop = 'month_' . ($index + 1);
                $value[$prop] = $month[$key];
                if ($key === 'amountPrice' || $key === 'newUserOrderPrice') {
                    $value['total'] = bcadd($value['total'], $month[$key], 1);
                } else {
                    $value['total'] += $month[$key];
                }
            }
        }

        $tableData = array_values($tableData);
        
        return [
            'status' => 1,
            'data' => $tableData
        ];
    }

    // 特价商品销售详情
    public function specialSaleDetail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id',
            'start_time' => 'required|string',
            'end_time' => 'required|string'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $startTime = strtotime($request->start_time);
        $endTime = strtotime($request->end_time);
        $orderData = DB::table('erp_stock_order as order')
            ->leftJoin('erp_stock_order_info as orderInfo', 'order.id', 'orderInfo.stock_order_id')
            ->leftJoin('erp_product_list as product', 'orderInfo.product_id', 'product.id')
            ->leftJoin('erp_product_class as class', 'product.class_id', 'class.id')
            ->leftJoin('erp_product_class as brand', 'product.brand_id', 'brand.id')
            ->leftJoin('erp_product_class as series', 'product.series_id', 'series.id')
            ->leftJoin('wxuser', 'wxuser.id', 'order.user_id')
            ->where([
                'order.flag' => 0,
                'orderInfo.flag' => 0,
                'order.business_id' => $request->business_id
            ])
            ->where('orderInfo.special_id', '>', 0)
            ->whereBetween('order.created_at', [$startTime, $endTime])
            ->orderBy('order.created_at', 'desc')
            ->select([
                'product.product_no',
                'class.name as className',
                'brand.name as brandName',
                'series.name as seriesName',
                'product.product_name',
                'product.model',
                'order.order_num',
                'orderInfo.number',
                'orderInfo.price',
                'wxuser.nickname'
            ])
            ->get()
            ->toArray();

        return [
            'status' => 1,
            'data' => $orderData
        ];
    }

    // 用户留存报表
    public function userRetentionReport (Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id'
            // 'start_time' => 'required|string',
            // 'end_time' => 'required|string'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $thisYear = date('Y'); // 今年
        $thisWeek = date('W'); // 本周

        $date = new \DateTime();
        $date->setISODate($thisYear, $thisWeek - 8, 1);
        // $week = $date->format('W'); // 当前周数
        $startTime = strtotime($date->format('Y-m-d'));

        $date->setISODate($thisYear, $thisWeek, 1);
        $endTime = strtotime($date->format('Y-m-d'));

        // 所有新用户
        $newUsers = DB::table('wxuser')
            ->whereBetween('created_at', [$startTime, $endTime])
            ->select(['id', 'created_at'])
            ->get()->toArray();
        // dd($newUsers);

        $users = array_column($newUsers, 'id');

        $visitors = DB::table('mp_operation_log')
            ->whereIn('wx_user_id', $users)
            ->whereBetween('created_at', [$startTime, $endTime])
            ->orderBy('created_at', 'asc')
            // ->selectRaw('wx_user_id, FROM_UNIXTIME(created_at, "%Y-%m-%d")')
            ->select(['wx_user_id', 'created_at'])
            ->groupBy( DB::Raw('FROM_UNIXTIME(created_at, "%Y-%m-%d")') )
            ->groupBy('wx_user_id')
            ->get()->toArray();
        // dd($visitors);

        $date = [];
        for($index = 1, $start = $startTime; $index < 9; $index++, $start += 7*24*60*60) {
            $weekNumber = date('W', $start);
            $end = $start + 7*24*60*60;
            $date[$weekNumber]['week'] = $weekNumber;
            $date[$weekNumber]['dateRange'] = [$start, $end];
            $newUsersInWeek = array_values(array_filter($newUsers, function ($user) use ($start, $end) {
                return $user->created_at >= $start && $user->created_at < $end;
            }));
            // dd($newUsersInWeek);
            $date[$weekNumber]['newUserCount'] = count($newUsersInWeek); // 本周的新用户数

            // 后面每周的留存用户数
            for ($nextWeek = $end, $i = 1; $nextWeek < $endTime; $nextWeek += 7*24*60*60, $i++) {
                if ($date[$weekNumber]['newUserCount']) {
                    $nextWeekEnd = $nextWeek + 7*24*60*60;
                    $users = array_filter($newUsersInWeek, function ($user) use ($nextWeek, $nextWeekEnd, $newUsersInWeek, $visitors) {
                        for($j = 0; $j < count($visitors); $j++) {
                            if ($visitors[$j]->wx_user_id === $user->id && ($visitors[$j]->created_at >= $nextWeek && $visitors[$j]->created_at < $nextWeekEnd)) {
                                return true;
                            }
                        }
                        return false;
                    });
                    $date[$weekNumber]["users_{$i}"] = array_values(array_column($users, 'id'));
                    $date[$weekNumber]["week_{$i}"] = (bcdiv(count($users), $date[$weekNumber]['newUserCount'], 4) * 100) . '%';
                } else {
                    $date[$weekNumber]["week_{$i}"] = '0%';
                    $date[$weekNumber]["users_{$i}"] = [];
                }
            }
        }
        // dd($date);
        $date = array_values($date);

        return [
            'status' => 1,
            'data' => $date
        ];
    }

    // 用户留存表中的新用户
    public function newUsers (Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id',
            'start_time' => 'required|string',
            'end_time' => 'required|string'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $users = DB::table('wxuser')
            ->whereBetween('created_at', [$request->start_time, $request->end_time])
            ->select(['nickname', 'created_at', 'id'])
            ->get()->toArray();
        foreach($users as &$user) {
            $user->created_at = date('Y-m-d', $user->created_at);
        }

        return [
            'status' => 1,
            'data' => $users
        ];
    }

    // 用户留存表中的留存用户
    public function retentionDetail (Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id',
            'users' => 'array'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $users = DB::table('wxuser')
            ->whereIn('id', $request->users)
            ->select(['nickname', 'created_at', 'id'])
            ->get()->toArray();

        foreach($users as &$user) {
            $user->created_at = date('Y-m-d', $user->created_at);
        }
        return [
            'status' => 1,
            'data' => $users
        ];
    }

    // 关键字报表
    public function keywordsReport (Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $startTime = strtotime('today') - 30*24*60*60;
        $inputs = DB::table('mp_operation_log as log')
            ->leftJoin('wxuser', 'wxuser.id', 'log.wx_user_id')
            ->where('log.wx_user_id', '>', 0)
            ->where('log.created_at', '>', $startTime)
            ->where('log.path', 'like', '/api/shop_mp/spus/search%')
            ->select([
                'log.input',
                'wxuser.id',
                'wxuser.nickname'
            ])
            ->get()->toArray();

        $keywordList = [];
        foreach($inputs as &$input) {
            $input->keyword = json_decode($input->input)->keyword;

            if (isset($keywordList[$input->keyword])) {
                $keywordList[$input->keyword]['count'] += 1;
                $update = false;
                foreach($keywordList[$input->keyword]['detail'] as &$user) {
                    if ($user['id'] === $input->id) {
                        $update = true;
                        $user['count'] += 1;
                    }
                }
                if (!$update) {
                    $keywordList[$input->keyword]['detail'][] = ['id' => $input->id, 'nickname' => $input->nickname, 'count' => 1];
                }
            } else {
                $keywordList[$input->keyword] = [
                    'keyword' => $input->keyword,
                    'count' => 1,
                    'detail' => [
                        ['id' => $input->id, 'nickname' => $input->nickname, 'count' => 1]
                    ]
                ];
            }
        }
        uasort($keywordList, function($a, $b) { return $b['count'] - $a['count']; });
        // dd($keywordList);
        $keywordList = array_values($keywordList);

        return [
            'status' => 1,
            'data' => $keywordList
        ];
    }
}
?>