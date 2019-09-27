<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BatchPackagesRelation extends Model
{
    //
    protected $table = 'batch_packages_relation';


    protected $dateFormat = 'U';


    //删除关系
    public function deleteRelation($package_id,$batch_id,$repertory_id){
        DB::table('batch_packages_relation') -> where([
            'package_id' => $package_id,
            'batch_id' => $batch_id,
            'repertory_id' => $repertory_id
        ]) -> delete();
    }

}
