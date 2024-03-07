<?php

namespace App\Http\Resources;

class ModuleModWithoutModule extends AbstractResource
{
    protected $ignoredRelationships = ['module'];

    protected $fields = [
        'id',
        'module_id',
        'test',
        'value',
        'error',
    ];
}
