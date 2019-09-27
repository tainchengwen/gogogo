<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BatchRepertoryRelation extends Model
{
    //
    protected $table = 'batch_repertory_relation';


    protected $dateFormat = 'U';


    public function getInfoByRelationId($relation_id){
        $relation_info = self::find($relation_id);
        //通过批次id  到货单id 获取所有包裹
        $package_ids = BatchPackagesRelation::where([
            'batch_id' => $relation_info -> batch_id,
            'repertory_id' => $relation_info -> repertory_id
        ]) -> orderBy('id','desc') -> get();
        $package_info = [];
        foreach($package_ids as $vo){
            $package_info[] = DB::table('packages') -> where([
                'id' => $vo -> package_id
            ]) -> first();
        }
        return $package_info;

    }


    //生成交货单时，修改物流单号的状态
    public function updateRepertoryStatus($batch_id){
        //通过托盘id 找对应的物流单号id
        $relations = BatchRepertoryRelation::where('batch_id',$batch_id) -> first();
        if($relations){
            $repertory_id = $relations -> repertory_id;
            //看下这个物流单号 有多少托盘
            $batch_ids = BatchRepertoryRelation::where('repertory_id',$repertory_id)-> get();
            if(count($batch_ids)){
                //看下这些单号 是不是都生成交货单了
                $is_update_status = true;
                $fa_num = 0;
                foreach($batch_ids as $vo){
                    $temp = BatchList::find($vo -> batch_id);
                    if(!$temp && !$temp -> send_order_id){
                        $is_update_status = false;
                    }
                    //累加此物流单号 发出包裹的数量
                    $fa_num += intval($vo -> count_packages);
                }

                if($is_update_status){
                    //更新
                    Repertory::where('id',$repertory_id) -> update([
                        'status' => 3, //改为已出货
                        'updated_at' => time()
                    ]);
                }else{
                    //改为出货中
                    Repertory::where('id',$repertory_id) -> update([
                        'status' => 1, //改为出货中
                        'updated_at' => time()
                    ]);
                }

                $repertory_info = Repertory::where('id',$repertory_id) -> first();


                //更新此物流单号 发出的数量
                Repertory::where('id',$repertory_id) -> update([
                    'fachu_num' => $fa_num,
                    'shengyu_num' => intval($repertory_info -> dabao_num) - intval($fa_num)
                ]);

            }




        }
    }



}
