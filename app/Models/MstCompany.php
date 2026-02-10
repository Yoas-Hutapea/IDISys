<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MstCompany extends Model
{
    protected $table = 'mstCompany';

    protected $primaryKey = 'ID';

    public $timestamps = false;

    protected $fillable = [
        'CompanyID',
        'CompanyGroupID',
        'CompanyIDAX',
        'CompanyIDFinance',
        'Company',
        'CompanyAX',
        'CompanyAddress',
        'CompanyAddress1',
        'CompanyAddress2',
        'mstPurchaseCountriesID',
        'ZipCode',
        'TelpNumber',
        'Fax',
        'BankName',
        'AccNO',
        'NPWP',
        'NPWPAddress',
        'NPWPAddress1',
        'NPWPAddress2',
        'Manager',
        'Director',
        'Position',
        'CreatedBy',
        'CreatedDate',
        'UpdatedBy',
        'UpdatedDate',
        'IsActive',
    ];

    protected $casts = [
        'CreatedDate' => 'datetime',
        'UpdatedDate' => 'datetime',
        'IsActive' => 'boolean',
    ];
}
