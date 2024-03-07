<?php
declare(strict_types=1);

namespace Tests\Unit\App\Modules\Interaction\Services\GetProjectUsers\Get;

use App\Models\Db\User;
use App\Modules\Interaction\Services\GetProjectUsers;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Tests\TestCase;

class GetProjectUsersTest extends TestCase
{
    use GetProjectUsersTrait;
    use DatabaseTransactions;

    /**
     * @feature Ticket
     * @scenario Add Comment to ticket
     * @case Comment with no interaction
     *
     * @test
     * @Expectation return empty notification collection
     */
    public function extract_commentWithCommentWithNoInteraction()
    {
        //GIVEN
        $company = $this->createCompany('test company');
        $project = $this->createProject($company);

        $user_1_name = 'test name user1';
        $user_1 = $this->createNewUser(['first_name' => $user_1_name]);
        $project->users()->attach($user_1);

        $user_2_name = 'test name user2';
        $user_2 = $this->createNewUser(['first_name' => $user_2_name]);
        $project->users()->attach($user_2);

        $interaction = $this->mockInteraction($project->id);

        /** @var GetProjectUsers $users_group_members */
        $users_group_members = $this->app->make(GetProjectUsers::class);

        //WHEN
        $result = $users_group_members->get($interaction);

        //THEN
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);

        /** @var User $user */
        $user = $result->first();
        $this->assertEquals($user_1_name, $user->first_name);

        /** @var User $user */
        $user = $result->last();
        $this->assertEquals($user_2_name, $user->first_name);
    }
}