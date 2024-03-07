<?php

namespace App\Http\Resources;

class File extends AbstractResource
{
    protected $fields = [
        'id',
        'project_id',
        'user_id',
        'owner',
        'name',
        'size',
        'extension',
        'description',
        'created_at',
        'updated_at',
    ];

    public function toArray($request)
    {
        $data = parent::toArray($request);
        $data['created_at'] = $this->created_at->toDateTimeString();
        $data['updated_at'] = $this->updated_at ? $this->updated_at->toDateTimeString() : null;

        return $data;
    }
}
