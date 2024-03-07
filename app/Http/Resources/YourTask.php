<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Db\Ticket;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Ticket $resource
 */
class YourTask extends JsonResource
{
    public function toArray($request)
    {
        $data = [
            'id' => $this->resource->id,
            'title' => $this->resource->title,
            'name' => $this->resource->name,
            'status_id' => $this->resource->status_id,
            'status_name' => $this->resource->status->name,
            'sprint_id' => $this->resource->sprint_id,
            'project_id' => $this->resource->project_id,
            'project_name' => $this->resource->project->name,
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
