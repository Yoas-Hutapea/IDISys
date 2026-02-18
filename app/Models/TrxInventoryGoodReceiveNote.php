<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrxInventoryGoodReceiveNote extends Model
{
    protected $table = 'trxInventoryGoodReceiveNote';

    protected $primaryKey = 'ID';

    public $timestamps = false;

    protected $guarded = [];
}
