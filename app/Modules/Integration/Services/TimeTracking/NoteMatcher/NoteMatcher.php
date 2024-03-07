<?php

namespace App\Modules\Integration\Services\TimeTracking\NoteMatcher;

use App\Models\Db\Integration\TimeTracking\Note;
use App\Models\Other\Integration\TimeTracking\Activity;
use Illuminate\Support\Collection;

class NoteMatcher
{
    /**
     * @var BeforeNoteVerifier
     */
    protected $before_note_verifier;

    /**
     * NoteMatcher constructor.
     *
     * @param BeforeNoteVerifier $before_note_verifier
     */
    public function __construct(BeforeNoteVerifier $before_note_verifier)
    {
        $this->before_note_verifier = $before_note_verifier;
    }

    /**
     * Match note to activity. It will return collection of activities because during assigning
     * notes it might happen activity will be split into multiple activities.
     *
     * @param Activity $activity
     * @param Note|null $before_note
     * @param Collection $during_notes
     *
     * @return Collection
     */
    public function find(Activity $activity, Note $before_note = null, Collection $during_notes)
    {
        $before_note = $this->before_note_verifier->get($activity, $before_note);

        switch ($during_notes->count()) {
            case 0;
                $assigner = new SingleNoteMatcher($activity, $before_note);
                break;
            default:
                $assigner = new MultipleNotesMatcher($activity, $before_note, $during_notes);
                break;
        }

        return $assigner->match();
    }
}
