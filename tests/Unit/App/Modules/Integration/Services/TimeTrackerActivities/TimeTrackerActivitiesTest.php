<?php

declare(strict_types=1);

namespace Tests\Unit\App\Modules\Integration\Services\TimeTrackerActivities;

use App\Models\Db\Integration\TimeTracking\Activity;
use App\Models\Db\Ticket;
use App\Modules\Integration\Models\ActivityExportDto;
use App\Modules\Integration\Models\ActivitySummaryExportDto;
use App\Modules\Integration\Services\TimeTrackerActivities;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TimeTrackerActivitiesTest extends TestCase
{
    use DatabaseTransactions;
    use TimeTrackerActivitiesTrait;

    private TimeTrackerActivities $service;

    /**
     * @test
     * @dataProvider activityDataProvider
     */
    public function getActivitiesForExport_ShouldReturnCorrectActivitiesData(
        bool $activity_has_user,
        ?string $user_first_name,
        ?string $user_last_name,
        ?string $utc_started_at,
        ?string $utc_finished_at,
        int $tracked_seconds,
        int $activity_seconds,
        string $project_name,
        string $sprint_name,
        ?string $ticket_title
    ) {
        //Given
        $this->createActivityForExport(
            $activity_has_user,
            $user_first_name,
            $user_last_name,
            $utc_started_at,
            $utc_finished_at,
            $tracked_seconds,
            $activity_seconds,
            $project_name,
            $sprint_name,
            $ticket_title
        );
        factory(Activity::class)->create();

        //When
        $result = $this->service->getActivitiesForExport(Activity::all());

        //Then
        $this->assertCount(2, $result);
        /** @var ActivityExportDto $activity */
        $activity = $result->first();
        $this->assertSame($tracked_seconds, $activity->getTracked());
        $this->assertSame($user_first_name, $activity->getUserFirstName());
        $this->assertSame($user_last_name, $activity->getUserLastName());
        $this->assertSame($ticket_title, $activity->getTicketTitle());
        $this->assertSame($project_name, $activity->getProjectName());
        $this->assertSame($sprint_name, $activity->getSprintName());
        $this->assertSame(
            $utc_started_at,
            $activity->getUtcStartedAt() ? $activity->getUtcStartedAt()->toDateTimeString() : null
        );
        $this->assertSame(
            $utc_finished_at,
            $activity->getUtcFinishedAt() ? $activity->getUtcFinishedAt()->toDateTimeString() : null
        );
    }

    /**
     * @test
     * @dataProvider activityForSummaryDataProvider
     */
    public function getSummaryActivitiesForExport_ShouldReturnCorrectActivitiesSummaryData(
        bool $activity_has_user,
        string $user_first_name,
        string $user_last_name,
        bool $has_ticket,
        ?string $ticket_title,
        ?string $ticket_description,
        int $estimate_time,
        array $tracked_times,
        ?string $sprint_name,
        ?string $project_name
    ) {
        $ticket = $this->createTicketActivityForSummaryExport(
            $activity_has_user,
            $user_first_name,
            $user_last_name,
            $has_ticket,
            $ticket_title,
            $ticket_description,
            $estimate_time,
            $tracked_times,
            $sprint_name,
            $project_name
        );
        factory(Activity::class)->create();

        //When
        $result = $this->service->getSummaryActivitiesForExport(Activity::all());

        //Then
        $this->assertCount(2, $result);
        /** @var ActivitySummaryExportDto $activity */
        $activity = $result->first();

        $this->assertSame($ticket ? $ticket->id : null, $activity->getTicketId());
        $this->assertSame($user_first_name, $activity->getUserFirstName());
        $this->assertSame($user_last_name, $activity->getUserLastName());
        $this->assertSame($ticket_title??'', $activity->getTicketTitle());
        $this->assertSame($ticket_description??'', $activity->getTicketDescription());
        $this->assertSame(array_sum($tracked_times), $activity->getTotalTime());
        $this->assertSame($estimate_time, $activity->getEstimate());
        $this->assertSame($sprint_name??'', $activity->getSprintName());
        $this->assertSame($project_name??'', $activity->getProjectName());
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(TimeTrackerActivities::class);
        Ticket::unsetEventDispatcher();
        Activity::preventLazyLoading();
    }
    
    protected function tearDown(): void
    {
        parent::tearDown();
        Activity::preventLazyLoading(false);
    }
}
