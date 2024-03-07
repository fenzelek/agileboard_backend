<?php

namespace App\Http\Resources;

class TicketShortEstimate extends AbstractResource
{
    /**
     * @inheritdoc
     */
    protected $fields = [
        'id',
        'name',
        'title',
        'estimate_time',
    ];
}
