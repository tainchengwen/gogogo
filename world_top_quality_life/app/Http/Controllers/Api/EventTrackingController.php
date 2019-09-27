<?php
namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\StockOrderController;
use App\Repositories\SaleDetailData;


class EventTrackingController extends Controller
{
    public function __construct(SaleDetailData $saleDetailData)
    {
        $this->saleDetailData = $saleDetailData;
    }

    // 日常数据埋点
    public function dailyDetail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id',
            'dateRange' => 'required|array'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $startTime = strtotime($request->dateRange[0]);
        $endTime = strtotime($request->dateRange[1]);

        $category = [];
        $datetime = $startTime;
        while($datetime <= $endTime) {
            $category[] = [
                'date' => date('Y-m-d', $datetime)
            ];
            $datetime += 24 * 60 * 60;
        }

        foreach ($category as &$data) {
            $today = strtotime($data['date']);
            $tomorrow = $today + 24 * 60 * 60;

            // 昨日新曾用户数量
            $newUserCount = DB::table('wxuser')
                ->whereBetween('created_at', [$today, $tomorrow])
                ->count();
            $data['newUserCount'] = $newUserCount;
        }

        foreach($category as &$data) {
            $today = strtotime($data['date']);
            $tomorrow = $today + 24 * 60 * 60;
            
            $detail = $this->getOrderData($today, $tomorrow, $request->business_id);
            // $data['saleAmount'] = $detail->saleAmount ? $detail->saleAmount : '0.0';
            // $data['userCount'] = $detail->userCount ? $detail->userCount : 0;
            // $data['orderCount'] = $detail->orderCount ? $detail->orderCount : 0;
            // $data['averageOrderPrice'] = $detail->saleAmount
            //     ? bcdiv($data['saleAmount'], $data['orderCount'], 1)
            //     : '0.0';

            $data['visitorCount'] = $this->visitorCount($today, $tomorrow);
            $data['userCount'] = count($detail, 0); // 下单用户数
            $data['orderCount'] = 0; // 订单数
            $data['saleAmount'] = '0.0'; // 销售额
            $data['newUserInOrder'] = 0; // 订单中是新客户的客户数量

            $users = array_column($detail, 'user_id');
            $data['newUserInOrder'] = $this->isFirstOrder($users, $today, $request->business_id);

            foreach($detail as $user) {
                $data['saleAmount'] = bcadd($data['saleAmount'], $user->saleAmount, 1);
                $data['orderCount'] = bcadd($data['orderCount'], $user->orderCount, 0);
            }
            $data['averageOrderPrice'] = $data['orderCount']
                ? number_format($data['saleAmount'] / $data['orderCount'], 1)
                : '0.0';
            $data['saleAmount'] = number_format($data['saleAmount'], 1);
            $data['conversion'] = $data['visitorCount']
                ? (bcdiv($data['orderCount'], $data['visitorCount'], 4) * 100) . '%'
                : '0%';
            $data['oldUserPersent'] = $data['userCount']
                ? (bcdiv($data['userCount'] - $data['newUserInOrder'], $data['userCount'], 4) * 100) . '%'
                : '0%';
        }

