<?php

namespace App\Http\Resources;

class UserCompany extends AbstractResource
{
    /**
     * @inheritdoc
     */
    protected $fields = [
        'user_id',
        'company_id',
        'role_id',
        'status',
        'title',
        'skills',
        'description',
        'department',
        'contract_type',
    ];
}
