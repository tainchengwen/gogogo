<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Refund extends Model
{
    //
    protected $table = 'erp_refunds';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $fillable=['order_id','request_reason','result','reason','operate_id','created_at','updated_at'];
}
