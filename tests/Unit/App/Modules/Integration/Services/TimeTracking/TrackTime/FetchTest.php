<?php

namespace Tests\Unit\App\Modules\Integration\Services\TimeTracking\TrackTime;

use App\Models\Db\Integration\Integration;
use App\Models\Db\Package;
use App\Models\Other\RoleType;
use App\Modules\Integration\Services\Factory;
use App\Modules\Integration\Services\TimeTracking\TrackTime;
use Tests\BrowserKitTestCase;
use Mockery as m;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class FetchTest extends BrowserKitTestCase
{
    use DatabaseTransactions;

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function it_stops_when_is_not_ready_to_run()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::CEP_ENTERPRISE);

        $integration = new Integration(['id' => 512, 'company_id' => $company->id]);
        $track_time = m::mock(TrackTime::class)->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $handler = m::mock(stdClass::class);
        $handler->shouldReceive('isReadyToRun')->once()->withNoArgs()->andReturn(false);

        $track_time->shouldReceive('setIntegrationAndHandler')->once()
            ->with(m::on(function ($arg) {
                return $arg instanceof Integration && $arg->id = 512;
            }))->passthru();

        $factory = m::mock('overload:' . Factory::class);
        $factory->shouldReceive('make')->once()->andReturn($handler);

        $track_time->shouldNotReceive('fetchProjects');
        $track_time->shouldNotReceive('fetchUsers');
        $track_time->shouldNotReceive('fetchNotes');
        $track_time->shouldNotReceive('fetchActivities');

        $track_time->fetch($integration);
        $this->assertTrue(true);
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function it_runs_all_tasks_when_is_ready_to_run()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::CEP_ENTERPRISE);

        $integration = new Integration(['id' => 512, 'company_id' => $company->id]);
        $track_time = m::mock(TrackTime::class)->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $handler = m::mock(stdClass::class);
        $handler->shouldReceive('isReadyToRun')->once()->withNoArgs()->andReturn(true);

        $track_time->shouldReceive('setIntegrationAndHandler')->once()
            ->with(m::on(function ($arg) {
                return $arg instanceof Integration && $arg->id = 512;
            }))->passthru();

        $factory = m::mock('overload:' . Factory::class);
        $factory->shouldReceive('make')->once()->andReturn($handler);

        $track_time->shouldReceive('fetchProjects')->once()->withNoArgs()->andReturn($track_time);
        $track_time->shouldReceive('fetchUsers')->once()->withNoArgs()->andReturn($track_time);
        $track_time->shouldReceive('fetchNotes')->once()->withNoArgs()->andReturn($track_time);
        $track_time->shouldReceive('fetchActivities')->once()->withNoArgs()->andReturn($track_time);

        $track_time->fetch($integration);
        $this->assertTrue(true);
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function hubstaff_not_active()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::CEP_FREE);

        $integration = new Integration(['id' => 512, 'company_id' => $company->id]);
        $track_time = m::mock(TrackTime::class)->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $track_time->shouldReceive('setIntegrationAndHandler')->never();
        $track_time->fetch($integration);
        $this->assertTrue(true);
    }
}
