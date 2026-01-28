<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MstProPurchaseItemInventory extends Model
{
    protected $table = 'mstPROPurchaseItemInventory';

    protected $primaryKey = 'ID';

    public $timestamps = false;

    protected $guarded = [];
}
