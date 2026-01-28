<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrxProPurchaseOrder extends Model
{
    protected $table = 'trxPROPurchaseOrder';

    protected $primaryKey = 'ID';

    public $timestamps = false;

    protected $guarded = [];
}
