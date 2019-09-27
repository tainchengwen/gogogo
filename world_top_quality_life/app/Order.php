<?php

namespace App;

use App\Jobs\ProcessPodcast;
use EasyWeChat\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Order extends Model
{
    //
    //
    protected $table = 'order';


    protected $dateFormat = 'U';

    protected $fillable = [
        'price', 'order_num', 'status',
    ];

    public function packages(){
        return $this->hasMany(Package::class, 'order_id');
    }

    public function users()
    {
        return $this->belongsTo(WxUser::class,'userid');
    }

    //下单
    //通过重量获取单价
    public function getPriceByWeight($weight){
        $weight = floatval($weight);
        if($weight){
            if($weight <= 3){
                return 200;
            }elseif($weight > 3 && $weight <= 4 ){
                return 250;
            }elseif($weight > 4 && $weight <= 5  ){
                //单价
                return 300;
            }else {
                //如果超过5  就不变了
                return 300;
            }
        }else{
            return 200;
        }
    }

    //通过用户的价格模板获取单价
    public function getPriceTempByWeightUserid($userid,$weight){
        //查下此userid 有没有模版
        $temp = DB::table('price_temp') -> where([
            'user_id' => $userid
        ]) -> first();
        //如果不存在模板 则直接返回通用模版
        if(!$temp || !$temp -> small_price || !$temp -> first_price || !$temp -> price_other){
            return $this -> getPriceByWeight($weight);
        }

        //根据模板 计算价格
        if($weight <= 1){
            return $temp -> small_price;
        }

        //计重重量
        $weight_count = floatval($weight) - 1 ;
        //向上取0。5 的倍数
        $times = ceil($weight_count*2);
        //计算价格
        $price = $temp -> first_price + $times * $temp -> price_other;
        return $price;
    }

    //通过重量  路线 获取成本
    public function getPriceInfoByWeight($weight,$route){
        //获取路线的价格
        $price_temp = DB::table('price_temp') -> where([
            'route_id' => $route,
            'user_id' => 0
        ]) -> first();
        if(!$price_temp || !$price_temp -> small_price || !$price_temp -> first_price || !$price_temp -> price_other){
            return 0;
        }

        //计重重量
        $weight_count = floatval($weight) - 1 ;
        //向上取0。5 的倍数
        $times = ceil($weight_count*2);
        //计算价格
        $price = $price_temp -> first_price + $times * $price_temp -> price_other;

        return $price;


    }

    //获取区域的价格
    public function getAreaPriceTemp($area_id,$weight){
        //获取区域模版
        $price_temp = DB::table('price_temp') -> where([
            'area_id' => $area_id  //区域id
        ]) -> first();
        if(!$price_temp || !$price_temp -> small_price || !$price_temp -> first_price || !$price_temp -> price_other){
            return 0;
        }

        //计重重量
        $weight_count = floatval($weight) - 1 ;
        //向上取0。5 的倍数
        $times = ceil($weight_count*2);
        //计算价格
        $price = round(floatval($price_temp -> first_price + $times * $price_temp -> price_other),2);

        return $price;

    }

    //获取最新包裹 update by 20190310
    static function getPackageNum0310($from_area,$userid){
        switch (date("Y")){
            case '2018':$year = 'A';break;
            case '2019':$year = 'B';break;
            case '2020':$year = 'C';break;
            case '2021':$year = 'D';break;
            case '2022':$year = 'E';break;
            case '2023':$year = 'F';break;
            case '2024':$year = 'G';break;
            default: $year='B';
        }
        $date = date('md').$year;

        if(intval($from_area) <= 999  ){
            $from_area_str = 'A'.sprintf('%03s',$from_area);
        }else{
            $from_area_str = 'B'.sprintf('%03s',$from_area);
        }



        $num_info = DB::table('package_numbers_queue')
            -> where([
                'number_type' => 0,
                'date' => $date,
                'area_id' => $from_area,
                'user_id' => $userid,
            ]) -> first();
        if(!$num_info){
            DB::table('package_numbers_queue') -> insertGetId([
                'number_type' => 0,
                'date' => $date,
                'area_id' => $from_area,
                'user_id' => $userid,
                'num' => 1,
                'created_at' => time(),
                'updated_at' => time(),
            ]);
            $ordernum = 1;

        }else{
            DB::table('package_numbers_queue')
                -> where([
                    'id' => $num_info -> id
                ])-> update([
                    'updated_at' => time(),
                    'num' => intval($num_info -> num) + 1
                ]);

            $ordernum = intval($num_info -> num) + 1;
        }

        return [
            'order_number' => 'H'.$from_area_str.'-'.sprintf('%06s',$userid).'-'.$date.'-'.sprintf('%03s',$ordernum),
            'sequeue_num' => $ordernum
        ];

    }


    //获取最新包裹编号
    //此方法废弃 之后方法  直接用 getPackageNum0310
    public function getPackageNum($from_area,$code = '',$userid=0){
        return self::getPackageNum0310($from_area,$userid);
        
        //大阪 01 东京 02
        $ordernum = 1;
        //找下包裹编号
        $sequeue_num = DB::table('sequeue') -> where([
            'type' => 1,
            'date' => date('Y-m-d'),
            'from_area' => $from_area
        ]) -> first();
        if(empty($sequeue_num)){
            //如果不存在
            DB::table('sequeue') -> insert([
                'date' => date('Y-m-d'),
                'order_num' => 1,
                'type' => 1,
                'from_area' => $from_area
            ]);
        }else{
            $ordernum = intval($sequeue_num -> order_num)+1;
            DB::table('sequeue') -> where([
                'date' => date('Y-m-d'),
                'from_area' => $from_area,
                'type' => 1,
            ]) -> update([
                'order_num' => $ordernum
            ]);

        }

        //拼接包裹编号
        $order_number = 'HQ-'.$from_area.'-'.$code.date('md').'B-'.substr(strval($ordernum+1000),1,3);
        return [
            'order_number' => $order_number,
            'sequeue_num' => $ordernum
        ];
    }

    //获取订单编号
    public function getOrderNum(){
        //大阪 01 东京 02
        $ordernum = 1;
        //找下订单编号
        $sequeue_num = DB::table('sequeue') -> where([
            'type' => 0,
            'date' => date('Y-m-d'),
        ]) -> first();
        if(empty($sequeue_num)){
            //如果不存在
            DB::table('sequeue') -> insert([
                'date' => date('Y-m-d'),
                'order_num' => 1,
                'type' => 0,
            ]);
        }else{
            $ordernum = intval($sequeue_num -> order_num)+1;
            DB::table('sequeue') -> where([
                'date' => date('Y-m-d'),
                'type' => 0,
            ]) -> update([
                'order_num' => $ordernum
            ]);

        }

        //拼接订单编号
        $order_number = 'W'.date('Ymd').substr(strval($ordernum+10000),1,4);
        return $order_number;
    }


    //通过姓名 电话 地址 看下这组数据 可以用几次
    public function getNumbers($arr){
        if($arr['name'] && $arr['tel'] && $arr['address']){
            $number_max = 0;
            //找出最多使用的个数
            //名字是否重复
            $count_pre = DB::table('packages') -> where([
                'wuliu_num' => '',
                'name' => trim($arr['name']),
                'flag' => 0
            ])  -> count();
            if($count_pre > $number_max){
                $number_max = $count_pre;
            }


            //电话是否重复
            $count_pre = DB::table('packages') -> where([
                'wuliu_num' => '',
                'tel' => trim($arr['tel']),
                'flag' => 0
            ])  -> count();
            if($count_pre > $number_max){
                $number_max = $count_pre;
            }

            //address
            //地址是否重复
            $count_pre = DB::table('packages') -> where([
                'wuliu_num' => '',
                'address' => trim($arr['address']),
                'flag' => 0
            ])  -> count();
            if($count_pre > $number_max){
                $number_max = $count_pre;
            }

            if($number_max >= 3){
                return [];
            }else{
                $return_arr = [];
                for($i = $number_max;$i < 3;$i++ ){
                    $return_arr[] = [
                        'name' => $arr['name'],
                        'tel' => $arr['tel'],
                        'address' => $arr['address'],
                        'province' => $arr['province'],
                        'city' =>$arr['city'],
                    ];
                }
                return $return_arr;
            }




        }else{
            return [];
        }
    }


    //用户批量下单 以及自动下单
    public function underOrder($data){
        $weights = isset($data['weights'])?$data['weights']:[];
        $package_nums = isset($data['package_nums'])?$data['package_nums']:[];
        $from_area = isset($data['from_area'])?$data['from_area']:1;
        $userid = isset($data['user_id'])?$data['user_id']:0;
        $names = isset($data['names'])?$data['names']:[];
        $address = isset($data['address'])?$data['address']:[];
        $provinces = isset($data['provinces'])?$data['provinces']:[];
        $citys = isset($data['citys'])?$data['citys']:[];
        $tels = isset($data['tels'])?$data['tels']:[];
        $uuid_names_arr = isset($data['uuid_names_arr'])?$data['uuid_names_arr']:'';
        $remark = isset($data['remark'])?$data['remark']:'';
        $pay_status = isset($data['pay_status'])?$data['pay_status']:0;
        $pay_type = isset($data['pay_type'])?$data['pay_type']:0;
        $repertory_id = isset($data['repertory_id'])?$data['repertory_id']:0;
        $taxs = isset($data['taxs_arr'])?$data['taxs_arr']:[];
        $split_id = isset($data['split_id'])?$data['split_id']:[];
        $mp_package_id = isset($data['mp_package_id'])?$data['mp_package_id']:0;
        $is_min_order = isset($data['is_min_order'])?$data['is_min_order']:0;
        $not_need_pay = isset($data['not_need_pay'])?$data['not_need_pay']:0;
        $declare_ids = isset($data['declare_ids'])?$data['declare_ids']:[];
        $order_from = isset($data['order_from'])?$data['order_from']:'';
        $mp_order_type = isset($data['mp_order_type'])?$data['mp_order_type']:0;

        if(!count($weights)){
            return false;
        }

        if(count($split_id)){
            $order_is_split = 1;
        }else{
            $order_is_split = 0;
        }

        $repertory_weight = $weights;
        //到货物流单号
        if($repertory_id){
            //重量取随机数
            foreach($weights as $k => $vo){
                //加减一个 0 -0.15 的随机数
                //$fuhao = rand(0,1);
                $num = rand(0,15);
                $weights[$k] = round(floatval($vo) - 0.02 - $num/100,2);
                /*
                if($fuhao){
                    $weights[$k] = round(floatval($vo) + $num/100,2);
                }else{
                    $weights[$k] = round(floatval($vo) - $num/100,2);
                }
                */

            }
        }




        //计算价格
        $price_sum = 0;
        //每个包裹的价格
        $price_package = [];

        foreach($weights as $key => $vo){
            //每个包裹的价格
            $price_package[$key]  = $this -> getPriceTempByWeightUserid($userid,$vo);
            //总价
            $price_sum += $price_package[$key];
        }


        //通过区域 找到区域编号
        $area_info = DB::table('area_name') -> where([
            'id' => $from_area
        ]) -> first();


        //用户信息
        $userinfo = DB::table('wxuser') -> where([
            'id' => $userid
        ]) -> first();
        //会员等级优惠比例
        //$config_price = config('admin.class_price');
        //该会员优惠的比例
        //$parameter_user = $config_price[$userinfo ->class];
        $temp_price_setting = DB::table('price_setting') -> where([
            'class_num' => $userinfo ->class
        ]) -> first();

        //该会员优惠的比例
        //$parameter_user = $config_price[$userinfo ->class];
        $parameter_user = $temp_price_setting -> bili;
        //订单编号
        $order_number = $this -> getOrderNum();
        if($split_id || $is_min_order){
            $status_temp = 1;
        }else{
            $status_temp = 5;
        }

        if($mp_package_id){
            $order_temp = DB::table('order')
                -> where([
                    'mp_package_id' => $mp_package_id,
                    'flag' => 0
                ]) -> first();
            if($order_temp){
                return false;
            }
        }

        $orderid = DB::table('order') -> insertGetId([
            'price' => $price_sum,
            'order_num' => $order_number,  //订单编号
            'userid' => $userid,
            'package_num' => count($names),
            'created_at' => time(),
            'updated_at' => time(),
            'date_time' => date('Y-m-d'),
            'count_package' => count($names),
            'count_address' => ceil(count($names)/3),
            'from_area' => $from_area,
            //实际需要支付的金额
            'pay_price' => $parameter_user * $price_sum,
            //实际优惠的金额
            'minus_price' => $price_sum*(1-$parameter_user),

            'status' => $status_temp,
            'remark' => $remark,
            'pay_status' => $pay_status,
            'pay_type' => $pay_type,
            'repertory_id' => $repertory_id,
            'is_split' => $order_is_split,
            'mp_package_id' => $mp_package_id,
            'not_need_pay' => $not_need_pay
        ]);

        //保存包裹信息
        //获取goods_paratemer 最大id
        //$info = DB::table('goods_paratemer') -> where('is_super','0') -> orderBy('id','desc')  -> first();
        foreach($weights as $k => $vo){
            //$order_model = new Order();
            //获取订单编号
            if(count($package_nums)){
                $package_num = $package_nums[$k];
                $sequeue_num = 1;
            }else{
                $package_num_arr = $this -> getPackageNum($area_info -> id,$userinfo -> code,$userid);
                $package_num = $package_num_arr['order_number'];
                $sequeue_num = $package_num_arr['sequeue_num'];
            }


            //如果是客户自己  填写的申报 则新增申报 然后关联
            if(isset($taxs[$k]) && count($taxs[$k]) > 1){
                $goods_id = $this -> addGoodsPartemer($taxs[$k]);
            }elseif(isset($declare_ids[$k])){
                $goods_id = $declare_ids[$k];
            }else{
                $goods_id = $this -> getGoodsPartemerId($weights[$k]);
            }



            $package_id = DB::table('packages') -> insertGetId([
                'package_num' => $package_num,
                'order_id' => $orderid,
                'userid' => $userid,
                'weight' => $weights[$k],
                'address' => $address[$k],
                'province' => $provinces[$k],
                'city' => $citys[$k],
                'name' => $names[$k],
                'uuid' => isset($uuid_names_arr[$names[$k]])?$uuid_names_arr[$names[$k]]:$this -> create_uuid(),
                'tel' => $tels[$k],
                'price' => $price_package[$k],
                'created_at' => time(),
                'updated_at' => time(),
                'goods_id' => $goods_id,
                'sequeue_num' => $sequeue_num,
                'from_area' => $from_area,
                'repertory_weight' => $repertory_weight[$k], //原下单重量
                'split_id' => isset($split_id[$k])?$split_id[$k]:0,
            ]);


            //非小程序订单下单 要下个小程序单
            if(!$order_from){

                $min_package_id = DB::table('mp_temp_package_number') -> insertGetId([
                    'package_num' => $package_num,
                    'wx_user_id' => $userid,
                    'order_type' => $mp_order_type?$mp_order_type:1,
                    'weight' => $weights[$k],
                    'package_id' => $package_id,
                    'province' => $provinces[$k],
                    'city' => $citys[$k],
                    'address' => $address[$k],
                    'name' => $names[$k],
                    'tel' => $tels[$k],
                    'created_at' => time(),
                    'updated_at' => time(),
                ]);

            }



        }



        //下单成功发送模版消息
        $config = [
            'app_id' => env('WECHAT_OFFICIAL_ACCOUNT_APPID'),
            'secret' => env('WECHAT_OFFICIAL_ACCOUNT_SECRET'),
        ];


        $env = env('APP_ENV');

        if($env != 'local' && isset($userinfo->openid)) {
            $app = Factory::officialAccount($config);
            $res = $app->template_message->send([
                'touser' => $userinfo->openid,
                'template_id' => 'HBrHuSBwZtgdYAN-pCJpOmgYP0YkTzMRtKcb4NrXbRM',
                'url' => url('writeAdd') . '/' . $orderid,
                'miniprogram' => [
                    'appid' => env('MINI_APPID'),
                    'pagepath' => "/pages/orderlist/orderlist"
                    //'pagepath' => isset($min_package_id)?'/pages/info/info?package_id='.$min_package_id.'&status=0':''
                ],
                'data' => [
                    'first' => '尊敬的用户，您已下单成功！',
                    'keyword1' => $order_number,
                    'keyword2' => date('Y-m-d H:i'),
                    'remark' => '请尽快填写收货地址'
                ]
            ]);
        }

        return $orderid;
    }


    function create_uuid($prefix = ""){    //可以指定前缀
        $str = md5(uniqid(mt_rand(), true));
        $uuid  = substr($str,0,8) . '-';
        $uuid .= substr($str,8,4) . '-';
        $uuid .= substr($str,12,4) . '-';
        $uuid .= substr($str,16,4) . '-';
        $uuid .= substr($str,20,12);
        return $prefix . $uuid;
    }


    //随机取一条 goods_paratemer 更新到 package_id 对应 goods_paratemer 关系表
    public function getGoodsPartemerId($weight){
        $max_info = DB::table('goods_paratemer')
            -> orderBy('id','desc')
            -> first();
        $rand = rand(1,intval($max_info -> id));
        $info = DB::table('goods_paratemer') -> where('id',$rand) -> first();

        $info = $this -> declareWeight($info,$weight);


        $id = DB::table('packages_goods_paratemer') -> insertGetId([
            's_content1' => $info -> s_content1,
            'Tax_code1' => $info ->Tax_code1,
            's_price1' => $info ->s_price1,
            's_pieces1' => $info ->s_pieces1,
            's_weight1' => $info ->s_weight1,
            's_content2' => $info ->s_content2,
            'Tax_code2' => $info ->Tax_code2,
            's_price2' => $info ->s_price2,
            's_pieces2' => $info ->s_pieces2,
            's_weight2' => $info ->s_weight2,
            's_content3' => $info ->s_content3,
            'Tax_code3' => $info ->Tax_code3,
            's_price3' => $info ->s_price3,
            's_pieces3' => $info ->s_pieces3,
            's_weight3' => $info ->s_weight3,
            'declare_currency' => $info ->declare_currency,
            'declare_value' => $info ->declare_value
        ]);
        return $id;
    }


    //新增goods_paratemer
    public function addGoodsPartemer($arr){
        $arr['declare_currency'] = 'RMB';
        $id = DB::table('packages_goods_paratemer') -> insertGetId($arr);
        return $id;
    }


    //根据商品总重量 计算每个申报物品重量
    public function declareWeight($vol,$weight){
        $weight_package = round(floatval($weight),2);
//商品总数
        $count_goods = intval($vol -> s_pieces1) + intval($vol -> s_pieces2) + intval($vol -> s_pieces3);
        //商品3的重量
        $weight_goods_3 = round(floatval($weight_package/$count_goods),1);
        //商品1的重量
        $weight_goods_1 = round($weight_goods_3 - rand(5,10)/100,1);
        //商品2的重量
        $weight_goods_2 = round($weight_goods_3 - rand(5,10)/100,1);
        $vol -> s_weight1 = $weight_goods_1;
        $vol -> s_weight2 = $weight_goods_2;
        $vol -> s_weight3 = $weight_goods_3;
        //商品总价值 = 每个商品的数量 * 申报价格
        $vol -> declare_value = round(intval($vol -> s_pieces1)*floatval($vol -> s_price1) + intval($vol -> s_pieces2)*floatval($vol -> s_price2) + intval($vol -> s_pieces3)*floatval($vol -> s_price3) ,1  );
        return $vol;



        //update by 20181225

        $weight_package = round(floatval($weight),2) - 0.3;
        /*
        包裹重量在3-5kg
        1.	申报3个品名，随机调用一个组合
        2.	计算商品3的重量，公式：包裹重量/商品总数
        3.	商品1和商品2的重量公式：商品3重量-介于0.12~0.17的随机数
        */

        if($weight > 3){
            //商品总数
            $count_goods = intval($vol -> s_pieces1) + intval($vol -> s_pieces2) + intval($vol -> s_pieces3);
            //商品3的重量
            $weight_goods_3 = round(floatval($weight_package/$count_goods),1);
            //商品1的重量
            $weight_goods_1 = round($weight_goods_3 - rand(12,17)/100,1);
            //商品2的重量
            $weight_goods_2 = round($weight_goods_3 - rand(12,17)/100,1);
            $vol -> s_weight1 = $weight_goods_1;
            $vol -> s_weight2 = $weight_goods_2;
            $vol -> s_weight3 = $weight_goods_3;
            //商品总价值 = 每个商品的数量 * 申报价格
            $vol -> declare_value = round(intval($vol -> s_pieces1)*floatval($vol -> s_price1) + intval($vol -> s_pieces2)*floatval($vol -> s_price2) + intval($vol -> s_pieces3)*floatval($vol -> s_price3) ,1  );
            return $vol;
        }
        /*
        i.	包裹重量在2-3公斤
        1.	申报2个品名，随机调用一个组合，然后去除商品3，只申报商品1和2
        2.	计算商品2的重量，公式：包裹重量/商品总数
        3.	商品1重量公式：商品2重量-介于0.12~0.17的随机数
        */
        if($weight > 2 && $weight <= 3){

            //商品总数
            $count_goods = intval($vol -> s_pieces1) + intval($vol -> s_pieces2);


            //商品2的重量
            $weight_goods_2 = round(floatval($weight_package/$count_goods),1);
            //商品1的重量
            $weight_goods_1 = round($weight_goods_2 - rand(12,17)/100,1);

            //3全部不要
            $vol -> s_content3 = '';
            $vol -> Tax_code3 = '';
            $vol -> s_price3 = '';
            $vol -> s_pieces3 = '';
            $vol -> s_weight3 = '';

            $vol -> s_weight1 = $weight_goods_1;
            $vol -> s_weight2 = $weight_goods_2;
            //商品总价值 = 每个商品的数量 * 申报价格
            $vol -> declare_value = round(intval($vol -> s_pieces1)*floatval($vol -> s_price1) + intval($vol -> s_pieces2)*floatval($vol -> s_price2),1  );
            return $vol;
        }

        if($weight <= 2 ){
            /*
            i.	包裹重量在1-2公斤
            1.	申报1个品名，随机调用一个组合，然后去除商品2和3，只申报商品1
            2.  计算商品1的重量，公式：包裹重量/商品总数-介于0.12~0.17的随机数
            */

            //商品总数
            $count_goods = intval($vol -> s_pieces1);

            $weight_goods_temp = round(floatval($weight_package/$count_goods),1);
            //商品1的重量
            $weight_goods_1 = round($weight_goods_temp - rand(12,17)/100,1);


            // 2 3 全部不要
            $vol -> s_content3 = '';
            $vol -> Tax_code3 = '';
            $vol -> s_price3 = '';
            $vol -> s_pieces3 = '';
            $vol -> s_weight3 = '';

            $vol -> s_content2 = '';
            $vol -> Tax_code2 = '';
            $vol -> s_price2 = '';
            $vol -> s_pieces2 = '';
            $vol -> s_weight2 = '';

            $vol -> s_weight1 = $weight_goods_1;
            $vol -> declare_value = round(intval($vol -> s_pieces1)*floatval($vol -> s_price1),2);
            return $vol;
        }
    }



    public function tracnsform($vol){
        $weight_package = round(floatval($vol -> weight),2) - rand(35,45)/100;

        $weight_xiaoyu1 = round(floatval($vol -> weight),2);

        //通过goods_id 找packages_goods_paratemer
        $vol = DB::table('packages_goods_paratemer') -> where('id',$vol -> goods_id) -> first();
        if($vol -> s_content1 && $vol -> s_content2 && $vol -> s_content3){
            //商品总数
            $count_goods = intval($vol -> s_pieces1) + intval($vol -> s_pieces2) + intval($vol -> s_pieces3);
            //商品3的重量
            $weight_goods_3 = round(floatval($weight_package/$count_goods),2);
            //商品1的重量
            $weight_goods_1 = round($weight_goods_3 - rand(5,7)/100,2);
            //商品2的重量
            $weight_goods_2 = round($weight_goods_3 - rand(5,7)/100,2);
            $vol -> s_weight1 = $weight_goods_1;
            $vol -> s_weight2 = $weight_goods_2;
            $vol -> s_weight3 = $weight_goods_3;
            $cellData = [

                [
                    'ProductName' => $vol -> s_content1.'',
                    'PostTaxNum' => $vol -> Tax_code1.'',
                    'Unit' => '个',
                    'ProductCount' => $vol -> s_pieces1,
                    'Price' => $vol -> s_price1,
                    'Currency' => 'CNY',
                    'TotalWeight' => $vol -> s_weight1
                ],

                [
                    'ProductName' => $vol -> s_content2.'',
                    'PostTaxNum' => $vol -> Tax_code2.'',
                    'Unit' => '个',
                    'ProductCount' => $vol -> s_pieces2,
                    'Price' => $vol -> s_price2,
                    'Currency' => 'CNY',
                    'TotalWeight' => $vol -> s_weight2
                ],

                [
                    'ProductName' => $vol -> s_content3.'',
                    'PostTaxNum' => $vol -> Tax_code3.'',
                    'Unit' => '个',
                    'ProductCount' => $vol -> s_pieces3,
                    'Price' => $vol -> s_price3,
                    'Currency' => 'CNY',
                    'TotalWeight' => $vol -> s_weight3
                ]
            ];
        }elseif($vol -> s_content1 && $vol -> s_content2){
            //商品总数
            $count_goods = intval($vol -> s_pieces1) + intval($vol -> s_pieces2);


            //商品2的重量
            $weight_goods_2 = round(floatval($weight_package/$count_goods),2);
            //商品1的重量
            $weight_goods_1 = round($weight_goods_2 - rand(5,7)/100,2);


            $vol -> s_weight1 = $weight_goods_1;
            $vol -> s_weight2 = $weight_goods_2;
            $cellData = [

                [
                    'ProductName' => $vol -> s_content1.'',
                    'PostTaxNum' => $vol -> Tax_code1.'',
                    'Unit' => '个',
                    'ProductCount' => $vol -> s_pieces1,
                    'Price' => $vol -> s_price1,
                    'Currency' => 'CNY',
                    'TotalWeight' => $vol -> s_weight1
                ],

                [
                    'ProductName' => $vol -> s_content2.'',
                    'PostTaxNum' => $vol -> Tax_code2.'',
                    'Unit' => '个',
                    'ProductCount' => $vol -> s_pieces2,
                    'Price' => $vol -> s_price2,
                    'Currency' => 'CNY',
                    'TotalWeight' => $vol -> s_weight2
                ]
            ];
        }else{
            $weight_package = $weight_xiaoyu1;
            //商品总数
            $count_goods = intval($vol -> s_pieces1);

            $weight_goods_temp = $weight_package + rand(35,45)/100;
            //商品1的重量
            $weight_goods_1 = round($weight_goods_temp - $weight_package/$count_goods,2);

            $vol -> s_weight1 = $weight_goods_1;

            $cellData = [

                [
                    'ProductName' => $vol -> s_content1.'',
                    'PostTaxNum' => $vol -> Tax_code1.'',
                    'Unit' => '个',
                    'ProductCount' => $vol -> s_pieces1,
                    'Price' => $vol -> s_price1,
                    'Currency' => 'CNY',
                    'TotalWeight' => $vol -> s_weight1
                ],
            ];
        }

        return $cellData;
    }



    //手动下单 && 前台店员下单方法
    public function underhandOrder($data){
        $from_area = isset($data['from_area'])?$data['from_area']:1;
        $userid = isset($data['userid'])?$data['userid']:1;
        $weights = isset($data['weights'])?$data['weights']:[];
        $remark = isset($data['remark'])?$data['remark']:'';
        $printer_userid = isset($data['printer_userid'])?$data['printer_userid']:0;

        //计算价格
        $price_sum = 0;
        //每个包裹的价格
        $price_package = [];

        foreach($weights as $key => $vo){
            if($vo){
                //每个包裹的价格
                $price_package[$key]  = $this -> getPriceTempByWeightUserid($userid,$vo);
                //总价
                $price_sum += $price_package[$key];
            }
        }

        //通过区域 找到区域编号
        $area_info = DB::table('area_name') -> where([
            'id' => $from_area
        ]) -> first();


        //用户信息
        $userinfo = DB::table('wxuser') -> where([
            'id' => $userid
        ]) -> first();
        //会员等级优惠比例
        $config_price = config('admin.class_price');
        //该会员优惠的比例
        $parameter_user = $config_price[$userinfo ->class];
        //订单编号
        $order_number = $this -> getOrderNum();


        $orderid = DB::table('order') -> insertGetId([
            'price' => $price_sum,
            'order_num' => $order_number,  //订单编号
            'userid' => $userid,
            'package_num' => count($weights),
            'created_at' => time(),
            'updated_at' => time(),
            'date_time' => date('Y-m-d'),
            'count_package' => count($weights),
            'count_address' => ceil(count($weights)/3),
            'from_area' => $from_area,
            //实际需要支付的金额
            'pay_price' => $parameter_user * $price_sum,
            //实际优惠的金额
            'minus_price' => $price_sum*(1-$parameter_user),
            //'status' => 1,  //刚下单 是待填地址状态
            'remark' => $remark,
            'printer_userid' => $printer_userid

        ]);

        //保存包裹信息
        //获取goods_paratemer 最大id
        //$info = DB::table('goods_paratemer') -> orderBy('id','desc') -> first();
        foreach($weights as $k => $vo){
            $order_model = new Order();

            $goods_id = $this -> getGoodsPartemerId($weights[$k]);
            //获取订单编号
            $package_num = $order_model -> getPackageNum($area_info -> id,$userinfo -> code,$userid);
            $package_id = DB::table('packages') -> insertGetId([
                'package_num' => $package_num['order_number'],
                'order_id' => $orderid,
                'userid' => $userid,
                'weight' => $vo,
                'price' => $price_package[$k],
                'created_at' => time(),
                'updated_at' => time(),
                'from_area' => $from_area,
                'goods_id' => $goods_id,
                'sequeue_num' => $package_num['sequeue_num']
            ]);

            $min_package_id = DB::table('mp_temp_package_number') -> insertGetId([
                'package_num' => $package_num['order_number'],
                'wx_user_id' => $userid,
                'order_type' => 1,
                'weight' => $weights[$k],
                'package_id' => $package_id,
                'created_at' => time(),
                'updated_at' => time(),
            ]);
        }


        //下单成功发送模版消息

        $config = [
            'app_id' => env('WECHAT_OFFICIAL_ACCOUNT_APPID'),
            'secret' => env('WECHAT_OFFICIAL_ACCOUNT_SECRET'),
        ];

        $env = env('APP_ENV');
        if($env != 'local' && $userinfo->openid) {
            $app = Factory::officialAccount($config);
            $send = [
                'touser' => $userinfo->openid,
                'template_id' => 'HBrHuSBwZtgdYAN-pCJpOmgYP0YkTzMRtKcb4NrXbRM',
                'url' => url('writeAdd') . '/' . $orderid,
                'miniprogram' => [
                    'appid' => env('MINI_APPID'),
                    //'pagepath' => isset($min_package_id)?'/pages/info/info?package_id='.$min_package_id.'&status=1':"/pages/orderlist/orderlist"
                    'pagepath' => "/pages/orderlist/orderlist"
                ],
                'data' => [
                    'first' => '尊敬的用户，您已下单成功！',
                    'keyword1' => $order_number,
                    'keyword2' => date('Y-m-d H:i'),
                    'remark' => '请尽快填写收货地址'
                ]
            ];
            Log::info(json_encode($send));
            $res = $app->template_message->send($send);
        }

        return $orderid;

    }


    //取导出的表头
    public function getTableTopTr($type = 1){
        $cellData = [];
        if($type == 1){
            $cellData = [
                ['S.No','customer_hawb','eExpressno#','receiver_name','receiver_phone','receiver_address1','receiver_city','receiver_province','receiver_Zip','s_content1','Tax_code1','s_price1','s_pieces1','s_weight1','s_content2','Tax_code2','s_price2','s_pieces2','s_weight2','s_content3','Tax_code3','s_price3','s_pieces3','s_weight3','declare_currency','declare_value','weight','shipment_date','duty_paid'],
                ['序號','參考編號','e特快單號','收件人姓名','收件人電話(11位手机号)','收件人地址1','收件人城市','收件人省份','郵編','貨物名稱1','稅則號1','單價1','件數1','單個重量1','貨物名稱2','稅則號2','單價2','件數2','單個重量2','貨物名稱3','稅則號3','單價3','件數3','單個重量3','申報貨幣','申報價值','總重','提單日期','代付税金(Yes=DDP,No=DDU)']
            ];
        }elseif($type == 2 || $type == 10){
            //NN100 导出模版 && MX01导出模板
            $cellData = [
                ['关联单号',	'内件小票号',	'托盘号','发件人','发件人地址',	'发件人电话',	'收件人','收件人身份证号',	'收件人电话',	'城市',	'省份',	'地区','邮编','收件人地址','本包裹名称','本包裹总毛重量(KG)','国家简称','是否代缴税','货物类型','是否航空服务','货物名称']
            ];
        }elseif($type == 3){
            $cellData = [
                ['关联单号','货号','物品名称','物品简称','规格','品牌','物品数量','单位','物品单价','币别','物品总净重(KG)','行邮税号']
            ];
        }
        return $cellData;

    }

    //导出模板 订单
    public function exportPacketsTemp($vol,$key,$type = 1){
        $cellData = [];
        //通用模板的导出
        if($type == 1){
            //处理申报信息
            //通过包裹的重量 来处理
            //s_content1	Tax_code1	s_price1	s_pieces1	s_weight1
            //s_content2	Tax_code2	s_price2	s_pieces2	s_weight2
            //s_content3	Tax_code3	s_price3	s_pieces3	s_weight3


            //包裹重量
            $weight_package = round(floatval($vol -> weight),2) - 0.3;
            /*
            包裹重量在3-5kg
            1.	申报3个品名，随机调用一个组合
            2.	计算商品3的重量，公式：包裹重量/商品总数
            3.	商品1和商品2的重量公式：商品3重量-介于0.12~0.17的随机数
            */

            if($vol -> weight > 3 && $vol -> weight <= 10){
                //商品总数
                $count_goods = intval($vol -> s_pieces1) + intval($vol -> s_pieces2) + intval($vol -> s_pieces3);
                //商品3的重量
                $weight_goods_3 = round(floatval($weight_package/$count_goods),1);
                //商品1的重量
                $weight_goods_1 = round($weight_goods_3 - rand(12,17)/100,1);
                //商品2的重量
                $weight_goods_2 = round($weight_goods_3 - rand(12,17)/100,1);
                $vol -> s_weight1 = $weight_goods_1;
                $vol -> s_weight2 = $weight_goods_2;
                $vol -> s_weight3 = $weight_goods_3;
                //商品总价值 = 每个商品的数量 * 申报价格
                $vol -> declare_value = round(intval($vol -> s_pieces1)*floatval($vol -> s_price1) + intval($vol -> s_pieces2)*floatval($vol -> s_price2) + intval($vol -> s_pieces3)*floatval($vol -> s_price3) ,1  );

            }
            /*
            i.	包裹重量在2-3公斤
            1.	申报2个品名，随机调用一个组合，然后去除商品3，只申报商品1和2
            2.	计算商品2的重量，公式：包裹重量/商品总数
            3.	商品1重量公式：商品2重量-介于0.12~0.17的随机数
            */
            if($vol -> weight > 2 && $vol -> weight <= 3){

                //商品总数
                $count_goods = intval($vol -> s_pieces1) + intval($vol -> s_pieces2);


                //商品2的重量
                $weight_goods_2 = round(floatval($weight_package/$count_goods),1);
                //商品1的重量
                $weight_goods_1 = round($weight_goods_2 - rand(12,17)/100,1);

                //3全部不要
                $vol -> s_content3 = '';
                $vol -> Tax_code3 = '';
                $vol -> s_price3 = '';
                $vol -> s_pieces3 = '';
                $vol -> s_weight3 = '';

                $vol -> s_weight1 = $weight_goods_1;
                $vol -> s_weight2 = $weight_goods_2;

                //商品总价值 = 每个商品的数量 * 申报价格
                $vol -> declare_value = round(intval($vol -> s_pieces1)*floatval($vol -> s_price1) + intval($vol -> s_pieces2)*floatval($vol -> s_price2),1  );

            }

            if($vol -> weight <= 2 ){
                /*
                i.	包裹重量在1-2公斤
                1.	申报1个品名，随机调用一个组合，然后去除商品2和3，只申报商品1
                2.  计算商品1的重量，公式：包裹重量/商品总数-介于0.12~0.17的随机数
                */

                //商品总数
                $count_goods = intval($vol -> s_pieces1);

                $weight_goods_temp = round(floatval($weight_package/$count_goods),1);
                //商品1的重量
                $weight_goods_1 = round($weight_goods_temp - rand(12,17)/100,1);


                // 2 3 全部不要
                $vol -> s_content3 = '';
                $vol -> Tax_code3 = '';
                $vol -> s_price3 = '';
                $vol -> s_pieces3 = '';
                $vol -> s_weight3 = '';

                $vol -> s_content2 = '';
                $vol -> Tax_code2 = '';
                $vol -> s_price2 = '';
                $vol -> s_pieces2 = '';
                $vol -> s_weight2 = '';

                $vol -> s_weight1 = $weight_goods_1;

                //商品总价值 = 每个商品的数量 * 申报价格
                $vol -> declare_value = round(intval($vol -> s_pieces1)*floatval($vol -> s_price1) ,1  );

            }


            //查看邮编
            $zip_info = DB::table('zips')
                -> where('city','like','%'.mb_substr(trim($vol -> province),0,2,'utf-8').'%')
                -> where('province','like','%'.mb_substr(trim($vol -> city),0,2,'utf-8').'%')
                -> first();

            if(!empty($zip_info)){
                $zip = $zip_info -> zip_code;
            }else{
                $zip = $vol -> zip;
            }


            $key ++ ;
            $cellData = [
                (string)$key.'',
                (string)$vol -> package_num,
                (string)'',
                (string)$vol -> name,
                (string)"".$vol -> tel."",
                (string)$vol -> address,
                (string)$vol -> city,
                (string)$vol -> province,
                (string)$zip.'',
                (string)$vol -> s_content1.'',
                (string)$vol -> Tax_code1.'',
                (string)$vol -> s_price1.'',
                (string)$vol -> s_pieces1.'',
                (string)$vol -> s_weight1.'',
                (string)$vol -> s_content2.'',
                (string)$vol -> Tax_code2.'',
                (string)$vol -> s_price2.'',
                (string)$vol -> s_pieces2.'',
                (string)$vol -> s_weight2.'',
                (string)$vol -> s_content3.'',
                (string)$vol -> Tax_code3.'',
                (string)$vol -> s_price3.'',
                (string)$vol -> s_pieces3.'',
                (string)$vol -> s_weight3.'',
                (string)$vol -> declare_currency.'',
                (string)$vol -> declare_value.'',
                (string)$vol -> weight.'',
                date('Y-m-d'),
                'YES'
            ];
        }elseif($type == 2 || $type == 10){
            //第二套模板  NN100 模板
            $key ++ ;
            //查看邮编
            $zip_info = DB::table('zips')
                -> where('city','like','%'.mb_substr(trim($vol -> province),0,2,'utf-8').'%')
                -> where('province','like','%'.mb_substr(trim($vol -> city),0,2,'utf-8').'%')
                -> first();

            if(!empty($zip_info)){
                $zip = $zip_info -> zip_code;
            }else{
                $zip = $vol -> zip;
            }
            $cellData = [

                (string)$vol -> package_num,
                (string)'',
                (string)'',
                (string)'MS. CHAN',
                (string)'LOT89, NEAR RCP,SHA LING, MAN KAM TO ROAD, SHEUNG SHUI，NEW TERRITORIES',
                (string)'54848815'."",

                (string)"".$vol -> name."",
                (string)"",
                (string)"".$vol -> tel."",
                (string)$vol -> city,
                (string)$vol -> province,
                (string)"",
                (string)$zip.'',
                (string)$vol -> address,
                //这里 是本包裹名称
                (string)"",
                (string)$vol -> weight.'',
                (string)"HK",
                (string)"Y",
                "本地",
                "N",

            ];
        }elseif($type == 3){
            $weight_package = round(floatval($vol -> weight),2) - 0.3;
            /*
            包裹重量在3-5kg
            1.	申报3个品名，随机调用一个组合
            2.	计算商品3的重量，公式：包裹重量/商品总数
            3.	商品1和商品2的重量公式：商品3重量-介于0.12~0.17的随机数
            */

            if($vol -> weight > 3){
                //商品总数
                $count_goods = intval($vol -> s_pieces1) + intval($vol -> s_pieces2) + intval($vol -> s_pieces3);
                //商品3的重量
                $weight_goods_3 = round(floatval($weight_package/$count_goods),1);
                //商品1的重量
                $weight_goods_1 = round($weight_goods_3 - rand(12,17)/100,1);
                //商品2的重量
                $weight_goods_2 = round($weight_goods_3 - rand(12,17)/100,1);
                $vol -> s_weight1 = $weight_goods_1;
                $vol -> s_weight2 = $weight_goods_2;
                $vol -> s_weight3 = $weight_goods_3;


                $cellData = [

                    [
                        (string)$vol -> s_content1.'',
                        (string)$vol -> Tax_code1.'',
                        (string)$vol -> s_price1.'',
                        (string)$vol -> s_pieces1.'',
                        (string)$vol -> s_weight1.''
                    ],

                    [
                        (string)$vol -> s_content2.'',
                        (string)$vol -> Tax_code2.'',
                        (string)$vol -> s_price2.'',
                        (string)$vol -> s_pieces2.'',
                        (string)$vol -> s_weight2.'',
                    ],

                    [
                        (string)$vol -> s_content3.'',
                        (string)$vol -> Tax_code3.'',
                        (string)$vol -> s_price3.'',
                        (string)$vol -> s_pieces3.'',
                        (string)$vol -> s_weight3.'',
                    ]
                ];

                //商品总价值 = 每个商品的数量 * 申报价格
                //$vol -> declare_value = round(intval($vol -> s_pieces1)*floatval($vol -> s_price1) + intval($vol -> s_pieces2)*floatval($vol -> s_price2) + intval($vol -> s_pieces3)*floatval($vol -> s_price3) ,1  );

            }
            /*
            i.	包裹重量在2-3公斤
            1.	申报2个品名，随机调用一个组合，然后去除商品3，只申报商品1和2
            2.	计算商品2的重量，公式：包裹重量/商品总数
            3.	商品1重量公式：商品2重量-介于0.12~0.17的随机数
            */
            if($vol -> weight > 2 && $vol -> weight <= 3){

                //商品总数
                $count_goods = intval($vol -> s_pieces1) + intval($vol -> s_pieces2);


                //商品2的重量
                $weight_goods_2 = round(floatval($weight_package/$count_goods),1);
                //商品1的重量
                $weight_goods_1 = round($weight_goods_2 - rand(12,17)/100,1);

                //3全部不要
                $vol -> s_content3 = '';
                $vol -> Tax_code3 = '';
                $vol -> s_price3 = '';
                $vol -> s_pieces3 = '';
                $vol -> s_weight3 = '';

                $vol -> s_weight1 = $weight_goods_1;
                $vol -> s_weight2 = $weight_goods_2;


                $cellData = [

                    [
                        (string)$vol -> s_content1.'',
                        (string)$vol -> Tax_code1.'',
                        (string)$vol -> s_price1.'',
                        (string)$vol -> s_pieces1.'',
                        (string)$vol -> s_weight1.''
                    ],

                    [
                        (string)$vol -> s_content2.'',
                        (string)$vol -> Tax_code2.'',
                        (string)$vol -> s_price2.'',
                        (string)$vol -> s_pieces2.'',
                        (string)$vol -> s_weight2.'',
                    ]
                ];

                //商品总价值 = 每个商品的数量 * 申报价格
                //$vol -> declare_value = round(intval($vol -> s_pieces1)*floatval($vol -> s_price1) + intval($vol -> s_pieces2)*floatval($vol -> s_price2),1  );

            }

            if($vol -> weight <= 2 ){
                /*
                i.	包裹重量在1-2公斤
                1.	申报1个品名，随机调用一个组合，然后去除商品2和3，只申报商品1
                2.  计算商品1的重量，公式：包裹重量/商品总数-介于0.12~0.17的随机数
                */

                //商品总数
                $count_goods = intval($vol -> s_pieces1);

                $weight_goods_temp = round(floatval($weight_package/$count_goods),1);
                //商品1的重量
                $weight_goods_1 = round($weight_goods_temp - rand(12,17)/100,1);


                // 2 3 全部不要
                $vol -> s_content3 = '';
                $vol -> Tax_code3 = '';
                $vol -> s_price3 = '';
                $vol -> s_pieces3 = '';
                $vol -> s_weight3 = '';

                $vol -> s_content2 = '';
                $vol -> Tax_code2 = '';
                $vol -> s_price2 = '';
                $vol -> s_pieces2 = '';
                $vol -> s_weight2 = '';

                $vol -> s_weight1 = $weight_goods_1;


                $cellData = [

                    [
                        (string)$vol -> s_content1.'',
                        (string)$vol -> Tax_code1.'',
                        (string)$vol -> s_price1.'',
                        (string)$vol -> s_pieces1.'',
                        (string)$vol -> s_weight1.''
                    ]
                ];

                //商品总价值 = 每个商品的数量 * 申报价格
                //$vol -> declare_value = round(intval($vol -> s_pieces1)*floatval($vol -> s_price1) ,1  );

            }







        }


        return $cellData;
    }



    //通过包裹信息 查询所有的申报（融通）
    public function getAllGoodsByPackage($vol){
        //判断创建的时间
        if($vol -> id > config('admin.paratemer_package_id')){
            $transRes = $this -> tracnsform($vol);
            return $transRes;
        }





        //如果他的goods_id >= 10000 则 不需要搞下边的了
        if($vol -> goods_id >= 10000){
            if($vol -> s_content1 && $vol -> s_content2 && $vol -> s_content3){
                $cellData = [

                    [
                        'ProductName' => $vol -> s_content1.'',
                        'PostTaxNum' => $vol -> Tax_code1.'',
                        'Unit' => '个',
                        'ProductCount' => $vol -> s_pieces1,
                        'Price' => $vol -> s_price1,
                        'Currency' => 'CNY',
                        'TotalWeight' => $vol -> s_weight1
                    ],

                    [
                        'ProductName' => $vol -> s_content2.'',
                        'PostTaxNum' => $vol -> Tax_code2.'',
                        'Unit' => '个',
                        'ProductCount' => $vol -> s_pieces2,
                        'Price' => $vol -> s_price2,
                        'Currency' => 'CNY',
                        'TotalWeight' => $vol -> s_weight2
                    ],

                    [
                        'ProductName' => $vol -> s_content3.'',
                        'PostTaxNum' => $vol -> Tax_code3.'',
                        'Unit' => '个',
                        'ProductCount' => $vol -> s_pieces3,
                        'Price' => $vol -> s_price3,
                        'Currency' => 'CNY',
                        'TotalWeight' => $vol -> s_weight3
                    ]
                ];
            }elseif($vol -> s_content1 && $vol -> s_content2){
                $cellData = [

                    [
                        'ProductName' => $vol -> s_content1.'',
                        'PostTaxNum' => $vol -> Tax_code1.'',
                        'Unit' => '个',
                        'ProductCount' => $vol -> s_pieces1,
                        'Price' => $vol -> s_price1,
                        'Currency' => 'CNY',
                        'TotalWeight' => $vol -> s_weight1
                    ],

                    [
                        'ProductName' => $vol -> s_content2.'',
                        'PostTaxNum' => $vol -> Tax_code2.'',
                        'Unit' => '个',
                        'ProductCount' => $vol -> s_pieces2,
                        'Price' => $vol -> s_price2,
                        'Currency' => 'CNY',
                        'TotalWeight' => $vol -> s_weight2
                    ]
                ];
            }else{
                $cellData = [

                    [
                        'ProductName' => $vol -> s_content1.'',
                        'PostTaxNum' => $vol -> Tax_code1.'',
                        'Unit' => '个',
                        'ProductCount' => $vol -> s_pieces1,
                        'Price' => $vol -> s_price1,
                        'Currency' => 'CNY',
                        'TotalWeight' => $vol -> s_weight1
                    ],
                ];
            }

            return $cellData;
        }




        $weight_package = round(floatval($vol -> weight),2) - rand(35,45)/100;
        /*
        包裹重量在3-5kg
        1.	申报3个品名，随机调用一个组合
        2.	计算商品3的重量，公式：包裹重量/商品总数
        3.	商品1和商品2的重量公式：商品3重量-介于0.12~0.17的随机数
        */

        //update by 20181117
        if($vol -> weight > 3){
            //商品总数
            $count_goods = intval($vol -> s_pieces1) + intval($vol -> s_pieces2) + intval($vol -> s_pieces3);
            //商品3的重量
            $weight_goods_3 = round(floatval($weight_package/$count_goods),2);
            //商品1的重量
            $weight_goods_1 = round($weight_goods_3 - rand(5,7)/100,2);
            //商品2的重量
            $weight_goods_2 = round($weight_goods_3 - rand(5,7)/100,2);
            $vol -> s_weight1 = $weight_goods_1;
            $vol -> s_weight2 = $weight_goods_2;
            $vol -> s_weight3 = $weight_goods_3;



            $cellData = [

                [
                    'ProductName' => $vol -> s_content1.'',
                    'PostTaxNum' => $vol -> Tax_code1.'',
                    'Unit' => '个',
                    'ProductCount' => $vol -> s_pieces1,
                    'Price' => $vol -> s_price1,
                    'Currency' => 'CNY',
                    'TotalWeight' => $vol -> s_weight1
                ],

                [
                    'ProductName' => $vol -> s_content2.'',
                    'PostTaxNum' => $vol -> Tax_code2.'',
                    'Unit' => '个',
                    'ProductCount' => $vol -> s_pieces2,
                    'Price' => $vol -> s_price2,
                    'Currency' => 'CNY',
                    'TotalWeight' => $vol -> s_weight2
                ],

                [
                    'ProductName' => $vol -> s_content3.'',
                    'PostTaxNum' => $vol -> Tax_code3.'',
                    'Unit' => '个',
                    'ProductCount' => $vol -> s_pieces3,
                    'Price' => $vol -> s_price3,
                    'Currency' => 'CNY',
                    'TotalWeight' => $vol -> s_weight3
                ]
            ];

            //商品总价值 = 每个商品的数量 * 申报价格
            //$vol -> declare_value = round(intval($vol -> s_pieces1)*floatval($vol -> s_price1) + intval($vol -> s_pieces2)*floatval($vol -> s_price2) + intval($vol -> s_pieces3)*floatval($vol -> s_price3) ,1  );

        }
        /*
        i.	包裹重量在2-3公斤
        1.	申报2个品名，随机调用一个组合，然后去除商品3，只申报商品1和2
        2.	计算商品2的重量，公式：包裹重量/商品总数
        3.	商品1重量公式：商品2重量-介于0.12~0.17的随机数
        */
        if($vol -> weight > 2 && $vol -> weight <= 3){

            //商品总数
            $count_goods = intval($vol -> s_pieces1) + intval($vol -> s_pieces2);


            //商品2的重量
            $weight_goods_2 = round(floatval($weight_package/$count_goods),2);
            //商品1的重量
            $weight_goods_1 = round($weight_goods_2 - rand(5,7)/100,2);

            //3全部不要
            $vol -> s_content3 = '';
            $vol -> Tax_code3 = '';
            $vol -> s_price3 = '';
            $vol -> s_pieces3 = '';
            $vol -> s_weight3 = '';

            $vol -> s_weight1 = $weight_goods_1;
            $vol -> s_weight2 = $weight_goods_2;


            $cellData = [

                [
                    'ProductName' => $vol -> s_content1.'',
                    'PostTaxNum' => $vol -> Tax_code1.'',
                    'Unit' => '个',
                    'ProductCount' => $vol -> s_pieces1,
                    'Price' => $vol -> s_price1,
                    'Currency' => 'CNY',
                    'TotalWeight' => $vol -> s_weight1
                ],

                [
                    'ProductName' => $vol -> s_content2.'',
                    'PostTaxNum' => $vol -> Tax_code2.'',
                    'Unit' => '个',
                    'ProductCount' => $vol -> s_pieces2,
                    'Price' => $vol -> s_price2,
                    'Currency' => 'CNY',
                    'TotalWeight' => $vol -> s_weight2
                ]
            ];

            //商品总价值 = 每个商品的数量 * 申报价格
            //$vol -> declare_value = round(intval($vol -> s_pieces1)*floatval($vol -> s_price1) + intval($vol -> s_pieces2)*floatval($vol -> s_price2),1  );

        }

        if($vol -> weight > 1 &&  $vol -> weight <= 2 ){

            /*
            i.	包裹重量在1-2公斤
            1.	申报1个品名，随机调用一个组合，然后去除商品2和3，只申报商品1
            2.  计算商品1的重量，公式：包裹重量/商品总数-介于0.12~0.17的随机数
            */

            //商品总数
            $count_goods = intval($vol -> s_pieces1);

            $weight_goods_temp = round(floatval($weight_package/$count_goods),2);
            //商品1的重量
            $weight_goods_1 = round($weight_goods_temp - rand(5,7)/100,2);


            // 2 3 全部不要
            $vol -> s_content3 = '';
            $vol -> Tax_code3 = '';
            $vol -> s_price3 = '';
            $vol -> s_pieces3 = '';
            $vol -> s_weight3 = '';

            $vol -> s_content2 = '';
            $vol -> Tax_code2 = '';
            $vol -> s_price2 = '';
            $vol -> s_pieces2 = '';
            $vol -> s_weight2 = '';

            $vol -> s_weight1 = $weight_goods_1;


            $cellData = [

                [
                    'ProductName' => $vol -> s_content1.'',
                    'PostTaxNum' => $vol -> Tax_code1.'',
                    'Unit' => '个',
                    'ProductCount' => $vol -> s_pieces1,
                    'Price' => $vol -> s_price1,
                    'Currency' => 'CNY',
                    'TotalWeight' => $vol -> s_weight1
                ],
            ];

            //商品总价值 = 每个商品的数量 * 申报价格
            //$vol -> declare_value = round(intval($vol -> s_pieces1)*floatval($vol -> s_price1) ,1  );

        }

        if($vol -> weight <= 1){
            /*
            i.	包裹重量在1公斤以下
            1.	申报1个品名，随机调用一个组合，然后去除商品2和3，只申报商品1
            2.  计算商品1重量，公式：包裹重量-0.2，再除以商品1数量
            */

            //商品总数
            $count_goods = intval($vol -> s_pieces1);

            $weight_goods_temp = $weight_package - 0.2;
            //商品1的重量
            $weight_goods_1 = round($weight_goods_temp - $count_goods,2);


            // 2 3 全部不要
            $vol -> s_content3 = '';
            $vol -> Tax_code3 = '';
            $vol -> s_price3 = '';
            $vol -> s_pieces3 = '';
            $vol -> s_weight3 = '';

            $vol -> s_content2 = '';
            $vol -> Tax_code2 = '';
            $vol -> s_price2 = '';
            $vol -> s_pieces2 = '';
            $vol -> s_weight2 = '';

            $vol -> s_weight1 = $weight_goods_1;


            $cellData = [

                [
                    'ProductName' => $vol -> s_content1.'',
                    'PostTaxNum' => $vol -> Tax_code1.'',
                    'Unit' => '个',
                    'ProductCount' => $vol -> s_pieces1,
                    'Price' => $vol -> s_price1,
                    'Currency' => 'CNY',
                    'TotalWeight' => $vol -> s_weight1
                ],
            ];

            //商品总价值 = 每个商品的数量 * 申报价格
            //$vol -> declare_value = round(intval($vol -> s_pieces1)*floatval($vol -> s_price1) ,1  );
        }


        return $cellData;

    }

    //通过包裹 查询申报信息（中通）
    public function getAllGoodsItem($vol){
        $weight_package = round(floatval($vol -> weight),2) - 0.3;
        /*
        包裹重量在3-5kg
        1.	申报3个品名，随机调用一个组合
        2.	计算商品3的重量，公式：包裹重量/商品总数
        3.	商品1和商品2的重量公式：商品3重量-介于0.12~0.17的随机数
        */

        if($vol -> weight > 3 && $vol -> weight <= 5){
            //商品总数
            $count_goods = intval($vol -> s_pieces1) + intval($vol -> s_pieces2) + intval($vol -> s_pieces3);
            //商品3的重量
            $weight_goods_3 = round(floatval($weight_package/$count_goods),1);
            //商品1的重量
            $weight_goods_1 = round($weight_goods_3 - rand(12,17)/100,1);
            //商品2的重量
            $weight_goods_2 = round($weight_goods_3 - rand(12,17)/100,1);
            $vol -> s_weight1 = $weight_goods_1;
            $vol -> s_weight2 = $weight_goods_2;
            $vol -> s_weight3 = $weight_goods_3;


            $cellData = [
                [
                    'ItemDescription' => $vol -> s_content1.'',
                    'hsCode' => $vol -> Tax_code1.'',
                    'ItemPrice' =>  $vol -> s_price1,
                    'ItemPieces' => $vol -> s_pieces1,
                    'ItemWeight' => $vol -> s_weight1

                ],[
                    'ItemDescription' => $vol -> s_content2.'',
                    'hsCode' => $vol -> Tax_code2.'',
                    'ItemPrice' =>  $vol -> s_price2,
                    'ItemPieces' => $vol -> s_pieces2,
                    'ItemWeight' => $vol -> s_weight2
                ],[
                    'ItemDescription' => $vol -> s_content3.'',
                    'hsCode' => $vol -> Tax_code3.'',
                    'ItemPrice' =>  $vol -> s_price3,
                    'ItemPieces' => $vol -> s_pieces3,
                    'ItemWeight' => $vol -> s_weight3
                ]
            ];

            //商品总价值 = 每个商品的数量 * 申报价格
            //$vol -> declare_value = round(intval($vol -> s_pieces1)*floatval($vol -> s_price1) + intval($vol -> s_pieces2)*floatval($vol -> s_price2) + intval($vol -> s_pieces3)*floatval($vol -> s_price3) ,1  );

        }
        /*
        i.	包裹重量在2-3公斤
        1.	申报2个品名，随机调用一个组合，然后去除商品3，只申报商品1和2
        2.	计算商品2的重量，公式：包裹重量/商品总数
        3.	商品1重量公式：商品2重量-介于0.12~0.17的随机数
        */
        if($vol -> weight > 2 && $vol -> weight <= 3){

            //商品总数
            $count_goods = intval($vol -> s_pieces1) + intval($vol -> s_pieces2);


            //商品2的重量
            $weight_goods_2 = round(floatval($weight_package/$count_goods),1);
            //商品1的重量
            $weight_goods_1 = round($weight_goods_2 - rand(12,17)/100,1);

            //3全部不要
            $vol -> s_content3 = '';
            $vol -> Tax_code3 = '';
            $vol -> s_price3 = '';
            $vol -> s_pieces3 = '';
            $vol -> s_weight3 = '';

            $vol -> s_weight1 = $weight_goods_1;
            $vol -> s_weight2 = $weight_goods_2;


            $cellData = [
                [
                    'ItemDescription' => $vol -> s_content1.'',
                    'hsCode' => $vol -> Tax_code1.'',
                    'ItemPrice' =>  $vol -> s_price1,
                    'ItemPieces' => $vol -> s_pieces1,
                    'ItemWeight' => $vol -> s_weight1

                ],[
                    'ItemDescription' => $vol -> s_content2.'',
                    'hsCode' => $vol -> Tax_code2.'',
                    'ItemPrice' =>  $vol -> s_price2,
                    'ItemPieces' => $vol -> s_pieces2,
                    'ItemWeight' => $vol -> s_weight2
                ]
            ];

            //商品总价值 = 每个商品的数量 * 申报价格
            //$vol -> declare_value = round(intval($vol -> s_pieces1)*floatval($vol -> s_price1) + intval($vol -> s_pieces2)*floatval($vol -> s_price2),1  );

        }

        if($vol -> weight <= 2 ){
            /*
            i.	包裹重量在1-2公斤
            1.	申报1个品名，随机调用一个组合，然后去除商品2和3，只申报商品1
            2.  计算商品1的重量，公式：包裹重量/商品总数-介于0.12~0.17的随机数
            */

            //商品总数
            $count_goods = intval($vol -> s_pieces1);

            $weight_goods_temp = round(floatval($weight_package/$count_goods),1);
            //商品1的重量
            $weight_goods_1 = round($weight_goods_temp - rand(12,17)/100,1);


            // 2 3 全部不要
            $vol -> s_content3 = '';
            $vol -> Tax_code3 = '';
            $vol -> s_price3 = '';
            $vol -> s_pieces3 = '';
            $vol -> s_weight3 = '';

            $vol -> s_content2 = '';
            $vol -> Tax_code2 = '';
            $vol -> s_price2 = '';
            $vol -> s_pieces2 = '';
            $vol -> s_weight2 = '';

            $vol -> s_weight1 = $weight_goods_1;


            $cellData = [
                [
                    'ItemDescription' => $vol -> s_content1.'',
                    'hsCode' => $vol -> Tax_code1.'',
                    'ItemPrice' =>  $vol -> s_price1,
                    'ItemPieces' => $vol -> s_pieces1,
                    'ItemWeight' => $vol -> s_weight1

                ]
            ];

            //商品总价值 = 每个商品的数量 * 申报价格
            //$vol -> declare_value = round(intval($vol -> s_pieces1)*floatval($vol -> s_price1) ,1  );

        }

        return $cellData;

    }





    //计算成本
    public function checkPrice($package_info,$order_info,$route){
        $order_info = DB::table('order') -> where([
            'id' => $order_info -> id
        ]) -> first();

        $weight = $package_info -> weight;
        $from_area = $order_info -> from_area;
        //区域价格  通过from_area 找价格模板
        $area_price = $this -> getAreaPriceTemp($from_area,$weight);
        //Log::info('price :'.$area_price);
        if(!$area_price){
            return false;
        }
        //比较 区域价格 跟 区域余额
        //查找区域余额
        $area_info= DB::table('area_name') -> where([
            'id' => $from_area
        ]) -> first();
        if($area_info ->  price >= $area_price){
            return true;
        }else{
            return false;
        }

    }

    //发货 处理
    public function sendOrder($package_info,$order_info,$route,$wuliu_num,$is_api=true){
        //更新这个包裹的成本

        $order_info = DB::table('order') -> where([
            'id' => $order_info -> id
        ]) -> first();

        $weight = $package_info -> weight;
        $cost = $this -> getPriceInfoByWeight($weight,$route);
        $from_area = $order_info -> from_area;
        //区域价格  通过from_area 找价格模板
        $area_price = $this -> getAreaPriceTemp($from_area,$weight);
        //区域余额 扣除
        $model_area = new AreaName();
        if(!$order_info -> is_split){
            //2019 0310 新增 异常件下单 不需要扣除
            //拆单的 不需要扣除余额
            $model_area -> usePrice($from_area,$area_price,$package_info -> package_num);
        }





        //dd($cost);

        DB::table('packages') -> where([
            'id' => $package_info -> id
        ]) -> update([
            'wuliu_num' => $wuliu_num,
            'cost' => $cost,
            'area_price' => $area_price,
            'route_id' => $route,
            //'pdf' => '',
            'is_api' => $is_api?0:1
        ]);

        //修改小程序订单的状态为待发货
        DB::table('mp_temp_package_number') -> where([
            'package_id' => $package_info -> id
        ]) -> update([
            'order_status' => 3
        ]);

        //把单号 包裹编号 记录储存下来
        
        PackageNumbers::insert([
            'package_id' => $package_info -> id,
            'wuliu_number' => $wuliu_num,
            'created_at' => time(),
            'updated_at' => time()
        ]);


        Log::info('id:'.$package_info -> id.' wuliu_num:'.$wuliu_num.' route_id:'.$route);


        //如果此订单 status 2 -》 3 则发消息  update status:1为待发货 2部分发货 3已发货

        //如果是第一次修改 则发送发货信息 && 返点
        if($order_info -> status == 1 && !$order_info -> is_split){
            //通过订单拿到openid
            $userinfo = DB::table('wxuser') -> where([
                'id' => $order_info -> userid
            ]) -> first();
            //发送微信消息
            $config = [
                'app_id' => env('WECHAT_OFFICIAL_ACCOUNT_APPID'),
                'secret' => env('WECHAT_OFFICIAL_ACCOUNT_SECRET'),
            ];


            //如果开发环境 不需要发
            $env = env('APP_ENV');
            if($env != 'local' && isset($userinfo -> openid)){
                $app = Factory::officialAccount($config);
                $res = $app->template_message->send([
                    'touser' => $userinfo -> openid,
                    'template_id' => 'wRWj8-05qeb4x5huiX5psaws-KX80-gcNaSPWvwZ1cI',
                    'url' => url('payOrder').'/'.$package_info -> order_id,
                    'data' => [
                        'first' => '您好，您的订单已发货！',
                        'keyword1' => $order_info -> order_num,
                        'keyword2' => date('Y-m-d H:i'),
                        'remark' => '感谢您的使用'
                    ]
                ]);
            }



            //发货完 返点
            // 这个人发货 给他上家返点


            //找到他的邀请人
            $from_user_id = $userinfo -> from_userid;
            if($from_user_id){

                $from_user_info = DB::table('wxuser') -> where([
                    'id' => $from_user_id,
                ]) -> first();


                //得到他的等级 和 该返的点

                $end_price_info = DB::table('price_setting') -> where([
                    'class_num' => $from_user_info ->class
                ]) -> first();
                // 应该返的比例
                $temp = $end_price_info -> price_point;
                //应该返的金额
                $end_price = round($temp * $order_info -> pay_price,2);


                //给他加上
                DB::table('wxuser') -> where([
                    'id' => $from_user_id,
                ]) -> increment('price', $end_price);


                $fromuserinfo = DB::table('wxuser') -> where([
                    'id' => $from_user_id,
                ]) -> first();
                //添加日志
                DB::table('price_log') -> insert([
                    'userid' => $from_user_id,
                    'price' => $end_price,
                    'type' => 3,
                    'created_at' => time(),
                    'updated_at' => time(),
                    'from_user_id' => $userinfo -> id,
                    'in_out' => 0 ,// 0收入1支出
                    'end_price' => $fromuserinfo -> price
                ]);

                if($env != 'local' && isset($fromuserinfo->openid)) {
                    //给他发返点模版消息
                    $res = $app->template_message->send([
                        'touser' => $fromuserinfo->openid,
                        'template_id' => 'RmayJqYux-RGgY5n_4T7mKuzIJyTkXGBvDLmcQdD4dE',
                        'url' => url('priceTable'),
                        'data' => [
                            'first' => '到账提醒',
                            'keyword1' => date('Y-m-d H:i'),
                            'keyword2' => '¥' . $end_price,
                            'keyword3' => $order_info->order_num,
                            'keyword4' => '好友' . $userinfo->nickname . '返点',
                            'remark' => '感谢您的使用'
                        ]
                    ]);
                }



            }



        }


        //2018-06-16 修改
        //  查看下 此订单 是不是所有包裹都发货了
        $is_all = DB::table('packages') -> where([
            'wuliu_num' => '',
            'order_id' => $package_info -> order_id,
            'flag' => 0
        ]) -> count();
        //dd($is_all);
        if($is_all == 0){
            //全部发货 标记已发货
            DB::table('order') -> where([
                'id' => $package_info -> order_id
            ]) -> update([
                'status' => 3
            ]);
        }else{
            //标记部分发货
            DB::table('order') -> where([
                'id' => $package_info -> order_id
            ]) -> update([
                'status' => 2
            ]);
        }
    }

    //对每个包裹发货
    public function sendPackage($vol,$order_info,$route){
        //计算成本 -- 如果余额充足 则允许发货
        if(!$order_info -> is_split){
            //拆单下单 不需要判定这些
            $is_sendOrder = $this -> checkPrice($vol,$order_info,$route);
            if(!$is_sendOrder){
                //不允许发货
                $apiRes['data'] = '余额不足';
                $apiRes['result'] = 'error';
                $apiRes['package_num'] = $vol -> package_num;
                return $apiRes;
            }
        }






        //开始区分 是融通接口 还是 中通接口
        if(in_array($route,[4,5,6,7])){
            //融通接口

            //查看邮编
            $zip_info = DB::table('zips')
                -> where('city','like','%'.mb_substr(trim($vol -> province),0,2,'utf-8').'%')
                -> where('province','like','%'.mb_substr(trim($vol -> city),0,2,'utf-8').'%')
                -> first();

            if(!empty($zip_info)){
                $zip = $zip_info -> zip_code;
            }else{
                $zip = $vol -> zip;

            }

            $items = $this -> getAllGoodsByPackage($vol);

            //计算去申报物品的总价 和 个数
            $count_goods = 0;
            $price_goods = 0;
            foreach($items as $value){
                $count_goods += $value['ProductCount'];
                $price_goods += round(floatval($value['Price']*$value['ProductCount']),2);
            }
            $price_goods = round($price_goods,2);



            //判断路线
            // 4 NN100 香港
            // 5 XS001 香港

            // 6 NN100 澳门
            // 7 XS001 澳门

            if(in_array($route,[5,7])){
                $author = base64_encode("XS001:X123456:1a0832ebcce6b5286e9d6e5d2177edac");
            }elseif(in_array($route,[4,6])){
                $author = base64_encode("NN100:N123456:672890e7bc70a3a4b8e067caa3c1305c");
            }
            $post_send_data_temp = [];




            if(in_array($route,[6,7])){
                //澳门e特快
                $ShopId = 11400;
                $CountryCode = 'CA';
                $ExpressId = 36;
                //澳门E特快口岸必填，“进口”、“转运”、“本地”
                $post_send_data_temp = [
                    'goodsType' => '转运'
                ];

            }elseif(in_array($route,[4,5])){
                //香港e特快
                $ShopId = 26;
                $CountryCode = 'HK';
                $ExpressId = 14;
            }


            $post_send_data = [
                'OrderCode' => $vol -> package_num,
                'Sender' => 'MS. CHAN',
                'SendAddress' => 'LOT89, NEAR RCP,SHA LING, MAN KAM TO ROAD, SHEUNG SHUI，NEW TERRITORIES',
                'SendPhone' => '54848815',
                'Receiver' => $vol -> name,
                //'ReceiveCardNo' => '111111141',
                'ReceivePhone' => $vol -> tel,
                'ReceiveAddress' => $vol -> address,
                'ReceiveProvince' => $vol -> province,
                'ReceiveCity' => $vol -> city,
                'ReceiveZip' => $zip,
                //口岸
                'ShopId' => $ShopId,
                'CountryCode' => $CountryCode,
                //快递公司 Id
                'ExpressId' => $ExpressId,
                //毛重
                'BagWeight' => $vol -> weight,
                //内件数量
                'BagCount' => $count_goods,
                'IsTax' => true,
                'TotalPrice' => $price_goods,
                'Items' => $items
            ];

            $post_send_data = array_merge($post_send_data_temp,$post_send_data);




            $post_data = $this -> post('http://webapi.rongtong-group.com/api/order/PostOrder',$post_send_data,[
                'Content-Type:application/json',
                "Authorization: Basic ".$author
            ]);
            Log::info(json_encode($post_send_data));

            Log::info($post_data);
            $json_decode = json_decode($post_data,true);

            if($json_decode['Code'] == '200'){
                $apiRes['data'] = $json_decode['Data'];
                $apiRes['result'] = 'success';
                $apiRes['package_num'] = $vol -> package_num;
                //来  开始发货
                $this -> sendOrder($vol,$order_info,$route,$json_decode['Data']);
                //发货完之后 加入队列 去请求pdf
                dispatch(new ProcessPodcast(Package::find($vol -> id)));
            }else{
                $apiRes['data'] = $json_decode['ErrorMsg'];
                $apiRes['result'] = 'error';
                $apiRes['package_num'] = $vol -> package_num;
            }
            return $apiRes;
        }elseif(in_array($route,[8,9])){
            Log::info('中通接口开始');
            //中通接口
            if($route == 8){
                $index_number = 2;
            }elseif($route == 9){
                $index_number = 3;
            }
            $api_res = $this -> eExpress_shipment_import_label($vol,$index_number);
            Log::info('中通接口结束');
            /*
               return [
                    'code' => 'success',
                    'msg' => $vals[$msg_index]['value']
                ];
            */
            $api_res = json_decode($api_res,true);

            Log::info(print_r($api_res,true));

            if($api_res['code'] == 'success'){
                $apiRes['data'] = $api_res['msg'];
                $apiRes['result'] = 'success';
                $apiRes['package_num'] = $vol -> package_num;


                //把 pdf 文件 更新到我们表中
                DB::table('packages') -> where([
                    'id' => $vol -> id
                ]) -> update([
                    'updated_at' => time(),
                    'pdf' => $api_res['pdf']
                ]);

                //开始调用发货
                $this -> sendOrder($vol,$order_info,$route,$api_res['msg']);

            }else{
                $apiRes['data'] = $api_res['msg'];
                $apiRes['result'] = 'error';
                $apiRes['package_num'] = $vol -> package_num;
            }
            return $apiRes;

        }





    }


    public  function post($url, $post_data = '',$headers = []){
        header("Content-type:text/html;charset=utf-8");
        $ch = curl_init();
        curl_setopt ($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS,json_encode($post_data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            return  curl_error($ch);
        }
        curl_close($ch);
        return $result;
    }

    public function get($url,$headers = []){
        header("Content-type:text/html;charset=utf-8");
        $ch = curl_init();
        curl_setopt ($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT,20);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            return  curl_error($ch);
        }
        curl_close($ch);
        return $result;
    }

    /**
     * @param $code
     */
    public function deleteApiData($code,$route_id){
        $get_data = [];
        if(in_array($route_id,[4,6])){
            //融通接口
            $get_data = $this -> getData('http://webapi.rongtong-group.com/api/order/DeleteOrderCode?Code='.$code,[
                'Content-Type:application/json',
                "Authorization: Basic ".base64_encode("NN100:N123456:672890e7bc70a3a4b8e067caa3c1305c")
            ]);
        }elseif(in_array($route_id,[5,7])){
            //融通熊谢
            $get_data = $this -> getData('http://webapi.rongtong-group.com/api/order/DeleteOrderCode?Code='.$code,[
                'Content-Type:application/json',
                "Authorization: Basic ".base64_encode("XS001:X123456:1a0832ebcce6b5286e9d6e5d2177edac")
            ]);
        }elseif(in_array($route_id,[8])){
            $get_data = $this -> eExpress_shipment_cancel($code,2);
        }

        return $get_data;

    }

    public function getData($url,$headers = []){
        //header("Content-type:text/html;charset=utf-8");
        $ch = curl_init();
        curl_setopt ($ch, CURLOPT_URL, $url);
        //curl_setopt ($ch, CURLOPT_POST, 1);

        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $file_contents = curl_exec($ch);
        if (curl_errno($ch)) {
            return  curl_error($ch);
        }
        curl_close($ch);
        return $file_contents;
    }

    //生成pdf
    //type = 1 请求香港生成Pdf 接口
    //type = 2 请求澳门生成PDf接口
    public function makePdf($temp_str,$route_id){
        header("Content-type:text/html;charset=utf-8");
        $paf_arr = [];
        $expressNo = trim($temp_str,',');
        //Log::info($expressNo);

        //通过route_id 判断  请求哪个生成Pdf 的接口
        if(in_array($route_id,[4])){
            //HK(NN100)
            $get_data = $this -> getData('http://webapi.rongtong-group.com/api/order/GetETKInfo?expressNo='.$expressNo,[
                'Content-Type:application/json',
                "Authorization: Basic ".base64_encode("NN100:N123456:672890e7bc70a3a4b8e067caa3c1305c")
            ]);
        }elseif(in_array($route_id,[5])){
            //HK(XS001)
            $get_data = $this -> getData('http://webapi.rongtong-group.com/api/order/GetETKInfo?expressNo='.$expressNo,[
                'Content-Type:application/json',
                "Authorization: Basic ".base64_encode("XS001:X123456:1a0832ebcce6b5286e9d6e5d2177edac")
            ]);
        }elseif(in_array($route_id,[6])){
            //MO(NN100)
            $get_data = $this -> getData('http://webapi.rongtong-group.com/api/order/GetOMETKInfo?expressNo='.$expressNo,[
                'Content-Type:application/json',
                "Authorization: Basic ".base64_encode("NN100:N123456:672890e7bc70a3a4b8e067caa3c1305c")
            ]);
        }elseif(in_array($route_id,[7])){
            //MO(XS001)
            $get_data = $this -> getData('http://webapi.rongtong-group.com/api/order/GetOMETKInfo?expressNo='.$expressNo,[
                'Content-Type:application/json',
                "Authorization: Basic ".base64_encode("XS001:X123456:1a0832ebcce6b5286e9d6e5d2177edac")
            ]);
        }elseif(in_array($route_id,[8])){
            exit;
            //已经在上一步 取到了

            //MX01 请求邮局接口
            //$get_data = $this -> eExpress_makeAllPdf($expressNo);
        }
        Log::info($get_data);
        return $get_data;


        /*
        if($type == 1){
            //香港pdf
            $get_data = $this -> getData('http://webapi.rongtong-group.com/api/order/GetETKInfo?expressNo='.$expressNo,[
                'Content-Type:application/json',
                "Authorization: Basic ".base64_encode("NN100:N123456:672890e7bc70a3a4b8e067caa3c1305c")
            ]);
        }else{


            //澳门pdf
            $get_data = $this -> getData('http://webapi.rongtong-group.com/api/order/GetOMETKInfo?expressNo='.$expressNo,[
                'Content-Type:application/json',
                "Authorization: Basic ".base64_encode("NN100:N123456:672890e7bc70a3a4b8e067caa3c1305c")
            ]);
        }
        */



        if(in_array($route_id,[4,5,6,7])){
            $get_data = json_decode($get_data,true);


            //香港
            if(in_array($route_id,[4,5])){
                foreach($get_data as $vo){
                    if(isset($vo['Data']['PDF'])){
                        $pdf = $vo['Data']['PDF'];
                        $paf_arr[] = $pdf;
                        Log::info($pdf);
                    }
                }
            }elseif(in_array($route_id,[6,7])){
                if($get_data['Code'] != 200){
                    return 'error';
                }

                foreach($get_data['Data'] as $vo){
                    if(isset($vo['Data']['PDF'])){
                        $pdf = $vo['Data']['PDF'];
                        $paf_arr[] = $pdf;
                        Log::info($pdf);
                    }
                }
            }
        }

        if(in_array($route_id,[8])){

        }



        /*
        if($type == 1){
            foreach($get_data as $vo){
                if(isset($vo['Data']['PDF'])){
                    $pdf = $vo['Data']['PDF'];
                    $paf_arr[] = $pdf;
                    Log::info($pdf);
                }
            }
        }else{

            if($get_data['Code'] != 200){
                return 'error';
            }

            foreach($get_data['Data'] as $vo){
                if(isset($vo['Data']['PDF'])){
                    $pdf = $vo['Data']['PDF'];
                    $paf_arr[] = $pdf;
                    Log::info($pdf);
                }
            }
        }
        */


        //请求Java接口  获取生成pdf文件名称

        //curl -d "data=http://files.rongtong-group.com/B2CFile/OrderFile/ETK/PDF/2018/08/21/EK419924388HK.pdf,http://files.rongtong-group.com/B2CFile/OrderFile/ETK/PDF/2018/08/21/EK419924391HK.pdf,http://files.rongtong-group.com/B2CFile/OrderFile/ETK/PDF/2018/08/21/EK419924405HK.pdf" "127.0.0.1:8080/springMvc/merge"



        $post_data = $this -> post2('localhost:8080/pdf/merge',[
            'data' => implode(',',$paf_arr)
        ]);

        Log::info("java back:".$post_data);
        if($post_data){
            return $post_data;
        }else{
            return false;
        }

    }

    /**
     * @param $route_id
     * @param $get_data  上一步 makePdf 生成的
     * @return bool|mixed|string
     */
    public function javaMergePdf($route_id,$get_data,$pdf_files=[],$is_merge = true){
        $paf_arr = [];
        if(in_array($route_id,[4,5,6,7]) && $get_data){
            $get_data = json_decode($get_data,true);


            //香港
            if(in_array($route_id,[4,5])){
                foreach($get_data as $vo){
                    if(isset($vo['Data']['PDF'])){
                        $pdf = $vo['Data']['PDF'];
                        $wuliu_num = $vo['Data']['ExpressNo'];
                        $paf_arr[] = $pdf;
                        //文件保存在本地  然后 更新pdf字段
                        $this -> savePdfFile($pdf,$wuliu_num);
                        //Log::info($pdf);
                    }
                }
            }elseif(in_array($route_id,[6,7])){
                if($get_data['Code'] != 200){
                    return 'error';
                }

                foreach($get_data['Data'] as $vo){
                    if(isset($vo['Data']['PDF'])){
                        $pdf = $vo['Data']['PDF'];
                        $wuliu_num = $vo['Data']['ExpressNo'];
                        $paf_arr[] = $pdf;
                        //Log::info($pdf);
                        //文件保存在本地  然后 更新pdf字段
                        $this -> savePdfFile($pdf,$wuliu_num);


                    }
                }
            }
        }

        if(count($pdf_files)){
            foreach($pdf_files as $vo){
                $paf_arr[] = $vo;
            }
        }

        //var_dump($paf_arr);exit;


        //队列 跟 后台生成Pdf 公用此方法 队列不需要合并
        if($is_merge){
            $post_data = $this -> post2('localhost:8080/pdf/merge',[
                'data' => implode(',',$paf_arr)
            ]);

            Log::info("java back:".$post_data);
            if($post_data){
                return $post_data;
            }else{
                return false;
            }
        }

    }


    public function savePdfFile($url,$wuliu_num){
        $pdf_file = uniqid().rand(100,999).'.pdf';
        file_put_contents(public_path('temp_pdf/'.$pdf_file),file_get_contents($url));
        DB::table('packages') -> where([
            'wuliu_num' => $wuliu_num
        ]) -> update([
            'pdf' => $pdf_file
        ]);
    }


    //邮局 直接带pdf 文件数组 请求java
    public function makeYoujuPdf($pdf_arr){
        $post_data = $this -> post2('localhost:8080/pdf/merge',[
            'data' => implode(',',$pdf_arr)
        ]);

        Log::info("java back:".$post_data);
        if($post_data){
            return $post_data;
        }else{
            return false;
        }
    }

    public  function post2($url, $post_data = '',$headers = []){
        //header("Content-type:text/html;charset=utf-8");
        $ch = curl_init();
        curl_setopt ($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS,'data='.$post_data['data']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            return  curl_error($ch);
        }
        curl_close($ch);
        return $result;
    }


    //删除包裹
    public function deletePackage($package_info,$order_info){
        //查到此人具体信息
        $userinfo = DB::table('wxuser') -> where([
            'id' => $order_info -> userid
        ]) -> first();
        //会员等级优惠比例
        //$config_price = config('admin.class_price');
        //该会员优惠的比例
        //$parameter_user = $config_price[$userinfo ->class];
        $temp_price_setting = DB::table('price_setting') -> where([
            'class_num' => $userinfo ->class
        ]) -> first();

        //该会员优惠的比例
        $parameter_user = $temp_price_setting -> bili;


        $price_res = floatval($order_info -> price - $package_info -> price);

        //订单总价里边 减去
        DB::table('order') -> where([
            'id' => $package_info -> order_id
        ]) -> update([
            'price' => $price_res,
            'cost' => $order_info -> cost - $package_info -> cost,
            //订单的包裹数量减1
            'count_package' => $order_info -> count_package - 1,
            'count_address' => ceil(($order_info -> count_package - 1)/3),
            //修改应付的金额 优惠金额
            'pay_price' => $price_res * $parameter_user,
            'minus_price' => $price_res * (1-$parameter_user),

        ]);


        DB::table('packages') -> where([
            'id' => $package_info -> id
        ]) -> update([
            'flag' => 1
        ]);


        //判断订单里有几个没发货的 如果全没发货 则订单状态改为 待发货1  否则 为部分发货2
        $count_send = DB::table('packages') -> where([
            'order_id' => $package_info -> order_id,
            'flag' => 0,
        ]) -> where('wuliu_num','!=','') -> count();

        $count_package = DB::table('packages') -> where([
            'order_id' => $package_info -> order_id,
            'flag' => 0,
        ]) -> count();


        //判断下 如果这个包裹 是通过物流单号生成的 则把这个重量退回去
        if($order_info -> repertory_id){

            //查找到货物流信息
            $repertory_info = DB::table('repertory') -> where([
                'id' => $order_info -> repertory_id
            ]) -> first();

            //把 order_json 减去
            $order_json = json_decode($repertory_info -> order_json,true);
            foreach($order_json as $k => $vo){
                if($k == $package_info -> repertory_weight){
                    if(isset($order_json[$k])){
                        $order_json[$k] --;
                    }else{
                        $order_json[$k] = 0;
                    }
                }
            }



            //把 over_json 增加
            $over_json = json_decode($repertory_info -> over_json,true);
            foreach($over_json as $k => $vo){
                if($k == $package_info -> repertory_weight){
                    if(isset($over_json[$k])){
                        $over_json[$k] ++ ;
                    }else{
                        $over_json[$k]  = 1 ;
                    }
                }
            }

            //回写到repertory 里
            DB::table('repertory') -> where([
                'id' => $order_info -> repertory_id
            ]) -> update([
                'over_json' => json_encode($over_json),
                'order_json' => json_encode($order_json),
            ]);
        }

        if($package_info -> split_id){
            //如果是从拆单里下单的 恢复此拆单
            SplitPackage::where('id',$package_info -> split_id) -> update([
                'is_order' => 0
            ]);
        }



        if($count_send){

            if($count_package == $count_send){
                //全部发货
                DB::table('order') -> where([
                    'id' => $package_info -> order_id,
                    'flag' => 0
                ]) -> update([
                    'status' => 3  //全部发货
                ]);
            }else{
                //部分发货
                DB::table('order') -> where([
                    'id' => $package_info -> order_id,
                    'flag' => 0
                ]) -> update([
                    'status' => 2  //部分发货
                ]);
            }


        }else{
            DB::table('order') -> where([
                'id' => $package_info -> order_id,
                'flag' => 0
            ]) -> update([
                'status' => 1 //待发货
            ]);
        }

        /*

        //判断下 此订单下 还有没有包裹  如果没有 把订单也删除掉
        $packages = DB::table('packages') -> where([
            'order_id' => $package_info -> order_id,
            'flag' => 0,
        ]) -> first();
        if(!$packages){
            //将订单也删除掉
            DB::table('order') -> where([
                'id' => $package_info -> order_id,
            ]) -> update([
                'flag' => 1
            ]);
        }

        */


    }


    //删除订单
    public function deleteOrder($order_info){
        $area_name = DB::table('area_name') -> where([
            'id' => $order_info -> from_area
        ]) -> first();

        $packages = DB::table('packages') -> where([
            'order_id' => $order_info -> id
        ]) -> orderBy('sequeue_num','desc') -> first();


        //小程序下单  取消小程序下单状态
        if($order_info -> mp_package_id){
            DB::table('mp_temp_package_number')
                -> where([
                    'id' => $order_info -> mp_package_id
                ]) -> update([
                    'order_id' => 0
                ]);
        }

        //如果是 异常件删除 则 需要把异常件的order干掉
        if($order_info -> warning_package_id){
            DB::table('warning_package')
                -> where([
                    'id' => $order_info -> warning_package_id
                ]) -> update([
                    'order_id' => 0
                ]);
        }


        if($packages && $packages -> sequeue_num){
            //查询pcakages的sequeue 是不是最大的
            $is_big = DB::table('sequeue') -> where([
                'date' => $order_info -> date_time,
                'order_num' => $packages -> sequeue_num,
                'from_area' => $area_name -> area_num,
                'type' => 1
            ]) -> first();
            if($is_big){
                //如果是最大的 则退回去
                //查下有多少包裹
                $count_packages = DB::table('packages') -> where([
                    'order_id' => $order_info -> id
                ]) -> count();
                //减去
                DB::table('sequeue') -> where([
                    'date' => $order_info -> date_time,
                    'from_area' => $area_name -> area_num,
                    'type' => 1
                ]) -> decrement('order_num',$count_packages);
            }
        }


        //标记删除订单
        DB::table('order') -> where([
            'id' => $order_info -> id
        ]) -> update([
            'flag' => 1
        ]);

        if($order_info -> is_split){
            //如果订单是拆单后下的 则退回拆单
            $package_infos =  DB::table('packages') -> where([
                'order_id' => $order_info -> id,
                'flag' => 0
            ]) -> get();

            foreach($package_infos as $vo){
                SplitPackage::where('id',$vo -> split_id) -> update([
                    'is_order' => 0
                ]);
            }


        }


        //删除订单时候 如果此单是从物流单下的 则还回去
        if($order_info -> repertory_id){
            $package_infos =  DB::table('packages') -> where([
                'order_id' => $order_info -> id,
                'flag' => 0
            ]) -> get();
            $package_weight = [];
            foreach($package_infos as $vo){
                //收集包裹重量
                $package_weight[] = $vo -> repertory_weight;
            }
            //查找到货物流信息
            $repertory_info = DB::table('repertory') -> where([
                'id' => $order_info -> repertory_id
            ]) -> first();

            //把 order_json 减去
            $order_json = json_decode($repertory_info -> order_json,true);
            foreach($order_json as $k => $vo){
                foreach($package_weight as $value){
                    if($k == $value){
                        if(isset($order_json[$k])){
                            $order_json[$k] --;
                        }else{
                            $order_json[$k] = 0;
                        }

                    }
                }
             }



            //把 over_json 增加
            $over_json = json_decode($repertory_info -> over_json,true);
            foreach($over_json as $k => $vo){
                foreach($package_weight as $value){
                    if($k == $value){
                        if(isset($over_json[$k])){
                            $over_json[$k] ++ ;
                        }else{
                            $over_json[$k]  = 1 ;
                        }

                    }
                }
            }

            //回写到repertory 里
            DB::table('repertory') -> where([
                'id' => $order_info -> repertory_id
            ]) -> update([
                'over_json' => json_encode($over_json),
                'order_json' => json_encode($order_json),
            ]);

        }


        DB::table('packages') -> where([
            'order_id' => $order_info -> id
        ]) -> update([
            'flag' => 1
        ]);

        //删除小程序订单中这些包裹
        $packages = DB::table('packages') -> where([
            'order_id' => $order_info -> id,
        ]) -> get();

        foreach($packages as $vo){
            DB::table('mp_temp_package_number')
            -> where([
                'package_id' =>  $vo -> id
            ]) -> update([
                'flag' => 1
            ]);
        }



    }


    //更新包裹重量
    public function updateWeight($package_info,$order_info,$weight){
        $from_area = $order_info -> from_area;
        $cost_new = 0;
        $area_cost_new = 0;
        //之前的区域成本
        $area_cost_pre = round(floatval($package_info -> area_price),2);
        if($package_info -> cost && $package_info -> route_id){
            //新的成本
            $cost_new = $this -> getPriceInfoByWeight($weight,$package_info -> route_id);
            //新的区域成本
            $area_cost_new = $this -> getAreaPriceTemp($order_info -> from_area,$weight);
            if(!$area_cost_new){
                return false;
            }
        }

        //新的价格
        $price_new = round(floatval($this -> getPriceTempByWeightUserid($order_info -> userid,$weight)),2);

        //如果有物流编号 则请求api
        //$is_api = false;
        if($package_info -> wuliu_num){

            $api_res = $this -> updateWeightApi([
                'ExpressNo' => trim($package_info -> wuliu_num),
                'BagWeight' => $weight
            ]);
            Log::info($api_res);
            $json_decode = json_decode($api_res,true);
            if($json_decode['Code'] == '200'){
                //更新成功
                //给他加余额 或者补扣
                $model_area = new AreaName();
                $abs_price = abs($area_cost_new - $area_cost_pre);
                if($area_cost_new > $area_cost_pre){
                    //补扣
                    $res = $model_area -> updatePackage($from_area,$abs_price,2,$package_info -> package_num);
                    if($res == 'no_price'){
                        return [
                            'res' => 'error',
                            'data' => '区域id为'.$from_area.' 已欠款，已扣款为负数'
                        ];
                    }
                }elseif($area_cost_new < $area_cost_pre){
                    //返还
                    $model_area -> updatePackage($from_area,$abs_price,1,$package_info -> package_num);
                }

            }else{
                //错误返回errormsg
                return [
                    'res' => 'error',
                    'data' => $json_decode['ErrorMsg']
                ];
            }
        }

        $package_id = $package_info -> id;

        DB::table('packages') -> where([
            'id' => $package_id
        ]) -> update([
            'updated_at' => time(),
            'cost' => $cost_new,
            'area_price' => $area_cost_new,
            'price' => $price_new,
            'weight' => $weight,
            'pdf' => ''
        ]);

        //算下包裹价格差
        $price_diff = $price_new - $package_info -> price;
        $cost_diff = $cost_new - $package_info -> cost;
        //新的区域价格 减去 旧价格
        $area_price_ext = $area_cost_new - $package_info -> area_price;



        //重新计算订单的支付价格
        $userinfo = DB::table('wxuser') -> where([
            'id' => $order_info -> userid
        ]) -> first();
        //会员等级优惠比例
        //$config_price = config('admin.class_price');
        $temp_price_setting = DB::table('price_setting') -> where([
            'class_num' => $userinfo ->class
        ]) -> first();

        //该会员优惠的比例
        //$parameter_user = $config_price[$userinfo ->class];
        $parameter_user = $temp_price_setting -> bili;

        DB::table('order') -> where([
            'id' => $package_info -> order_id
        ]) -> update([
            'price' => $order_info -> price + $price_diff,
            'cost' => $order_info -> cost + $cost_diff,
            'pay_price' => round(($order_info -> price + $price_diff) * $parameter_user,2)
        ]);

        return [
            'res' => 'success'
        ];

    }

    //更新重量接口
    protected function updateWeightApi($post_send_data){
        $post_data = $this -> post('http://webapi.rongtong-group.com/api/order/UpdateWeight',$post_send_data,[
            'Content-Type:application/json',
            "Authorization: Basic ".base64_encode("NN100:N123456:672890e7bc70a3a4b8e067caa3c1305c")
        ]);
        return $post_data;
    }


    //获取香港物流信息接口
    public  function getTrackingList($code){
        $get_data = $this -> get('http://webapi.rongtong-group.com/api/order/GetHKLogisticsInfo?code='.$code,[
            'Content-Type:application/json',
            "Authorization: Basic ".base64_encode("NN100:N123456:672890e7bc70a3a4b8e067caa3c1305c")
        ]);
        return $get_data;
    }

    //http://open.16tong.cn/api/order/GetLogisticsInfo?code=xxxx

    //获取物流信息接口
    public function getWuLiuInfo($code){
        $get_data = $this -> get('http://webapi.rongtong-group.com/api/order/GetLogisticsInfo?code='.$code,[
            'Content-Type:application/json',
            "Authorization: Basic ".base64_encode("NN100:N123456:672890e7bc70a3a4b8e067caa3c1305c")
        ]);
        return $get_data;
    }



    //登录 获取token
    public function sendXmlGetToken($index_number = 1){
        $setting_config = config('admin.api_number');
        $loginID = $setting_config[$index_number]['loginID'];
        $pwd = $setting_config[$index_number]['pwd'];

        $xml_post_string = '<?xml version="1.0" encoding="utf-8"?>
                            <soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
                              <soap:Body>
                                 <eExpress_Login xmlns="http://linexsolutions.com/">
                                      <loginID>'.$loginID.'</loginID>
                                      <pwd>'.$pwd.'</pwd>
                                    </eExpress_Login>
                              </soap:Body>
                            </soap:Envelope>';

        $headers = array(
            "Content-type: text/xml;charset=\"utf-8\"",
            "Accept: text/xml",
            "Cache-Control: no-cache",
            "Pragma: no-cache",
            "SOAPAction: http://linexsolutions.com/eExpress_Login",
            "Content-length: ".strlen($xml_post_string),
        );

        $url = 'http://eexpress-ws.linexsolutions.com/eExpressClientWebService.asmx?op=eExpress_Login';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_post_string); // the SOAP request
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // converting
        $response = curl_exec($ch);
        if(curl_error($ch)){
            return curl_error($ch);
        }
        curl_close($ch);

        $p = xml_parser_create();
        xml_parse_into_struct($p, $response, $vals, $index);
        xml_parser_free($p);

        //dump($index);

        //dd($vals);
        $request_index = $index['RESULT'][0];
        $msg_index = $index['MSG'][0];
        if($vals[$request_index]['value'] == 'T'){
            return $vals[$msg_index]['value'];
        }else{
            return false;
        }

    }

    //获取过机重量
    protected function sendXmlGetWeight($token,$wuliu_num){
        $xml_post_string = '<?xml version="1.0" encoding="utf-8"?>
                            <soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
                              <soap:Body>
                                <eExpress_shipment_billedlist xmlns="http://linexsolutions.com/">
                                  <userToken>'.$token.'</userToken>
                                  <shipmentNumber>'.$wuliu_num.'</shipmentNumber>
                                </eExpress_shipment_billedlist>
                              </soap:Body>
                            </soap:Envelope>';

        $headers = array(
            "Content-type: text/xml;charset=\"utf-8\"",
            "Accept: text/xml",
            "Cache-Control: no-cache",
            "Pragma: no-cache",
            "SOAPAction: http://linexsolutions.com/eExpress_shipment_billedlist",
            "Content-length: ".strlen($xml_post_string),
        );

        $url = 'http://eexpress-ws.linexsolutions.com/eExpressClientWebService.asmx?op=eExpress_shipment_billedlist';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_post_string); // the SOAP request
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // converting
        $response = curl_exec($ch);
        if(curl_error($ch)){
            return curl_error($ch);
        }
        curl_close($ch);

        dump($response);

        $p = xml_parser_create();
        xml_parse_into_struct($p, $response, $vals, $index);
        xml_parser_free($p);
        dump($index);
        dd($vals);
        return $vals;
    }

    //包裹清关状态查询
    public  function sendXmlGetTax($code,$index_number = 1){
        $setting_config = config('admin.api_number');
        $loginID = $setting_config[$index_number]['loginID'];
        $pwd = $setting_config[$index_number]['pwd'];

        $xml_post_string = '<?xml version="1.0" encoding="utf-8"?>
                            <soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
                              <soap:Body>
                                <eExpress_GetTax xmlns="http://linexsolutions.com/"> 
                                    <input>
                                        <LoginId>'.$loginID.'</LoginId>
                                        <Password>'.$pwd.'</Password>
                                        <ShipmentNumber>'.$code.'</ShipmentNumber>
                                      </input>
                                </eExpress_GetTax >
                              </soap:Body>
                            </soap:Envelope>';

        $headers = array(
            "Content-type: text/xml;charset=\"utf-8\"",
            "Accept: text/xml",
            "Cache-Control: no-cache",
            "Pragma: no-cache",
            "SOAPAction: http://linexsolutions.com/eExpress_GetTax",
            "Content-length: ".strlen($xml_post_string),
        );

        $url = 'http://eexpress-ws.linexsolutions.com/eExpressClientWebservice.asmx?op=eExpress_GetTax';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_post_string); // the SOAP request
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // converting
        $response = curl_exec($ch);
        if(curl_error($ch)){
            return curl_error($ch);
        }
        curl_close($ch);
        //dump($response);

        $p = xml_parser_create();
        xml_parse_into_struct($p, $response, $vals, $index);
        xml_parser_free($p);
        
        //return $vals;
        $request_index = $index['REUSLT'][0];

        //请求结果
        if($vals[$request_index]['value'] == 'false'){
            return false;
        }else{
            $result_index = $index['TAXNAMEFORCN'][0];
            return $vals[$result_index]['value'];
        }
    }


    //查询包裹的状态 add by 20180923
    protected function getPackageStatus($code,$token){
        $xml_post_string = '<?xml version="1.0" encoding="utf-8"?>
                            <soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
                              <soap:Body>
                                <eExpress_shipment_tracking xmlns="http://linexsolutions.com/">
                                  <userToken>'.$token.'</userToken>
                                  <shipmentNumber>'.$code.'</shipmentNumber>
                                </eExpress_shipment_tracking>
                              </soap:Body>
                            </soap:Envelope>';

        $headers = array(
            "Content-type: text/xml;charset=\"utf-8\"",
            "Accept: text/xml",
            "Cache-Control: no-cache",
            "Pragma: no-cache",
            //"SOAPAction: http://linexsolutions.com/eExpress_shipment_tracking",
            "Content-length: ".strlen($xml_post_string),
        );

        $url = 'http://eexpress-ws.linexsolutions.com/eExpressClientWebService.asmx?op=eExpress_shipment_tracking';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_post_string); // the SOAP request
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // converting
        $response = curl_exec($ch);
        if(curl_error($ch)){
            return curl_error($ch);
        }
        curl_close($ch);
        dump($response);

        $p = xml_parser_create();
        xml_parse_into_struct($p, $response, $vals, $index);
        xml_parser_free($p);
        dump($index);
        dd($vals);
        return $vals;
    }



    //邮局接口
    //下单（中通）
    public function eExpress_shipment_import($package_info,$index_number = 1){
        $setting_config = config('admin.api_number');
        $loginID = $setting_config[$index_number]['loginID'];
        $pwd = $setting_config[$index_number]['pwd'];
        $token = $setting_config[$index_number]['token'];


        //查看邮编
        $zip_info = DB::table('zips')
            -> where('city','like','%'.mb_substr(trim($package_info -> province),0,2,'utf-8').'%')
            -> where('province','like','%'.mb_substr(trim($package_info -> city),0,2,'utf-8').'%')
            -> first();

        if(!empty($zip_info)){
            $zip = $zip_info -> zip_code;
        }else{
            $zip = $package_info -> zip;

        }

        $items = $this -> getAllGoodsByPackage($package_info);

        $detail_str = '';

        foreach($items as $vo){
            $detail_str .= '<awbDetail>
                              <itemDescription>'.$vo['ProductName'].'</itemDescription>
                              <hsCode>'.$vo['PostTaxNum'].'</hsCode>
                              <itemPrice>'.$vo['Price'].'</itemPrice>
                              <itemPieces>'.$vo['ProductCount'].'</itemPieces>
                              <itemWeight>'.$vo['TotalWeight'].'</itemWeight>
                            </awbDetail>';
        }


        //计算去申报物品的总价 和 个数
        $count_goods = 0;
        $price_goods = 0;
        $count_weight = 0;

        foreach($items as $value){
            $count_goods += $value['ProductCount'];
            $price_goods += $value['Price']*$value['ProductCount'];
            $count_weight += $value['TotalWeight'];
        }

        $xml_post_string = '<?xml version="1.0" encoding="utf-8"?>
                                <soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
                                  <soap:Body>
                                    <eExpress_shipment_import xmlns="http://tempuri.org/">
                                      <awb>
                                        <userToken>'.$token.'</userToken>
                                        <customerHawb>'.$package_info -> package_num.'</customerHawb> //客户自己的相关号码，如订单号
                                        <shipmentDate>'.date('Y-m-d').'</shipmentDate> //发货日期(格式yyyy-MM-dd)
                                        <rName>'.$package_info -> name.'</rName> //收件人姓名
                                        <rCountry>CN</rCountry> //收件人所属国家，默认值(CN)
                                        <rProvince>'.$package_info -> province.'</rProvince> //收件人所属省份
                                        <rAddress1>'.$package_info -> address.'</rAddress1> //收件人地址1
                                        <rAddress2></rAddress2> //收件人地址2
                                        <rCity>'.$package_info -> city.'</rCity> //收件人城市
                                        <rZip>'.$zip.'</rZip> //收件人邮编 
                                        <rTel>'.$package_info -> tel.'</rTel> //收件人电话，必须是11位手机号码
                                        <Pieces>'.$count_goods.'</Pieces> //货物件数
                                        <weight>'.$count_weight.'</weight> //货物重量，不超过10KG
                                        <dCurrency>CNY</dCurrency> //申报货币，(USD,CNY,HKD,EUR)
                                        <dValue>'.$price_goods.'</dValue> //申报价值
                                        <duty_paid>Y</duty_paid> //税金已付(Y/N，若不填写默认“N”)
                                        <airmail></airmail>
                                      </awb>
                                      <objAwbDetail>
                                        '.$detail_str.'
                                      </objAwbDetail>
                                    </eExpress_shipment_import>
                                  </soap:Body>
                                </soap:Envelope>';

        $headers = array(
            "Content-type: text/xml;charset=\"utf-8\"",
            "Accept: text/xml",
            "Cache-Control: no-cache",
            "Pragma: no-cache",
            "SOAPAction: http://tempuri.org/eExpress_shipment_import",
            "Content-length: ".strlen($xml_post_string),
        );

        $url = 'http://eexpress-ws.linexsolutions.com/eExpressForCustomerWebservice.asmx?op=eExpress_shipment_import';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_post_string); // the SOAP request
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // converting
        $response = curl_exec($ch);
        if(curl_error($ch)){
            return curl_error($ch);
        }
        curl_close($ch);

        $p = xml_parser_create();
        xml_parse_into_struct($p, $response, $vals, $index);
        xml_parser_free($p);

        Log::info(print_r($vals,true));


        $request_index = $index['RESULT'][0];

        if($vals[$request_index]['value'] == 'T'){
            //成功以后的返回值
            $msg_index = $index['SHIPMENT_NUMBER'][0];
            return json_encode([
                'code' => 'success',
                'msg' => $vals[$msg_index]['value']
            ]);
        }else{
            $msg_index = $index['MSG'][0];
            return json_encode([
                'code' => 'error',
                'msg' => $vals[$msg_index]['value']
            ]);
            //return false;
        }
    }



    //中通 批量接收单号 生成Pdf 文件 返回java处理 合并Pdf

    /**
     * @param $wuliu_numbers 物流单号 用逗号隔开
     */
    public function eExpress_makeAllPdf($wuliu_numbers){
        $number_arr = explode(',',$wuliu_numbers);
        $paf_arr = [];
        foreach($number_arr as $vo){
            $package_info = DB::table('packages') -> where([
                'flag' => 0,
                'wuliu_num' => trim($vo)
            ]) ->join('goods_paratemer', 'packages.goods_id', '=', 'goods_paratemer.id')
                ->select('packages.*', 'goods_paratemer.*','packages.id as id')-> first();

            $temp_pdf = $this -> eExpress_shipment_import_label($package_info,2);
            if(!$temp_pdf){
                return false;
            }



        }
    }

    //中通 生成pdf文件 + 下单
    public function eExpress_shipment_import_label($package_info,$index_number = 1){
        $setting_config = config('admin.api_number');
        $loginID = $setting_config[$index_number]['loginID'];
        $pwd = $setting_config[$index_number]['pwd'];
        $token = $setting_config[$index_number]['token'];


        //查看邮编
        $zip_info = DB::table('zips')
            -> where('city','like','%'.mb_substr(trim($package_info -> province),0,2,'utf-8').'%')
            -> where('province','like','%'.mb_substr(trim($package_info -> city),0,2,'utf-8').'%')
            -> first();

        if(!empty($zip_info)){
            $zip = $zip_info -> zip_code;
        }else{
            $zip = $package_info -> zip;
        }

        $items = $this -> getAllGoodsByPackage($package_info);

        $detail_str = '';

        foreach($items as $vo){
            $detail_str .= '<awbDetail>
                              <itemDescription>'.$vo['ProductName'].'</itemDescription>
                              <hsCode>'.$vo['PostTaxNum'].'</hsCode>
                              <itemPrice>'.$vo['Price'].'</itemPrice>
                              <itemPieces>'.$vo['ProductCount'].'</itemPieces>
                              <itemWeight>'.$vo['TotalWeight'].'</itemWeight>
                            </awbDetail>';
        }


        //计算去申报物品的总价 和 个数
        $count_goods = 0;
        $price_goods = 0;
        $count_weight = $package_info -> weight;

        foreach($items as $value){
            $count_goods += $value['ProductCount'];
            $price_goods += $value['Price']*$value['ProductCount'];
            //$count_weight += $value['TotalWeight']*$value['ProductCount'];
        }

        $xml_post_string = '<?xml version="1.0" encoding="utf-8"?>
                                <soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
                                  <soap:Body>
                                    <eExpress_shipment_import_label xmlns="http://tempuri.org/">
                                      <awb>
                                        <userToken>'.$token.'</userToken>
                                        <customerHawb>'.$package_info -> package_num.'</customerHawb> //客户自己的相关号码，如订单号
                                        <shipmentDate>'.date('Y-m-d').'</shipmentDate> //发货日期(格式yyyy-MM-dd)
                                        <rName>'.$package_info -> name.'</rName> //收件人姓名
                                        <rCountry>CN</rCountry> //收件人所属国家，默认值(CN)
                                        <rProvince>'.$package_info -> province.'</rProvince> //收件人所属省份
                                        <rAddress1>'.$package_info -> address.'</rAddress1> //收件人地址1
                                        <rAddress2></rAddress2> //收件人地址2
                                        <rCity>'.$package_info -> city.'</rCity> //收件人城市
                                        <rZip>'.$zip.'</rZip> //收件人邮编 
                                        <rTel>'.$package_info -> tel.'</rTel> //收件人电话，必须是11位手机号码
                                        <Pieces>'.$count_goods.'</Pieces> //货物件数
                                        <weight>'.$count_weight.'</weight> //货物重量，不超过10KG
                                        <dCurrency>CNY</dCurrency> //申报货币，(USD,CNY,HKD,EUR)
                                        <dValue>'.$price_goods.'</dValue> //申报价值
                                        <duty_paid>Y</duty_paid> //税金已付(Y/N，若不填写默认“N”)
                                        <airmail></airmail>
                                      </awb>
                                      <objAwbDetail>
                                        '.$detail_str.'
                                      </objAwbDetail>
                                    </eExpress_shipment_import_label>
                                  </soap:Body>
                                </soap:Envelope>';

        $headers = array(
            "Content-type: text/xml;charset=\"utf-8\"",
            "Accept: text/xml",
            "Cache-Control: no-cache",
            "Pragma: no-cache",
            "SOAPAction: http://tempuri.org/eExpress_shipment_import_label",
            "Content-length: ".strlen($xml_post_string),
        );

        $url = 'http://eexpress-ws.linexsolutions.com/eExpressForCustomerWebservice.asmx?op=eExpress_shipment_import_label';
        Log::info($url);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_post_string); // the SOAP request
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // converting
        $response = curl_exec($ch);
        if(curl_error($ch)){
            return curl_error($ch);
        }
        curl_close($ch);

        $p = xml_parser_create();
        xml_parse_into_struct($p, $response, $vals, $index);
        xml_parser_free($p);


        //file_put_contents(storage_path('logs/tttttt.pdf'),base64_decode($vals[8]['value']));
        //dump($index);
        //dump($vals);
        //Log::info(json_encode($index));
        //Log::info(json_encode($vals));


        $request_index = $index['RESULT'][0];

        if($vals[$request_index]['value'] == 'true'){
            //成功以后的返回值
            $pdf_index = $index['SHIPMENTLABEL'][0];

            //单号
            $number_index = $index['SHIPMENTNUMBER'][0];
            //物流单号
            $wuliu_num = $vals[$number_index]['value'];

            //Log::info($vals[$pdf_index]['value']);
            //dump($msg_index);
            //pdf base64 加密数据
            $base64_decode_data = base64_decode($vals[$pdf_index]['value']);

            //dd($base64_decode_data);

            $encode = mb_detect_encoding($base64_decode_data, array("ASCII",'UTF-8',"GB2312","GBK",'BIG5'));
            //Log::info('1111111');
            //Log::info(print_r($encode,true));

            //dump($base64_decode_data);
            //保存pdf文件
            $pdf_file = uniqid().rand(100,999).'.pdf';
            $this_php_file_charset = 'CP936';
            //$p=iconv($this_php_file_charset,"utf-8//IGNORE",$base64_decode_data);
            //$p=iconv('utf-8','latin1//IGNORE', $base64_decode_data);


            file_put_contents(public_path('temp_pdf/'.$pdf_file),$this -> charsetToUTF8($base64_decode_data));
            //file_put_contents(public_path('temp_pdf/'.$pdf_file),$base64_decode_data);

            return json_encode([
                'pdf' => $pdf_file,
                'wuliu_num' => $wuliu_num,
                'code' => 'success',
                'msg' => $wuliu_num
            ]);

            //return $pdf_file;
        }else{
            $msg_index = $index['MESSAGE'][0];
            return json_encode([
                'code' => 'error',
                'msg' => $vals[$msg_index]['value']
            ]);

        }
    }

    function charsetToUTF8($mixed)
    {
        if (is_array($mixed)) {
            foreach ($mixed as $k => $v) {
                if (is_array($v)) {
                    $mixed[$k] = $this -> charsetToUTF8($v);
                } else {
                    $encode = mb_detect_encoding($v, array('ASCII', 'UTF-8', 'GB2312', 'GBK', 'BIG5'));
                    if ($encode == 'EUC-CN') {
                        $mixed[$k] = iconv('GBK', 'UTF-8', $v);
                    }
                }
            }
        } else {
            $encode = mb_detect_encoding($mixed, array('ASCII', 'UTF-8', 'GB2312', 'GBK', 'BIG5'));
            if ($encode == 'EUC-CN') {
                $mixed = iconv('GBK', 'UTF-8', $mixed);
            }
        }
        return $mixed;
    }


    //中通 取消单号
    public function eExpress_shipment_cancel($wuliu_num,$index_number = 1){
        $setting_config = config('admin.api_number');
        $loginID = $setting_config[$index_number]['loginID'];
        $pwd = $setting_config[$index_number]['pwd'];
        $token = $setting_config[$index_number]['token'];

        $xml_post_string = '<?xml version="1.0" encoding="utf-8"?>
                        <soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
                          <soap:Body>
                            <eExpress_shipment_cancel xmlns="http://tempuri.org/">
                              <shipmentNo>'.$wuliu_num.'</shipmentNo>
                              <userToken>'.$token.'</userToken>
                            </eExpress_shipment_cancel>
                          </soap:Body>
                        </soap:Envelope>';

        $headers = array(
            "Content-type: text/xml;charset=\"utf-8\"",
            "Accept: text/xml",
            "Cache-Control: no-cache",
            "Pragma: no-cache",
            "SOAPAction: http://tempuri.org/eExpress_shipment_cancel",
            "Content-length: ".strlen($xml_post_string),
        );

        $url = 'http://eexpress-ws.linexsolutions.com/eExpressForCustomerWebservice.asmx?op=eExpress_shipment_cancel';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_post_string); // the SOAP request
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // converting
        $response = curl_exec($ch);
        if(curl_error($ch)){
            return curl_error($ch);
        }
        curl_close($ch);

        $p = xml_parser_create();
        xml_parse_into_struct($p, $response, $vals, $index);
        xml_parser_free($p);


        //file_put_contents(storage_path('logs/tttttt.pdf'),base64_decode($vals[8]['value']));
        //dump($index);
        //dd($vals);


        $request_index = $index['RESULTTYPE'][0];

        if($vals[$request_index]['value'] == 'T'){
            return json_encode([
                'Code' => '200',
                'ErrorMsg' => '',
                'Data' => $wuliu_num
            ]);
            //return $pdf_file;
        }else{
            $msg_index = $index['MSG'][0];
            return json_encode([
                'Code' => '500',
                'ErrorMsg' => $vals[$msg_index]['value'],
                'Data' => ''
            ]);
        }
    }



    //通过过机重量 补扣或者返还
    public function changePriceByPassWeight($wuliu_num,$pass_weight){
        $package_info = DB::table('packages') -> where([
            'is_pass' => 0,
            'wuliu_num' => $wuliu_num,
            'flag' => 0
        ]) -> first();
        if($package_info){
            //如果有 则开始过机
            //计算重量差价

            //新的成本
            //$cost_new = $this -> getPriceInfoByWeight($pass_weight,$package_info -> route_id);
            //新的区域成本
            $from_area = $package_info -> from_area;
            $area_cost_new = $this -> getAreaPriceTemp($from_area,$pass_weight);
            if(!$area_cost_new){
                return '没有区域价格';
            }
            //之前的区域成本
            //用之前的申报重量计算
            $area_cost_pre = $this -> getAreaPriceTemp($from_area,$package_info -> weight);

            //$area_cost_pre = round(floatval($package_info -> area_price),2);

            //更新成功
            //给他加余额 或者补扣
            $model_area = new AreaName();
            $abs_price = abs($area_cost_new - $area_cost_pre);

            //更新过机重量 以及 已过机标记
            DB::table('packages') -> where([
                'id' => $package_info -> id
            ]) -> update([
                'is_pass' => 1,
                'pass_weight' => $pass_weight,
                'updated_at' => time()
            ]);

            if($area_cost_new > $area_cost_pre){
                //补扣
                $res = $model_area -> updateGuojiWeight($from_area,$abs_price,2,$package_info -> package_num,$package_info -> weight,$pass_weight);

                if($res == 'no_price'){
                    return '已补扣:'.$abs_price.',id为'.$from_area.'的区域 已欠款，已扣款为负数';
                }
                return '已补扣:'.$abs_price;
            }elseif($area_cost_new < $area_cost_pre){
                //返还
                $model_area -> updateGuojiWeight($from_area,$abs_price,1,$package_info -> package_num,$package_info -> weight,$pass_weight);
                return '已返还:'.$abs_price;
            }

            return '无变化';

        }else{
            //不存在 或 已经过机
            return '不存在 或 已经过机';
        }
    }


    //提单清关状态查询
    public function eExpress_GetTax($wuliu_num,$index_number = 1){
        $setting_config = config('admin.api_number');
        $loginID = $setting_config[$index_number]['loginID'];
        $pwd = $setting_config[$index_number]['pwd'];
        $token = $setting_config[$index_number]['token'];

        $xml_post_string = '<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <eExpress_shipment_tracking xmlns="http://tempuri.org/">
      <loginId>'.$loginID.'</loginId>
      <shipment_number>'.$wuliu_num.'</shipment_number>
    </eExpress_shipment_tracking>
  </soap:Body>
</soap:Envelope>';

        $headers = array(
            "Content-type: text/xml;charset=\"utf-8\"",
            "Accept: text/xml",
            "Cache-Control: no-cache",
            "Pragma: no-cache",
            "SOAPAction: http://tempuri.org/eExpress_shipment_tracking",
            "Content-length: ".strlen($xml_post_string),
        );

        $url = 'http://eexpress-ws.linexsolutions.com/eExpressWebService.asmx?op=eExpress_shipment_tracking';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_post_string); // the SOAP request
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // converting
        $response = curl_exec($ch);
        if(curl_error($ch)){
            return curl_error($ch);
        }
        curl_close($ch);

        $p = xml_parser_create();
        xml_parse_into_struct($p, $response, $vals, $index);
        xml_parser_free($p);

        //dump($response);
        //file_put_contents(storage_path('logs/tttttt.pdf'),base64_decode($vals[8]['value']));
        //dump($index);
        //dd($vals);


        $request_index = $index['RESULT'][0];

        if($vals[$request_index]['value'] == 'T'){
            //正确的返回值
            $return_index1 = $index['SHIPMENT_NUMBER'];
            $return_index2 = $index['CUSTOMER_NUMBER'];
            $return_index3 = $index['STATUS_CODE'];
            $return_index4 = $index['DESCRIPTION'];
            $return_index5 = $index['ENTRY_DATETIME'];


            $return_arr3 = [];
            $return_arr4 = [];
            $return_arr5 = [];

            foreach($return_index1 as $vo){
                $return_arr1[] = $vals[$vo]['value'];
            }
            foreach($return_index2 as $vo){
                $return_arr2[] = $vals[$vo]['value'];
            }
            foreach($return_index3 as $vo){
                $return_arr3[] = $vals[$vo]['value'];
            }
            foreach($return_index4 as $vo){
                $return_arr4[] = $vals[$vo]['value'];
            }
            foreach($return_index5 as $vo){
                $return_arr5[] = $vals[$vo]['value'];
            }
            /*
            dump($return_arr1);
            dump($return_arr2);
            dump($return_arr3);
            dump($return_arr4);
            dump($return_arr5);exit;
            */
            return json_encode([
                'status' =>$return_arr3,
                'detail' => $return_arr4,
                'date' => $return_arr5,
                'Code' => '200',
            ]);

        }else{
            $msg_index = $index['MESSAGE'][0];
            return json_encode([
                'Code' => '500',
                'ErrorMsg' => $vals[$msg_index]['value'],
                'Data' => ''
            ]);
        }
    }


    //TrackingMore API 创建多个运单号
    public function makeMoreNumber($arr){

        //改为请求邻里格服务器
        $url = 'http://m.tianluyangfa.com/public/api/trackingMore';

        $ch = curl_init();
        curl_setopt ($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS,'numbers='.json_encode($arr));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            return  curl_error($ch);
        }
        curl_close($ch);
        return $result;


        /*
        $url = 'https://api.trackingmore.com/v2/trackings/batch';
        $res = $this -> post($url,$arr,[
            'Content-Type: application/json',
            'Trackingmore-Api-Key:015dbffa-15ef-4f5a-83a2-dc040d64816d'
        ]);
        return $res;
        */

    }

    //TrackingMore API 获取多个运单单号的物流信息
    //$numbers 用都好隔开
    public function getInfoByTrackingMore($numbers){

        $data = [
            'numbers' => $numbers
        ];

        $data = http_build_query($data);

        $url = 'https://api.trackingmore.com/v2/trackings/get?';

        $res = $this -> get($url.$data,[
            'Content-Type: application/json',
            'Trackingmore-Api-Key:015dbffa-15ef-4f5a-83a2-dc040d64816d'
        ]);
        $json_decode_data = json_decode($res,true);
        //dump($json_decode_data);
        if($json_decode_data['meta']['code'] == '200'){
            return $json_decode_data['data'];
        }else{
            return false;
        }
    }


    //异步请求
    public  function asyPost($url, $post_data = '',$headers = []){
        header("Content-type:text/html;charset=utf-8");
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);

        curl_setopt($ch, CURLOPT_TIMEOUT, 1);

        $result = curl_exec($ch);
        //curl_close($ch);
        if (curl_errno($ch)) {
            return  curl_error($ch);
        }
        curl_close($ch);
        return $result;
    }


    //trackingStatus 翻译
    public function transTrackingStatus($trackingStatus){

        $temp = [
            'created' => '订单建立',
            'exported' => '已预报',
            'arrived at destination' => '到达广州交换局',
            'arrived at processing centre' => '到达广州海关',
            'arrived at warehouse' => '邮局已接收',
            'inscanned' => '邮局已过机',
            'clearance' => '香港海关放行',
            'submitted' => '提交运输请求',
            'accepted' => '接受运输请求',
            'despatched' => '离开香港',
            'completed' => '运输流程完成',
            'delivery' => '已派送',
            'handed' => '移交待发货区',
            'delivered' => '已派送',
            'held by customs' => '送交海关',
            'shipment rejected' => '拒绝出货',
            'shipment is ready to returned to sender' => '准备退回',
            'shipment returned' => '已退回',
            'inward office of exchange' => '海关放行',
        ];

        foreach($temp as $k => $vo){
            if($k == $trackingStatus){
                return $temp[$trackingStatus];
            }
        }

        return $trackingStatus;





        if($trackingStatus == 'created'){
            return '订单建立';
        }elseif($trackingStatus == 'exported'){
            return '已预报';
        }elseif($trackingStatus == 'arrived at destination'){
            return '到达广州交换局';
        }elseif($trackingStatus == 'arrived at processing centre'){
            return '到达广州海关';
        }elseif($trackingStatus == 'arrived at warehouse'){
            return '邮局已接收';
        }elseif($trackingStatus == 'inscanned'){
            return '邮局已过机';
        }elseif($trackingStatus == 'clearance'){
            return '香港海关放行';
        }elseif($trackingStatus == 'submitted'){
            return '提交运输请求';
        }elseif($trackingStatus == 'accepted'){
            return '接受运输请求';
        }elseif($trackingStatus == 'despatched'){
            return '离开香港';
        }elseif($trackingStatus == 'completed'){
            return '运输流程完成';
        }elseif($trackingStatus == 'delivery'){
            return '已派送';
        }elseif($trackingStatus == 'handed'){
            return '移交待发货区';
        }elseif($trackingStatus == 'delivered'){
            return '已派送';
        }

        //update by 2019-03-25
        elseif($trackingStatus == 'held by customs'){
            return '送交海关';
        }elseif($trackingStatus == 'shipment rejected'){
            return '拒绝出货';
        }elseif($trackingStatus == 'shipment is ready to returned to sender'){
            return '准备退回';
        }elseif($trackingStatus == 'shipment returned'){
            return '已退回';
        }elseif($trackingStatus == 'inward office of exchange'){
            return '海关放行';
        }else{
            return $trackingStatus;
        }
    }




}
