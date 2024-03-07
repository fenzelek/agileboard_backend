<?php

namespace App\Modules\Integration\Services\TimeTracking\NoteMatcher;

use App\Models\Db\Integration\TimeTracking\Note;
use App\Models\Other\Integration\TimeTracking\Activity;
use App\Models\Other\Integration\TimeTracking\ActivityPeriod;
use App\Modules\Integration\Services\TimeTracking\NoteMatcher\Contracts\Matcher;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class MultipleNotesMatcher implements Matcher
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
     * @var Collection
     */
    protected $during_notes;

    /**
     * SingleNoteMatcher constructor.
     *
     * @param Activity $activity
     * @param Note|null $before_note
     * @param Collection $during_notes
     */
    public function __construct(Activity $activity, Note $before_note = null, Collection $during_notes)
    {
        $this->activity = $activity;
        $this->before_note = $before_note;
        $this->during_notes = $during_notes;
    }

    /**
     * @inheritdoc
     */
    public function match()
    {
        return $this->getActivities($this->getTimes());
    }

    /**
     * Get times array.
     *
     * @return array[ActivityPeriod]
     */
    protected function getTimes()
    {
        $times = collect();
        // if we have before note we should use it
        if ($this->before_note) {
            $times->push(new ActivityPeriod(
                Carbon::parse($this->before_note->utc_recorded_at, 'UTC')->timestamp,
                $this->before_note
            ));
        }

        // now we add all the notes that were created during this activity
        $this->during_notes->each(function ($during_note) use ($times) {
            /** @var Note $during_note */
            $times->push(new ActivityPeriod(
                Carbon::parse($during_note->utc_recorded_at, 'UTC')->timestamp,
                $during_note
            ));
        });

        // make sure 1st one starts with activity timestamp (if we added before note it might be
        // for example 1 hour before this activity started)
        if ($this->activity->getUtcStartedAt()->timestamp > $times[0]->getTimestamp()) {
            $times[0]->setTimestamp($this->activity->getUtcStartedAt()->timestamp);
        }

        // if 1st entry is not the same as this activity beginning, we should add empty period (it
        // happens in case we didn't have before note and during note was not added at exact same
        // time this activity was started)
        if ($times[0]->getTimestamp() != $this->activity->getUtcStartedAt()->timestamp) {
            // if 1st entry was created up to 90 seconds from activity were are going to add,
            // use the 1st entry note in such case. This is because when we start tracker we don't
            // have any note, we have to add it after that so if we don't do it we might have
            // multiple short entries for each project before user fills the note
            if (isset($times[0]) && Carbon::createFromTimestamp($times[0]->getTimestamp(), 'UTC')
                    ->diffInSeconds($this->activity->getUtcStartedAt()) < 90) {
                $note = $times[0]->getNote();
            } else {
                $note = null;
            }

            $times->prepend(new ActivityPeriod(
                $this->activity->getUtcStartedAt()->timestamp,
                $note
            ));
        }

        // and if last entry is before the end, we should add one more extra time to be exact
        // as the end of this activity. It will only simplify implementation (we won't create
        // activity from last entry in times)
        if ($times[count($times) - 1]->getTimestamp() <
            $this->activity->getUtcFinishedAt()->timestamp) {
            $times->push(new ActivityPeriod(
                Carbon::parse($this->activity->getUtcFinishedAt(), 'UTC')->timestamp,
                null
            ));
        }

        return $times->all();
    }

    /**
     * Get split activities with notes assigned.
     *
     * @param array $times
     *
     * @return Collection[Activity]
     * @throws \Exception
     */
    protected function getActivities(array $times)
    {
        $activities = collect();

        $total_seconds = $times[count($times) - 1]->getTimestamp() - $times[0]->getTimestamp();
        $total_activity = $this->activity->getActivitySeconds();
        $left_activity = $total_activity;

        // it should not happen as we add additional entry at the end but if it did, just throw
        // exception to make sure nothing more will really happen
        if (count($times) == 1) {
            throw new \Exception('Too little entries in times array');
        }

        for ($i = 0, $c = count($times) - 1; $i < $c; ++$i) {
            // new note is based on note that we already have
            $new_activity = clone $this->activity;

            // but we set new start time
            $new_activity->setUtcStartedAt(
                Carbon::createFromTimestamp($times[$i]->getTimestamp(), 'UTC')
            );

            // we calculate tracked time
            $new_activity->setTrackedSeconds(
                $times[$i + 1]->getTimestamp() - $times[$i]->getTimestamp()
            );

            // we assign note if it's set
            $new_activity->setTimeTrackingNoteId(($note =
                $times[$i]->getNote()) ? $note->id : null);

            // and we set user activity during this activity
            $this->setUserActivity($new_activity, $total_seconds, $total_activity, $left_activity);

            // and we need to save how much user activity we already used
            $left_activity -= $new_activity->getActivitySeconds();

            $activities->push($new_activity);
        }

        return $activities;
    }

    /**
     * Set user activity for given activity.
     *
     * @param Activity $activity
     * @param int $total_seconds
     * @param int $total_activity
     * @param int $left_activity
     */
    protected function setUserActivity(Activity $activity, $total_seconds, $total_activity, $left_activity)
    {
        // first we calculate user activity for this activity - we assume it's proportional
        $user_activity = round($total_activity * $activity->getTrackedSeconds()
            / (float) $total_seconds);

        // in case calculated activity is greater than left activity we will use left activity
        if ($user_activity > $left_activity) {
            $user_activity = $left_activity;
        }

        // also in case calculated activity is greater than tracked time, we should set it to
        // tracked time (it might happens because user activity is int so some values are rounded)
        if ($user_activity > $activity->getTrackedSeconds()) {
            $user_activity = $activity->getTrackedSeconds();
        }

        // now we can set user activity
        $activity->setActivitySeconds($user_activity);
    }
}
