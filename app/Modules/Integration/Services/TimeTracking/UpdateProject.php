<?php

namespace App\Modules\Integration\Services\TimeTracking;

use App\Models\Db\Integration\TimeTracking\Project as TimeTrackingProject;
use App\Models\Db\User;
use App\Modules\Integration\Services\TimeTracking\Processor\ActivitiesProcessor;
use DB;

class UpdateProject
{
    /**
     * @var TimeTrackingProject
     */
    private $time_tracking_project;
    /**
     * @var ActivitiesProcessor
     */
    private $activities_processor;

    /**
     * UpdateProject constructor.
     *
     * @param TimeTrackingProject $time_tracking_project
     * @param ActivitiesProcessor $activities_processor
     */
    public function __construct(TimeTrackingProject $time_tracking_project, ActivitiesProcessor $activities_processor)
    {
        $this->time_tracking_project = $time_tracking_project;
        $this->activities_processor = $activities_processor;
    }

    /**
     * Update project for existing time tracking project and also update all its activities.
     *
     * @param int $time_tracking_project_id
     * @param int $project_id
     * @param User $user
     *
     * @return TimeTrackingProject
     */
    public function run($time_tracking_project_id, $project_id, User $user)
    {
        return DB::transaction(function () use ($time_tracking_project_id, $project_id, $user) {
            $project = $this->time_tracking_project
                ->whereHas('integration', function ($q) use ($user) {
                    $q->where('company_id', $user->getSelectedCompanyId());
                })->findOrFail($time_tracking_project_id);

            // first update project
            $project->update(['project_id' => $project_id]);

            // then update all its activities with same project - we don't care here if activity is
            // locked or not. Locked is for regular user only and this action is run by admin or
            // owner so they know what they are doing
            $project->activities()->update(['project_id' => $project_id]);

            // finally for all its activities update ticket
            $activities = $project->activities()->with('project', 'timeTrackingNote')->get();
            $activities->each(function ($activity) {
                $ticket = $this->activities_processor->findTicket($activity);

                // in case someone changed project completely in case no ticket was found
                // we want to make sure we don't have invalid ticket assigned so we remove
                // ticket assignment in case we didn't find any
                $activity->update(['ticket_id' => $ticket ? $ticket->id : null]);
            });

            return $project;
        });
    }
}
