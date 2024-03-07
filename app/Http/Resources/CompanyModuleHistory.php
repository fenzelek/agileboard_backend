<?php

namespace App\Http\Resources;

class CompanyModuleHistory extends AbstractResource
{
    /**
     * @inheritdoc
     */
    protected $fields = [
        'id',
        'company_id',
        'module_id',
        'module_mod_id',
        'old_value',
        'new_value',
        'start_date',
        'expiration_date',
        'status',
        'package_id',
        'price',
        'currency',
        'vat',
        'transaction_id',
        'created_at',
        'updated_at',
    ];

    /**
     * @inheritdoc
     */
    public function toArray($request)
    {
        $data = parent::toArray($request);
        if ($this->start_date) {
            $data['start_date'] = $this->start_date->toDateTimeString();
        }
        if ($this->expiration_date) {
            $data['expiration_date'] = $this->expiration_date->toDateTimeString();
        }
        if ($this->created_at) {
            $data['created_at'] = $this->created_at->toDateTimeString();
        }
        if ($this->updated_at) {
            $data['updated_at'] = $this->updated_at->toDateTimeString();
        }

        return $data;
    }
}
