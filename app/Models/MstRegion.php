<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MstRegion extends Model
{
    protected $table = 'mstRegion';

    protected $primaryKey = 'RegionId';

    public $timestamps = false;

    protected $guarded = [];
}
