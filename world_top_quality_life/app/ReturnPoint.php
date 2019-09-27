<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ReturnPoint extends Model
{
    //
    protected $table = 'return_point';


    protected $dateFormat = 'U';

    public function mpusers(){
        return $this -> belongsTo(MpUser::class,'user_id');
    }

}
