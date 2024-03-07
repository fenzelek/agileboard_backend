<?php

namespace App\Http\Resources;

use App\Models\Db\KnowledgePageComment as KnowledgePageCommentModel;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property KnowledgePageCommentModel $resource
 */
class KnowledgePageCommentBasic extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->resource->id,
            'knowledge_page_id' => $this->resource->knowledge_page_id,
            'user_id' => $this->resource->user_id,
            'type' => $this->resource->type,
            'text' => $this->resource->text,
            'ref' => $this->resource->ref,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
