<?php

namespace App\Http\Resources;

class KnowledgePageWithoutContent extends AbstractResource
{
    protected $fields = [
        'id',
        'project_id',
        'name',
        'created_at',
        'updated_at',
        'creator_id',
        'knowledge_directory_id',
        'pinned',
        'deleted_at',
    ];

    public function toArray($request)
    {
        $data = parent::toArray($request);
        $data['created_at'] = $this->created_at->toDateTimeString();
        $data['updated_at'] = $this->updated_at ? $this->updated_at->toDateTimeString() : null;
        $data['deleted_at'] = $this->deleted_at ? $this->deleted_at->toDateTimeString() : null;

        return $data;
    }
}
