<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrxProPurchaseRequestDocument extends Model
{
    protected $table = 'trxPROPurchaseRequestDocument';

    protected $primaryKey = 'ID';

    public $timestamps = false;

    protected $guarded = [];
}
