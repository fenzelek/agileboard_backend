<?php

namespace App\Models\Other\Integration\TimeTracking;

use App\Models\Db\Integration\TimeTracking\Note as NoteModel;

class ActivityPeriod
{
    /**
     * @var int
     */
    private $timestamp;

    /**
     * @var NoteModel
     */
    private $note;

    /**
     * ActivityPeriod constructor.
     *
     * @param int $start_utc_timestamp Timestamp when it was started in UTC
     * @param NoteModel|null $note
     */
    public function __construct($start_utc_timestamp, NoteModel $note = null)
    {
        $this->timestamp = $start_utc_timestamp;
        $this->note = $note;
    }

    /**
     * Get timestamp.
     *
     * @return int
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * Get related note.
     *
     * @return NoteModel|null
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * Set timestamp.
     *
     * @param int $timestamp
     */
    public function setTimestamp(int $timestamp)
    {
        $this->timestamp = $timestamp;
    }
}
