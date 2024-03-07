<?php

namespace Tests\Feature\App\Console\Commands;

use App\Console\Commands\TrackTime as TrackTimeConsole;
use App\Models\Db\Company;
use App\Models\Db\Integration\Integration;
use App\Models\Db\Integration\IntegrationProvider;
use App\Modules\Integration\Services\TimeTracking\TrackTime;
use Exception;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Log;
use Mockery as m;
use Tests\BrowserKitTestCase;

class TrackTimeTest extends BrowserKitTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_chooses_only_active_integrations_to_run()
    {
        $company = factory(Company::class)->create();

        $hubstaff_integration = $company->integrations()->create([
            'integration_provider_id' => IntegrationProvider::findBySlug(IntegrationProvider::HUBSTAFF)->id,
            'active' => 1,
        ]);

        $other_hubstaff_integration = $company->integrations()->create([
            'integration_provider_id' => IntegrationProvider::findBySlug(IntegrationProvider::HUBSTAFF)->id,
            'active' => 0,
        ]);

        $upwork_integration = $company->integrations()->create([
            'integration_provider_id' => IntegrationProvider::findBySlug(IntegrationProvider::UPWORK)->id,
            'active' => 1,
        ]);

        $track_time = m::mock(TrackTime::class);

        $command = m::mock(TrackTimeConsole::class)->makePartial();
        $command->__construct($track_time);

        $command->shouldReceive('info')->once()->with("Integration #{$hubstaff_integration->id} was started");

        $track_time->shouldReceive('fetch')->once()->with(m::on(function ($arg) use ($hubstaff_integration) {
            return $arg instanceof Integration && $arg->id == $hubstaff_integration->id;
        }))->andReturn(true);

        $command->shouldReceive('info')->once()->with("Integration #{$hubstaff_integration->id} was completed");

        $command->shouldNotReceive('info')->with("Integration #{$other_hubstaff_integration->id} was started");

        $track_time->shouldNotReceive('fetch')->with(m::on(function ($arg) use ($other_hubstaff_integration) {
            return $arg instanceof Integration && $arg->id == $other_hubstaff_integration->id;
        }))->andReturn(true);

        $command->shouldReceive('info')->once()->with("Integration #{$upwork_integration->id} was started");

        $track_time->shouldReceive('fetch')->once()->with(m::on(function ($arg) use ($upwork_integration) {
            return $arg instanceof Integration && $arg->id == $upwork_integration->id;
        }))->andReturn(true);

        $command->shouldReceive('info')->once()->with("Integration #{$upwork_integration->id} was completed");

        $command->handle();
        $this->assertTrue(true);
    }

    /** @test */
    public function it_process_all_the_integrations_in_case_exception_is_thrown_by_previous_one()
    {
        $company = factory(Company::class)->create();

        $hubstaff_integration = $company->integrations()->create([
            'integration_provider_id' => IntegrationProvider::findBySlug(IntegrationProvider::HUBSTAFF)->id,
            'active' => 1,
        ]);

        $upwork_integration = $company->integrations()->create([
            'integration_provider_id' => IntegrationProvider::findBySlug(IntegrationProvider::UPWORK)->id,
            'active' => 1,
        ]);

        $track_time = m::mock(TrackTime::class);

        $command = m::mock(TrackTimeConsole::class)->makePartial();
        $command->__construct($track_time);

        $command->shouldReceive('info')->once()->with("Integration #{$hubstaff_integration->id} was started");

        $track_time->shouldReceive('fetch')->once()->with(m::on(function ($arg) use ($hubstaff_integration) {
            return $arg instanceof Integration && $arg->id == $hubstaff_integration->id;
        }))->andThrow(Exception::class, 'Sample message');

        Log::shouldReceive('error')->once()->with(m::on(function ($arg) {
            return $arg instanceof Exception && $arg->getMessage() == 'Sample message';
        }));

        $command->shouldNotReceive('info')->with("Integration #{$hubstaff_integration->id} was completed");

        $command->shouldReceive('error')->once()->with(m::on(function ($arg) {
            return $arg instanceof Exception && $arg->getMessage() == 'Sample message';
        }));

        $command->shouldReceive('info')->once()->with("Integration #{$upwork_integration->id} was started");

        $track_time->shouldReceive('fetch')->once()->with(m::on(function ($arg) use ($upwork_integration) {
            return $arg instanceof Integration && $arg->id == $upwork_integration->id;
        }))->andReturn(true);

        $command->shouldReceive('info')->once()->with("Integration #{$upwork_integration->id} was completed");

        $command->handle();
        $this->assertTrue(true);
    }
}
