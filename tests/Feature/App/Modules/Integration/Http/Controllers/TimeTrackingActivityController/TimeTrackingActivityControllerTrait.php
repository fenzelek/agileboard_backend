<?php

namespace Tests\Feature\App\Modules\Integration\Http\Controllers\TimeTrackingActivityController;

use App\Models\Db\Integration\IntegrationProvider;
use App\Models\Db\Integration\TimeTracking\Activity;
use App\Models\Db\Ticket;

trait TimeTrackingActivityControllerTrait
{
    protected function getTicket($project): Ticket
    {
        return factory(Ticket::class)->create([
            'project_id' => $project->id,
        ]);
    }

    protected function setManualIntegration()
    {
        return $this->manual_integration = $this->company->integrations()->create([
            'integration_provider_id' => IntegrationProvider::findBySlug(IntegrationProvider::MANUAL)->id,
        ]);
    }

    protected function setTimeTrackerIntegration()
    {
        return $this->manual_integration = $this->company->integrations()->create([
            'integration_provider_id' => IntegrationProvider::findBySlug(IntegrationProvider::TIME_TRACKER)->id,
        ]);
    }

    private function createDBActivity($from, $to, $tracked = 600)
    {
        return factory(Activity::class)->create([
            'utc_started_at' => $from,
            'utc_finished_at' => $to,
            'tracked' => $tracked,
            'activity' => 75,
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'ticket_id' => $this->ticket->id,
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
