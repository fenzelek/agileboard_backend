<?php

namespace App\Http\Resources;

class Company extends AbstractResource
{
    /**
     * @inheritdoc
     */
    protected $fields = [
        'id',
        'name',
        'vat_payer',
    ];
}
