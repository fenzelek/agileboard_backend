<?php

namespace App\Modules\Integration\Services\TimeTracking\Processor;

use App\Models\Db\Integration\Integration;
use App\Models\Other\Integration\TimeTracking\Activity as TimeTrackingHelperActivity;
use App\Modules\Integration\Services\TimeTracking\NoteMatcher\NoteMatcher;
use Illuminate\Support\Collection;
use App\Models\Db\Integration\TimeTracking\Activity;
use App\Models\Db\Ticket;
use App\Models\Db\Integration\TimeTracking\Note;

class ActivitiesProcessor
{
    /**
     * @var NoteMatcher
     */
    protected $note_matcher;

    /**
     * @var Activity
     */
    protected $activity;

    /**
     * @var Ticket
     */
    protected $ticket;

    /**
     * @var Note
     */
    protected $note;

    /**
     * ActivitiesProcessor constructor.
     *
     * @param NoteMatcher $note_matcher
     * @param Activity $activity
     * @param Ticket $ticket
     * @param Note $note
     */
    public function __construct(
        NoteMatcher $note_matcher,
        Activity $activity,
        Ticket $ticket,
        Note $note
    ) {
        $this->note_matcher = $note_matcher;
        $this->activity = $activity;
        $this->ticket = $ticket;
        $this->note = $note;
    }

    /**
     * Save activities.
     *
     * @param Integration $integration
     * @param Collection $activities
     * @param Collection $user_mappings
     * @param Collection $project_mappings
     */
    public function save(
        Integration $integration,
        Collection $activities,
        Collection $user_mappings,
        Collection $project_mappings
    ) {
        $activities->each(function ($activity) use ($integration, $user_mappings, $project_mappings) {
            $this->processActivity($activity, $integration, $user_mappings, $project_mappings);
        });
    }

    /**
     * Find matching ticket for given activity.
     *
     * @param Activity $activity_model
     *
     * @return Ticket|null
     */
    public function findTicket(Activity $activity_model)
    {
        // if no note or no project we won't find matching ticket
        if (! $activity_model->timeTrackingNote || ! $activity_model->project) {
            return null;
        }

        $short_name = $activity_model->project->short_name;

        preg_match(
            '#(' . $short_name . '\-(\d+))#i',
            $activity_model->timeTrackingNote->content,
            $matches
        );

        if ($matches && ! empty($matches[1])) {
            $ticket = $this->ticket->where('project_id', $activity_model->project->id)
                ->where('title', $matches[0])->first();
            if ($ticket) {
                return $ticket;
            }
        }

        return null;
    }

    /**
     * Process single activity.
     *
     * @param TimeTrackingHelperActivity $activity
     * @param Integration $integration
     * @param Collection $user_mappings
     * @param Collection $project_mappings
     *
     * @return bool
     * @throws \Exception
     */
    protected function processActivity(
        TimeTrackingHelperActivity $activity,
        Integration $integration,
        Collection $user_mappings,
        Collection $project_mappings
    ) {
        /** @var TimeTrackingHelperActivity $activity */
        $activity_model = $this->activity->where('integration_id', $integration->id)
            ->where('external_activity_id', $activity->getExternalId())->first();

        // it shouldn't happen in standard situations but in case there were some time
        // tracking issues and we want to restart from some period in past we might get some
        // activities again but we don't want to update them
        if ($activity_model) {
            return true;
        }

        // if note is assigned directly to activity probably workflow should be a bit
        // different. But for now we don't have such handler so we can raise exception
        if ($activity->getNote() !== null) {
            throw new \Exception('Handling direct notes not implemented yet');
        }

        $this->activitiesWithNotes($activity, $integration)->reject(function ($activity) {
            return $activity->getTrackedSeconds() == 0 && $activity->getActivitySeconds() == 0;
        })->each(function (TimeTrackingHelperActivity $activity) use ($integration, $user_mappings, $project_mappings) {
            $this->saveActivity($activity, $integration, $user_mappings, $project_mappings);
        });
    }

    /**
     * Save single activity.
     *
     * @param TimeTrackingHelperActivity $activity
     * @param Integration $integration
     * @param Collection $user_mappings
     * @param Collection $project_mappings
     */
    protected function saveActivity(
        TimeTrackingHelperActivity $activity,
        Integration $integration,
        Collection $user_mappings,
        Collection $project_mappings
    ) {
        // first we create new activity
        $activity_model = $this->activity->create([
            'integration_id' => $integration->id,
            'external_activity_id' => $activity->getExternalId(),
            'time_tracking_user_id' => $user_mappings->get($activity->getExternalUserId()),
            'time_tracking_project_id' => $project_mappings->get($activity->getExternalProjectId()),
            'time_tracking_note_id' => $activity->getTimeTrackingNoteId(),
            'user_id' => null,
            'project_id' => null,
            'ticket_id' => null,
            'utc_started_at' => $activity->getUtcStartedAt(),
            'utc_finished_at' => $activity->getUtcFinishedAt(),
            'tracked' => $activity->getTrackedSeconds(),
            'activity' => $activity->getActivitySeconds(),
            'comment' => '',
        ]);

        // but now we want to match user and project
        $activity_model->fill([
            'user_id' => ($tracking_user = $activity_model->timeTrackingUser)
                ? $tracking_user->user_id : null,
            'project_id' => ($project = $activity_model->timeTrackingProject)
                ? $project->project_id : null,
        ]);

        // and when we have set project_id now we can try to find ticket
        $activity_model->fill([
            'ticket_id' => ($ticket = $this->findTicket($activity_model))
                ? $ticket->id : null,
        ]);

        $activity_model->save();
    }

    /**
     * Find matching notes for given activity. As result multiple activities might be returned.
     *
     * @param TimeTrackingHelperActivity $activity
     * @param Integration $integration
     *
     * @return Collection
     */
    private function activitiesWithNotes(TimeTrackingHelperActivity $activity, Integration $integration)
    {
        /** @var Note $before_note */
        $before_note = $this->note->where('integration_id', $integration->id)
            ->where('external_project_id', $activity->getExternalProjectId())
            ->where('external_user_id', $activity->getExternalUserId())
            ->where('utc_recorded_at', '<', $activity->getUtcStartedAt()->toDateTimeString())
            ->latest('utc_recorded_at')->first();

        $during_notes = $this->note->where('integration_id', $integration->id)
            ->where('external_project_id', $activity->getExternalProjectId())
            ->where('external_user_id', $activity->getExternalUserId())
            ->where('utc_recorded_at', '>=', $activity->getUtcStartedAt()->toDateTimeString())
            ->where('utc_recorded_at', '<=', $activity->getUtcFinishedAt()->toDateTimeString())
            ->oldest('utc_recorded_at')->get();

        return $this->note_matcher->find($activity, $before_note, $during_notes);
    }
}
