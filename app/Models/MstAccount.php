<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MstAccount extends Authenticatable
{
    use Notifiable;

    protected $table = 'mstAccount';

    protected $primaryKey = 'ID';

    public $timestamps = false;

    protected $fillable = [
        'Username',
        'PasswordHash',
        'PasswordSalt',
        'Employ_Id',
        'CreatedDate',
        'CreatedBy',
        'UpdatedDate',
        'UpdatedBy',
        'IsActive',
    ];

    protected $hidden = [
        'PasswordHash',
        'PasswordSalt',
    ];

    protected $casts = [
        'CreatedDate' => 'datetime',
        'UpdatedDate' => 'datetime',
        'IsActive' => 'boolean',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(MstEmployee::class, 'Employ_Id', 'Employ_Id');
    }

    public function getAuthPassword(): string
    {
        return (string) $this->PasswordHash;
    }

    public function getRememberTokenName()
    {
        return null;
    }
}
