<?php

namespace App\Modules\TimeTracker\Http\Resources;

use App\Modules\TimeTracker\DTO\AddFrame;
use Illuminate\Http\Resources\Json\ResourceCollection;

class RejectFrames extends ResourceCollection
{
    /**
     * Enable wrap for this resource.
     *
     * @param $resource
     */
    public function __construct($resource)
    {
        parent::__construct($resource);
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
        return $this->collection->map(function ($item, $key) {
            /** @var AddFrame $item */
            return [
                'from' => $item->getFrom(),
                'to' => $item->getTo(),
                'companyId' => $item->getCompanyId(),
                'projectId' => $item->getProjectId(),
                'activity' => $item->getActivity(),
                'taskId' => $item->getTaskId(),
            ];
        });
    }
}
