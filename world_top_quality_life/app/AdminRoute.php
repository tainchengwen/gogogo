<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AdminRoute extends Model
{
    //
    protected $table = 'route';


    protected $dateFormat = 'U';


    public function port(){
        return $this -> belongsTo(AdminPort::class,'port_id','id');
    }
}
