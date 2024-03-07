<?php

namespace App\Http\Resources;

class ProjectSimply extends AbstractResource
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
    ];

    /**
     * @inheritdoc
     */
    protected $ignoredRelationships = ['timeTrackingSummary'];

    public function toArray($request)
    {
        $data = parent::toArray($request);

        $data['closed_at'] = $this->closed_at ? $this->closed_at->toDateTimeString() : null;
        $data['created_at'] = $this->created_at ? $this->created_at->toDateTimeString() : null;
        $data['updated_at'] = $this->updated_at ? $this->updated_at->toDateTimeString() : null;
        $data['deleted_at'] = $this->deleted_at ? $this->deleted_at->toDateTimeString() : null;

        return $data;
    }
}
