<?php

namespace App\Models\Other\Integration\TimeTracking;

use Carbon\Carbon;

class Note
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
     * Description in external system.
     *
     * @var string
     */
    private $content;

    /**
     * Date/time when this note was created.
     *
     * @var Carbon
     */
    private $utc_recorded_at;

    /**
     * Note constructor.
     *
     * @param string $external_id
     * @param string $external_project_id
     * @param string $external_user_id
     * @param string $content
     * @param Carbon $utc_recorded_at
     */
    public function __construct(
        $external_id,
        $external_project_id,
        $external_user_id,
        $content,
        Carbon $utc_recorded_at
    ) {
        $this->external_id = $external_id;
        $this->external_project_id = $external_project_id;
        $this->external_user_id = $external_user_id;
        $this->content = $content;
        $this->utc_recorded_at = $utc_recorded_at;
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
     * Get content.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Get date/time when it was created.
     *
     * @return Carbon
     */
    public function getUtcRecordedAt()
    {
        return clone $this->utc_recorded_at;
    }
}
