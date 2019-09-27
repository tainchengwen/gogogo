<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SendOrder extends Model
{
    protected $table = 'send_order_list';


    protected $dateFormat = 'U';


    // 通过计算交货单的过机通过率
    public function updatePassNumber($package_id){
        //查看此包裹 是否过机
        $package_info = Package::find($package_id);
        if(!$package_info -> pass_mark){
            //找到这个包裹 所属的批次
            $relations_batch = BatchPackagesRelation::where('package_id',$package_id) -> first();
            if($relations_batch){
                //通过批次号 查找批次信息
                $batch_info = BatchList::find($relations_batch -> batch_id);
                if($batch_info){
                    //通过批次号 找send_order_id
                    DB::table('send_order_list') -> where('id',$batch_info -> send_order_id) -> increment('pass_num',1);

                    Package::where('id',$package_id) -> update([
                        'pass_mark' => 1
                    ]);

                }
            }
        }
    }



    //出货汇总
    public function getSummaryList($layDate){
        $timestamp_right = intval(strtotime($layDate)) + 86400;
        $timestamp_left = intval(strtotime($layDate));
        $send_order_list = SendOrder::where('created_at','>=',$timestamp_left) -> where('created_at','<=',$timestamp_right)  -> orderBy('id','desc') ->  get();


        $send_order_ids = [];
        foreach($send_order_list as $vo){
            $send_order_ids[] = $vo -> id;
        }
        //这些交货单下的托盘
        $batchs = BatchList::whereIn('send_order_id',$send_order_ids) -> get();
        $batch_ids = [];
        foreach($batchs as $vo){
            $batch_ids[] = $vo -> id;
        }

        //通过交货单号 在交货单-物流关系表里查
        $relations = BatchRepertoryRelation::whereIn('batch_id',$batch_ids) -> get();

        //找到所有的到货物流id
        $repertory_ids = [];
        foreach($relations as $vo){
            $repertory_ids[] = $vo -> repertory_id;
        }

        $reperatorys = Repertory::whereIn('id',$repertory_ids) -> get();

        //通过到货物流单号 逆查
        foreach($reperatorys as $k => $vo){
            //通过物流单号 查包裹数量
            $package_repertory_count = BatchPackagesRelation::where('repertory_id',$vo -> id) -> count();
            $temp = BatchPackagesRelation::where('repertory_id',$vo -> id) -> orderBy('id','desc') -> first();
            if($temp){
                $reperatorys[$k] -> tie_date = $temp -> created_at;
            }else{
                $reperatorys[$k] -> tie_date = '';
            }
            //包裹数量
            $reperatorys[$k] -> count_package = $package_repertory_count;
        }

        return $reperatorys;


        dd($reperatorys);




        foreach($send_order_list as $k => $vo){
            //通过交货单号 找托盘
            $batchs = BatchList::where('send_order_id',$vo -> id) -> get();
            $send_order_list[$k] -> batchs = $batchs;
            //通过托盘 找包裹数量
            $package_num = 0;
            foreach($batchs as $key => $value){
                $packages = BatchPackagesRelation::where('batch_id',$value -> id) -> count();
                $package_num += $packages;
            }

            $send_order_list[$k] -> count_package_num = $package_num;


        }

        dd($send_order_list);

        return $send_order_list;
    }


}
