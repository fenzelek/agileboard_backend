<?php

namespace App\Http\Resources\TimeTracking;

use App\Http\Resources\AbstractResource;

class ActivitySummary extends AbstractResource
{
    /**
     * @inheritdoc
     */
    protected $fields = '*';

    /**
     * @inheritdoc
     */
    public function toArray($request)
    {
        $data = parent::toArray($request);

        $data['activity_level'] = $this->getActivitySummaryLevel();

        return $data;
    }
}
