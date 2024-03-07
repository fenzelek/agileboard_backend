<?php

namespace Tests\Unit\App\Modules\Agile\Services\SprintIndexService;

use App\Models\Db\Integration\TimeTracking\Activity;
use App\Modules\Agile\Services\SprintIndexService;
use App\Modules\Agile\Services\SprintStats;
use App\Modules\Agile\Services\TicketIndexService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery as m;
use Mockery\LegacyMockInterface;
use Mockery\MockInterface;
use Tests\BrowserKitTestCase;

class SprintIndexServiceTest extends BrowserKitTestCase
{
    use DatabaseTransactions, SprintIndexServiceTrait;
    /**
     * @var SprintIndexService
     */
    private $service;
    /**
     * @var SprintStats|LegacyMockInterface|MockInterface
     */
    private $sprint_stats;

    public function setUp():void
    {
        parent::setUp();

        $this->sprint_stats = m::mock(SprintStats::class);
        $this->service = $this->app->make(SprintIndexService::class, [
            'sprint_stats' => $this->sprint_stats,
        ]);
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
    public function getData_add_time_tracking()
    {
        $ticket_index_service = m::mock(TicketIndexService::class);
        $activity = m::mock(Activity::class);
        $project = $this->makeProject();
        $request = $this->makeLocalRequest('all');
        $sprint_stats_report_call = $this->sprintStartReportCall();

        //When
        $stats = $this->service->getData($request, $project, $activity, $ticket_index_service);

        //Then
        $sprint_stats_report_call->times(1);
        $this->assertEmpty($stats);
    }
}
