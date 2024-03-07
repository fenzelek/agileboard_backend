<?php

namespace App\Http\Resources;

class VatReleaseReason extends AbstractResource
{
    protected $fields = [
        'id',
        'slug',
        'description',
    ];
}
