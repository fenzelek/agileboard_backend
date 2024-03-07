<?php

namespace App\Modules\Integration\Services\TimeTracking\NoteMatcher;

use App\Models\Db\Integration\TimeTracking\Note;
use App\Models\Other\Integration\TimeTracking\Activity;
use App\Modules\Integration\Services\TimeTracking\NoteMatcher\Contracts\Matcher;

class SingleNoteMatcher implements Matcher
{
    /**
     * @var Activity
     */
    protected $activity;

    /**
     * @var Note
     */
    protected $before_note;

    /**
     * SingleNoteMatcher constructor.
     *
     * @param Activity $activity
     * @param Note|null $before_note
     */
    public function __construct(Activity $activity, Note $before_note = null)
    {
        $this->activity = $activity;
        $this->before_note = $before_note;
    }

    /**
     * @inheritdoc
     */
    public function match()
    {
        // no note, impossible to set note for this activity
        if (! $this->before_note) {
            return collect([$this->activity]);
        }

        // otherwise we can set note for this activity
        $activity = clone $this->activity;
        $activity->setTimeTrackingNoteId($this->before_note->id);

        // and now we again return activity but this time it will have assigned note
        return collect([$activity]);
    }
}
