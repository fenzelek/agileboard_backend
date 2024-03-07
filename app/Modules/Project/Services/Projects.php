<?php

namespace App\Modules\Project\Services;

use App\Models\Db\Company;
use App\Models\Db\ModuleMod;
use App\Models\Db\Status;
use App\Models\Db\User as UserModel;
use App\Models\Other\ModuleType;
use App\Modules\Agile\Services\TicketClone;
use App\Modules\Project\Events\AssignedEvent;
use App\Modules\Project\Http\Requests\ProjectClone;
use App\Modules\Project\Http\Requests\ProjectUpdate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use App\Models\Db\User;
use App\Models\Db\Project;
use Illuminate\Support\Collection;
use Illuminate\Database\Connection as DBConnection;
use Illuminate\Support\Facades\DB;

class Projects
{
    protected Project $project;
    protected Status $status;
    private DBConnection $db;
    private array $map_of_cloned_relations;
    private File $file_service;
    private TicketClone $ticket_clone_service;

    public function __construct(
        Project $project,
        Status $status,
        DBConnection $db,
        File $file_service,
        TicketClone $ticket_clone_service
    ) {
        $this->project = $project;
        $this->status = $status;
        $this->db = $db;
        $this->file_service = $file_service;
        $this->ticket_clone_service = $ticket_clone_service;
    }

    public function filterProjects(Request $request, User $user)
    {
        $status = $request->input('status');
        // Get all projects in company
        $projects = Project::inCompany($user)->orderBy('id');

        if ($search = $request->input('search')) {
            $projects->where('name', 'LIKE', '%' . $search . '%');
        }

        // Admin / Owner
        if (($user->isAdmin() || $user->isOwner()) && $request->input('has_access') != '1') {
            // Admin or owner want to see only closed projects
            if ($status == 'closed') {
                return $projects->whereNotNull('closed_at');
            }
            // Admin or owner want to see only opened projects
            if ($status == 'opened') {
                return $projects->whereNull('closed_at');
            }
        } // if neither admin nor owner get all open projects for this company and user
        else {
            return $projects->whereHas('users', function ($query) use ($user) {
                $query->where('project_user.user_id', $user->id);
            })->whereNull('closed_at');
        }

        // If admin/owner don't use filter or use 'all' return all projects in company
        return $projects;
    }

    /**
     * Add details into project.
     */
    public function addDetails(Project $project, User $user): Project
    {
        $manager_in_project = $user->managerInProject($project);

        $project->total_estimate_time =
            $this->getTotalEstimate($project, $user, $manager_in_project);
        $project->non_todo_estimate_time =
            $this->getNonTodoEstimate($project, $user, $manager_in_project);
        $project->not_estimated_tickets_count =
            $this->getNotEstimatedTicketsCount($project, $user, $manager_in_project);
        $project->not_assigned_tickets_count = (int) $project->tickets()
            ->whereNull('assigned_id')->count();
        $project->tracked = $this->timeTrackedSummary($project, $user, $manager_in_project);
        $project->activity = $this->timeActivitySummary($project, $user, $manager_in_project);
        $project->setRelation('tracking_summary', $this->getTimeTrackingSummary($project, $user, $manager_in_project));

        return $project;
    }

    public function cantAddProjects(int $company_id, Project $project): bool
    {
        $setting = Company::findOrFail($company_id)->appSettings(ModuleType::PROJECTS_MULTIPLE_PROJECTS);

        if ($setting == ModuleMod::UNLIMITED) {
            return false;
        }

        if ($setting > $project->where('company_id', $company_id)->count()) {
            return false;
        }

        return true;
    }

    public function checkTooManyUsers(int $company_id, int $count): bool
    {
        $setting = Company::findOrFail($company_id)->appSettings(ModuleType::PROJECTS_USERS_IN_PROJECT);

        if ($setting == ModuleMod::UNLIMITED) {
            return false;
        }

        if ($setting > $count) {
            return false;
        }

        return true;
    }

    /**
     * @throws \Exception
     */
    public function cloneProject(Project $base_project, ProjectClone $request): Project
    {
        $relations = ['sprints', 'stories', 'statuses'];
        /** @var Project $project */
        $project = $base_project->replicate();

        try {
            $this->db->beginTransaction();

            $project->name = $request->input('name');
            $project->short_name = $request->input('short_name');
            $project->push();

            foreach ($base_project->users as $user) {
                $project->users()->attach($user, ['role_id' => $user->pivot->role_id]);
            }

            foreach ($relations as $relation) {
                $this->cloneRelation($base_project, $relation, $project);
            }

            $this->cloneStatusForCalendar($base_project, $project);
            $this->clonePermission($base_project, $project);
            $this->cloneTickets($base_project, $project);
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }

        return $project;
    }

    public function updateProject(ProjectUpdate $request, Project $project): void
    {
        DB::transaction(function () use ($request, $project) {
            $fields = [
                'name' => $request->input('name'),
                'time_tracking_visible_for_clients' => $request->input('time_tracking_visible_for_clients'),
                'status_for_calendar_id' => $request->input('status_for_calendar_id'),
                'language' => $request->input('language', 'en'),
                'email_notification_enabled' => $request->input('email_notification_enabled', 0),
                'slack_notification_enabled' => $request->input('slack_notification_enabled', 0),
                'slack_webhook_url' => $request->input('slack_webhook_url'),
                'slack_channel' => $request->input('slack_channel'),
                'color' => $request->input('color'),
                'ticket_scheduled_dates_with_time' => $request->input('ticket_scheduled_dates_with_time'),
            ];
            if ($project->hasEditableShortName()) {
                $fields['short_name'] = $request->input('short_name');
            }

            $project->update($fields);
            $changes = $project->users()->sync($request->mappedUsers());

            foreach ($changes['attached'] as $user_id) {
                event(new AssignedEvent($project, UserModel::findOrFail($user_id)));
            }
        });
    }

