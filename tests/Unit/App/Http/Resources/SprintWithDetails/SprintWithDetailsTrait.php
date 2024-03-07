<?php

namespace Tests\Unit\App\Http\Resources\SprintWithDetails;

use App\Http\Requests\Request;
use App\Models\Other\RoleType;
use Illuminate\Support\Collection;
use Mockery\LegacyMockInterface;
use Mockery\MockInterface;
use App\Models\Db\Model;
use App\Models\Db\User;
use Mockery as m;

trait SprintWithDetailsTrait
{
    /**
     * @var m\Expectation|m\ExpectationInterface|m\HigherOrderMessage
     */
    protected $dataReceivedCallGetAttributes;
    /**
     * @var m\Expectation|m\ExpectationInterface|m\HigherOrderMessage
     */
    protected $dataReceivedCallTrackingMethods;
    /**
     * @var m\Expectation|m\ExpectationInterface|m\HigherOrderMessage
     */
    protected $dataReceivedCallGetAttribute;
    /**
     * @var m\Expectation|m\ExpectationInterface|m\HigherOrderMessage
     */
    protected $dataReceivedCallGetRelations;

    protected function assertStatsWasLoaded()
    {
        $this->dataReceivedCallTrackingMethods->times(1);
        $this->dataReceivedCallGetAttributes->times(1);
        $this->dataReceivedCallGetRelations->times(2);
        $this->dataReceivedCallGetAttribute->times(7);
    }

    /**
     * @return Model|array|LegacyMockInterface|MockInterface
     */
    protected function mockData()
    {
        $data = m::mock(Model::class);
        $this->dataReceivedCallTrackingMethods = $data->shouldReceive('getAttribute')->with('timeTrackingGeneralSummary')->andReturn(Collection::make());
        $this->dataReceivedCallTrackingMethods = $data->shouldReceive('getAttribute')->with('ticketsGeneralSummary')->andReturn(Collection::make());
        $this->dataReceivedCallGetAttributes = $data->shouldReceive('getAttributes')->andReturn([]);
        $this->dataReceivedCallGetRelations = $data->shouldReceive('getRelations')->andReturn([]);
        $this->dataReceivedCallGetAttribute = $data->shouldReceive('getAttribute')->andReturn(null);

        return $data;
    }

    protected function assertStatsNotLoaded(): void
    {
        $this->dataReceivedCallTrackingMethods->never();
        $this->dataReceivedCallGetAttributes->times(1);
        $this->dataReceivedCallGetRelations->times(2);
        $this->dataReceivedCallGetAttribute->times(6);
    }

    /**
     * @param $request
     */
    protected function makeUser($request): void
    {
        $user = m::mock(User::class);
        $user->shouldReceive('getRoles')->andReturn([RoleType::OWNER]);
        $request->shouldReceive('user')->andReturn($user);
    }

    /**
     * @return Request|array|LegacyMockInterface|MockInterface
     */
    protected function makeRequest($stats_type)
    {
        $request = m::mock(Request::class);
        $request->shouldReceive('input')->with('stats')->andReturn($stats_type);

        return $request;
    }
}
