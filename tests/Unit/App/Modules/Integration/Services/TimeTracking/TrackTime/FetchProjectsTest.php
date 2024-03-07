<?php

namespace Tests\Unit\App\Modules\Integration\Services\TimeTracking\TrackTime;

use App\Models\Db\Integration\Integration;
use App\Modules\Integration\Services\Factory;
use App\Modules\Integration\Services\TimeTracking\Processor\ActivitiesProcessor;
use App\Modules\Integration\Services\TimeTracking\Processor\NotesProcessor;
use App\Modules\Integration\Services\TimeTracking\Processor\ProjectsProcessor;
use App\Modules\Integration\Services\TimeTracking\Processor\UsersProcessor;
use App\Modules\Integration\Services\TimeTracking\TrackTime;
use Illuminate\Support\Collection;
use stdClass;
use Tests\BrowserKitTestCase;
use Mockery as m;

class FetchProjectsTest extends BrowserKitTestCase
{
    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function it_fetches_and_saves_projects()
    {
        $activities_processor = m::mock(ActivitiesProcessor::class);
        $notes_processor = m::mock(NotesProcessor::class);
        $projects_processor = m::mock(ProjectsProcessor::class);
        $users_processor = m::mock(UsersProcessor::class);

        $integration = new Integration(['id' => 512]);
        $handler = m::mock(stdClass::class);

        $factory = m::mock('overload:' . Factory::class);
        $factory->shouldReceive('make')->once()->andReturn($handler);

        $track_time = m::mock(TrackTime::class)->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $track_time->__construct(
            $activities_processor,
            $notes_processor,
            $projects_processor,
            $users_processor
        );

        $collection = collect([1, 2]);

        $handler->shouldReceive('projects')->once()->withNoArgs()->andReturn($collection);

        $track_time->shouldReceive('saveProjects')->once()
            ->with(m::on(function ($arg) use ($collection) {
                return $arg instanceof Collection && $arg->all() == $collection->all();
            }))->passthru();
        $track_time->shouldReceive('saveInfo')->once()->withNoArgs();

        $projects_processor->shouldReceive('save')->once()
            ->with(m::on(function ($arg) {
                return $arg instanceof Integration && $arg->id == 512;
            }), m::on(function ($arg) use ($collection) {
                return $arg instanceof Collection && $arg->all() == $collection->all();
            }));

        $track_time->shouldNotReceive('saveActivities');
        $track_time->shouldNotReceive('saveUsers');
        $track_time->shouldNotReceive('saveNotes');

        $track_time->fetchProjects($integration);
        $this->assertTrue(true);
    }
}
