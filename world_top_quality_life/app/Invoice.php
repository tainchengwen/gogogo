<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    //
    protected $table = 'invoice';


    protected $dateFormat = 'U';

    public function mp_users()
    {
        return $this->belongsTo(MpUser::class,'mp_user_id');
    }


}
