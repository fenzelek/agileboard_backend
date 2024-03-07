<?php

namespace App\Http\Resources;

class Module extends AbstractResource
{
    /**
     * @inheritdoc
     */
    protected $fields = [
        'id',
        'name',
        'slug',
        'description',
        'visible',
        'available',
    ];
}
