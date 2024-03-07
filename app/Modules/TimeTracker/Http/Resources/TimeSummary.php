<?php

namespace App\Modules\TimeTracker\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class TimeSummary extends JsonResource
{
    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function toArray($request)
    {
        return [
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
