<?php

namespace App\Http\Resources\TimeTracking;

use App\Http\Resources\AbstractResource;

class User extends AbstractResource
{
    /**
     * @inheritdoc
     */
    protected $fields = '*';

    public function toArray($request)
    {
        $data = parent::toArray($request);

        $data['created_at'] = $this->created_at ? $this->created_at->toDateTimeString() : null;
        $data['updated_at'] = $this->updated_at ? $this->updated_at->toDateTimeString() : null;

        return $data;
    }
}
