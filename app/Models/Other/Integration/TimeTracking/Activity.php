<?php

namespace App\Models\Other\Integration\TimeTracking;

use Carbon\Carbon;

class Activity
{
    /**
     * Id in external system.
     *
     * @var string
     */
    private $external_id;

    /**
     * Project id in external system.
     *
     * @var string
     */
    private $external_project_id;

    /**
     * User id in external system.
     *
     * @var string
     */
    private $external_user_id;

    /**
     * Activity tracked seconds.
     *
     * @var int
     */
    private $tracked_seconds;

    /**
     * User activity during this activity in seconds.
     *
     * @var int
     */
    private $activity_seconds;

    /**
     * Date/time when this activity started.
     *
     * @var Carbon
     */
    private $utc_started_at;

    /**
     * Note assigned to this activity. It should contain description of activity (and not id of
     * note).
     *
     * @var string|null
     */
    private $note;

    /**
     * Id of time tracking note.
     *
     * @var int|null
     */
    private $time_tracking_note_id;

    /**
     * Activity constructor.
     *
     * @param string $external_id
     * @param string $external_project_id
     * @param string $external_user_id
     * @param int $tracked_seconds
     * @param int $activity_seconds
     * @param Carbon $utc_started_at
     * @param string $note
     */
    public function __construct(
        $external_id,
        $external_project_id,
        $external_user_id,
        $tracked_seconds,
        $activity_seconds,
        Carbon $utc_started_at,
        $note = null
    ) {
        $this->external_id = $external_id;
        $this->external_project_id = $external_project_id;
        $this->external_user_id = $external_user_id;
        $this->tracked_seconds = $tracked_seconds;
        $this->activity_seconds = $activity_seconds;
        $this->utc_started_at = $utc_started_at;
        $this->note = $note;
    }

    /**
     * Set time tracking note id.
     *
     * @param int $time_tracking_note_id
     */
    public function setTimeTrackingNoteId($time_tracking_note_id)
    {
        $this->time_tracking_note_id = $time_tracking_note_id;
    }

    /**
     * Get id in external system.
     *
     * @return string
     */
    public function getExternalId()
    {
        return $this->external_id;
    }

    /**
     * Get project id in external system.
     *
     * @return string
     */
    public function getExternalProjectId()
    {
        return $this->external_project_id;
    }

    /**
     * Get user id in external system.
     *
     * @return string
     */
    public function getExternalUserId()
    {
        return $this->external_user_id;
    }

    /**
     * Get number of tracked seconds.
     *
     * @return int
     */
    public function getTrackedSeconds()
    {
        return $this->tracked_seconds;
    }

    /**
     * Ger number of user activity seconds.
     *
     * @return int
     */
    public function getActivitySeconds()
    {
        return $this->activity_seconds;
    }

    /**
     * Get date/time when this activity was started.
     *
     * @return Carbon
     */
    public function getUtcStartedAt()
    {
        return clone $this->utc_started_at;
    }

    /**
     * Get note content.
     *
     * @return string|null
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * Get date/time when this activity was finished.
     *
     * @return Carbon
     */
    public function getUtcFinishedAt()
    {
        return (clone $this->utc_started_at)->addSeconds($this->getTrackedSeconds());
    }

    /**
     * Set tracked seconds.
     *
     * @param int $tracked_seconds
     */
    public function setTrackedSeconds(int $tracked_seconds)
    {
        $this->tracked_seconds = $tracked_seconds;
    }

    /**
     * Set activity seconds.
     *
     * @param int $activity_seconds
     */
    public function setActivitySeconds(int $activity_seconds)
    {
        $this->activity_seconds = $activity_seconds;
    }

    /**
     * Set date time when it was started.
     *
     * @param Carbon $utc_started_at
     */
    public function setUtcStartedAt(Carbon $utc_started_at)
    {
        $this->utc_started_at = $utc_started_at;
    }

    /**
     * Get time tracking note id.
     *
     * @return int|null
     */
    public function getTimeTrackingNoteId()
    {
        return $this->time_tracking_note_id;
    }
}
