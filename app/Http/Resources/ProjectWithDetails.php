<?php

namespace App\Http\Resources;

class ProjectWithDetails extends AbstractResource
{
    /**
     * @inheritdoc
     */
    protected $fields = [
        'id',
        'company_id',
        'name',
        'short_name',
        'created_tickets',
        'time_tracking_visible_for_clients',
        'language',
        'color',
        'closed_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * @inheritdoc
     */
//    protected $ignoredRelationships = ['tracking_summary'];

    /**
     * @inheritdoc
     */
    public function toArray($request)
    {
        $data = parent::toArray($request);

        $data['editable_short_name'] = $this->hasEditableShortName();
        $data['stats']['data'] = [
            'total_estimate_time' => $this->total_estimate_time,
            'non_todo_estimate_time' => $this->non_todo_estimate_time,
            'not_estimated_tickets_count' => $this->not_estimated_tickets_count,
            'not_assigned_tickets_count' => $this->not_assigned_tickets_count,
            'tracked' => $this->tracked,
            'activity' => $this->activity,
            'activity_level' => activity_level($this->tracked, $this->activity),
            'time_tracking_summary' => [
                'data' => $this->tracking_summary,
            ],
        ];

        $data['ticket_scheduled_dates_with_time'] = $this->ticket_scheduled_dates_with_time;
        $data['closed_at'] = $this->closed_at ? $this->closed_at->toDateTimeString() : null;
        $data['created_at'] = $this->created_at ? $this->created_at->toDateTimeString() : null;
        $data['updated_at'] = $this->updated_at ? $this->updated_at->toDateTimeString() : null;
        $data['deleted_at'] = $this->deleted_at ? $this->deleted_at->toDateTimeString() : null;

        return $data;
    }
}
