<?php

namespace App\Http\Resources;

class PackageWithPrice extends AbstractResource
{
    protected $ignoredRelationships = ['modPrices'];

    /**
     * @inheritdoc
     */
    protected $fields = [
        'id',
        'name',
        'slug',
        'default',
        'portal_name',
        'price',
        'days',
    ];
}
