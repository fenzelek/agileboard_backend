<?php

namespace App\Http\Resources;

class ModuleWithModsCount extends AbstractResource
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
        'mods_count',
    ];
}
