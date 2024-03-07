<?php

namespace Tests\Helpers;

use App\Models\Other\Integration\TimeTracking\Activity;
use Carbon\Carbon;

trait ActivityHelper
{
    /**
     * Create activity.
     *
     * @param array $fields
     * @param string|null $note
     *
     * @return Activity
     */
    protected function createActivity(array $fields, $note = null)
    {
        return new Activity(
            $fields['id'],
            $fields['project_id'],
            $fields['user_id'],
            $fields['tracked'],
            $fields['overall'],
            Carbon::parse($fields['starts_at'], 'UTC'),
            $note
        );
    }

    /**
     * Get some activity fields when no need to set specific values.
     *
     * @return array
     */
    protected function getActivityFields()
    {
        return [
            'id' => 4341231,
            'time_slot' => '2017-08-01 08:10:00',
            'starts_at' => '2017-08-01 08:00:00',
            'user_id' => 453112,
            'project_id' => 12321,
            'task_id' => 421,
            'keyboard' => 3,
            'mouse' => 18,
            'overall' => 301,
            'tracked' => 600,
            'paid' => false,
        ];
    }

    /**
     * Verify whether given activity matches given field and note.
     *
     * @param array $fields
     * @param Activity $activity
     * @param int|null $note_id
     */
    protected function verifyActivityFields(array $fields, Activity $activity, $note_id = null)
    {
        $this->assertSame($fields['id'], $activity->getExternalId());
        $this->assertSame($fields['project_id'], $activity->getExternalProjectId());
        $this->assertSame($fields['user_id'], $activity->getExternalUserId());
        $this->assertSame($fields['tracked'], $activity->getTrackedSeconds());
        $this->assertSame($fields['overall'], $activity->getActivitySeconds());
        $this->assertSame(
            Carbon::parse($fields['starts_at'], 'UTC')->toDateTimeString(),
            $activity->getUtcStartedAt()->toDateTimeString()
        );
        $this->assertSame(null, $activity->getNote());
        $this->assertSame(Carbon::parse($fields['starts_at'], 'UTC')->addSeconds($fields['tracked'])
            ->toDateTimeString(), $activity->getUtcFinishedAt()->toDateTimeString());
        $this->assertSame($note_id, $activity->getTimeTrackingNoteId());
    }
}
