<?php

namespace App\Http\Resources;

class ModPriceWitchChecksum extends AbstractResource
{
    protected $ignoredRelationships = ['moduleMod'];

    protected $fields = [
        'id',
        'module_mod_id',
        'package_id',
        'days',
        'default',
        'price',
        'price_change',
        'currency',
        'created_at',
        'updated_at',
        'checksum',
        'checksum_change',
    ];

    public function toArray($request)
    {
        $data = parent::toArray($request);

        $data['created_at'] = $this->created_at ? $this->created_at->toDateTimeString() : null;
        $data['updated_at'] = $this->updated_at ? $this->updated_at->toDateTimeString() : null;

        return $data;
    }
}
