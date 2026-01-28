<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrxProPurchaseRequestItem extends Model
{
    protected $table = 'trxPROPurchaseRequestItem';

    protected $primaryKey = 'ID';

    public $timestamps = false;

    protected $guarded = [];
}
