<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AreaName extends Model
{
    //
    protected $table = 'area_name';


    protected $dateFormat = 'U';


    public function price_temp()
    {
        return $this->hasOne(PriceTemp::class,'area_id');
    }

    //花费区域余额
    public function usePrice($area_id,$price_use,$package_num){
        DB::table('area_name') -> where([
            'id' => $area_id
        ]) -> decrement('price',$price_use);

        //余额记录
        $log_model = new AreaPriceLog();
        $log_model -> addLog($area_id,$price_use,2,$package_num);
    }

    //区域余额充值
    public function chargePrice($area_id,$price_charge){
        //余额加上
        DB::table('area_name') -> where([
            'id' => $area_id
        ]) -> increment('price',$price_charge);

        //余额记录
        $log_model = new AreaPriceLog();
        $log_model -> addLog($area_id,$price_charge,1,'');
    }

    //取消订单 返还余额
    public function cancelPackage($package_id){
        $package_info = DB::table('packages') -> where([
            'id' => $package_id
        ]) -> first();
        $order_info = DB::table('order') -> where([
            'id' => $package_info -> order_id
        ]) -> first();

        //返还余额
        DB::table('area_name') -> where([
            'id' => $order_info -> from_area
        ]) -> increment('price',$package_info -> area_price);
        $package_num = $package_info -> package_num;
        //余额记录
        $log_model = new AreaPriceLog();
        $log_model -> addLog($order_info -> from_area,$package_info -> area_price,5,$package_num);
    }

    //修改重量 返还/补扣

    /**
     * @param $from_area
     * @param $price
     * @param $type 1返还 2补扣
     */
    public function updatePackage($from_area,$price,$type = 2,$package_num){
        if($type == 1){
            //返还
            DB::table('area_name') -> where([
                'id' => $from_area
            ]) -> increment('price',$price);

            //余额记录
            $log_model = new AreaPriceLog();
            $log_model -> addLog($from_area,$price,3,$package_num);
        }elseif($type == 2){
            //补扣 要看下他的钱 够不够
            $area_info = DB::table('area_name') -> where([
                'id' => $from_area
            ]) -> first();
            $no_price = 0;
            if($area_info -> price <= $price){
                //钱不够
                $no_price = 1;
            }

            DB::table('area_name') -> where([
                'id' => $from_area
            ]) -> decrement('price',$price);

            //余额记录
            $log_model = new AreaPriceLog();
            $log_model -> addLog($from_area,$price,4,$package_num);
            if($no_price){
                //钱不够 先扣了 再提醒
                return 'no_price';
            }
        }
    }


    //更新过机重量 补扣或者返还
    public function updateGuojiWeight($from_area,$price,$type = 2,$package_num,$weight,$passweight){
        if($type == 1){
            //返还
            DB::table('area_name') -> where([
                'id' => $from_area
            ]) -> increment('price',$price);

            //余额记录
            $log_model = new AreaPriceLog();
            $log_model -> addLog($from_area,$price,3,$package_num,'申报重量:'.$weight.',过机重量:'.$passweight);
        }elseif($type == 2){
            //补扣 要看下他的钱 够不够
            $area_info = DB::table('area_name') -> where([
                'id' => $from_area
            ]) -> first();
            $no_price = 0;
            if($area_info -> price <= $price){
                //钱不够
                $no_price = 1;
            }

            DB::table('area_name') -> where([
                'id' => $from_area
            ]) -> decrement('price',$price);

            //余额记录
            $log_model = new AreaPriceLog();
            $log_model -> addLog($from_area,$price,4,$package_num,'申报重量:'.$weight.',过机重量:'.$passweight);
            if($no_price){
                //钱不够 先扣了 再提醒
                return 'no_price';
            }
        }
    }



}

