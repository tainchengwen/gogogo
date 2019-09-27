<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SplitPackage extends Model
{
    //
    protected $table = 'packages_split';


    protected $dateFormat = 'U';


    public function packages(){
        return $this->belongsTo(Package::class,'package_id');
    }

}
