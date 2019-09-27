<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
    //
    protected $table = 'erp_business';


    protected $dateFormat = 'U';

    protected $fillable = ['name','describe','currency','attribute'];
}
