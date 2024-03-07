<?php
declare(strict_types=1);

namespace Tests\Unit\App\Modules\Interaction\Services\GetProjectUsersFactory\GetGroupUsers;

use App\Models\Other\Interaction\NotifiableGroupType;
use App\Modules\Interaction\Services\GetProjectUsers;
use App\Modules\Interaction\Services\GetProjectUsersFactory;
use Tests\TestCase;

class GetProjectUsersFactoryTest extends TestCase
{
    use GetProjectUsersFactoryTrait;

    /**
     * @feature Ticket
     * @scenario Add Comment to ticket
     * @case Comment with one interaction to user
     *
     * @test
     * @Expectation valid method executed once
     */
    public function extract_commentWithOneInteractionToUser()
    {
        //GIVEN
        $users_group_members = $this->mockGetProjectUsers();

        $app = $this->mockApp();
        $expectation = $app->shouldReceive('make')
            ->with(GetProjectUsers::class)
            ->andReturns($users_group_members);

        /** @var GetProjectUsersFactory $get_project_users_factory */
        $get_project_users_factory = $this->app->make(GetProjectUsersFactory::class, [
            'app' => $app
        ]);

        //WHEN
        $get_project_users_factory->create(NotifiableGroupType::ALL);

        //THEN
        $expectation->once();
    }
}