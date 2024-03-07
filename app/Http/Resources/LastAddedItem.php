<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Db\Ticket;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Ticket $resource
 */
class LastAddedItem extends JsonResource
{
    public function toArray($request)
    {
        $data = [
            'id' => $this->resource->id,
            'title' => $this->resource->title,
            'name' => $this->resource->name,
            'created_at' => $this->resource->created_at->toDateTimeString(),
            'type_id' => $this->resource->type_id,
            'type_name' => $this->resource->type ? $this->resource->type->name : '',
        ];

        $data['stories'] = collect();

        $this->resource->stories->each(
            fn ($story) => $data['stories']->push([
                'color' => $story->color,
                'name' => $story->name,
            ])
        );

        return $data;
    }
}
