<?php

namespace App\Modules\TimeTracker\Http\Resources;

use App\Models\Db\Integration\TimeTracking\Activity;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Activity
 */
class ActivityResource extends JsonResource
{
    public function toArray($request)
    {
        $data = [
            'id' => $this->id,
            'utc_started_at' => $this->utc_started_at->toDateTimeString(),
            'utc_finished_at' => $this->utc_finished_at->toDateTimeString(),
            'tracked' => $this->tracked,
            'activity' => $this->activity,
            'activity_level' => $this->activity_level,
            'comment' => $this->comment,
            'ticket' => $this->ticket,
            'project_name' => $this->project->name,
        ];

        $prefix = config('filesystems.disks.azure.url');

        $data['screens'] = $this->getScreens()->map(function (\App\Models\Db\TimeTracker\ActivityFrameScreen $item) use ($prefix) {
            return [
                'thumb' => $prefix . $item->getRelation('screen')->thumbnail_link,
                'url' => $prefix . $item->getRelation('screen')->url_link,
            ];
        });

        return $data;
    }
}
