<?php
declare(strict_types=1);

namespace Tests\Unit\App\Modules\Interaction\Services\GetProjectUsersFactory\GetGroupUsers;

use App\Modules\Interaction\Contracts\IUsersGroupMembers;
use Illuminate\Container\Container;
use Mockery as m;

trait GetProjectUsersFactoryTrait
{
    private function mockGetProjectUsers()
    {
        return m::mock(IUsersGroupMembers::class);
    }
    private function mockApp()
    {
        return m::mock(Container::class);
    }
}