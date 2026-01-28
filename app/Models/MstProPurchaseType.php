<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MstProPurchaseType extends Model
{
    protected $table = 'mstPROPurchaseType';

    protected $primaryKey = 'ID';

    public $timestamps = false;

    protected $guarded = [];
}
