<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MstCurrency extends Model
{
    protected $table = 'mstCurrency';

    protected $primaryKey = 'ID';

    public $timestamps = false;

    protected $guarded = [];
}
