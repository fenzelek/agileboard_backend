<?php

namespace App\Http\Resources;

class FileUser extends AbstractResource
{
    /**
     * @inheritdoc
     */
    protected $fields = [
        'id',
        'email',
        'first_name',
        'last_name',
        'avatar',
    ];
}
