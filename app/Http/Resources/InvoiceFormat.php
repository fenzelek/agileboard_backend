<?php

namespace App\Http\Resources;

class InvoiceFormat extends AbstractResource
{
    /**
     * @inheritdoc
     */
    protected $fields = [
        'id',
        'name',
        'format',
        'example',
    ];
}
