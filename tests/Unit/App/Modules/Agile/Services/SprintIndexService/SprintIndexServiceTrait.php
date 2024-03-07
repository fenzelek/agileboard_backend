<?php

namespace Tests\Unit\App\Modules\Agile\Services\SprintIndexService;

use App\Models\Db\Project;
use App\Modules\Agile\Http\Requests\SprintIndex;
use Illuminate\Support\Collection;
use Mockery as m;

trait SprintIndexServiceTrait
{
    /**
     * @return m\Expectation|m\ExpectationInterface|m\HigherOrderMessage
     */
    protected function sprintStartReportCall()
    {
        return $this->sprint_stats->shouldReceive('reportFor')->andReturn(Collection::make());
    }

    /**
     * @return SprintIndex
     */
    protected function makeLocalRequest($stats_type)
    {
        $request = m::mock(SprintIndex::class);
        $request->shouldReceive('input')->with('stats')->andReturn($stats_type);
        $request->shouldReceive('input')->andReturn(null);

        return $request;
    }

    protected function makeProject(): Project
    {
        return factory(Project::class)->make();
    }
}
