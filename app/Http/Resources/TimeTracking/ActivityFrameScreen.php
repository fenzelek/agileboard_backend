<?php

namespace App\Http\Resources\TimeTracking;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ActivityFrameScreen extends ResourceCollection
{
    private string $prefix;

    /**
     * ContractorShortListResource constructor.
     * Enable wrap for this resource.
     *
     * @param $resource
     */
    public function __construct($resource)
    {
        parent::__construct($resource);
        $this->prefix = request()->getSchemeAndHttpHost();
    }

    /**
     * Transform the resource into an array.
     *
     * @param $request
     *
     * @return \Illuminate\Support\Collection
     */
    public function toArray($request)
    {
        return $this->collection->pluck('resource.screen')->map(function ($item, $key) {
            return [
                'url_link' => $this->prefix . $item->url_link,
                'thumbnail_link' => $this->prefix . $item->thumbnail_link,
            ];
        });
    }
}
