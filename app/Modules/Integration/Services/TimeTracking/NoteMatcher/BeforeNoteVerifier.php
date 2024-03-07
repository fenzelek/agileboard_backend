<?php

namespace App\Modules\Integration\Services\TimeTracking\NoteMatcher;

use App\Models\Db\Integration\TimeTracking\Note;
use App\Models\Other\Integration\TimeTracking\Activity;
use Carbon\Carbon;

class BeforeNoteVerifier
{
    /**
     * Get verified before note.
     *
     * @param Activity $activity
     * @param Note|null $before_note
     *
     * @return Note|null
     */
    public function get(Activity $activity, Note $before_note = null)
    {
        if ($before_note && ! $this->shouldBeforeNoteBeUsed($activity, $before_note)) {
            $before_note = null;
        }

        return $before_note;
    }

    /**
     * Verify whether before note should be used for given activity.
     *
     * @param Activity $activity
     * @param Note $before_note
     *
     * @return bool
     */
    protected function shouldBeforeNoteBeUsed(Activity $activity, Note $before_note)
    {
        $note_time = Carbon::parse($before_note->utc_recorded_at, 'UTC');

        $activity_time = $activity->getUtcStartedAt();
        // it it's same day or difference is less than 4 hours then use this note
        if ($note_time->isSameDay($activity_time) || $note_time->diffInSeconds($activity_time) <= 4 * 3600) {
            return true;
        }

        return false;
    }
}
