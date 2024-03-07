<?php

namespace App\Modules\Integration\Services\ManualActivityTools;

use App\Models\Db\Integration\Integration;
use App\Models\Db\Integration\TimeTracking\Activity;
use App\Models\Db\Integration\TimeTracking\ManualActivityHistory;
use App\Modules\Integration\Services\Contracts\ManualActivityDataProvider;

class ManualActivityTimeConverter
{
    private FreeTimeSlotSearch $free_time_slot_search;
    private ManualActivityCreator $activity_creator;

    private array $activities;

    public function __construct(FreeTimeSlotSearch $free_time_slot_search, ManualActivityCreator $activity_creator)
    {
        $this->free_time_slot_search = $free_time_slot_search;
        $this->activity_creator = $activity_creator;

        $this->activities = [];
    }

    /**
     * @param ManualActivityDataProvider $activity_data_provider
     *
     * @return Activity[]
     */
    public function convert(ManualActivityDataProvider $activity_data_provider, Integration $integration, ManualActivityHistory $history)
    {
        $free_slots = $this->free_time_slot_search->lookup($activity_data_provider);

        $free_slots->each(function ($slot) use ($activity_data_provider, $integration, $history) {
            $this->activities [] = $this->activity_creator->create($slot, $activity_data_provider, $integration, $history);
        });

        return $this->activities;
    }
}
