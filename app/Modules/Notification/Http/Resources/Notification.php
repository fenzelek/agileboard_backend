<?php

namespace App\Modules\Notification\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \App\Modules\Notification\Models\Dto\Notification $resource
 */
class Notification extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->resource->getId(),
            'type' => $this->resource->getType(),
            'created_at' => $this->resource->getCreatedAt(),
            'read_at' => $this->resource->getReadAt(),
            'company_id' => $this->resource->getCompanyId(),
            'data' => $this->resource->getData(),
        ];
    }
}
