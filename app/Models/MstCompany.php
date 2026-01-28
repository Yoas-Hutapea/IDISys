<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MstCompany extends Model
{
    protected $table = 'mstCompany';

    protected $primaryKey = 'ID';

    public $timestamps = false;

    protected $guarded = [];
}
