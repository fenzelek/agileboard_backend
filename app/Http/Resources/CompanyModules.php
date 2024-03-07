<?php

namespace App\Http\Resources;

class CompanyModules extends AbstractResource
{
    protected $fields = '*';

    public function toArray($request)
    {
        $data = parent::toArray($request);

        $data['expiration_date'] = $this->expiration_date->toDateTimeString();

        return $data;
    }
}
