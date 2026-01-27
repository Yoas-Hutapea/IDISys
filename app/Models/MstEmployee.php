<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MstEmployee extends Model
{
    protected $table = 'mstEmployee';

    protected $primaryKey = 'Employ_Id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $guarded = [];
}
