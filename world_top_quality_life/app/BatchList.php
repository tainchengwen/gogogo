<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BatchList extends Model
{
    //
    protected $table = 'batch_packages';


    protected $dateFormat = 'U';


    //增加 批次 包裹关系
    public function addBatchPackageRelation($batch_id,$repertory_id,$package_id){
        //先查看有没有关系
        $relation = DB::table('batch_packages_relation') -> where([
            'package_id' => $package_id,
            'batch_id' => $batch_id,
            'repertory_id' => $repertory_id
        ]) -> first();
        if(!$relation){
            DB::table('batch_packages_relation') -> insertGetId([
                'package_id' => $package_id,
                'batch_id' => $batch_id,
                'repertory_id' => $repertory_id,
                'created_at' => time(),
                'updated_at' => time(),
            ]);
        }
        //返回 此批次-此到货物流编号  有多少包裹
        return BatchPackagesRelation::where([
            'batch_id' => $batch_id,
            'repertory_id' => $repertory_id
        ]) -> get();
    }


    //增加 批次 物流单号关系
    /**
     * @param $batch_id 批次号
     * @param $repertory_id 到货物流单号id
     */
    public function addBatchRepertoryRelation($batch_id,$repertory_id){
        $relation = DB::table('batch_repertory_relation') -> where([
            'batch_id' => $batch_id,
            'repertory_id' => $repertory_id,
        ]) -> first();
        if(!$relation){
            $relation_id = DB::table('batch_repertory_relation') -> insertGetId([
                'batch_id' => $batch_id,
                'repertory_id' => $repertory_id,
                'created_at' => time(),
                'updated_at' => time(),
            ]);
            return $relation_id;
        }else{
            return $relation -> id;
        }

    }


    //查看此包裹ID  是否存在与批次中
    public function getBatchInfoByPackage($package_id){
        $relation = DB::table('batch_packages_relation') -> where([
            'package_id' => $package_id
        ]) -> first();
        return $relation;
    }

    //查看一个批次里 有多少包裹
    function getPackagesByPici($pici_id){
        //查找这个批次 所有的批次-物流单号关系
        $relations = DB::table('batch_repertory_relation') -> where([
            'batch_id' => $pici_id
        ]) -> get();

        $count_package = 0;
        foreach($relations as $vo){
            //通过批次id 入库物流单号id 拿包裹
            $package_ids = DB::table('batch_packages_relation') -> where([
                'batch_id' => $pici_id,
                'repertory_id' => $vo -> repertory_id
            ]) -> get();
            $count_package += count($package_ids);
        }
        return $count_package;
    }

    //通过包裹编号查找 跟其他的包裹是不是一个路线的
    function checkPackageRoute($package_info,$batch_id){
        $relations = DB::table('batch_packages_relation') -> where([
            'batch_id' => $batch_id
        ]) -> first();
        if($relations){
            $package_temp_info = DB::table('packages') -> where([
                'id' => $relations -> package_id
            ]) -> first();
            if($package_info -> route_id == $package_temp_info -> route_id){
                return true;
            }else{
                return false;
            }
        }else{
            return true;
        }
    }




}
