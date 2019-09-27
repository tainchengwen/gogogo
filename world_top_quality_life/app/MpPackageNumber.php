<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MpPackageNumber extends Model
{
    //
    protected $table = 'mp_temp_package_number';


    protected $dateFormat = 'U';


    //小程序user
    public function mp_users(){
        $this -> belongsTo(MpUser::class,'user_id','id');
    }


    //区域
    public function area_name(){
        $this -> belongsTo(AreaName::class,'area_id','id');
    }



    //小程序包裹编号生成
    static function makeMpNumber($from_area,$number){
        //批量生成单号
        $arr = [];
        $queue_info = DB::table('sequeue')
            -> where([
                'from_area' => $from_area,
                'type' => 2,
            ]) -> first();
        if(!$queue_info){
            DB::table('sequeue') -> insert([
                'from_area' => $from_area,
                'type' => 2,
                'order_num' => 1
            ]);
            $start_num = 1;
        }else{
            $start_num = $queue_info -> order_num;
        }



        for($i=0;$i<$number;$i++){

            //$arr[] = 'HQ'.substr(time(),5).$this -> milliseconds().sprintf('%02s', $i);
            $arr[] = 'H'.sprintf('%03s', $from_area).sprintf('%09s', $start_num);
            $start_num ++ ;
        }

        DB::table('sequeue') -> where([
            'from_area' => $from_area,
            'type' => 2,
        ]) -> update([
            'order_num' => $start_num
        ]);

        $ids = [];

        foreach($arr as $vo){
            $info = DB::table('mp_temp_package_number')
                -> where([
                    'package_num' => $vo
                ]) -> first();
            if(!$info){
                $id_temp = DB::table('mp_temp_package_number')
                    -> insertGetId([
                        'package_num' =>$vo,
                        'area_id' => $from_area,
                        'created_at' => time(),
                        'updated_at' => time()
                    ]);
                $ids[] = $id_temp;
            }
        }
        return $ids;
    }


}
