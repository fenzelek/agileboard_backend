<?php

namespace Tests\Unit\App\Modules\Agile\Services\SprintStats;

use App\Models\Db\Integration\TimeTracking\Activity;
use App\Models\Db\Project;
use App\Models\Db\Sprint;
use App\Models\Db\Status;
use App\Models\Db\Ticket;
use App\Modules\Agile\Services\SprintStats;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Mockery as m;
use Mockery\LegacyMockInterface;
use Mockery\MockInterface;
use Tests\BrowserKitTestCase;

class SprintStatsTest extends BrowserKitTestCase
{
    use DatabaseTransactions;

    /**
     * @var SprintStats
     */
    private $sprint_stats;
    /**
     * @var Activity|LegacyMockInterface|MockInterface
     */
    private $activity;

    public function setUp():void
    {
        parent::setUp();

        \Auth::shouldReceive('id')->andReturn(1);

        $this->sprint_stats = $this->app->make(SprintStats::class);
    }

    protected function tearDown():void
    {
        m::close();
        parent::tearDown();
    }

    /**
     * @feature Sprints
     * @scenario Get Sprint
     * @case It adds time tracking
     * @test
     */
    public function reportFor_it_adds_time_tracking()
    {
        $project = $this->createProject();

        //When
        $stats = $this->sprint_stats->reportFor(Collection::make(), $project, null);

        //Then
        $this->assertEmpty($stats);
    }

    /**
     * @feature Sprints
     * @scenario Get Sprint
     * @case It found unStarted time tracking
     * @test
     */
    public function reportFor_found_unStarted_estimations()
    {
        $estimate_time = 100;

        $project = $this->createProject();
        $sprint = $this->createSprint($project);
        $ticket = $this->createEstimatedTicket($sprint, $estimate_time);

        //When
        $stats = $this->sprint_stats->reportFor(Collection::make([$sprint]), $project, null);

        //Then
        $this->assertSame($estimate_time, $stats[0]->stats['un_started_estimations']);
        $this->assertSame($estimate_time, $stats[0]->stats['expected_final']);
    }

    /**
     * @feature Sprints
     * @scenario Get Sprint
     * @case It found tracked time tickets
     * @test
     */
    public function reportFor_found_tracked_time_tickets()
    {
        $tracked_time = 100;

        $project = $this->createProject();
        $sprint = $this->createSprint($project);
        $ticket = $this->createTicket($sprint);
        $activity = $this->createActivity($ticket, $tracked_time);

        //When
        $stats = $this->sprint_stats->reportFor(Collection::make([$sprint]), $project, null);

        //Then
        $this->assertSame(0, $stats[0]->stats['un_started_estimations']);
        $this->assertSame($tracked_time, $stats[0]->stats['expected_final']);
    }

    /**
     * @feature Sprints
     * @scenario Get Sprint
     * @case It found in progress time tickets and returns estimation
     * @test
     */
    public function reportFor_found_in_progress_time_tickets_and_returns_estimated_time()
    {
        $estimated_time = 99;
        $tracked_time = 98;

        $status_in_progress = factory(Status::class)->create();

        $project = $this->createProject($status_in_progress);
        $sprint = $this->createSprint($project);
        $ticket = $this->createEstimatedTicketWithStatus($sprint, $estimated_time, $status_in_progress);
        $activity = $this->createActivity($ticket, $tracked_time);

        //When
        $stats = $this->sprint_stats->reportFor(Collection::make([$sprint]), $project, null);

        //Then
        $this->assertSame(0, $stats[0]->stats['un_started_estimations']);
        $this->assertSame($estimated_time, $stats[0]->stats['expected_final']);
    }

    /**
     * @feature Sprints
     * @scenario Get Sprint
     * @case It found in progress time tickets and returns tracked
     * @test
     */
    public function reportFor_found_in_progress_time_tickets_and_returns_tracked_time()
    {
        $estimated_time = 99;
        $tracked_time = 100;

        $status_in_progress = factory(Status::class)->create();

        $project = $this->createProject($status_in_progress);
        $sprint = $this->createSprint($project);
        $ticket = $this->createEstimatedTicketWithStatus($sprint, $estimated_time, $status_in_progress);
        $activity = $this->createActivity($ticket, $tracked_time);

        //When
        $stats = $this->sprint_stats->reportFor(Collection::make([$sprint]), $project, null);

        //Then
        $this->assertSame(0, $stats[0]->stats['un_started_estimations']);
        $this->assertSame($tracked_time, $stats[0]->stats['expected_final']);
    }

    protected function createProject(Status $status = null): Project
    {
        /**
         * @var Project $project
         */
        $project = factory(Project::class)->make();

        $project->status_for_calendar_id = $status->id ?? null;
        $project->save();

        return $project;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|mixed
     */
    private function createSprint(Project $project)
    {
        /**
         * @var Sprint $sprint
         */
        $sprint = factory(Sprint::class)->make();
        $sprint->project()->associate($project);
        $sprint->save();

        return $sprint;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|mixed
     */
    private function createEstimatedTicket(Sprint $sprint, int $estimate_time):Ticket
    {
        $ticket = $this->createTicket($sprint, [
            'estimate_time' => $estimate_time,
        ]);

        return $ticket;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|mixed
     */
    private function createEstimatedTicketWithStatus(Sprint $sprint, int $estimate_time, Status $status):Ticket
    {
        $ticket = $this->createTicket($sprint, [
            'estimate_time' => $estimate_time,
            'status_id' => $status->id,
        ]);

        return $ticket;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|mixed
     */
    private function createTicket(Sprint $sprint, array $attributes = []):Ticket
    {
        /**
         * @var Ticket $ticket
         */
        $ticket = factory(Ticket::class)->make($attributes);

        $ticket->sprint()->associate($sprint);
        $ticket->project()->associate($sprint->project);
        $ticket->save();

        return $ticket;
    }

    private function createActivity(Ticket $ticket, int $tracked):Activity
    {
        /**
         * @var Activity $activity
         */
        $activity = factory(Activity::class)->make([
            'tracked' => $tracked,
        ]);
        $activity->ticket()->associate($ticket);
        $activity->save();

        return $activity;
    }
}
