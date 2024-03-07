<?php

namespace App\Modules\TimeTracker\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;
use function request;

class ActivityFrameScreen extends ResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @param $request
     *
     * @return \Illuminate\Support\Collection
     */
    public function toArray($request)
    {
        $prefix = request()->getSchemeAndHttpHost();

        return $this->collection->pluck('resource.screen')->map(function ($item) use ($prefix) {
            return [
                'url_link' => $prefix . $item->url_link,
                'thumbnail_link' => $prefix . $item->thumbnail_link,
            ];
        });
    }
}
