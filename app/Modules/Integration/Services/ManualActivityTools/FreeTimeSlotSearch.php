<?php

namespace App\Modules\Integration\Services\ManualActivityTools;

use App\Modules\Integration\Models\ActivityFromToDTO;
use App\Modules\Integration\Services\Contracts\ManualActivityDataProvider;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class FreeTimeSlotSearch
{
    private TimeTrackerActivitySearch $search;
    private array $activities;
    private Collection $free_slots;

    public function __construct(TimeTrackerActivitySearch $search)
    {
        $this->search = $search;
        $this->activities = [];
        $this->free_slots = Collection::make();
    }

    /**
     * @param ManualActivityDataProvider $activity_data_provider
     *
     * @return Collection
     */
    public function lookup(ManualActivityDataProvider $activity_data_provider): Collection
    {
        $this->activities = $this->search->lookupOverLap($activity_data_provider);

        $search_range = new ActivityFromToDTO(
            $activity_data_provider->getFrom(),
            $activity_data_provider->getTo()
        );

        if (! count($this->activities)) {
            return $this->free_slots->push($search_range);
        }

        $search_range = $this->cutSearchRangeToBounceActivities($search_range);

        return $this->getFreeSlots($search_range);
    }

    protected function cutSearchRangeToBounceActivities(ActivityFromToDTO $activity_dto): ActivityFromToDTO
    {
        $last_activity = Arr::last($this->activities);
        $first_activity = Arr::first($this->activities);

        if ($first_activity->utc_started_at <= $activity_dto->from
            && $first_activity->utc_finished_at < $activity_dto->to) {
            $activity_dto->from = $first_activity->utc_finished_at;
            array_shift($this->activities);
        }
        if ($last_activity->utc_started_at > $activity_dto->from
            && $last_activity->utc_finished_at >= $activity_dto->to) {
            $activity_dto->to = $last_activity->utc_started_at;
            array_pop($this->activities);
        }

        return $activity_dto;
    }

    private function getFreeSlots(ActivityFromToDTO $search_range): Collection
    {
        Collection::make($this->activities)->each(function ($activity) use ($search_range) {
            if ($search_range->from < $activity->utc_started_at) {
                $free_range = new ActivityFromToDTO($search_range->from, $activity->utc_started_at);
                $this->free_slots->push($free_range);
            }
            $search_range->from = $activity->utc_finished_at;
        });

        // processing last cut
        if ($search_range->from < $search_range->to) {
            $free_range = new ActivityFromToDTO($search_range->from, $search_range->to);
            $this->free_slots->push($free_range);
        }

        return $this->free_slots;
    }
}
