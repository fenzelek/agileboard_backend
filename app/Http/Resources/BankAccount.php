<?php

namespace App\Http\Resources;

class BankAccount extends AbstractResource
{
    protected $fields = [
        'id',
        'number',
        'bank_name',
        'default',
    ];
}
