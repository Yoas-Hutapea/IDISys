<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrxProPurchaseRequest extends Model
{
    protected $table = 'trxPROPurchaseRequest';

    protected $primaryKey = 'ID';

    public $timestamps = false;

    protected $guarded = [];
}