        return [
            'status' => 1,
            'data' => $category
        ];
    }

    // 销售数据列表
    public function saleDetail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id',
            'start_time' => 'required|string',
            'end_time' => 'required|string'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $startTime = $request->start_time;
        $endTime = $request->end_time;

        if ($startTime === $endTime) {
            $endTime += 24 * 60 * 60;
        }
        $data = $this->saleDetailData->searchData($request->business_id, $startTime, $endTime);

        foreach($data as $key => $value) {
            $value->created_at = date('Y-m-d H:i', $value->created_at);
            unset($value->cost);
        }

        return [
            'status' => 1,
            'data' => $data
        ];
    }

    // 近八周的数据
    public function weeksData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $thisYear = date('Y'); // 今年
        $thisWeek = date('W'); // 本周
        $tableHeader = [ // 前端表头数据
            ['name' => '类别', 'prop' => 'type']
        ];
        $tableData = [ // 前端表格数据格式
            'newUserCount' => ['type' => '新增用户数', 'all' => 0],
            'saleAmount' => ['type' => '销售额',  'all' => 0],
            'orderCount' => ['type' => '订单数',  'all' => 0],
            'userCount' => ['type' => '下单人数',  'all' => 0],
            'newUserInOrder' => ['type' => '新客户下单数',  'all' => 0],
            'averageOrderPrice' => ['type' => '客单价',  'all' => 0],
            'visitorCount' => ['type' => '访客数', 'all' => 0],
            'oldUserPersent' => ['type' => '老买家占比', 'all' => '0%']
        ];

        for ($index = 8; $index > 0; $index--) {
            $date = new \DateTime();
            $date->setISODate($thisYear, $thisWeek - $index, 1);
            $week = $date->format('W'); // 当前周数
            $startTime = strtotime($date->format('Y-m-d')); // 周一的时间戳
            $date->setISODate($thisYear, $thisWeek - $index + 1, 1);
            $endTime = strtotime($date->format('Y-m-d')); // 周日的时间戳
            $propIndex = 9 - $index;
            $tableHeader[] = [
                'name' => "第{$week}周",
                'range' => [$startTime, $endTime],
                'prop' => "week_{$propIndex}"
            ];

            // 当前周的新用户数量
            $tableData['newUserCount']["week_{$propIndex}"] = $this->getNewUserCount($startTime, $endTime);
            $tableData['newUserCount']['all'] += $tableData['newUserCount']["week_{$propIndex}"]; // 加入汇总数据

            $tableData['visitorCount']["week_{$propIndex}"] = $this->visitorCount($startTime, $endTime); // 访客数

            $data = $this->getOrderData($startTime, $endTime, $request->business_id);

            $tableData['userCount']["week_{$propIndex}"] = 0;
            $tableData['orderCount']["week_{$propIndex}"] = 0;
            $tableData['saleAmount']["week_{$propIndex}"] = '0.0';
            $tableData['newUserInOrder']["week_{$propIndex}"] = 0;

            if (!$data) { // 当搜索结果为空，没有订单的情况（所有统计值均为0）
                $tableData['averageOrderPrice']["week_{$propIndex}"] = '0.0';
                $tableData['oldUserPersent']["week_{$propIndex}"] = '0%';
            } else {
                $users = array_column($data, 'user_id');
                $tableData['newUserInOrder']["week_{$propIndex}"] = $this->isFirstOrder($users, $startTime, $request->business_id);
                $tableData['newUserInOrder']['all'] = bcadd($tableData['newUserInOrder']['all'], $tableData['newUserInOrder']["week_{$propIndex}"], 0);

                $tableData['userCount']["week_{$propIndex}"] = count($data, 0); // 下单用户数
                $tableData['userCount']['all'] += $tableData['userCount']["week_{$propIndex}"]; // 加入汇总数据
                // dd($tableData);
                // dd($tableData['userCount']["week_{$propIndex}"], $tableData['newUserCount']["week_{$propIndex}"]);
                $tableData['oldUserPersent']["week_{$propIndex}"] = (
                    bcdiv(
                        $tableData['userCount']["week_{$propIndex}"] - $tableData['newUserInOrder']["week_{$propIndex}"],
                        $tableData['userCount']["week_{$propIndex}"],
                        4
                    ) * 100
                ) . '%';
                foreach ($data as $user) {
                    $tableData['orderCount']["week_{$propIndex}"] += $user->orderCount;
                    $tableData['saleAmount']["week_{$propIndex}"] = bcadd($tableData['saleAmount']["week_{$propIndex}"], $user->saleAmount, 1);
                }
                // 当前周的客单价
                $tableData['averageOrderPrice']["week_{$propIndex}"] = number_format(
                    bcdiv($tableData['saleAmount']["week_{$propIndex}"], $tableData['orderCount']["week_{$propIndex}"], 1), 1
                );
                
                $tableData['orderCount']['all'] += $tableData['orderCount']["week_{$propIndex}"];
                $tableData['saleAmount']['all'] = bcadd($tableData['saleAmount']['all'], $tableData['saleAmount']["week_{$propIndex}"], 1);
                $tableData['saleAmount']["week_{$propIndex}"] = number_format($tableData['saleAmount']["week_{$propIndex}"], 1);
            }

        }
        if ($tableData['orderCount']['all']) {
            $tableData['averageOrderPrice']['all'] = number_format(
                bcdiv($tableData['saleAmount']['all'], $tableData['orderCount']['all'], 1), 1
            );
            $tableData['oldUserPersent']['all'] = (
                bcdiv(
                    $tableData['userCount']['all'] - $tableData['newUserInOrder']['all'],
                    $tableData['userCount']['all'],
                    4
                ) * 100
            ) . '%';
        } else {
            $tableData['averageOrderPrice']['all'] = '0.0';
        }

        $tableData['visitorCount']['all'] = $this->visitorCount($tableHeader[1]['range'][0], $tableHeader[8]['range'][1]);

        $tableData['saleAmount']['all'] = number_format($tableData['saleAmount']['all'], 1);
        $tableData = array_values($tableData);


        $tableHeader[] = [
            'name' => '汇总',
            'range' => [
                $tableHeader[1]['range'][0],
                $tableHeader[8]['range'][1]
            ],
            'prop' => 'all'
        ];

        return [
            'status' => 1,
            'data' => [
                'tableData' => $tableData,
                'tableHeader' => $tableHeader
            ]
        ];
    }

    // 年度埋点数据
    public function yearData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|numeric|exists:erp_business,id'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        // 年份
        $year = $request->year ? $request->year : date('Y');

        $tableData = [
            'newUserCount' => ['type' => '新增用户数', 'all' => 0],
            'saleAmount' => ['type' => '销售额',  'all' => 0],
            'orderCount' => ['type' => '订单数',  'all' => 0],
            'userCount' => ['type' => '下单人数',  'all' => 0],
            'newUserInOrder' => ['type' => '新客户下单数',  'all' => 0],
            'averageOrderPrice' => ['type' => '客单价',  'all' => 0],
            'visitorCount' => ['type' => '访客数', 'all' => 0],
            'oldUserPersent' => ['type' => '老买家占比', 'all' => '0%']
        ];

        for($index = 1; $index < 13; $index++) {
            $startTime = strtotime("{$year}-{$index}");
            $nextMonth = $index + 1;
            if ($nextMonth === 13) {
                $nextYear = $year + 1;
                $endTime = strtotime("{$nextYear}-01");
            } else {
                $endTime = strtotime("${year}-{$nextMonth}");
            }

            $newUserCount = $this->getNewUserCount($startTime, $endTime);

            $tableData['newUserCount']["month_{$index}"] = $newUserCount;
            $tableData['newUserCount']['all'] = bcadd($newUserCount, $tableData['newUserCount']['all'], 0);

            $tableData['visitorCount']["month_{$index}"] = $this->visitorCount($startTime, $endTime);

            $data = $this->getOrderData($startTime, $endTime, $request->business_id);

            $tableData['userCount']["month_{$index}"] = 0;
            $tableData['orderCount']["month_{$index}"] = 0;
            $tableData['saleAmount']["month_{$index}"] = '0.0';
            $tableData['newUserInOrder']["month_{$index}"] = 0;

            if (!$data) { // 当搜索结果为空，没有订单的情况（所有统计值均为0）
                $tableData['averageOrderPrice']["month_{$index}"] = '0.00';
                $tableData['oldUserPersent']["month_{$index}"] = '0%';
            } else {
                $users = array_column($data, 'user_id');
                $tableData['newUserInOrder']["month_{$index}"] = $this->isFirstOrder($users, $startTime, $request->business_id);
                $tableData['newUserInOrder']['all'] = bcadd($tableData['newUserInOrder']['all'], $tableData['newUserInOrder']["month_{$index}"], 0);

                $tableData['userCount']["month_{$index}"] = count($data, 0); // 下单用户数
                $tableData['userCount']['all'] += $tableData['userCount']["month_{$index}"]; // 加入汇总数据
                foreach ($data as $user) {
                    $tableData['orderCount']["month_{$index}"] += $user->orderCount;
                    $tableData['saleAmount']["month_{$index}"] = bcadd($tableData['saleAmount']["month_{$index}"], $user->saleAmount, 1);
                }
                // 当前周的客单价
                $tableData['averageOrderPrice']["month_{$index}"] = number_format(
                    bcdiv($tableData['saleAmount']["month_{$index}"], $tableData['orderCount']["month_{$index}"], 1), 1
                );

                $tableData['oldUserPersent']["month_{$index}"] = (
                    bcdiv(
                        $tableData['userCount']["month_{$index}"] - $tableData['newUserInOrder']["month_{$index}"],
                        $tableData['userCount']["month_{$index}"],
                        4
                    ) * 100
                ) . '%';
                
                $tableData['orderCount']['all'] += $tableData['orderCount']["month_{$index}"];
                $tableData['saleAmount']['all'] = bcadd($tableData['saleAmount']['all'], $tableData['saleAmount']["month_{$index}"], 1);
                // 格式化金额
                $tableData['saleAmount']["month_{$index}"] = number_format($tableData['saleAmount']["month_{$index}"], 1);
            }

        }
        if ($tableData['orderCount']['all']) {
            $tableData['averageOrderPrice']['all'] = number_format($tableData['saleAmount']['all'] / $tableData['orderCount']['all'], 1);
            $tableData['oldUserPersent']['all'] = (
                bcdiv(
                    $tableData['userCount']['all'] - $tableData['newUserInOrder']['all'],
                    $tableData['userCount']['all'],
                    4
                ) * 100
            ) . '%';
        } else {
            $tableData['averageOrderPrice']['all'] = '0.0';
            $tableData['oldUserPersent']['all'] = '0%';
        }
        // 格式化金额
        $tableData['saleAmount']['all'] = number_format($tableData['saleAmount']['all'], 1);

        $nextYear = $year + 1;
        $tableData['visitorCount']['all'] = $this->visitorCount(strtotime("{$year}-01"), strtotime("{$nextYear}-01"));
        
        $tableData = array_values($tableData);
        return [
            'status' => 1,
            'data' => $tableData
        ];
    }

    /**
     * @description 查询某一时段的访客数
     * @param int $startTime 起始时间戳
     * @param int $endTime 截止时间戳
     * @return int 返回访客的数量
     */
    public function visitorCount($startTime, $endTime)
    {
        $data = DB::table('mp_operation_log')
            ->where('wx_user_id', '>', 0)
            ->whereBetween('created_at', [$startTime, $endTime])
            ->groupBy('wx_user_id')
            ->get()->toArray();
        return count($data);
    }
    /**
     * @description 查询某一个时段的新用户数量
     * @param int $startTime 起始时间的时间戳
     * @param int $endTime 截止时间的时间戳
     * @return int 返回新用户的数量
     */
    public function getNewUserCount(int $startTime, int $endTime): int
    {
        return DB::table('wxuser')
            ->select('*')
            ->whereBetween('created_at', [$startTime, $endTime])
            ->count();
    }

    // 是否是第一次下单（第一次下单）
    public function isFirstOrder(array $users_id, int $startTime, int $business_id): int
    {
        $firstOrder = DB::table('erp_stock_order')
            ->where([
                'flag' => 0,
                'business_id' => $business_id
            ])
            ->whereIn('user_id', $users_id)
            ->groupBy('user_id')
            ->select(DB::raw("user_id, IF(MIN(`created_at`) > {$startTime}, 1, 0) as isFirst"))
            ->get()
            ->toArray();

        $newUsers = array_filter(array_column($firstOrder, 'isFirst'), function ($item) {
            return $item;
        });
        return count($newUsers);
    }

    /**
     * @description 查询某一时段的 每个用户的 下单数量 和 订单总金额
     * @param int $startTime 起始时间的时间戳
     * @param int $endTime 截止时间的时间戳
     * @param int $business_id 事业部 id
     * @return int 返回一个数组包含 每个用户的 下单数量 和 订单总金额
     */
    public function getOrderData(int $startTime, int $endTime, int $business_id): array
    {
        /**
         * sum_price 销售额
         * orderCount 下单数
         * userCount 下单用户数
         */
        // $sql = 
// <<<EOF
// select
// count(userId) as userCount,
// SUM(sum_price) as saleAmount,
// SUM(order_count) as orderCount
// from (
//     SELECT
//     SUM(price) as sum_price,
//     count(order_id) as order_count,
//     userId
//     FROM (
//         SELECT
//         order.price as price,
//         wxuser.id as userId,
//         order.id as order_id
//         FROM
//         `erp_stock_order` as `order`
//         LEFT JOIN `wxuser` on order.user_id = wxuser.id
//         LEFT JOIN erp_stock_order_info as orderInfo on orderInfo.stock_order_id = order.id
//         LEFT JOIN erp_stock_order_info_receive as orderInfoReceive on orderInfoReceive.stock_order_info_id = orderInfo.id
//         LEFT JOIN erp_deliver_goods_record_info as deliverRecordInfo on deliverRecordInfo.stock_order_info_receive_id = orderInfoReceive.id
//         LEFT JOIN erp_deliver_goods_record as deliverRecord on deliverRecord.id = deliverRecordInfo.deliver_goods_record_id
//         WHERE (
//             order.flag = 0 AND
//             orderInfo.flag = 0 AND
//             orderInfoReceive.flag = 0 AND
//             deliverRecordInfo.flag = 0 AND
//             deliverRecord.flag = 0 AND
//             wxuser.in_black_list = 0 AND
//             order.business_id = {$business_id} AND
//             order.send_status = 1 AND
//             deliverRecord.created_at >= {$startTime} AND
//             deliverRecord.created_at <= {$endTime}
//         )
//         GROUP BY order.id
//     ) as result
//     GROUP BY result.userId
// ) as usersOrder
// EOF;

//         return DB::select($sql)[0];

        return DB::select('
            select
            sum(order_price) as `saleAmount`,
            count(order_id) as `orderCount`,
            user_id
            from (
                select
                `order`.`id` as `order_id`,
                `wxuser`.`id` as `user_id`,
                `order`.`price` as `order_price`
                from
                `erp_stock_order` as `order`
                left join `erp_deliver_goods_record` as `deliverRecord` on `order`.`id` = `deliverRecord`.`stock_order_id`
                left join `wxuser` on `order`.`user_id` = `wxuser`.`id`
                where (
                    `order`.`flag` = 0 and
                    `order`.`send_status` = 1 and
                    `wxuser`.`in_black_list` = 0 and
                    `deliverRecord`.`flag` = 0 and
                    `order`.`business_id` = ? and
                    `deliverRecord`.`created_at` between ? and ?
                )
                group by `order`.`id`
            ) as `result`
            group by `user_id`
        ', [$business_id, $startTime, $endTime]);
    }
}
?>