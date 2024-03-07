<?php

namespace App\Http\Resources;

class KnowledgePage extends AbstractResource
{
    protected $fields = '*';

    public function toArray($request)
    {
        $data = parent::toArray($request);
        $data['created_at'] = $this->created_at->toDateTimeString();
        $data['updated_at'] = $this->updated_at ? $this->updated_at->toDateTimeString() : null;
        $data['deleted_at'] = $this->deleted_at ? $this->deleted_at->toDateTimeString() : null;

        return $data;
    }
}
