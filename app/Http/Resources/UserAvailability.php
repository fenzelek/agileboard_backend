<?php

namespace App\Http\Resources;

class UserAvailability extends AbstractResource
{
    /**
     * {@inhertidoc}.
     */
    protected $fields = [
        'day',
        'time_start',
        'time_stop',
        'available',
        'description',
        'status',
        'overtime',
        'source',
    ];

    /**
     * @inheritdoc
     */
    public function toArray($request)
    {
        $data = parent::toArray($request);
        $data['available'] = (bool) $data['available'];
        $data['overtime'] = (bool) $data['overtime'];

        return $data;
    }
}
