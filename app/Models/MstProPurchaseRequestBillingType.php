<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MstProPurchaseRequestBillingType extends Model
{
    protected $table = 'mstPROPurchaseRequestBillingType';

    protected $primaryKey = 'ID';

    public $timestamps = false;

    protected $guarded = [];
}
