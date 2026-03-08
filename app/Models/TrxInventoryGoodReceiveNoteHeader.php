<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrxInventoryGoodReceiveNoteHeader extends Model
{
    protected $table = 'trxInventoryGoodReceiveNoteHeader';

    protected $primaryKey = 'ID';

    public $timestamps = false;

    protected $guarded = [];
}
