<?php

namespace App\Modules\Integration\Http\Resources;

use App\Modules\Integration\Models\DailyActivity;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Collection;

/**
 * @property DailyActivity $resource
 */
class TimeSummaryItem extends JsonResource
{
    public function toArray($request)
    {
        return [
            'date' => $this->resource->date,
            'tracked' => $this->resource->tracked,
        ];
    }
}
