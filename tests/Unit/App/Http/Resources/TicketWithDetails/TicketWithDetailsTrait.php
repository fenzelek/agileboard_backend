<?php

namespace Tests\Unit\App\Http\Resources\TicketWithDetails;

use App\Http\Requests\Request;
use App\Models\Db\Project;
use App\Models\Db\Sprint;
use App\Models\Other\RoleType;
use Illuminate\Support\Collection;
use Mockery\LegacyMockInterface;
use Mockery\MockInterface;
use App\Models\Db\Model;
use App\Models\Db\User;
use Mockery as m;

trait TicketWithDetailsTrait
{
    /**
     * @var m\Expectation|m\ExpectationInterface|m\HigherOrderMessage
     */
    protected $dataReceivedCallGetAttributes;
    /**
     * @var m\Expectation|m\ExpectationInterface|m\HigherOrderMessage
     */
    protected $dataReceivedGetSpintPropertyMethods;
    /**
     * @var m\Expectation|m\ExpectationInterface|m\HigherOrderMessage
     */
    protected $dataReceivedCallGetAttribute;
    /**
     * @var m\Expectation|m\ExpectationInterface|m\HigherOrderMessage
     */
    protected $dataReceivedCallGetRelations;

    /**
     * @return Model|array|LegacyMockInterface|MockInterface
     */

    protected $dataReceivedGetActivityPermissionProperty, $dataReceivedGetProjectProperty;
    protected function mockData()
    {
        $data = m::mock(Model::class);
        $this->dataReceivedGetSpintPropertyMethods = $data->shouldReceive('offsetExists')->with('sprint')->andReturn(false);
        $this->dataReceivedGetActivityPermissionProperty = $data->shouldReceive('getAttribute')->with('activity_permission')->andReturn(false);
        $this->dataReceivedGetProjectProperty = $data->shouldReceive('getAttribute')->with('project')->andReturn(new Project());

        $this->dataReceivedCallGetAttributes = $data->shouldReceive('getAttributes')->andReturn([]);
        $this->dataReceivedCallGetAttribute = $data->shouldReceive('getAttribute')->andReturn(null);

        $this->dataReceivedCallGetRelations = $data->shouldReceive('getRelations')->andReturn([]);

        return $data;
    }

    protected function assertTimeTrackingSummaryRender(): void
    {
        $this->dataReceivedGetSpintPropertyMethods->once();
        $this->dataReceivedGetProjectProperty->once();
        $this->dataReceivedGetActivityPermissionProperty->once();
        $this->dataReceivedCallGetAttributes->times(1);
        $this->dataReceivedCallGetRelations->times(2);
        $this->dataReceivedCallGetAttribute->times(5);
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
    protected function makeRequest()
    {
        $request = m::mock(Request::class);

        return $request;
    }
}
