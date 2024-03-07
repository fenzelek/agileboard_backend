<?php

namespace App\Http\Resources\TimeTracking;

use App\Http\Resources\AbstractResource;

class Activity extends AbstractResource
{
    public static $wrap;
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

        $data['created_at'] = $this->created_at ? $this->created_at->toDateTimeString() : null;
        $data['updated_at'] = $this->updated_at ? $this->updated_at->toDateTimeString() : null;

        $data['activity_level'] = $this->getActivityLevel();
        $data['locked'] = $this->isLocked();
        $data['isManual'] = $this->manual;
        $data['isLonger'] = $this->tracked > config('time_tracker.extended_time');

        return $data;
    }
}