    /**
     * Get total estimate of tickets in seconds.
     */
    protected function getTotalEstimate(Project $project, User $user, bool $manager_in_project): int
    {
        $query = $project->tickets();
        $this->addQueryCondition($query, $manager_in_project, $user, 'assigned_id');

        return (int) $query->sum('estimate_time');
    }

    /**
     * Get estimate of non-TODO tickets in seconds.
     */
    protected function getNonTodoEstimate(Project $project, User $user, bool $manager_in_project): int
    {
        $first_status = $this->status::firstStatus($project->id);
        $first_status_id = $first_status ? $first_status->id : 0;

        $query = $project->tickets()->where('status_id', '!=', $first_status_id);

        $this->addQueryCondition($query, $manager_in_project, $user, 'assigned_id');

        return (int) $query->sum('estimate_time');
    }

    /**
     * Get number of not estimated tickets.
     */
    protected function getNotEstimatedTicketsCount(Project $project, User $user, bool $manager_in_project): int
    {
        $query = $project->tickets()->where('estimate_time', 0);
        $this->addQueryCondition($query, $manager_in_project, $user, 'assigned_id');

        return (int) $query->count();
    }

    /**
     * Get tracked time of project activities in seconds.
     */
    protected function timeTrackedSummary(Project $project, User $user, bool $manager_in_project): int
    {
        $query = $project->timeTrackingActivities();
        $this->addQueryCondition($query, $manager_in_project, $user);

        return (int) $query->sum('tracked');
    }

    /**
     * Get user activity time of project activities in seconds.
     */
    protected function timeActivitySummary(Project $project, User $user, bool $manager_in_project): int
    {
        $query = $project->timeTrackingActivities();
        $this->addQueryCondition($query, $manager_in_project, $user);

        return (int) $query->sum('activity');
    }

    /**
     * Get project time tracking summary - it will contain project tracked summaries for different
     * users.
     */
    protected function getTimeTrackingSummary(Project $project, User $user, bool $manager_in_project): Collection
    {
        return $this->addQueryCondition($project->timeTrackingSummary(), $manager_in_project, $user)
            ->get();
    }

    /**
     * Add condition to query. When user is not manager extra condition should be applied to display
     * only entries assigned to given user.
     *
     * @return mixed
     */
    private function addQueryCondition($query, bool $manager_in_project, User $user, string $column = 'user_id')
    {
        if (! $manager_in_project) {
            $query->where($column, $user->id);
        }

        return $query;
    }

    private function cloneRelation(Project $base_project, string $relation, Model $project): void
    {
        foreach ($base_project->{$relation} as $value) {
            $cloned_value = $value->replicate();
            $cloned_value->push();
            $project->{$relation}()->save($cloned_value);

            $this->map_of_cloned_relations[$relation][$value->id] = $cloned_value->fresh()->id;

            $this->file_service->cloneFilesInProject(
                $value,
                $cloned_value,
                $base_project,
                $project
            );
        }
    }

    private function cloneTickets(Project $base_project, Project $project): void
    {
        $map_of_cloned_tickets = [];

        foreach ($base_project->tickets as $ticket) {
            $cloned_ticket = $ticket->replicate();
            $cloned_ticket->title = $this->createTicketTitle($project, $ticket);

            if (isset($this->map_of_cloned_relations['sprints'][$cloned_ticket->sprint_id])) {
                $cloned_ticket->sprint_id =
                    $this->map_of_cloned_relations['sprints'][$cloned_ticket->sprint_id];
            }

            if (isset($this->map_of_cloned_relations['statuses'][$cloned_ticket->status_id])) {
                $cloned_ticket->status_id =
                    $this->map_of_cloned_relations['statuses'][$cloned_ticket->status_id];
            }

            $cloned_ticket->push();

            foreach ($ticket->comments as $comment) {
                $cloned_comment = $comment->replicate();
                $cloned_comment->push();
                $cloned_ticket->comments()->save($cloned_comment);
            }

            foreach ($ticket->stories as $story) {
                if (isset($this->map_of_cloned_relations['stories'][$story->id])) {
                    $cloned_ticket->stories()->attach(
                        $this->map_of_cloned_relations['stories'][$story->id]
                    );
                }
            }

            $project->tickets()->save($cloned_ticket);
            $this->file_service->cloneFilesInProject(
                $ticket,
                $cloned_ticket,
                $base_project,
                $project
            );

            $map_of_cloned_tickets[$ticket->id] = $cloned_ticket;
        }

        $this->ticket_clone_service->cloneRelatedTickets($base_project->tickets, $map_of_cloned_tickets);
    }

    private function createTicketTitle(Project $project, $ticket): string
    {
        $title_array = explode('-', $ticket->title);
        $title_array[0] = mb_strtoupper($project->short_name);

        return implode('-', $title_array);
    }

    private function clonePermission(Project $base_project, Project $project)
    {
        $cloned_permission = $base_project->permission->replicate();
        $project->permission()->delete();
        $project->permission()->save($cloned_permission);
    }

    private function cloneStatusForCalendar(Project $base_project, Model $project): void
    {
        if (isset($this->map_of_cloned_relations['statuses'][$base_project->status_for_calendar_id])) {
            $project->status_for_calendar_id =
                $this->map_of_cloned_relations['statuses'][$base_project->status_for_calendar_id];
            $project->save();
        }
    }
}
