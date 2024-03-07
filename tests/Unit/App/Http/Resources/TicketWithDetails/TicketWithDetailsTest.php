<?php

namespace Tests\Unit\App\Http\Resources\TicketWithDetails;

use App\Http\Resources\SprintWithDetails;
use App\Http\Resources\TicketWithDetails;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TicketWithDetailsTest extends TestCase
{
    use DatabaseTransactions, TicketWithDetailsTrait;

    /**
     * @feature Sprints
     * @scenario Get Sprint
     * @case Loading Ticket Details
     * @test
     */
    public function toArray_loadTicketDetails()
    {
        //Given
        $request = $this->makeRequest();
        $data = $this->mockData();

        //When
        $resource = new TicketWithDetails($data);
        $resource->toArray($request);

        //Then
        $this->assertTimeTrackingSummaryRender();
    }
}
