<?php

namespace App\Http\Resources;

class RegisterOfInvoices extends AbstractResource
{
    protected $fields = [
        'id',
        'number',
        'company_id',
        'contractor_id',
        'sale_date',
        'issue_date',
        'invoice_type_id',
    ];

    public function toArray($request)
    {
        $data = parent::toArray($request);

        $contractor_data = array_only($data['invoice_contractor']['data']->resource->toArray(), [
            'name',
            'vatin',
            'main_address_street',
            'main_address_number',
            'main_address_zip_code',
            'main_address_city',
            'main_address_country',
        ]);

        unset($data['invoice_contractor']);

        return array_merge($data, $contractor_data, ['taxes' => $data['taxes']]);
    }
}
