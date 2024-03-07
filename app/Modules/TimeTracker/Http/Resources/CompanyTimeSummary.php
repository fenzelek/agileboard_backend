<?php

namespace App\Modules\TimeTracker\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CompanyTimeSummary extends JsonResource
{
    public $preserveKeys = true;

    public function toArray($request)
    {
        return [
            $this->resource->time_summary,
        ];
    }
}
