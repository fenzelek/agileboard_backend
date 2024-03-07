<?php

namespace App\Modules\TimeTracker\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ActivityCollection extends ResourceCollection
{
    public $collects = ActivityResource::class;

    /**
     * Transform the resource into an array.
     *
     * @param $request
     *
     * @return \Illuminate\Support\Collection
     */
    public function toArray($request)
    {
        return parent::toArray($request);
    }
}
