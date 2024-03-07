<?php

namespace App\Modules\TimeTracker\Http\Resources;

use App\Modules\TimeTracker\Models\ProcessResult;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class AddFramesResource extends JsonResource
{
    /**
     * Create a new resource instance.
     *
     * @param mixed $resource
     *
     * @return void
     */
    public function __construct($resource, ProcessResult $reject_frames)
    {
        parent::__construct($resource);

        $this->resource = $resource;
        $this->reject_frames = $reject_frames;
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function toArray($request)
    {
        return [
            'reject_frames' => RejectFrames::make($this->reject_frames->getRejectedFrames()),
            'companies' => $this->groupByCompanies($this->resource),
            'projects' => $this->groupByProjects($this->resource),
            'tickets' => $this->groupByTickers($this->resource),
        ];
    }

    private function groupByCompanies(Collection $resources): Collection
    {
        return $resources->groupBy('company_id')->map(function ($group, $id) {
            return [
                'id' => $id,
                'tracked' => $group->sum('tracked'),
            ];
        })->pluck('tracked', 'id');
    }

    private function groupByProjects(Collection $resources): Collection
    {
        return $resources->groupBy(['company_id', 'project_id'])->map->map(function ($group) {
            return [
                'id' => implode(':', [$group->max('company_id'), $group->max('project_id')]),
                'tracked' => $group->sum('tracked'),
            ];
        })->flatten(1)->pluck('tracked', 'id');
    }

    private function groupByTickers(Collection $resources): Collection
    {
        return $resources->groupBy([
            'company_id',
            'project_id',
            'ticket_id',
        ])->map->map(function ($group) {
            return $group->map(function ($group) {
                return [
                    'id' => implode(':', [
                        $group->max('company_id'),
                        $group->max('project_id'),
                        $group->max('ticket_id'),
                    ]),
                    'tracked' => $group->sum('tracked'),
                ];
            });
        })->flatten(2)->pluck('tracked', 'id');
    }
}
