<?php

namespace Tests\Unit\App\Modules\Integration\Services\ManualActivityTools;

use App\Models\Db\Integration\IntegrationProvider;
use App\Models\Db\Integration\TimeTracking\Activity;
use App\Models\Db\Integration\TimeTracking\ManualActivityHistory;
use App\Models\Db\Project;
use App\Models\Db\Role;
use App\Models\Db\Ticket;
use App\Models\Other\RoleType;
use App\Modules\Integration\Http\Requests\TimeTracking\RemoveManualActivities;
use App\Modules\Integration\Http\Requests\TimeTracking\StoreActivity;
use App\Modules\Integration\Services\ManualActivityTools\FreeTimeSlotSearch;
use Illuminate\Support\Collection;

trait ManualActivityToolsTrait
{
    public function getTicket(Project $project)
    {
        return factory(Ticket::class)->create([
            'project_id' => $project->id,
        ]);
    }

    /**
     * @return StoreActivity
     */
    public function getStoreActivity($from = '2021-10-01 11:00:00', $to = '2021-10-01 11:10:00'): StoreActivity
    {
        $data = [
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'ticket_id' => $this->ticket->id,
            'from' => $from,
            'to' => $to,
        ];

        $store_activity = new StoreActivity($data);

        return $store_activity;
    }

    public function getRemoveActivities(array $activity_ids)
    {
        $data = [
            'activities' => $activity_ids,
            'selected_company_id' => $this->company->id,
        ];

        return new RemoveManualActivities($data);
    }

    public function getFreeSlots(Collection $free_slots): FreeTimeSlotSearch
    {
        $free_slots_search = \Mockery::mock(FreeTimeSlotSearch::class);
        $free_slots_search->shouldReceive('lookup')->andReturn($free_slots);

        return $free_slots_search;
    }

    /**
     * Project creation and assign user.
     *
     * @param $company
     * @param $user_id
     *
     * @return Project
     */
    protected function getProject($company, $user_id, $role_type = RoleType::OWNER)
    {
        $project = factory(Project::class)->create([
            'company_id' => $company->id,
        ]);

        $project->users()->attach($user_id, ['role_id' => Role::findByName($role_type)->id]);

        return $project;
    }

    protected function setManualIntegration()
    {
        return $this->manual_integration = $this->company->integrations()->create([
            'integration_provider_id' => IntegrationProvider::findBySlug(IntegrationProvider::MANUAL)->id,
        ]);
    }

    protected function setTimeTrackerIntegration($company)
    {
        return $company->integrations()->create([
            'integration_provider_id' => IntegrationProvider::findBySlug(IntegrationProvider::TIME_TRACKER)->id,
        ]);
    }

    /**
     * @return void
     */
    protected function setDeletedActivityState($from, $to): void
    {
        $activity =
            $this->createDBActivity(
                $this->user,
                $from,
                $to,
                $this->project,
                $this->ticket
            );
        $activity->delete();
    }

    protected function assertActivityExist($store_activity, $integration): void
    {
        $history = ManualActivityHistory::first();

        $this->assertDatabaseHas('time_tracking_activities', [
            'user_id' => $this->user->id,
            'integration_id' => $integration->id,
            'external_activity_id' => 'manual' . $history->id,
            'utc_started_at' => $store_activity->getFrom(),
            'utc_finished_at' => $store_activity->getTo(),
        ]);
    }

    /**
     * @param StoreActivity $store_activity
     *
     * @return void
     */
    protected function assertActivityHistoryExist(StoreActivity $store_activity): void
    {
        $history = ManualActivityHistory::first();

        $this->assertDatabaseHas('time_tracking_manual_activity_history', [
            'id' => $history->id,
            'author_id' => $this->user->id,
            'user_id' => $this->user->id,
            'from' => $store_activity->getFrom(),
            'to' => $store_activity->getTo(),
        ]);
    }

    private function createDBActivity($user, $from, $to, $project, $ticket, $tracked = 600)
    {
        return factory(Activity::class)->create([
            'utc_started_at' => $from,
            'utc_finished_at' => $to,
            'tracked' => $tracked,
            'activity' => 75,
            'user_id' => $user->id,
            'project_id' => $project->id,
            'ticket_id' => $ticket->id,
        ]);
    }

    private function createDBActivityIntegration($user, $from, $to, $integration_id, $external_activity_id)
    {
        return factory(Activity::class)->create([
            'integration_id' => $integration_id,
            'external_activity_id' => $external_activity_id,
            'utc_started_at' => $from,
            'utc_finished_at' => $to,
            'tracked' => 600,
            'activity' => 75,
            'user_id' => $user->id,
            'project_id' => $this->project->id,
            'ticket_id' => $this->ticket->id,
        ]);
    }
}
