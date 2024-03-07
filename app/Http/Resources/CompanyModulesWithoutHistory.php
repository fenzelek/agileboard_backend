<?php

namespace App\Http\Resources;

class CompanyModulesWithoutHistory extends AbstractResource
{
    protected $ignoredRelationships = ['companyModuleHistory'];

    protected $fields = '*';

    public function toArray($request)
    {
        $data = parent::toArray($request);

        $data['expiration_date'] = $this->expiration_date ? $this->expiration_date->toDateTimeString() : null;

        return $data;
    }
}
