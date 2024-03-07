<?php

namespace App\Http\Resources;

class TicketShort extends AbstractResource
{
    /**
     * @inheritdoc
     */
    protected $fields = [
        'id',
        'name',
        'title',
    ];
}
