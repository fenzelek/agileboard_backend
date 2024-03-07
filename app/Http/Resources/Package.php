<?php

namespace App\Http\Resources;

class Package extends AbstractResource
{
    /**
     * @inheritdoc
     */
    protected $fields = [
        'id',
        'name',
        'slug',
        'default',
        'portal_name',
    ];
}
