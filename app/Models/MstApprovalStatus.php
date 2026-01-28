<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MstApprovalStatus extends Model
{
    protected $table = 'mstApprovalStatus';

    protected $primaryKey = 'ID';

    public $timestamps = false;

    protected $guarded = [];
}
