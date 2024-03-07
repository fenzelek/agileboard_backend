<?php

namespace App\Http\Resources;

class TicketWithDetails extends AbstractResource
{
    /**
     * @inheritdoc
     */
    protected $fields = '*';

    /**
     * @inheritdoc
     */
    protected $ignoredRelationships = ['timeTrackingGeneralSummary', 'project'];

    /**
     * @inheritdoc
     */
    public function toArray($request)
    {
        $data = parent::toArray($request);

        $data['sprint_name'] = $this->sprint->name ?? null;
        $data['created_at'] = $this->created_at ? $this->created_at->toDateTimeString() : null;
        $data['updated_at'] = $this->updated_at ? $this->updated_at->toDateTimeString() : null;
        $data['deleted_at'] = $this->deleted_at ? $this->deleted_at->toDateTimeString() : null;
        $data['scheduled_time_start'] = $this->scheduled_time_start ? $this->scheduled_time_start->toDateTimeString() : null;
        $data['scheduled_time_end'] = $this->scheduled_time_end ? $this->scheduled_time_end->toDateTimeString() : null;

        if ($this->activity_permission || $this->project->time_tracking_visible_for_clients) {
            $time_tracking_summary = $this->timeTrackingGeneralSummary->first();

            $data['stats']['data'] = [
                'tracked_summary' => (int) ($time_tracking_summary ? $time_tracking_summary->tracked_sum : 0),
                'activity_summary' => (int) ($time_tracking_summary ? $time_tracking_summary->activity_sum : 0),
            ];

            $data['stats']['data']['activity_level'] =
                activity_level(
                    $data['stats']['data']['tracked_summary'],
                    $data['stats']['data']['activity_summary']
                );

            $data['stats']['data']['time_usage'] =
                activity_level(
                    $this->estimate_time,
                    $data['stats']['data']['tracked_summary']
                );
        }

        return $data;
    }
}
