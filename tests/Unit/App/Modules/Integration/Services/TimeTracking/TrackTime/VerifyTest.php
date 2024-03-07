<?php

namespace Tests\Unit\App\Modules\Integration\Services\TimeTracking\TrackTime;

use App\Models\Db\Integration\Integration;
use App\Modules\Integration\Services\TimeTracking\TrackTime;
use Exception;
use Tests\BrowserKitTestCase;
use Mockery as m;

class VerifyTest extends BrowserKitTestCase
{
    /** @test */
    public function it_returns_true_when_fetched_projects_and_users()
    {
        $integration = new Integration(['id' => 512]);
        $track_time = m::mock(TrackTime::class)->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $track_time->shouldReceive('setIntegrationAndHandler')->once()
            ->with(m::on(function ($arg) {
                return $arg instanceof Integration && $arg->id = 512;
            }));

        $track_time->shouldReceive('fetchProjects')->once()->withNoArgs()->andReturn($track_time);
        $track_time->shouldReceive('fetchUsers')->once()->withNoArgs()->andReturn($track_time);

        $this->assertTrue($track_time->verify($integration));
    }

    /** @test */
    public function it_returns_false_when_fetching_users_failed()
    {
        $integration = new Integration(['id' => 512]);
        $track_time = m::mock(TrackTime::class)->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $track_time->shouldReceive('setIntegrationAndHandler')->once()
            ->with(m::on(function ($arg) {
                return $arg instanceof Integration && $arg->id = 512;
            }));

        $track_time->shouldReceive('fetchProjects')->once()->withNoArgs()->andReturn($track_time);
        $track_time->shouldReceive('fetchUsers')->once()->withNoArgs()->andThrow(Exception::class);

        $this->assertFalse($track_time->verify($integration));
    }

    /** @test */
    public function it_returns_false_when_fetching_projects_failed()
    {
        $integration = new Integration(['id' => 512]);
        $track_time = m::mock(TrackTime::class)->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $track_time->shouldReceive('setIntegrationAndHandler')->once()
            ->with(m::on(function ($arg) {
                return $arg instanceof Integration && $arg->id = 512;
            }));

        $track_time->shouldReceive('fetchProjects')->once()->withNoArgs()
            ->andThrow(Exception::class);
        $track_time->shouldNotReceive('fetchUsers');

        $this->assertFalse($track_time->verify($integration));
    }
}
