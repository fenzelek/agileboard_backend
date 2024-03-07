<?php

namespace App\Http\Resources;

class KnowledgeDirectory extends AbstractResource
{
    protected $fields = '*';

    public function toArray($request)
    {
        $data = parent::toArray($request);
        $data['created_at'] = $this->created_at->toDateTimeString();
        $data['updated_at'] = $this->updated_at ? $this->updated_at->toDateTimeString() : null;

        return $data;
    }
}
