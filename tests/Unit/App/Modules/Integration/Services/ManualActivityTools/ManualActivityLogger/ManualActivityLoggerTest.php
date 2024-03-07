<?php

namespace Tests\Unit\App\Modules\Integration\Services\ManualActivityTools\ManualActivityLogger;

use App\Models\Db\Company;
use App\Models\Db\Integration\TimeTracking\ManualActivityHistory;
use App\Models\Other\RoleType;
use App\Modules\Integration\Services\ManualActivityTools\ManualActivityLogger;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Unit\App\Modules\Integration\Services\ManualActivityTools\ManualActivityToolsTrait;

class ManualActivityLoggerTest extends TestCase
{
    use DatabaseTransactions;
    use ManualActivityToolsTrait;

    /**
     * @var ManualActivityLogger|mixed
     */
    private ManualActivityLogger $manual_activity_logger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $this->company = factory(Company::class)->create();
        $this->project = $this->getProject($this->company, $this->user, RoleType::DEVELOPER);
        $this->ticket = $this->getTicket($this->project);

        $this->manual_activity_logger = $this->app->make(ManualActivityLogger::class);
    }

    /**
     * @feature Time Tracker
     * @scenario Manual activity logger
     * @case success, logger add
     *
     * @test
     */
    public function success_add_log()
    {
        //GIVEN
        $store_activity = $this->getStoreActivity('2021-10-01 11:00:00', '2021-10-01 11:10:00');

        //WHEN
        $response = $this->manual_activity_logger->log($store_activity, $this->user);

        //THEN
        $this->assertInstanceOf(ManualActivityHistory::class, $response);
        $this->assertDatabaseCount('time_tracking_manual_activity_history', 1);
        $this->assertDatabaseHas('time_tracking_manual_activity_history', [
            'author_id' => $this->user->id,
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'ticket_id' => $this->ticket->id,
            'from' => '2021-10-01 11:00:00',
            'to' => '2021-10-01 11:10:00',
        ]);
    }
}
