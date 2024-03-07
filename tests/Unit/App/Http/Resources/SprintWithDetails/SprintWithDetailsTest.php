<?php

namespace Tests\Unit\App\Http\Resources\SprintWithDetails;

use App\Http\Resources\SprintWithDetails;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SprintWithDetailsTest extends TestCase
{
    use DatabaseTransactions, SprintWithDetailsTrait;

    /**
     * @feature Sprints
     * @scenario Get Sprint
     * @case Loading without Stats
     * @test
     */
    public function toArray_it_prevent_load_stats()
    {
        //Given
        $request = $this->makeRequest(null);
        $data = $this->mockData();

        //When
        $resource = new SprintWithDetails($data);
        $resource->toArray($request);

        //Then
        $this->assertStatsNotLoaded();
    }

    /**
     * @feature Sprints
     * @scenario Get Sprint
     * @case Loading with Stats
     * @test
     */
    public function toArray_it_load_stats()
    {
        //Given
        $request = $this->makeRequest('min');
        $this->makeUser($request);
        $data = $this->mockData();
        $resource = new SprintWithDetails($data);

        //When
        $response = $resource->toArray($request);

        //Then
        $this->assertStatsWasLoaded();
    }
}
