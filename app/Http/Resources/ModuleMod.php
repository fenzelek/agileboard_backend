<?php

namespace App\Http\Resources;

class ModuleMod extends AbstractResource
{
    /**
     * @inheritdoc
     */
    protected $fields = [
        'id',
        'module_id',
        'test',
        'value',
    ];
}
