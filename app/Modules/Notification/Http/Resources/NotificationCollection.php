<?php

declare(strict_types=1);

namespace App\Modules\Notification\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\Paginator;

/**
 * @property Paginator $resource
 */
class NotificationCollection extends ResourceCollection
{
    public function toArray($request): array
    {
        return [
            'data' => Notification::collection($this->resource->items()),
            'current_page' => $this->resource->currentPage(),
            'per_page' => $this->resource->perPage(),
        ];
    }
}
