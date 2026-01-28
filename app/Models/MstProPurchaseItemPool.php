<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MstProPurchaseItemPool extends Model
{
    protected $table = 'mstPROPurchaseItemPool';

    protected $primaryKey = 'Id';

    public $timestamps = false;

    protected $guarded = [];
}
