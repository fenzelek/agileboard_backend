<?php

namespace App\Models\Db;

class InvoiceCompany extends Model
{
    use FullVatin;

    protected $fillable = [
        'invoice_id',
        'company_id',
        'name',
        'country_vatin_prefix_id',
        'vatin',
        'email',
        'logotype',
        'website',
        'phone',
        'bank_name',
        'bank_account_number',
        'main_address_street',
        'main_address_number',
        'main_address_zip_code',
        'main_address_city',
        'main_address_country',
    ];
}
