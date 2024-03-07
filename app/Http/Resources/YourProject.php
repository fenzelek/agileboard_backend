<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Db\Project;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Project $resource
 */
class YourProject extends JsonResource
{
    public function toArray($request)
    {
        return [
          'id' => $this->resource->id,
          'short_name' => $this->resource->short_name,
          'company_id' => $this->resource->company_id,
          'name' => $this->resource->name,
          'color' => $this->resource->color,
        ];
    }
}
